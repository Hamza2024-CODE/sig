@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $allTables
 * @var array  $reportsMap
 */
$allTablesJson = json_encode($allTables, JSON_UNESCAPED_UNICODE);
?>

<style>
:root {
    --sync-primary:   #4f46e5;
    --sync-success:   #10b981;
    --sync-danger:    #ef4444;
    --sync-warning:   #f59e0b;
    --sync-muted:     #6b7280;
    --card-radius:    16px;
}

/* Status Colors and Badges */
.badge-synced     { background: #e6fcf5; color: #0ca678; border: 1px solid #c3fae8; }
.badge-empty      { background: #fff9db; color: #f08c00; border: 1px solid #ffe3e3; }
.badge-outdated   { background: #fff5f5; color: #e03131; border: 1px solid #ffc9c9; }
.badge-local-only { background: #f1f3f5; color: #495057; border: 1px solid #e9ecef; }
.badge-unknown    { background: #f8f9fa; color: #868e96; border: 1px solid #dee2e6; }

/* Pulse Animation for Random Sample Selector */
@keyframes highlight-pulse {
    0% { background-color: transparent; }
    30% { background-color: rgba(79, 70, 229, 0.2); box-shadow: 0 0 15px rgba(79, 70, 229, 0.4); }
    70% { background-color: rgba(79, 70, 229, 0.1); }
    100% { background-color: transparent; }
}
.pulse-highlight {
    animation: highlight-pulse 2s ease-in-out 3;
    border: 2px solid var(--sync-primary) !important;
}

/* General Layout and Cards */
.stat-card {
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.05);
}
.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

/* Controls */
.filter-btn {
    border-radius: 99px;
    padding: 6px 16px;
    font-size: 0.82rem;
    font-weight: 600;
    transition: all 0.2s;
}
.filter-btn.active {
    background-color: var(--sync-primary);
    color: white !important;
    border-color: var(--sync-primary);
}

/* Spinner */
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }
</style>

<div class="animate__animated animate__fadeIn">

    <!-- ===== Header ===== -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--text-main); font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-square-poll-horizontal text-primary me-2"></i> تدقيق ومقارنة البيانات (MySQL ↔ HFSQL)
            </h4>
            <p class="text-muted small mb-0">
                نظام تدقيق وتطابق البيانات (Data Reconciliation Dashboard) — فحص موثوق وذكي على دفعات
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sync.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-list-check me-1"></i> لوحة المزامنة والطابور
            </a>
        </div>
    </div>

    <!-- ===== Stats Row ===== -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="fa-solid fa-table"></i></div>
                <div>
                    <div class="fw-bold fs-5" id="stat-total"><?= count($allTables) ?></div>
                    <div class="small text-muted">إجمالي الجداول</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="fw-bold fs-5" id="stat-synced">0</div>
                    <div class="small text-muted">جداول متطابقة</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="fw-bold fs-5" id="stat-outdated">0</div>
                    <div class="small text-muted">تحتاج تحديث</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-circle-xmark"></i></div>
                <div>
                    <div class="fw-bold fs-5" id="stat-empty">0</div>
                    <div class="small text-muted">فارغة محلياً</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Control Panel ===== -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--card-radius);">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-6 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary fw-bold px-4" id="btn-start-scan" onclick="startReconciliation(true, 'scan')">
                        <i class="fa-solid fa-arrows-rotate me-2"></i> بدء الفحص والتحديث الشامل
                    </button>
                    <button class="btn btn-warning fw-bold text-white px-4" id="btn-reverify-stats" onclick="startReconciliation(true, 'reverify')">
                        <i class="fa-solid fa-shield-halved me-2"></i> إعادة التحقق من الإحصائيات / Re-verify Stats
                    </button>
                    <button class="btn btn-outline-primary fw-bold" id="btn-random-sample" onclick="pickRandomSample()">
                        <i class="fa-solid fa-dice me-2"></i> اختيار جدول عينة عشوائي
                    </button>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-inline-block w-100" style="max-width: 300px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 shadow-none bg-light" id="table-search" placeholder="بحث في الجداول..." oninput="filterTables()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar (Hidden by default) -->
            <div class="mt-3" id="progress-container" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-semibold text-primary" id="progress-status">جاري فحص الجداول...</span>
                    <span class="small fw-bold text-primary" id="progress-pct">0%</span>
                </div>
                <div class="progress" style="height: 8px; border-radius: 99px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" id="progress-bar-fill" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Filters & Summary ===== -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-light border filter-btn active" data-filter="all" onclick="setFilter('all')">الكل</button>
            <button class="btn btn-sm btn-light border filter-btn" data-filter="SYNCED" onclick="setFilter('SYNCED')">متطابق</button>
            <button class="btn btn-sm btn-light border filter-btn" data-filter="OUTDATED" onclick="setFilter('OUTDATED')">تحتاج تحديث</button>
            <button class="btn btn-sm btn-light border filter-btn" data-filter="EMPTY_LOCALLY" onclick="setFilter('EMPTY_LOCALLY')">فارغ محلياً</button>
        </div>
        <div class="text-muted small">
            عدد الجداول المعروضة: <span class="fw-bold text-dark" id="visible-count">0</span>
        </div>
    </div>

    <!-- ===== Data Table ===== -->
    <div class="card border-0 shadow-sm" style="border-radius: var(--card-radius);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;" id="compare-table">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="ps-4">اسم الجدول</th>
                            <th>عدد السجلات (MySQL)</th>
                            <th>عدد السجلات (HFSQL)</th>
                            <th>الحالة</th>
                            <th>تاريخ التحديث</th>
                            <th class="pe-4 text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="compare-tbody">
                        <?php foreach ($allTables as $table): 
                            $cached = $reportsMap[$table] ?? null;
                            $mysqlC = $cached ? (int)$cached['mysql_count'] : 0;
                            $hfsqlC = $cached ? (int)$cached['hfsql_count'] : 0;
                            $status = $cached ? $cached['status'] : 'UNKNOWN';
                            $updated = $cached ? date('H:i:s Y-m-d', strtotime($cached['updated_at'])) : '—';
                            $badge = getStatusBadge($status);
                        ?>
                            <tr id="row_<?= htmlspecialchars($table) ?>" data-table="<?= htmlspecialchars($table) ?>" data-status="<?= $status ?>">
                                <td class="ps-4 fw-semibold table-name-cell">
                                    <i class="fa-solid fa-table me-2 text-muted opacity-50"></i><?= htmlspecialchars($table) ?>
                                </td>
                                <td class="mysql-count-cell fw-bold"><?= $cached ? number_format($mysqlC) : '—' ?></td>
                                <td class="hfsql-count-cell fw-bold"><?= $cached ? number_format($hfsqlC) : '—' ?></td>
                                <td class="status-cell"><?= $badge ?></td>
                                <td class="updated-cell text-muted"><?= $updated ?></td>
                                <td class="pe-4 text-end actions-cell">
                                    <?php if ($status !== 'SYNCED' && $status !== 'LOCAL_ONLY'): ?>
                                        <button class="btn btn-xs btn-primary fw-semibold" onclick="syncTableDirect('<?= htmlspecialchars($table) ?>')">
                                            <i class="fa-solid fa-cloud-arrow-down me-1"></i> مزامنة الآن
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-xs btn-outline-secondary fw-semibold" onclick="syncTableDirect('<?= htmlspecialchars($table) ?>')">
                                             إعادة مزامنة
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
const CSRF_TOKEN = '<?= csrf_token() ?? '' ?>';
const ALL_TABLES = <?= $allTablesJson ?>;
let currentFilter = 'all';
let isScanning    = false;

document.addEventListener('DOMContentLoaded', () => {
    updateStatsCounters();
    filterTables();
});

// Calculate statistics counters from the rendered table rows
function updateStatsCounters() {
    let synced = 0, outdated = 0, empty = 0;
    document.querySelectorAll('#compare-tbody tr').forEach(row => {
        const status = row.getAttribute('data-status');
        if (status === 'SYNCED') synced++;
        else if (status === 'OUTDATED') outdated++;
        else if (status === 'EMPTY_LOCALLY') empty++;
    });

    document.getElementById('stat-synced').textContent = synced;
    document.getElementById('stat-outdated').textContent = outdated;
    document.getElementById('stat-empty').textContent = empty;
}

// Set Filter
function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-filter') === filter);
    });
    filterTables();
}

