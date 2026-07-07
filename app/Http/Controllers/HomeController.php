<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $stat_inscrits = 1862939;
        $stat_etablissements = 2035;
        $stat_wilayas = 58;
        $stat_specialites = 1289;

        try {
            $count = DB::table('apprenant')->count();
            if ($count > 0) {
                $stat_inscrits = $count;
            }

            $count = DB::table('etablissement')->count();
            if ($count > 0) {
                $stat_etablissements = $count;
            }

            $count = DB::table('wilaya')->count();
            if ($count > 0) {
                // Algeria has 58 wilayas officially, cap it or display actual records
                $stat_wilayas = min($count, 58);
            }

            $count = DB::table('specialite')->count();
            if ($count > 0) {
                $stat_specialites = $count;
            }
        } catch (\Exception $e) {
            // Silently fallback to default stats if tables do not exist
        }

        return view('home.index', [
            'title' => 'المنصة الوطنية الموحدة لتسيير التكوين المهني ERP',
            'stat_inscrits' => $stat_inscrits,
            'stat_etablissements' => $stat_etablissements,
            'stat_wilayas' => $stat_wilayas,
            'stat_specialites' => $stat_specialites
        ]);
    }

    public function searchResult(Request $request)
    {
        $mode_translations = \App\Services\ModeService::getTranslations();
        $matricule = trim($request->input('matricule', ''));

        if (empty($matricule)) {
            return view('home.results', [
                'title' => 'SGFEP - بوابة الاستعلام عن كشوف النقاط / Relevés de Notes',
                'student' => null,
                'grades' => [],
                'matricule' => '',
                'is_search_only' => true,
                'mode_translations' => $mode_translations
            ]);
        }

        $result = null;

        try {
            // Step 1: Resolve student candidate ID using indexed lookups
            $candidateId = null;

            // Search by Nccp in apprenant table
            $row = DB::selectOne("SELECT IDCandidat FROM apprenant WHERE Nccp = ? LIMIT 1", [$matricule]);
            if ($row) {
                $candidateId = $row->IDCandidat;
            } else {
                // Search by NumIns in candidat table
                $row = DB::selectOne("SELECT IDCandidat FROM candidat WHERE NumIns = ? LIMIT 1", [$matricule]);
                if ($row) {
                    $candidateId = $row->IDCandidat;
                } else {
                    // Search by Nin in candidat table
                    $row = DB::selectOne("SELECT IDCandidat FROM candidat WHERE Nin = ? LIMIT 1", [$matricule]);
                    if ($row) {
                        $candidateId = $row->IDCandidat;
                    }
                }
            }

            // Step 2: Fetch student details using the resolved candidate ID
            if ($candidateId) {
                $query = "SELECT a.IDapprenant as id, COALESCE(NULLIF(a.Nccp, ''), c.NumIns) as numero_matricule, c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.DateNais as date_naissance, c.Nin as nin,
                                 o.IDMode_formation as mode_formation, sp.Nom as spec_ar, sp.NomFr as spec_fr,
                                 e.Nom as etab_ar, e.NomFr as etab_fr,
                                 d.Numdiplome as numero_diplome, m.Nom as mention, d.MoyGen as moyenne_generale, d.urlauth as qr_code_token
                          FROM candidat c
                          JOIN Apprenant a ON a.IDCandidat = c.IDCandidat
                          JOIN section s ON a.IDSection = s.IDSection
                          JOIN offre o ON s.IDOffre = o.IDOffre
                          JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                          JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                          LEFT JOIN apprenant_fin d ON a.IDapprenant = d.IDapprenant
                          LEFT JOIN mention m ON d.IDmention = m.IDmention
                          WHERE c.IDCandidat = ?
                          LIMIT 1";

                $dbResult = DB::selectOne($query, [$candidateId]);
                if ($dbResult) {
                    $result = (array)$dbResult;
                    // Sanitize all French fields from DB to fix CP850/double UTF-8 corruptions
                    $result['nom_fr'] = \App\Helpers\TakwinHelper::fixDoubleUtf8($result['nom_fr'] ?? '');
                    $result['prenom_fr'] = \App\Helpers\TakwinHelper::fixDoubleUtf8($result['prenom_fr'] ?? '');
                    $result['spec_fr'] = \App\Helpers\TakwinHelper::fixDoubleUtf8($result['spec_fr'] ?? '');
                    $result['etab_fr'] = \App\Helpers\TakwinHelper::fixDoubleUtf8($result['etab_fr'] ?? '');
                }
            }
        } catch (\Exception $e) {
            // Graceful fallback on DB errors
        }

        $grades = [];

        if ($result && isset($result['id'])) {
            try {
                // Query dynamic database grades using the primary Windev tables
                $stmtGrades = "
                    SELECT ssm.NomMdl as libelle_ar, ssm.NomFrMdl as libelle_fr, ssm.coef as coef,
                           ssm.IDsection_semestre_Module as code,
                           COALESCE(assm.MoyApr, assm.MoyAvr, 0.0) as note_final
                    FROM apprenant_section_semstre ass
                    JOIN apprenant_section_semstre_module assm ON ass.IDapprenant_Section_semstre = assm.IDapprenant_Section_semstre
                    JOIN section_semestre_module ssm ON assm.IDsection_semestre_Module = ssm.IDsection_semestre_Module
                    WHERE ass.IDapprenant = ?
                ";

                $dbGrades = DB::select($stmtGrades, [$result['id']]);

                foreach ($dbGrades as $dg) {
                    $dg = (array)$dg;
                    $grades[] = [
                        'code' => $dg['code'] ?: ('MOD-' . $dg['coef']),
                        'libelle_ar' => $dg['libelle_ar'],
                        'libelle_fr' => \App\Helpers\TakwinHelper::fixDoubleUtf8($dg['libelle_fr'] ?? ''),
                        'coef' => (float)$dg['coef'],
                        'note' => (float)$dg['note_final']
                    ];
                }
            } catch (\Exception $e) {
                // Graceful fallback
            }
        }

        $isDemo = (strtolower($matricule) === 'stg-001');

        if (!$result) {
            if ($isDemo) {
                // Mock demonstration fallback for presentation demo
                $result = [
                    'numero_matricule' => 'STG-001',
                    'nin' => '173049281048291038',
                    'nom_ar' => 'بن علي',
                    'prenom_ar' => 'عبد الرحمن',
                    'nom_fr' => 'BENALI',
                    'prenom_fr' => 'Abderrahmane',
                    'date_naissance' => '2004-05-12',
                    'spec_ar' => 'تطوير البرمجيات وقواعد البيانات',
                    'spec_fr' => 'Développement Logiciel et Bases de Données',
                    'diplome_vise' => 'BTS',
                    'mode_formation' => 'apprentissage',
                    'etab_ar' => 'المعهد الوطني المتخصص في التكوين المهني - السانية',
                    'etab_fr' => 'INSFP Es-Senia - Oran',
                    'numero_diplome' => 'DIP-2026-00482',
                    'mention' => 'tres_bien',
                    'moyenne_generale' => 16.85,
                    'qr_code_token' => 'verification-token-demo-123456789',
                    'is_demo' => true
                ];

                $grades = [
                    ['code' => 'MOD-01', 'libelle_ar' => 'الخوارزميات والمنطق', 'libelle_fr' => 'Algorithmique & Logique', 'coef' => 3, 'note' => 17.50],
                    ['code' => 'MOD-02', 'libelle_ar' => 'قواعد البيانات الكائنية', 'libelle_fr' => 'Bases de Données Relationnelles', 'coef' => 4, 'note' => 18.00],
                    ['code' => 'MOD-03', 'libelle_ar' => 'تطوير تطبيقات الويب (PHP/MVC)', 'libelle_fr' => 'Développement Web (PHP/MVC)', 'coef' => 5, 'note' => 16.50],
                    ['code' => 'MOD-04', 'libelle_ar' => 'أمن الشبكات والمعلومات', 'libelle_fr' => 'Sécurité des Réseaux & Infos', 'coef' => 2, 'note' => 14.00],
                    ['code' => 'MOD-05', 'libelle_ar' => 'اللغة الإنجليزية التقنية', 'libelle_fr' => 'Anglais Technique', 'coef' => 2, 'note' => 15.50],
                ];
            } else {
                return view('home.results', [
                    'title' => 'SGFEP - بوابة الاستعلام عن كشوف النقاط / Relevés de Notes',
                    'student' => null,
                    'grades' => [],
                    'matricule' => $matricule,
                    'is_search_only' => true,
                    'mode_translations' => $mode_translations,
                    'error_message' => 'عذراً، رقم التسجيل أو التعريف الوطني غير موجود في قاعدة البيانات.'
                ]);
            }
        } else {
            // If student exists but grades are empty, fetch the actual modules of their current section semester
            if (empty($grades) && isset($result['id'])) {
                try {
                    $dbModules = DB::select("
                        SELECT ssm.NomMdl as libelle_ar, ssm.NomFrMdl as libelle_fr, ssm.coef as coef,
                               ssm.IDsection_semestre_Module as code
                        FROM section_semestre ss
                        JOIN section_semestre_module ssm ON ss.IDSection_Semestre = ssm.IDSection_Semestre
                        JOIN section sec ON ss.IDSection = sec.IDSection
                        JOIN apprenant a ON a.IDSection = sec.IDSection
                        WHERE a.IDapprenant = ?
                        ORDER BY ssm.NumOrd
                    ", [$result['id']]);
                    
                    foreach ($dbModules as $dm) {
                        $dm = (array)$dm;
                        $grades[] = [
                            'code' => $dm['code'] ?: ('MOD-' . $dm['coef']),
                            'libelle_ar' => $dm['libelle_ar'],
                            'libelle_fr' => \App\Helpers\TakwinHelper::fixDoubleUtf8($dm['libelle_fr'] ?? ''),
                            'coef' => (float)$dm['coef'],
                            'note' => 0.0
                        ];
                    }
                } catch (\Exception $e) {
                    // Fail gracefully
                }
            }
        }

        // PDF Generation Trigger
        if (request()->query('pdf') && $result) {
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
            $mpdf->SetTitle('كشف النقاط الرسمي للمتخرج - ' . ($result['numero_matricule'] ?? ''));

            $html = view('home.results_pdf', [
                'student' => $result,
                'grades'  => $grades,
                'mode_translations' => $mode_translations
            ])->render();

            $mpdf->WriteHTML($html);
            $filename = 'releve_notes_' . ($result['numero_matricule'] ?? 'export') . '.pdf';
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::INLINE))
                ->header('Content-Type', 'application/pdf');
        }

        return view('home.results', [
            'title' => 'SGFEP - كشف النقاط الرسمي / Relevé de Notes Officiel',
            'student' => $result,
            'grades' => $grades,
            'matricule' => $matricule,
            'is_search_only' => false,
            'mode_translations' => $mode_translations
        ]);
    }
}
