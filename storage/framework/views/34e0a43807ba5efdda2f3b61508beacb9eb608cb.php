
<?php $__env->startSection('title', $title ?? 'المنصة الرقمية للتكوين المهني'); ?>
<?php $__env->startSection('content'); ?>
<?php
/**
 * @var string $title
 * @var array $tables
 */

$flashError   = session('flash_error')    ?? null;
$flashSuccess  = session('flash_success')  ?? null;
$queryResults  = session('query_results')  ?? null;
$querySql      = session('query_sql')      ?? '';

session()->forget(['flash_error', 'flash_success', 'query_results', 'query_sql']);

$totalTables = count($tables);
$totalRows   = array_sum(array_column($tables, 'rows'));
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    .db-sidebar {
        max-height: calc(100vh - 80px);
        overflow-y: auto;
        position: sticky;
        top: 10px;
    }
    .table-btn {
        width: 100%;
        text-align: right;
        padding: 0.55rem 1rem;
        font-size: 0.82rem;
        font-family: 'Cairo', sans-serif;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-btn:hover {
        background: var(--primary-glow);
        color: var(--primary-color);
    }
    .table-btn.active {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: white !important;
    }
    .table-btn.active .row-badge {
        background: rgba(255,255,255,0.25) !important;
        color: white !important;
    }
    .row-badge {
        background: var(--bg-dashboard);
        color: var(--text-muted);
        border-radius: 20px;
        padding: 2px 8px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .crud-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: white;
        border-radius: 16px 16px 0 0;
        padding: 1.2rem 1.5rem;
    }
    .col-type-badge {
        font-size: 0.68rem;
        font-family: monospace;
        padding: 2px 6px;
        border-radius: 4px;
        background: var(--primary-glow);
        color: var(--primary-color);
    }
    .sql-textarea {
        font-family: 'Courier New', monospace;
        font-size: 0.88rem;
        border-radius: 12px;
        background: #0a0615;
        color: #a6e22e;
        border: 1px solid var(--border-color);
        padding: 1rem;
        width: 100%;
        resize: vertical;
        min-height: 110px;
    }
    .sql-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }
    #dataGrid td {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.82rem;
    }
    .stat-kpi {
        text-align: center;
        padding: 1.2rem;
        border-radius: 16px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
    }
    .stat-kpi .kpi-val {
        font-size: 2.2rem;
        font-weight: 900;
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .stat-kpi .kpi-label {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-weight: 700;
    }
    .search-overlay {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
    }
</style>

<div class="animate__animated animate__fadeIn" dir="rtl">
    <div class="container-fluid" style="max-width: 1800px; padding-left: 0; padding-right: 0;">

    <!-- ===== TOP HEADER ===== -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="font-family:'Cairo';color:var(--primary-color);">
                <i class="fa-solid fa-database me-2"></i> مدير البيانات والإدارة الشاملة
            </h3>
            <p class="text-muted mb-0 small">استعراض الجداول • CRUD الكامل • محطة SQL • التحليلات</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="/public/relations_report.html" target="_blank" class="btn btn-outline-primary rounded-pill px-3 py-1.5 fw-bold shadow-sm" style="font-size: 0.82rem; border-color: var(--primary-color);">
                <i class="fa-solid fa-diagram-project me-1"></i> مستكشف العلاقات والمخططات / Relations Schema
            </a>
            <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:var(--primary-glow);color:var(--primary-color);font-size:0.82rem;">
                <i class="fa-solid fa-table me-1"></i> <?= $totalTables ?> جدول
            </span>
            <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:var(--primary-glow);color:var(--primary-color);font-size:0.82rem;">
                <i class="fa-solid fa-database me-1"></i> <?= number_format($totalRows) ?> صف
            </span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($flashSuccess): ?>
        <div class="alert border-0 rounded-4 mb-3 d-flex align-items-center gap-2" style="background:rgba(16,185,129,0.1);color:#10b981;">
            <i class="fa-solid fa-circle-check fs-5"></i> <?= $flashSuccess ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert border-0 rounded-4 mb-3 d-flex align-items-center gap-2" style="background:rgba(239,68,68,0.1);color:#ef4444;">
            <i class="fa-solid fa-triangle-exclamation fs-5"></i> <?= htmlspecialchars($flashError) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== KPI ROW ===== -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-kpi">
                <div class="kpi-val"><?= $totalTables ?></div>
                <div class="kpi-label"><i class="fa-solid fa-table me-1"></i> إجمالي الجداول</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-kpi">
                <div class="kpi-val"><?= number_format($totalRows) ?></div>
                <div class="kpi-label"><i class="fa-solid fa-rows me-1"></i> إجمالي الصفوف</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-kpi" id="activeTableKpi">
                <div class="kpi-val" id="kpiActiveRows">—</div>
                <div class="kpi-label"><i class="fa-solid fa-eye me-1"></i> صفوف الجدول المحدد</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-kpi" id="activeTableKpi2">
                <div class="kpi-val" id="kpiActiveCols">—</div>
                <div class="kpi-label"><i class="fa-solid fa-columns me-1"></i> أعمدة الجدول المحدد</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ===== SIDEBAR: TABLES LIST ===== -->
        <div class="col-12 col-lg-3 col-xl-2 mb-4">
            <div class="card border-0 shadow-sm rounded-4 db-sidebar">
                <div class="card-header border-0 pt-3 px-3 pb-2">
                    <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-folder-tree text-primary me-2"></i> الجداول</h6>
                    <div class="position-relative">
                        <input type="text" class="form-control rounded-pill bg-light border-light pe-4" id="tableSearch"
                               placeholder="ابحث عن جدول...">
                        <i class="fa-solid fa-magnifying-glass search-overlay" style="right:auto;left:12px;"></i>
                    </div>
                </div>
                <div class="card-body p-2" id="tablesList">
                    <?php foreach ($tables as $t): ?>
                        <button class="table-btn mb-1" onclick="openTable('<?= htmlspecialchars($t['name']) ?>', <?= $t['rows'] ?>)">
                            <span><i class="fa-solid fa-table-cells me-2" style="opacity:0.5;"></i><?= htmlspecialchars($t['name']) ?></span>
                            <span class="row-badge"><?= $t['rows'] >= 0 ? number_format($t['rows']) : '?' ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ===== MAIN AREA ===== -->
        <div class="col-12 col-lg-9 col-xl-10">

            <!-- SQL Console -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header border-0 px-4 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-terminal text-primary me-2"></i> محطة أوامر SQL المباشرة</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="insertSnippet('SELECT * FROM `TABLE` LIMIT 10;')">SELECT</button>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="insertSnippet('DESCRIBE `TABLE`;')">DESCRIBE</button>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="insertSnippet('ALTER TABLE `TABLE` ADD COLUMN `new_col` VARCHAR(100) NULL;')">ALTER</button>
                        <button class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="insertSnippet('CREATE TABLE `new_table` (\n  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,\n  `Nom` VARCHAR(100),\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB;')">CREATE</button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="/dashboard/database/query" method="POST" class="mt-2">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                        <textarea class="sql-textarea mb-3" id="sqlInput" name="sql" placeholder="-- اكتب استعلام SQL هنا..."><?= htmlspecialchars($querySql) ?></textarea>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="fa-solid fa-shield-halved me-1"></i> جميع الاستعلامات تُسجَّل في سجل الأمان</small>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-play me-2"></i> تنفيذ / Exécuter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SQL Query Results (if any) -->
            <?php if ($queryResults !== null): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 animate__animated animate__fadeInDown">
                <div class="card-header border-0 px-4 pt-3 pb-0 d-flex justify-content-between">
                    <h6 class="fw-bold mb-0" style="color:var(--secondary-color);">
                        <i class="fa-solid fa-table-list me-2"></i> نتائج الاستعلام
                    </h6>
                    <span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background:rgba(16,185,129,0.1);color:#10b981;">
                        <?= count($queryResults) ?> صف
                    </span>
                </div>
                <div class="card-body p-0 mt-2">
                    <?php if (empty($queryResults)): ?>
                        <div class="p-4 text-center text-muted">لا توجد بيانات مرجعة من الاستعلام.</div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height:350px;overflow-y:auto;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead>
                                    <tr class="bg-light">
                                        <?php foreach (array_keys($queryResults[0]) as $col): ?>
                                            <th class="fw-bold text-muted" style="white-space:nowrap;"><?= htmlspecialchars($col) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queryResults as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $val): ?>
                                                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($val ?? '') ?>">
                                                    <?= htmlspecialchars($val !== null ? $val : 'NULL') ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- CRUD DATA EXPLORER -->
            <div class="card border-0 shadow-sm rounded-4" id="crudCard">
                <!-- Empty state -->
                <div class="card-body p-5 text-center" id="crudPlaceholder">
                    <div style="width:80px;height:80px;background:var(--primary-glow);border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                        <i class="fa-solid fa-database" style="font-size:2rem;color:var(--primary-color);"></i>
                    </div>
                    <h5 class="fw-bold text-dark">حدد جدولاً من القائمة الجانبية</h5>
                    <p class="text-muted small">انقر فوق أي جدول لاستعراض بياناته وإدارتها بشكل كامل (إضافة، تعديل، حذف)</p>
                </div>

                <!-- Active CRUD Panel -->
                <div id="crudPanel" class="d-none">
                    <div class="crud-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1" id="crudTableTitle">—</h5>
                            <small id="crudTableMeta" style="opacity:0.8;"></small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm fw-bold rounded-pill px-3" style="background:rgba(255,255,255,0.15);color:white;border:1px solid rgba(255,255,255,0.3);" onclick="refreshTable()">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> تحديث
                            </button>
                            <button class="btn btn-sm fw-bold rounded-pill px-3" style="background:rgba(255,255,255,0.25);color:white;" onclick="openInsertModal()">
                                <i class="fa-solid fa-plus me-1"></i> إضافة صف جديد
                            </button>
                        </div>
                    </div>

                    <!-- Search + Pagination Controls -->
                    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom" style="border-color:var(--border-color) !important;">
                        <div class="position-relative" style="width:300px;">
                            <input type="text" class="form-control rounded-pill bg-light border-light pe-4" id="rowSearchInput"
                                   placeholder="🔍 بحث في البيانات..." oninput="debounceSearch()">
                        </div>
                        <div id="paginationInfo" class="text-muted small fw-bold"></div>
                    </div>

                    <!-- Data Table -->
                    <div class="table-responsive px-0" style="max-height:450px;overflow-y:auto;">
                        <table class="table table-hover align-middle mb-0" id="crudDataTable">
                            <thead id="crudTableHead" style="position:sticky;top:0;z-index:5;background:var(--card-bg);"></thead>
                            <tbody id="crudTableBody">
                                <tr><td colspan="10" class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top" style="border-color:var(--border-color) !important;">
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" id="prevPageBtn" onclick="changePage(-1)">
                            <i class="fa-solid fa-chevron-right me-1"></i> السابق
                        </button>
                        <div id="pageNumbers" class="d-flex gap-1"></div>
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" id="nextPageBtn" onclick="changePage(1)">
                            التالي <i class="fa-solid fa-chevron-left ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>
