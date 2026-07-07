@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')

@section('styles')
<style>
    /* Design Variables and System Tokens */
    :root {
        --transition-premium: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow-premium: 0 10px 30px -10px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-premium-hover: 0 20px 40px -15px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .stat-card {
        border-radius: 20px;
        border: 1px solid rgba(0, 0, 0, 0.03);
        transition: var(--transition-premium);
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.04);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-premium-hover) !important;
    }
    
    .stat-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-premium);
    }
    .stat-card:hover .stat-icon-wrapper {
        transform: scale(1.1) rotate(6deg);
    }

    .form-control-premium {
        border-radius: 12px !important;
        border: 1.5px solid #e2e8f0;
        padding: 0.65rem 1rem;
        transition: var(--transition-premium);
        font-size: 0.88rem;
        background-color: #f8fafc;
    }
    .form-control-premium:focus {
        border-color: #643edb;
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(100, 62, 219, 0.12);
        outline: none;
    }
    select.form-control-premium {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: left 0.75rem center;
        background-size: 16px 12px;
        padding-left: 2.25rem;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    /* Column sorting headers */
    .sortable-header {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.2s ease;
    }
    .sortable-header:hover {
        background-color: rgba(100, 62, 219, 0.05) !important;
        color: #643edb !important;
    }
    
    /* Custom pagination styling */
    .pagination .page-item .page-link {
        border-radius: 8px;
        margin: 0 2px;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 600;
        transition: var(--transition-premium);
    }
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #482b8f 0%, #643edb 100%);
        border-color: transparent;
        color: #fff;
    }
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: rgba(100, 62, 219, 0.08);
        border-color: #643edb;
        color: #643edb;
    }

    /* Modal styling */
    .modal-content-glass {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    .modal-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    /* Arabic text right aligns */
    .text-right {
        text-align: right !important;
    }
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn" style="font-family: 'Cairo', sans-serif;">
    
    <!-- Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert" style="border-radius: 12px; background: #e6f7ed; color: #006233;">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-4 me-2"></i>
                <div class="fw-semibold"><?= session('flash_success');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert" style="border-radius: 12px; background: #fdf2f2; color: #9b1c1c;">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
                <div class="fw-semibold"><?= session('flash_error');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Sovereign Title and Header toolbar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-3 border-bottom border-secondary border-opacity-10 gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-handshake text-purple-600 me-2" style="color: #643edb;"></i> الإدماج المهني والتنسيق مع المؤسسات الاقتصادية / Insertion
            </h3>
            <p class="text-muted small mb-0">متابعة خريجي قطاع التكوين المهني وعقود الإدماج بالتنسيق مع الشركاء الاقتصاديين والقطاع المستخدم</p>
        </div>
        <div class="d-flex gap-2">
            <?php 
            $isRestrictedRole = in_array($scope['role'] ?? '', ['etablissement', 'directeur', 'formateur', 'employee']);
            if (!$isRestrictedRole): ?>
                <div class="d-flex align-items-center gap-2">
                    <label for="wilaya_filter" class="small fw-bold text-muted text-nowrap"><i class="fa-solid fa-map-location-dot me-1"></i> الولاية:</label>
                    <select id="wilaya_filter" class="form-select rounded-pill border-light-subtle shadow-sm px-3 small" style="width: 180px;" onchange="filterWilaya(this.value)">
                        <option value="">كل الولايات</option>
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= ($wilayaId ?? '') == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($etablissements)): ?>
                    <div class="d-flex align-items-center gap-2 ms-2">
                        <label for="etab_filter" class="small fw-bold text-muted text-nowrap"><i class="fa-solid fa-hotel me-1"></i> المؤسسة:</label>
                        <select id="etab_filter" class="form-select rounded-pill border-light-subtle shadow-sm px-3 small" style="width: 250px;" onchange="filterEtab(this.value)">
                            <option value="">كل المؤسسات التكوينية</option>
                            <?php foreach ($etablissements as $etab): ?>
                                <option value="<?= $etab['id'] ?>" <?= $selected_etab == $etab['id'] ? 'selected' : '' ?>><?= htmlspecialchars($etab['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            <?php elseif ($isRestrictedRole && !empty($etablissements)): ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-2" style="background:linear-gradient(135deg,#482b8f,#643edb);font-size:0.82rem;">
                        <i class="fa-solid fa-lock me-1"></i>
                        <?= htmlspecialchars($etablissements[0]['nom_ar'] ?? 'مؤسستك') ?>
                    </span>
                </div>
            <?php endif; ?>

            <button class="btn btn-primary d-flex align-items-center px-4 fw-bold shadow-sm" style="border-radius: 10px; background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;" data-bs-toggle="modal" data-bs-target="#addAgreementModal">
                <i class="fa-solid fa-file-contract me-2"></i> إدراج اتفاقية أو عقد إدماج
            </button>
        </div>
    </div>

    <!-- Stats Panel (Fixed visibility issues and styled beautifully) -->
    <div class="row g-4 mb-4">
        <!-- 1. Total integration contracts (Green theme, visible) -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white stat-card">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fw-bold small d-block mb-1">إجمالي عقود الإدماج والاتفاقيات</span>
                        <h2 class="display-6 fw-bold my-1 text-success" style="font-family: 'Outfit';"><?= number_format($stats['total_integres']) ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i>نشطة وجارية حالياً</span>
                    </div>
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="fa-solid fa-file-invoice-dollar fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- 2. Apprenticeship contracts (Blue theme) -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white stat-card">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fw-bold small d-block mb-1">عقود التمهين النشطة (Apprentissage)</span>
                        <h2 class="display-6 fw-bold my-1 text-primary" style="font-family: 'Outfit';"><?= number_format($stats['apprentissage']) ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-building text-primary me-1"></i>مسجلة لفائدة المتربصين</span>
                    </div>
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="fa-solid fa-graduation-cap fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- 3. CDI/CDD employment agreements (Purple/Dark theme) -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white stat-card">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fw-bold small d-block mb-1">عقود التوظيف والشراكات (CDD/CDI)</span>
                        <h2 class="display-6 fw-bold my-1 text-dark" style="font-family: 'Outfit';"><?= number_format($stats['cdd_cdi']) ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-briefcase text-secondary me-1"></i>شراكات وعقود تشغيل مباشرة</span>
                    </div>
                    <div class="stat-icon-wrapper bg-secondary-subtle text-dark">
                        <i class="fa-solid fa-briefcase fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Table and Search Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        
        <!-- Header & Export Panel -->
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Cairo';">
                <i class="fa-solid fa-shield-halved text-purple-600 me-2" style="color: #643edb;"></i> سجل عقود الإدماج والشراكات الاقتصادية المبرمة
            </h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportListToExcel()" class="btn btn-sm btn-success rounded-pill px-3.5 py-1.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-file-excel"></i> تصدير إكسيل / Excel (الكل)
                </button>
                <button onclick="exportListToCSV()" class="btn btn-sm btn-info text-white rounded-pill px-3.5 py-1.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-file-csv"></i> تصدير CSV (الكل)
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3.5 py-1.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-print"></i> طباعة / Print
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            
            <!-- Premium Filter Console -->
            <div class="row g-3 align-items-center mb-4 p-3 rounded-4 bg-light border border-opacity-25" style="border-color: #cbd5e1;">
                <div class="col-12 col-md-4">
                    <div class="input-group bg-white rounded-3 border border-1" style="overflow:hidden;">
                        <span class="input-group-text bg-white border-0 ps-3 pe-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="searchFilter" class="form-control border-0 py-2.5 ps-2 shadow-none" style="font-size: 0.88rem;" placeholder="بحث باسم المستفيد، الشريك، أو التخصص...">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <select id="filterType" class="form-select border-1 py-2.5 px-3 rounded-3 form-control-premium bg-white shadow-none">
                        <option value="">كل طبيعة العقود (Toutes)</option>
                        <option value="convention">اتفاقية شراكة وتعاون</option>
                        <option value="apprentissage">عقد تمهين وتكوين تطبيقي</option>
                        <option value="cdd">عقد عمل محدد المدة (CDD)</option>
                        <option value="cdi">عقد عمل غير محدد المدة (CDI)</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select id="filterStatus" class="form-select border-1 py-2.5 px-3 rounded-3 form-control-premium bg-white shadow-none">
                        <option value="">كل الحالات (Statuts)</option>
                        <option value="actif">سارية المفعول / Actif</option>
                        <option value="expire">منتهية الصلاحية / Expiré</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex justify-content-between align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2 fs-7 rounded-pill fw-bold" id="itemCountBadge">تحميل...</span>
                    <button id="resetFilters" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" title="إعادة تعيين الفلاتر" style="width:38px; height:38px; border-width: 1.5px;">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>

            <!-- Table Component -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="integrTable">
                    <thead class="table-light">
                        <tr class="fw-bold text-muted border-bottom" style="font-size:0.85rem; border-color: #e2e8f0;">
                            <th class="sortable-header ps-4 py-3" data-col="nom_ar" style="width: 30%;">المستفيد / موضوع الاتفاقية <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                            <th class="sortable-header py-3" data-col="employeur_nom" style="width: 25%;">المؤسسة الاقتصادية / الشريك <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                            <th class="sortable-header text-center py-3" data-col="type_contrat" style="width: 20%;">طبيعة العقد / الاتفاقية <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                            <th class="sortable-header text-center py-3" data-col="date_debut" style="width: 10%;">تاريخ المباشرة <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                            <th class="text-center py-3" style="width: 8%;">الحالة</th>
                            <th class="pe-4 text-end no-print no-export py-3" style="width: 7%;">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS Dynamic Rows -->
                    </tbody>
                </table>
            </div>

            <!-- Premium Pagination Footer -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top gap-3" style="border-color: #f1f5f9 !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">عرض</span>
                    <select id="pageSize" class="form-select form-select-sm border shadow-none" style="width: 80px; border-radius: 8px; font-weight: 600; padding: 0.35rem 1.5rem 0.35rem 0.5rem;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">الكل</option>
                    </select>
                    <span class="text-muted small">سجل في الصفحة</span>
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationCtrl">
                        <!-- JS Pagination -->
                    </ul>
                </nav>
            </div>

        </div>
    </div>
</div>

<!-- Add Agreement Modal -->
<div class="modal fade" id="addAgreementModal" tabindex="-1" aria-labelledby="addAgreementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addAgreementModalLabel" style="color: var(--color-gov-purple-dark, #482b8f); font-family: 'Cairo';">
                    <i class="fa-solid fa-file-contract text-primary me-2"></i> إدراج اتفاقية أو عقد إدماج جديد
                </h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/integration/store" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="stagiaire_id" class="form-label small fw-bold text-dark">الطالب المستفيد (اختياري، يترك فارغاً للاتفاقيات العامة)</label>
                        <select class="form-select form-control-premium" id="stagiaire_id" name="stagiaire_id">
                            <option value="">-- اتفاقية إطار مؤسساتية عامة --</option>
                            <?php foreach ($stagiaires as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom_ar'] . ' ' . $s['prenom_ar'] . ' - ' . ($s['numero_matricule'] ?? 'بدون رقم')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="employeur_nom" class="form-label small fw-bold text-dark">الشريك الاقتصادي / المؤسسة أو موضوع الاتفاقية *</label>
                        <input type="text" class="form-control form-control-premium" id="employeur_nom" name="employeur_nom" placeholder="مثال: شركة اتصالات الجزائر - المديرية العامة" required>
                    </div>
                    <div class="mb-3">
                        <label for="type_contrat" class="form-label small fw-bold text-dark">طبيعة العقد أو الاتفاقية *</label>
                        <select class="form-select form-control-premium" id="type_contrat" name="type_contrat" required>
                            <option value="convention" selected>اتفاقية شراكة وتعاون (Convention)</option>
                            <option value="apprentissage">عقد تمهين وتكوين تطبيقي (Apprentissage)</option>
                            <option value="cdd">عقد عمل محدد المدة (CDD)</option>
                            <option value="cdi">عقد عمل غير محدد المدة (CDI)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date_debut" class="form-label small fw-bold text-dark">تاريخ سريان الاتفاقية أو بدء العمل *</label>
                        <input type="date" class="form-control form-control-premium" id="date_debut" name="date_debut" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="statut" class="form-label small fw-bold text-dark">الحالة *</label>
                        <select class="form-select form-control-premium" id="statut" name="statut">
                            <option value="actif" selected>نشطة / سارية</option>
                            <option value="expire">منتهية الصلاحية</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius:10px; background:linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; font-weight: 600;">حفظ وإدراج</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Logic Block -->
<script>
    // Data list passed from Laravel DB
    const listData = <?= json_encode($list) ?>;

    // Filters and sorting state objects
    let state = {
        search: '',
        type: '',
        status: '',
        sortBy: 'id',
        sortOrder: 'desc',
        currentPage: 1,
        pageSize: 10
    };

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function validateForm() {
        const studentSelect = document.getElementById('stagiaire_id');
        const typeSelect = document.getElementById('type_contrat');
        
        // Validation: If it's an employment contract (cdd/cdi/apprentissage), require a student to be selected
        if (['cdd', 'cdi', 'apprentissage'].includes(typeSelect.value) && studentSelect.value === '') {
            alert('يرجى تحديد الطالب المستفيد من عقد العمل أو التمهين. لا يمكن تركه فارغاً إلا في حال كانت اتفاقية إطار عامة.');
            return false;
        }
        return true;
    }

    // Dynamic Row Actions
    function getActionsHtml(item) {
        const csrf = '<?= csrf_token() ?? "" ?>';
        return `
            <form action="/dashboard/integration/delete/${item.id}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد / الاتفاقية؟')">
                <input type="hidden" name="csrf_token" value="${csrf}">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fa-solid fa-trash me-1"></i> حذف</button>
            </form>
        `;
    }

    // Render integration table rows
    function renderTable() {
        const tbody = document.querySelector('#integrTable tbody');
        if (!tbody) return;

        // Filtering
        let filtered = listData.filter(item => {
            const fullname = (item.nom_ar || '') + ' ' + (item.prenom_ar || '');
            const matchSearch = !state.search ||
                (fullname.includes(state.search)) ||
                (item.numero_matricule && item.numero_matricule.toLowerCase().includes(state.search.toLowerCase())) ||
                (item.employeur_nom && item.employeur_nom.toLowerCase().includes(state.search.toLowerCase())) ||
                (item.spec_ar && item.spec_ar.includes(state.search));

            const matchType = !state.type || item.type_contrat === state.type;
            const matchStatus = !state.status || item.statut === state.status;

            return matchSearch && matchType && matchStatus;
        });

        // Sorting
        filtered.sort((a, b) => {
            let valA = a[state.sortBy] || '';
            let valB = b[state.sortBy] || '';

            if (state.sortBy === 'id') {
                valA = parseInt(a.id) || 0;
                valB = parseInt(b.id) || 0;
            } else {
                valA = valA.toString().toLowerCase();
                valB = valB.toString().toLowerCase();
            }

            if (valA < valB) return state.sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return state.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        // Count badge update
        document.getElementById('itemCountBadge').textContent = `عرض ${filtered.length} من ${listData.length} عقد`;

        // Pagination
        const totalItems = filtered.length;
        let paginated = filtered;
        if (state.pageSize !== 'all') {
            const limit = parseInt(state.pageSize);
            const totalPages = Math.ceil(totalItems / limit) || 1;
            if (state.currentPage > totalPages) state.currentPage = totalPages;
            const start = (state.currentPage - 1) * limit;
            paginated = filtered.slice(start, start + limit);
            renderPagination(totalPages, state.currentPage);
        } else {
            document.getElementById('paginationCtrl').innerHTML = '';
        }

        // Render HTML rows
        if (paginated.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-folder-open fs-4 d-block mb-2 text-secondary"></i>لا توجد اتفاقيات أو عقود إدماج مطابقة للبحث حالياً.</td></tr>`;
            return;
        }

        tbody.innerHTML = paginated.map(item => {
            const fullname = (item.nom_ar || '') + ' ' + (item.prenom_ar || '');
            const userCell = item.nom_ar ? `
                <div class="fw-bold text-dark">${escapeHtml(fullname)}</div>
                <div class="text-muted small"><i class="fa-solid fa-graduation-cap me-1"></i> ${escapeHtml(item.spec_ar || '')}</div>
                <div class="text-secondary small mt-1" style="font-size: 0.8rem;"><i class="fa-solid fa-hotel me-1"></i> ${escapeHtml(item.etab_nom || '')}</div>
            ` : `
                <div class="fw-bold text-primary"><i class="fa-solid fa-file-signature me-1"></i> اتفاقية إطار / مؤسساتية</div>
                <div class="text-muted small">${escapeHtml(item.etab_nom || 'شراكة تكوين وتشغيل عامة')}</div>
            `;

            let typeBadge = '';
            if (item.type_contrat === 'apprentissage') {
                typeBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fw-bold">تمهين (Apprentissage)</span>';
            } else if (item.type_contrat === 'convention') {
                typeBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1.5 rounded-pill fw-bold">اتفاقية شراكة</span>';
            } else {
                typeBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill fw-bold">${escapeHtml(item.type_contrat.toUpperCase())}</span>`;
            }

            const statusBadge = item.statut === 'actif' ? 
                `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5"><i class="fa-solid fa-check me-1"></i> سارية المفعول</span>` :
                `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1.5"><i class="fa-solid fa-circle-xmark me-1"></i> منتهية</span>`;

            return `
                <tr>
                    <td class="ps-4">${userCell}</td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(item.employeur_nom)}</div>
                        <div class="text-muted small">قطاع مستخدم معتمد</div>
                    </td>
                    <td class="text-center">${typeBadge}</td>
                    <td class="text-center text-muted">${escapeHtml(item.date_debut)}</td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="pe-4 text-end no-print no-export">${getActionsHtml(item)}</td>
                </tr>
            `;
        }).join('');

        updateSortIndicators(state.sortBy, state.sortOrder);
    }

    // Pagination links generator
    function renderPagination(totalPages, currentPage) {
        const el = document.getElementById('paginationCtrl');
        if (!el) return;

        let html = '';
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }

        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;

        el.innerHTML = html;

        el.querySelectorAll('a.page-link').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const page = parseInt(a.getAttribute('data-page'));
                if (page && page >= 1 && page <= totalPages && page !== currentPage) {
                    state.currentPage = page;
                    renderTable();
                }
            });
        });
    }

    // Sort column indicators update
    function updateSortIndicators(activeCol, activeOrder) {
        const headers = document.querySelectorAll(`#integrTable .sortable-header`);
        headers.forEach(h => {
            const col = h.getAttribute('data-col');
            const icon = h.querySelector('i');
            if (!icon) return;
            if (col === activeCol) {
                icon.className = activeOrder === 'asc' ? 'fa-solid fa-sort-up text-primary' : 'fa-solid fa-sort-down text-primary';
                icon.classList.remove('opacity-50');
            } else {
                icon.className = 'fa-solid fa-sort ms-1 small opacity-50';
            }
        });
    }

    // Full export functions (all records in memory)
    function exportListToCSV() {
        let csvContent = "\ufeff"; // BOM
        csvContent += "المستفيد / موضوع الاتفاقية,المؤسسة الاقتصادية / الشريك,طبيعة العقد,تاريخ المباشرة,الحالة\n";

        listData.forEach(item => {
            const fullname = item.nom_ar ? (item.nom_ar + ' ' + item.prenom_ar) : 'اتفاقية إطار عامة';
            const row = [
                fullname,
                item.employeur_nom || '',
                item.type_contrat || '',
                item.date_debut || '',
                item.statut === 'actif' ? 'سارية المفعول' : 'منتهية'
            ].map(val => `"${val.toString().replace(/"/g, '""')}"`);
            csvContent += row.join(",") + "\n";
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "conventions_integration_completes.csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportListToExcel() {
        let html = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
                <!--[if gte mso 9]>
                <xml>
                    <` + `x:ExcelWorkbook>
                        <` + `x:ExcelWorksheets>
                            <` + `x:ExcelWorksheet>
                                <` + `x:Name>Conventions</` + `x:Name>
                                <` + `x:WorksheetOptions>
                                    <` + `x:DisplayGridlines/>
                                </` + `x:WorksheetOptions>
                            </` + `x:ExcelWorksheet>
                        </` + `x:ExcelWorksheets>
                    </` + `x:ExcelWorkbook>
                </xml>
                <![endif]-->
            </head>
            <body dir="rtl">
                <table border="1">
                    <tr style="font-weight:bold; background-color:#643edb; color:white;">
                        <th>المستفيد / موضوع الاتفاقية</th>
                        <th>المؤسسة الاقتصادية / الشريك</th>
                        <th>طبيعة العقد / الاتفاقية</th>
                        <th>تاريخ المباشرة</th>
                        <th>الحالة</th>
                    </tr>
        `;

        listData.forEach(item => {
            const fullname = item.nom_ar ? (item.nom_ar + ' ' + item.prenom_ar) : 'اتفاقية إطار عامة';
            html += `
                <tr>
                    <td>${escapeHtml(fullname)}</td>
                    <td>${escapeHtml(item.employeur_nom || '')}</td>
                    <td>${escapeHtml(item.type_contrat || '')}</td>
                    <td>${escapeHtml(item.date_debut || '')}</td>
                    <td>${item.statut === 'actif' ? 'سارية المفعول' : 'منتهية'}</td>
                </tr>
            `;
        });

        html += `
                </table>
            </body>
            </html>
        `;

        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "conventions_integration_completes.xls");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Attach Event Listeners
    document.addEventListener("DOMContentLoaded", function() {
        // Initial render
        renderTable();

        // Bind filter change events
        document.getElementById('searchFilter').addEventListener('input', e => {
            state.search = e.target.value;
            state.currentPage = 1;
            renderTable();
        });
        document.getElementById('filterType').addEventListener('change', e => {
            state.type = e.target.value;
            state.currentPage = 1;
            renderTable();
        });
        document.getElementById('filterStatus').addEventListener('change', e => {
            state.status = e.target.value;
            state.currentPage = 1;
            renderTable();
        });
        document.getElementById('pageSize').addEventListener('change', e => {
            state.pageSize = e.target.value;
            state.currentPage = 1;
            renderTable();
        });
        document.getElementById('resetFilters').addEventListener('click', () => {
            document.getElementById('searchFilter').value = '';
            document.getElementById('filterType').value = '';
            document.getElementById('filterStatus').value = '';
            state.search = '';
            state.type = '';
            state.status = '';
            state.currentPage = 1;
            renderTable();
        });

        // Column Sorting clicks
        document.querySelectorAll('#integrTable .sortable-header').forEach(h => {
            h.addEventListener('click', () => {
                const col = h.getAttribute('data-col');
                if (state.sortBy === col) {
                    state.sortOrder = state.sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortBy = col;
                    state.sortOrder = 'asc';
                }
                state.currentPage = 1;
                renderTable();
            });
        });
    });

    function filterWilaya(val) {
        const url = new URL(window.location.href);
        if (val) {
            url.searchParams.set('wilaya_id', val);
        } else {
            url.searchParams.delete('wilaya_id');
        }
        url.searchParams.delete('etab_id');
        window.location.href = url.toString();
    }

    function filterEtab(val) {
        const url = new URL(window.location.href);
        if (val) {
            url.searchParams.set('etab_id', val);
        } else {
            url.searchParams.delete('etab_id');
        }
        window.location.href = url.toString();
    }
</script>
@endsection
