<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class DashboardBuilderController extends Controller
{
    /**
     * Display the dynamic dashboard builder workspace.
     */
    public function index(Request $request)
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

        // Fetch users from database
        $users = DB::select("
            SELECT u.IDUtilisateur as id, u.NomUser as username, u.Nom as nom_complet,
                   CASE 
                       WHEN u.admin = 1 THEN 'مدير النظام'
                       WHEN u.IDNature = 4 THEN 'DFEP ولائي'
                       WHEN u.IDNature = 2 THEN 'مدير مؤسسة'
                       WHEN u.IDNature = 3 THEN 'مكوّن'
                       WHEN u.IDNature = 5 THEN 'متربص'
                       ELSE 'حساب خاص'
                   END as role_ar
            FROM utilisateur u
            ORDER BY u.IDUtilisateur DESC
        ");

        $users = array_map(fn($item) => (array)$item, $users);

        // Define available widget templates
        $availableWidgets = [
            [
                'type' => 'absences_summary',
                'name' => 'جدول غيابات المتربصين',
                'default_w' => 8,
                'default_h' => 2,
                'icon' => 'fa-clipboard-user',
                'description' => 'يعرض قائمة بآخر غيابات المتربصين المسجلة في المؤسسة مع تفاصيل الحضور.'
            ],
            [
                'type' => 'trainee_stats',
                'name' => 'إحصائيات تعداد المتربصين الكلية',
                'default_w' => 4,
                'default_h' => 2,
                'icon' => 'fa-users',
                'description' => 'يعرض بطاقات تعداد المتربصين المستمرين والتوزيع حسب الجنس ونسب الحضور.'
            ],
            [
                'type' => 'grades_overview',
                'name' => 'موجز نتائج الامتحانات والمداولات',
                'default_w' => 6,
                'default_h' => 2,
                'icon' => 'fa-chart-line',
                'description' => 'يعرض إحصائيات عامة حول عدد مقاييس الامتحانات المكتملة ومتوسط أداء الفئات.'
            ]
        ];

        return $this->render('admin/builder/index', [
            'title' => 'منشئ لوحات التحكم الديناميكي | Dashboard Builder',
            'users' => $users,
            'availableWidgets' => $availableWidgets
        ]);
    }

    /**
     * Get layout and widgets configuration for a user portal via AJAX.
     */
    public function getPortalConfig($userId, $portalNum)
    {
        $userId = (int)$userId;
        $portalNum = (int)$portalNum;

        try {
            $dashboard = DB::table('dashboards')
                ->where('user_id', $userId)
                ->where('portal_number', $portalNum)
                ->first();

            if (!$dashboard) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'layout_type' => 'grid-12-cols',
                    'widgets' => []
                ]);
            }

            $layoutConfig = json_decode($dashboard->layout_config, true) ?? [];
            $layoutType = $layoutConfig['layout_type'] ?? 'grid-12-cols';

            $widgets = DB::table('dashboard_widgets')
                ->where('dashboard_id', $dashboard->id)
                ->orderBy('grid_y')
                ->orderBy('grid_x')
                ->get();

            $formattedWidgets = [];
            foreach ($widgets as $w) {
                $formattedWidgets[] = [
                    'id' => $w->id,
                    'type' => $w->type,
                    'title' => $w->title,
                    'grid_x' => (int)$w->grid_x,
                    'grid_y' => (int)$w->grid_y,
                    'grid_w' => (int)$w->grid_w,
                    'grid_h' => (int)$w->grid_h,
                    'config' => json_decode($w->config, true) ?? []
                ];
            }

            return response()->json([
                'success' => true,
                'exists' => true,
                'layout_type' => $layoutType,
                'widgets' => $formattedWidgets
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Save portal configuration and widgets in an atomic database transaction.
     */
    public function savePortalConfig(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'portal_number' => 'required|integer|min:1|max:11',
            'layout_type' => 'required|string',
            'widgets' => 'nullable|array'
        ]);

        $userId = (int)$request->input('user_id');
        $portalNum = (int)$request->input('portal_number');
        $layoutType = $request->input('layout_type');
        $widgetsInput = $request->input('widgets') ?? [];

        try {
            DB::transaction(function () use ($userId, $portalNum, $layoutType, $widgetsInput) {
                // Find or create dashboard
                $dashboard = DB::table('dashboards')
                    ->where('user_id', $userId)
                    ->where('portal_number', $portalNum)
                    ->first();

                $layoutConfigJson = json_encode([
                    'layout_type' => $layoutType,
                    'updated_by_admin_id' => session('user')['id'] ?? null
                ]);

                if ($dashboard) {
                    DB::table('dashboards')
                        ->where('id', $dashboard->id)
                        ->update([
                            'layout_config' => $layoutConfigJson,
                            'updated_at' => now()
                        ]);
                    $dashboardId = $dashboard->id;
                } else {
                    $dashboardId = DB::table('dashboards')->insertGetId([
                        'user_id' => $userId,
                        'portal_number' => $portalNum,
                        'layout_config' => $layoutConfigJson,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Delete existing widgets
                DB::table('dashboard_widgets')
                    ->where('dashboard_id', $dashboardId)
                    ->delete();

                // Insert new widgets
                foreach ($widgetsInput as $w) {
                    DB::table('dashboard_widgets')->insert([
                        'dashboard_id' => $dashboardId,
                        'type' => $w['type'],
                        'title' => $w['title'] ?? null,
                        'grid_x' => (int)($w['grid_x'] ?? 0),
                        'grid_y' => (int)($w['grid_y'] ?? 0),
                        'grid_w' => (int)($w['grid_w'] ?? 4),
                        'grid_h' => (int)($w['grid_h'] ?? 2),
                        'config' => json_encode($w['config'] ?? []),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ تخطيط لوحة التحكم والمكونات بنجاح!'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر حفظ البيانات: ' . $e->getMessage()
            ], 500);
        }
    }
}