</div>

<!-- ========== INSERT / EDIT MODAL ========== -->
<div class="modal fade" id="crudModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="crudModalTitle">إضافة صف جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="row g-3" id="crudFormFields"><!-- Dynamic form fields --></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-1">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="crudSaveBtn" onclick="saveCrudRow()">
                    <i class="fa-solid fa-floppy-disk me-2"></i> حفظ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== DB ROW DETAILS MODAL ========== -->
<div class="modal fade" id="dbRowDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="font-family:'Cairo';">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-circle-info text-primary me-2"></i> تفاصيل السجل / Détails du Saccard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">العمود / Colonne</th>
                                <th style="width: 75%;">القيمة والمعاينة / Valeur & Aperçu</th>
                            </tr>
                        </thead>
                        <tbody id="dbRowDetailsBody">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-1">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
            </div>
        </div>
    </div>
</div>

<!-- ========== DELETE CONFIRM MODAL ========== -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body p-5 text-center">
                <div style="width:70px;height:70px;background:rgba(239,68,68,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;">
                    <i class="fa-solid fa-trash-can fs-2 text-danger"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">تأكيد الحذف النهائي</h5>
                <p class="text-muted mb-1 small" id="deleteConfirmMsg">هل أنت متأكد من حذف هذا الصف؟ لا يمكن التراجع عن هذه العملية.</p>
                <div class="d-flex gap-3 justify-content-center mt-4">
                    <button class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button class="btn btn-danger rounded-pill px-4 fw-bold" id="confirmDeleteBtn">
                        <i class="fa-solid fa-trash-can me-2"></i> حذف نهائي
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
/* ============================
   GLOBAL STATE
   ============================ */
