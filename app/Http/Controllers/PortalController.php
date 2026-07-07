<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PortalController extends Controller
{
    /**
     * Dynamically renders public sub-pages with high-fidelity, premium contemporary templates.
     */
    public function renderPage(string $page)
    {
        $page = htmlspecialchars($page);

        \App\Helpers\PortalCMSHelper::ensureTableExists();
        $portalPage = \App\Helpers\PortalCMSHelper::getPage($page);

        if (!$portalPage) {
            abort(404);
        }

        $title = $portalPage->title . ($portalPage->title_fr ? ' / ' . $portalPage->title_fr : '');

        $extraData = [];
        $extraData['portal_page'] = $portalPage;

        if ($page === 'directions') {
            try {
                $dfeps = DB::select("
                    SELECT e.IDetablissement, e.Nom as name_ar, e.NomFr as name_fr, ed.Latitude as lat, ed.Longitude as lng
                    FROM etablissement e
                    JOIN etablisement_detail ed ON ed.IDetablissement = e.IDetablissement
                    WHERE e.IDNature_etsF = 5
                ");
                
                $formattedDfeps = [];
                foreach ($dfeps as $d) {
                    $d = (array)$d;
                    $lat = (double)$d['lat'];
                    $lng = (double)$d['lng'];
                    
                    if ($lat == 0 || $lng == 0) {
                        continue;
                    }
                    
                    // Correct inverted/swapped coordinates (Latitude should be between 18 and 38 N, Longitude is between -9 and 12 E)
                    if ($lat < 15 && $lng > 18) {
                        $temp = $lat;
                        $lat = $lng;
                        $lng = $temp;
                    }
                    
                    $nameAr = $d['name_ar'];
                    $nameFr = $d['name_fr'];
                    
                    // Dynamically classify for premium maps visuals
                    $class1 = ['الجزائر', 'وهران', 'قسنطينة', 'عنابة', 'سطيف', 'ورقلة', 'البليدة', 'تيزي وزو', 'بجاية', 'تلمسان'];
                    $class3 = ['إليزي', 'تندوف', 'إن قزام', 'برج باجي مختار', 'جانت', 'عين صالح', 'تمنراست', 'أدرار', 'بشار'];
                    
                    $isClass1 = false;
                    foreach ($class1 as $c) {
                        if (mb_strpos($nameAr, $c) !== false) {
                            $isClass1 = true;
                            break;
                        }
                    }
                    
                    $isClass3 = false;
                    foreach ($class3 as $c) {
                        if (mb_strpos($nameAr, $c) !== false) {
                            $isClass3 = true;
                            break;
                        }
                    }
                    
                    if ($isClass1) {
                        $type = "مديرية صنف 1";
                        $badgeClass = "bg-primary-glow text-primary border-primary";
                    } elseif ($isClass3) {
                        $type = "مديرية صنف 3";
                        $badgeClass = "bg-warning-glow text-warning border-warning";
                    } else {
                        $type = "مديرية صنف 2";
                        $badgeClass = "bg-success-glow text-success border-success";
                    }
                    
                    $formattedDfeps[] = [
                        'name' => $nameAr,
                        'name_fr' => $nameFr ?: $nameAr,
                        'lat' => $lat,
                        'lng' => $lng,
                        'type' => $type,
                        'badgeClass' => $badgeClass
                    ];
                }
                $extraData['dfeps'] = $formattedDfeps;
            } catch (\Exception $e) {
                $extraData['dfeps'] = [];
            }
        }

        return view('portal.page', array_merge([
            'title' => $title,
            'page' => $page
        ], $extraData));
    }

    /**
     * Public card verification: Employee (Professional Card)
     */
    public function publicVerifyEmployeeCard(string $hash)
    {
        $id = \App\Helpers\SecureIdHelper::decrypt($hash);
        if (!$id) {
            return view('portal.verify_card', ['success' => false, 'message' => 'الرمز الشريطي أو الرابط غير صالح.']);
        }

        $sql = "
            SELECT enc.*, 
                   et.Nom AS etab_nom, 
                   et.NomFr AS etab_fr, 
                   g.Nom as grade_nom,
                   f.Nom as fonction_nom
            FROM encadrement enc
            LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
            LEFT JOIN grade g ON enc.IDGrade = g.IDGrade
            LEFT JOIN fonctions f ON enc.IDFonctions = f.IDFonctions
            WHERE enc.IDEncadrement = ?
            LIMIT 1
        ";
        $employee = DB::selectOne($sql, [$id]);
        if (!$employee) {
            return view('portal.verify_card', ['success' => false, 'message' => 'لم يتم العثور على بيانات الموظف في النظام.']);
        }

        $employee = (array)$employee;

        // Decrypt DateNais
        if (!empty($employee['DateNais'])) {
            try {
                $dec = \Illuminate\Support\Facades\Crypt::decryptString($employee['DateNais']);
                if ($dec) {
                    $employee['DateNais'] = $dec;
                }
            } catch (\Exception $e) {}
        }

        return view('portal.verify_card', [
            'success' => true,
            'type' => 'employee',
            'data' => $employee
        ]);
    }

    /**
     * Public card verification: Trainee (Trainee Card)
     */
    public function publicVerifyTraineeCard(string $hash)
    {
        $id = \App\Helpers\SecureIdHelper::decrypt($hash);
        if (!$id) {
            return view('portal.verify_card', ['success' => false, 'message' => 'الرمز الشريطي أو الرابط غير صالح.']);
        }

        $sql = "
            SELECT a.IDapprenant as id, a.Nccp as numero_matricule, 
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                   c.photo, c.Civ, c.DateNais, c.LieuNais,
                   sp.Nom as spec_ar, e.Nom as etab_nom,
                   mf.Nom as mode_nom, s.DateDF as date_deb, s.DateFF as date_fin
            FROM apprenant a
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN offre o ON o.IDOffre = COALESCE(NULLIF(s.IDOffre, 0), NULLIF(c.IDOffre, 0))
            LEFT JOIN specialite sp ON sp.IDSpecialite = COALESCE(NULLIF(s.IDSpecialite, 0), NULLIF(o.IDSpecialite, 0))
            LEFT JOIN etablissement e ON e.IDetablissement = COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0))
            LEFT JOIN mode_formation mf ON mf.IDMode_formation = COALESCE(NULLIF(s.IDMode_formation, 0), NULLIF(o.IDMode_formation, 0))
            WHERE a.IDapprenant = ?
            LIMIT 1
        ";
        $trainee = DB::selectOne($sql, [$id]);
        if (!$trainee) {
            return view('portal.verify_card', ['success' => false, 'message' => 'لم يتم العثور على بيانات المتربص في النظام.']);
        }

        $trainee = (array)$trainee;

        // Decrypt DateNais if encrypted
        if (!empty($trainee['DateNais'])) {
            try {
                $dec = \Illuminate\Support\Facades\Crypt::decryptString($trainee['DateNais']);
                if ($dec) {
                    $trainee['DateNais'] = $dec;
                }
            } catch (\Exception $e) {}
        }

        return view('portal.verify_card', [
            'success' => true,
            'type' => 'trainee',
            'data' => $trainee
        ]);
    }
}
