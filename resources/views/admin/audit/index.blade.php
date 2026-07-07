@extends('layouts.main')
@section('title', 'سجل العمليات والدخول — SGFEP')
@section('content')
<?php
$audit_logs = $audit_logs ?? [];
$total_logs  = $total_logs  ?? count($audit_logs);
$stats       = $stats       ?? [];
$filter_actions = $filter_actions ?? ['LOGIN','CREATE','UPDATE','DELETE','EXPORT','PRINT','SYNC'];
?>
<div class="animate__animated animate__fadeIn">

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family:'Cairo';">
                <i class="fa-solid fa-list-check text-primary me-2"></i>
                سجل العمليات والدخول الأخير للنظام
            </h3>
            <p class="text-muted small mb-0">مراقبة شاملة لكافة العمليات والأحداث الأمنية — Audit Trail Complet</p>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
            <a href="/dashboard" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة
            </a>
            <button onclick="refreshLogs()" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-rotate me-1"></i> تحديث
            </button>
            <a href="/dashboard/audit-logs/export" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:linear-gradient(135deg,#006233,#10b981);border:none;">
                <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
            </a>
        </div>
    </div>

    <!-- ── KPI Row ──────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <?php
        $kpis = [
            ['label' => 'إجمالي العمليات', 'val' => number_format($total_logs), 'icon' => 'fa-database', 'color' => '#006233'],
            ['label' => 'عمليات اليوم',    'val' => number_format($stats['today'] ?? 0), 'icon' => 'fa-calendar-day', 'color' => '#3b82f6'],
            ['label' => 'دخول ناجح',       'val' => number_format($stats['logins'] ?? 0), 'icon' => 'fa-right-to-bracket', 'color' => '#10b981'],
            ['label' => 'عمليات حساسة',    'val' => number_format($stats['sensitive'] ?? 0), 'icon' => 'fa-triangle-exclamation', 'color' => '#f59e0b'],
        ];
        foreach ($kpis as $k): ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:48px;height:48px;background:<?= $k['color'] ?>22;color:<?= $k['color'] ?>;">
                    <i class="fa-solid <?= $k['icon'] ?> fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 text-dark"><?= $k['val'] ?></div>
                    <div class="text-muted small fw-bold"><?= $k['label'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Filters ──────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <form method="GET" action="/dashboard/audit-logs" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">البحث بالمستخدم</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-search text-primary"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="الاسم أو اسم المستخدم..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">نوع العملية</label>
                <select name="action" class="form-select">
                    <option value="">الكل</option>
                    <?php foreach ($filter_actions as $act): ?>
                    <option value="<?= $act ?>" <?= ($_GET['action'] ?? '') === $act ? 'selected' : '' ?>><?= $act ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">من تاريخ</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">إلى تاريخ</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold flex-grow-1" style="background:linear-gradient(135deg,#006233,#10b981);border:none;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="/dashboard/audit-logs" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- ── Table ────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0" style="font-family:'Cairo';">
                <i class="fa-solid fa-table-list text-primary me-2"></i>
                سجل العمليات التفصيلي
            </h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-bold" style="font-size:0.8rem;">
                <?= number_format($total_logs) ?> سجل
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-family:'Outfit',sans-serif;">
                    <thead class="table-light text-muted small fw-bold" style="font-family:'Cairo';">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="py-3">اسم الموظف / المستخدم</th>
                            <th class="py-3">العملية</th>
                            <th class="py-3">الجدول المستهدف</th>
                            <th class="py-3">عنوان IP</th>
                            <th class="py-3">التاريخ والوقت</th>
                            <th class="py-3">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($audit_logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fa-solid fa-inbox text-muted" style="font-size:2.5rem;opacity:0.3;"></i>
                                <p class="text-muted mt-3 mb-0 fw-bold" style="font-family:'Cairo';">لا توجد سجلات عمليات بعد</p>
                                <p class="text-muted small">ستظهر هنا جميع عمليات النظام فور تنفيذها</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($audit_logs as $i => $log): ?>
                        <?php
                            $action = $log['action'] ?? 'LOGIN';
                            $actionColors = [
                                'LOGIN'  => ['bg' => 'success', 'icon' => 'fa-right-to-bracket'],
                                'CREATE' => ['bg' => 'primary', 'icon' => 'fa-plus'],
                                'UPDATE' => ['bg' => 'warning', 'icon' => 'fa-pen'],
                                'DELETE' => ['bg' => 'danger',  'icon' => 'fa-trash'],
                                'EXPORT' => ['bg' => 'info',    'icon' => 'fa-file-export'],
                                'PRINT'  => ['bg' => 'secondary','icon' => 'fa-print'],
                                'SYNC'   => ['bg' => 'purple',  'icon' => 'fa-rotate'],
                            ];
                            $ac = $actionColors[$action] ?? ['bg' => 'secondary', 'icon' => 'fa-circle'];
                        ?>
                        <tr class="<?= ($i % 2 === 0) ? '' : 'table-light' ?>">
                            <td class="px-4 text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:34px;height:34px;background:linear-gradient(135deg,#006233,#10b981);color:#fff;font-family:'Cairo';font-weight:700;font-size:0.8rem;">
                                        <?= mb_strtoupper(mb_substr($log['nom_complet'] ?? $log['username'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($log['nom_complet'] ?? '—') ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($log['username'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $ac['bg'] ?>-subtle text-<?= $ac['bg'] ?> border border-<?= $ac['bg'] ?>-subtle rounded-pill px-3 py-1 fw-bold small">
                                    <i class="fa-solid <?= $ac['icon'] ?> me-1"></i><?= $action ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($log['table_name'] ?? '—') ?></span>
                                <?php if (!empty($log['record_id'])): ?>
                                <span class="text-muted ms-1" style="font-size:0.75rem;">#<?= $log['record_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted small" style="font-family:'Outfit';">
                                    <i class="fa-solid fa-network-wired text-primary me-1" style="font-size:0.7rem;"></i>
                                    <?= htmlspecialchars($log['iplocal'] ?? $log['ip_address'] ?? '127.0.0.1') ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small" style="font-family:'Outfit';">
                                    <i class="fa-regular fa-clock text-muted me-1" style="font-size:0.7rem;"></i>
                                    <?= htmlspecialchars(substr($log['created_at'] ?? $log['Date'] ?? '', 0, 16)) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($log['details'] ?? $log['old_values'] ?? '')): ?>
                                <button class="btn btn-sm btn-light rounded-pill px-3"
                                        onclick="showLogDetail(<?= htmlspecialchars(json_encode($log), ENT_QUOTES) ?>)"
                                        title="عرض التفاصيل">
                                    <i class="fa-solid fa-eye text-primary"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <?php if (!empty($pagination)): ?>
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted small fw-bold">
                عرض <?= $pagination['from'] ?? 1 ?> — <?= $pagination['to'] ?? count($audit_logs) ?>
                من <?= number_format($total_logs) ?> سجل
            </span>
            <nav aria-label="Pagination">
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= ($pagination['last_page'] ?? 1); $p++): ?>
                    <li class="page-item <?= $p === ($pagination['current_page'] ?? 1) ? 'active' : '' ?>">
                        <a class="page-link rounded-3 mx-1" href="?page=<?= $p ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-family:'Cairo';">
                    <i class="fa-solid fa-circle-info text-primary me-2"></i>
                    تفاصيل العملية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailBody">
                <pre class="bg-light rounded-3 p-3 small" id="logDetailContent" style="font-family:'Outfit';direction:ltr;text-align:left;overflow:auto;max-height:400px;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetail(log) {
    document.getElementById('logDetailContent').textContent = JSON.stringify(log, null, 2);
    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
}
function refreshLogs() {
    location.reload();
}
</script>
@endsection
