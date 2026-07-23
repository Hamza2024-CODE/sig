<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class UtilisateursController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role_code = strtolower($user['role_code'] ?? '');
        $allowedRoles = ['admin', 'dfep', 'directeur', 'etablissement'];
        if (!in_array($role_code, $allowedRoles)) {
            session(['flash_error' => 'غير مصرح لك بالولوج إلى هذه الصفحة / Non autorisé.']);
            return redirect()->route('dashboard');
        }

        // المدير/المؤسسة: يرى فقط موظفي مؤسسته
        $isEtabRole = in_array($role_code, ['directeur', 'etablissement']);
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? $user['IDetablissement'] ?? 0);

        $users = [];
        $wilayas = [];
        $etablissements = [];
        $totalCount = 0;
        $page = max(1, (int)$request->input('page', 1));
        $perPage = 30;
        $totalPages = 1;
        $search = trim($request->input('search', ''));

        try {
            $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? $user['wilaya_id'] ?? $user['IDWilayaa'] ?? 0);
            $selWilaya = (int)$request->input('wilaya_id', 0);
            $selEtab   = (int)$request->input('etablissement_id', 0);
            $selStatus = trim($request->input('status', '')); // 'active', 'suspended' or ''

            if ($role_code === 'dfep' && $dfepId > 0) {
                $selWilaya = $dfepId;
            }

            // ── 1. Count uQuery (utilisateur table) — not accessible to etab roles ──
            $whereU = [];
            $paramsU = [];
            if ($isEtabRole) {
                $whereU[] = '1=0'; // etab role doesn't manage utilisateur table
            } elseif ($selWilaya > 0) {
                $whereU[] = "((u.Code = ? AND u.IDNature = 4) OR (u.IDBureau = ? AND u.IDNature = 4) OR (e.IDDFEP = ?))";
                $paramsU[] = $selWilaya;
                $paramsU[] = $selWilaya;
                $paramsU[] = $selWilaya;
            }
            if ($selEtab > 0) {
                $whereU[] = "u.IDBureau = ?";
                $paramsU[] = $selEtab;
            }
            if ($selStatus === 'active') {
                $whereU[] = "u.activee = 0";
            } elseif ($selStatus === 'suspended') {
                $whereU[] = "u.activee = 1";
            }
            $matchingUserIds = [0];
            if ($search !== '') {
                try {
                    $allEncUsers = DB::select("SELECT IDUtilisateur, NomUser FROM utilisateur WHERE NomUser LIKE '@%'");
                    foreach ($allEncUsers as $eu) {
                        try {
                            $decrypted = decrypt(substr($eu->NomUser, 1), false);
                            if ($decrypted && mb_strpos($decrypted, $search) !== false) {
                                $matchingUserIds[] = (int)$eu->IDUtilisateur;
                            }
                        } catch (\Exception $ex) {}
                    }
                } catch (\Exception $ex) {}
            }
            if ($search !== '') {
                $whereU[] = "(u.NomUser LIKE ? OR u.Nom LIKE ? OR u.IDUtilisateur IN (" . implode(',', $matchingUserIds) . "))";
                $paramsU[] = "%{$search}%";
                $paramsU[] = "%{$search}%";
            }
            $whereUSQL = !empty($whereU) ? 'WHERE ' . implode(' AND ', $whereU) : '';
            $countU = DB::selectOne("
                SELECT COUNT(*) as c 
                FROM utilisateur u
                LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                {$whereUSQL}
            ", $paramsU)->c;

            // ── 2. Count eQuery (etablissement accounts) — not for etab roles ──
            $whereE = ["e.nomUser IS NOT NULL AND e.nomUser != ''"];
            $paramsE = [];
            if ($isEtabRole) {
                $whereE[] = '1=0'; // etab role doesn't manage etablissement accounts
            } elseif ($selWilaya > 0) {
                $whereE[] = "e.IDDFEP = ?";
                $paramsE[] = $selWilaya;
            }
            if ($selEtab > 0) {
                $whereE[] = "e.IDetablissement = ?";
                $paramsE[] = $selEtab;
            }
            if ($selStatus === 'active') {
                $whereE[] = "e.activee = 0";
            } elseif ($selStatus === 'suspended') {
                $whereE[] = "e.activee = 1";
            }
            if ($search !== '') {
                $whereE[] = "(e.nomUser LIKE ? OR e.Nom LIKE ?)";
                $paramsE[] = "%{$search}%";
                $paramsE[] = "%{$search}%";
            }
            $whereESQL = !empty($whereE) ? 'WHERE ' . implode(' AND ', $whereE) : '';
            $countE = DB::selectOne("
                SELECT COUNT(*) as c 
                FROM etablissement e
                {$whereESQL}
            ", $paramsE)->c;

            // ── 3. Count encQuery (encadrement — موظفو المؤسسة) ──
            $whereEnc = ["enc.nin IS NOT NULL AND enc.nin != '' AND enc.MotDePass IS NOT NULL AND enc.MotDePass != ''"];
            $paramsEnc = [];
            if ($isEtabRole && $etabId > 0) {
                $whereEnc[] = "(enc.IDetablissement = ? OR enc.IDEts_Form = ?)";
                $paramsEnc[] = $etabId;
                $paramsEnc[] = $etabId;
            } elseif ($selWilaya > 0) {
                $whereEnc[] = "(e.IDDFEP = ? OR et_form.IDDFEP = ?)";
                $paramsEnc[] = $selWilaya;
                $paramsEnc[] = $selWilaya;
            }
            if ($selEtab > 0) {
                $whereEnc[] = "(enc.IDetablissement = ? OR enc.IDEts_Form = ?)";
                $paramsEnc[] = $selEtab;
                $paramsEnc[] = $selEtab;
            }
            if ($selStatus === 'suspended') {
                $whereEnc[] = "1=0";
            }
            $matchingEncIds = [0];
            if ($search !== '') {
                try {
                    $allEncStaff = DB::select("SELECT IDEncadrement, nin FROM encadrement WHERE nin IS NOT NULL AND nin <> ''");
                    foreach ($allEncStaff as $es) {
                        try {
                            $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($es->nin);
                            if ($decrypted && mb_strpos($decrypted, $search) !== false) {
                                $matchingEncIds[] = (int)$es->IDEncadrement;
                            }
                        } catch (\Exception $ex) {}
                    }
                } catch (\Exception $ex) {}
            }
            if ($search !== '') {
                $whereEnc[] = "(enc.nin LIKE ? OR enc.Nom LIKE ? OR enc.Prenom LIKE ? OR enc.IDEncadrement IN (" . implode(',', $matchingEncIds) . "))";
                $paramsEnc[] = "%{$search}%";
                $paramsEnc[] = "%{$search}%";
                $paramsEnc[] = "%{$search}%";
            }
            $whereEncSQL = !empty($whereEnc) ? 'WHERE ' . implode(' AND ', $whereEnc) : '';
            $countEnc = DB::selectOne("
                SELECT COUNT(*) as c 
                FROM encadrement enc
                LEFT JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                LEFT JOIN etablissement et_form ON enc.IDEts_Form = et_form.IDetablissement
                {$whereEncSQL}
            ", $paramsEnc)->c;

            $totalCount = $countU + $countE + $countEnc;
            $totalPages = $totalCount > 0 ? (int)ceil($totalCount / $perPage) : 1;
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $perPage;

            // ── 4. Fetch Paginated UNION ──
            $unionQuery = "
                SELECT u.IDUtilisateur as id, u.NomUser as username, u.Nom as nom_complet,
                       '' as email, u.admin, IF(u.activee = 0, 1, 0) as est_actif,
                       u.IDBureau as etablissement_id, e.Nom as etab_nom,
                       IF(u.IDNature = 4, u.Code, e.IDDFEP) as wilaya_id, '' as wilaya_nom,
                       '' as last_login,
                       '2026-01-01 00:00:00' as created_at,
                        CASE 
                            WHEN u.admin = 1 THEN 'admin'
                            WHEN u.IDNature = 4 THEN 'dfep'
                            WHEN u.IDNature = 2 THEN 'directeur'
                            WHEN u.IDNature = 3 THEN 'formateur'
                            WHEN u.IDNature = 5 THEN 'stagiaire'
                            ELSE 'special'
                        END as role_code,
                        CASE 
                            WHEN u.admin = 1 THEN 'مدير النظام'
                            WHEN u.IDNature = 4 THEN 'DFEP ولائي'
                            WHEN u.IDNature = 2 THEN 'مدير مؤسسة'
                            WHEN u.IDNature = 3 THEN 'مكوّن'
                            WHEN u.IDNature = 5 THEN 'متربص'
                            ELSE 'حساب خاص'
                        END as role_ar,
                        '' as api_key,
                        '{}' as permissions,
                        'utilisateur' as source_table,
                        u.avatar as avatar
                FROM utilisateur u
                LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                {$whereUSQL}

                UNION ALL

                SELECT e.IDetablissement as id, e.nomUser as username, e.Nom as nom_complet,
                       e.Email as email, 0 as admin, IF(e.activee = 0, 1, 0) as est_actif,
                       e.IDetablissement as etablissement_id, e.Nom as etab_nom,
                       e.IDDFEP as wilaya_id, '' as wilaya_nom,
                       '' as last_login,
                       '2026-01-01 00:00:00' as created_at,
                       IF(n.IDNature = 4, 'dfep', 'directeur') as role_code,
                       IF(n.IDNature = 4, 'DFEP ولائي', 'مدير مؤسسة') as role_ar,
                       '' as api_key,
                       '{}' as permissions,
                       'etablissement' as source_table,
                       e.avatar as avatar
                FROM etablissement e
                LEFT JOIN nature_etsf n ON e.IDNature_etsF = n.IDNature_etsF
                {$whereESQL}

                UNION ALL

                SELECT enc.IDEncadrement as id, enc.nin as username, CONCAT(enc.Nom, ' ', enc.Prenom) as nom_complet,
                       enc.Email as email, 0 as admin, 1 as est_actif,
                       COALESCE(NULLIF(enc.IDEts_Form, 0), enc.IDetablissement) as etablissement_id, COALESCE(et_form.Nom, e.Nom) as etab_nom,
                       COALESCE(et_form.IDDFEP, e.IDDFEP) as wilaya_id, '' as wilaya_nom,
                       '' as last_login,
                       '2026-01-01 00:00:00' as created_at,
                       'formateur' as role_code,
                       'مكوّن' as role_ar,
                       '' as api_key,
                       '{}' as permissions,
                       'encadrement' as source_table,
                       enc.photo as avatar
                FROM encadrement enc
                LEFT JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                LEFT JOIN etablissement et_form ON enc.IDEts_Form = et_form.IDetablissement
                {$whereEncSQL}

                LIMIT ? OFFSET ?
            ";

            $db = new \App\Core\LaravelDbAdapter();
            $stmt = $db->prepare($unionQuery);

            $paramIndex = 1;
            foreach ($paramsU as $p) { $stmt->bindValue($paramIndex++, $p); }
            foreach ($paramsE as $p) { $stmt->bindValue($paramIndex++, $p); }
            foreach ($paramsEnc as $p) { $stmt->bindValue($paramIndex++, $p); }
            $stmt->bindValue($paramIndex++, $perPage, \PDO::PARAM_INT);
            $stmt->bindValue($paramIndex++, $offset, \PDO::PARAM_INT);

            $stmt->execute();
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Decrypt usernames starting with @ or encrypted NINs
            foreach ($users as &$u) {
                if (!empty($u['username'])) {
                    $uname = $u['username'];
                    if (strpos($uname, '@') === 0) {
                        try {
                            $dec = decrypt(substr($uname, 1), false);
                            if ($dec) {
                                $u['username'] = $dec;
                            }
                        } catch (\Exception $ex) {}
                    } else {
                        try {
                            $dec = \Illuminate\Support\Facades\Crypt::decryptString($uname);
                            if ($dec) {
                                $u['username'] = $dec;
                            }
                        } catch (\Exception $ex) {}
                    }
                }
            }
            unset($u);

            // ── Calculate global stats for all tables ──
            $whereUActive = !empty($whereU) ? 'WHERE ' . implode(' AND ', array_merge($whereU, ['u.activee = 0'])) : 'WHERE u.activee = 0';
            $activeU = DB::selectOne("
                SELECT COUNT(*) as c 
                FROM utilisateur u
                LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                {$whereUActive}
            ", $paramsU)->c;

            $whereEActive = !empty($whereE) ? 'WHERE ' . implode(' AND ', array_merge($whereE, ['e.activee = 0'])) : 'WHERE e.activee = 0';
            $activeE = DB::selectOne("
                SELECT COUNT(*) as c 
                FROM etablissement e
                {$whereEActive}
            ", $paramsE)->c;

            $totalActive = $activeU + $activeE + $countEnc;
            $totalSuspended = $totalCount - $totalActive;

            $totalApiKeys = DB::table('api_clients')->where('is_active', 1)->count();

            // Wilayas and Etablissements scoped by role
            if ($isEtabRole && $etabId > 0) {
                // مدير المؤسسة: مؤسسته فقط
                $etabRow = DB::selectOne("
                    SELECT e.IDetablissement as id, e.Nom as nom_ar, e.NomFr as nom_fr, d.IDWilayaa as wilaya_id, IF(e.PublPrive = 1, 'PRIVE', 'PUBLIC') as type_code 
                    FROM etablissement e 
                    LEFT JOIN dfep d ON e.IDDFEP = d.IDDFEP 
                    WHERE e.IDetablissement = ? 
                    LIMIT 1
                ", [$etabId]);
                $etablissements = $etabRow ? [(array)$etabRow] : [];
                $wilayas = [];
            } elseif ($role_code === 'dfep' && $dfepId > 0) {
                $etablissements = DB::select("
                    SELECT e.IDetablissement as id, e.Nom as nom_ar, e.NomFr as nom_fr, d.IDWilayaa as wilaya_id, IF(e.PublPrive = 1, 'PRIVE', 'PUBLIC') as type_code 
                    FROM etablissement e 
                    LEFT JOIN dfep d ON e.IDDFEP = d.IDDFEP 
                    WHERE e.IDDFEP = ? 
                    ORDER BY e.Nom ASC
                ", [$dfepId]);
                $etablissements = array_map(fn($item) => (array)$item, $etablissements);

                $wilayas = DB::select("
                    SELECT w.IDWilayaa as id, w.Nom as nom_ar 
                    FROM wilaya w 
                    INNER JOIN dfep d ON d.IDWilayaa = w.IDWilayaa 
                    WHERE d.IDDFEP = ?
                ", [$dfepId]);
                $wilayas = array_map(fn($item) => (array)$item, $wilayas);
            } else {
                $wilayas = DB::select("SELECT IDWilayaa as id, Nom as nom_ar FROM wilaya ORDER BY Code ASC");
                $wilayas = array_map(fn($item) => (array)$item, $wilayas);

                $etablissements = DB::select("
                    SELECT e.IDetablissement as id, e.Nom as nom_ar, e.NomFr as nom_fr, d.IDWilayaa as wilaya_id, IF(e.PublPrive = 1, 'PRIVE', 'PUBLIC') as type_code 
                    FROM etablissement e 
                    LEFT JOIN dfep d ON e.IDDFEP = d.IDDFEP 
                    ORDER BY e.Nom ASC
                ");
                $etablissements = array_map(fn($item) => (array)$item, $etablissements);
            }

        } catch (Exception $e) {
            session(['flash_error' => 'خطأ في جلب البيانات: ' . $e->getMessage()]);
        }

        return view('admin.users.index', [
            'title'          => 'إدارة المستخدمين والصلاحيات / Users & Rights',
            'users'          => $users,
            'roles'          => [
                ['id' => 1, 'libelle_ar' => 'مدير النظام',  'libelle_fr' => 'Administrateur', 'code' => 'admin'],
                ['id' => 2, 'libelle_ar' => 'مدير مؤسسة',  'libelle_fr' => 'Directeur',      'code' => 'directeur'],
                ['id' => 3, 'libelle_ar' => 'مكوّن',       'libelle_fr' => 'Formateur',      'code' => 'formateur'],
                ['id' => 4, 'libelle_ar' => 'DFEP ولائي',  'libelle_fr' => 'DFEP',           'code' => 'dfep'],
                ['id' => 5, 'libelle_ar' => 'متربص / متكون', 'libelle_fr' => 'Stagiaire', 'code' => 'stagiaire'],
            ],
            'etablissements' => $etablissements,
            'wilayas'        => $wilayas,
            'sel_wilaya'     => $selWilaya ?? 0,
            'sel_etab'       => $selEtab ?? 0,
            'sel_status'     => $selStatus ?? '',
            'departments'    => [],
            'total_count'    => $totalCount,
            'page'           => $page,
            'total_pages'    => $totalPages,
            'per_page'       => $perPage,
            'search'         => $search,
            'active_count'   => $totalActive ?? 0,
            'suspended_count'=> $totalSuspended ?? 0,
            'api_keys_count' => $totalApiKeys ?? 0,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if ($role_code !== 'admin' && $role_code !== 'dfep') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $username    = trim($request->input('username', ''));
        $nom_complet = trim($request->input('nom_complet', ''));
        $etab_id     = !empty($request->input('etablissement_id')) ? (int)$request->input('etablissement_id') : null;

        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
        if ($role_code === 'dfep' && $dfepId > 0) {
            if ($etab_id !== null) {
                $checkE = DB::selectOne("SELECT COUNT(*) as c FROM etablissement WHERE IDetablissement = ? AND IDDFEP = ?", [$etab_id, $dfepId]);
                if ($checkE->c == 0) {
                    session(['flash_error' => 'غير مصرح لك بإسناد مستخدم لمؤسسة خارج ولايتك.']);
                    return redirect()->route('users.index');
                }
            } else {
                if ((int)$request->input('role_id', 4) === 1) {
                    session(['flash_error' => 'غير مصرح لك بإنشاء مدير نظام.']);
                    return redirect()->route('users.index');
                }
            }
        }

        $password = trim($request->input('password', ''));
        $auto_generated = false;
        if (empty($password)) {
            $password = self::generateStrongPassword();
            $auto_generated = true;
        }

        if (empty($username) || empty($nom_complet)) {
            session(['flash_error' => 'يرجى ملء جميع الحقول المطلوبة.']);
            return redirect()->route('users.index');
        }

        try {
            // Check uniqueness of username in utilisateur table (considering encrypted usernames)
            $isDuplicate = false;
            $allUsers = DB::select("SELECT NomUser FROM utilisateur");
            foreach ($allUsers as $au) {
                $checkName = $au->NomUser;
                if (strpos($checkName, '@') === 0) {
                    try {
                        $dec = decrypt(substr($checkName, 1), false);
                        if ($dec === $username) {
                            $isDuplicate = true;
                            break;
                        }
                    } catch (\Exception $ex) {}
                } else {
                    if ($checkName === $username) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }

            if ($isDuplicate) {
                session(['flash_error' => 'اسم المستخدم موجود مسبقاً.']);
                return redirect()->route('users.index');
            }

            // Encrypt the username for database storage (prefix with @)
            $dbUsername = '@' . \Illuminate\Support\Facades\Crypt::encryptString($username);

            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $role_id      = (int)$request->input('role_id', 4);
            $isAdmin      = ($role_id === 1) ? 1 : 0;
            $idNature     = $role_id;
            $wilaya_id    = !empty($request->input('wilaya_id')) ? (int)$request->input('wilaya_id') : null;

            $maxUserId = (int)DB::selectOne("SELECT MAX(IDUtilisateur) as m FROM utilisateur")->m;
            $newUserId = max(100, $maxUserId + 1);
            $codeVal   = ($role_id === 4 && $wilaya_id > 0) ? $wilaya_id : $newUserId;

            // Sync with bureau
            if ($etab_id !== null && $etab_id > 0) {
                $chkBureau = DB::selectOne("SELECT COUNT(*) as c FROM bureau WHERE IDBureau = ?", [$etab_id]);
                if ($chkBureau->c == 0) {
                    $etabData = DB::selectOne("SELECT Nom, NomFr FROM etablissement WHERE IDetablissement = ? LIMIT 1", [$etab_id]);
                    $etabNom = $etabData->Nom ?? ('Etablissement ' . $etab_id);
                    $etabNomFr = $etabData->NomFr ?? ('Etablissement ' . $etab_id);
                    
                    DB::insert("INSERT INTO bureau (IDBureau, Nom, NomFr, IDService) VALUES (?, ?, ?, NULL)", [$etab_id, $etabNom, $etabNomFr]);
                }
            }

            DB::insert("
                INSERT INTO utilisateur
                    (IDUtilisateur, Code, NomUser, MotPass, Nom, admin, activee,
                     IDBureau, IDNature, IDMode_formation, IDdirection, IDMode_gestion, IDNature1)
                VALUES (?, ?, ?, ?, ?, ?, 0,
                        ?, ?, NULL, NULL, NULL, NULL)
            ", [$newUserId, $codeVal, $dbUsername, $passwordHash, $nom_complet, $isAdmin, $etab_id, $idNature]);

            if ($auto_generated) {
                session(['flash_success' => 'تم إضافة المستخدم! كلمة المرور التلقائية: <code style="background:#e8f5e9;padding:2px 8px;border-radius:4px;font-weight:700;">' . htmlspecialchars($password) . '</code>']);
            } else {
                session(['flash_success' => 'تم إضافة المستخدم بنجاح!']);
            }
        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حفظ المستخدم: ' . $e->getMessage()]);
        }

        return redirect()->route('users.index');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if ($role_code !== 'admin' && $role_code !== 'dfep') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $id          = (int)$request->input('id', 0);
        $username    = trim($request->input('username', ''));
        $nom_complet = trim($request->input('nom_complet', ''));
        $password    = trim($request->input('password', ''));
        $est_actif   = $request->has('est_actif') ? 1 : 0;
        $etab_id     = !empty($request->input('etablissement_id')) ? (int)$request->input('etablissement_id') : null;
        $source_table = trim($request->input('source_table', 'utilisateur'));

        if (empty($id) || empty($username) || empty($nom_complet)) {
            session(['flash_error' => 'يرجى ملء جميع الحقول المطلوبة.']);
            return redirect()->route('users.index');
        }

        try {
            $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            if ($role_code === 'dfep' && $dfepId > 0) {
                if ($source_table === 'utilisateur') {
                    $usr = DB::selectOne("
                        SELECT u.IDUtilisateur, u.IDBureau, u.Code, u.IDNature, e.IDDFEP
                        FROM utilisateur u
                        LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                        WHERE u.IDUtilisateur = ?
                    ", [$id]);
                    
                    if (!$usr) {
                        session(['flash_error' => 'المستخدم غير موجود.']);
                        return redirect()->route('users.index');
                    }
                    
                    $userBelongs = false;
                    if ((int)$usr->IDNature === 4 && ((int)($usr->Code ?? 0) === $dfepId || (int)$usr->IDBureau === $dfepId)) {
                        $userBelongs = true;
                    } elseif ($usr->IDDFEP !== null && (int)$usr->IDDFEP === $dfepId) {
                        $userBelongs = true;
                    }
                    
                    if (!$userBelongs) {
                        session(['flash_error' => 'غير مصرح لك بتعديل هذا المستخدم خارج ولايتك.']);
                        return redirect()->route('users.index');
                    }

                    if ($etab_id !== null) {
                        $checkE = DB::selectOne("SELECT COUNT(*) as c FROM etablissement WHERE IDetablissement = ? AND IDDFEP = ?", [$etab_id, $dfepId]);
                        if ($checkE->c == 0) {
                            session(['flash_error' => 'غير مصرح لك بإسناد مستخدم لمؤسسة خارج ولايتك.']);
                            return redirect()->route('users.index');
                        }
                    }

                    if ((int)$request->input('role_id', 4) === 1) {
                        session(['flash_error' => 'غير مصرح لك بترقية مستخدم إلى مدير نظام.']);
                        return redirect()->route('users.index');
                    }
                } elseif ($source_table === 'etablissement') {
                    $targetEtab = DB::selectOne("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ?", [$id]);
                    if (!$targetEtab || (int)$targetEtab->IDDFEP !== $dfepId) {
                        session(['flash_error' => 'غير مصرح لك بتعديل مستخدم خارج ولايتك.']);
                        return redirect()->route('users.index');
                    }
                } elseif ($source_table === 'encadrement') {
                    $targetEnc = DB::selectOne("
                        SELECT e.IDDFEP 
                        FROM encadrement enc
                        LEFT JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                        WHERE enc.IDEncadrement = ?
                    ", [$id]);
                    if (!$targetEnc || (int)$targetEnc->IDDFEP !== $dfepId) {
                        session(['flash_error' => 'غير مصرح لك بتعديل موظف خارج ولايتك.']);
                        return redirect()->route('users.index');
                    }
                }
            }

            if ($source_table === 'utilisateur') {
                // Prevent duplicate username (considering encrypted usernames) excluding current user
                $isDuplicate = false;
                $allUsers = DB::select("SELECT IDUtilisateur, NomUser FROM utilisateur WHERE IDUtilisateur <> ?", [$id]);
                foreach ($allUsers as $au) {
                    $checkName = $au->NomUser;
                    if (strpos($checkName, '@') === 0) {
                        try {
                            $dec = decrypt(substr($checkName, 1), false);
                            if ($dec === $username) {
                                $isDuplicate = true;
                                break;
                            }
                        } catch (\Exception $ex) {}
                    } else {
                        if ($checkName === $username) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                }

                if ($isDuplicate) {
                    session(['flash_error' => 'اسم المستخدم مستخدم من قبل مستخدم آخر.']);
                    return redirect()->route('users.index');
                }

                // Encrypt the username for database storage (prefix with @)
                $dbUsername = '@' . \Illuminate\Support\Facades\Crypt::encryptString($username);

                $role_id  = (int)$request->input('role_id', 4);
                $isAdmin  = ($role_id === 1) ? 1 : 0;
                $idNature = $role_id;
                $activeeVal = ($est_actif === 1) ? 0 : 1;
                $wilaya_id = !empty($request->input('wilaya_id')) ? (int)$request->input('wilaya_id') : null;
                $codeVal   = ($role_id === 4 && $wilaya_id > 0) ? $wilaya_id : $id;

                if ($etab_id !== null && $etab_id > 0) {
                    $chkBureau = DB::selectOne("SELECT COUNT(*) as c FROM bureau WHERE IDBureau = ?", [$etab_id]);
                    if ($chkBureau->c == 0) {
                        $etabData = DB::selectOne("SELECT Nom, NomFr FROM etablissement WHERE IDetablissement = ? LIMIT 1", [$etab_id]);
                        $etabNom = $etabData->Nom ?? ('Etablissement ' . $etab_id);
                        $etabNomFr = $etabData->NomFr ?? ('Etablissement ' . $etab_id);
                        
                        DB::insert("INSERT INTO bureau (IDBureau, Nom, NomFr, IDService) VALUES (?, ?, ?, NULL)", [$etab_id, $etabNom, $etabNomFr]);
                    }
                }

                if (!empty($password)) {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    DB::update("
                        UPDATE utilisateur
                        SET NomUser = ?, MotPass = ?, Nom = ?, admin = ?, activee = ?,
                            IDBureau = ?, IDNature = ?, Code = ?,
                            IDMode_formation = NULL, IDdirection = NULL, IDMode_gestion = NULL, IDNature1 = NULL
                        WHERE IDUtilisateur = ?
                    ", [$dbUsername, $passwordHash, $nom_complet, $isAdmin, $activeeVal, $etab_id, $idNature, $codeVal, $id]);
                } else {
                    DB::update("
                        UPDATE utilisateur
                        SET NomUser = ?, Nom = ?, admin = ?, activee = ?,
                            IDBureau = ?, IDNature = ?, Code = ?,
                            IDMode_formation = NULL, IDdirection = NULL, IDMode_gestion = NULL, IDNature1 = NULL
                        WHERE IDUtilisateur = ?
                    ", [$dbUsername, $nom_complet, $isAdmin, $activeeVal, $etab_id, $idNature, $codeVal, $id]);
                }
            } elseif ($source_table === 'etablissement') {
                $stmtCheck = DB::selectOne("SELECT COUNT(*) as c FROM etablissement WHERE nomUser = ? AND IDetablissement <> ?", [$username, $id]);
                if ($stmtCheck->c > 0) {
                    session(['flash_error' => 'اسم المستخدم مستخدم من قبل مؤسسة أخرى.']);
                    return redirect()->route('users.index');
                }

                $activeeVal = ($est_actif === 1) ? 0 : 1;

                if (!empty($password)) {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    DB::update("
                        UPDATE etablissement
                        SET nomUser = ?, MotDePass = ?, Nom = ?, Email = ?, activee = ?
                        WHERE IDetablissement = ?
                    ", [$username, $passwordHash, $nom_complet, $request->input('email'), $activeeVal, $id]);
                } else {
                    DB::update("
                        UPDATE etablissement
                        SET nomUser = ?, Nom = ?, Email = ?, activee = ?
                        WHERE IDetablissement = ?
                    ", [$username, $nom_complet, $request->input('email'), $activeeVal, $id]);
                }
            } elseif ($source_table === 'encadrement') {
                $stmtCheck = DB::selectOne("SELECT COUNT(*) as c FROM encadrement WHERE nin = ? AND IDEncadrement <> ?", [$username, $id]);
                if ($stmtCheck->c > 0) {
                    session(['flash_error' => 'رقم التعريف الوطني (اسم المستخدم) مستخدم من قبل موظف آخر.']);
                    return redirect()->route('users.index');
                }

                $parts = explode(' ', $nom_complet, 2);
                $nom = $parts[0] ?? '';
                $prenom = $parts[1] ?? '';

                if (!empty($password)) {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    if ($est_actif === 0) {
                        DB::update("
                            UPDATE encadrement
                            SET nin = ?, MotDePass = NULL, Nom = ?, Prenom = ?, Email = ?
                            WHERE IDEncadrement = ?
                        ", [$username, $nom, $prenom, $request->input('email'), $id]);
                    } else {
                        DB::update("
                            UPDATE encadrement
                            SET nin = ?, MotDePass = ?, Nom = ?, Prenom = ?, Email = ?
                            WHERE IDEncadrement = ?
                        ", [$username, $passwordHash, $nom, $prenom, $request->input('email'), $id]);
                    }
                } else {
                    if ($est_actif === 0) {
                        DB::update("
                            UPDATE encadrement
                            SET nin = ?, MotDePass = NULL, Nom = ?, Prenom = ?, Email = ?
                            WHERE IDEncadrement = ?
                        ", [$username, $nom, $prenom, $request->input('email'), $id]);
                    } else {
                        DB::update("
                            UPDATE encadrement
                            SET nin = ?, Nom = ?, Prenom = ?, Email = ?
                            WHERE IDEncadrement = ?
                        ", [$username, $nom, $prenom, $request->input('email'), $id]);
                    }
                }
            }

            session(['flash_success' => 'تم تحديث بيانات المستخدم وصلاحياته بنجاح!']);
        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء تحديث بيانات المستخدم: ' . $e->getMessage()]);
        }

        return redirect()->route('users.index');
    }

    /**
     * Delete the specified user.
     */
    public function destroy(Request $request, $id)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if ($role_code !== 'admin' && $role_code !== 'dfep') {
            session(['flash_error' => 'Unauthorized']);
            return redirect()->route('users.index');
        }

        $source_table = trim($request->input('source_table', 'utilisateur'));

        try {
            $id = (int)$id;

            if ($source_table === 'utilisateur' && $id === (int)$user['id']) {
                session(['flash_error' => 'لا يمكنك حذف حسابك الشخصي الذي تستخدمه حالياً.']);
                return redirect()->route('users.index');
            }

            $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            if ($role_code === 'dfep' && $dfepId > 0) {
                if ($source_table === 'utilisateur') {
                    $usr = DB::selectOne("
                        SELECT u.IDUtilisateur, u.IDBureau, u.Code, u.IDNature, e.IDDFEP
                        FROM utilisateur u
                        LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                        WHERE u.IDUtilisateur = ?
                    ", [$id]);
                    
                    if (!$usr) {
                        session(['flash_error' => 'المستخدم غير موجود.']);
                        return redirect()->route('users.index');
                    }
                    
                    $userBelongs = false;
                    if ((int)$usr->IDNature === 4 && ((int)($usr->Code ?? 0) === $dfepId || (int)$usr->IDBureau === $dfepId)) {
                        $userBelongs = true;
                    } elseif ($usr->IDDFEP !== null && (int)$usr->IDDFEP === $dfepId) {
                        $userBelongs = true;
                    }
                    
                    if (!$userBelongs) {
                        session(['flash_error' => 'غير مصرح لك بحذف هذا المستخدم خارج ولايتك.']);
                        return redirect()->route('users.index');
                    }
                } elseif ($source_table === 'etablissement') {
                    $targetEtab = DB::selectOne("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ?", [$id]);
                    if (!$targetEtab || (int)$targetEtab->IDDFEP !== $dfepId) {
                        session(['flash_error' => 'غير مصرح لك بحذف مستخدم خارج ولايتك.']);
                        return redirect()->route('users.index');
                    }
                } elseif ($source_table === 'encadrement') {
                    $targetEnc = DB::selectOne("
                        SELECT e.IDDFEP 
                        FROM encadrement enc
                        LEFT JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                        WHERE enc.IDEncadrement = ?
                    ", [$id]);
                    if (!$targetEnc || (int)$targetEnc->IDDFEP !== $dfepId) {
                        session(['flash_error' => 'غير مصرح لك بحذف موظف خارج ولايتك.']);
                        return redirect()->route('users.index');
                    }
                }
            }

            if ($source_table === 'utilisateur') {
                DB::delete("DELETE FROM utilisateur WHERE IDUtilisateur = ?", [$id]);
                session(['flash_success' => 'تم حذف المستخدم من النظام نهائياً بنجاح.']);
            } elseif ($source_table === 'etablissement') {
                DB::update("
                    UPDATE etablissement
                    SET nomUser = NULL, MotDePass = NULL
                    WHERE IDetablissement = ?
                ", [$id]);
                session(['flash_success' => 'تم سحب صلاحيات الدخول للمؤسسة بنجاح.']);
            } elseif ($source_table === 'encadrement') {
                DB::update("
                    UPDATE encadrement
                    SET nin = NULL, MotDePass = NULL
                    WHERE IDEncadrement = ?
                ", [$id]);
                session(['flash_success' => 'تم سحب صلاحيات الدخول للموظف/المكون بنجاح.']);
            }
        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف المستخدم: ' . $e->getMessage()]);
        }

        return redirect()->route('users.index');
    }

    /**
     * Generate new API key.
     */
    public function generateApiKey(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if ($role_code !== 'admin' && $role_code !== 'dfep') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $plainNewKey = 'sgfep_live_' . bin2hex(random_bytes(24));
            $user['api_key'] = $plainNewKey;
            session(['user' => $user]);
            return response()->json(['success' => true, 'api_key' => $plainNewKey]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate password reset token.
     */
    public function generatePasswordResetToken(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if ($role_code !== 'admin' && $role_code !== 'dfep') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $user_id = (int)$request->input('user_id', 0);
            $token = bin2hex(random_bytes(32));

            // Delete any existing tokens for this user (one active token at a time)
            \DB::table('password_reset_tokens')->where('user_id', $user_id)->delete();

            // Store new token in the database (secure, auditable, no file race conditions)
            \DB::table('password_reset_tokens')->insert([
                'token'      => hash('sha256', $token), // store hashed token
                'user_id'    => $user_id,
                'expires_at' => now()->addHour(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success'    => true,
                'reset_link' => '/reset-password?token=' . $token
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display printable listing of users.
     */
    public function printUsers()
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة المستخدمين معطلة من قبل مدير النظام / Printing is disabled by administrator.');
        }

        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if ($role_code !== 'admin' && $role_code !== 'dfep') {
            return redirect()->route('dashboard');
        }

        $users = [];
        try {
            $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            
            $whereClauses = [];
            $params = [];
            
            if ($role_code === 'dfep' && $dfepId > 0) {
                $whereClauses[] = "(u.IDBureau = :dfep_id AND u.IDNature = 4) OR (e.IDDFEP = :dfep_id_2)";
                $params['dfep_id'] = $dfepId;
                $params['dfep_id_2'] = $dfepId;
            }
            
            $whereSql = '';
            if (!empty($whereClauses)) {
                $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
            }

            $users = DB::select("
                SELECT u.IDUtilisateur as id, u.NomUser as username, u.Nom as nom_complet, '' as email,
                       u.admin, IF(u.activee = 0, 1, 0) as active, e.Nom as etab_nom
                FROM utilisateur u
                LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                {$whereSql}
                ORDER BY u.IDUtilisateur DESC
            ", $params);
            
            $users = array_map(fn($item) => (array)$item, $users);
            foreach ($users as &$u) {
                if (!empty($u['username'])) {
                    $uname = $u['username'];
                    if (strpos($uname, '@') === 0) {
                        try {
                            $dec = decrypt(substr($uname, 1), false);
                            if ($dec) {
                                $u['username'] = $dec;
                            }
                        } catch (\Exception $ex) {}
                    } else {
                        try {
                            $dec = \Illuminate\Support\Facades\Crypt::decryptString($uname);
                            if ($dec) {
                                $u['username'] = $dec;
                            }
                        } catch (\Exception $ex) {}
                    }
                }
            }
            unset($u);
        } catch (Exception $e) {
            session(['flash_error' => 'خطأ في جلب البيانات: ' . $e->getMessage()]);
        }

        return view('admin.users.print', [
            'title' => 'طباعة قائمة مستخدمي المنصة - SGFEP',
            'users' => $users
        ]);
    }

    /**
     * Generate strong random password.
     */
    private static function generateStrongPassword(): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $special = '@#$!';

        $pass  = $upper[random_int(0, strlen($upper) - 1)];
        $pass .= $lower[random_int(0, strlen($lower) - 1)];
        $pass .= $digits[random_int(0, strlen($digits) - 1)];
        $pass .= $special[random_int(0, strlen($special) - 1)];

        $all = $upper . $lower . $digits . $special;
        for ($i = 0; $i < 6; $i++) {
            $pass .= $all[random_int(0, strlen($all) - 1)];
        }
        return str_shuffle($pass);
    }

    /**
     * Export credentials for establishments and users.
     */
    public function exportCredentials(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if ($role_code !== 'admin') {
            session(['flash_error' => 'غير مصرح لك بالولوج إلى هذه الصفحة.']);
            return redirect()->route('dashboard');
        }

        // Fetch all active wilayas
        $wilayas = DB::select("SELECT IDWilayaa, Nom FROM wilaya ORDER BY Nom");

        // Selected Wilaya filter
        $selectedWilayaId = (int)$request->input('wilaya_id', 0);
        $searchQuery = trim($request->input('search', ''));

        // Query active establishments (closed/inactive are filtered out by our connection interceptor)
        $queryStr = "
            SELECT e.IDetablissement, e.Nom as etab_nom, e.nomUser, e.MotDePass, e.Code as etab_code,
                   w.Nom as wilaya_nom, nets.Nom as nature_nom, nets.IDNature as nature_id
            FROM etablissement e
            INNER JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
            LEFT JOIN nature_etsf nets ON e.IDNature_etsF = nets.IDNature_etsF
            WHERE 1=1
        ";

        $params = [];
        if ($selectedWilayaId > 0) {
            $queryStr .= " AND e.IDDFEP = :wilaya_id";
            $params['wilaya_id'] = $selectedWilayaId;
        }
        if (!empty($searchQuery)) {
            $queryStr .= " AND (e.Nom LIKE :search1 OR e.nomUser LIKE :search2 OR e.Code LIKE :search3)";
            $params['search1'] = "%{$searchQuery}%";
            $params['search2'] = "%{$searchQuery}%";
            $params['search3'] = "%{$searchQuery}%";
        }

        $queryStr .= " ORDER BY w.Nom, e.Nom";

        $etabs = DB::select($queryStr, $params);

        // Fetch all user secret codes grouped by IDNature
        $userRows = DB::select("SELECT NomUser, Nom, MotPass, IDNature FROM utilisateur WHERE IDNature IS NOT NULL");
        $natureUsers = [];
        foreach ($userRows as $u) {
            $passShow = str_starts_with($u->MotPass, '$2y$') ? '[مُشفر / Bcrypt Hash]' : $u->MotPass;
            $natureUsers[$u->IDNature][] = [
                'username' => $u->NomUser,
                'name' => $u->Nom,
                'secret_code' => $passShow
            ];
        }

        // If CSV export is requested:
        if ($request->has('export') && $request->input('export') === 'csv') {
            $filename = "credentials_wilaya_" . ($selectedWilayaId ?: 'all') . "_" . date('Ymd_His') . ".csv";
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            
            $output = fopen('php://output', 'w');
            
            fputcsv($output, [
                'الولاية',
                'رمز المؤسسة',
                'اسم المؤسسة',
                'اسم المستخدم',
                'كلمة المرور',
                'الحسابات الفرعية المتاحة ورموزها السرية'
            ]);
            
            foreach ($etabs as $e) {
                $subAccounts = [];
                $assocUsers = $natureUsers[$e->nature_id] ?? [];
                foreach ($assocUsers as $au) {
                    $subAccounts[] = $au['name'] . " (" . $au['username'] . "): " . $au['secret_code'];
                }
                
                fputcsv($output, [
                    $e->wilaya_nom,
                    $e->etab_code,
                    $e->etab_nom,
                    $e->nomUser,
                    $e->MotDePass,
                    implode(" | ", $subAccounts)
                ]);
            }
            fclose($output);
            exit;
        }

        return view('admin.users.credentials', [
            'etabs' => $etabs,
            'wilayas' => $wilayas,
            'selectedWilayaId' => $selectedWilayaId,
            'searchQuery' => $searchQuery,
            'natureUsers' => $natureUsers
        ]);
    }
}
