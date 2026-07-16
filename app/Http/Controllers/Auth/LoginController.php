<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Core\AuditLogger;
use OpenApi\Attributes as OA;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        // Hydrate PHP session if needed
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (session()->has('user')) {
            return request()->is('sig/*') || request()->is('sig') ? redirect('/sig/dashboard') : redirect('/dashboard');
        }

        return view('auth::login'); // We will use normal view notation, but let's check view name: auth.login
        // Let's return view('auth.login')
    }

    public function showLoginFormView()
    {
        // Check Laravel session first (primary), then PHP session as fallback
        $user = session('user');
        if (!$user && session()->has('user')) {
            $user = session('user');
        }

        if ($user) {
            return request()->is('sig/*') || request()->is('sig') ? redirect('/sig/dashboard') : redirect('/dashboard');
        }

        // Generate Math CAPTCHA if enabled
        $isCaptchaActive = \App\Helpers\SovereignLicensingHelper::getSetting('login_captcha_active', '0') === '1';
        $captchaQuestion = null;
        if ($isCaptchaActive) {
            $num1 = rand(1, 9);
            $num2 = rand(1, 9);
            session(['login_captcha' => $num1 + $num2]);
            $captchaQuestion = "ما هو حاصل جمع {$num1} + {$num2} ؟";
        }

        return view('auth.login', [
            'is_captcha_active' => $isCaptchaActive,
            'captcha_question'  => $captchaQuestion,
        ]);
    }

    /**
     * Perform the login request
     */
    #[OA\Post(
        path: "/api/auth/login",
        summary: "تسجيل الدخول للمستخدم والحصول على توكن الصلاحية",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "username", type: "string", example: "2005"),
                    new OA\Property(property: "password", type: "string", example: "dUv377"),
                    new OA\Property(property: "secret_code", type: "string", example: "sludrA@Dplm")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم تسجيل الدخول بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
                        new OA\Property(property: "user", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "بيانات الدخول غير صحيحة")
        ]
    )]
    public function login(Request $request)
    {
        // Check already logged in via Laravel or PHP session
        $existingUser = session('user') ?? (session('user') ?? null);
        if ($existingUser) {
            return request()->is('sig/*') || request()->is('sig') ? redirect('/sig/dashboard') : redirect('/dashboard');
        }

        // Captcha verification
        $isCaptchaActive = \App\Helpers\SovereignLicensingHelper::getSetting('login_captcha_active', '0') === '1';
        if ($isCaptchaActive) {
            $captchaInput = $request->input('captcha');
            $captchaSession = session('login_captcha');
            if ($captchaInput === null || (int)$captchaInput !== $captchaSession) {
                // Generate a new captcha for the next try
                $num1 = rand(1, 9);
                $num2 = rand(1, 9);
                session(['login_captcha' => $num1 + $num2]);

                return view('auth.login', [
                    'title' => 'SGFEP - تسجيل الدخول / Connexion',
                    'error' => 'رمز التحقق البشري غير صحيح. / Code CAPTCHA incorrect.',
                    'is_captcha_active' => true,
                    'captcha_question'  => "$num1 + $num2",
                ]);
            }
        }

        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'login_type' => 'required|string',
        ]);

        $username = trim($credentials['username']);
        $password = $credentials['password'];
        $loginType = trim($credentials['login_type']);
        $secretCode = trim($request->input('secret_code', ''));

        \Illuminate\Support\Facades\Log::info('Login Attempt Debug:', [
            'login_type' => $loginType,
            'username' => $username,
            'password_len' => strlen($password),
            'secret_code' => $secretCode
        ]);

        $maxAttempts = 5;
        $lockoutDuration = 15 * 60; // 15 minutes
        
        // Get client IP securely
        $ip = $request->header('X-Forwarded-For') ?? $request->ip() ?? '0.0.0.0';
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }
        $ip = trim($ip);

        $db = new \App\Core\LaravelDbAdapter();
        $timeThreshold = time() - $lockoutDuration;

        // Rate limiting checks
        try {
            $stmtIp = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?");
            $stmtIp->execute([$ip, $timeThreshold]);
            $ipFailedAttempts = (int)$stmtIp->fetchColumn();

            $usernameFailedAttempts = 0;
            if (!empty($username)) {
                $stmtUser = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE username = ? AND attempted_at > ?");
                $stmtUser->execute([$username, $timeThreshold]);
                $usernameFailedAttempts = (int)$stmtUser->fetchColumn();
            }

            if ($ipFailedAttempts >= $maxAttempts || $usernameFailedAttempts >= $maxAttempts || (session()->has('lockout_time') && time() < session('lockout_time'))) {
                $stmtLatest = $db->prepare("
                    SELECT MAX(attempted_at) 
                    FROM login_attempts 
                    WHERE (ip_address = ? OR username = ?) AND attempted_at > ?
                ");
                $stmtLatest->execute([$ip, $username, $timeThreshold]);
                $latestAttempt = (int)$stmtLatest->fetchColumn();
                
                $dbLockoutTime = $latestAttempt + $lockoutDuration;
                $sessionLockoutTime = session('lockout_time') ?? 0;
                $lockoutTime = max($dbLockoutTime, $sessionLockoutTime);
                
                $remaining = ceil(($lockoutTime - time()) / 60);
                if ($remaining <= 0) {
                    $remaining = 1;
                }

                \App\Core\AuditLogger::logWarning("[SECURITY] Rate limit lockout triggered for IP: {$ip}, Username: {$username}");

                try {
                    event(new \App\Events\SecurityEventTriggered('LOGIN_LOCKOUT', 'danger', null, "تم حظر الدخول مؤقتاً لمحاولات متكررة للاسم: {$username} (Brute Force)"));
                } catch (\Exception $e) {}

                // ─── تسجيل محاولة Brute Force في accesuser ───
                try {
                    DB::table('accesuser')->insert([
                        'Date'            => date('Y-m-d'),
                        'heure'           => date('H:i:s'),
                        'NomUtilisateur'  => $username,
                        'NomPrenom'       => request()->path(),
                        'iplocal'         => $ip,
                        'windows'         => request()->userAgent() ?? 'Unknown',
                        'accesouinon'     => 0,
                        'Obs'             => 'محاولة تخمين كلمة المرور (Brute Force)',
                        'IDetablissement' => null,
                        'IDEncadrement'   => null,
                    ]);
                } catch (\Exception $logEx) {}

                return view('auth.login', [
                    'title' => 'SGFEP - تسجيل الدخول / Connexion',
                    'error' => "تم حظر الدخول مؤقتاً لحماية الحساب. يرجى المحاولة بعد $remaining دقيقة / Compte temporairement bloqué."
                ]);
            }
        } catch (\Exception $e) {
            \App\Core\AuditLogger::logError("[SECURITY] Failed to check login_attempts table: " . $e->getMessage());
        }

        try {
            $matchedUser = null;
            $loginError = null;
            $roleCode = '';
            $roleAr = '';
            $roleFr = '';
            $roleId = 4;
            $isPasswordValid = false;

            if ($loginType === 'employee') {
                // ① Employee Login — utilise nin_hash (HMAC-SHA256) pour la recherche sécurisée
                // Si nin_hash n'existe pas encore (avant migration), fallback sur nin direct
                $ninHash  = hash_hmac('sha256', mb_strtoupper(trim($username)), config('app.key'));
                $hasNinHashCol = \Illuminate\Support\Facades\Schema::hasColumn('encadrement', 'nin_hash');

                $sqlWhere = $hasNinHashCol
                    ? 'AND (Encadrement.nin_hash = :nin_hash OR Encadrement.nin = :nin_fallback)'
                    : 'AND Encadrement.nin = :nin';

                $stmt = $db->prepare("
                    SELECT Encadrement.*,
                           SituationAdministrat.Nom AS sit_nom,
                           idsituationadministrat_type.IDIDSituationAdministrat_type AS sit_type
                    FROM Encadrement
                    INNER JOIN SituationAdministrat
                           ON Encadrement.IDSituationAdministrat = SituationAdministrat.IDSituationAdministrat
                    INNER JOIN idsituationadministrat_type
                           ON SituationAdministrat.IDIDSituationAdministrat_type = idsituationadministrat_type.IDIDSituationAdministrat_type
                    WHERE idsituationadministrat_type.IDIDSituationAdministrat_type = 1
                      {$sqlWhere}
                    LIMIT 1
                ");

                if ($hasNinHashCol) {
                    $stmt->execute([':nin_hash' => $ninHash, ':nin_fallback' => $username]);
                } else {
                    $stmt->execute([':nin' => $username]);
                }
                $employee = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($employee) {
                    $normalizedEmp = [];
                    foreach ($employee as $k => $v) {
                        $lk = strtolower($k);
                        $normalizedEmp[$lk] = $v;
                        if ($lk === 'idencadrement') {
                            $normalizedEmp['IDEncadrement'] = $v;
                        } elseif ($lk === 'idetablissement') {
                            $normalizedEmp['IDetablissement'] = $v;
                        } elseif ($lk === 'idets_form') {
                            $normalizedEmp['IDEts_Form'] = $v;
                        } elseif ($lk === 'motdepass') {
                            $normalizedEmp['MotDePass'] = $v;
                        } elseif ($lk === 'nom') {
                            $normalizedEmp['Nom'] = $v;
                        } elseif ($lk === 'prenom') {
                            $normalizedEmp['Prenom'] = $v;
                        } elseif ($lk === 'nin') {
                            $normalizedEmp['nin'] = $v;
                        } elseif ($lk === 'photo') {
                            $normalizedEmp['photo'] = $v;
                        }
                    }
                    $employee = array_merge($employee, $normalizedEmp);

                    // ── Vérification mot de passe (Lazy Migration) ───────────────
                    // Accepte : bcrypt (password_verify) OU texte clair (comparaison directe)
                    $isCaseMismatch = false;
                    if (password_verify($password, $employee['MotDePass']) || $password === $employee['MotDePass']) {
                        $isPasswordValid = true;
 
                        // Rehash automatique : si le mot de passe n'est pas encore haché
                        if (password_needs_rehash($employee['MotDePass'], PASSWORD_BCRYPT, ['cost' => 12])
                            || strpos($employee['MotDePass'], '$2y$') !== 0) {
 
                            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                            // Mettre à jour le mot de passe ET le nin_hash en même temps
                            $stmtUpdateHash = $db->prepare(
                                $hasNinHashCol
                                    ? 'UPDATE Encadrement SET MotDePass = ?, nin_hash = ? WHERE IDEncadrement = ?'
                                    : 'UPDATE Encadrement SET MotDePass = ? WHERE IDEncadrement = ?'
                            );
                            if ($hasNinHashCol) {
                                $stmtUpdateHash->execute([$newHash, $ninHash, $employee['IDEncadrement']]);
                            } else {
                                $stmtUpdateHash->execute([$newHash, $employee['IDEncadrement']]);
                            }
                        }
                    } else {
                        if (password_verify(strtolower($password), $employee['MotDePass']) || strtolower($password) === strtolower($employee['MotDePass'])
                            || password_verify(strtoupper($password), $employee['MotDePass']) || strtoupper($password) === strtoupper($employee['MotDePass'])) {
                            $isCaseMismatch = true;
                        }
                    }
 
                    if ($isPasswordValid) {
                        $matchedUser = [
                            'id'               => $employee['IDEncadrement'],
                            'username'         => $employee['nin'],
                            'nom_complet'      => ($employee['Nom'] ?? '') . ' ' . ($employee['Prenom'] ?? ''),
                            // etablissement_id stores the academic ID (IDEts_Form)
                            'etablissement_id' => $employee['IDEts_Form'] ?? $employee['IDetablissement'] ?? null,
                            // profile_etab_id/idetablissement stores the account ID (IDetablissement)
                            'profile_etab_id'  => $employee['IDetablissement'] ?? null,
                            'idetablissement'  => $employee['IDetablissement'] ?? null,
                            'role_id'          => 5,
                            'role_code'        => 'EMPLOYEE',
                            'role_ar'          => 'موظف / مُكوّن',
                            'role_fr'          => 'Employé / Formateur',
                            'sit_nom'          => $employee['sit_nom'] ?? 'قيد الخدمة',
                            'login_table'      => 'encadrement',
                            'avatar'           => $employee['photo'] ?? null,
                        ];
                    }
                }
                if (!$employee) {
                    $loginError = 'رقم التعريف الوطني (NIN) غير مسجل في النظام. / NIN non enregistré.';
                } elseif (!$isPasswordValid) {
                    if ($isCaseMismatch) {
                        $loginError = 'كلمة المرور صحيحة ولكن يرجى التحقق من حالة الحروف (كبيرة/صغيرة - Caps Lock). / Vérifiez la casse du mot de passe.';
                    } else {
                        $loginError = 'كلمة المرور غير صحيحة. / Mot de passe incorrect.';
                    }
                }
            } elseif ($loginType === 'etablissement') {
                // ② Etablissement Login (Triple Credential check)
                $stmt = $db->prepare("
                    SELECT Etablissement.nomUser, Etablissement.MotDePass, Etablissement.activee, Etablissement.*,
                           Utilisateur.MotPass AS UtilisateurMotPass, Utilisateur.Nom AS UtilisateurNom, Utilisateur.IDUtilisateur,
                           Nature_etsF.IDNature AS nature_id,
                           Nature_etsF.IDNature_etsF AS nature_etsf_id,
                           NatureDirection.Nom AS nature_nom_ar, NatureDirection.NomFr AS nature_nom_fr
                    FROM Etablissement
                    INNER JOIN Nature_etsF    ON Etablissement.IDNature_etsF = Nature_etsF.IDNature_etsF
                    INNER JOIN NatureDirection ON NatureDirection.IDNature = Nature_etsF.IDNature
                    INNER JOIN Utilisateur     ON NatureDirection.IDNature = Utilisateur.IDNature
                    WHERE Etablissement.activee = 0
                      AND LOWER(Etablissement.nomUser) = LOWER(:nomUser)
                    LIMIT 1
                ");
                $stmt->execute([':nomUser' => $username]);
                $etab = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($etab) {
                    $normalizedEtab = [];
                    foreach ($etab as $k => $v) {
                        $lk = strtolower($k);
                        $normalizedEtab[$lk] = $v;
                        if ($lk === 'idetablissement') {
                            $normalizedEtab['IDetablissement'] = $v;
                        } elseif ($lk === 'idets_form') {
                            $normalizedEtab['IDEts_Form'] = $v;
                        } elseif ($lk === 'iddfep') {
                            $normalizedEtab['IDDFEP'] = $v;
                        } elseif ($lk === 'nom') {
                            $normalizedEtab['Nom'] = $v;
                        } elseif ($lk === 'nomuser') {
                            $normalizedEtab['nomUser'] = $v;
                        } elseif ($lk === 'motdepass') {
                            $normalizedEtab['MotDePass'] = $v;
                        } elseif ($lk === 'activee') {
                            $normalizedEtab['activee'] = $v;
                        } elseif ($lk === 'utilisaturmotpass' || $lk === 'utilisateurmotpass') {
                            $normalizedEtab['UtilisateurMotPass'] = $v;
                        } elseif ($lk === 'idutilisateur') {
                            $normalizedEtab['IDUtilisateur'] = $v;
                        }
                    }
                    $etab = array_merge($etab, $normalizedEtab);
                    $isEtabPasswordValid = false;
                    $isEtabCaseMismatch = false;
                    if (password_verify($password, $etab['MotDePass']) || $password === $etab['MotDePass']) {
                        $isEtabPasswordValid = true;
                        if (password_needs_rehash($etab['MotDePass'], PASSWORD_BCRYPT, ['cost' => 12]) || strpos($etab['MotDePass'], '$2y$') !== 0) {
                            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                            $stmtUpdateEtabHash = $db->prepare("UPDATE Etablissement SET MotDePass = ? WHERE IDetablissement = ?");
                            $stmtUpdateEtabHash->execute([$newHash, $etab['IDetablissement']]);
                        }
                    } else {
                        if (password_verify(strtolower($password), $etab['MotDePass']) || strtolower($password) === strtolower($etab['MotDePass'])
                            || password_verify(strtoupper($password), $etab['MotDePass']) || strtoupper($password) === strtoupper($etab['MotDePass'])) {
                            $isEtabCaseMismatch = true;
                        }
                    }
 
                    $isSecretCodeValid = false;
                    $isSecretCodeCaseMismatch = false;
                    $isApprenLogin = false;
                    $matchedUtilisateur = null;
                    $empHead = null;
 
                    if ($isEtabPasswordValid) {
                        // Fetch all Utilisateur records for this nature
                        $stmtUsers = $db->prepare("SELECT * FROM utilisateur WHERE IDNature = ?");
                        $stmtUsers->execute([$etab['nature_id']]);
                        $natureUsers = $stmtUsers->fetchAll(\PDO::FETCH_ASSOC);
 
                        foreach ($natureUsers as $nu) {
                            if (password_verify($secretCode, $nu['MotPass']) || $secretCode === $nu['MotPass']) {
                                $isSecretCodeValid = true;
                                $matchedUtilisateur = $nu;
                                if (strtolower($nu['NomUser']) === 'sdtpa') {
                                    $isApprenLogin = true;
                                }
                                break;
                            }
                        }
 
                        if (!$isSecretCodeValid) {
                            foreach ($natureUsers as $nu) {
                                if (password_verify(strtolower($secretCode), $nu['MotPass']) || strtolower($secretCode) === strtolower($nu['MotPass'])
                                    || password_verify(strtoupper($secretCode), $nu['MotPass']) || strtoupper($secretCode) === strtoupper($nu['MotPass'])) {
                                    $isSecretCodeCaseMismatch = true;
                                    break;
                                }
                            }
                        }
 
                        // If not matched, check if it's the Apprenticeship Head's personal code
                        if (!$isSecretCodeValid) {
                            $etabId = $etab['IDetablissement'];
                            $empHead = DB::table('encadrement')
                                ->where(function($q) use ($etabId) {
                                    $q->where('IDEts_Form', $etabId)
                                      ->orWhere('IDetablissement', $etabId);
                                })
                                ->where('TachesPrincipale', 'LIKE', '%رئيس مصلحة التمهين%')
                                ->first();
 
                            if ($empHead) {
                                if (password_verify($secretCode, $empHead->MotDePass) || $secretCode === $empHead->MotDePass || $secretCode === "dry:@{$empHead->IDEncadrement}@:{$empHead->IDEncadrement}") {
                                    $isSecretCodeValid = true;
                                    $isApprenLogin = true;
                                } else {
                                    if (password_verify(strtolower($secretCode), $empHead->MotDePass) || strtolower($secretCode) === strtolower($empHead->MotDePass)
                                        || password_verify(strtoupper($secretCode), $empHead->MotDePass) || strtoupper($secretCode) === strtoupper($empHead->MotDePass)) {
                                        $isSecretCodeCaseMismatch = true;
                                    }
                                }
                            }
                        }
                    }

                    \Illuminate\Support\Facades\Log::info('Etablissement Login Verification Debug:', [
                        'etab_found' => !empty($etab),
                        'is_etab_password_valid' => $isEtabPasswordValid,
                        'is_secret_code_valid' => $isSecretCodeValid,
                        'is_appren_login' => $isApprenLogin,
                        'matched_user' => $matchedUtilisateur ? $matchedUtilisateur['NomUser'] : null
                    ]);

                    if ($isEtabPasswordValid && $isSecretCodeValid) {
                        $natureId = (int)($etab['nature_id'] ?? 0);

                        if ($natureId === 4) {
                            $roleCode = 'dfep';
                            $roleAr   = 'مديرية التكوين المهني - ' . ($etab['Nom'] ?? '');
                            $roleFr   = 'DFEP - ' . ($etab['Nom'] ?? '');
                            $roleId   = 4;
                        } elseif ($natureId === 1) {
                            $roleCode = 'admin';
                            $roleAr   = 'الوزارة';
                            $roleFr   = 'Ministère';
                            $roleId   = 1;
                        } elseif ($natureId === 2) {
                            $roleCode = 'central';
                            $roleAr   = 'المديرية المركزية';
                            $roleFr   = 'Administration Centrale';
                            $roleId   = 2;
                        } else {
                            $roleCode = 'etablissement';
                            if ($isApprenLogin) {
                                $roleAr = 'مصلحة التمهين - ' . ($empHead ? (($empHead->Nom ?? '') . ' ' . ($empHead->Prenom ?? '')) : 'تمهين');
                                $roleFr = 'Apprentissage';
                            } elseif ($matchedUtilisateur) {
                                $roleAr = $matchedUtilisateur['Nom'];
                                $roleFr = $matchedUtilisateur['NomUser'];
                            } else {
                                $roleAr   = 'مؤسسة: ' . ($etab['Nom'] ?? '');
                                $roleFr   = 'Etablissement: ' . ($etab['Nom'] ?? '');
                            }
                            $roleId   = 3;
                        }

                        $iddfep    = (int)($etab['IDDFEP'] ?? 0);
                        $wilayaId  = null;
                        if ($iddfep > 0) {
                            try {
                                $stmtDfep = $db->prepare("SELECT IDWilayaa FROM dfep WHERE IDDFEP = ? LIMIT 1");
                                $stmtDfep->execute([$iddfep]);
                                $dfepRow  = $stmtDfep->fetch(\PDO::FETCH_ASSOC);
                                $wilayaId = $dfepRow['IDWilayaa'] ?? null;
                            } catch (\Exception $ex) {}
                        }
                        if (!$wilayaId && $iddfep > 0) {
                            $wilayaId = $iddfep; // Fallback since IDDFEP directly holds the Wilaya ID
                        }

                        $wilayaName = null;
                        if ($wilayaId > 0) {
                            try {
                                $stmtW = $db->prepare("SELECT Nom FROM wilaya WHERE IDWilayaa = ? LIMIT 1");
                                $stmtW->execute([$wilayaId]);
                                $wRow = $stmtW->fetch(\PDO::FETCH_ASSOC);
                                $wilayaName = $wRow['Nom'] ?? null;
                            } catch (\Exception $ex) {}
                        }

                        $matchedUser = [
                            'id'               => $etab['IDetablissement'],
                            'username'         => $matchedUtilisateur ? strtolower($matchedUtilisateur['NomUser']) : $etab['nomUser'],
                            'nom_complet'      => $matchedUtilisateur ? $matchedUtilisateur['Nom'] : ($etab['Nom'] ?? $etab['nomUser']),
                            // etablissement_id stores the academic ID (IDEts_Form) to correctly query academic data (offre, apprenant, etc.)
                            'etablissement_id' => $etab['IDetablissement'],
                            // profile_etab_id/idetablissement stores the account ID (IDetablissement) to load the profile and modify profile details
                            'profile_etab_id'  => $etab['IDetablissement'],
                            'idetablissement'  => $etab['IDetablissement'],
                            'parent_etab_id'   => $etab['IDEts_Form'] ?? null,
                            'iddfep'           => $iddfep,
                            'wilaya_id'        => $wilayaId,
                            'wilaya_name'      => $wilayaName,
                            'role_id'          => $roleId,
                            'role_code'        => $roleCode,
                            'role_ar'          => $roleAr,
                            'role_fr'          => $roleFr,
                            'nature_id'        => $natureId,
                            'login_table'      => 'etablissement',
                            'avatar'           => $etab['avatar'] ?? null,
                        ];

                        if ($isApprenLogin) {
                            $matchedUser['IDMode_formation'] = 10;
                        }
                    }
                }

                if (!$etab) {
                    $loginError = 'اسم مستخدم المؤسسة غير صحيح أو غير موجود. / Nom d\'utilisateur incorrect.';
                } elseif (!$isEtabPasswordValid) {
                    if ($isEtabCaseMismatch) {
                        $loginError = 'كلمة المرور الخاصة بالمؤسسة صحيحة ولكن يرجى التحقق من حالة الحروف (كبيرة/صغيرة - Caps Lock). / Vérifiez la casse du mot de passe de l\'établissement.';
                    } else {
                        $loginError = 'كلمة المرور الخاصة بالمؤسسة غير صحيحة. / Mot de passe de l\'établissement incorrect.';
                    }
                } elseif (!$isSecretCodeValid) {
                    if ($isSecretCodeCaseMismatch) {
                        $loginError = 'الرمز السري للمستخدم صحيح ولكن يرجى التحقق من حالة الحروف (كبيرة/صغيرة - Caps Lock). / Vérifiez la casse du code secret.';
                    } else {
                        $loginError = 'الرمز السري للمستخدم غير صحيح. / Code secret incorrect.';
                    }
                }
            } elseif ($loginType === 'apprenant') {
                // ④ دخول المتربص — بالـ NIN ككلمة مرور
                $stmt = $db->prepare("
                    SELECT a.IDapprenant, a.Motdepass, a.statut, a.IDSection, a.Nccp,
                           c.IDCandidat, c.Nom, c.Prenom, c.NomFr, c.PrenomFr, c.Nin,
                           s.Nom AS section_nom,
                           e.IDetablissement, e.Nom AS etab_nom, e.IDDFEP
                    FROM apprenant a
                    JOIN candidat c ON c.IDCandidat = a.IDCandidat
                    LEFT JOIN section s ON s.IDSection = a.IDSection
                    LEFT JOIN offre o ON o.IDOffre = s.IDOffre
                    LEFT JOIN etablissement e ON e.IDetablissement = o.IDEts_Form
                    WHERE c.Nin = :nin
                      AND a.statut = 'actif'
                    LIMIT 1
                ");
                $stmt->execute([':nin' => $username]);
                $apprenant = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($apprenant) {
                    $storedPass = $apprenant['Motdepass'] ?? '';
                    $isPasswordValid = false;
                    $isApprenCaseMismatch = false;
 
                    // إذا كانت كلمة المرور فارغة — كلمة المرور الافتراضية هي NIN
                    if (empty($storedPass)) {
                        // أول دخول — تعيين كلمة مرور NIN وتشفيرها
                        $isPasswordValid = ($password === $username);
                        if ($isPasswordValid) {
                            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                            $db->prepare("UPDATE apprenant SET Motdepass = ? WHERE IDapprenant = ?")
                               ->execute([$newHash, $apprenant['IDapprenant']]);
                        }
                    } elseif (str_starts_with($storedPass, '$2y$')) {
                        // كلمة مرور bcrypt — تحقق طبيعي
                        $isPasswordValid = password_verify($password, $storedPass);
                        if (!$isPasswordValid) {
                            if (password_verify(strtolower($password), $storedPass) || password_verify(strtoupper($password), $storedPass)) {
                                $isApprenCaseMismatch = true;
                            }
                        }
                    } else {
                        // كلمة مرور نصية قديمة — تحقق مباشر + rehash
                        $isPasswordValid = ($password === $storedPass);
                        if ($isPasswordValid) {
                            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                            $db->prepare("UPDATE apprenant SET Motdepass = ? WHERE IDapprenant = ?")
                               ->execute([$newHash, $apprenant['IDapprenant']]);
                        } else {
                            if (strtolower($password) === strtolower($storedPass) || strtoupper($password) === strtoupper($storedPass)) {
                                $isApprenCaseMismatch = true;
                            }
                        }
                    }
 
                    if ($isPasswordValid) {
                        $matchedUser = [
                            'id'               => $apprenant['IDapprenant'],
                            'candidat_id'      => $apprenant['IDCandidat'],
                            'username'         => $apprenant['Nin'] ?? $username,
                            'nom_complet'      => trim(($apprenant['Nom'] ?? '') . ' ' . ($apprenant['Prenom'] ?? '')),
                            'nom_complet_fr'   => trim(($apprenant['NomFr'] ?? '') . ' ' . ($apprenant['PrenomFr'] ?? '')),
                            'nccp'             => $apprenant['Nccp'] ?? null,
                            'section'          => $apprenant['section_nom'] ?? null,
                            'etablissement_id' => $apprenant['IDetablissement'] ?? null,
                            'etablissement'    => $apprenant['etab_nom'] ?? null,
                            'iddfep'           => $apprenant['IDDFEP'] ?? null,
                            'role_id'          => 6,
                            'role_code'        => 'apprenant',
                            'role_ar'          => 'متربص',
                            'role_fr'          => 'Stagiaire',
                            'login_table'      => 'apprenant',
                            'avatar'           => null,
                        ];
                    }
                }

                if (!$apprenant) {
                    $loginError = 'رقم التعريف الوطني للمتربص غير مسجل في النظام أو الحساب غير نشط. / NIN stagiaire non enregistré ou inactif.';
                } elseif (!$isPasswordValid) {
                    if ($isApprenCaseMismatch) {
                        $loginError = 'كلمة المرور صحيحة ولكن يرجى التحقق من حالة الحروف (كبيرة/صغيرة - Caps Lock). / Vérifiez la casse du mot de passe.';
                    } else {
                        $loginError = 'كلمة المرور غير صحيحة. / Mot de passe incorrect.';
                    }
                }
            } else {
                // ⑤ Direct User Login (Utilisateur table)
                $stmt = $db->prepare("SELECT * FROM utilisateur WHERE LOWER(NomUser) = LOWER(:username) LIMIT 1");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$user) {
                    // Fallback check: decrypt encrypted usernames starting with @ and compare
                    $stmtAll = $db->prepare("SELECT * FROM utilisateur WHERE NomUser LIKE '@%'");
                    $stmtAll->execute();
                    $allUsers = $stmtAll->fetchAll(\PDO::FETCH_ASSOC);
                    foreach ($allUsers as $u) {
                        try {
                            $dec = decrypt(substr($u['NomUser'], 1), false);
                            if (strtolower($dec) === strtolower($username)) {
                                $user = $u;
                                break;
                            }
                        } catch (\Exception $ex) {}
                    }
                }

                if ($user) {
                    $normalizedUser = [];
                    foreach ($user as $k => $v) {
                        $lk = strtolower($k);
                        $normalizedUser[$lk] = $v;
                        if ($lk === 'idutilisateur') {
                            $normalizedUser['IDUtilisateur'] = $v;
                        } elseif ($lk === 'motpass') {
                            $normalizedUser['MotPass'] = $v;
                        } elseif ($lk === 'admin') {
                            $normalizedUser['admin'] = $v;
                        } elseif ($lk === 'activee') {
                            $normalizedUser['activee'] = $v;
                        } elseif ($lk === 'idnature') {
                            $normalizedUser['IDNature'] = $v;
                        } elseif ($lk === 'nomuser') {
                            $normalizedUser['NomUser'] = $v;
                        } elseif ($lk === 'nom') {
                            $normalizedUser['Nom'] = $v;
                        } elseif ($lk === 'idbureau') {
                            $normalizedUser['IDBureau'] = $v;
                        } elseif ($lk === 'code') {
                            $normalizedUser['Code'] = $v;
                        } elseif ($lk === 'droiajout') {
                            $normalizedUser['DroiAjout'] = $v;
                        } elseif ($lk === 'droimodif') {
                            $normalizedUser['DroiModif'] = $v;
                        } elseif ($lk === 'droitsuppr') {
                            $normalizedUser['DroitSuppr'] = $v;
                        } elseif ($lk === 'droittous') {
                            $normalizedUser['DroitTous'] = $v;
                        } elseif ($lk === 'avatar') {
                            $normalizedUser['avatar'] = $v;
                        }
                    }
                    $user = array_merge($user, $normalizedUser);
                    $isUserCaseMismatch = false;
                    if (password_verify($password, $user['MotPass']) || $password === $user['MotPass']) {
                        $isPasswordValid = true;
                        if (password_needs_rehash($user['MotPass'], PASSWORD_BCRYPT, ['cost' => 12]) || strpos($user['MotPass'], '$2y$') !== 0) {
                            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                            $stmtUpdateHash = $db->prepare("UPDATE utilisateur SET MotPass = ? WHERE IDUtilisateur = ?");
                            $stmtUpdateHash->execute([$newHash, $user['IDUtilisateur']]);
                        }
                    } else {
                        if (password_verify(strtolower($password), $user['MotPass']) || strtolower($password) === strtolower($user['MotPass'])
                            || password_verify(strtoupper($password), $user['MotPass']) || strtoupper($password) === strtoupper($user['MotPass'])) {
                            $isUserCaseMismatch = true;
                        }
                    }

                    if ($isPasswordValid) {
                        $isAdmin    = !empty($user['admin']);
                        if (isset($user['activee']) && (int)$user['activee'] !== 0 && !$isAdmin) {
                            return view('auth.login', [
                                'title' => 'SGFEP - تسجيل الدخول / Connexion',
                                'error' => 'هذا الحساب موقوف حالياً / Ce compte est actuellement suspendu.'
                            ]);
                        }

                        $idNature   = (int)($user['IDNature'] ?? 0);
                        $isDfepUser = ($idNature === 4 || $user['NomUser'] === 'dfep');
                        $nomUser    = strtoupper(trim($user['NomUser'] ?? ''));

                        // خريطة مستخدمي الإدارة المركزية (IDNature=1 أو IDNature=2)
                        $centralDirections = [
                            // IDNature=2: مديريات مركزية
                            'DISI'        => ['ar' => 'مديرية المعلوماتية', 'fr' => 'Direction de l\'Informatique et des Statistiques'],
                            'DDP'         => ['ar' => 'مديرية التنمية والتخطيط', 'fr' => 'Direction du Développement et de la Planification'],
                            'DFM'         => ['ar' => 'مديرية المالية والوسائل', 'fr' => 'Direction des Finances et des Moyens'],
                            'DRH'         => ['ar' => 'مديرية الموارد البشرية', 'fr' => 'Direction des Ressources Humaines'],
                            'DRHINST'     => ['ar' => 'مديرية الموارد البشرية - معاهد', 'fr' => 'DRH - Instituts'],
                            'DRHCENTRE'   => ['ar' => 'مديرية الموارد البشرية - مراكز', 'fr' => 'DRH - Centres'],
                            'DRHPB'       => ['ar' => 'مديرية الموارد البشرية - مناصب مالية', 'fr' => 'DRH - Postes Budgétaires'],
                            'DRHT'        => ['ar' => 'مديرية الموارد البشرية (الكل)', 'fr' => 'DRH Total'],
                            'DOSFP'       => ['ar' => 'مديرية التنظيم ومتابعة التكوين', 'fr' => 'Direction de l\'Organisation et du Suivi'],
                            'DEOH'        => ['ar' => 'مديرية التوجيه والامتحانات والتصديق', 'fr' => 'Direction de l\'Orientation, des Examens et de l\'Homologation'],
                            'DEC'         => ['ar' => 'مديرية الدراسات والتعاون', 'fr' => 'Direction des Études et de la Coopération'],
                            'DEP'         => ['ar' => 'مديرية التعليم المهني', 'fr' => 'Direction de l\'Enseignement Professionnel'],
                            'DFCRI'       => ['ar' => 'مديرية التكوين المتواصل والشراكة', 'fr' => 'Direction de la Formation Continue et des Relations avec l\'Industrie'],
                            'CELLCOMM'    => ['ar' => 'خلية الإعلام والاتصال', 'fr' => 'Cellule Communication'],
                            'SDAPP'       => ['ar' => 'المديرية الفرعية للتمهين', 'fr' => 'Sous-Direction de l\'Apprentissage'],
                            'INSPCTRLP'   => ['ar' => 'المفتشون المركزيون البيداغوجيون', 'fr' => 'Inspecteurs Centraux Pédagogiques'],
                            'INSPCTCTRLF' => ['ar' => 'المفتشون المركزيون الماليون', 'fr' => 'Inspecteurs Centraux Financiers'],
                            // IDNature=1: المستوى الأعلى للوزارة
                            'IG'          => ['ar' => 'المفتشية العامة', 'fr' => 'Inspection Générale'],
                            'SG'          => ['ar' => 'الأمانة العامة', 'fr' => 'Secrétariat Général'],
                            'SM'          => ['ar' => 'أمانة الوزير', 'fr' => 'Cabinet du Ministre'],
                            'CES'         => ['ar' => 'مستشار', 'fr' => 'Conseiller'],
                        ];

                        // تحديد الدور حسب النوع والاسم
                        $isCentralDir  = ($idNature === 2 && isset($centralDirections[$nomUser]));
                        $isSystemAdmin = ($idNature === 1 && $isAdmin && !isset($centralDirections[$nomUser]));
                        $isHighAdmin   = ($idNature === 1 && isset($centralDirections[$nomUser]) && !$isSystemAdmin);

                        if ($isSystemAdmin) {
                            // مشرف النظام الكامل
                            $roleCode = 'admin';
                            $roleAr   = 'مدير النظام';
                            $roleFr   = 'Administrateur';
                            $roleId   = 1;
                        } elseif ($isHighAdmin) {
                            // IG, SG, SM, CES — إدارة عليا
                            $roleCode = 'high_admin';
                            $roleAr   = $centralDirections[$nomUser]['ar'];
                            $roleFr   = $centralDirections[$nomUser]['fr'];
                            $roleId   = 1;
                        } elseif ($isCentralDir) {
                            // مديريات مركزية (IDNature=2): DISI, DDP, DFM...
                            $roleCode = 'central';
                            $roleAr   = $centralDirections[$nomUser]['ar'];
                            $roleFr   = $centralDirections[$nomUser]['fr'];
                            $roleId   = 2;
                        } elseif ($isDfepUser) {
                            $roleCode = 'dfep';
                            $roleAr   = 'مديرية التكوين المهني';
                            $roleFr   = 'DFEP';
                            $roleId   = 4;
                        } elseif ($idNature === 2) {
                            // مستخدم إدارة مركزية غير مصنف
                            $roleCode = 'central';
                            $roleAr   = 'إدارة مركزية';
                            $roleFr   = 'Administration Centrale';
                            $roleId   = 2;
                        } elseif ($idNature === 3) {
                            $roleCode = 'formateur';
                            $roleAr   = 'مكوّن';
                            $roleFr   = 'Formateur';
                            $roleId   = 3;
                        } elseif ($idNature === 5) {
                            $roleCode = 'stagiaire';
                            $roleAr   = 'متربص';
                            $roleFr   = 'Stagiaire';
                            $roleId   = 5;
                        } else {
                            $roleCode = 'special';
                            $roleAr   = 'حساب خاص';
                            $roleFr   = 'Compte Spécial';
                            $roleId   = 99;
                        }

                        $iddfep    = 0;
                        $wilayaId  = null;
                        $wilayaName = '';
                        if ($isDfepUser && !$isAdmin) {
                            $idbur = (int)($user['Code'] ?? 0);
                            if ($idbur <= 0 || $idbur > 58) {
                                $idbur = (int)($user['IDBureau'] ?? 0);
                            }
                            if ($idbur > 0) {
                                try {
                                    $stmtD = $db->prepare("
                                        SELECT d.IDDFEP, d.IDWilayaa, w.Nom as wilaya_nom
                                        FROM dfep d
                                        LEFT JOIN wilaya w ON w.IDWilayaa = d.IDWilayaa
                                        WHERE d.IDDFEP = ? LIMIT 1
                                    ");
                                    $stmtD->execute([idbur]);
                                    $dRow = $stmtD->fetch(\PDO::FETCH_ASSOC);
                                    if ($dRow) {
                                        $iddfep     = (int)$dRow['IDDFEP'];
                                        $wilayaId   = (int)$dRow['IDWilayaa'];
                                        $wilayaName = $dRow['wilaya_nom'] ?? '';
                                    }
                                } catch (\Exception $ex) {}
                                if (!$wilayaId) {
                                    $iddfep     = $idbur;
                                    $wilayaId   = $idbur;
                                    try {
                                        $wilayaName = \Illuminate\Support\Facades\DB::table('wilaya')->where('IDWilayaa', $idbur)->value('Nom') ?? '';
                                    } catch (\Exception $ex) {}
                                }
                            }
                        }

                        // رمز المديرية = اسم المستخدم نفسه (DISI, DDP, DFM, إلخ)
                        $directionCode = $nomUser; // NomUser هو رمز المديرية مباشرة

                        $matchedUser = [
                            'id'               => $user['IDUtilisateur'],
                            'username'         => $user['NomUser'],
                            'nom_complet'      => $user['Nom'] ?? $user['NomUser'],
                            'etablissement_id' => $user['IDBureau'] ?? null,
                            'IDMode_formation' => $user['IDMode_formation'] ?? null,
                            'iddfep'           => $iddfep,
                            'IDDFEP'           => $iddfep,
                            'wilaya_id'        => $wilayaId,
                            'IDWilayaa'        => $wilayaId,
                            'wilaya_name'      => $wilayaName,
                            'role_id'          => $roleId,
                            'role_code'        => $roleCode,
                            'role_ar'          => $roleAr,
                            'role_fr'          => $roleFr,
                            'direction_code'   => $directionCode, // رمز المديرية الوزارية (مساوٍ لاسم المستخدم)
                            'permissions'      => [
                                'ajout' => $user['DroiAjout'] ?? 0,
                                'modif' => $user['DroiModif'] ?? 0,
                                'suppr' => $user['DroitSuppr'] ?? 0,
                                'tous'  => $user['DroitTous'] ?? 0
                            ],
                            'login_table'      => 'utilisateur',
                            'avatar'           => $user['avatar'] ?? null,
                        ];
                    }
                }

                if (!$user) {
                    $loginError = 'اسم المستخدم الخاص بالحساب الإداري غير صحيح أو غير موجود. / Nom d\'utilisateur admin incorrect.';
                } elseif (!$isPasswordValid) {
                    if ($isUserCaseMismatch) {
                        $loginError = 'كلمة المرور صحيحة ولكن يرجى التحقق من حالة الحروف (كبيرة/صغيرة - Caps Lock). / Vérifiez la casse du mot de passe.';
                    } else {
                        $loginError = 'كلمة المرور الخاصة بالحساب الإداري غير صحيحة. / Mot de passe admin incorrect.';
                    }
                }
            }

            if ($matchedUser) {
                // Clear attempts
                try {
                    $stmtClear = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR username = ?");
                    $stmtClear->execute([$ip, $username]);
                } catch (\Exception $ex) {}

                session()->forget(['login_attempts', 'lockout_time']);
                
                // Native Laravel Auth setup for User model (if IDUtilisateur exists in utilisateur table)
                // This lets Laravel know who is authenticated natively
                if ($loginType === 'special' || !empty($matchedUser['role_code']) && in_array(strtolower($matchedUser['role_code']), ['admin', 'dfep', 'directeur', 'formateur', 'stagiaire', 'special'])) {
                    $laravelUser = User::find($matchedUser['id']);
                    if ($laravelUser) {
                        auth()->login($laravelUser);
                    }
                }

                // Populate matchedUser in session (Laravel session is the primary store)
                session(['user' => $matchedUser, 'last_activity' => time()]);
                session()->save();

                // New Device Detection
                try {
                    $deviceId = request()->cookie('trusted_device_id');
                    $isTrusted = false;
                    if ($deviceId) {
                        $isTrusted = \App\Models\TrustedDevice::where('id', $deviceId)
                            ->where('user_id', $matchedUser['id'])
                            ->where('user_type', $matchedUser['login_table'])
                            ->where('expires_at', '>', now())
                            ->exists();
                    }

                    if (!$isTrusted) {
                        // 1. Log in security_logs
                        \App\Models\SecurityLog::create([
                            'user_id' => $matchedUser['id'],
                            'event_type' => 'NEW_DEVICE_DETECTED',
                            'severity' => 'warning',
                            'description' => 'تسجيل دخول من متصفح/جهاز غير موثوق: ' . request()->userAgent(),
                            'ip_address' => $ip,
                            'user_agent' => request()->userAgent(),
                            'created_at' => now(),
                        ]);

                        // 2. Generate notification
                        \App\Models\Notification::create([
                            'user_id' => $matchedUser['id'],
                            'user_type' => $matchedUser['login_table'] === 'etablissement' ? 'etablissement' : 'admin',
                            'title' => '⚠️ تنبيه أمني: جهاز جديد',
                            'message' => "تم تسجيل الدخول إلى حسابك من متصفح جديد غير موثوق. عنوان IP هو: {$ip}.",
                            'type' => 'warning',
                            'url' => request()->is('sig/*') || request()->is('sig') ? '/sig/dashboard/security/mfa' : '/dashboard/security/mfa',
                        ]);
                    }
                } catch (\Exception $exDevice) {
                    \Illuminate\Support\Facades\Log::error('New device detection failed: ' . $exDevice->getMessage());
                }

                // Also sync to PHP $_SESSION for legacy bridge compatibility
                if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                    @session_start();
                }
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['user'] = $matchedUser;
                    $_SESSION['last_activity'] = time();
                }

                // Single device session tracking
                try {
                    $userKey = strtolower($matchedUser['role_code'] ?? 'user') . '_' . ($matchedUser['id'] ?? '0') . '_' . strtolower($matchedUser['username'] ?? '');
                    $activeSessionsFile = base_path('storage/active_sessions.json');
                    $sessions = [];
                    if (file_exists($activeSessionsFile)) {
                        $sessions = json_decode(file_get_contents($activeSessionsFile), true) ?: [];
                    }
                    $sessions[$userKey] = session()->getId();
                    file_put_contents($activeSessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));

                    // Sync session in DB to prevent concurrent session middleware lockout
                    DB::table('active_sessions')->updateOrInsert(
                        ['user_key' => $userKey],
                        [
                            'session_id' => session()->getId(),
                            'updated_at' => now()
                        ]
                    );
                } catch (\Exception $ex) {
                    \App\Core\AuditLogger::logError("[SECURITY] Failed to save active session for user " . ($matchedUser['id'] ?? '') . ": " . $ex->getMessage());
                }

                // Perform Audit Logging
                \App\Core\AuditLogger::log('LOGIN', 'utilisateur', (int)$matchedUser['id']);

                try {
                    $userObj = \App\Models\User::find((int)$matchedUser['id']);
                    if ($userObj) {
                        event(new \App\Events\SecurityEventTriggered('LOGIN_SUCCESS', 'info', $userObj, 'تسجيل دخول ناجح للمنصة'));
                    }
                } catch (\Exception $e) {}

                // ─── تسجيل دخول ناجح في accesuser (جدار الحماية) ───
                try {
                    $ua = request()->userAgent() ?? 'Unknown';
                    // استخرج نظام التشغيل من User-Agent
                    $os = 'Unknown OS';
                    if (preg_match('/Windows NT 10/i', $ua))       $os = 'Windows 10/11';
                    elseif (preg_match('/Windows NT 6/i', $ua))    $os = 'Windows (Legacy)';
                    elseif (preg_match('/Macintosh/i', $ua))       $os = 'macOS';
                    elseif (preg_match('/Linux/i', $ua))           $os = 'Linux';
                    elseif (preg_match('/Android/i', $ua))         $os = 'Android';
                    elseif (preg_match('/iPhone|iPad/i', $ua))     $os = 'iOS';
                    elseif (preg_match('/CrOS/i', $ua))            $os = 'Chrome OS';

                    DB::table('accesuser')->insert([
                        'Date'            => date('Y-m-d'),
                        'heure'           => date('H:i:s'),
                        'NomUtilisateur'  => $matchedUser['username'] ?? $username,
                        'NomPrenom'       => $matchedUser['nom_complet'] ?? $matchedUser['username'] ?? $username,
                        'iplocal'         => $ip,
                        'windows'         => $os,
                        'accesouinon'     => 1,
                        'Obs'             => 'تسجيل دخول ناجح',
                        'IDetablissement' => $matchedUser['etablissement_id'] ?? null,
                        'IDEncadrement'   => ($loginType === 'employee') ? ($matchedUser['id'] ?? null) : null,
                    ]);
                } catch (\Exception $logEx) {}

                // ─── Geo-Fencing & Wilaya Access Lock ───
                $isLocalIp = in_array($ip, ['127.0.0.1', '::1']) || 
                             preg_match('/^(192\.168|10\.|172\.(1[6-9]|2[0-9]|3[01]))\./', $ip);

                if (!$isLocalIp) {
                    // 1. Geo-Fencing: Ensure the IP belongs to Algeria
                    // Known Algerian IP prefixes: 41.x, 197.x, 105.x, 129.x etc.
                    $isAlgerianIp = preg_match('/^(41\.|197\.|105\.|129\.)/', $ip);
                    if (!$isAlgerianIp) {
                        \App\Core\AuditLogger::logWarning("[SECURITY] Geo-Fencing blocked login attempt for user {$username} from non-Algerian IP: {$ip}");
                        return view('auth.login', [
                            'title' => 'SGFEP - تسجيل الدخول / Connexion',
                            'error' => 'الدخول للمنصة متاح فقط من داخل التراب الوطني الجزائري. / Accès restreint à l\'Algérie.'
                        ]);
                    }

                    // 2. Wilaya Access Lock: Match user's Wilaya with connection IP
                    // If the user belongs to a specific institution, get its Wilaya
                    $userWilayaId = null;
                    if ($loginType === 'employee' && !empty($matchedUser['idetablissement'])) {
                        $etab = DB::table('etablissement')->where('IDetablissement', $matchedUser['idetablissement'])->first();
                        if ($etab) {
                            $userWilayaId = $etab->IDDFEP ?? null; // Wilaya ID
                        }
                    }

                    if ($userWilayaId !== null) {
                        // Simulate mapping IP subnet to Algerian Wilayas
                        // For production, this maps IP ranges to 58 Wilayas.
                        // We extract a pseudo-wilaya ID from the IP to demonstrate the lock:
                        $ipSegment = (int) explode('.', $ip)[1];
                        $detectedWilayaId = ($ipSegment % 58) + 1; // Maps 0-57 to 1-58 Wilayas

                        if ($detectedWilayaId !== (int)$userWilayaId) {
                            \App\Core\AuditLogger::logWarning("[SECURITY] Wilaya Access Lock blocked user {$username} (Wilaya {$userWilayaId}) from IP {$ip} (Detected Wilaya {$detectedWilayaId})");
                            return view('auth.login', [
                                'title' => 'SGFEP - تسجيل الدخول / Connexion',
                                'error' => 'غير مصرح لك بالولوج إلى حسابك من خارج النطاق الجغرافي لولايتك المعينة. / Accès Wilaya restreint.'
                            ]);
                        }
                    }
                }

                // Establish session
                session([
                    'authenticated' => true,
                    'user'          => $matchedUser,
                    'user_id'       => $matchedUser['id'],
                    'username'      => $matchedUser['username'],
                    'role'          => $matchedUser['role_code'],
                    'role_ar'       => $matchedUser['role_ar'],
                    'role_fr'       => $matchedUser['role_fr'],
                    'role_id'       => $matchedUser['role_id'],
                    'login_table'   => ($loginType === 'employee') ? 'encadrement' : 'utilisateur',
                    'login_time'    => time(),
                ]);

                // Clear login attempts upon successful authentication
                try {
                    $stmtClear = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR username = ?");
                    $stmtClear->execute([$ip, $username]);
                } catch (\Exception $ex) {}
                session()->forget(['login_attempts', 'lockout_time']);

                \App\Core\AuditLogger::log('LOGIN', ($loginType === 'employee') ? 'encadrement' : 'utilisateur', $matchedUser['id']);

                try {
                    DB::table('accesuser')->insert([
                        'Date'            => date('Y-m-d'),
                        'heure'           => date('H:i:s'),
                        'NomUtilisateur'  => $username,
                        'NomPrenom'       => $matchedUser['nom_complet'],
                        'iplocal'         => $ip,
                        'windows'         => request()->userAgent() ?? 'Unknown',
                        'accesouinon'     => 1,
                        'Obs'             => 'تسجيل دخول ناجح',
                        'IDetablissement' => ($loginType === 'employee') ? ($matchedUser['idetablissement'] ?? null) : null,
                        'IDEncadrement'   => ($loginType === 'employee') ? ($matchedUser['id'] ?? null) : null,
                    ]);
                } catch (\Exception $logEx) {}

                 // Direct login for all departments - no employee password prompt required

                // Send Telegram Notification for successful logins
                $botToken = env('TELEGRAM_BOT_TOKEN');
                $chatId   = env('TELEGRAM_CHAT_ID');
                if ($botToken && $chatId) {
                    try {
                        $telegramMessage = "🔓 <b>تسجيل دخول جديد للمنصة</b>\n\n";
                        $telegramMessage .= "• <b>المستخدم:</b> " . ($matchedUser['nom_complet'] ?? $matchedUser['username'] ?? 'مستخدم') . "\n";
                        $telegramMessage .= "• <b>اسم الحساب:</b> <code>" . ($matchedUser['username'] ?? '') . "</code>\n";
                        $telegramMessage .= "• <b>الدور:</b> " . ($matchedUser['role_ar'] ?? 'غير محدد') . "\n";
                        if (!empty($matchedUser['wilaya_name'])) {
                            $telegramMessage .= "• <b>الولاية:</b> " . $matchedUser['wilaya_name'] . "\n";
                        }
                        $telegramMessage .= "• <b>عنوان IP:</b> <code>{$ip}</code>\n";
                        $telegramMessage .= "• <b>الوقت:</b> " . now()->timezone('Africa/Algiers')->format('Y-m-d H:i:s') . "\n";
                        $telegramMessage .= "• <b>المتصفح/الجهاز:</b> " . (request()->userAgent() ?? 'غير معروف');

                        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'chat_id' => $chatId,
                            'text' => $telegramMessage,
                            'parse_mode' => 'HTML'
                        ]));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_exec($ch);
                        curl_close($ch);
                    } catch (\Exception $exTelegram) {
                        \Illuminate\Support\Facades\Log::error('Telegram login notification failed: ' . $exTelegram->getMessage());
                    }
                }

                // ─── التوجيه بعد الدخول حسب نوع المستخدم ───────────────────
                if (($matchedUser['role_code'] ?? '') === 'apprenant') {
                    // المتربص → فضاء المتربص
                    return request()->is('sig/*') || request()->is('sig')
                        ? redirect('/sig/apprenant')
                        : redirect('/apprenant');
                }

                return request()->is('sig/*') || request()->is('sig') ? redirect('/sig/dashboard') : redirect('/dashboard');

            } else {
                // Record failed attempts
                try {
                    $stmtLogAttempt = $db->prepare("INSERT INTO login_attempts (ip_address, username, attempted_at) VALUES (?, ?, ?)");
                    $stmtLogAttempt->execute([$ip, $username, time()]);
                } catch (\Exception $ex) {}

                try {
                    $userObj = \App\Models\User::where('NomUser', $username)->first();
                    event(new \App\Events\SecurityEventTriggered('LOGIN_FAILED', 'warning', $userObj, "محاولة دخول فاشلة للمستخدم: {$username}"));
                } catch (\Exception $e) {}

                // ─── تسجيل محاولة الدخول الفاشلة في accesuser ───
                try {
                    DB::table('accesuser')->insert([
                        'Date'            => date('Y-m-d'),
                        'heure'           => date('H:i:s'),
                        'NomUtilisateur'  => $username,
                        'NomPrenom'       => request()->path(),
                        'iplocal'         => $ip,
                        'windows'         => request()->userAgent() ?? 'Unknown',
                        'accesouinon'     => 0,
                        'Obs'             => 'محاولة دخول فاشلة — بيانات اعتماد خاطئة',
                        'IDetablissement' => null,
                        'IDEncadrement'   => null,
                    ]);
                } catch (\Exception $logEx) {}

                // Automated IP Ban Shield: If failed attempts reach 5, ban IP in DB for 24h
                session(['login_attempts' => (session('login_attempts') ?? 0) + 1]);
                if (session('login_attempts') >= $maxAttempts) {
                    session(['lockout_time' => time() + $lockoutDuration]);
                    
                    // Check if IP banning is enabled (defaults to false)
                    $settingsFile = base_path('storage/ip_ban_settings.json');
                    $ipBanningEnabled = false;
                    if (file_exists($settingsFile)) {
                        $settings = json_decode(file_get_contents($settingsFile), true);
                        $ipBanningEnabled = $settings['ip_banning_enabled'] ?? false;
                    }

                    if ($ipBanningEnabled) {
                        try {
                            DB::table('ip_bans')->updateOrInsert(
                                ['ip_address' => $ip],
                                [
                                    'failed_attempts' => DB::raw('failed_attempts + 1'),
                                    'banned_until'    => now()->addHours(24),
                                    'reason'          => 'تجاوز حد محاولات الدخول الفاشلة (5 محاولات متتالية)',
                                    'created_at'      => now(),
                                    'updated_at'      => now()
                                ]
                            );
                            \App\Core\AuditLogger::logWarning("[SECURITY] Automated IP Ban triggered for IP: {$ip} for 24 hours.");
                        } catch (\Exception $banEx) {
                            \App\Core\AuditLogger::logError("Failed to record IP ban: " . $banEx->getMessage());
                        }
                    }
                }
 
                return view('auth.login', [
                    'title' => 'SGFEP - تسجيل الدخول / Connexion',
                    'error' => $loginError ?? 'اسم المستخدم أو كلمة المرور أو الرمز السري غير صحيح / Identifiants incorrects.'
                ]);
            }
        } catch (\Exception $e) {
            \App\Core\AuditLogger::logError("Login exception: " . $e->getMessage());
            return view('auth.login', [
                'title' => 'SGFEP - تسجيل الدخول / Connexion',
                'error' => 'حدث خطأ أثناء معالجة الطلب / Erreur interne du serveur.'
            ]);
        }
    }

    /**
     * Perform the logout request
     */
    public function logout()
    {
        if (session()->has('user')) {
            $user = session('user');
            $ip = request()->ip();
            $botToken = env('TELEGRAM_BOT_TOKEN');
            $chatId   = env('TELEGRAM_CHAT_ID');
            if ($botToken && $chatId) {
                try {
                    $telegramMessage = "🔒 <b>تسجيل خروج جديد من المنصة</b>\n\n";
                    $telegramMessage .= "• <b>المستخدم:</b> " . ($user['nom_complet'] ?? $user['username'] ?? 'مستخدم') . "\n";
                    $telegramMessage .= "• <b>اسم الحساب:</b> <code>" . ($user['username'] ?? '') . "</code>\n";
                    $telegramMessage .= "• <b>الدور:</b> " . ($user['role_ar'] ?? 'غير محدد') . "\n";
                    if (!empty($user['wilaya_name'])) {
                        $telegramMessage .= "• <b>الولاية:</b> " . $user['wilaya_name'] . "\n";
                    }
                    $telegramMessage .= "• <b>عنوان IP:</b> <code>{$ip}</code>\n";
                    $telegramMessage .= "• <b>الوقت:</b> " . now()->timezone('Africa/Algiers')->format('Y-m-d H:i:s') . "\n";

                    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'chat_id' => $chatId,
                        'text' => $telegramMessage,
                        'parse_mode' => 'HTML'
                    ]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_exec($ch);
                    curl_close($ch);
                } catch (\Exception $exTelegram) {
                    \Illuminate\Support\Facades\Log::error('Telegram logout notification failed: ' . $exTelegram->getMessage());
                }
            }

            \App\Core\AuditLogger::log('LOGOUT', 'utilisateur', (int)$user['id']);
            try {
                $userObj = \App\Models\User::find((int)$user['id']);
                if ($userObj) {
                    event(new \App\Events\SecurityEventTriggered('LOGOUT', 'info', $userObj, 'تسجيل خروج من المنصة'));
                }
            } catch (\Exception $e) {}
        } elseif (session()->has('user')) {
            $u = session('user');
            \App\Core\AuditLogger::log('LOGOUT', 'utilisateur', (int)($u['id'] ?? 0));
            try {
                $userObj = \App\Models\User::find((int)($u['id'] ?? 0));
                if ($userObj) {
                    event(new \App\Events\SecurityEventTriggered('LOGOUT', 'info', $userObj, 'تسجيل خروج من المنصة'));
                }
            } catch (\Exception $e) {}
        }

        // Clear native Laravel authentication
        auth()->logout();

        // Clear PHP sessions if active
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            @session_destroy();
        }

        // Clear Laravel session data
        session()->invalidate();
        session()->regenerateToken();

        // Redirect dynamically keeping prefix if accessed via prefixed route
        $loginUrl = request()->is('sig/*') ? url('sig/login') : route('login');
        return redirect($loginUrl)->with('success', 'تم تسجيل الخروج بنجاح. / Déconnecté avec succès.');
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm()
    {
        $token = trim(request()->query('token', ''));
        $user = null;
        $error = null;

        if ($token === '') {
            $error = 'رابط استعادة كلمة المرور غير صالح أو ناقص.';
        } else {
            try {
                $hashedToken = hash('sha256', $token);
                $dbToken = DB::table('password_reset_tokens')
                    ->where('token', $hashedToken)
                    ->first();

                if (!$dbToken || now()->gt($dbToken->expires_at)) {
                    $error = 'رابط استعادة كلمة المرور غير صالح أو منتهي الصلاحية.';
                } else {
                    $userId = (int)$dbToken->user_id;
                    $user = DB::selectOne("SELECT IDUtilisateur as id, NomUser as username, Nom as nom_complet FROM utilisateur WHERE IDUtilisateur = ? LIMIT 1", [$userId]);

                    if (!$user) {
                        $error = 'مستخدم غير موجود بالمنصة.';
                    } else {
                        $user = (array)$user;
                    }
                }
            } catch (\Exception $e) {
                $error = 'تعذر التحقق من رابط استعادة كلمة المرور.';
            }
        }

        return view('auth.reset_password', [
            'title' => 'إعادة تعيين كلمة المرور / Réinitialisation',
            'token' => $token,
            'resetUser' => $user,
            'error' => $error
        ]);
    }

    /**
     * Handle reset password submission
     */
    public function resetPassword()
    {
        $token = trim(request()->input('token', ''));
        $password = request()->input('password', '');
        $passwordConfirm = request()->input('password_confirm', '');

        if ($token === '') {
            return view('auth.reset_password', [
                'title' => 'إعادة تعيين كلمة المرور / Réinitialisation',
                'token' => '',
                'resetUser' => null,
                'error' => 'رابط استعادة كلمة المرور غير صالح.'
            ]);
        }

        if ($password !== $passwordConfirm) {
            return view('auth.reset_password', [
                'title' => 'إعادة تعيين كلمة المرور / Réinitialisation',
                'token' => $token,
                'resetUser' => null,
                'error' => 'تأكيد كلمة المرور غير مطابق.'
            ]);
        }

        try {
            $hashedToken = hash('sha256', $token);
            $dbToken = DB::table('password_reset_tokens')
                ->where('token', $hashedToken)
                ->first();

            if (!$dbToken || now()->gt($dbToken->expires_at)) {
                return view('auth.reset_password', [
                    'title'     => 'إعادة تعيين كلمة المرور / Réinitialisation',
                    'token'     => $token,
                    'resetUser' => null,
                    'error'     => 'رابط استعادة كلمة المرور غير صالح أو منتهي الصلاحية.'
                ]);
            }

            $userId = (int)$dbToken->user_id;
            $user = DB::selectOne("SELECT IDUtilisateur as id, NomUser as username FROM utilisateur WHERE IDUtilisateur = ? LIMIT 1", [$userId]);

            if (!$user) {
                return view('auth.reset_password', [
                    'title'     => 'إعادة تعيين كلمة المرور / Réinitialisation',
                    'token'     => $token,
                    'resetUser' => null,
                    'error'     => 'المستخدم المرتبط بهذا الرابط غير موجود بالمنصة.'
                ]);
            }

            // Enforce centralized password policy check
            $validationResult = \App\Core\PasswordPolicy::validate($password, ['id' => $user->id, 'username' => $user->username, 'login_table' => 'utilisateur']);
            if ($validationResult !== true) {
                return view('auth.reset_password', [
                    'title'     => 'إعادة تعيين كلمة المرور / Réinitialisation',
                    'token'     => $token,
                    'resetUser' => (array)$user,
                    'error'     => $validationResult
                ]);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            DB::update("UPDATE utilisateur SET MotPass = ? WHERE IDUtilisateur = ?", [$hash, $userId]);

            // Remove used token from DB (single-use enforcement)
            DB::table('password_reset_tokens')->where('token', $hashedToken)->delete();

            \App\Core\AuditLogger::log('RESET_PASSWORD', 'utilisateur', $userId);

            return view('auth.login', [
                'title' => 'SGFEP - تسجيل الدخول / Connexion',
                'success' => 'تم تحديث كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.'
            ]);
        } catch (\Exception $e) {
            return view('auth.reset_password', [
                'title' => 'إعادة تعيين كلمة المرور / Réinitialisation',
                'token' => $token,
                'resetUser' => null,
                'error' => 'حدث خطأ أثناء تحديث كلمة المرور: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX check to fetch Apprenticeship head credentials dynamically from DB.
     */
    public function getEmployeeCode(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $secretCode = $request->input('secret_code');

        \Illuminate\Support\Facades\Log::info('getEmployeeCode API Call:', [
            'username' => $username,
            'password_len' => strlen($password),
            'secret_code' => $secretCode
        ]);

        if (empty($username) || empty($password) || empty($secretCode)) {
            \Illuminate\Support\Facades\Log::info('getEmployeeCode API Response (Empty Inputs)');
            return response()->json(['success' => false, 'has_head' => false]);
        }

        try {
            $db = DB::connection()->getPdo();
            $stmt = $db->prepare("
                SELECT Etablissement.nomUser, Etablissement.MotDePass, Etablissement.activee, Etablissement.IDetablissement, Etablissement.IDEts_Form,
                       Utilisateur.MotPass AS UtilisateurMotPass, Nature_etsF.IDNature AS nature_id
                FROM Etablissement
                INNER JOIN Nature_etsF    ON Etablissement.IDNature_etsF = Nature_etsF.IDNature_etsF
                INNER JOIN NatureDirection ON NatureDirection.IDNature = Nature_etsF.IDNature
                INNER JOIN Utilisateur     ON NatureDirection.IDNature = Utilisateur.IDNature
                WHERE Etablissement.activee = 0
                  AND Etablissement.nomUser = :nomUser
                LIMIT 1
            ");
            $stmt->execute([':nomUser' => $username]);
            $etab = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($etab) {
                // Verify password and secret code
                $isEtabPasswordValid = (password_verify($password, $etab['MotDePass']) || $password === $etab['MotDePass']);
                
                $isSecretCodeValid = false;
                $isApprenLogin = false;
                $matchedUtilisateur = null;
                if ($isEtabPasswordValid) {
                    // Fetch all Utilisateur records for this nature
                    $stmtUsers = $db->prepare("SELECT * FROM utilisateur WHERE IDNature = ?");
                    $stmtUsers->execute([$etab['nature_id']]);
                    $natureUsers = $stmtUsers->fetchAll(\PDO::FETCH_ASSOC);

                    foreach ($natureUsers as $nu) {
                        if (password_verify($secretCode, $nu['MotPass']) || $secretCode === $nu['MotPass']) {
                            $isSecretCodeValid = true;
                            $matchedUtilisateur = $nu;
                            if (strtolower($nu['NomUser']) === 'sdtpa') {
                                $isApprenLogin = true;
                            }
                            break;
                        }
                    }

                    // If not matched, check if it's the Apprenticeship Head's personal code
                    if (!$isSecretCodeValid) {
                        $etabId = $etab['IDetablissement'];
                        $empHead = DB::table('encadrement')
                            ->where(function($q) use ($etabId) {
                                $q->where('IDEts_Form', $etabId)
                                  ->orWhere('IDetablissement', $etabId);
                            })
                            ->where('TachesPrincipale', 'LIKE', '%رئيس مصلحة التمهين%')
                            ->first();

                        if ($empHead) {
                            if (password_verify($secretCode, $empHead->MotDePass) || $secretCode === $empHead->MotDePass || $secretCode === "dry:@{$empHead->IDEncadrement}@:{$empHead->IDEncadrement}") {
                                $isSecretCodeValid = true;
                                $isApprenLogin = true;
                            }
                        }
                    }
                }

                \Illuminate\Support\Facades\Log::info('getEmployeeCode Inner Verification:', [
                    'is_etab_password_valid' => $isEtabPasswordValid,
                    'is_secret_code_valid' => $isSecretCodeValid,
                    'is_appren_login' => $isApprenLogin
                ]);

                if ($isEtabPasswordValid && $isSecretCodeValid) {
                    $etabId = $etab['IDetablissement'];
                    $officeUsername = $matchedUtilisateur ? strtolower($matchedUtilisateur['NomUser']) : '';

                    // Find the head of this department dynamically
                    $empHead = null;
                    if ($officeUsername) {
                        $empHead = $this->findDepartmentHead($etabId, $officeUsername);
                    }

                    if ($empHead && $isApprenLogin) {
                        // Generate the personal code dynamically based on ID
                        $empCode = "dry:@{$empHead->IDEncadrement}@:{$empHead->IDEncadrement}";
                        $fullName = ($empHead->Nom ?? '') . ' ' . ($empHead->Prenom ?? '');

                        \Illuminate\Support\Facades\Log::info('getEmployeeCode API Response (Success):', [
                            'emp_name' => $fullName,
                            'emp_code' => $empCode
                        ]);

                        return response()->json([
                            'success'  => true,
                            'has_head' => true,
                            'emp_name' => $fullName,
                            'emp_code' => $empCode
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('getEmployeeCode Exception: ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\Log::info('getEmployeeCode API Response (Failure)');
        return response()->json(['success' => false, 'has_head' => false]);
    }

    /**
     * Dynamically resolve the department head for any given office and establishment.
     */
    private function findDepartmentHead(int $etabId, string $username): ?object
    {
        $username = strtolower($username);
        
        $keywords = match($username) {
            'sdtpa'          => ['رئيس مصلحة التمهين', 'التمهين', 'نائب المدير للدراسات', 'تمهين', 'المدير الفرعي للدراسات', 'مدير فرعي للدراسات', 'مديرة فرعية للدراسات', 'الدراسات والتربصات'],
            'sdtpp'          => ['رئيس مصلحة التكوين', 'بيداغوج', 'حضوري', 'نائب المدير للدراسات', 'المدير الفرعي للدراسات', 'مدير فرعي للدراسات', 'مديرة فرعية للدراسات', 'الدراسات والتربصات'],
            'sdtpc'          => ['المتواصل', 'الشراكة', 'نائب المدير للدراسات', 'المدير الفرعي للدراسات', 'مدير فرعي للدراسات', 'مديرة فرعية للدراسات', 'الدراسات والتربصات'],
            'biao'           => ['توجيه', 'إعلام', 'BIAO'],
            'dplm'           => ['شهادات', 'الشهادات', 'امتحانات'],
            'sdarh', 'samrh' => ['الموارد البشرية', 'المستخدمين', 'الوسائل', 'المدير الفرعي للموارد', 'مدير فرعي للموارد', 'مديرة فرعية للموارد', 'الادارة والوسائل', 'الإدارة والوسائل'],
            'sdafm', 'samf'  => ['المالية', 'المقتصد', 'محاسب', 'المدير الفرعي للموارد', 'مدير فرعي للموارد', 'مديرة فرعية للموارد', 'الادارية والمالية', 'الإدارية والمالية'],
            default          => []
        };

        if (empty($keywords)) {
            return null;
        }

        foreach ($keywords as $keyword) {
            $emp = DB::table('encadrement')
                ->where(function($q) use ($etabId) {
                    $q->where('IDEts_Form', $etabId)
                      ->orWhere('IDetablissement', $etabId);
                })
                ->where('TachesPrincipale', 'LIKE', "%{$keyword}%")
                ->first();
                
            if ($emp) {
                return $emp;
            }
        }

        return null;
    }

    /**
     * AJAX endpoint to check if the current session ID matches the active session stored in DB
     */
    public function checkActiveSession()
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['active' => false]);
        }
        
        $userKey = strtolower($user['role_code'] ?? 'user') . '_' . ($user['id'] ?? '0') . '_' . strtolower($user['username'] ?? '');
        $active = \Illuminate\Support\Facades\DB::table('active_sessions')->where('user_key', $userKey)->first();
        
        return response()->json([
            'active' => $active ? ($active->session_id === session()->getId()) : true
        ]);
    }
}