let activeTable   = null;
let activeColumns = [];
let activePkCol   = null;
let currentPage   = 1;
let totalPages    = 1;
let deleteTarget  = null;
let searchTimeout = null;
let isEditMode    = false;
let editRowData   = null;

/* ============================
   TABLE SIDEBAR SEARCH
   ============================ */
document.getElementById('tableSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.table-btn').forEach(btn => {
        btn.style.display = btn.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

/* ============================
   OPEN TABLE
   ============================ */
function openTable(name, rows) {
    activeTable = name;
    currentPage = 1;

    // Update sidebar highlight
    document.querySelectorAll('.table-btn').forEach(b => b.classList.remove('active'));
    event.currentTarget.classList.add('active');

    // Update KPIs
    document.getElementById('kpiActiveRows').textContent = rows >= 0 ? rows.toLocaleString('ar-DZ') : '—';

    // Show CRUD panel
    document.getElementById('crudPlaceholder').classList.add('d-none');
    document.getElementById('crudPanel').classList.remove('d-none');
    document.getElementById('crudTableTitle').innerHTML = '<i class="fa-solid fa-table-cells me-2"></i>' + name;

    loadTableData();
}

function refreshTable() {
    loadTableData();
}

/* ============================
   LOAD DATA (PAGINATED)
   ============================ */
function loadTableData() {
    if (!activeTable) return;
    const search = document.getElementById('rowSearchInput')?.value ?? '';
    const url = `/sig/dashboard/database/data?table=${encodeURIComponent(activeTable)}&page=${currentPage}&q=${encodeURIComponent(search)}`;

    document.getElementById('crudTableBody').innerHTML = `
        <tr><td colspan="20" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0 small">جاري تحميل البيانات...</p>
        </td></tr>`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                showToast('error', 'خطأ: ' + data.message);
                return;
            }

            activeColumns = data.columns;
            document.getElementById('kpiActiveCols').textContent = data.columns.length;

            // Detect primary key
            activePkCol = null;
            for (const col of data.columns) {
                if (col.Key === 'PRI') { activePkCol = col.Field; break; }
            }
            if (!activePkCol && data.columns.length > 0) activePkCol = data.columns[0].Field;

            renderTableHead(data.columns);
            renderTableBody(data.rows);
            renderPagination(data.pagination);

            const meta = `${data.pagination.total_rows.toLocaleString('ar-DZ')} صف • ${data.columns.length} عمود`;
            document.getElementById('crudTableMeta').textContent = meta;
        })
        .catch(err => {
            showToast('error', 'خطأ في الاتصال: ' + err.message);
        });
}

