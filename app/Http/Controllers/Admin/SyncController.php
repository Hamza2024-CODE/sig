<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Sync\Services\SyncService;
use App\Domains\Sync\Services\SyncLogger;
use App\Jobs\SyncTableJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

use Exception;

class SyncController extends Controller
{
    // =========================================================
    // INDEX — Main page
    // =========================================================
    public function index()
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            session(['flash_error' => 'لا تملك الصلاحيات الكافية.']);
            return $this->redirect('/dashboard');
        }

        $db = new \App\Core\LaravelDbAdapter();

        $wilayas = $db->query("SELECT IDWilayaa, Nom, Code FROM wilaya ORDER BY Code ASC")->fetchAll();
        $etabs   = $db->query("SELECT IDetablissement, Nom, Code, IDDFEP FROM etablissement ORDER BY Nom ASC")->fetchAll();

        // Fetch all MySQL tables dynamically
        $allTables = SyncService::getAllMysqlTables();

        // Exclude system/sync tables from the list
        $excludedTables = ['sync_queue', 'sync_jobs', 'sync_logs', 'migrations', 'sessions', 'jobs', 'failed_jobs'];
        $allTables = array_filter($allTables, fn($t) => !in_array($t, $excludedTables));

        return $this->render('admin/sync/index', [
            'title'     => 'مزامنة البيانات من HFSQL',
            'wilayas'   => $wilayas,
            'etabs'     => $etabs,
            'allTables' => array_values($allTables),
        ]);
    }

    // =========================================================
    // ENQUEUE — Add jobs to the queue and fire workers
    // =========================================================
    public function enqueue(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Release session lock
        try { request()->session()->save(); } catch (\Throwable $e) {}

        $tables   = request()->input('tables', []);
        $syncType = request()->input('sync_type', 'global');
        $filterId = !empty(request()->input('wilaya_id'))
                  ? (int)request()->input('wilaya_id')
                  : (!empty(request()->input('etab_id')) ? (int)request()->input('etab_id') : null);

        if (empty($tables)) {
            return response()->json(['success' => false, 'message' => 'يرجى تحديد جدول واحد على الأقل.'], 422);
        }

        $db     = new \App\Core\LaravelDbAdapter();
        $jobIds = [];

        foreach ($tables as $table) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) continue;

            $jobId = Str::uuid()->toString();

            // 1) Insert into sync_queue
            $db->prepare(
                "INSERT INTO sync_queue (job_id, table_name, sync_type, filter_id, status)
                 VALUES (?, ?, ?, ?, 'pending')"
            )->execute([$jobId, $table, $syncType, $filterId]);

            // 2) Insert into sync_jobs (progress tracker)
            SyncService::createJob($jobId, $table);

            // 3) Dispatch to Laravel Queue
            SyncTableJob::dispatch(
                jobId:    $jobId,
                table:    $table,
                syncType: $syncType !== 'global' ? $syncType : null,
                filterId: $filterId,
            );

            $jobIds[] = ['job_id' => $jobId, 'table' => $table];
        }

        return response()->json([
            'success' => true,
            'message' => count($jobIds) . ' مهمة أُضيفت للطابور.',
            'jobs'    => $jobIds,
        ]);
    }

    // =========================================================
    // STATUS — Get live job status (for polling)
    // =========================================================
    public function status(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try { request()->session()->save(); } catch (\Throwable $e) {}

        $jobId = request()->input('job_id');

        $db = new \App\Core\LaravelDbAdapter();

        if (!$jobId) {
            // Return all recent jobs (last 50)
            $jobs = $db->query(
                "SELECT j.*, q.status as queue_status
                 FROM sync_jobs j
                 LEFT JOIN sync_queue q ON j.job_id = q.job_id
                 ORDER BY j.id DESC LIMIT 50"
            )->fetchAll();
            $jobsData = [];
            foreach ($jobs as $j) {
                $j['started_at_ts'] = $j['started_at'] ? strtotime($j['started_at']) : null;
                $j['finished_at_ts'] = $j['finished_at'] ? strtotime($j['finished_at']) : null;
                $jobsData[] = $j;
            }
            return response()->json([
                'success' => true, 
                'jobs' => $jobsData,
                'server_time' => time()
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $job = $db->prepare(
            "SELECT j.*, q.status as queue_status
             FROM sync_jobs j
             LEFT JOIN sync_queue q ON j.job_id = q.job_id
             WHERE j.job_id = ?"
        );
        $job->execute([$jobId]);
        $row = $job->fetch();
        if ($row) {
            $row['started_at_ts'] = $row['started_at'] ? strtotime($row['started_at']) : null;
            $row['finished_at_ts'] = $row['finished_at'] ? strtotime($row['finished_at']) : null;
        }

        return response()->json([
            'success' => true, 
            'job' => $row ?: null,
            'server_time' => time()
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    // =========================================================
    // LOGS — Get log entries for a job
    // =========================================================
    public function logs(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try { request()->session()->save(); } catch (\Throwable $e) {}

        $jobId = request()->input('job_id');

        if (!$jobId) {
            return response()->json(['success' => false, 'message' => 'job_id مطلوب.'], 422);
        }

        $logs = SyncLogger::getLogs($jobId, 100);
        return response()->json(['success' => true, 'logs' => $logs])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    // =========================================================
    // RETRY — Re-run a failed job from last_synced_id
    // =========================================================
    public function retry(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try { request()->session()->save(); } catch (\Throwable $e) {}

        $originalJobId = request()->input('job_id');
        if (!$originalJobId) {
            return response()->json(['success' => false, 'message' => 'job_id مطلوب.'], 422);
        }

        $db = new \App\Core\LaravelDbAdapter();

        // Get original job details
        $jobStmt = $db->prepare("SELECT j.*, q.sync_type, q.filter_id FROM sync_jobs j LEFT JOIN sync_queue q ON j.job_id = q.job_id WHERE j.job_id = ?");
        $jobStmt->execute([$originalJobId]);
        $origJob = $jobStmt->fetch();

        if (!$origJob) {
            return response()->json(['success' => false, 'message' => 'المهمة غير موجودة.'], 404);
        }

        $newJobId   = Str::uuid()->toString();
        $table      = $origJob['table_name'];
        $syncType   = $origJob['sync_type'] ?? 'global';
        $filterId   = $origJob['filter_id'];
        $resumeId   = $origJob['last_synced_id']; // Start from where it stopped!

        // Insert new queue + jobs entries
        $db->prepare(
            "INSERT INTO sync_queue (job_id, table_name, sync_type, filter_id, status)
             VALUES (?, ?, ?, ?, 'pending')"
        )->execute([$newJobId, $table, $syncType, $filterId]);

        // Create job with resume ID
        $db->prepare(
            "INSERT INTO sync_jobs (job_id, table_name, status, last_synced_id, updated_at)
             VALUES (?, ?, 'pending', ?, NOW())"
        )->execute([$newJobId, $table, $resumeId]);

        // Dispatch to Laravel Queue
        SyncTableJob::dispatch(
            jobId:    $newJobId,
            table:    $table,
            syncType: $syncType !== 'global' ? $syncType : null,
            filterId: $filterId ? (int)$filterId : null,
            resumeId: $resumeId,
        );

        return response()->json([
            'success'    => true,
            'message'    => "إعادة التشغيل من السجل: $resumeId",
            'new_job_id' => $newJobId,
        ]);
    }

    // =========================================================
    // PAUSE — Pause a running or pending job
    // =========================================================
    public function pause(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try { request()->session()->save(); } catch (\Throwable $e) {}

        $jobId = request()->input('job_id');
        if (!$jobId) {
            return response()->json(['success' => false, 'message' => 'job_id مطلوب.'], 422);
        }

        $db = new \App\Core\LaravelDbAdapter();

        $db->prepare("UPDATE sync_jobs SET status = 'paused' WHERE job_id = ?")->execute([$jobId]);
        $db->prepare("UPDATE sync_queue SET status = 'paused' WHERE job_id = ?")->execute([$jobId]);

        $logger = new SyncLogger($jobId);
        $logger->warning("تم تلقي طلب إيقاف مؤقت من المستخدم.");

        return response()->json([
            'success' => true,
            'message' => 'تم إيقاف المزامنة مؤقتاً.'
        ]);
    }

    // =========================================================
    // QUEUE STATUS — All queue jobs for dashboard
    // =========================================================
    public function queue(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try { request()->session()->save(); } catch (\Throwable $e) {}

        $db = new \App\Core\LaravelDbAdapter();

        // Auto-recover stuck running jobs (no updates in the last 5 minutes)
        try {
            $stuckJobs = $db->query(
                "SELECT job_id FROM sync_jobs
                 WHERE status = 'running' AND updated_at < NOW() - INTERVAL 5 MINUTE"
            )->fetchAll(\PDO::FETCH_COLUMN);

            if (!empty($stuckJobs)) {
                $placeholders = implode(',', array_fill(0, count($stuckJobs), '?'));

                $stmt1 = $db->prepare("UPDATE sync_jobs SET status = 'paused' WHERE job_id IN ($placeholders)");
                $stmt1->execute($stuckJobs);

                $stmt2 = $db->prepare("UPDATE sync_queue SET status = 'paused' WHERE job_id IN ($placeholders)");
                $stmt2->execute($stuckJobs);

                foreach ($stuckJobs as $jobId) {
                    $logger = new SyncLogger($jobId);
                    $logger->warning("تم الكشف عن توقف العملية تلقائياً (لم يتم تلقي تحديثات لأكثر من 5 دقائق). تم تحويل الحالة إلى متوقف.");
                }
            }
        } catch (\Throwable $e) {
            // Fail silently
        }

        // Stats
        $stats = $db->query(
            "SELECT status, COUNT(*) as cnt FROM sync_jobs GROUP BY status"
        )->fetchAll();

        // Recent jobs (last 100)
        $jobs = $db->query(
            "SELECT j.job_id, j.table_name, j.status, j.total_rows, j.synced_rows,
                    j.last_synced_id, j.error_message, j.started_at, j.finished_at, j.updated_at,
                    q.sync_type, q.filter_id
             FROM sync_jobs j
             LEFT JOIN sync_queue q ON j.job_id = q.job_id
             ORDER BY j.id DESC LIMIT 100"
        )->fetchAll();

        $jobsData = [];
        foreach ($jobs as $j) {
            $j['started_at_ts'] = $j['started_at'] ? strtotime($j['started_at']) : null;
            $j['finished_at_ts'] = $j['finished_at'] ? strtotime($j['finished_at']) : null;
            $jobsData[] = $j;
        }

        return response()->json([
            'success' => true,
            'stats'   => $stats,
            'jobs'    => $jobsData,
            'server_time' => time(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    // =========================================================
    // CLEAR — Delete completed/failed jobs
    // ✅ إصلاح: sync_queue لا تملك عمود finished_at — نربطها بـ sync_jobs
    // =========================================================
    public function clear(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try { request()->session()->save(); } catch (\Throwable $e) {}

        $db = new \App\Core\LaravelDbAdapter();

        // 1) حذف سجلات log المرتبطة بالمهام القديمة أولاً
        $db->exec(
            "DELETE FROM sync_logs WHERE job_id IN
             (SELECT job_id FROM sync_jobs WHERE status IN ('done','failed') AND finished_at < NOW() - INTERVAL 24 HOUR)"
        );

        // 2) حذف من sync_queue عبر الربط بـ sync_jobs (لأن sync_queue لا تملك finished_at)
        $db->exec(
            "DELETE sq FROM sync_queue sq
             INNER JOIN sync_jobs sj ON sq.job_id = sj.job_id
             WHERE sj.status IN ('done','failed') AND sj.finished_at < NOW() - INTERVAL 24 HOUR"
        );

        // 3) حذف من sync_jobs
        $db->exec(
            "DELETE FROM sync_jobs WHERE status IN ('done','failed') AND finished_at < NOW() - INTERVAL 24 HOUR"
        );

        return response()->json(['success' => true, 'message' => 'تم تنظيف الطابور.']);
    }

    // =========================================================
    // COMPARE — Data reconciliation and auditing dashboard
    // =========================================================
    public function compare()
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            session(['flash_error' => 'لا تملك الصلاحيات الكافية.']);
            return $this->redirect('/dashboard');
        }

        $db = new \App\Core\LaravelDbAdapter();

        // Get all MySQL tables
        $allTables = SyncService::getAllMysqlTables();
        $excludedTables = ['sync_queue', 'sync_jobs', 'sync_logs', 'migrations', 'sessions', 'jobs', 'failed_jobs', 'sync_reports'];
        $allTables = array_filter($allTables, fn($t) => !in_array($t, $excludedTables));
        $allTables = array_values($allTables);

        // Fetch existing cached reports
        $cachedReports = $db->query("SELECT * FROM sync_reports")->fetchAll();
        $reportsMap = [];
        foreach ($cachedReports as $r) {
            $reportsMap[$r['table_name']] = $r;
        }

        // Fetch latest sync jobs to fallback if reports are empty
        $latestJobs = [];
        try {
            $jobs = $db->query("
                SELECT j1.table_name, j1.status, j1.total_rows, j1.synced_rows, j1.finished_at, j1.updated_at
                FROM sync_jobs j1
                WHERE j1.id = (SELECT MAX(id) FROM sync_jobs j2 WHERE j2.table_name = j1.table_name)
            ")->fetchAll();
            foreach ($jobs as $j) {
                $latestJobs[$j['table_name']] = $j;
            }
        } catch (\Throwable $e) {}

        // Fetch InnoDB estimated row counts from information_schema
        $estimates = [];
        try {
            $estRows = $db->query("
                SELECT TABLE_NAME, TABLE_ROWS
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
            ")->fetchAll();
            foreach ($estRows as $r) {
                $estimates[$r['TABLE_NAME']] = (int)$r['TABLE_ROWS'];
            }
        } catch (\Throwable $e) {}

        // Merge latest jobs and estimates into reportsMap where missing
        foreach ($allTables as $table) {
            if (!isset($reportsMap[$table])) {
                if (isset($latestJobs[$table])) {
                    $job = $latestJobs[$table];
                    $status = 'UNKNOWN';
                    if ($job['status'] === 'done') {
                        $status = ((int)$job['synced_rows'] === (int)$job['total_rows']) ? 'SYNCED' : 'OUTDATED';
                    } elseif (in_array($job['status'], ['running', 'paused'])) {
                        $status = 'OUTDATED';
                    }
                    $reportsMap[$table] = [
                        'table_name'  => $table,
                        'mysql_count' => (int)$job['synced_rows'],
                        'hfsql_count' => (int)$job['total_rows'],
                        'status'      => $status,
                        'updated_at'  => $job['finished_at'] ?? $job['updated_at']
                    ];
                } elseif (isset($estimates[$table]) && $estimates[$table] > 0) {
                    $reportsMap[$table] = [
                        'table_name'  => $table,
                        'mysql_count' => $estimates[$table],
                        'hfsql_count' => -1,
                        'status'      => 'UNKNOWN',
                        'updated_at'  => null
                    ];
                }
            }
        }

        return $this->render('admin/sync/compare', [
            'title'      => 'تدقيق ومقارنة البيانات (MySQL ↔ HFSQL)',
            'allTables'  => $allTables,
            'reportsMap' => $reportsMap,
        ]);
    }

    // =========================================================
    // COMPARE COUNTS — API batch checker (10 tables per request)
    // =========================================================
    public function compareCounts(): JsonResponse
    {
        if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try { request()->session()->save(); } catch (\Throwable $e) {}

        $tables = request()->input('tables', []);
        $force  = request()->input('force') === '1';

        if (empty($tables) || !is_array($tables)) {
            return response()->json(['success' => false, 'message' => 'No tables provided.'], 422);
        }

        try {
            $syncService = new SyncService();
            $results = [];

            foreach ($tables as $table) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) continue;
                $counts = $syncService->getTableCounts($table, $force);
                $results[] = [
                    'table'  => $table,
                    'mysql'  => $counts['mysql'],
                    'hfsql'  => $counts['hfsql'],
                    'status' => $counts['status']
                ];
            }

            return response()->json(['success' => true, 'results' => $results]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    // NOTE: startQueueWorker() و runBackground() و generateUuid()
    // تم حذفها بالكامل. المهام تُطلق الآن عبر SyncTableJob::dispatch()
    // مما يلغي الحاجة لـ popen/shell_exec تماماً.
    // يُمكن تشغيل الـ worker بشكل دائم بالأمر:
    //   php artisan queue:work --queue=sync --timeout=3600 --tries=3
}
