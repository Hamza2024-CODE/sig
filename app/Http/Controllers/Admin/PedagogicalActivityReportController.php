<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PedagogicalActivityReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = session('user');
            if (!$user) {
                return redirect()->to('/login');
            }
            return $next($request);
        });
    }

    /**
     * Display report with filters and pagination
     */
    public function index(Request $request)
    {
        try {
            $user = session('user');
            $role = strtolower($user['role_code'] ?? '');
            $etabId = (int)($user['etablissement_id'] ?? 0);
            $dfepId = (int)($user['iddfep'] ?? 0);

            // Fetch filter data for dropdowns
            $branches = DB::select("SELECT IDBranche as id, Nom as nom FROM branche ORDER BY Nom ASC");
            $wilayas = DB::select("SELECT IDWilayaa as id, Nom as nom FROM wilaya ORDER BY Nom ASC");
            
            $etablissements = [];
            if (in_array($role, ['admin', 'central', 'ministre'])) {
                $etablissements = DB::select("SELECT IDetablissement as id, Nom as nom, IDDFEP as wilaya_id FROM etablissement ORDER BY Nom ASC");
            } elseif ($role === 'dfep' && $dfepId > 0) {
                $etablissements = DB::select("SELECT IDetablissement as id, Nom as nom, IDDFEP as wilaya_id FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC", [$dfepId]);
            } else {
                $etablissements = DB::select("SELECT IDetablissement as id, Nom as nom, IDDFEP as wilaya_id FROM etablissement WHERE IDetablissement = ?", [$etabId]);
            }

            $modes = DB::select("SELECT IDMode_formation as id, Nom as nom FROM mode_formation ORDER BY Nom ASC");

            // Build base data query
            $query = "
                SELECT 
                    s.IDSection AS section_id,
                    s.IDOffre AS id_offre,
                    e.Nom AS nom_etablissement,
                    sp.CodeSpec AS code_specialite,
                    sp.Nom AS nom_specialite,
                    sp.NomFr AS nom_formation,
                    sp.NbrSem AS duree_semestres,
                    s.Nom AS section_nom,
                    COALESCE(ss.NumSem, 1) AS numero_semestre,
                    s.DateDF AS date_debut,
                    s.DateFF AS date_fin,
                    mf.Nom AS nom_mode_formation,
                    b.Nom AS nom_branche,
                    (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection) AS total_inscrits,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND c.Civ IN ('أنثى', 'female', '2', 'أنثي', 'f', 'F')) AS femmes_inscrits,
                    (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection AND a.statut = 'actif') AS total_actifs,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND a.statut = 'actif' AND c.Civ IN ('أنثى', 'female', '2', 'أنثي', 'f', 'F')) AS femmes_actifs,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND c.Nationalite IS NOT NULL AND TRIM(c.Nationalite) != '' AND c.Nationalite NOT IN ('الجزائرية', 'جزائرية', 'algerienne', 'Algerian', 'dz', 'DZ', '1')) AS total_foreigners,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND (c.endicape = 1 OR c.endicape = '1')) AS total_handicapes
                FROM section s
                LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
                LEFT JOIN branche b ON sp.IDBranche = b.IDBranche
                LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                LEFT JOIN mode_formation mf ON s.IDMode_formation = mf.IDMode_formation
                LEFT JOIN section_semestre ss ON s.IDSection = ss.IDSection AND ss.IDSection_Semestre = (
                    SELECT MAX(ss2.IDSection_Semestre) FROM section_semestre ss2 WHERE ss2.IDSection = s.IDSection
                )
                WHERE 1=1
            ";

            // Build count query
            $countQuery = "
                SELECT COUNT(*) AS total
                FROM section s
                LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
                LEFT JOIN branche b ON sp.IDBranche = b.IDBranche
                LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                LEFT JOIN mode_formation mf ON s.IDMode_formation = mf.IDMode_formation
                LEFT JOIN section_semestre ss ON s.IDSection = ss.IDSection AND ss.IDSection_Semestre = (
                    SELECT MAX(ss2.IDSection_Semestre) FROM section_semestre ss2 WHERE ss2.IDSection = s.IDSection
                )
                WHERE 1=1
            ";

            $params = [];
            $filterSql = "";

            // Role Scoping
            if ($role === 'dfep' && $dfepId > 0) {
                $filterSql .= " AND e.IDDFEP = ? ";
                $params[] = $dfepId;
            } elseif (in_array($role, ['etablissement', 'directeur', 'employee']) && $etabId > 0) {
                $filterSql .= " AND s.IDEts_Form = ? ";
                $params[] = $etabId;
            }

            // Apply HTML Filters
            if ($request->filled('wilaya_id')) {
                $filterSql .= " AND e.IDDFEP = ? ";
                $params[] = (int)$request->wilaya_id;
            }
            if ($request->filled('etab_id')) {
                $filterSql .= " AND s.IDEts_Form = ? ";
                $params[] = (int)$request->etab_id;
            }
            if ($request->filled('branche_id')) {
                $filterSql .= " AND sp.IDBranche = ? ";
                $params[] = (int)$request->branche_id;
            }
            if ($request->filled('mode_id')) {
                $filterSql .= " AND s.IDMode_formation = ? ";
                $params[] = (int)$request->mode_id;
            }
            if ($request->filled('semester')) {
                $filterSql .= " AND ss.NumSem = ? ";
                $params[] = (int)$request->semester;
            }
            if ($request->filled('search')) {
                $q = '%' . $request->search . '%';
                $filterSql .= " AND (sp.Nom LIKE ? OR sp.CodeSpec LIKE ? OR s.Nom LIKE ?) ";
                $params[] = $q;
                $params[] = $q;
                $params[] = $q;
            }

            $query .= $filterSql;
            $countQuery .= $filterSql;

            // Get total records
            $total = (int)(DB::select($countQuery, $params)[0]->total ?? 0);

            // Pagination parameters
            $perPage = 50;
            $currentPage = (int)$request->query('page', 1);
            if ($currentPage < 1) $currentPage = 1;
            $offset = ($currentPage - 1) * $perPage;

            $query .= " ORDER BY e.Nom ASC, b.Nom ASC, sp.Nom ASC LIMIT ? OFFSET ? ";
            
            $queryParams = $params;
            $queryParams[] = $perPage;
            $queryParams[] = $offset;

            $rawData = DB::select($query, $queryParams);
            $dataList = [];
            foreach ($rawData as $item) {
                $arr = (array)$item;
                $arr['section_formatted'] = $this->formatSectionName($arr['section_nom']);
                $dataList[] = $arr;
            }

            // Create LengthAwarePaginator
            $data = new \Illuminate\Pagination\LengthAwarePaginator(
                $dataList,
                $total,
                $perPage,
                $currentPage,
                ['path' => $request->url()]
            );
            $data->withQueryString();

            return view('admin.reports.pedagogical_activities', compact('data', 'branches', 'wilayas', 'etablissements', 'modes', 'user'));
        } catch (\Throwable $e) {
            return back()->with('error', 'حدث خطأ أثناء تحميل الحصيلة البيداغوجية: ' . $e->getMessage());
        }
    }

    /**
     * Export the filtered list to an Excel spreadsheet matching the user template structure
     */
    public function exportExcel(Request $request)
    {
        try {
            $user = session('user');
            $role = strtolower($user['role_code'] ?? '');
            $etabId = (int)($user['etablissement_id'] ?? 0);
            $dfepId = (int)($user['iddfep'] ?? 0);

            // Build data query
            $query = "
                SELECT 
                    s.IDSection AS section_id,
                    s.IDOffre AS id_offre,
                    e.Nom AS nom_etablissement,
                    sp.CodeSpec AS code_specialite,
                    sp.Nom AS nom_specialite,
                    sp.NomFr AS nom_formation,
                    sp.NbrSem AS duree_semestres,
                    s.Nom AS section_nom,
                    COALESCE(ss.NumSem, 1) AS numero_semestre,
                    s.DateDF AS date_debut,
                    s.DateFF AS date_fin,
                    mf.Nom AS nom_mode_formation,
                    b.Nom AS nom_branche,
                    (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection) AS total_inscrits,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND c.Civ IN ('أنثى', 'female', '2', 'أنثي', 'f', 'F')) AS femmes_inscrits,
                    (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection AND a.statut = 'actif') AS total_actifs,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND a.statut = 'actif' AND c.Civ IN ('أنثى', 'female', '2', 'أنثي', 'f', 'F')) AS femmes_actifs,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND c.Nationalite IS NOT NULL AND TRIM(c.Nationalite) != '' AND c.Nationalite NOT IN ('الجزائرية', 'جزائرية', 'algerienne', 'Algerian', 'dz', 'DZ', '1')) AS total_foreigners,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND (c.endicape = 1 OR c.endicape = '1')) AS total_handicapes
                FROM section s
                LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
                LEFT JOIN branche b ON sp.IDBranche = b.IDBranche
                LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                LEFT JOIN mode_formation mf ON s.IDMode_formation = mf.IDMode_formation
                LEFT JOIN section_semestre ss ON s.IDSection = ss.IDSection AND ss.IDSection_Semestre = (
                    SELECT MAX(ss2.IDSection_Semestre) FROM section_semestre ss2 WHERE ss2.IDSection = s.IDSection
                )
                WHERE 1=1
            ";

            $params = [];

            // Role Scoping
            if ($role === 'dfep' && $dfepId > 0) {
                $query .= " AND e.IDDFEP = ? ";
                $params[] = $dfepId;
            } elseif (in_array($role, ['etablissement', 'directeur', 'employee']) && $etabId > 0) {
                $query .= " AND s.IDEts_Form = ? ";
                $params[] = $etabId;
            }

            // Apply HTML Filters
            if ($request->filled('wilaya_id')) {
                $query .= " AND e.IDDFEP = ? ";
                $params[] = (int)$request->wilaya_id;
            }
            if ($request->filled('etab_id')) {
                $query .= " AND s.IDEts_Form = ? ";
                $params[] = (int)$request->etab_id;
            }
            if ($request->filled('branche_id')) {
                $query .= " AND sp.IDBranche = ? ";
                $params[] = (int)$request->branche_id;
            }
            if ($request->filled('mode_id')) {
                $query .= " AND s.IDMode_formation = ? ";
                $params[] = (int)$request->mode_id;
            }
            if ($request->filled('semester')) {
                $query .= " AND ss.NumSem = ? ";
                $params[] = (int)$request->semester;
            }
            if ($request->filled('search')) {
                $q = '%' . $request->search . '%';
                $query .= " AND (sp.Nom LIKE ? OR sp.CodeSpec LIKE ? OR s.Nom LIKE ?) ";
                $params[] = $q;
                $params[] = $q;
                $params[] = $q;
            }

            $query .= " ORDER BY e.Nom ASC, b.Nom ASC, sp.Nom ASC ";

            $rawData = DB::select($query, $params);
            $data = [];
            foreach ($rawData as $item) {
                $arr = (array)$item;
                $arr['section_formatted'] = $this->formatSectionName($arr['section_nom']);
                $data[] = $arr;
            }

            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setRightToLeft(true);
            $sheet->setTitle('حصيلة النشاطات البيداغوجية');

            // Headers matching the Excel layout
            $headers = [
                'A' => 'الرمز',
                'B' => 'رمز الاختصاص',
                'C' => 'التسمية العربية',
                'D' => 'Nom Français',
                'E' => 'رقم السداسي',
                'F' => 'الفوج / القسم',
                'G' => 'بداية التكوين',
                'H' => 'نهاية التكوين',
                'I' => 'عدد المدمجين للمسجلين',
                'J' => 'منهم إناث',
                'K' => 'عدد الأجانب',
                'L' => 'عدد ذوي الاحتياجات الخاصة',
                'M' => 'قيد التكوين (نشط)',
                'N' => 'منهم إناث (نشط)',
                'O' => 'النمط',
                'P' => 'المؤسسة التكوينية',
                'Q' => 'الشعبة المهنية'
            ];

            // Format Headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'name' => 'Cairo',
                    'size' => 11
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F172A']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '475569']
                    ]
                ]
            ];

            $sheet->getRowDimension(1)->setRowHeight(35);

            foreach ($headers as $col => $text) {
                $sheet->setCellValue($col . '1', $text);
                $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Fill data
            $rowIdx = 2;
            $dataStyle = [
                'font' => [
                    'name' => 'Cairo',
                    'size' => 10
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1']
                    ]
                ]
            ];

            foreach ($data as $item) {
                $sheet->setCellValue('A' . $rowIdx, $item['id_offre']);
                $sheet->setCellValue('B' . $rowIdx, $item['code_specialite']);
                $sheet->setCellValue('C' . $rowIdx, $item['nom_specialite']);
                $sheet->setCellValue('D' . $rowIdx, $item['nom_formation']);
                $sheet->setCellValue('E' . $rowIdx, $item['numero_semestre']);
                $sheet->setCellValue('F' . $rowIdx, $item['section_formatted']);
                $sheet->setCellValue('G' . $rowIdx, $item['date_debut'] ? date('Y/m/d', strtotime($item['date_debut'])) : '—');
                $sheet->setCellValue('H' . $rowIdx, $item['date_fin'] ? date('Y/m/d', strtotime($item['date_fin'])) : '—');
                $sheet->setCellValue('I' . $rowIdx, $item['total_inscrits']);
                $sheet->setCellValue('J' . $rowIdx, $item['femmes_inscrits']);
                $sheet->setCellValue('K' . $rowIdx, $item['total_foreigners']);
                $sheet->setCellValue('L' . $rowIdx, $item['total_handicapes']);
                $sheet->setCellValue('M' . $rowIdx, $item['total_actifs']);
                $sheet->setCellValue('N' . $rowIdx, $item['femmes_actifs']);
                $sheet->setCellValue('O' . $rowIdx, $item['nom_mode_formation']);
                $sheet->setCellValue('P' . $rowIdx, $item['nom_etablissement']);
                $sheet->setCellValue('Q' . $rowIdx, $item['nom_branche']);

                $sheet->getStyle('A' . $rowIdx . ':Q' . $rowIdx)->applyFromArray($dataStyle);
                $sheet->getRowDimension($rowIdx)->setRowHeight(25);
                $rowIdx++;
            }

            // Export file as download
            $filename = 'حصيلة_النشاطات_البيداغوجية_محدثة_' . date('Y_m_d_His') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            return back()->with('error', 'حدث خطأ أثناء تصدير ملف إكسيل: ' . $e->getMessage());
        }
    }

    /**
     * AJAX endpoint to return trainees of a section grouped by their residence Wilaya
     */
    public function getSectionTrainees(Request $request)
    {
        try {
            $sectionId = (int)$request->query('section_id');
            if ($sectionId <= 0) {
                return response()->json(['success' => false, 'message' => 'معرف القسم غير صحيح'], 400);
            }

            // Get section details (mode and latest semester)
            $section = DB::selectOne("
                SELECT s.IDMode_formation as mode_formation, s.IDSpecialite,
                       COALESCE(MAX(ss.NumSem), 1) as num_sem
                FROM section s
                LEFT JOIN section_semestre ss ON s.IDSection = ss.IDSection
                WHERE s.IDSection = ?
                GROUP BY s.IDSection, s.IDMode_formation, s.IDSpecialite
            ", [$sectionId]);

            if (!$section) {
                return response()->json(['success' => false, 'message' => 'القسم غير موجود'], 404);
            }

            $mode = $section->mode_formation;
            $semestre = $section->num_sem;

            // Fetch modules for this section and semester
            $matieres = array_map(fn($item) => (array)$item, DB::select("
                SELECT DISTINCT 
                    ssm.IDsection_semestre_Module as id,
                    ssm.NomMdl as libelle_ar,
                    ssm.NomFrMdl as libelle_fr,
                    ssm.coef as coefficient
                FROM section_semestre_module ssm
                JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
                WHERE ss.IDSection = ? AND ss.NumSem = ?
            ", [$sectionId, $semestre]));

            // Fetch trainees of this section
            $trainees = array_map(fn($item) => (array)$item, DB::select("
                SELECT a.IDapprenant as id, a.Nccp as matricule, 
                       c.Nom as nom_ar, c.Prenom as prenom_ar, c.Civ as sexe,
                       COALESCE(ass.IDapprenant_Section_semstre, 0) as ass_id,
                       ass.MoyApr as official_average,
                       ass.Obs as official_decision,
                       COALESCE(ass.NoteStage, 0) as note_stage,
                       COALESCE(ass.NoteMemoire, 0) as note_memoire,
                       COALESCE(ass.NoteSoutenance, 0) as note_soutenance,
                       COALESCE(w.Nom, 'غير محدد') as wilaya_nom,
                       a.statut
                FROM apprenant a
                LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                LEFT JOIN wilaya w ON c.IDWilayaR = w.IDWilayaa
                LEFT JOIN section_semestre ss ON a.IDSection = ss.IDSection AND ss.NumSem = ?
                LEFT JOIN apprenant_section_semstre ass ON a.IDapprenant = ass.IDapprenant AND ass.IDSection_Semestre = ss.IDSection_Semestre
                WHERE a.IDSection = ? AND a.statut = 'actif'
                ORDER BY w.Nom ASC, c.Nom ASC, c.Prenom ASC
            ", [$semestre, $sectionId]));

            $config = \App\Helpers\GradingConfigHelper::read();
            $gradingService = new \App\Domains\Academic\Services\GradingSystemService();

            $formattedTrainees = [];

            foreach ($trainees as $stg) {
                $assId = (int)$stg['ass_id'];
                
                // Use official deliberated data first
                $gpa = $stg['official_average'];
                $decisionText = $stg['official_decision'];

                // If not officially deliberated yet, fallback to dynamic calculation
                if (($gpa === null || $gpa === '') && $assId > 0 && !empty($matieres)) {
                    $gradesList = array_map(fn($item) => (array)$item, DB::select("
                        SELECT IDsection_semestre_Module as ssm_id, NoteC1 as cc1, NoteC2 as cc2, NoteCs as exam, NoteR as rattrapage
                        FROM apprenant_section_semstre_module
                        WHERE IDapprenant_Section_semstre = ?
                    ", [$assId]));

                    $gradesBySsm = [];
                    foreach ($gradesList as $gl) {
                        $gradesBySsm[$gl['ssm_id']] = $gl;
                    }

                    $hasElimination = false;
                    $modulesForGpa = [];

                    foreach ($matieres as $m) {
                        $g = $gradesBySsm[$m['id']] ?? null;
                        $typeM = (strpos(strtolower($m['libelle_ar']), 'stage') !== false || strpos(strtolower($m['libelle_fr']), 'stage') !== false) ? 'stage_pratique' :
                                 ((strpos(strtolower($m['libelle_ar']), 'memoire') !== false || strpos(strtolower($m['libelle_fr']), 'memoire') !== false) ? 'memoire' : 'theorique');

                        $calc = $gradingService->calculateModuleGrade([
                            'type_matiere' => $typeM,
                            'cc1' => $g['cc1'] ?? null,
                            'cc2' => $g['cc2'] ?? null,
                            'exam' => $g['exam'] ?? null,
                            'rattrapage' => $g['rattrapage'] ?? null,
                            'stage' => $stg['note_stage'] ?? null,
                            'memoire' => $stg['note_memoire'] ?? null,
                            'soutenance' => $stg['note_soutenance'] ?? null,
                        ], $config, (int)$mode);

                        if ($calc['is_eliminated']) {
                            $hasElimination = true;
                        }

                        $modulesForGpa[] = [
                            'coefficient' => $m['coefficient'],
                            'note_avr' => $calc['moy_avr'],
                            'note_apr' => $calc['moy_apr']
                        ];
                    }

                    if (!empty($modulesForGpa)) {
                        $semCalc = $gradingService->calculateSemesterGpa($modulesForGpa, $stg['note_stage'], $mode, $config);
                        $gpa = $semCalc['gpa_apr'];
                        $isAdmis = $semCalc['is_admis'] && !$hasElimination;
                        $decisionText = $isAdmis ? 'ناجح' : 'راسب';
                    }
                }

                // Final string formatting fallbacks
                if ($gpa === null || $gpa === '') {
                    $gpaStr = '—';
                } else {
                    $gpaStr = number_format((float)$gpa, 2);
                }

                if (empty($decisionText) || $decisionText === '—') {
                    $decisionText = 'غير مداول';
                }

                $civ = strtolower(trim($stg['sexe'] ?? ''));
                $fullName = trim(($stg['nom_ar'] ?? '') . ' ' . ($stg['prenom_ar'] ?? ''));
                if (empty($fullName)) {
                    $fullName = 'متربص #' . $stg['id'];
                }

                $formattedTrainees[] = [
                    'id' => $stg['id'],
                    'nom' => $fullName,
                    'matricule' => $stg['matricule'],
                    'sexe' => (in_array($civ, ['m', 'checkmark', '1', 'ذكر'])) ? 'ذكر' : 'أنثى',
                    'average' => $gpaStr,
                    'decision' => $decisionText,
                    'statut' => $stg['statut'] === 'actif' ? 'نشط' : 'غير نشط',
                    'wilaya_nom' => $stg['wilaya_nom']
                ];
            }

            return response()->json([
                'success' => true,
                'trainees' => $formattedTrainees
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function formatSectionName(string $name): string
    {
        $name = trim($name);
        
        // Map common words
        $map = [
            'الأول' => '1',
            'الاول' => '1',
            'واحد' => '1',
            'الثاني' => '2',
            'الثاني' => '2',
            'اثنان' => '2',
            'الثالث' => '3',
            'ثلاثة' => '3',
            'الرابع' => '4',
            'أربعة' => '4',
            'الخامس' => '5',
            'خمسة' => '5',
            'السادس' => '6',
            'ستة' => '6',
            'السابع' => '7',
            'سبعة' => '7',
            'الثامن' => '8',
            'ثمانية' => '8',
            'التاسع' => '9',
            'تسعة' => '9',
            'العاشر' => '10',
            'عشرة' => '10',
        ];

        // Try exact/partial matching of words
        foreach ($map as $word => $num) {
            if (mb_strpos($name, $word) !== false) {
                return $num;
            }
        }

        // Extract any existing digit
        $digits = preg_replace('/[^0-9]/', '', $name);
        if ($digits !== '') {
            return $digits;
        }

        return $name;
    }
}
