<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserPreferences;
use App\Core\AuditLogger;
use Illuminate\Http\Request;

/**
 * PreferencesController — Save per-user settings to the database.
 * Theme, branding, pagination, notifications — all persisted per user.
 */
class PreferencesController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');
        $prefs = UserPreferences::forUser($user);
        return view('admin.preferences.index', compact('user', 'prefs'));
    }

    public function save(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);

        $userId   = (int)($user['id'] ?? 0);
        $userType = match ($user['login_table'] ?? 'utilisateur') {
            'etablissement' => 'etablissement',
            'encadrement'   => 'encadrement',
            default         => 'utilisateur',
        };

        $allowed = [
            'theme', 'theme_color', 'sidebar_bg', 'accent_color',
            'compact_mode', 'animations_enabled', 'font_size', 'language',
            'institution_logo_url', 'institution_code',
            'institution_name_ar', 'institution_name_fr', 'institution_type',
            'items_per_page', 'show_welcome_banner', 'default_tab',
            'notif_email', 'notif_browser', 'notif_sound',
        ];

        $data = array_intersect_key($request->all(), array_flip($allowed));

        // Cast booleans
        foreach (['compact_mode','animations_enabled','show_welcome_banner','notif_email','notif_browser','notif_sound'] as $bool) {
            if (array_key_exists($bool, $data)) {
                $data[$bool] = filter_var($data[$bool], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Validate items_per_page
        if (isset($data['items_per_page'])) {
            $data['items_per_page'] = max(10, min(100, (int)$data['items_per_page']));
        }

        $prefs = UserPreferences::updateOrCreate(
            ['user_id' => $userId],
            array_merge($data, [
                'user_type' => $userType,
                'username'  => $user['username'] ?? '',
            ])
        );

        AuditLogger::log('UPDATE', 'user_preferences', $prefs->id);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح',
            'prefs'   => $prefs->toArray(),
        ]);
    }

    public function reset(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);

        $userId = (int)($user['id'] ?? 0);
        UserPreferences::where('user_id', $userId)->update(UserPreferences::defaults());

        return response()->json(['success' => true, 'message' => 'تمت إعادة ضبط الإعدادات إلى القيم الافتراضية']);
    }
}