function renderTableHead(columns) {
    let th = '<tr class="text-muted" style="font-size:0.78rem;font-weight:700;">';
    th += '<th style="width:80px;white-space:nowrap;">الإجراءات</th>';
    for (const col of columns) {
        const isPk = col.Key === 'PRI';
        th += `<th style="white-space:nowrap;">
            ${isPk ? '<i class="fa-solid fa-key text-warning me-1" style="font-size:0.7rem;"></i>' : ''}
            ${escHtml(col.Field)}
            <span class="col-type-badge ms-1">${escHtml(col.Type)}</span>
        </th>`;
    }
    th += '</tr>';
    document.getElementById('crudTableHead').innerHTML = th;
}

function renderTableBody(rows) {
    if (!rows || rows.length === 0) {
        document.getElementById('crudTableBody').innerHTML = `
            <tr><td colspan="${activeColumns.length + 1}" class="text-center py-5 text-muted">
                <i class="fa-solid fa-inbox fs-2 d-block mb-2 opacity-50"></i>
                لا توجد بيانات في هذا الجدول
            </td></tr>`;
        return;
    }

    let html = '';
    for (const row of rows) {
        const pkVal = row[activePkCol] ?? '';
        html += '<tr>';
        // Actions
        html += `<td style="white-space:nowrap;">
            <button class="btn btn-sm rounded-circle" style="width:28px;height:28px;background:var(--primary-glow);color:var(--primary-color);border:none;" onclick="viewDbRowDetails(${escJson(row)})" title="عرض التفاصيل">
                <i class="fa-solid fa-eye" style="font-size:0.65rem;"></i>
            </button>
            <button class="btn btn-sm rounded-circle ms-1" style="width:28px;height:28px;background:var(--primary-glow);color:var(--primary-color);border:none;" onclick="openEditModal(${escJson(row)})" title="تعديل">
                <i class="fa-solid fa-pen" style="font-size:0.65rem;"></i>
            </button>
            <button class="btn btn-sm rounded-circle ms-1" style="width:28px;height:28px;background:rgba(239,68,68,0.1);color:#ef4444;border:none;" onclick="confirmDelete('${escHtml(pkVal)}')" title="حذف">
                <i class="fa-solid fa-trash" style="font-size:0.65rem;"></i>
            </button>
        </td>`;
        // Data cells
        for (const col of activeColumns) {
            const val = row[col.Field];
            const display = val !== null ? String(val) : '<span class="text-muted opacity-50" style="font-style:italic;">NULL</span>';
            html += `<td title="${escHtml(val ?? 'NULL')}" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${display}</td>`;
        }
        html += '</tr>';
    }
    document.getElementById('crudTableBody').innerHTML = html;
}

