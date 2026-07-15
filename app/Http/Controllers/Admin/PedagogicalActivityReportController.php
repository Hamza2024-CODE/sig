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
     * Display report with filters
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
            
            $etablissements = [];
            if (in_array($role, ['admin', 'central', 'ministre'])) {
                $etablissements = DB::select("SELECT IDetablissement as id, Nom as nom FROM etablissement ORDER BY Nom ASC");
            } elseif ($role === 'dfep' && $dfepId > 0) {
                $etablissements = DB::select("SELECT IDetablissement as id, Nom as nom FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC", [$dfepId]);
            }

            $modes = DB::select("SELECT IDMode_formation as id, Nom as nom FROM mode_formation ORDER BY Nom ASC");

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
                    -- Trainees stats:
                    (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection) AS total_inscrits,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND (c.sexe = 'F' OR c.sexe = '2' OR c.sexe = 'أنثى' OR c.sexe = 'أنثي')) AS femmes_inscrits,
                    (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection AND a.statut = 'actif') AS total_actifs,
                    (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND a.statut = 'actif' AND (c.sexe = 'F' OR c.sexe = '2' OR c.sexe = 'أنثى' OR c.sexe = 'أنثي')) AS femmes_actifs
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

            $data = array_map(fn($item) => (array)$item, DB::select($query, $params));

            return view('admin.reports.pedagogical_activities', compact('data', 'branches', 'etablissements', 'modes', 'user'));
        } catch (\Throwable $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
        }
    }

    /**
     * Export the filtered list to an Excel spreadsheet matching the user template structure
     */
    public function exportExcel(Request $request)
    {
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
                (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND (c.sexe = 'F' OR c.sexe = '2' OR c.sexe = 'أنثى' OR c.sexe = 'أنثي')) AS femmes_inscrits,
                (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection AND a.statut = 'actif') AS total_actifs,
                (SELECT COUNT(*) FROM apprenant a JOIN candidat c ON a.IDCandidat = c.IDCandidat WHERE a.IDSection = s.IDSection AND a.statut = 'actif' AND (c.sexe = 'F' OR c.sexe = '2' OR c.sexe = 'أنثى' OR c.sexe = 'أنثي')) AS femmes_actifs
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

        $data = array_map(fn($item) => (array)$item, DB::select($query, $params));

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
            'I' => 'العدد الكلي للمسجلين',
            'J' => 'منهم إناث',
            'K' => 'قيد التكوين (نشط)',
            'L' => 'منهم إناث (نشط)',
            'M' => 'النمط',
            'N' => 'المؤسسة التكوينية',
            'O' => 'الشعبة المهنية'
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
                'startColor' => ['rgb' => '0F172A'] // Platform dark color
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
            $sheet->getColumnDimension($col)->setAutoWidth(true);
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
            $sheet->setCellValue('F' . $rowIdx, $item['section_nom']);
            $sheet->setCellValue('G' . $rowIdx, $item['date_debut'] ? date('Y/m/d', strtotime($item['date_debut'])) : '—');
            $sheet->setCellValue('H' . $rowIdx, $item['date_fin'] ? date('Y/m/d', strtotime($item['date_fin'])) : '—');
            $sheet->setCellValue('I' . $rowIdx, $item['total_inscrits']);
            $sheet->setCellValue('J' . $rowIdx, $item['femmes_inscrits']);
            $sheet->setCellValue('K' . $rowIdx, $item['total_actifs']);
            $sheet->setCellValue('L' . $rowIdx, $item['femmes_actifs']);
            $sheet->setCellValue('M' . $rowIdx, $item['nom_mode_formation']);
            $sheet->setCellValue('N' . $rowIdx, $item['nom_etablissement']);
            $sheet->setCellValue('O' . $rowIdx, $item['nom_branche']);

            $sheet->getStyle('A' . $rowIdx . ':O' . $rowIdx)->applyFromArray($dataStyle);
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
    }
}
