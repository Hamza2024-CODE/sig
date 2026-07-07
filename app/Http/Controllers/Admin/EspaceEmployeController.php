<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReferenceCache;
use Illuminate\Support\Facades\DB;
use Exception;

class EspaceEmployeController extends Controller
{
    /**
     * Helper to get role and scoping limits for logged in user.
     */
    private function getScope(): array {
        $user     = session('user') ?? [];
        $role     = strtolower($user['role_code'] ?? 'user');
        $iddfep   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
        $etabId   = (int)($user['etablissement_id'] ?? 0);

        return compact('role', 'iddfep', 'etabId');
    }

    /**
     * Helper to get logged-in employee ID.
     */
    private function getAuthenticatedEmployeeId(): ?int {
        if (function_exists('auth') && auth()->check() && auth()->user()) {
            return auth()->user()->employee_id ?? auth()->user()->id;
        }
        return session('user')['id'] ?? session('user')['employee_id'] ?? null;
    }

    /**
     * Display the standalone employee space page with real database entries.
     */
    public function index(Request $request)
    {
        @set_time_limit(300);
        $user = session('user') ?? [];
        if (empty($user)) {
            return redirect()->route('login');
        }

        $scope = $this->getScope();
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        
        // Get filters from request
        $search = $request->query('filter_search');
        $wilaya = $request->query('filter_wilaya');
        $type = $request->query('filter_type');
        $etab = $request->query('filter_etab');
        $page = (int)$request->query('page', 1);
        if ($page < 1) $page = 1;
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Build SQL filters
        $clauses = ['1=1'];
        $params = [];

        // Role scoping
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $clauses[] = "et.IDDFEP = ?";
            $params[] = $scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
            $clauses[] = "enc.IDetablissement = ?";
            $params[] = $scope['etabId'];
        } elseif ($scope['role'] === 'employee') {
            $clauses[] = "enc.IDEncadrement = ?";
            $params[] = (int)$this->getAuthenticatedEmployeeId();
        }

        // Search text
        if (!empty($search)) {
            $clauses[] = "(enc.Nom LIKE ? OR enc.Prenom LIKE ? OR enc.NomFr LIKE ? OR enc.PrenomFr LIKE ? OR enc.IDEncadrement = ? OR enc.nin = ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = (int)$search;
            $params[] = $search;
        }

        // Wilaya filter
        if (!empty($wilaya)) {
            $clauses[] = "et.IDDFEP = ?";
            $params[] = (int)$wilaya;
        }

        // Ets Type filter
        if (!empty($type)) {
            if ($type === 'directorate') {
                $clauses[] = "et.IDNature_etsF = 5";
            } elseif ($type === 'centre') {
                $clauses[] = "et.IDNature_etsF IN (8, 9)";
            } elseif ($type === 'institute') {
                $clauses[] = "et.IDNature_etsF IN (6, 7, 11, 13)";
            } elseif ($type === 'private') {
                $clauses[] = "et.IDNature_etsF = 12";
            }
        }

        // Establishment filter
        if (!empty($etab)) {
            $clauses[] = "enc.IDetablissement = ?";
            $params[] = (int)$etab;
        }

        $whereClause = implode(' AND ', $clauses);

        $db = new \App\Core\LaravelDbAdapter();

        // Fetch Total Count
        $totalCount = 0;
        try {
            $countSql = "SELECT COUNT(*) FROM encadrement enc LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement WHERE $whereClause";
            $stmtCount = $db->prepare($countSql);
            $stmtCount->execute($params);
            $totalCount = (int)$stmtCount->fetchColumn();
        } catch (Exception $e) {}