function renderPagination(pag) {
    totalPages  = pag.total_pages;
    currentPage = pag.current_page;

    document.getElementById('paginationInfo').textContent =
        `صفحة ${pag.current_page} من ${pag.total_pages} (${pag.total_rows.toLocaleString('ar-DZ')} صف)`;

    document.getElementById('prevPageBtn').disabled = currentPage <= 1;
    document.getElementById('nextPageBtn').disabled = currentPage >= totalPages;

    // Page number bubbles (max 5 shown)
    let html = '';
    const start = Math.max(1, currentPage - 2);
    const end   = Math.min(totalPages, start + 4);
    for (let p = start; p <= end; p++) {
        const active = p === currentPage ? 'background:var(--primary-color);color:white;' : 'background:var(--bg-dashboard);color:var(--text-muted);';
        html += `<button onclick="goToPage(${p})" class="btn btn-sm rounded-circle fw-bold" style="width:32px;height:32px;font-size:0.78rem;${active}">${p}</button>`;
    }
    document.getElementById('pageNumbers').innerHTML = html;
}

function changePage(dir) {
    const np = currentPage + dir;
    if (np >= 1 && np <= totalPages) { currentPage = np; loadTableData(); }
}
function goToPage(p) { currentPage = p; loadTableData(); }

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { currentPage = 1; loadTableData(); }, 400);
}

/* ============================
   INSERT MODAL
   ============================ */
function openInsertModal() {
    isEditMode = false;
    editRowData = null;
    document.getElementById('crudModalTitle').innerHTML = '<i class="fa-solid fa-plus-circle text-primary me-2"></i> إضافة صف جديد في «' + activeTable + '»';
    buildForm({});
    new bootstrap.Modal(document.getElementById('crudModal')).show();
}

function openEditModal(rowData) {
    isEditMode = true;
    editRowData = rowData;
    document.getElementById('crudModalTitle').innerHTML = '<i class="fa-solid fa-pen text-primary me-2"></i> تعديل صف في «' + activeTable + '»';
    buildForm(rowData);
    new bootstrap.Modal(document.getElementById('crudModal')).show();
}

