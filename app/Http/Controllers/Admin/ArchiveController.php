<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Core\ArchiveDatabase;
use PDO;

class ArchiveController extends Controller
{
    protected ?PDO $archiveDb = null; // Archive DB (HFSQL)
    protected ?string $archiveDbError = null;

    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
        
        try {
            $this->archiveDb = ArchiveDatabase::getInstance()->getConnection();
        } catch (\Exception $e) {
            $this->archiveDbError = $e->getMessage();
        }
    }

    public function index()
    {
        $user = session('user') ?? null;
        if (!$user) {
            return $this->redirect('/login');
        }

        $role = strtolower($user['role_code'] ?? '');
        $userWilayaId = (int)($user['wilaya_id'] ?? $user['IDWilayaa'] ?? 0);

        $selectedWilaya = request()->all()['wilaya_id'] ?? null;
        if ($role === 'dfep' && $userWilayaId > 0) {
            $selectedWilaya = $userWilayaId;
        }

        $data = [
            'title' => 'بوابة الأرشيف الوطني التاريخي (HFSQL) | Archive Portal',
            'error' => $this->archiveDbError ?? null,
            'current_wilaya' => $selectedWilaya,
            'wilayas' => [],
            'total_stagiaires' => 0,
            'total_offres' => 0,
            'total_etablissements' => 0,
            'recent_offres' => [],
            'gender_breakdown' => [],
            'top_specialites' => []
        ];

        // Caching Configuration
        $nocache = isset(request()->all()['nocache']) && request()->all()['nocache'] == 1;
        $cacheDir = defined('BASE_PATH') ? BASE_PATH . '/storage/cache' : dirname(dirname(dirname(__DIR__))) . '/storage/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $cacheExpiry = 86400; // 24 Hours (Historical Archive Data is Static)

        $wilayasCacheFile = $cacheDir . '/archive_wilayas.json';
        $wilayasCached = false;

        if (!$nocache && file_exists($wilayasCacheFile) && (time() - filemtime($wilayasCacheFile) < $cacheExpiry)) {
            $cachedWilayas = json_decode(file_get_contents($wilayasCacheFile), true);
            if (is_array($cachedWilayas)) {
                $data['wilayas'] = $cachedWilayas;
                $wilayasCached = true;
            }
        }

        if ($this->archiveDb) {
            try {
                if (!$wilayasCached) {
                    // Fetch list of Wilayas in the Archive
                    $rawWilayas = $this->archiveDb->query("
                        SELECT IDWilayaa as id, Code as code, Nom as nom_ar, NomFr as nom_fr 
                        FROM wilaya 
                        ORDER BY Code ASC
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    $data['wilayas'] = $this->cleanArchiveData($rawWilayas);
                    @file_put_contents($wilayasCacheFile, json_encode($data['wilayas'], JSON_UNESCAPED_UNICODE));
                }

                if ($role === 'dfep' && $userWilayaId > 0) {
                    $data['wilayas'] = array_values(array_filter($data['wilayas'], function($w) use ($userWilayaId) {
                        return (int)$w['id'] === $userWilayaId;
                    }));
                }

                // If no wilaya is selected, default to the first one
                if (empty($data['current_wilaya']) && !empty($data['wilayas'])) {
                    $data['current_wilaya'] = $data['wilayas'][0]['id'];
                }

                $wilayaId = $data['current_wilaya'];

                if (!empty($wilayaId)) {
                    $wilayaCacheFile = $cacheDir . "/archive_wilaya_{$wilayaId}.json";
                    $wilayaCached = false;

                    if (!$nocache && file_exists($wilayaCacheFile) && (time() - filemtime($wilayaCacheFile) < $cacheExpiry)) {
                        $cachedData = json_decode(file_get_contents($wilayaCacheFile), true);
                        if (is_array($cachedData)) {
                            $data['total_stagiaires'] = $cachedData['total_stagiaires'] ?? 0;
                            $data['total_offres'] = $cachedData['total_offres'] ?? 0;
                            $data['total_etablissements'] = $cachedData['total_etablissements'] ?? 0;
                            $data['recent_offres'] = $cachedData['recent_offres'] ?? [];
                            $data['gender_breakdown'] = $cachedData['gender_breakdown'] ?? [];
                            $data['top_specialites'] = $cachedData['top_specialites'] ?? [];
                            $wilayaCached = true;
                        }
                    }

                    if (!$wilayaCached) {
                        // 1. Total Archived Students in this Wilaya (Defensive check using section.IDDFEP)
                        try {
                            $stmtStg = $this->archiveDb->prepare("
                                SELECT COUNT(a.IDapprenant) 
                                FROM apprenant a
                                LEFT JOIN section s ON a.IDSection = s.IDSection
                                WHERE s.IDDFEP = :wilaya_id
                            ");
                            $stmtStg->execute(['wilaya_id' => $wilayaId]);
                            $data['total_stagiaires'] = (int)$stmtStg->fetchColumn();
                        } catch (\Exception $ex1) {
                            // Fallback using candidat
                            try {
                                $stmtStg = $this->archiveDb->prepare("
                                    SELECT COUNT(a.IDapprenant) 
                                    FROM apprenant a
                                    LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                                    WHERE c.IDWilayaa = :wilaya_id
                                ");
                                $stmtStg->execute(['wilaya_id' => $wilayaId]);
                                $data['total_stagiaires'] = (int)$stmtStg->fetchColumn();
                            } catch (\Exception $ex2) {
                                $data['total_stagiaires'] = 0;
                            }
                        }

                        // 2. Total Archived Offers in this Wilaya
                        try {
                            $stmtOff = $this->archiveDb->prepare("
                                SELECT COUNT(*) 
                                FROM offre o
                                LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                                WHERE e.IDDFEP = :wilaya_id
                            ");
                            $stmtOff->execute(['wilaya_id' => $wilayaId]);
                            $data['total_offres'] = (int)$stmtOff->fetchColumn();
                        } catch (\Exception $ex) {
                            $data['total_offres'] = (int)$this->archiveDb->query("SELECT COUNT(*) FROM offre")->fetchColumn();
                        }

                        // 3. Total Etablissements in this Wilaya
                        try {
                            $stmtEts = $this->archiveDb->prepare("
                                SELECT COUNT(*) 
                                FROM etablissement 
                                WHERE IDDFEP = :wilaya_id
                            ");
                            $stmtEts->execute(['wilaya_id' => $wilayaId]);
                            $data['total_etablissements'] = (int)$stmtEts->fetchColumn();
                        } catch (\Exception $ex) {
                            $data['total_etablissements'] = 0;
                        }

                        // 4. Detailed active offers list for this Wilaya (Highly optimized with LIMIT 50 and IDOffre Index)
                        try {
                            $stmtOffList = $this->archiveDb->prepare("
                                SELECT o.IDOffre as code, sp.Nom as spec_ar, o.NbrInscr as capacite,
                                       e.Nom as etab_ar, o.DateD as date_debut, o.DateF as date_fin
                                FROM offre o
                                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                                LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                                WHERE e.IDDFEP = :wilaya_id
                                ORDER BY o.IDOffre DESC
                                LIMIT 50
                            ");
                            $stmtOffList->execute(['wilaya_id' => $wilayaId]);
                            $data['recent_offres'] = $this->cleanArchiveData($stmtOffList->fetchAll(PDO::FETCH_ASSOC));
                        } catch (\Exception $ex) {
                            $data['recent_offres'] = [];
                        }

                        // 5. Gender breakdown for selected Wilaya
                        try {
                            $stmtGender = $this->archiveDb->prepare("
                                SELECT c.Civ as sexe, COUNT(a.IDapprenant) as count
                                FROM apprenant a
                                LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                                LEFT JOIN section s ON a.IDSection = s.IDSection
                                WHERE s.IDDFEP = :wilaya_id
                                GROUP BY c.Civ
                            ");
                            $stmtGender->execute(['wilaya_id' => $wilayaId]);
                            $data['gender_breakdown'] = $this->cleanArchiveData($stmtGender->fetchAll(PDO::FETCH_ASSOC));
                        } catch (\Exception $ex) {
                            $data['gender_breakdown'] = [];
                        }

                        // 6. Top specialties in this Wilaya (Optimized with LIMIT 10 and sp.Nom grouping)
                        try {
                            $stmtTopSpecs = $this->archiveDb->prepare("
                                SELECT sp.Nom as spec_ar, COUNT(a.IDapprenant) as count
                                FROM apprenant a
                                LEFT JOIN section s ON a.IDSection = s.IDSection
                                LEFT JOIN offre o ON s.IDOffre = o.IDOffre
                                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                                WHERE s.IDDFEP = :wilaya_id
                                GROUP BY sp.Nom
                                ORDER BY count DESC
                                LIMIT 10
                            ");
                            $stmtTopSpecs->execute(['wilaya_id' => $wilayaId]);
                            $data['top_specialites'] = $this->cleanArchiveData($stmtTopSpecs->fetchAll(PDO::FETCH_ASSOC));
                        } catch (\Exception $ex) {
                            $data['top_specialites'] = [];
                        }

                        // Save to cache
                        $wilayaDataToCache = [
                            'total_stagiaires' => $data['total_stagiaires'],
                            'total_offres' => $data['total_offres'],
                            'total_etablissements' => $data['total_etablissements'],
                            'recent_offres' => $data['recent_offres'],
                            'gender_breakdown' => $data['gender_breakdown'],
                            'top_specialites' => $data['top_specialites']
                        ];
                        @file_put_contents($wilayaCacheFile, json_encode($wilayaDataToCache, JSON_UNESCAPED_UNICODE));
                    }
                }

            } catch (\Exception $e) {
                $data['error'] = 'خطأ أثناء الاستعلام من قاعدة الأرشيف: ' . $e->getMessage();
            }
        }

        return $this->render('admin/archive/index', $data);
    }

    /**
     * Recursively trims trailing spaces from keys of HFSQL returned data (ODBC driver behavior)
     * and converts CP1256 encoded strings (Arabic text) to UTF-8.
     * 
     * @param mixed $data
     * @return mixed
     */
    private function cleanArchiveData($data)
    {
        if (!is_array($data)) {
            if (is_string($data)) {
                if (!mb_check_encoding($data, 'UTF-8')) {
                    return trim(iconv('CP1256', 'UTF-8//IGNORE', $data));
                }
                return trim($data);
            }
            return $data;
        }
        $clean = [];
        foreach ($data as $key => $val) {
            $trimmedKey = is_string($key) ? trim($key) : $key;
            $clean[$trimmedKey] = $this->cleanArchiveData($val);
        }
        return $clean;
    }
}