// Filter Tables list by search and active status filter
function filterTables() {
    const q = document.getElementById('table-search').value.toLowerCase().trim();
    let visible = 0;

    document.querySelectorAll('#compare-tbody tr').forEach(row => {
        const table = row.getAttribute('data-table').toLowerCase();
        const status = row.getAttribute('data-status');

        const matchesQuery = q ? table.includes(q) : true;
        const matchesFilter = (currentFilter === 'all') ? true : (status === currentFilter);

        if (matchesQuery && matchesFilter) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('visible-count').textContent = visible;
}

// ============================================================
// BATCH RECONCILIATION SCANNER (10 Tables Per Request)
// ============================================================
async function startReconciliation(force = true, triggerType = 'scan') {
    if (isScanning) return;
    isScanning = true;

    // Toggle button state
    const btnScan = document.getElementById('btn-start-scan');
    const btnReverify = document.getElementById('btn-reverify-stats');
    const btnRandom = document.getElementById('btn-random-sample');

    if (btnScan) btnScan.disabled = true;
    if (btnReverify) btnReverify.disabled = true;
    if (btnRandom) btnRandom.disabled = true;

    if (triggerType === 'reverify' && btnReverify) {
        btnReverify.innerHTML = '<i class="fa-solid fa-spinner spin me-2"></i> جاري التحقق وتصحيح الإحصائيات...';
    } else if (btnScan) {
        btnScan.innerHTML = '<i class="fa-solid fa-spinner spin me-2"></i> جاري التدقيق الشامل...';
    }

    // Show progress bar
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar-fill');
    const progressStatus = document.getElementById('progress-status');
    const progressPct = document.getElementById('progress-pct');
    progressContainer.style.display = 'block';

    // Chunk tables in batches of 10
    const batchSize = 10;
    const chunks = [];
    for (let i = 0; i < ALL_TABLES.length; i += batchSize) {
        chunks.push(ALL_TABLES.slice(i, i + batchSize));
    }

    let processedCount = 0;

    for (let index = 0; index < chunks.length; index++) {
        const chunk = chunks[index];
        progressStatus.textContent = `جاري فحص الدفعة ${index + 1} من أصل ${chunks.length}...`;

        // Update UI row status to loading spinners for these tables
        chunk.forEach(tbl => {
            const row = document.getElementById('row_' + tbl);
            if (row) {
                row.querySelector('.status-cell').innerHTML = '<i class="fa-solid fa-spinner spin text-primary"></i> <small class="text-muted">جاري الفحص...</small>';
            }
        });

        // Send AJAX batch request
        const formData = new FormData();
        chunk.forEach(t => formData.append('tables[]', t));
        if (force) formData.append('force', '1');
        formData.append('csrf_token', CSRF_TOKEN);

        try {
            const resp = await fetch('{{ route("sync.compare.counts") }}', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success && data.results) {
                // Update table rows with fresh results
                data.results.forEach(res => {
                    const row = document.getElementById('row_' + res.table);
                    if (row) {
                        row.setAttribute('data-status', res.status);
                        row.querySelector('.mysql-count-cell').textContent = Number(res.mysql).toLocaleString();
                        row.querySelector('.hfsql-count-cell').textContent = Number(res.hfsql).toLocaleString();
                        row.querySelector('.status-cell').innerHTML = getStatusBadgeHtml(res.status);
                        row.querySelector('.updated-cell').textContent = new Date().toLocaleTimeString('ar-DZ') + ' ' + new Date().toLocaleDateString('ar-DZ');
                        
                        // Re-render Action button based on status
                        const actionsCell = row.querySelector('.actions-cell');
                        if (res.status !== 'SYNCED' && res.status !== 'LOCAL_ONLY') {
                            actionsCell.innerHTML = `<button class="btn btn-xs btn-primary fw-semibold" onclick="syncTableDirect('${res.table}')"><i class="fa-solid fa-cloud-arrow-down me-1"></i> مزامنة الآن</button>`;
                        } else {
                            actionsCell.innerHTML = `<button class="btn btn-xs btn-outline-secondary fw-semibold" onclick="syncTableDirect('${res.table}')">إعادة مزامنة</button>`;
                        }
                    }
                });
            }
        } catch (e) {
            // Handle error silently or update status
            chunk.forEach(tbl => {
                const row = document.getElementById('row_' + tbl);
                if (row) {
                    row.querySelector('.status-cell').innerHTML = '<span class="badge badge-unknown">خطأ في الاتصال</span>';
                }
            });
        }

        processedCount += chunk.length;
        const pct = Math.round((processedCount / ALL_TABLES.length) * 100);
        progressBar.style.width = pct + '%';
        progressPct.textContent = pct + '%';

        updateStatsCounters();
        filterTables();
    }

    // Reset button state
    isScanning = false;
    if (btnScan) {
        btnScan.disabled = false;
        btnScan.innerHTML = '<i class="fa-solid fa-arrows-rotate me-2"></i> بدء الفحص والتحديث الشامل';
    }
    if (btnReverify) {
        btnReverify.disabled = false;
        btnReverify.innerHTML = '<i class="fa-solid fa-shield-halved me-2"></i> إعادة التحقق من الإحصائيات / Re-verify Stats';
    }
    if (btnRandom) btnRandom.disabled = false;
    progressStatus.textContent = 'اكتمل تدقيق ومقارنة كافة البيانات بنجاح!';
    setTimeout(() => { progressContainer.style.display = 'none'; }, 5000);
}

// Get Badge HTML dynamically
function getStatusBadgeHtml(status) {
    const map = {
        SYNCED: '<span class="badge badge-synced px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>متطابق</span>',
        EMPTY_LOCALLY: '<span class="badge badge-empty px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>فارغ محلياً</span>',
        OUTDATED: '<span class="badge badge-outdated px-2 py-1"><i class="fa-solid fa-triangle-exclamation"></i> يحتاج تحديث</span>',
        LOCAL_ONLY: '<span class="badge badge-local-only px-2 py-1">محلي فقط</span>',
        UNKNOWN: '<span class="badge badge-unknown px-2 py-1">غير مفحوص</span>'
    };
    return map[status] || `<span class="badge bg-secondary">${status}</span>`;
}

// ============================================================
// RANDOM SAMPLE SELECTOR
// ============================================================
function pickRandomSample() {
    // Select all rows that are outdated or empty locally
    const targetRows = Array.from(document.querySelectorAll('#compare-tbody tr')).filter(row => {
        const status = row.getAttribute('data-status');
        return (status === 'OUTDATED' || status === 'EMPTY_LOCALLY' || status === 'UNKNOWN');
    });

    if (targetRows.length === 0) {
        showToast('جميع الجداول متطابقة تماماً 100%! لا حاجة لعينات عشوائية.', 'success');
        return;
    }

    // Choose random table
    const randomRow = targetRows[Math.floor(Math.random() * targetRows.length)];
    const tableName = randomRow.getAttribute('data-table');

    // Remove any previous highlight
    document.querySelectorAll('#compare-tbody tr').forEach(r => r.classList.remove('pulse-highlight'));

    // Highlight and Smooth Scroll
    randomRow.classList.add('pulse-highlight');
    randomRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Toast and Sync Confirmation
    showToast(`تم اختيار جدول العينة: ${tableName}`, 'info');

    setTimeout(() => {
        if (confirm(`تم اختيار جدول العينة عشوائياً: [${tableName}]\nهل تريد بدء مزامنة هذا الجدول فوراً عبر طابور المهام؟`)) {
            syncTableDirect(tableName);
        }
    }, 800);
}

// ============================================================
// DIRECT TABLE SYNC (Enqueue)
// ============================================================
async function syncTableDirect(tableName) {
    const formData = new FormData();
    formData.append('tables[]', tableName);
    formData.append('sync_type', 'global');
    formData.append('csrf_token', CSRF_TOKEN);
    
    try {
        const resp = await fetch('{{ route("sync.enqueue") }}', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            showToast(`تمت إضافة الجدول [${tableName}] لطابور المزامنة بنجاح!`, 'success');
            // Ask to redirect to dashboard or stay
            if (confirm('تمت إضافة المهمة إلى طابور الخلفية بنجاح.\nهل تريد الانتقال إلى لوحة المزامنة لمراقبة تقدم المزامنة الحية؟')) {
                window.location.href = '{{ route("sync.index") }}';
            }
        } else {
            showToast(data.message || 'فشلت إضافة المهمة', 'danger');
        }
    } catch (e) {
        showToast('خطأ في الاتصال بالخادم', 'danger');
    }
}

// Helper to show toasts
function showToast(msg, type = 'info') {
    const colors = { success: '#10b981', danger: '#ef4444', warning: '#f59e0b', info: '#4f46e5' };
    const toast  = document.createElement('div');
    toast.style.cssText = `
        position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
        background:${colors[type] || colors.info}; color:#fff;
        padding:12px 24px; border-radius:99px; font-weight:600;
        box-shadow:0 8px 24px rgba(0,0,0,.2); z-index:9999;
        animation: toastIn .3s ease;
        font-family:'Cairo',sans-serif; font-size:.9rem;
    `;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3500);
}
</script>

<?php
// PHP helper to output initial badges
function getStatusBadge($status) {
    $map = [
        'SYNCED' => '<span class="badge badge-synced px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>متطابق</span>',
        'EMPTY_LOCALLY' => '<span class="badge badge-empty px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>فارغ محلياً</span>',
        'OUTDATED' => '<span class="badge badge-outdated px-2 py-1"><i class="fa-solid fa-triangle-exclamation"></i> يحتاج تحديث</span>',
        'LOCAL_ONLY' => '<span class="badge badge-local-only px-2 py-1">محلي فقط</span>',
        'UNKNOWN' => '<span class="badge badge-unknown px-2 py-1">غير مفحوص</span>'
    ];
    return $map[$status] ?? '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}
?>

@endsection