function buildForm(rowData) {
    let html = '';
    for (const col of activeColumns) {
        const val = rowData[col.Field] ?? '';
        const isPk = col.Key === 'PRI';
        const isAutoInc = col.Extra && col.Extra.includes('auto_increment');
        const disabled = (isEditMode && isPk) ? 'readonly style="background:var(--bg-dashboard);cursor:not-allowed;"' : '';
        const hint = isPk ? (isAutoInc ? '<small class="text-warning"><i class="fa-solid fa-key me-1"></i>مفتاح أساسي — تلقائي</small>' : '<small class="text-warning"><i class="fa-solid fa-key me-1"></i>مفتاح أساسي</small>') : '';

        html += `<div class="col-md-4">
            <label class="form-label fw-bold small text-muted">${escHtml(col.Field)} <span class="col-type-badge">${escHtml(col.Type)}</span></label>
            ${hint}
            <input type="text" class="form-control rounded-3 bg-light border-light mt-1 crud-field"
                   name="${escHtml(col.Field)}" data-col="${escHtml(col.Field)}"
                   value="${escHtml(String(val === null ? '' : val))}"
                   placeholder="${col.Null === 'YES' ? 'NULL' : 'مطلوب'}"
                   ${disabled}>
        </div>`;
    }
    document.getElementById('crudFormFields').innerHTML = html;
}

/* ============================
   SAVE (INSERT / UPDATE)
   ============================ */
function saveCrudRow() {
    const fields = {};
    document.querySelectorAll('.crud-field').forEach(input => {
        fields[input.dataset.col] = input.value;
    });

    const formData = new FormData();
    formData.append('table', activeTable);
    formData.append('csrf_token', '<?= csrf_token() ?? '' ?>');
    for (const [k, v] of Object.entries(fields)) {
        formData.append(`fields[${k}]`, v);
    }

    let url = '/sig/dashboard/database/insert';
    if (isEditMode) {
        url = '/sig/dashboard/database/update';
        formData.append('pk_column', activePkCol);
        formData.append('pk_val', editRowData[activePkCol]);
    }

    document.getElementById('crudSaveBtn').disabled = true;
    document.getElementById('crudSaveBtn').innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-2"></i> جاري الحفظ...';

    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('crudModal')).hide();
            document.getElementById('crudSaveBtn').disabled = false;
            document.getElementById('crudSaveBtn').innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> حفظ';
            if (data.success) {
                showToast('success', data.message);
                loadTableData();
            } else {
                showToast('error', data.message);
            }
        })
        .catch(err => {
            showToast('error', 'خطأ: ' + err.message);
            document.getElementById('crudSaveBtn').disabled = false;
            document.getElementById('crudSaveBtn').innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> حفظ';
        });
}

/* ============================
   DELETE
   ============================ */
function confirmDelete(pkVal) {
    deleteTarget = pkVal;
    document.getElementById('deleteConfirmMsg').textContent =
        `هل أنت متأكد من حذف الصف رقم "${pkVal}" من جدول "${activeTable}"؟ لا يمكن التراجع عن هذه العملية.`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
    if (!deleteTarget || !activeTable) return;

    const formData = new FormData();
    formData.append('table', activeTable);
    formData.append('pk_column', activePkCol);
    formData.append('pk_val', deleteTarget);
    formData.append('csrf_token', '<?= csrf_token() ?? '' ?>');

    fetch('/sig/dashboard/database/delete', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            if (data.success) {
                showToast('success', data.message);
                loadTableData();
            } else {
                showToast('error', data.message);
            }
        })
        .catch(err => showToast('error', 'خطأ: ' + err.message));
});

/* ============================
   SQL CONSOLE HELPERS
   ============================ */
function insertSnippet(snippet) {
    const ta = document.getElementById('sqlInput');
    const name = activeTable || 'TABLE';
    ta.value = snippet.replace(/TABLE/g, name);
    ta.focus();
}

/* ============================
   HELPERS
   ============================ */
function showToast(type, msg) {
    const icon = type === 'success' ? 'success' : 'error';
    Swal.fire({ toast: true, position: 'top-end', icon, title: msg, showConfirmButton: false, timer: 3000, timerProgressBar: true });
}

