<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ApiClient;

class ApiCenterController extends Controller
{
    /**
     * Display the API Control Center (both user key and external clients).
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

        // Current user API key (derived)
        $api_key = 'sgfep_live_' . substr(hash('sha256', $user['username']), 0, 32);

        // Fetch external API clients
        $clients = ApiClient::orderBy('id', 'DESC')->get();

        // Get some stats
        $stats = [
            'total_users' => DB::table('utilisateur')->count(),
            'active_clients' => ApiClient::where('is_active', true)->count(),
            'inactive_clients' => ApiClient::where('is_active', false)->count()
        ];

        return view('admin.api-center.index', [
            'api_key' => $api_key,
            'clients' => $clients,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a new external API client and generate a secure API key.
     */
    public function store(Request $request)
    {
        $user = session('user');
        if (!$user || strtolower($user['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'client_name' => 'required|string|max:100|min:3',
            'allowed_ips' => 'nullable|string',
            'allowed_endpoints' => 'nullable|array'
        ]);

        // Generate a cryptographically secure random API key for the external platform
        $plainKey = 'sgfep_ext_' . bin2hex(random_bytes(20));
        $hashedKey = hash('sha256', $plainKey);

        try {
            $client = ApiClient::create([
                'client_name' => $request->input('client_name'),
                'api_key' => $hashedKey,
                'is_active' => true,
                'allowed_ips' => $request->input('allowed_ips'),
                'allowed_endpoints' => $request->input('allowed_endpoints')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء منصة الربط بنجاح.',
                'plain_key' => $plainKey, // Returned ONLY ONCE
                'client' => $client
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إنشاء المنصة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an external API client details.
     */
    public function update(Request $request, $id)
    {
        $user = session('user');
        if (!$user || strtolower($user['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'client_name' => 'required|string|max:100|min:3',
            'allowed_ips' => 'nullable|string',
            'allowed_endpoints' => 'nullable|array'
        ]);

        $client = ApiClient::find($id);
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'المنصة غير موجودة.'], 404);
        }

        try {
            $client->update([
                'client_name' => $request->input('client_name'),
                'allowed_ips' => $request->input('allowed_ips'),
                'allowed_endpoints' => $request->input('allowed_endpoints')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تحديث البيانات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle client status (is_active).
     */
    public function toggle(Request $request, $id)
    {
        $user = session('user');
        if (!$user || strtolower($user['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $client = ApiClient::find($id);
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'المنصة غير موجودة.'], 404);
        }

        try {
            $client->is_active = !$client->is_active;
            $client->save();

            return response()->json([
                'success' => true,
                'message' => $client->is_active ? 'تم تنشيط المنصة بنجاح.' : 'تم حظر المنصة بنجاح.',
                'is_active' => $client->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تغيير الحالة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke and delete the API Client.
     */
    public function destroy($id)
    {
        $user = session('user');
        if (!$user || strtolower($user['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $client = ApiClient::find($id);
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'المنصة غير موجودة.'], 404);
        }

        try {
            $client->delete();
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنصة وسحب صلاحيات المفتاح بنجاح.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل حذف المنصة: ' . $e->getMessage()
            ], 500);
        }
    }
}