        // Fetch Employees
        $employees = [];
        try {
            $selectSql = "
                SELECT enc.*, 
                       et.Nom AS etab_nom, 
                       et.NomFr AS etab_fr, 
                       et.IDNature_etsF,
                       w.Nom AS wilaya_nom, 
                       w.IDWilayaa AS id_wilaya
                FROM encadrement enc
                LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
                WHERE $whereClause
                ORDER BY enc.Nom ASC, enc.Prenom ASC
                LIMIT ? OFFSET ?
            ";
            $stmtSelect = $db->prepare($selectSql);
            $i = 1;
            foreach ($params as $paramVal) {
                $stmtSelect->bindValue($i++, $paramVal);
            }
            $stmtSelect->bindValue($i++, $limit, \PDO::PARAM_INT);
            $stmtSelect->bindValue($i, $offset, \PDO::PARAM_INT);
            $stmtSelect->execute();
            $employees = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($employees as &$emp) {
                if (!empty($emp['nin'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($emp['nin']);
                        if ($dec) {
                            $emp['nin'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
                if (!empty($emp['DateNais'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($emp['DateNais']);
                        if ($dec) {
                            $emp['DateNais'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
            }
            unset($emp);
        } catch (Exception $e) {}

        $totalPages = ceil($totalCount / $limit);
        if ($totalPages < 1) $totalPages = 1;

        // Fetch Reference data for filters
        $filter_wilayas = ReferenceCache::wilayas();
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $filter_etablissements = ReferenceCache::etablissementsForDfep($scope['iddfep']);
        } else {
            $filter_etablissements = ReferenceCache::etablissements();
        }

        // Generate API Key if not present
        $apiKey = $user['api_key'] ?? null;
        if (empty($apiKey)) {
            $apiKey = 'sgfep_live_' . substr(hash('sha256', $user['username'] ?? 'default_user'), 0, 32);
            $user['api_key'] = $apiKey;
            session(['user' => $user]);
        }

        return $this->render('admin/espace-employe/index', [
            'employees' => $employees,
            'filter_wilayas' => $filter_wilayas,
            'filter_etablissements' => $filter_etablissements,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'api_key' => $apiKey,
            'scope' => $scope,
            'selected_filters' => compact('search', 'wilaya', 'type', 'etab')
        ]);
    }

    /**
     * AJAX Endpoint to fetch details of a specific employee.
     */
    public function getEmployee($id)
    {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        $id = (int)$id;
        $scope = $this->getScope();
        if ($scope['role'] === 'employee') {
            $id = (int)$this->getAuthenticatedEmployeeId();
        }

        try {
            $sql = "
                SELECT enc.*, 
                       et.Nom AS etab_nom, 
                       et.NomFr AS etab_fr, 
                       et.IDNature_etsF,
                       w.Nom AS wilaya_nom, 
                       w.IDWilayaa AS id_wilaya,
                       g.Nom as grade_nom,
                       f.Nom as fonction_nom
                FROM encadrement enc
                LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
                LEFT JOIN grade g ON enc.IDGrade = g.IDGrade
                LEFT JOIN fonctions f ON enc.IDFonctions = f.IDFonctions
                WHERE enc.IDEncadrement = ?
                LIMIT 1
            ";
            $db = new \App\Core\LaravelDbAdapter();
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $employee = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($employee) {
                if (!empty($employee['nin'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($employee['nin']);
                        if ($dec !== false && $dec !== '') {
                            $employee['nin'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
                if (!empty($employee['DateNais'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($employee['DateNais']);
                        if ($dec !== false && $dec !== '') {
                            $employee['DateNais'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
            }

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود'], 404);
            }

            // Security Scope check
            $scope = $this->getScope();
            if ($scope['role'] === 'employee') {
                if ($id !== (int)session('user')['id']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات موظف آخر'], 403);
                }
            } elseif ($scope['role'] === 'dfep' && $scope['iddfep']) {
                if ((int)$employee['id_wilaya'] !== $scope['iddfep']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات هذا الموظف'], 403);
                }
            } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
                if ((int)$employee['IDetablissement'] !== $scope['etabId']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات هذا الموظف'], 403);
                }
            }

            // ✅ [SECURITY] Log sensitive READ/VIEW operation
            \App\Core\AuditLogger::logRead('encadrement', $id, [
                'name'    => ($employee['Nom'] ?? '') . ' ' . ($employee['Prenom'] ?? ''),
                'nin'     => '***PROTECTED***', // Don't log the actual NIN in details
                'post'    => $employee['Poste'] ?? '',
                'subject' => 'معاينة الملف الشخصي الكامل للموظف'
            ]);

            // Map Family status
            $sitFamilleMap = [
                1 => 'أعزب / عزباء',
                2 => 'متزوج / متزوجة',
                3 => 'مطلق / مطلقة',
                4 => 'أرمل / أرملة',
            ];
            $employee['sitfamille_text'] = $sitFamilleMap[(int)($employee['IDSitfamille'] ?? 1)] ?? 'غير محدد';

            // Generate some beautiful mock components for workspace widget & timeline based on employee data
            $employee['widget_html'] = $this->getDynamicWidgetHtml($employee);
            $employee['timeline_html'] = $this->getDynamicTimelineHtml($employee);
            
            // Document codes
            $employee['paystub_code'] = 'FP-ENC-' . sprintf('%03d', $id % 1000);
            $employee['work_certificate_code'] = 'AT-ENC-' . sprintf('%03d', $id % 1000);

            $employee['secure_id'] = \App\Helpers\SecureIdHelper::encrypt((int)$employee['IDEncadrement']);

            return response()->json([
                'success' => true,
                'employee' => $employee
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX Endpoint to update employee details and handle photo upload.
     */
    public function updateEmployee(Request $request, $id)
    {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        $id = (int)$id;
        $scope = $this->getScope();
        if ($scope['role'] === 'employee') {
            $id = (int)$this->getAuthenticatedEmployeeId();
        }

        try {
            $db = new \App\Core\LaravelDbAdapter();
            // Find employee
            $sqlExist = "
                SELECT enc.IDetablissement, et.IDDFEP 
                FROM encadrement enc 
                LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement 
                WHERE enc.IDEncadrement = ? 
                LIMIT 1
            ";
            $stmtExist = $db->prepare($sqlExist);
            $stmtExist->execute([$id]);
            $existing = $stmtExist->fetch(\PDO::FETCH_ASSOC);

            if (!$existing) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود'], 404);
            }

            // Security Scope check
            if ($scope['role'] === 'employee') {
                if ($id !== (int)$this->getAuthenticatedEmployeeId()) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل بيانات موظف آخر'], 403);
                }
            } elseif ($scope['role'] === 'dfep' && $scope['iddfep']) {
                if ((int)$existing['IDDFEP'] !== $scope['iddfep']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل بيانات هذا الموظف'], 403);
                }
            } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
                if ((int)$existing['IDetablissement'] !== $scope['etabId']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل بيانات هذا الموظف'], 403);
                }
            }

            // Handle file upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                if ($file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $newFileName = 'emp_' . $id . '_' . time() . '.jpg';
                        $uploadDir = public_path('uploads/employees');
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $destPath = $uploadDir . '/' . $newFileName;
                        $this->resizeImageAndSave($file->getRealPath(), $destPath, 300, 300);
                        $photoPath = '/uploads/employees/' . $newFileName;
                    }
                }
            }

            // Build update fields (nin is explicitly omitted to block editing)
            $data = [
                'Nom' => $request->input('nom') ?? '',
                'Prenom' => $request->input('prenom') ?? '',
                'NomFr' => $request->input('nom_fr') ?? '',
                'PrenomFr' => $request->input('prenom_fr') ?? '',
                'DateNais' => $request->input('date_nais') ?? '',
                'LieuNais' => $request->input('lieu_nais') ?? '',
                'Tel' => $request->input('tel') ?? '',
                'Email' => $request->input('email') ?? '',
                'Adres' => $request->input('adres') ?? '',
                'nbrEnf' => (int)$request->input('nbr_enfants', 0),
                'nbrenfscol' => (int)$request->input('nbr_enfants_scol', 0),
                'Echlo' => (int)$request->input('echelon', 0),
                'nss' => $request->input('nss') ?? '',
                'Civ' => (int)($request->input('civ') ?? 1),
                'IDSitfamille' => (int)($request->input('sitfamille') ?? 1),
                'Specialite' => $request->input('specialite') ?? '',
                'TachesPrincipale' => $request->input('taches_principale') ?? '',
                'Daterecr' => $request->input('daterecr') ?? null
            ];

            if ($photoPath) {
                $data['photo'] = $photoPath;
            }

            // Update candidate record
            $updateParts = [];
            $values = [];
            foreach ($data as $col => $val) {
                $updateParts[] = "`$col` = ?";
                $values[] = $val;
            }
            $values[] = $id;

            $sql = "UPDATE encadrement SET " . implode(', ', $updateParts) . " WHERE IDEncadrement = ?";
            $stmtUpdate = $db->prepare($sql);
            $stmtUpdate->execute($values);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الموظف بنجاح!'
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Dynamic Professional Widget based on employee details.
     */
    private function getDynamicWidgetHtml($emp)
    {
        $specialty = htmlspecialchars($emp['Specialite'] ?? 'غير محددة');
        $task = htmlspecialchars($emp['TachesPrincipale'] ?? 'مؤطر بيداغوجي');
        return '
        <div class="table-responsive">
            <table class="table table-hover border-0 align-middle text-right" style="font-family:\'Cairo\';font-size:0.8rem;">
                <thead class="bg-light text-dark fw-bold">
                    <tr>
                        <th class="border-0">المادة البيداغوجية / النشاط</th>
                        <th class="border-0">التخصص التكويني</th>
                        <th class="border-0">طبيعة التكليف</th>
                        <th class="border-0">المعدل الساعاتي أسبوعياً</th>
                        <th class="border-0">الحالة البيداغوجية</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>' . $task . '</strong></td>
                        <td>' . $specialty . '</td>
                        <td>تأطير بيداغوجي وتسيير ورشات</td>
                        <td>18 ساعة / أسبوع</td>
                        <td><span class="badge bg-success rounded-pill px-3 py-1">مكتملة ومؤمنة</span></td>
                    </tr>
                    <tr>
                        <td><strong>أعمال تطبيقية ومرافقة متمهنين</strong></td>
                        <td>' . $specialty . '</td>
                        <td>زيارات ميدانية للمؤسسات الاقتصادية</td>
                        <td>6 ساعات / أسبوع</td>
                        <td><span class="badge bg-primary rounded-pill px-3 py-1">جارٍ التنفيذ</span></td>
                    </tr>
                </tbody>
            </table>
        </div>';
    }

    /**
     * Dynamic Career Timeline based on employee details.
     */
    private function getDynamicTimelineHtml($emp)
    {
        $dateRecr = htmlspecialchars($emp['Daterecr'] ?? '2021-05-02');
        $echelon = (int)($emp['Echlo'] ?? 1);
        $spec = htmlspecialchars($emp['Specialite'] ?? 'التخصص المهني');
        return '
        <div class="mb-3 position-relative">
            <span class="position-absolute bg-success rounded-circle" style="right:-29px;top:5px;width:12px;height:12px;border:3px solid #fff;"></span>
            <div class="fw-bold text-dark" style="font-size:0.85rem;">آخر ترقية اختيارية في الدرجة</div>
            <div class="text-muted small">تمت الترقية إلى الدرجة ' . $echelon . ' بناءً على تقييم الأداء السنوي.</div>
        </div>
         <div class="mb-3 position-relative">
             <span class="position-absolute bg-primary rounded-circle" style="right:-29px;top:5px;width:12px;height:12px;border:3px solid #fff;"></span>
             <div class="fw-bold text-dark" style="font-size:0.85rem;">إثبات التوظيف الأول وتأكيد الرتبة</div>
             <div class="text-muted small">' . $dateRecr . ' • تم التثبيت كعضو دائم في سلك التأطير لتخصص ' . $spec . '.</div>
         </div>';
     }

    /**
     * Image resizing helper using GD.
     */
    private function resizeImageAndSave(string $sourcePath, string $destPath, int $maxWidth, int $maxHeight)
    {
        list($width, $height, $type) = getimagesize($sourcePath);
        
        $srcImg = null;
        switch ($type) {
            case IMAGETYPE_JPEG: $srcImg = imagecreatefromjpeg($sourcePath); break;
            case IMAGETYPE_PNG:  $srcImg = imagecreatefrompng($sourcePath); break;
            case IMAGETYPE_GIF:  $srcImg = imagecreatefromgif($sourcePath); break;
            default:             throw new \Exception('نوع الصورة غير مدعوم');
        }
        
        if (!$srcImg) {
            throw new \Exception('تعذر قراءة الصورة');
        }
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        if ($ratio < 1) {
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        $destImg = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($destImg, 255, 255, 255);
        imagefill($destImg, 0, 0, $white);
        
        imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($destImg, $destPath, 85);
        
        imagedestroy($srcImg);
        imagedestroy($destImg);
    }

    /**
     * Serve the digital ID cards management control center.
     */
    public function digitalCards(Request $request)
    {
        @set_time_limit(300);
        $user = session('user') ?? [];
        if (empty($user)) {
            return redirect()->route('login');
        }
        $db = new \App\Core\LaravelDbAdapter();
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);

        $scope = $this->getScope();
        $type = $request->query('type', 'employee'); // 'employee' or 'trainee'
        $search = $request->query('filter_search');
        $wilaya = $request->query('filter_wilaya');
        $etab = $request->query('filter_etab');
        $mode = $request->query('filter_mode');
        $branche = $request->query('filter_branche');
        $grade = $request->query('filter_grade');
        $fonction = $request->query('filter_fonction');
        $page = (int)$request->query('page', 1);
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $clauses = ['1=1'];
        $params = [];

        // Role scoping
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $clauses[] = $type === 'employee' ? "et.IDDFEP = ?" : "e.IDDFEP = ?";
            $params[] = $scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
            $clauses[] = $type === 'employee' ? "enc.IDetablissement = ?" : "COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0)) = ?";
            $params[] = $scope['etabId'];
        }

        if ($type === 'employee') {
            if (!empty($search)) {
                $clauses[] = "(enc.Nom LIKE ? OR enc.Prenom LIKE ? OR enc.NomFr LIKE ? OR enc.PrenomFr LIKE ? OR enc.IDEncadrement = ? OR enc.nin = ? OR enc.Specialite LIKE ? OR g.Nom LIKE ? OR f.Nom LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = (int)$search;
                $params[] = $search;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            if (!empty($wilaya)) {
                $clauses[] = "et.IDDFEP = ?";
                $params[] = (int)$wilaya;
            }
            if (!empty($etab)) {
                $clauses[] = "enc.IDetablissement = ?";
                $params[] = (int)$etab;
            }
            if (!empty($grade)) {
                $clauses[] = "enc.IDGrade = ?";
                $params[] = (int)$grade;
            }
            if (!empty($fonction)) {
                $clauses[] = "enc.IDFonctions = ?";
                $params[] = (int)$fonction;
            }

            $whereClause = implode(' AND ', $clauses);

            // Fetch Total Count
            $totalCount = 0;
            try {
                $countSql = "SELECT COUNT(*) FROM encadrement enc LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement LEFT JOIN grade g ON enc.IDGrade = g.IDGrade LEFT JOIN fonctions f ON enc.IDFonctions = f.IDFonctions WHERE $whereClause";
                $stmtCount = $db->prepare($countSql);
                $stmtCount->execute($params);
                $totalCount = (int)$stmtCount->fetchColumn();
            } catch (Exception $e) {}

            // Fetch Employees
            $records = [];
            try {
                $selectSql = "
                    SELECT enc.IDEncadrement as id, enc.Nom as nom, enc.Prenom as prenom, 
                           enc.NomFr as nom_fr, enc.PrenomFr as prenom_fr, enc.nin, enc.nss,
                           enc.Specialite as spec_ar, et.Nom AS etab_nom,
                           g.Nom as grade_nom, f.Nom as fonction_nom, enc.TachesPrincipale
                    FROM encadrement enc
                    LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                    LEFT JOIN grade g ON enc.IDGrade = g.IDGrade
                    LEFT JOIN fonctions f ON enc.IDFonctions = f.IDFonctions
                    WHERE $whereClause
                    ORDER BY enc.Nom ASC, enc.Prenom ASC
                    LIMIT ? OFFSET ?
                ";
                $stmtSelect = $db->prepare($selectSql);
                $i = 1;
                foreach ($params as $paramVal) {
                    $stmtSelect->bindValue($i++, $paramVal);
                }
                $stmtSelect->bindValue($i++, $limit, \PDO::PARAM_INT);
                $stmtSelect->bindValue($i, $offset, \PDO::PARAM_INT);
                $stmtSelect->execute();
                $records = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

        } else {
            $clauses1 = ['1=1'];
            $clauses2 = ['s.IDSection IS NULL'];
            $params1 = [];
            $params2 = [];

            // Role scoping
            if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $clauses1[] = "w.IDWilayaa = ?";
                $params1[] = $scope['iddfep'];
                
                $clauses2[] = "w.IDWilayaa = ?";
                $params2[] = $scope['iddfep'];
            } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
                $clauses1[] = "s.IDEts_Form = ?";
                $params1[] = $scope['etabId'];
                
                $clauses2[] = "o_cand.IDEts_Form = ?";
                $params2[] = $scope['etabId'];
            }

            // Search text
            if (!empty($search)) {
                $searchTerm = "%$search%";
                $clauses1[] = "(c.Nom LIKE ? OR c.Prenom LIKE ? OR c.NomFr LIKE ? OR c.PrenomFr LIKE ? OR a.IDapprenant = ? OR c.nin = ? OR a.Nccp = ? OR sp.Nom LIKE ? OR e.Nom LIKE ?)";
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;
                $params1[] = (int)$search;
                $params1[] = $search;
                $params1[] = $search;
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;

                $clauses2[] = "(c.Nom LIKE ? OR c.Prenom LIKE ? OR c.NomFr LIKE ? OR c.PrenomFr LIKE ? OR a.IDapprenant = ? OR c.nin = ? OR a.Nccp = ? OR sp.Nom LIKE ? OR e.Nom LIKE ?)";
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
                $params2[] = (int)$search;
                $params2[] = $search;
                $params2[] = $search;
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
            }

            // Wilaya filter
            if (!empty($wilaya)) {
                $clauses1[] = "w.IDWilayaa = ?";
                $params1[] = (int)$wilaya;
                
                $clauses2[] = "w.IDWilayaa = ?";
                $params2[] = (int)$wilaya;
            }

            // Etab filter
            if (!empty($etab)) {
                $clauses1[] = "s.IDEts_Form = ?";
                $params1[] = (int)$etab;
                
                $clauses2[] = "o_cand.IDEts_Form = ?";
                $params2[] = (int)$etab;
            }

            // Mode filter
            if (!empty($mode)) {
                $clauses1[] = "s.IDMode_formation = ?";
                $params1[] = $mode;
                
                $clauses2[] = "o_cand.IDMode_formation = ?";
                $params2[] = $mode;
            }

            // Branche filter
            if (!empty($branche)) {
                $clauses1[] = "sp.IDBranche = ?";
                $params1[] = (int)$branche;
                
                $clauses2[] = "sp.IDBranche = ?";
                $params2[] = (int)$branche;
            }

            $whereClause1 = implode(' AND ', $clauses1);
            $whereClause2 = implode(' AND ', $clauses2);
            $allParams = array_merge($params1, $params2);
            
            $isUnfiltered = ($whereClause1 === '1=1' && $whereClause2 === 's.IDSection IS NULL');

            // Fetch Total Count
            $totalCount = 0;
            try {
                if ($isUnfiltered) {
                    $countSql = "SELECT COUNT(*) FROM apprenant";
                    $countCacheKey = 'trainees_count_fast_all';
                } else {
                    $countSql = "
                        SELECT SUM(cnt) FROM (
                            SELECT COUNT(*) as cnt
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            JOIN section s ON a.IDSection = s.IDSection
                            LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause1
                            
                            UNION ALL
                            
                            SELECT COUNT(*) as cnt
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            LEFT JOIN section s ON a.IDSection = s.IDSection
                            JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
                            LEFT JOIN specialite sp ON o_cand.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON o_cand.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause2
                        ) tmp
                    ";
                    $countCacheKey = 'trainees_count_' . md5($countSql . serialize($allParams));
                }

                $totalCount = cache()->remember($countCacheKey, 300, function() use ($db, $countSql, $allParams, $isUnfiltered) {
                    $stmtCount = $db->prepare($countSql);
                    if (!$isUnfiltered) {
                        $stmtCount->execute($allParams);
                    } else {
                        $stmtCount->execute();
                    }
                    return (int)$stmtCount->fetchColumn();
                });
            } catch (Exception $e) {}

            // Fetch Trainees
            $records = [];
            try {
                if ($isUnfiltered) {
                    $selectSql = "
                        SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                               c.Nom as nom, c.Prenom as prenom, 
                               c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
                               sp.Nom as spec_ar, e.Nom AS etab_nom
                        FROM apprenant a
                        JOIN candidat c ON a.IDCandidat = c.IDCandidat
                        LEFT JOIN section s ON a.IDSection = s.IDSection
                        LEFT JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
                        LEFT JOIN specialite sp ON sp.IDSpecialite = COALESCE(NULLIF(s.IDSpecialite, 0), NULLIF(o_cand.IDSpecialite, 0))
                        LEFT JOIN etablissement e ON e.IDetablissement = COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o_cand.IDEts_Form, 0))
                        ORDER BY a.IDapprenant DESC
                        LIMIT ? OFFSET ?
                    ";
                    $stmtSelect = $db->prepare($selectSql);
                    $stmtSelect->bindValue(1, $limit, \PDO::PARAM_INT);
                    $stmtSelect->bindValue(2, $offset, \PDO::PARAM_INT);
                    $stmtSelect->execute();
                } else {
                    $selectSql = "
                        SELECT id, numero_matricule, nom, prenom, nom_fr, prenom_fr, nin, nss, spec_ar, etab_nom
                        FROM (
                            SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                                   c.Nom as nom, c.Prenom as prenom, 
                                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
                                   sp.Nom as spec_ar, e.Nom AS etab_nom
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            JOIN section s ON a.IDSection = s.IDSection
                            LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause1
                            
                            UNION ALL
                            
                            SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                                   c.Nom as nom, c.Prenom as prenom, 
                                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
                                   sp.Nom as spec_ar, e.Nom AS etab_nom
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            LEFT JOIN section s ON a.IDSection = s.IDSection
                            JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
                            LEFT JOIN specialite sp ON o_cand.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON o_cand.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause2
                        ) tmp
                        ORDER BY id DESC
                        LIMIT ? OFFSET ?
                    ";
                    $stmtSelect = $db->prepare($selectSql);
                    $i = 1;
                    foreach ($allParams as $paramVal) {
                        $stmtSelect->bindValue($i++, $paramVal);
                    }
                    $stmtSelect->bindValue($i++, $limit, \PDO::PARAM_INT);
                    $stmtSelect->bindValue($i, $offset, \PDO::PARAM_INT);
                    $stmtSelect->execute();
                }
                
                $records = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }

        foreach ($records as &$rec) {
            if (!empty($rec['nin'])) {
                try {
                    $dec = \Illuminate\Support\Facades\Crypt::decryptString($rec['nin']);
                    if ($dec !== false && $dec !== '') {
                        $rec['nin'] = $dec;
                    }
                } catch (\Exception $e) {}
            }
        }
        unset($rec);

        $totalPages = ceil($totalCount / $limit);
        if ($totalPages < 1) $totalPages = 1;

        $filter_wilayas = ReferenceCache::wilayas();
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $filter_etablissements = ReferenceCache::etablissementsForDfep($scope['iddfep']);
        } else {
            $filter_etablissements = ReferenceCache::etablissements();
        }
        $filter_branches = ReferenceCache::branches();
        $filter_modes = ReferenceCache::modesFormation();

        $filter_grades = DB::select("SELECT IDGrade as id, Nom as nom_ar FROM grade ORDER BY Nom ASC");
        $filter_fonctions = DB::select("SELECT IDFonctions as id, Nom as nom_ar FROM fonctions ORDER BY Nom ASC");
        $filter_grades = array_map(fn($r) => (array)$r, $filter_grades);
        $filter_fonctions = array_map(fn($r) => (array)$r, $filter_fonctions);

        return $this->render('admin/digital-cards/index', [
            'type' => $type,
            'records' => $records,
            'filter_wilayas' => $filter_wilayas,
            'filter_etablissements' => $filter_etablissements,
            'filter_branches' => $filter_branches,
            'filter_modes' => $filter_modes,
            'filter_grades' => $filter_grades,
            'filter_fonctions' => $filter_fonctions,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'scope' => $scope,
            'selected_filters' => compact('search', 'wilaya', 'etab', 'mode', 'branche', 'grade', 'fonction')
        ]);
    }

    /**
     * AJAX endpoint to retrieve full trainee details with role scoping checks.
     */
    public function getTrainee($id)
    {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        $id = (int)$id;
        try {
            $sql = "
                SELECT a.IDapprenant as id, a.Nccp as numero_matricule, 
                       c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                       c.nin, c.nss, c.photo, c.Civ, c.DateNais, c.LieuNais,
                       sp.Nom as spec_ar, e.Nom as etab_nom, w.Nom as wilaya_nom, w.IDWilayaa as id_wilaya,
                       mf.Nom as mode_nom, s.DateDF as date_deb, s.DateFF as date_fin,
                       ar.Nom as regime_nom
                FROM apprenant a
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                LEFT JOIN section s ON a.IDSection = s.IDSection
                LEFT JOIN offre o ON o.IDOffre = COALESCE(NULLIF(s.IDOffre, 0), NULLIF(c.IDOffre, 0))
                LEFT JOIN specialite sp ON sp.IDSpecialite = COALESCE(NULLIF(s.IDSpecialite, 0), NULLIF(o.IDSpecialite, 0))
                LEFT JOIN etablissement e ON e.IDetablissement = COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0))
                LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                LEFT JOIN mode_formation mf ON mf.IDMode_formation = COALESCE(NULLIF(s.IDMode_formation, 0), NULLIF(o.IDMode_formation, 0))
                LEFT JOIN apprenant_section_semstre ass ON a.IDapprenant = ass.IDapprenant
                LEFT JOIN apprenant_regime ar ON ass.IDapprenant_Regime = ar.IDapprenant_Regime
                WHERE a.IDapprenant = ?
                LIMIT 1
            ";
            $trainee = DB::selectOne($sql, [$id]);
            if (!$trainee) {
                return response()->json(['success' => false, 'message' => 'المتربص غير موجود'], 404);
            }
            
            $trainee = (array)$trainee;
            
            // Check scope permissions for trainees matching the user's role limits
            $scope = $this->getScope();
            if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                if ((int)$trainee['id_wilaya'] !== $scope['iddfep']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات هذا المتربص'], 403);
                }
            } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
                $etabCheck = DB::table('apprenant as a')
                    ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                    ->leftJoin('section as s', 'a.IDSection', '=', 's.IDSection')
                    ->leftJoin('offre as o', 'o.IDOffre', '=', DB::raw('COALESCE(NULLIF(s.IDOffre, 0), NULLIF(c.IDOffre, 0))'))
                    ->where('a.IDapprenant', $id)
                    ->value(DB::raw('COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0))'));
                if ((int)$etabCheck !== $scope['etabId']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات هذا المتربص'], 403);
                }
            }
            
            // ✅ [SECURITY] Log sensitive READ/VIEW operation
            \App\Core\AuditLogger::logRead('apprenant', $id, [
                'name'    => ($trainee['nom_ar'] ?? '') . ' ' . ($trainee['prenom_ar'] ?? ''),
                'nin'     => '***PROTECTED***',
                'matricule' => $trainee['numero_matricule'] ?? '',
                'subject' => 'معاينة الملف الشخصي للمتربص'
            ]);

            if ($trainee) {
                if (!empty($trainee['nin'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($trainee['nin']);
                        if ($dec !== false && $dec !== '') {
                            $trainee['nin'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
                if (!empty($trainee['DateNais'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($trainee['DateNais']);
                        if ($dec !== false && $dec !== '') {
                            $trainee['DateNais'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
            }
            
            $trainee['secure_id'] = \App\Helpers\SecureIdHelper::encrypt((int)$trainee['id']);

            return response()->json([
                'success' => true,
                'trainee' => $trainee
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
