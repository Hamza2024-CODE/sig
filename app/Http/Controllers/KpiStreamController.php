<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KpiStreamController — Server-Sent Events real-time KPI dashboard stream.
 *
 * Streams live platform statistics to authorized dashboard clients using SSE.
 * Each event contains a JSON payload with current KPI metrics.
 * The stream closes automatically after MAX_EVENTS or on client disconnect.
 */
class KpiStreamController extends Controller
{
    /** Maximum events to send before closing the stream (prevents infinite connections) */
    private const MAX_EVENTS = 60;

    /** Seconds between each KPI refresh push */
    private const INTERVAL_SECONDS = 5;

    /**
     * GET /dashboard/kpi-stream
     *
     * Returns a StreamedResponse with Content-Type: text/event-stream.
     * The client side should use the browser's EventSource API.
     */
    public function stream(Request $request): StreamedResponse
    {
        $role   = session('role', '');
        $userId = session('user_id', 0);

        // Only admins, directors, and RH staff get live KPI access
        $allowedRoles = ['admin', 'directeur', 'rh', 'inspecteur'];
        if (!in_array($role, $allowedRoles)) {
            abort(403, 'غير مصرح لك بالوصول إلى البث الفوري.');
        }

        return new StreamedResponse(function () use ($role, $userId) {
            // Disable output buffering for true streaming
            if (ob_get_level() > 0) {
                ob_end_flush();
            }

            $count = 0;

            while ($count < self::MAX_EVENTS) {
                // Check if client has disconnected
                if (connection_aborted()) {
                    break;
                }

                try {
                    $kpis = $this->fetchKpis($role);

                    // SSE format: "data: <json>\n\n"
                    echo 'id: ' . ($count + 1) . "\n";
                    echo 'event: kpi-update' . "\n";
                    echo 'data: ' . json_encode($kpis) . "\n\n";

                    // Flush output buffer to send immediately
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                } catch (\Exception $e) {
                    // Send error event to client but keep stream alive
                    echo 'event: error' . "\n";
                    echo 'data: ' . json_encode(['error' => 'تعذّر تحميل البيانات مؤقتاً.']) . "\n\n";
                    flush();
                }

                $count++;
                sleep(self::INTERVAL_SECONDS);
            }

            // Signal end of stream
            echo 'event: stream-end' . "\n";
            echo 'data: ' . json_encode(['message' => 'انتهى البث الفوري. أعد تحميل الصفحة للاستمرار.']) . "\n\n";
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',    // Disable nginx buffering
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Fetch real-time KPI data scoped to the user's role.
     */
    private function fetchKpis(string $role): array
    {
        $kpis = [
            'timestamp' => now()->toIso8601String(),
            'role'      => $role,
        ];

        // ── Universal KPIs (all roles) ────────────────────────────────────
        $kpis['total_employees'] = (int) DB::selectOne(
            "SELECT COUNT(*) as cnt FROM encadrement"
        )->cnt ?? 0;

        $kpis['active_sessions'] = (int) DB::selectOne(
            "SELECT COUNT(*) as cnt FROM active_sessions WHERE last_activity > ?",
            [now()->subMinutes(30)->timestamp]
        )->cnt ?? 0;

        // ── Admin / Director specific ─────────────────────────────────────
        if (in_array($role, ['admin', 'directeur'])) {
            $kpis['total_etablissements'] = (int) DB::selectOne(
                "SELECT COUNT(*) as cnt FROM etablissement"
            )->cnt ?? 0;

            $kpis['total_users'] = (int) DB::selectOne(
                "SELECT COUNT(*) as cnt FROM utilisateur"
            )->cnt ?? 0;

            // Platform activity in last 24h from audit_logs if available
            try {
                $kpis['activity_24h'] = (int) DB::selectOne(
                    "SELECT COUNT(*) as cnt FROM audit_logs WHERE created_at > ?",
                    [now()->subHours(24)->toDateTimeString()]
                )->cnt ?? 0;
            } catch (\Exception $e) {
                $kpis['activity_24h'] = null;
            }
        }

        // ── RH specific ───────────────────────────────────────────────────
        if ($role === 'rh') {
            try {
                $kpis['pending_requests'] = (int) DB::selectOne(
                    "SELECT COUNT(*) as cnt FROM workflow_requests WHERE status = 'pending'"
                )->cnt ?? 0;
            } catch (\Exception $e) {
                $kpis['pending_requests'] = null;
            }
        }

        return $kpis;
    }
}