function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function escJson(obj) {
    return "'" + JSON.stringify(obj).replace(/'/g, "&#039;") + "'";
}

function openEditModal(rowDataJson) {
    try {
        const rowData = typeof rowDataJson === 'string' ? JSON.parse(rowDataJson) : rowDataJson;
        isEditMode = true;
        editRowData = rowData;
        document.getElementById('crudModalTitle').innerHTML = '<i class="fa-solid fa-pen text-primary me-2"></i> تعديل صف في «' + activeTable + '»';
        buildForm(rowData);
        new bootstrap.Modal(document.getElementById('crudModal')).show();
    } catch(e) {
        showToast('error', 'خطأ في فتح نموذج التعديل');
    }
}

function viewDbRowDetails(row) {
    let html = '';
    
    // Helper to check if a value is a file/image path
    function getFilePreviewHtml(val) {
        if (!val || typeof val !== 'string') return '';
        const lowerVal = val.toLowerCase().trim();
        
        // Detect extensions
        const isImage = lowerVal.endsWith('.png') || lowerVal.endsWith('.jpg') || lowerVal.endsWith('.jpeg') || lowerVal.endsWith('.gif') || lowerVal.endsWith('.svg') || lowerVal.endsWith('.webp') || lowerVal.startsWith('data:image/');
        const isPdf = lowerVal.endsWith('.pdf');
        
        // Compute path prefix if relative
        let resolvedPath = val;
        if (!lowerVal.startsWith('http://') && !lowerVal.startsWith('https://') && !lowerVal.startsWith('data:')) {
            // If it doesn't have slash prefix, make sure it does
            let cleanVal = val.replace(/^\/+/, '');
            resolvedPath = `/sig/${cleanVal}`;
        }
        
        if (isImage) {
            return `<div class="mt-2">
                <img src="${resolvedPath}" class="img-thumbnail img-fluid rounded" style="max-height: 250px; cursor: pointer;" onclick="window.open('${resolvedPath}')" alt="Preview">
                <div class="small text-muted mt-1"><i class="fa-solid fa-image me-1"></i>انقر للمعاينة بحجمها الكامل</div>
            </div>`;
        }
        
        if (isPdf) {
            return `<div class="mt-2 border rounded p-2" style="background:#f8f9fa;">
                <iframe src="${resolvedPath}" style="width:100%; height:350px;" border="0"></iframe>
                <div class="small text-muted mt-1"><i class="fa-solid fa-file-pdf me-1"></i>معاينة مستند PDF داخل الصفحة</div>
            </div>`;
        }
        
        // If it starts with uploads/ or has doc-like extensions, assume general file
        const isGeneralFile = lowerVal.startsWith('uploads/') || lowerVal.includes('/uploads/') || lowerVal.endsWith('.doc') || lowerVal.endsWith('.docx') || lowerVal.endsWith('.xls') || lowerVal.endsWith('.xlsx');
        if (isGeneralFile) {
            return `<div class="mt-2 p-2 border rounded d-inline-block" style="background:#f8f9fa;">
                <a href="${resolvedPath}" target="_blank" class="btn btn-sm btn-outline-primary fw-bold">
                    <i class="fa-solid fa-download me-1"></i> تحميل / فتح الملف (${val.split('/').pop()})
                </a>
            </div>`;
        }
        
        return '';
    }

    for (const col of activeColumns) {
        const val = row[col.Field];
        const valStr = val !== null ? escHtml(String(val)) : '<span class="text-muted opacity-50" style="font-style:italic;">NULL</span>';
        
        let preview = '';
        if (val !== null) {
            preview = getFilePreviewHtml(String(val));
        }

        html += `<tr>
            <td class="fw-bold">
                ${escHtml(col.Field)}
                <span class="col-type-badge d-block mt-1" style="font-size:0.6rem; width:fit-content;">${escHtml(col.Type)}</span>
            </td>
            <td>
                <div class="text-dark font-monospace" style="word-break: break-all; font-size:0.88rem;">${valStr}</div>
                ${preview}
            </td>
        </tr>`;
    }
    
    document.getElementById('dbRowDetailsBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('dbRowDetailsModal')).show();
}
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sig\resources\views/admin/database/index.blade.php ENDPATH**/ ?>