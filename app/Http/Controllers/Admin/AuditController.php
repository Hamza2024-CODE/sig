<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    /**
     * Display a listing of system audit logs.
     */
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if (!in_array($role_code, ['admin', 'ministre', 'secretaire_general'])) {
            session(['flash_error' => 'غير مصرح لك بالولوج إلى هذه الصفحة.']);
            return redirect()->route('dashboard');
        }

        $query = DB::table('audit_logs as al')
            ->leftJoin('utilisateur as u', 'al.user_id', '=', 'u.IDUtilisateur')
            ->select('al.*', 'u.Nom as nom_complet', 'u.NomUser as username');

        // Apply filters
        if ($search = $request->query('search')) {
            $query->where(function($q) use ($search) {
                $q->where('u.Nom', 'like', "%{$search}%")
                  ->orWhere('u.NomUser', 'like', "%{$search}%");
            });
        }

        if ($action = $request->query('action')) {
            $query->where('al.action', '=', $action);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('al.created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('al.created_at', '<=', $dateTo);
        }

        // Paginate logs
        $perPage = 20;
        $paginator = $query->orderByDesc('al.created_at')->paginate($perPage);

        $logs = [];
        foreach ($paginator->items() as $item) {
            $details = json_decode($item->details, true) ?: [];
            
            // Format single log record
            $logs[] = [
                'id' => $item->id,
                'nom_complet' => $item->nom_complet ?? $details['nom_complet'] ?? '—',
                'username' => $item->username ?? $details['username'] ?? '',
                'action' => $item->action,
                'table_name' => $details['table_name'] ?? '—',
                'record_id' => $details['record_id'] ?? null,
                'iplocal' => $item->ip_address,
                'ip_address' => $item->ip_address,
                'created_at' => $item->created_at,
                'details' => $item->details,
                'old_values' => $details['old_data'] ?? null,
                'new_values' => $details['new_data'] ?? null
            ];
        }

        // Stats KPIs
        $totalLogs = DB::table('audit_logs')->count();
        $todayLogs = DB::table('audit_logs')->whereDate('created_at', today())->count();
        $loginsCount = DB::table('audit_logs')->where('action', 'LOGIN')->count();
        $sensitiveCount = DB::table('audit_logs')->whereIn('action', ['DELETE', 'SYNC'])->count();

        return view('admin.audit.index', [
            'audit_logs' => $logs,
            'total_logs' => $paginator->total(),
            'stats' => [
                'today' => $todayLogs,
                'logins' => $loginsCount,
                'sensitive' => $sensitiveCount,
            ],
            'pagination' => [
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'filter_actions' => ['LOGIN', 'CREATE', 'UPDATE', 'DELETE', 'EXPORT', 'PRINT', 'SYNC']
        ]);
    }

    /**
     * Export audit logs to Excel-compatible CSV.
     */
    public function export(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role_code = strtolower($user['role_code'] ?? '');
        if (!in_array($role_code, ['admin', 'ministre', 'secretaire_general'])) {
            session(['flash_error' => 'غير مصرح لك بالولوج إلى هذه الصفحة.']);
            return redirect()->route('dashboard');
        }

        $query = DB::table('audit_logs as al')
            ->leftJoin('utilisateur as u', 'al.user_id', '=', 'u.IDUtilisateur')
            ->select('al.*', 'u.Nom as nom_complet', 'u.NomUser as username');

        // Apply filters
        if ($search = $request->query('search')) {
            $query->where(function($q) use ($search) {
                $q->where('u.Nom', 'like', "%{$search}%")
                  ->orWhere('u.NomUser', 'like', "%{$search}%");
            });
        }
        if ($action = $request->query('action')) {
            $query->where('al.action', '=', $action);
        }
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('al.created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('al.created_at', '<=', $dateTo);
        }

        $logs = $query->orderByDesc('al.created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            // Add BOM for UTF-8 compatibility (especially for Excel reading Arabic names)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'اسم المستخدم', 'الاسم الكامل', 'العملية', 'الجدول المستهدف', 'المعرف المستهدف', 'عنوان IP', 'التاريخ والوقت']);
            
            foreach ($logs as $log) {
                $details = json_decode($log->details, true) ?: [];
                fputcsv($file, [
                    $log->id,
                    $log->username ?? $details['username'] ?? '',
                    $log->nom_complet ?? $details['nom_complet'] ?? '—',
                    $log->action,
                    $details['table_name'] ?? '—',
                    $details['record_id'] ?? '',
                    $log->ip_address,
                    $log->created_at
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
