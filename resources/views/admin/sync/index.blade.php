@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $wilayas
 * @var array  $etabs
 * @var array  $allTables
 */
$allTablesJson = json_encode($allTables, JSON_UNESCAPED_UNICODE);
?>

<style>
:root {
    --sync-primary:   #4f46e5;
    --sync-success:   #10b981;
    --sync-danger:    #ef4444;
    --sync-warning:   #f59e0b;
    --sync-running:   #3b82f6;
    --sync-pending:   #6b7280;
    --card-radius:    16px;
}

/* ---- Sidebar Table List ---- */
.table-scroll-box {
    max-height: 340px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #c7d2fe transparent;
}
.table-scroll-box::-webkit-scrollbar { width: 5px; }
.table-scroll-box::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }

.table-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background .15s;
    font-size: 0.82rem;
}
.table-item:hover { background: #eef2ff; }
.table-item input[type=checkbox] { accent-color: var(--sync-primary); width: 15px; height: 15px; }

/* ---- Job Row ---- */
.job-row { transition: background .2s; }
.job-row:hover { background: #f8fafc; }

/* ---- Progress Bar ---- */
.sync-progress {
    height: 6px;
    border-radius: 99px;
    background: #e5e7eb;
    overflow: hidden;
    min-width: 100px;
}
.sync-progress-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #4f46e5, #818cf8);
    transition: width .4s ease;
}

/* ---- Status Badges ---- */
.badge-pending  { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
.badge-running  { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
.badge-done     { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.badge-failed   { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.badge-paused   { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }

/* ---- Log Terminal ---- */
.log-terminal {
    background: #0f172a;
    border-radius: 12px;
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 0.75rem;
    max-height: 250px;
    overflow-y: auto;
    padding: 14px;
    color: #94a3b8;
    scrollbar-width: thin;
    scrollbar-color: #334155 transparent;
}
.log-terminal::-webkit-scrollbar { width: 5px; }
.log-terminal::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
.log-line-info    { color: #67e8f9; }
.log-line-warning { color: #fcd34d; }
.log-line-error   { color: #f87171; }
.log-line-debug   { color: #94a3b8; }

/* ---- Stats Cards ---- */
.stat-card {
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: none;
}
.stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }

/* ---- Spinner animation ---- */
@keyframes spin-slow { to { transform: rotate(360deg); } }
.spin { animation: spin-slow 1.2s linear infinite; }

/* ---- Selected counter badge ---- */
.selected-count-badge {
    background: var(--sync-primary);
    color: white;
    border-radius: 99px;
    font-size: 0.7rem;
    padding: 2px 8px;
    font-weight: 700;
}
</style>

<div class="animate__animated animate__fadeIn">

    <!-- ===== Header ===== -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--text-main); font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-cloud-arrow-down text-primary me-2"></i> مزامنة البيانات من HFSQL
            </h4>
            <p class="text-muted small mb-0">
                نظام المزامنة الذكي — Queue · Background Workers · Resume After Failure · Full Logging
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-success" onclick="refreshDashboardStats()" id="btn-refresh-stats" title="تحديث إحصائيات لوحة التحكم (KPIs)">
                <i class="fa-solid fa-arrows-rotate me-1"></i> تحديث الإحصائيات (KPIs)
            </button>
            <a href="{{ route('sync.compare') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-square-poll-horizontal me-1"></i> تدقيق ومقارنة البيانات
            </a>
            <button class="btn btn-sm btn-outline-danger" onclick="clearQueue()" title="حذف المهام المنتهية (أكثر من 24 ساعة)">
                <i class="fa-solid fa-trash-can me-1"></i> تنظيف الطابور
            </button>
        </div>
    </div>

    <div class="row g-4">

        <!-- ===== LEFT: Configuration Panel ===== -->
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm" style="border-radius: var(--card-radius);">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-sliders me-1"></i> إعدادات المزامنة</h6>
                </div>
                <div class="card-body p-4">
                    <form id="sync-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">

                        <!-- Sync Type -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">نطاق المزامنة:</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sync_type" id="syncWilaya" value="wilaya" checked onchange="toggleSyncType()">
                                    <label class="form-check-label fw-semibold" for="syncWilaya">حسب الولاية</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sync_type" id="syncEtab" value="etab" onchange="toggleSyncType()">
                                    <label class="form-check-label fw-semibold" for="syncEtab">حسب المؤسسة</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sync_type" id="syncGlobal" value="global" onchange="toggleSyncType()">
                                    <label class="form-check-label fw-semibold" for="syncGlobal">كلي</label>
                                </div>
                            </div>
                        </div>

                        <!-- Wilaya -->
                        <div class="mb-3" id="wilaya-container">
                            <label class="form-label fw-bold text-dark small">اختر الولاية:</label>
                            <select name="wilaya_id" class="form-select form-select-sm border-2 shadow-none">
                                <option value="">-- حدد الولاية --</option>
                                <?php foreach ($wilayas as $w): ?>
                                    <option value="<?= htmlspecialchars($w['IDWilayaa']) ?>">
                                        <?= htmlspecialchars($w['Code'] . ' - ' . $w['Nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Etablissement -->
                        <div class="mb-3" id="etab-container" style="display:none;">
                            <label class="form-label fw-bold text-dark small">اختر المؤسسة:</label>
                            <select name="etab_id" class="form-select form-select-sm border-2 shadow-none">
                                <option value="">-- حدد المؤسسة --</option>
                                <?php foreach ($etabs as $e): ?>
                                    <option value="<?= htmlspecialchars($e['IDetablissement']) ?>" data-wilaya="<?= htmlspecialchars($e['IDDFEP'] ?? '') ?>">
                                        <?= htmlspecialchars($e['Nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tables Selection -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fw-bold text-dark small mb-0">
                                    الجداول
                                    <span class="selected-count-badge ms-1" id="selected-count">0</span>
                                </label>
                                <div class="d-flex gap-2">
                                    <a href="#" class="text-decoration-none small text-primary" onclick="selectAllTables(true); return false;">الكل</a>
                                    <span class="text-muted">·</span>
                                    <a href="#" class="text-decoration-none small text-muted" onclick="selectAllTables(false); return false;">إلغاء</a>
                                </div>
                            </div>

                            <!-- Search -->
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 shadow-none bg-light" id="table-search" placeholder="بحث في الجداول...">
                            </div>

                            <!-- Table List -->
                            <div class="table-scroll-box border rounded-3 bg-white p-2" id="table-list">
                                <!-- Populated by JS -->
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" id="btn-sync">
                            <i class="fa-solid fa-play me-2"></i> إضافة للطابور وتشغيل
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== RIGHT: Monitoring Dashboard ===== -->
        <div class="col-xl-8 col-lg-7">

            <!-- Stats Row -->
            <div class="row g-3 mb-4" id="stats-row">
                <div class="col-6 col-sm-3">
                    <div class="stat-card bg-white border shadow-sm">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div><div class="fw-bold fs-5" id="stat-pending">—</div><div class="small text-muted">في الانتظار</div></div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="stat-card bg-white border shadow-sm">
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fa-solid fa-gears"></i></div>
                        <div><div class="fw-bold fs-5" id="stat-running">—</div><div class="small text-muted">قيد التشغيل</div></div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="stat-card bg-white border shadow-sm">
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-circle-check"></i></div>
                        <div><div class="fw-bold fs-5" id="stat-done">—</div><div class="small text-muted">مكتمل</div></div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="stat-card bg-white border shadow-sm">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div><div class="fw-bold fs-5" id="stat-failed">—</div><div class="small text-muted">فاشل</div></div>
                    </div>
                </div>
            </div>

            <!-- Queue Table -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--card-radius);">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-1 text-primary"></i> طابور المهام</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-success border border-success-subtle" id="live-badge">
                            <i class="fa-solid fa-circle text-success" style="font-size:.5rem;"></i> Live
                        </span>
                        <button class="btn btn-sm btn-light border" onclick="pollQueue()" title="تحديث يدوي">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0" style="font-size:0.82rem;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-3">الجدول</th>
                                    <th>الحالة</th>
                                    <th>التقدم</th>
                                    <th>السجلات</th>
                                    <th>الوقت</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="queue-tbody">
                                <tr><td colspan="6" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-inbox fa-2x opacity-25 mb-2 d-block"></i>
                                    لا توجد مهام حتى الآن
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Log Terminal -->
            <div class="card border-0 shadow-sm" style="border-radius: var(--card-radius);">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid fa-terminal me-1 text-secondary"></i>
                        سجل العمليات
                        <small class="text-muted fw-normal ms-2" id="log-job-label"></small>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Search Log -->
                        <input type="text" class="form-control form-control-sm border shadow-none bg-light" id="log-filter" placeholder="تصفية السجل..." style="width: 140px;">
                        
                        <!-- Line Wrap -->
                        <button class="btn btn-sm btn-outline-secondary" id="btn-log-wrap" onclick="toggleLogWrap()" title="التفاف الأسطر">
                            <i class="fa-solid fa-align-left"></i>
                        </button>
                        
                        <!-- Auto Scroll -->
                        <button class="btn btn-sm btn-outline-primary active" id="btn-log-scroll" onclick="toggleLogScroll()" title="النزول التلقائي">
                            <i class="fa-solid fa-angles-down"></i>
                        </button>
                        
                        <button class="btn btn-sm btn-light border" onclick="clearLogs()" title="مسح الشاشة"><i class="fa-solid fa-eraser"></i></button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="log-terminal" id="log-terminal">
                        <span class="log-line-debug">// سيتم عرض سجلات المزامنة هنا عند تشغيل مهمة...</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// ============================================================
// DATA
// ============================================================
const ALL_TABLES  = <?= $allTablesJson ?>;
let selectedTables = new Set();
let pollingInterval = null;
let activeLogJobId  = null;
let logPollingInterval = null;
let jobSpeeds = {};
let logAutoScroll = true;
let logLineWrap = false;
let masterEtabs = [];

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    // Save master list of establishments
    const etabSelect = document.querySelector('select[name="etab_id"]');
    if (etabSelect) {
        masterEtabs = Array.from(etabSelect.querySelectorAll('option')).map(opt => ({
            value: opt.value,
            text: opt.textContent,
            wilayaId: opt.getAttribute('data-wilaya')
        }));
    }

    renderTableList(ALL_TABLES);
    startPolling();
    pollQueue();

    document.getElementById('table-search').addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        renderTableList(q ? ALL_TABLES.filter(t => t.toLowerCase().includes(q)) : ALL_TABLES);
    });

    // Add event listener to Wilaya select to filter establishments
    const wilayaSelect = document.querySelector('select[name="wilaya_id"]');
    if (wilayaSelect) {
        wilayaSelect.addEventListener('change', filterEtabsByWilaya);
    }
});

// ============================================================
// TABLE LIST RENDERING
// ============================================================
function renderTableList(tables) {
    const list = document.getElementById('table-list');
    list.innerHTML = '';
    if (!tables.length) {
        list.innerHTML = '<p class="text-center text-muted small py-3">لا توجد نتائج</p>';
        return;
    }
    tables.forEach(t => {
        const div = document.createElement('div');
        div.className = 'table-item';
        const isChecked = selectedTables.has(t);
        div.innerHTML = `
            <input type="checkbox" class="table-checkbox" value="${t}" id="tbl_${t}" ${isChecked ? 'checked' : ''} onchange="onTableCheck(this)">
            <label for="tbl_${t}" style="cursor:pointer; width:100%; user-select:none;">${t}</label>
        `;
        list.appendChild(div);
    });
    updateSelectedCount();
}

function onTableCheck(cb) {
    if (cb.checked) selectedTables.add(cb.value);
    else selectedTables.delete(cb.value);
    updateSelectedCount();
}

function selectAllTables(check) {
    const visible = document.querySelectorAll('.table-checkbox');
    visible.forEach(cb => {
        cb.checked = check;
        if (check) selectedTables.add(cb.value);
        else selectedTables.delete(cb.value);
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    document.getElementById('selected-count').textContent = selectedTables.size;
}

// ============================================================
// SYNC TYPE TOGGLE & FILTERING
// ============================================================
function toggleSyncType() {
    const type = document.querySelector('input[name="sync_type"]:checked').value;
    document.getElementById('wilaya-container').style.display = type === 'wilaya' ? 'block' : 'none';
    document.getElementById('etab-container').style.display   = type === 'etab'   ? 'block' : 'none';

    // Clear Wilaya selection when sync type is changed to avoid stale filters
    const wilayaSelect = document.querySelector('select[name="wilaya_id"]');
    if (wilayaSelect && type === 'global') {
        wilayaSelect.value = '';
    }

    filterEtabsByWilaya();
}

function filterEtabsByWilaya() {
    const type = document.querySelector('input[name="sync_type"]:checked').value;
    const wilayaId = document.querySelector('select[name="wilaya_id"]').value;
    const etabSelect = document.querySelector('select[name="etab_id"]');
    if (!etabSelect) return;

    // Save current selected value
    const currentVal = etabSelect.value;

    // Clear and rebuild options
    etabSelect.innerHTML = '';

    masterEtabs.forEach(etab => {
        if (!etab.value) {
            // Placeholder option
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = etab.text;
            etabSelect.appendChild(opt);
            return;
        }

        // Show option if no Wilaya is selected, or if Wilaya matches
        if (!wilayaId || etab.wilayaId === wilayaId) {
            const opt = document.createElement('option');
            opt.value = etab.value;
            opt.textContent = etab.text;
            opt.setAttribute('data-wilaya', etab.wilayaId);
            etabSelect.appendChild(opt);
        }
    });

    // Restore selected value if still present
    etabSelect.value = currentVal;
    if (etabSelect.value !== currentVal) {
        etabSelect.value = '';
    }
}

// ============================================================
// FORM SUBMIT → ENQUEUE
// ============================================================
document.getElementById('sync-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (selectedTables.size === 0) {
        showToast('يرجى تحديد جدول واحد على الأقل.', 'warning');
        return;
    }

    const formData = new FormData(this);
    selectedTables.forEach(t => formData.append('tables[]', t));

    const btn = document.getElementById('btn-sync');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner spin me-2"></i> جاري الإضافة للطابور...';

    try {
        const resp = await fetch('{{ route("sync.enqueue") }}', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            showToast(data.message, 'success');
            pollQueue();
        } else {
            showToast(data.message || 'حدث خطأ', 'danger');
        }
    } catch (err) {
        showToast('فشل الاتصال: ' + err.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-play me-2"></i> إضافة للطابور وتشغيل';
    }
});

// ============================================================
// QUEUE POLLING
// ============================================================
function startPolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(pollQueue, 3000);
}

async function pollQueue() {
    try {
        const resp = await fetch('{{ route("sync.queue") }}?t=' + Date.now());
        const data = await resp.json();
        if (!data.success) return;

        updateStats(data.stats);
        renderQueue(data.jobs, data.server_time);
    } catch (e) {
        // Silent fail
    }
}

function updateStats(stats) {
    const map = { pending: 0, running: 0, done: 0, failed: 0 };
    (stats || []).forEach(s => { map[s.status] = s.cnt; });
    document.getElementById('stat-pending').textContent = map.pending;
    document.getElementById('stat-running').textContent = map.running;
    document.getElementById('stat-done').textContent    = map.done;
    document.getElementById('stat-failed').textContent  = map.failed;
}

function renderQueue(jobs, serverTime) {
    const tbody = document.getElementById('queue-tbody');
    if (!jobs || !jobs.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-5">
            <i class="fa-solid fa-inbox fa-2x opacity-25 mb-2 d-block"></i>لا توجد مهام
        </td></tr>`;
        return;
    }

    tbody.innerHTML = jobs.map(job => {
        const rawPct = job.total_rows > 0 ? (job.synced_rows / job.total_rows) * 100 : (job.status === 'done' ? 100 : 0);
        let pctText = '0%';
        if (job.total_rows > 0) {
            const raw = (job.synced_rows / job.total_rows) * 100;
            if (raw > 0 && raw < 0.1) {
                pctText = raw.toFixed(2) + '%';
            } else if (raw >= 0.1 && raw < 99.9) {
                pctText = raw.toFixed(1) + '%';
            } else {
                pctText = Math.round(raw) + '%';
            }
        } else if (job.status === 'done') {
            pctText = '100%';
        }

        const badge = badgeHtml(job.status);
        const elapsed = job.started_at_ts ? elapsedTime(job.started_at_ts, job.finished_at_ts, serverTime) : '—';

        // Calculate Speed & ETA
        let speedText = '';
        let etaText = '';
        if (job.status === 'running') {
            const prev = jobSpeeds[job.job_id];
            const now = Date.now();
            if (prev && prev.lastSyncedRows < job.synced_rows) {
                const elapsedS = (now - prev.lastTime) / 1000;
                const diff = job.synced_rows - prev.lastSyncedRows;
                const speed = Math.round(diff / elapsedS); // rows/sec
                if (speed > 0) {
                    prev.speed = speed;
                }
            }
            if (!prev) {
                jobSpeeds[job.job_id] = { lastSyncedRows: job.synced_rows, lastTime: now, speed: 0 };
            } else {
                prev.lastSyncedRows = job.synced_rows;
                prev.lastTime = now;
            }

            const currentSpeed = jobSpeeds[job.job_id].speed;
            if (currentSpeed > 0) {
                speedText = ` <span class="badge bg-light text-primary border ms-1">${currentSpeed}/ث</span>`;
                const remaining = job.total_rows - job.synced_rows;
                const etaSecs = Math.round(remaining / currentSpeed);
                if (etaSecs > 0) {
                    if (etaSecs < 60) etaText = `متبقي ~ ${etaSecs}ث`;
                    else etaText = `متبقي ~ ${Math.round(etaSecs / 60)}د`;
                }
            }
        } else {
            delete jobSpeeds[job.job_id];
        }

        let actionBtn = '';
        if (job.status === 'running' || job.status === 'pending') {
            actionBtn = `<button class="btn btn-xs btn-outline-danger ms-1" onclick="pauseJob('${job.job_id}')" title="إيقاف مؤقت">
                <i class="fa-solid fa-pause"></i>
               </button>`;
        } else if (job.status === 'paused') {
            actionBtn = `<button class="btn btn-xs btn-outline-success ms-1" onclick="retryJob('${job.job_id}')" title="استئناف">
                <i class="fa-solid fa-play"></i>
               </button>`;
        } else if (job.status === 'failed') {
            actionBtn = `<button class="btn btn-xs btn-outline-warning ms-1" onclick="retryJob('${job.job_id}')" title="إعادة المحاولة">
                <i class="fa-solid fa-rotate-right"></i>
               </button>`;
        }

        const logBtn = `<button class="btn btn-xs btn-outline-secondary" onclick="viewLogs('${job.job_id}','${job.table_name}')" title="عرض السجل">
            <i class="fa-solid fa-scroll"></i>
        </button>`;

        return `
            <tr class="job-row">
                <td class="ps-3 fw-semibold"><i class="fa-solid fa-database me-1 text-muted opacity-50"></i>${job.table_name}</td>
                <td>${badge}</td>
                <td>
                    <div class="sync-progress">
                        <div class="sync-progress-fill" style="width:${rawPct}%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-muted" style="font-size:.7rem;">${pctText} ${speedText}</span>
                        ${etaText ? `<span class="text-muted" style="font-size:.65rem;">${etaText}</span>` : ''}
                    </div>
                </td>
                <td>
                    <span class="fw-bold">${Number(job.synced_rows).toLocaleString()}</span>
                    ${job.total_rows > 0 ? '<small class="text-muted">/ ' + Number(job.total_rows).toLocaleString() + '</small>' : ''}
                </td>
                <td class="text-muted">${elapsed}</td>
                <td>${logBtn}${actionBtn}</td>
            </tr>
        `;
    }).join('');
}

function badgeHtml(status) {
    const map = {
        pending: '<span class="badge badge-pending px-2 py-1"><i class="fa-solid fa-hourglass me-1"></i>انتظار</span>',
        running: '<span class="badge badge-running px-2 py-1"><i class="fa-solid fa-spinner spin me-1"></i>يعمل</span>',
        done:    '<span class="badge badge-done px-2 py-1"><i class="fa-solid fa-check me-1"></i>مكتمل</span>',
        failed:  '<span class="badge badge-failed px-2 py-1"><i class="fa-solid fa-xmark me-1"></i>فشل</span>',
        paused:  '<span class="badge badge-paused px-2 py-1"><i class="fa-solid fa-pause me-1"></i>متوقف</span>',
    };
    return map[status] || `<span class="badge bg-secondary">${status}</span>`;
}

function elapsedTime(startTs, endTs, serverNowTs) {
    const s = parseInt(startTs);
    const e = endTs ? parseInt(endTs) : parseInt(serverNowTs || Math.floor(Date.now() / 1000));
    const diff = e - s;
    if (diff < 0) return '0ث';
    if (diff < 60)  return diff + 'ث';
    if (diff < 3600) return Math.floor(diff/60) + 'د ' + (diff%60) + 'ث';
    return Math.floor(diff/3600) + 'س ' + Math.floor((diff%3600)/60) + 'د';
}

// ============================================================
// LOG VIEWING
// ============================================================
async function viewLogs(jobId, tableName) {
    activeLogJobId = jobId;
    document.getElementById('log-job-label').textContent = '(' + tableName + ')';
    await fetchLogs(jobId);

    // Start live log polling if job is running
    if (logPollingInterval) clearInterval(logPollingInterval);
    logPollingInterval = setInterval(() => fetchLogs(activeLogJobId), 3000);
}

function toggleLogScroll() {
    logAutoScroll = !logAutoScroll;
    const btn = document.getElementById('btn-log-scroll');
    if (logAutoScroll) {
        btn.classList.add('active', 'btn-outline-primary');
        btn.classList.remove('btn-outline-secondary');
    } else {
        btn.classList.remove('active', 'btn-outline-primary');
        btn.classList.add('btn-outline-secondary');
    }
}

function toggleLogWrap() {
    logLineWrap = !logLineWrap;
    const terminal = document.getElementById('log-terminal');
    const btn = document.getElementById('btn-log-wrap');
    if (logLineWrap) {
        terminal.style.whiteSpace = 'pre-wrap';
        terminal.style.wordBreak = 'break-all';
        btn.classList.add('active', 'btn-outline-primary');
        btn.classList.remove('btn-outline-secondary');
    } else {
        terminal.style.whiteSpace = 'pre';
        terminal.style.wordBreak = 'normal';
        btn.classList.remove('active', 'btn-outline-primary');
        btn.classList.add('btn-outline-secondary');
    }
}

async function fetchLogs(jobId) {
    try {
        const filterVal = document.getElementById('log-filter').value.toLowerCase().trim();
        const resp = await fetch('{{ route("sync.logs") }}?job_id=' + jobId + '&t=' + Date.now());
        const data = await resp.json();
        if (!data.success) return;

        const terminal = document.getElementById('log-terminal');
        
        let filteredLogs = data.logs;
        if (filterVal) {
            filteredLogs = data.logs.filter(log => 
                log.message.toLowerCase().includes(filterVal) || 
                log.level.toLowerCase().includes(filterVal)
            );
        }

        terminal.innerHTML = filteredLogs.map(log => {
            const time = log.created_at.split(' ')[1] || '';
            return `<div class="log-line-${log.level}"><span class="opacity-50">[${time}]</span> [${log.level.toUpperCase()}] ${escapeHtml(log.message)}</div>`;
        }).join('');

        // Auto-scroll to bottom
        if (logAutoScroll) {
            terminal.scrollTop = terminal.scrollHeight;
        }
    } catch (e) {}
}

function clearLogs() {
    if (logPollingInterval) clearInterval(logPollingInterval);
    activeLogJobId = null;
    document.getElementById('log-terminal').innerHTML = '<span class="log-line-debug">// سجل فارغ</span>';
    document.getElementById('log-job-label').textContent = '';
}

// ============================================================
// PAUSE
// ============================================================
async function pauseJob(jobId) {
    if (!confirm('هل تريد إيقاف هذه المهمة مؤقتاً؟')) return;

    const formData = new FormData();
    formData.append('job_id', jobId);
    formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

    try {
        const resp = await fetch('{{ route("sync.pause") }}', { method: 'POST', body: formData });
        const data = await resp.json();
        showToast(data.message || (data.success ? 'تم الإيقاف مؤقتاً' : 'فشل'), data.success ? 'success' : 'danger');
        pollQueue();
    } catch (e) {
        showToast('فشل الاتصال', 'danger');
    }
}

// ============================================================
// RETRY
// ============================================================
async function retryJob(jobId) {
    if (!confirm('هل تريد استئناف هذه المهمة من آخر نقطة تم حفظها؟')) return;

    const formData = new FormData();
    formData.append('job_id', jobId);
    formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

    try {
        const resp = await fetch('{{ route("sync.retry") }}', { method: 'POST', body: formData });
        const data = await resp.json();
        showToast(data.message || (data.success ? 'تم الاستئناف' : 'فشل'), data.success ? 'success' : 'danger');
        pollQueue();
    } catch (e) {
        showToast('فشل الاتصال', 'danger');
    }
}

// ============================================================
// CLEAR QUEUE
// ============================================================
async function clearQueue() {
    if (!confirm('حذف المهام المنتهية (أكثر من 24 ساعة)؟')) return;

    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

    try {
        const resp = await fetch('{{ route("sync.clear") }}', { method: 'POST', body: formData });
        const data = await resp.json();
        showToast(data.message, data.success ? 'success' : 'danger');
        pollQueue();
    } catch (e) {
        showToast('فشل الاتصال', 'danger');
    }
}

// ============================================================
// TOAST NOTIFICATION
// ============================================================
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

// ============================================================
// UTILS
// ============================================================
function escapeHtml(text) {
    return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ============================================================
// REFRESH DASHBOARD STATS
// ============================================================
async function refreshDashboardStats() {
    if (!confirm('هل تريد إعادة حساب وتحديث كل إحصائيات لوحة التحكم (KPIs) الآن؟ قد يستغرق ذلك بضع ثوانٍ.')) return;

    const btn = document.getElementById('btn-refresh-stats');
    if (!btn) return;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner spin me-1"></i> جاري التحديث...';

    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

    try {
        const resp = await fetch('{{ route("dashboard.stats.refresh") }}', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            showToast(data.message || 'تم تحديث إحصائيات لوحة التحكم بنجاح!', 'success');
        } else {
            showToast(data.error || 'فشل تحديث الإحصائيات', 'danger');
        }
    } catch (e) {
        showToast('فشل الاتصال بخادم التحديث', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

// Add CSS for toast animation
const style = document.createElement('style');
style.textContent = `@keyframes toastIn { from { opacity:0; transform:translateX(-50%) translateY(10px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
.btn-xs { padding: 2px 6px; font-size: 0.7rem; }`;
document.head.appendChild(style);
</script>

@endsection
