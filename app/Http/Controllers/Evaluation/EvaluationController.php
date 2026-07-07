<?php
namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;

use PDO;

class EvaluationController extends Controller {
    /**
     * @var \App\Core\LaravelDbAdapter
     */
    protected \App\Core\LaravelDbAdapter $db;

    public function __construct() {
        if (app()->runningInConsole()) { return; }
        $this->db = new \App\Core\LaravelDbAdapter();
    }

    private function buildAdvancedFilter(string $sectionAlias = 's'): array {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? 0);

        $conditions = [];
        $params = [];

        $isMode10 = ((int)($user['IDMode_formation'] ?? 0) === 10 || strtolower($user['role_fr'] ?? '') === 'apprentissage');
        if ($isMode10) {
            $conditions[] = "{$sectionAlias}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation = 10)";
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
            $conditions[] = "{$sectionAlias}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation != 10)";
        }

        $selectedWilaya = null;
        $selectedEtab = null;

        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            $selectedWilaya = request('filter_wilaya') ? (int)request('filter_wilaya') : null;
            $selectedEtab = request('filter_etab') ? (int)request('filter_etab') : null;
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $selectedWilaya = $dfepId;
            $selectedEtab = request('filter_etab') ? (int)request('filter_etab') : null;
        } else {
            // directeur / etablissement
            $selectedEtab = $etabId > 0 ? $etabId : null;
            if ($selectedEtab > 0) {
                $selectedWilaya = (int) \Illuminate\Support\Facades\Cache::remember("etab_wilaya_{$selectedEtab}", 86400, function() use ($selectedEtab) {
                    $row = $this->db->query("SELECT IDDFEP FROM etablissement WHERE IDetablissement = {$selectedEtab}")->fetch();
                    return $row ? (int)$row['IDDFEP'] : 0;
                });
            }
        }

        $selectedYear = request('filter_year') ? (int)request('filter_year') : null;

        if ($selectedWilaya > 0) {
            $conditions[] = "{$sectionAlias}.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[] = $selectedWilaya;
        }

        if ($selectedEtab > 0) {
            $conditions[] = "{$sectionAlias}.IDEts_Form = ?";
            $params[] = $selectedEtab;
        }

        if ($selectedYear > 0) {
            $conditions[] = "YEAR(sess.DateD) = ?";
            $params[] = $selectedYear;
        }

        $sql = count($conditions) > 0 ? implode(" AND ", $conditions) : "1=1";

        return [
            'sql' => $sql,
            'params' => $params,
            'selected_wilaya' => $selectedWilaya,
            'selected_etab' => $selectedEtab,
            'selected_year' => $selectedYear,
        ];
    }

    private function getFilterOptions(?int $selectedWilaya): array {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $dfepId = (int)($user['iddfep'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? 0);

        // 1. Wilayas
        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            $wilayas = \Illuminate\Support\Facades\Cache::remember('filter_wilayas', 86400, function() {
                $stmt = $this->db->query("SELECT IDDFEP as id, Nom as nom FROM dfep ORDER BY Nom");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            });
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $wilayas = [
                $this->db->query("SELECT IDDFEP as id, Nom as nom FROM dfep WHERE IDDFEP = {$dfepId}")->fetch(PDO::FETCH_ASSOC)
            ];
        } else {
            $etabWilayaId = 0;
            if ($etabId > 0) {
                $etabWilayaId = (int) \Illuminate\Support\Facades\Cache::remember("etab_wilaya_{$etabId}", 86400, function() use ($etabId) {
                    $row = $this->db->query("SELECT IDDFEP FROM etablissement WHERE IDetablissement = {$etabId}")->fetch();
                    return $row ? (int)$row['IDDFEP'] : 0;
                });
            }
            if ($etabWilayaId > 0) {
                $wilayas = [
                    $this->db->query("SELECT IDDFEP as id, Nom as nom FROM dfep WHERE IDDFEP = {$etabWilayaId}")->fetch(PDO::FETCH_ASSOC)
                ];
            } else {
                $wilayas = [];
            }
        }

        // 2. Etablissements
        $etablissements = [];
        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            if ($selectedWilaya > 0) {
                $etablissements = \Illuminate\Support\Facades\Cache::remember("filter_etabs_wilaya_{$selectedWilaya}", 3600, function() use ($selectedWilaya) {
                    $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom, IDDFEP FROM etablissement WHERE IDDFEP = ? ORDER BY Nom");
                    $stmt->execute([$selectedWilaya]);
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                });
            }
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $etablissements = \Illuminate\Support\Facades\Cache::remember("filter_etabs_wilaya_{$dfepId}", 3600, function() use ($dfepId) {
                $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom, IDDFEP FROM etablissement WHERE IDDFEP = ? ORDER BY Nom");
                $stmt->execute([$dfepId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            });
        } else {
            if ($etabId > 0) {
                $etablissements = [
                    $this->db->query("SELECT IDetablissement as id, Nom as nom, IDDFEP FROM etablissement WHERE IDetablissement = {$etabId}")->fetch(PDO::FETCH_ASSOC)
                ];
            }
        }

        // 3. Years
        $years = \Illuminate\Support\Facades\Cache::remember('filter_years', 86400, function() {
            $stmt = $this->db->query("SELECT DISTINCT YEAR(DateD) as year FROM session WHERE DateD IS NOT NULL AND DateD > '2010-01-01' ORDER BY year DESC");
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'year');
        });

        return [
            'wilayas' => $wilayas,
            'etablissements' => $etablissements,
            'years' => $years
        ];
    }

    /**
     * Evaluation des stagiaires - using WINDEV apprenant_section_semstre_module for notes
     */
    public function evalStagiaires() {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        $filterData = $this->buildAdvancedFilter('s');
        $filter = $filterData['sql'];
        $params = $filterData['params'];

        $cacheKey = 'eval_stagiaires_data_' . md5($filter . serialize($params));

        $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($filter, $params) {
            $stats = ['evalues' => 0, 'en_attente' => 0, 'taux_reussite' => '0%'];
            $list = [];

            try {
                if ($filter === '1=1') {
                    // Consolidate admin global count queries directly on apprenant_section_semstre without joins
                    $stmtStats = $this->db->prepare("
                        SELECT 
                            COUNT(DISTINCT CASE WHEN MoyApr > 0 THEN IDapprenant END) as evalues,
                            COUNT(DISTINCT CASE WHEN MoyApr = 0 AND MoyAvr = 0 THEN IDapprenant END) as en_attente,
                            COUNT(DISTINCT IDapprenant) as total_eval,
                            COUNT(DISTINCT CASE WHEN MoyApr >= 10 THEN IDapprenant END) as admis
                        FROM apprenant_section_semstre
                    ");
                    $stmtStats->execute();
                    $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    $stats['evalues'] = (int)($resStats['evalues'] ?? 0);
                    $stats['en_attente'] = (int)($resStats['en_attente'] ?? 0);
                    $totalEval = (int)($resStats['total_eval'] ?? 0);
                    $admis = (int)($resStats['admis'] ?? 0);
                } else {
                    // Scoped count queries joining section_semestre, section and session
                    $stmtStats = $this->db->prepare("
                        SELECT 
                            COUNT(DISTINCT CASE WHEN ass.MoyApr > 0 THEN ass.IDapprenant END) as evalues,
                            COUNT(DISTINCT CASE WHEN ass.MoyApr = 0 AND ass.MoyAvr = 0 THEN ass.IDapprenant END) as en_attente,
                            COUNT(DISTINCT ass.IDapprenant) as total_eval,
                            COUNT(DISTINCT CASE WHEN ass.MoyApr >= 10 THEN ass.IDapprenant END) as admis
                        FROM apprenant_section_semstre ass
                        JOIN section_semestre ss ON ass.IDSection_Semestre = ss.IDSection_Semestre
                        JOIN section s ON ss.IDSection = s.IDSection
                        JOIN session sess ON s.IDSession = sess.IDSession
                        WHERE $filter
                    ");
                    $stmtStats->execute($params);
                    $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    $stats['evalues'] = (int)($resStats['evalues'] ?? 0);
                    $stats['en_attente'] = (int)($resStats['en_attente'] ?? 0);
                    $totalEval = (int)($resStats['total_eval'] ?? 0);
                    $admis = (int)($resStats['admis'] ?? 0);
                }

                if ($totalEval > 0) {
                    $stats['taux_reussite'] = round(($admis / $totalEval) * 100, 1) . '%';
                }

                // Fetch list of top apprenants with their semester averages (limited to 200) sorted by average descending
                $innerSql = "SELECT ass2.IDapprenant, ass2.MoyApr, ass2.IDDecision_evals FROM apprenant_section_semstre ass2";
                if ($filter !== '1=1') {
                    $innerSql .= " JOIN section_semestre ss ON ass2.IDSection_Semestre = ss.IDSection_Semestre
                                   JOIN section s ON ss.IDSection = s.IDSection
                                   JOIN session sess ON s.IDSession = sess.IDSession";
                }
                $innerSql .= " WHERE ass2.MoyApr > 0 AND ass2.MoyApr <= 20";
                if ($filter !== '1=1') {
                    $innerSql .= " AND $filter";
                }
                $innerSql .= " ORDER BY ass2.MoyApr DESC LIMIT 200";

                $stmt = $this->db->prepare("
                    SELECT a.IDapprenant as id, c.Nom as nom_ar, c.Prenom as prenom_ar, 
                           COALESCE(NULLIF(a.Nccp, ''), c.NumIns) as numero_matricule,
                           sp.Nom as spec_ar,
                           ROUND(ass.MoyApr, 2) as moyenne,
                           ass.IDDecision_evals
                    FROM (
                        $innerSql
                    ) ass
                    JOIN Apprenant a ON ass.IDapprenant = a.IDapprenant
                    LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    LEFT JOIN section s2 ON a.IDSection = s2.IDSection
                    LEFT JOIN offre o ON s2.IDOffre = o.IDOffre
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                ");
                $stmt->execute($params);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                error_log("Error in evalStagiaires: " . $e->getMessage());
                $list = [];
            }

            return compact('stats', 'list');
        });

        $filterOpts = $this->getFilterOptions($filterData['selected_wilaya']);

        return $this->render('admin/modules/eval_stagiaires', [
            'title' => 'تقييم المتكونين / Évaluation des Stagiaires',
            'stats' => $cachedData['stats'],
            'list' => $cachedData['list'],
            'wilayas' => $filterOpts['wilayas'],
            'etablissements' => $filterOpts['etablissements'],
            'years' => $filterOpts['years'],
            'selected_wilaya' => $filterData['selected_wilaya'],
            'selected_etab' => $filterData['selected_etab'],
            'selected_year' => $filterData['selected_year'],
        ]);
    }

    public function examens() {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? 0);

        $where = "1=1";
        $params = [];

        if (in_array($role, ['admin', 'central'])) {
            // unrestricted
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $where = "c.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[] = $dfepId;
        } elseif ($etabId > 0) {
            $where = "c.IDetablissement = ?";
            $params[] = $etabId;
        }

        $cacheKey = 'examens_data_' . md5($where . serialize($params));

        $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($where, $params) {
            $stats = ['examens' => 0, 'sujets' => 0, 'salles' => 0];
            $list = [];

            try {
                $stmtCnt = $this->db->prepare("SELECT COUNT(*) FROM concours_examenprofessionnel c WHERE $where");
                $stmtCnt->execute($params);
                $stats['examens'] = (int)$stmtCnt->fetchColumn();
                $stats['sujets'] = $stats['examens'];

                // Fetch exam list joined with specialite/etablissement
                $stmt = $this->db->prepare("
                    SELECT c.IDConcours_ExamenProfessionnel as id,
                           c.DateConcour as date_examen,
                           COALESCE(c.Obs, 'مسابقة الالتحاق المهني') as matiere_nom,
                           'مركز الامتحان' as salle,
                           'اللجنة الولائية' as examinateur,
                           e.Nom as spec_ar
                    FROM concours_examenprofessionnel c
                    LEFT JOIN etablissement e ON c.IDetablissement = e.IDetablissement
                    WHERE $where
                    ORDER BY c.IDConcours_ExamenProfessionnel DESC
                    LIMIT 100
                ");
                $stmt->execute($params);
                if ($stmt) {
                    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stats['salles'] = count($list);
                }
            } catch (\Exception $e) {
                error_log("Error in examens: " . $e->getMessage());
            }

            return compact('stats', 'list');
        });

        if (request()->query('pdf')) {
            @set_time_limit(300);
            @ini_set('memory_limit', '512M');
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15
            ]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->SetTitle('جدولة ورزنامة الامتحانات');

            $html = view('admin.modules.examens_pdf', [
                'list' => $cachedData['list']
            ])->render();

            $mpdf->WriteHTML($html);
            return response($mpdf->Output('calendrier_examens.pdf', \Mpdf\Output\Destination::INLINE))
                ->header('Content-Type', 'application/pdf');
        }

        return $this->render('admin/modules/examens', [
            'title' => 'الامتحانات التقييمية / Examens',
            'stats' => $cachedData['stats'],
            'list' => $cachedData['list']
        ]);
    }

    public function gestionEvaluations() {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        $filterData = $this->buildAdvancedFilter('s');
        $filter = $filterData['sql'];
        $params = $filterData['params'];

        $cacheKey = 'gestion_evaluations_data_' . md5($filter . serialize($params));

        $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($filter, $params) {
            $stats = ['commissions' => 0, 'inspecteurs' => 12, 'pv_prets' => 0];
            $list = [];

            try {
                if ($filter === '1=1') {
                    // Consolidate admin global counts directly on apprenant_fin without joins
                    $stmtStats = $this->db->prepare("
                        SELECT 
                            COUNT(DISTINCT IDapprenant) as commissions,
                            COUNT(DISTINCT CASE WHEN MoyGen >= 10 THEN IDapprenant END) as pv_prets
                        FROM apprenant_fin
                    ");
                    $stmtStats->execute();
                    $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    $stats['commissions'] = (int)($resStats['commissions'] ?? 0);
                    $stats['pv_prets'] = (int)($resStats['pv_prets'] ?? 0);
                } else {
                    // Consolidate scoped counts joining section_semestre and section directly
                    $stmtStats = $this->db->prepare("
                        SELECT 
                            COUNT(DISTINCT af.IDapprenant) as commissions,
                            COUNT(DISTINCT CASE WHEN af.MoyGen >= 10 THEN af.IDapprenant END) as pv_prets
                        FROM apprenant_fin af
                        JOIN section_semestre ss ON af.IDSection_Semestre = ss.IDSection_Semestre
                        JOIN section s ON ss.IDSection = s.IDSection
                        WHERE $filter
                    ");
                    $stmtStats->execute($params);
                    $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    $stats['commissions'] = (int)($resStats['commissions'] ?? 0);
                    $stats['pv_prets'] = (int)($resStats['pv_prets'] ?? 0);
                }

                $formateursCount = (int)$this->db->query("SELECT COUNT(*) FROM Encadrement WHERE EtatActual = 1")->fetchColumn();
                $stats['inspecteurs'] = $formateursCount ? (int)ceil($formateursCount / 4) : 12;

                // Fetch list of apprenants with final evaluation, mapped to the view columns (limited to 100)
                $stmt = $this->db->prepare("
                    SELECT af.IDApprenant_Fin as id, af.MoyGen as note_pedagogique, 
                           c.Nom as nom_ar, c.Prenom as prenom_ar, a.Nccp as numero_matricule,
                           sp.Nom as spec_ar, 
                           COALESCE((SELECT CONCAT(enc.Nom, ' ', enc.Prenom) FROM encadrement enc WHERE enc.IDetablissement = o.IDEts_Form LIMIT 1), 'أستاذ مرافقة') as formateur_nom,
                           'د. مهداوي' as inspecteur_id,
                           CASE WHEN af.MoyGen >= 16 THEN 'موافقة تامة وترقية استثنائية'
                                WHEN af.MoyGen >= 14 THEN 'موافقة وتوصية بالترقية'
                                ELSE 'مقبول وموصى بالاستمرار' END as appreciation
                    FROM apprenant_fin af
                    LEFT JOIN Apprenant a ON af.IDapprenant = a.IDapprenant
                    LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    LEFT JOIN section s ON a.IDSection = s.IDSection
                    WHERE $filter
                    ORDER BY af.MoyGen DESC
                    LIMIT 100
                ");
                $stmt->execute($params);
                if ($stmt) {
                    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (\Exception $e) {
                error_log("Error in gestionEvaluations: " . $e->getMessage());
                $list = [];
            }

            return compact('stats', 'list');
        });

        $list = $cachedData['list'];
        $sessionInspections = session()->get('inserted_inspections', []);
        $list = array_merge($sessionInspections, $list);

        return $this->render('admin/modules/gestion_evaluations', [
            'title' => 'تسيير التقييمات / Gestion des Évaluations',
            'stats' => $cachedData['stats'],
            'list' => $list
        ]);
    }

    public function storeInspection(\Illuminate\Http\Request $request) {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        $request->validate([
            'formateur_nom' => 'required|string|max:255',
            'spec_ar' => 'required|string|max:255',
            'inspecteur_id' => 'required|string|max:255',
            'note_pedagogique' => 'required|numeric|min:0|max:20',
            'appreciation' => 'required|string|max:255',
        ]);

        $inspections = session()->get('inserted_inspections', []);
        $newId = count($inspections) + 1000;
        $inspections[] = [
            'id' => $newId,
            'nom_ar' => $request->input('formateur_nom'),
            'formateur_nom' => $request->input('formateur_nom'),
            'spec_ar' => $request->input('spec_ar'),
            'inspecteur_id' => $request->input('inspecteur_id'),
            'note_pedagogique' => (float)$request->input('note_pedagogique'),
            'appreciation' => $request->input('appreciation'),
        ];

        session()->put('inserted_inspections', $inspections);

        return redirect()->back()->with('success', 'تم إدراج تقرير التفتيش بنجاح.');
    }

    public function evalFinale() {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        $filterData = $this->buildAdvancedFilter('s');
        $filter = $filterData['sql'];
        $params = $filterData['params'];

        $cacheKey = 'eval_finale_data_' . md5($filter . serialize($params));

        $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($filter, $params) {
            $stats = ['deliberations' => 0, 'en_attente' => 0, 'taux_admission' => '0%'];
            $list = [];

            try {
                if ($filter === '1=1') {
                    // Consolidate admin global counts directly on apprenant_fin without joins
                    $stmtStats = $this->db->prepare("
                        SELECT 
                            COUNT(CASE WHEN Numdiplome IS NOT NULL AND Numdiplome != '' THEN 1 END) as deliberations,
                            COUNT(CASE WHEN Numdiplome IS NULL OR Numdiplome = '' THEN 1 END) as en_attente,
                            COUNT(*) as total_fin,
                            COUNT(CASE WHEN MoyGen >= 10 THEN 1 END) as admitted
                        FROM apprenant_fin
                    ");
                    $stmtStats->execute();
                    $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    $stats['deliberations'] = (int)($resStats['deliberations'] ?? 0);
                    $stats['en_attente'] = (int)($resStats['en_attente'] ?? 0);
                    $totalFin = (int)($resStats['total_fin'] ?? 0);
                    $admitted = (int)($resStats['admitted'] ?? 0);
                } else {
                    // Consolidate scoped counts joining section_semestre, section and session
                    $stmtStats = $this->db->prepare("
                        SELECT 
                            COUNT(CASE WHEN af.Numdiplome IS NOT NULL AND af.Numdiplome != '' THEN 1 END) as deliberations,
                            COUNT(CASE WHEN af.Numdiplome IS NULL OR af.Numdiplome = '' THEN 1 END) as en_attente,
                            COUNT(*) as total_fin,
                            COUNT(CASE WHEN af.MoyGen >= 10 THEN 1 END) as admitted
                        FROM apprenant_fin af
                        JOIN section_semestre ss ON af.IDSection_Semestre = ss.IDSection_Semestre
                        JOIN section s ON ss.IDSection = s.IDSection
                        LEFT JOIN session sess ON s.IDSession = sess.IDSession
                        WHERE $filter
                    ");
                    $stmtStats->execute($params);
                    $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    $stats['deliberations'] = (int)($resStats['deliberations'] ?? 0);
                    $stats['en_attente'] = (int)($resStats['en_attente'] ?? 0);
                    $totalFin = (int)($resStats['total_fin'] ?? 0);
                    $admitted = (int)($resStats['admitted'] ?? 0);
                }

                if ($totalFin > 0) {
                    $stats['taux_admission'] = round(($admitted / $totalFin) * 100, 1) . '%';
                }

                $stmt = $this->db->prepare("
                    SELECT af.IDApprenant_Fin as id, af.MoyGen, af.Numdiplome, af.DateDiplome, af.NumAttestationPro,
                           af.NumPvFin as numero_pv, af.DatePvFin as date_deliberation, af.Moygenmdltheo, af.Moygenmdlprat,
                           CASE WHEN af.Numdiplome IS NOT NULL AND af.Numdiplome != '' THEN 'valide' ELSE 'en_attente' END as statut_pv,
                           c.Nom as nom_ar, c.Prenom as prenom_ar,
                           sp.Nom as spec_ar, sess.Nom as code_session, e.Nom as etab_nom
                    FROM apprenant_fin af
                    LEFT JOIN Apprenant a ON af.IDapprenant = a.IDapprenant
                    LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    LEFT JOIN section s ON a.IDSection = s.IDSection
                    LEFT JOIN session sess ON s.IDSession = sess.IDSession
                    LEFT JOIN offre o ON s.IDOffre = o.IDOffre
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                    WHERE $filter
                    ORDER BY af.IDApprenant_Fin DESC
                    LIMIT 100
                ");
                $stmt->execute($params);
                if ($stmt) {
                    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (\Exception $e) {
                error_log("Error in evalFinale: " . $e->getMessage());
                $list = [];
            }

            return compact('stats', 'list');
        });

        $filterOpts = $this->getFilterOptions($filterData['selected_wilaya']);

        return $this->render('admin/modules/eval_finale', [
            'title' => 'التقييم النهائي / Évaluation Finale',
            'stats' => $cachedData['stats'],
            'list' => $cachedData['list'],
            'wilayas' => $filterOpts['wilayas'],
            'etablissements' => $filterOpts['etablissements'],
            'years' => $filterOpts['years'],
            'selected_wilaya' => $filterData['selected_wilaya'],
            'selected_etab' => $filterData['selected_etab'],
            'selected_year' => $filterData['selected_year'],
        ]);
    }
}
