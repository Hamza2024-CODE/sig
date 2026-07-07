<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    /**
     * Show the user profile page.
     */
    public function index()
    {
        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $userId = $userSession['id'];
        $loginTable = $userSession['login_table'] ?? null;
        $roleCode = strtolower($userSession['role_code'] ?? '');

        // Fallback deduction of login_table if missing
        if (!$loginTable) {
            if (isset($userSession['permissions'])) {
                $loginTable = 'utilisateur';
            } elseif (isset($userSession['nature_id'])) {
                $loginTable = 'etablissement';
            } elseif ($roleCode === 'employee' || $roleCode === 'formateur' || isset($userSession['sit_nom'])) {
                $loginTable = 'encadrement';
            } else {
                $loginTable = 'utilisateur';
            }
        }

        $userData = [];

        if ($loginTable === 'encadrement') {
            $raw = DB::selectOne("SELECT * FROM encadrement WHERE IDEncadrement = ?", [$userId]);
            if ($raw) {
                $raw = (array)$raw;
                $userData = [
                    'id' => $raw['IDEncadrement'],
                    'nom_complet' => ($raw['Nom'] ?? '') . ' ' . ($raw['Prenom'] ?? ''),
                    'username' => $raw['nin'] ?? '',
                    'email' => $raw['Email'] ?? '',
                    'role_ar' => $userSession['role_ar'] ?? 'موظف / مُكوّن',
                    'created_at' => !empty($raw['create_time']) ? $raw['create_time'] : '2026-01-01',
                    'avatar' => $raw['photo'] ?? $userSession['avatar'] ?? null,
                    'login_table' => 'encadrement'
                ];
            }
        } elseif ($loginTable === 'etablissement') {
            $raw = DB::selectOne("SELECT * FROM etablissement WHERE IDetablissement = ?", [$userId]);
            if ($raw) {
                $raw = (array)$raw;
                $userData = [
                    'id' => $raw['IDetablissement'],
                    'nom_complet' => $raw['Nom'] ?? '',
                    'username' => $raw['nomUser'] ?? '',
                    'email' => $raw['Email'] ?? '',
                    'role_ar' => $userSession['role_ar'] ?? 'مؤسسة / مديرية',
                    'created_at' => !empty($raw['DateDecret']) ? $raw['DateDecret'] : '2026-01-01',
                    'avatar' => $raw['avatar'] ?? $userSession['avatar'] ?? null,
                    'login_table' => 'etablissement'
                ];
            }
        } else {
            $raw = DB::selectOne("SELECT * FROM utilisateur WHERE IDUtilisateur = ?", [$userId]);
            if ($raw) {
                $raw = (array)$raw;
                $userData = [
                    'id' => $raw['IDUtilisateur'],
                    'nom_complet' => $raw['Nom'] ?? '',
                    'username' => $raw['NomUser'] ?? '',
                    'email' => $userSession['email'] ?? 'contact@sgfep.dz',
                    'role_ar' => $userSession['role_ar'] ?? 'مستخدم المنصة',
                    'created_at' => '2026-01-01',
                    'avatar' => $raw['avatar'] ?? $userSession['avatar'] ?? null,
                    'login_table' => 'utilisateur'
                ];
            }
        }

        // Fallback userData
        if (empty($userData)) {
            $userData = [
                'id' => $userId,
                'nom_complet' => $userSession['nom_complet'] ?? 'مستخدم المنصة',
                'username' => $userSession['username'] ?? 'user',
                'email' => 'contact@sgfep.dz',
                'role_ar' => $userSession['role_ar'] ?? 'مستخدم',
                'created_at' => '2026-01-01',
                'avatar' => $userSession['avatar'] ?? null,
                'login_table' => $loginTable ?? 'utilisateur'
            ];
        }

        return view('dashboard.profile', [
            'title' => 'الملف الشخصي للمستخدم / Profil Utilisateur',
            'user' => $userData,
            'showGlobalFilter' => false
        ]);
    }

    /**
     * Update the user profile.
     */
    public function update(Request $request)
    {
        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $userId = $userSession['id'];
        $loginTable = $userSession['login_table'] ?? null;
        $roleCode = strtolower($userSession['role_code'] ?? '');

        // Fallback deduction of login_table if missing
        if (!$loginTable) {
            if (isset($userSession['permissions'])) {
                $loginTable = 'utilisateur';
            } elseif (isset($userSession['nature_id'])) {
                $loginTable = 'etablissement';
            } elseif ($roleCode === 'employee' || $roleCode === 'formateur' || isset($userSession['sit_nom'])) {
                $loginTable = 'encadrement';
            } else {
                $loginTable = 'utilisateur';
            }
        }

        $username = trim($request->input('username', ''));
        $nomComplet = trim($request->input('nom_complet', ''));
        $email = trim($request->input('email', ''));
        $newPass = $request->input('new_password', '');
        $confirmPass = $request->input('confirm_password', '');

        if (empty($username) || empty($nomComplet)) {
            return redirect()->back()->with('error', 'يرجى ملء جميع الحقول الإلزامية.');
        }

        // Password matching check
        if (!empty($newPass)) {
            if ($newPass !== $confirmPass) {
                return redirect()->back()->with('error', 'تأكيد كلمة المرور غير متطابق مع كلمة المرور الجديدة.');
            }
            $validationResult = \App\Core\PasswordPolicy::validate($newPass, $userSession);
            if ($validationResult !== true) {
                return redirect()->back()->with('error', $validationResult);
            }
        }

        // Handle Avatar File Upload via Laravel Request (secure MIME validation)
        $avatarPath = null;
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $file = $request->file('avatar');

            $validation = \App\Services\FileValidator::validate($file, 'image');
            if (!$validation['ok']) {
                return redirect()->back()->with('error', $validation['error']);
            }

            $fileExtension = strtolower($file->getClientOriginalExtension());
            $newFileName   = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;

            $uploadDir = public_path('uploads/avatars');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $file->move($uploadDir, $newFileName);
            $avatarPath = 'uploads/avatars/' . $newFileName;
        }

        // Update database based on login_table
        try {
            if ($loginTable === 'encadrement') {
                DB::update("UPDATE encadrement SET Email = ? WHERE IDEncadrement = ?", [$email, $userId]);
                if ($avatarPath !== null) {
                    DB::update("UPDATE encadrement SET photo = ? WHERE IDEncadrement = ?", [$avatarPath, $userId]);
                }
                if (!empty($newPass)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                    DB::update("UPDATE encadrement SET MotDePass = ? WHERE IDEncadrement = ?", [$newHash, $userId]);
                }
            } elseif ($loginTable === 'etablissement') {
                DB::update("UPDATE etablissement SET Nom = ?, nomUser = ?, Email = ? WHERE IDetablissement = ?", [$nomComplet, $username, $email, $userId]);
                if ($avatarPath !== null) {
                    DB::update("UPDATE etablissement SET avatar = ? WHERE IDetablissement = ?", [$avatarPath, $userId]);
                }
                if (!empty($newPass)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                    DB::update("UPDATE etablissement SET MotDePass = ? WHERE IDetablissement = ?", [$newHash, $userId]);
                }
            } else {
                DB::update("UPDATE utilisateur SET Nom = ?, NomUser = ? WHERE IDUtilisateur = ?", [$nomComplet, $username, $userId]);
                if ($avatarPath !== null) {
                    DB::update("UPDATE utilisateur SET avatar = ? WHERE IDUtilisateur = ?", [$avatarPath, $userId]);
                }
                if (!empty($newPass)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                    DB::update("UPDATE utilisateur SET MotPass = ? WHERE IDUtilisateur = ?", [$newHash, $userId]);
                }
            }

            // Sync Laravel session
            $userSession['username'] = $username;
            $userSession['nom_complet'] = $nomComplet;
            if ($avatarPath !== null) {
                $userSession['avatar'] = $avatarPath;
            }
            session(['user' => $userSession]);

            // Sync PHP native session
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['user'] = $userSession;
            }

            return redirect()->back()->with('success', 'تم تحديث ملفك الشخصي بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء حفظ التحديثات: ' . $e->getMessage());
        }
    }
}
