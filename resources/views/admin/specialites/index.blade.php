@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')

@section('styles')
<style>
    /* Design Variables and System Tokens */
    :root {
        --transition-premium: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow-premium: 0 10px 30px -10px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-premium-hover: 0 20px 40px -15px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.05);
        
        --color-purple-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --color-blue-gradient: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
        --color-green-gradient: linear-gradient(135deg, #047857 0%, #10b981 100%);
        --color-orange-gradient: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
        --color-teal-gradient: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
    }

    /* Bento statistics grid layout */
    .stats-bento-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 1200px) {
        .stats-bento-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 768px) {
        .stats-bento-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 480px) {
        .stats-bento-grid {
            grid-template-columns: 1fr;
        }
    }

    .stat-card {
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 1.25rem;
        transition: var(--transition-premium);
        position: relative;
        overflow: hidden;
        color: #fff;
        min-height: 115px;
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.05);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-premium-hover) !important;
    }
    .stat-card-purple { background: var(--color-purple-gradient); }
    .stat-card-blue { background: var(--color-blue-gradient); }
    .stat-card-green { background: var(--color-green-gradient); }
    .stat-card-orange { background: var(--color-orange-gradient); }
    .stat-card-teal { background: var(--color-teal-gradient); }

    .stat-icon-wrapper {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-premium);
    }
    .stat-card:hover .stat-icon-wrapper {
        transform: scale(1.1) rotate(6deg);
        background: rgba(255, 255, 255, 0.28);
    }

    /* Glassmorphic filter console and cards */
    .filter-card {
        background: var(--bg-surface-elevated, #fff);
        border: 1px solid var(--border, rgba(0, 0, 0, 0.05));
        border-radius: 20px;
        box-shadow: var(--shadow-premium);
        transition: var(--transition-premium);
        padding: 1.5rem;
    }
    .filter-card:hover {
        box-shadow: var(--shadow-premium-hover);
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

    /* CSV Import Drag & Drop zone */
    .dropzone-area {
        border: 2.5px dashed #cbd5e1;
        border-radius: 18px;
        background: #f8fafc;
        transition: var(--transition-premium);
        position: relative;
        cursor: pointer;
    }
    .dropzone-area:hover, .dropzone-area.drag-over {
        border-color: #643edb;
        background-color: #f5f3ff;
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
<div class="container-fluid animate__animated animate__fadeIn" style="font-family: 'Cairo', sans-serif;">

    <!-- Sovereign Title and Top Toolbar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-3 border-bottom border-secondary border-opacity-10 gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-folder-tree text-purple-600 me-2" style="color: #643edb;"></i> تنظيم الفروع وعروض التكوين / Spécialités & Offres
            </h3>
            <p class="text-muted small mb-0">تسيير الخارطة البيداغوجية الوطنية، الفروع المهنية المفتوحة، وعروض التكوين لدورات فيفري وسبتمبر</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="/dashboard/specialites/cartographie" class="btn d-flex align-items-center px-3 gap-2" style="border-radius: 10px; background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); border: 1.5px solid rgba(96,165,250,0.4); color: #60a5fa; font-weight: 700; font-size: 0.88rem; box-shadow: 0 4px 12px rgba(59,130,246,0.2);">
                <i class="fa-solid fa-map-location-dot"></i> خريطة تفاعلية / Carte Interactive
            </a>
            @if(\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') === '1')
            <a href="/dashboard/specialites/print" target="_blank" class="btn btn-outline-dark d-flex align-items-center px-3" style="border-radius: 10px; border: 1.5px solid #cbd5e1; font-weight: 600; font-size: 0.88rem;">
                <i class="fa-solid fa-print me-1.5 text-muted"></i> طباعة التخصصات / Print Specs
            </a>

            <a href="/dashboard/offres/print" target="_blank" class="btn btn-outline-dark d-flex align-items-center px-3" style="border-radius: 10px; border: 1.5px solid #cbd5e1; font-weight: 600; font-size: 0.88rem;">
                <i class="fa-solid fa-print me-1.5 text-muted"></i> طباعة العروض / Print Offers
            </a>
            @endif
            <button class="btn btn-outline-primary d-flex align-items-center px-3" data-bs-toggle="modal" data-bs-target="#importSpecialitesModal" style="border-radius: 10px; border: 1.5px solid #643edb; color: #643edb; font-weight: 600; font-size: 0.88rem;">
                <i class="fa-solid fa-file-import me-1.5"></i> استيراد تخصصات / CSV
            </button>
            <button class="btn btn-primary d-flex align-items-center px-3" data-bs-toggle="modal" data-bs-target="#addSpecialiteModal" style="border-radius: 10px; background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border:0; font-size: 0.88rem; font-weight: 600;">
                <i class="fa-solid fa-plus me-1.5"></i> إضافة تخصص / Nouvelle Spec
            </button>
            <button class="btn btn-success d-flex align-items-center px-3" data-bs-toggle="modal" data-bs-target="#addOffreModal" style="border-radius: 10px; background: linear-gradient(135deg, #006233 0%, #004d28 100%); border:0; font-size: 0.88rem; font-weight: 600;">
                <i class="fa-solid fa-graduation-cap me-1.5"></i> إدراج عرض / Offre
            </button>
        </div>
    </div>

    <!-- Notifications Alerts -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4 animate__animated animate__slideInDown" role="alert" style="border-radius: 12px; background: #e6f7ed; color: #006233;">
            <i class="fa-solid fa-circle-check me-2 fs-5 align-middle"></i> 
            <span class="align-middle fw-semibold"><?= session('flash_success');  ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4 animate__animated animate__slideInDown" role="alert" style="border-radius: 12px; background: #fdf2f2; color: #9b1c1c;">
            <i class="fa-solid fa-circle-exclamation me-2 fs-5 align-middle"></i>
            <span class="align-middle fw-semibold"><?= session('flash_error');  ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Bento Grid Statistics Dashboard Panel -->
    <div class="stats-bento-grid mb-4">
        <!-- 1. Specialties -->
        <div class="stat-card stat-card-purple p-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-white-50 small d-block mb-1">التخصصات المهنية</span>
                <h3 class="fw-bold mb-0" style="font-family: 'Outfit';"><?= count($specialites) ?></h3>
                <small class="text-white-50" style="font-size:0.75rem;">تخصص معتمد بالدليل</small>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-sitemap fa-fw fs-4 text-white"></i>
            </div>
        </div>
        <!-- 2. Branches -->
        <div class="stat-card stat-card-blue p-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-white-50 small d-block mb-1">الشعب المهنية</span>
                <h3 class="fw-bold mb-0" style="font-family: 'Outfit';"><?= count($filieres) ?></h3>
                <small class="text-white-50" style="font-size:0.75rem;">شعبة مهنية مسجلة</small>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-tags fa-fw fs-4 text-white"></i>
            </div>
        </div>
        <!-- 3. Active Offers -->
        <div class="stat-card stat-card-green p-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-white-50 small d-block mb-1">عروض التكوين</span>
                <h3 class="fw-bold mb-0" style="font-family: 'Outfit';"><?= $statsOffres['total_offres'] ?? count($offres) ?></h3>
                <small class="text-white-50" style="font-size:0.75rem;">عرض مفتوح ومصادق</small>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-graduation-cap fa-fw fs-4 text-white"></i>
            </div>
        </div>
        <!-- 4. Pedagogical Seats -->
        <div class="stat-card stat-card-orange p-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-white-50 small d-block mb-1">المقاعد المتاحة</span>
                <h3 class="fw-bold mb-0" style="font-family: 'Outfit';"><?= $statsOffres['total_capacite'] ?? array_sum(array_column($offres, 'capacite')) ?></h3>
                <small class="text-white-50" style="font-size:0.75rem;">مقعد بيداغوجي مقترح</small>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-users fa-fw fs-4 text-white"></i>
            </div>
        </div>
        <!-- 5. Registered trainees -->
        <div class="stat-card stat-card-teal p-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-white-50 small d-block mb-1">المسجلين والموجهين</span>
                <h3 class="fw-bold mb-0" style="font-family: 'Outfit';"><?= $statsOffres['total_inscrits'] ?? array_sum(array_column($offres, 'inscrits')) ?></h3>
                <small class="text-white-50" style="font-size:0.75rem;">متربص مسجل بالمنصة</small>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-user-check fa-fw fs-4 text-white"></i>
            </div>
        </div>
    </div>

    <!-- Custom styled tab selections -->
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-4 shadow-sm" id="pills-tab" role="tablist" style="max-width: fit-content; border: 1px solid rgba(0,0,0,0.03);">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4 py-2.5 transition-premium" id="pills-specialites-tab" data-bs-toggle="pill" data-bs-target="#pills-specialites" type="button" role="tab" aria-controls="pills-specialites" aria-selected="true" style="border-radius: 10px;">
                <i class="fa-solid fa-sitemap me-1.5"></i> دليل الشعب والتخصصات / Spécialités
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 py-2.5 transition-premium" id="pills-offres-tab" data-bs-toggle="pill" data-bs-target="#pills-offres" type="button" role="tab" aria-controls="pills-offres" aria-selected="false" style="border-radius: 10px;">
                <i class="fa-solid fa-bullhorn me-1.5"></i> عروض التكوين المتاحة / Offres Actives
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="pills-tabContent">
        
        <!-- Tab 1: Specialties List & Filters -->
        <div class="tab-pane fade show active" id="pills-specialites" role="tabpanel" aria-labelledby="pills-specialites-tab">
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
                
                <!-- Headers and full export buttons -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
                    <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Cairo';">
                        <i class="fa-solid fa-circle-info text-primary me-1.5"></i> دليل التخصصات البيداغوجية الوطنية / Répertoire National
                    </h5>
                    <div class="d-flex gap-2 no-print">
                        <button onclick="exportSpecsToExcel()" class="btn btn-sm btn-success rounded-pill px-3.5 py-1.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-file-excel"></i> تصدير إكسيل / Excel (الكل)
                        </button>
                        <button onclick="exportSpecsToCSV()" class="btn btn-sm btn-info text-white rounded-pill px-3.5 py-1.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-file-csv"></i> تصدير CSV (الكل)
                        </button>
                    </div>
                </div>

                <!-- Premium Filter Console -->
                <div class="row g-3 align-items-center mb-4 p-3 rounded-4 bg-light border border-opacity-25" style="border-color: #cbd5e1;">
                    <div class="col-12 col-md-4">
                        <div class="input-group bg-white rounded-3 border border-1" style="overflow:hidden;">
                            <span class="input-group-text bg-white border-0 ps-3 pe-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="searchSpec" class="form-control border-0 py-2.5 ps-2 shadow-none" style="font-size: 0.88rem;" placeholder="بحث برمز التخصص أو الاسم بالعربية/الفرنسية...">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <select id="filterFiliere" class="form-select border-1 py-2.5 px-3 rounded-3 form-control-premium bg-white shadow-none">
                            <option value="">كل الشعب المهنية (Toutes)</option>
                            <?php foreach ($filieres as $f): ?>
                                <option value="<?= htmlspecialchars($f['libelle_ar']) ?>"><?= htmlspecialchars($f['libelle_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select id="filterDuration" class="form-select border-1 py-2.5 px-3 rounded-3 form-control-premium bg-white shadow-none">
                            <option value="">كل السداسيات (Durées)</option>
                            <option value="1">سداسي واحد</option>
                            <option value="2">سداسيان (2)</option>
                            <option value="3">3 سداسيات</option>
                            <option value="4">4 سداسيات</option>
                            <option value="5">5 سداسيات</option>
                            <option value="6">6 سداسيات</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex justify-content-between align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2 fs-7 rounded-pill fw-bold" id="specCountBadge">تحميل...</span>
                        <button id="resetSpecFilters" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" title="إعادة تعيين الفلاتر" style="width:38px; height:38px; border-width: 1.5px;">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Component -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" id="specsTable">
                        <thead class="table-light">
                            <tr class="fw-bold text-muted border-bottom" style="font-size:0.85rem; border-color: #e2e8f0;">
                                <th class="sortable-header py-3" data-col="code" style="width: 15%;">رمز التخصص / Code <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header text-right py-3" data-col="libelle_ar" style="width: 35%;">التخصص / Spécialité <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header text-right py-3" data-col="filiere_ar" style="width: 25%;">الشعبة المهنية / Filière <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header py-3" data-col="duree" style="width: 12%;">المدة / Semestres <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="py-3" style="width: 8%;">الحالة / État</th>
                                <th class="no-print no-export py-3" style="width: 10%;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Javascript populated rows -->
                        </tbody>
                    </table>
                </div>

                <!-- Premium Pagination Footer -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top gap-3" style="border-color: #f1f5f9 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">عرض</span>
                        <select id="pageSizeSpec" class="form-select form-select-sm border shadow-none" style="width: 80px; border-radius: 8px; font-weight: 600; padding: 0.35rem 1.5rem 0.35rem 0.5rem;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">الكل</option>
                        </select>
                        <span class="text-muted small">تخصص في الصفحة</span>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1" id="paginationSpec">
                            <!-- JS Pagination -->
                        </ul>
                    </nav>
                </div>

            </div>
        </div>

        <!-- Tab 2: Offers List & Filters -->
        <div class="tab-pane fade" id="pills-offres" role="tabpanel" aria-labelledby="pills-offres-tab">
            <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
                
                <!-- Title & Exports -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
                    <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Cairo';">
                        <i class="fa-solid fa-circle-info text-success me-1.5"></i> تخطيط وتوزيع عروض التكوين المفتوحة / Plan d'Offres Actif
                    </h5>
                    <div class="d-flex gap-2 no-print">
                        <button onclick="exportOffresToExcel()" class="btn btn-sm btn-success rounded-pill px-3.5 py-1.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-file-excel"></i> تصدير إكسيل / Excel (الكل)
                        </button>
                        <button onclick="exportOffresToCSV()" class="btn btn-sm btn-info text-white rounded-pill px-3.5 py-1.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-file-csv"></i> تصدير CSV (الكل)
                        </button>
                    </div>
                </div>

                <!-- Premium Filter Console -->
                <div class="row g-3 align-items-center mb-4 p-3 rounded-4 bg-light border border-opacity-25" style="border-color: #cbd5e1;">
                    <div class="col-12 col-md-3">
                        <div class="input-group bg-white rounded-3 border border-1" style="overflow:hidden;">
                            <span class="input-group-text bg-white border-0 ps-3 pe-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="searchOffre" class="form-control border-0 py-2.5 ps-2 shadow-none" style="font-size: 0.88rem;" placeholder="بحث برمز العرض أو التخصص...">
                        </div>
                    </div>
                    <div class="col-12 col-md-2">
                        <select id="filterDiplome" class="form-select border-1 py-2.5 px-3 rounded-3 form-control-premium bg-white shadow-none">
                            <option value="">كل الشهادات (Diplômes)</option>
                            <option value="BTS">BTS (تقني سامي)</option>
                            <option value="BP">BP (تحكم مهني)</option>
                            <option value="CAP">CAP (أهلية مهنية)</option>
                            <option value="BEP">BEP</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select id="filterMode" class="form-select border-1 py-2.5 px-3 rounded-3 form-control-premium bg-white shadow-none">
                            <option value="">كل الأنماط (Modes)</option>
                            <option value="تمهين">تمهين / Apprentissage</option>
                            <option value="حضوري">حضوري / Présentiel</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select id="filterNiveau" class="form-select border-1 py-2.5 px-3 rounded-3 form-control-premium bg-white shadow-none">
                            <option value="">كل المستويات (Niveaux)</option>
                            <option value="ثالثة ثانوي">ثالثة ثانوي / Bac</option>
                            <option value="تعليم متوسط">تعليم متوسط / BEM</option>
                            <option value="بدون مستوى">بدون مستوى</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex justify-content-between align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success-emphasis px-3 py-2 fs-7 rounded-pill fw-bold" id="offreCountBadge">تحميل...</span>
                        <button id="resetOffreFilters" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" title="إعادة تعيين الفلاتر" style="width:38px; height:38px; border-width: 1.5px;">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>

                <!-- Offers Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" id="activeOffresTable">
                        <thead class="table-light">
                            <tr class="fw-bold text-muted border-bottom" style="font-size:0.85rem; border-color: #e2e8f0;">
                                <th class="sortable-header py-3" data-col="code" style="width: 12%;">رمز العرض <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header text-right py-3" data-col="spec_ar" style="width: 28%;">التخصص المقترح / Spécialité <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header py-3" data-col="diplome" style="width: 12%;">الشهادة / Diplôme <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header py-3" data-col="mode" style="width: 13%;">نمط التكوين / Mode <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header py-3" data-col="niveau" style="width: 13%;">المستوى المطلوب <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header py-3" data-col="capacite" style="width: 10%;">الاستيعاب <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="sortable-header py-3" data-col="periode" style="width: 12%;">تاريخ الدورة <i class="fa-solid fa-sort ms-1 small opacity-50"></i></th>
                                <th class="py-3" style="width: 10%;">حالة الالتحاق</th>
                                <th class="no-print no-export py-3" style="width: 10%;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JS populated rows -->
                        </tbody>
                    </table>
                </div>

                <!-- Offers Pagination Footer -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top gap-3" style="border-color: #f1f5f9 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">عرض</span>
                        <select id="pageSizeOffre" class="form-select form-select-sm border shadow-none" style="width: 80px; border-radius: 8px; font-weight: 600; padding: 0.35rem 1.5rem 0.35rem 0.5rem;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">الكل</option>
                        </select>
                        <span class="text-muted small">عرض في الصفحة</span>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1" id="paginationOffre">
                            <!-- JS Pagination -->
                        </ul>
                    </nav>
                </div>

            </div>
        </div>

    </div>

    <!-- =====================================================
         MODALS WITH PREMIUM GLASSMORPHIC STRUCTURE
         ===================================================== -->

    <!-- Modal 1: Add Specialty -->
    <div class="modal fade" id="addSpecialiteModal" tabindex="-1" aria-labelledby="addSpecialiteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addSpecialiteModalLabel" style="color:var(--color-gov-purple-dark); font-family: 'Cairo';">
                        <i class="fa-solid fa-plus-circle text-primary me-2"></i> إضافة تخصص جديد في الدليل
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/dashboard/specialites/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">رمز التخصص الموحد (Code) *</label>
                            <input type="text" name="code" required class="form-control form-control-premium" placeholder="مثال: HRT1806">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">التسمية بالعربية (Libellé Arabe) *</label>
                            <input type="text" name="libelle_ar" required class="form-control form-control-premium" placeholder="مثال: تطوير البرمجيات والوسائط">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">التسمية بالفرنسية (Libellé Français) *</label>
                            <input type="text" name="libelle_fr" required class="form-control form-control-premium" placeholder="Ex: Développement d'applications">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">الشعبة المهنية *</label>
                                <select name="filiere_id" required class="form-select form-control-premium py-2">
                                    <?php foreach ($allFilieres as $f): ?>
                                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['libelle_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">عدد السداسيات *</label>
                                <input type="number" name="duree_semestres" required class="form-control form-control-premium" value="4">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                        <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius:10px; background:linear-gradient(135deg, #482b8f 0%, #643edb 100%); border:0; font-weight: 600;">تأكيد الإضافة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Add Offer -->
    <div class="modal fade" id="addOffreModal" tabindex="-1" aria-labelledby="addOffreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addOffreModalLabel" style="color:#006233; font-family: 'Cairo';">
                        <i class="fa-solid fa-graduation-cap text-success me-2"></i> إدراج عرض تكوين لدورة جديدة
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/dashboard/offres/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">رمز العرض (Code Offre) *</label>
                            <input type="text" name="code" required class="form-control form-control-premium" placeholder="مثال: 260018">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">التخصص البيداغوجي المستهدف *</label>
                            <select name="specialite_id" id="add_offre_specialite_id" required class="form-select form-control-premium py-2">
                                <option value="" disabled selected data-code="" data-duree="0">اختر التخصص...</option>
                                <?php foreach ($allSpecialites as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-code="<?= htmlspecialchars($s['code']) ?>" data-duree="<?= (int)$s['duree_semestres'] ?>"><?= htmlspecialchars($s['libelle_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">الشهادة المستهدفة *</label>
                                <select name="diplome_vise" id="add_offre_diplome" required class="form-select form-control-premium py-2">
                                    <option value="BTS">BTS (تقني سامي)</option>
                                    <option value="BP">BP (تحكم مهني)</option>
                                    <option value="CAP">CAP (أهلية مهنية)</option>
                                    <option value="BEP">BEP</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">نمط التكوين *</label>
                                <select name="mode_formation" id="add_offre_mode" required class="form-select form-control-premium py-2">
                                    <option value="2">تمهين / Apprentissage</option>
                                    <option value="1">حضوري / Présentiel</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">المستوى المطلوب *</label>
                                <select name="niveau_requis" id="add_offre_niveau" required class="form-select form-control-premium py-2">
                                    <option value="bac">ثالثة ثانوي أو بكالوريا / Bac</option>
                                    <option value="bem">تعليم متوسط / BEM</option>
                                    <option value="sans_niveau">بدون مستوى</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">الطاقة الاستيعابية *</label>
                                <input type="number" name="capacite" required class="form-control form-control-premium" value="25">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">تاريخ البداية *</label>
                                <input type="date" name="date_debut" id="add_offre_debut" required class="form-control form-control-premium" value="2026-09-01">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">تاريخ النهاية *</label>
                                <input type="date" name="date_fin" id="add_offre_fin" required class="form-control form-control-premium" value="2028-09-01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">الدورة التكوينية (Session) *</label>
                            <input type="text" name="session_name" id="add_offre_session_name" required class="form-control form-control-premium" placeholder="مثال: دورة سبتمبر 2026" list="sessionsList">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">تاريخ بدء الانتقاء والتوجيه (Sélection)</label>
                            <input type="date" name="date_debut_selection" id="add_offre_debut_selection" class="form-control form-control-premium">
                            <div class="form-text text-muted" style="font-size:0.78rem;"><i class="fa-solid fa-circle-info me-1"></i> تحديد هذا التاريخ يملأ اسم الدورة تلقائياً</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                        <button type="submit" class="btn btn-success px-4 py-2" style="border-radius:10px; background:linear-gradient(135deg, #006233 0%, #004d28 100%); border:0; font-weight: 600;">تأكيد إدراج العرض</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 3: Edit Specialty -->
    <div class="modal fade" id="editSpecialiteModal" tabindex="-1" aria-labelledby="editSpecialiteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editSpecialiteModalLabel" style="color:var(--color-gov-purple-dark); font-family: 'Cairo';">
                        <i class="fa-solid fa-pen-to-square text-primary me-2"></i> تعديل التخصص البيداغوجي
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/dashboard/specialites/update" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <input type="hidden" name="id" id="edit_spec_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">رمز التخصص الموحد (Code) *</label>
                            <input type="text" name="code" id="edit_spec_code" required class="form-control form-control-premium">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">التسمية بالعربية (Libellé Arabe) *</label>
                            <input type="text" name="libelle_ar" id="edit_spec_libelle_ar" required class="form-control form-control-premium">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">التسمية بالفرنسية (Libellé Français) *</label>
                            <input type="text" name="libelle_fr" id="edit_spec_libelle_fr" required class="form-control form-control-premium">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">الشعبة المهنية *</label>
                                <select name="filiere_id" id="edit_spec_filiere_id" required class="form-select form-control-premium py-2">
                                    <?php foreach ($allFilieres as $f): ?>
                                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['libelle_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">عدد السداسيات *</label>
                                <input type="number" name="duree_semestres" id="edit_spec_duree" required class="form-control form-control-premium">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                        <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius:10px; background:var(--color-gov-purple); border:0; font-weight: 600;">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 4: Edit Offer -->
    <div class="modal fade" id="editOffreModal" tabindex="-1" aria-labelledby="editOffreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editOffreModalLabel" style="color:var(--color-gov-green); font-family: 'Cairo';">
                        <i class="fa-solid fa-pen-to-square text-success me-2"></i> تعديل عرض التكوين الحالي
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/dashboard/offres/update" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <input type="hidden" name="id" id="edit_offre_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">رمز العرض (Code Offre) *</label>
                            <input type="text" name="code" id="edit_offre_code" required class="form-control form-control-premium">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">التخصص البيداغوجي المستهدف *</label>
                            <select name="specialite_id" id="edit_offre_specialite_id" required class="form-select form-control-premium py-2">
                                <?php foreach ($allSpecialites as $spec_item): ?>
                                    <option value="<?= $spec_item['id'] ?>" data-code="<?= htmlspecialchars($spec_item['code']) ?>" data-duree="<?= (int)$spec_item['duree_semestres'] ?>"><?= htmlspecialchars($spec_item['libelle_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">الشهادة المستهدفة *</label>
                                <select name="diplome_vise" id="edit_offre_diplome" required class="form-select form-control-premium py-2">
                                    <option value="BTS">BTS (تقني سامي)</option>
                                    <option value="BP">BP (تحكم مهني)</option>
                                    <option value="CAP">CAP (أهلية مهنية)</option>
                                    <option value="BEP">BEP</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">نمط التكوين *</label>
                                <select name="mode_formation" id="edit_offre_mode" required class="form-select form-control-premium py-2">
                                    <option value="2">تمهين / Apprentissage</option>
                                    <option value="1">حضوري / Présentiel</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">المستوى المطلوب *</label>
                                <select name="niveau_requis" id="edit_offre_niveau" required class="form-select form-control-premium py-2">
                                    <option value="bac">ثالثة ثانوي أو بكالوريا / Bac</option>
                                    <option value="bem">تعليم متوسط / BEM</option>
                                    <option value="sans_niveau">بدون مستوى</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">الطاقة الاستيعابية *</label>
                                <input type="number" name="capacite" id="edit_offre_capacite" required class="form-control form-control-premium">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">تاريخ البداية *</label>
                                <input type="date" name="date_debut" id="edit_offre_debut" required class="form-control form-control-premium">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-dark">تاريخ النهاية *</label>
                                <input type="date" name="date_fin" id="edit_offre_fin" required class="form-control form-control-premium">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">الدورة التكوينية (Session) *</label>
                            <input type="text" name="session_name" id="edit_offre_session_name" required class="form-control form-control-premium" placeholder="مثال: دورة فيفري 2026" list="sessionsList">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">تاريخ بدء الانتقاء والتوجيه (Sélection)</label>
                            <input type="date" name="date_debut_selection" id="edit_offre_debut_selection" class="form-control form-control-premium">
                            <div class="form-text text-muted" style="font-size:0.78rem;"><i class="fa-solid fa-circle-info me-1"></i> تحديد هذا التاريخ يملأ اسم الدورة تلقائياً</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                        <button type="submit" class="btn btn-success px-4 py-2" style="border-radius:10px; background:var(--color-gov-green); border:0; font-weight: 600;">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 5: Bulk CSV Import with Interactive Client-Side Preview Parser -->
    <div class="modal fade" id="importSpecialitesModal" tabindex="-1" aria-labelledby="importSpecialitesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-glass">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="importSpecialitesModalLabel" style="color:var(--color-gov-purple-dark); font-family: 'Cairo';">
                        <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> استيراد التخصصات والمستوى التأهيلي / Import CSV
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/dashboard/specialites/import" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 shadow-sm p-3 mb-4" style="border-radius: 12px; background-color: #f0f4ff; color: #1e3a8a; font-size: 0.88rem; line-height: 1.6;">
                            <div class="fw-bold mb-1"><i class="fa-solid fa-circle-info me-1.5"></i> إرشادات هيكل ملف الاستيراد / CSV Format:</div>
                            <ul class="mb-0 ps-3">
                                <li>ترتيب الأعمدة بالملف: <strong>الرمز (0)، معرف ID (1)، (فارغ 2)، الاسم بالعربية (3)، الاسم بالفرنسية (4)، المستوى (5)، الشهادة (6)، مستوى التكوين (7)، النمط (8)، المدة بالشهور (9)</strong>.</li>
                                <li>سيتم تسجيل الشعب ومستوى التكوين والسداسيات تلقائياً في قاعدة البيانات.</li>
                            </ul>
                        </div>

                        <!-- Pill tabs for upload mode -->
                        <ul class="nav nav-tabs mb-3 border-bottom-0 gap-2" id="importModeTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active border-0 px-4 py-2" id="file-import-tab" data-bs-toggle="tab" data-bs-target="#file-import" type="button" role="tab" style="border-radius: 8px; font-weight: 600; font-size: 0.88rem;">
                                    <i class="fa-solid fa-file-csv me-1.5"></i> تحميل ملف CSV / Fichier CSV
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link border-0 px-4 py-2" id="text-import-tab" data-bs-toggle="tab" data-bs-target="#text-import" type="button" role="tab" style="border-radius: 8px; font-weight: 600; font-size: 0.88rem;">
                                    <i class="fa-solid fa-align-left me-1.5"></i> نسخ ولصق النص / Paste CSV Text
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="importModeTabContent">
                            <!-- Drag & Drop File Upload -->
                            <div class="tab-pane fade show active" id="file-import" role="tabpanel" aria-labelledby="file-import-tab">
                                <div class="dropzone-area p-5 text-center" id="dropzone" style="transition: all 0.3s ease;">
                                    <input type="file" name="csv_file" id="csv_file_input" accept=".csv" class="position-absolute top-0 start-0 opacity-0 w-100 h-100" style="cursor: pointer; z-index: 5;">
                                    <div class="dropzone-info">
                                        <i class="fa-solid fa-cloud-arrow-up text-muted mb-3" style="font-size: 3rem; color: #643edb !important;"></i>
                                        <h6 class="fw-bold mb-1 text-dark">اسحب وأسقط ملف الـ CSV هنا أو اضغط للتصفح</h6>
                                        <p class="text-muted small mb-0">Glissez-déposez le fichier CSV ou cliquez pour parcourir (.csv uniquement)</p>
                                    </div>
                                    <div class="selected-file-info d-none mt-2">
                                        <span class="badge px-3 py-2 fs-6 rounded-pill" style="background-color: #643edb; color: white;"><i class="fa-solid fa-file-circle-check me-2"></i> <span id="selected-filename">filename.csv</span></span>
                                    </div>
                                </div>
                                <!-- CSV Parsing Preview Area -->
                                <div id="csv-preview-container"></div>
                            </div>

                            <!-- Paste Text Area -->
                            <div class="tab-pane fade" id="text-import" role="tabpanel" aria-labelledby="text-import-tab">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-dark">ألصق أسطر الـ CSV هنا / Collez vos lignes CSV ici</label>
                                    <textarea name="csv_text" class="form-control form-control-premium" rows="8" placeholder="EP/MRA19401,726,,تركيب وصيانة عتاد الري,Installation et maintenance,الرابعة متوسط ناجح,شهادة التعليم المهني,الرابع,تعليم مهني,36&#10;EP/MRA19402,727,,صيانة الآلات الفلاحية,Maintenance machines agricoles,الرابعة متوسط ناجح,شهادة التعليم المهني,الرابع,تعليم مهني,36" style="font-family: 'Consolas', monospace; font-size: 0.82rem;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء / Annuler</button>
                        <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius:10px; background:linear-gradient(135deg, #482b8f 0%, #643edb 100%); border:0; font-weight: 600;">
                            <i class="fa-solid fa-cloud-arrow-up me-1.5"></i> بدء الاستيراد والدمج / Importer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Datalist of sessions for dynamic dropdown suggestions -->
    <datalist id="sessionsList">
        <?php foreach ($sessions as $session_item): ?>
            <option value="<?= htmlspecialchars($session_item['intitule_ar']) ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <!-- JS Logic Core -->
    <script>
    // Data bindings injected from Laravel database query
    const specialtiesData = <?= json_encode($specialites) ?>;
    const offresData = <?= json_encode($offres) ?>;

    // Filters and sorting state objects
    let specState = {
        search: '',
        filiere: '',
        duration: '',
        sortBy: 'libelle_ar',
        sortOrder: 'asc',
        currentPage: 1,
        pageSize: 25
    };

    let offreState = {
        search: '',
        diplome: '',
        mode: '',
        niveau: '',
        sortBy: 'id',
        sortOrder: 'desc',
        currentPage: 1,
        pageSize: 25
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

    function editSpecialite(s) {
        document.getElementById('edit_spec_id').value = s.id;
        document.getElementById('edit_spec_code').value = s.code;
        document.getElementById('edit_spec_libelle_ar').value = s.libelle_ar;
        document.getElementById('edit_spec_libelle_fr').value = s.libelle_fr;
        document.getElementById('edit_spec_filiere_id').value = s.filiere_id;
        document.getElementById('edit_spec_duree').value = s.duree_semestres;
        
        var modal = new bootstrap.Modal(document.getElementById('editSpecialiteModal'));
        modal.show();
    }

    function editOffre(o) {
        document.getElementById('edit_offre_id').value = o.id;
        document.getElementById('edit_offre_code').value = o.code;
        document.getElementById('edit_offre_specialite_id').value = o.specialite_id;
        document.getElementById('edit_offre_diplome').value = o.diplome_vise;
        
        // Match the legacy ID mapping
        document.getElementById('edit_offre_mode').value = (o.mode_formation == 2) ? "2" : "1";
        
        document.getElementById('edit_offre_niveau').value = o.niveau_requis;
        document.getElementById('edit_offre_capacite').value = o.capacite;
        document.getElementById('edit_offre_debut').value = o.date_debut;
        document.getElementById('edit_offre_fin').value = o.date_fin;
        document.getElementById('edit_offre_session_name').value = o.session_name || '';
        document.getElementById('edit_offre_debut_selection').value = o.date_debut_selection || '';
        
        var modal = new bootstrap.Modal(document.getElementById('editOffreModal'));
        modal.show();
    }

    // Generate dynamic action cell HTML
    function getSpecActionsHtml(s) {
        const csrf = '<?= csrf_token() ?? "" ?>';
        // HTML escape double quotes in the object string for onclick attribute safety
        const specStr = JSON.stringify(s).replace(/"/g, '&quot;');
        return `
            <button class="btn btn-sm btn-outline-primary rounded-circle" style="width:32px; height:32px;" onclick="editSpecialite(${specStr})" title="تعديل التخصص">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <form action="/dashboard/specialites/delete/${s.id}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا التخصص؟')">
                <input type="hidden" name="csrf_token" value="${csrf}">
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle ms-1" style="width:32px; height:32px;" title="حذف التخصص">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </form>
        `;
    }

    function getOffreActionsHtml(o) {
        const csrf = '<?= csrf_token() ?? "" ?>';
        const offreStr = JSON.stringify(o).replace(/"/g, '&quot;');
        return `
            <button class="btn btn-sm btn-outline-primary rounded-circle" style="width:32px; height:32px;" onclick="editOffre(${offreStr})" title="تعديل العرض">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <form action="/dashboard/offres/delete/${o.id}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العرض؟')">
                <input type="hidden" name="csrf_token" value="${csrf}">
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle ms-1" style="width:32px; height:32px;" title="حذف العرض">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </form>
        `;
    }

    // Table render functions
    function renderSpecsTable() {
        const tbody = document.querySelector('#specsTable tbody');
        if (!tbody) return;

        // Filtering
        let filtered = specialtiesData.filter(s => {
            const matchSearch = !specState.search || 
                (s.code && s.code.toLowerCase().includes(specState.search.toLowerCase())) ||
                (s.libelle_ar && s.libelle_ar.includes(specState.search)) ||
                (s.libelle_fr && s.libelle_fr.toLowerCase().includes(specState.search.toLowerCase())) ||
                (s.filiere_ar && s.filiere_ar.includes(specState.search));
            
            const matchFiliere = !specState.filiere || s.filiere_ar === specState.filiere;
            const matchDuration = !specState.duration || parseInt(s.duree_semestres) === parseInt(specState.duration);
            
            return matchSearch && matchFiliere && matchDuration;
        });

        // Sorting
        filtered.sort((a, b) => {
            let valA = a[specState.sortBy] || '';
            let valB = b[specState.sortBy] || '';
            
            if (specState.sortBy === 'duree') {
                valA = parseInt(a.duree_semestres) || 0;
                valB = parseInt(b.duree_semestres) || 0;
            } else if (specState.sortBy === 'code') {
                valA = (a.code || '').toString();
                valB = (b.code || '').toString();
            } else {
                valA = (a[specState.sortBy] || '').toString();
                valB = (b[specState.sortBy] || '').toString();
            }

            if (valA < valB) return specState.sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return specState.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        // Count badge
        document.getElementById('specCountBadge').textContent = `عرض ${filtered.length} من ${specialtiesData.length} تخصص`;

        // Pagination
        const totalItems = filtered.length;
        let paginated = filtered;
        if (specState.pageSize !== 'all') {
            const limit = parseInt(specState.pageSize);
            const totalPages = Math.ceil(totalItems / limit) || 1;
            if (specState.currentPage > totalPages) specState.currentPage = totalPages;
            const start = (specState.currentPage - 1) * limit;
            paginated = filtered.slice(start, start + limit);
            renderPagination('paginationSpec', totalPages, specState.currentPage, page => {
                specState.currentPage = page;
                renderSpecsTable();
            });
        } else {
            document.getElementById('paginationSpec').innerHTML = '';
        }

        // Render rows
        if (paginated.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-muted py-4"><i class="fa-solid fa-folder-open me-2 fs-4 d-block mb-2 text-secondary"></i>لا توجد تخصصات تطابق معايير البحث</td></tr>`;
            return;
        }

        tbody.innerHTML = paginated.map(s => {
            const activeBadge = s.activee ? 
                `<span class="badge bg-success-subtle text-success px-3 py-1.5" style="border-radius:20px;">نشط / Actif</span>` :
                `<span class="badge bg-danger-subtle text-danger px-3 py-1.5" style="border-radius:20px;">موقوف / Inactif</span>`;

            return `
                <tr class="align-middle">
                    <td class="fw-bold"><span class="badge bg-light text-dark border px-2.5 py-1.5" style="font-family:'Outfit';">${escapeHtml(s.code)}</span></td>
                    <td class="text-right">
                        <strong class="d-block text-dark">${escapeHtml(s.libelle_ar)}</strong>
                        <small class="text-muted">${escapeHtml(s.libelle_fr || '')}</small>
                    </td>
                    <td class="text-right">
                        <span class="d-block text-dark small">${escapeHtml(s.filiere_ar || '')}</span>
                        <small class="text-muted" style="font-size:0.75rem;">${escapeHtml(s.filiere_fr || '')}</small>
                    </td>
                    <td class="fw-bold text-primary">${s.duree_semestres} سداسيات</td>
                    <td>${activeBadge}</td>
                    <td class="no-print no-export text-center">
                        ${getSpecActionsHtml(s)}
                    </td>
                </tr>
            `;
        }).join('');

        updateSortIndicators('specsTable', specState.sortBy, specState.sortOrder);
    }

    function renderOffresTable() {
        const tbody = document.querySelector('#activeOffresTable tbody');
        if (!tbody) return;

        // Filtering
        let filtered = offresData.filter(o => {
            const matchSearch = !offreState.search ||
                (o.code && o.code.toLowerCase().includes(offreState.search.toLowerCase())) ||
                (o.spec_ar && o.spec_ar.includes(offreState.search)) ||
                (o.spec_fr && o.spec_fr.toLowerCase().includes(offreState.search.toLowerCase())) ||
                (o.session_ar && o.session_ar.includes(offreState.search)) ||
                (o.etab_ar && o.etab_ar.includes(offreState.search));

            const matchDiplome = !offreState.diplome || o.diplome_vise === offreState.diplome;
            const matchMode = !offreState.mode || 
                (offreState.mode === 'تمهين' && o.mode_formation == 2) ||
                (offreState.mode === 'حضوري' && o.mode_formation == 1);
            
            const matchNiveau = !offreState.niveau || o.niveau_requis === offreState.niveau;

            return matchSearch && matchDiplome && matchMode && matchNiveau;
        });

        // Sorting
        filtered.sort((a, b) => {
            let valA = a[offreState.sortBy];
            let valB = b[offreState.sortBy];

            if (offreState.sortBy === 'capacite') {
                valA = parseInt(a.capacite) || 0;
                valB = parseInt(b.capacite) || 0;
            } else if (offreState.sortBy === 'code') {
                valA = (a.code || '').toString();
                valB = (b.code || '').toString();
            } else if (offreState.sortBy === 'spec_ar') {
                valA = (a.spec_ar || '').toString();
                valB = (b.spec_ar || '').toString();
            } else if (offreState.sortBy === 'periode') {
                valA = (a.date_debut || '').toString();
                valB = (b.date_debut || '').toString();
            } else {
                valA = (a[offreState.sortBy] || '').toString();
                valB = (b[offreState.sortBy] || '').toString();
            }

            if (valA < valB) return offreState.sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return offreState.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        // Count badge
        document.getElementById('offreCountBadge').textContent = `عرض ${filtered.length} من ${offresData.length} عرض`;

        // Pagination
        const totalItems = filtered.length;
        let paginated = filtered;
        if (offreState.pageSize !== 'all') {
            const limit = parseInt(offreState.pageSize);
            const totalPages = Math.ceil(totalItems / limit) || 1;
            if (offreState.currentPage > totalPages) offreState.currentPage = totalPages;
            const start = (offreState.currentPage - 1) * limit;
            paginated = filtered.slice(start, start + limit);
            renderPagination('paginationOffre', totalPages, offreState.currentPage, page => {
                offreState.currentPage = page;
                renderOffresTable();
            });
        } else {
            document.getElementById('paginationOffre').innerHTML = '';
        }

        // Render rows
        if (paginated.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-muted py-4"><i class="fa-solid fa-graduation-cap me-2 fs-4 d-block mb-2 text-secondary"></i>لا توجد عروض تكوين تطابق معايير البحث</td></tr>`;
            return;
        }

        tbody.innerHTML = paginated.map(o => {
            const modeLabel = o.mode_formation == 2 ? 
                `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2.5 py-1.5" style="border-radius:6px;"><i class="fa-solid fa-industry me-1"></i>تمهين</span>` : 
                `<span class="badge bg-info-subtle text-info-emphasis border border-info px-2.5 py-1.5" style="border-radius:6px;"><i class="fa-solid fa-school me-1"></i>حضوري</span>`;
            
            return `
                <tr class="align-middle">
                    <td class="fw-bold text-dark" style="font-family:'Outfit';">${escapeHtml(o.code)}</td>
                    <td class="text-right">
                        <strong class="d-block text-dark">${escapeHtml(o.spec_ar)}</strong>
                        <small class="text-muted">${escapeHtml(o.spec_fr || '')}</small>
                    </td>
                    <td><span class="badge bg-primary px-3 py-1.5" style="border-radius:6px;">${escapeHtml(o.diplome_vise)}</span></td>
                    <td>${modeLabel}</td>
                    <td><span class="text-muted small">${escapeHtml(o.niveau_requis || 'بدون مستوى')}</span></td>
                    <td class="fw-bold">${o.capacite} مقعد</td>
                    <td class="small text-muted" dir="ltr">${escapeHtml(o.date_debut || '')} / ${escapeHtml(o.date_fin || '')}</td>
                    <td><span class="badge bg-success-subtle text-success px-2.5 py-1.5" style="border-radius:20px;"><i class="fa-solid fa-circle-check me-1"></i>تسجيل مفتوح</span></td>
                    <td class="no-print no-export text-center">
                        ${getOffreActionsHtml(o)}
                    </td>
                </tr>
            `;
        }).join('');

        updateSortIndicators('activeOffresTable', offreState.sortBy, offreState.sortOrder);
    }

    // Pagination helper
    function renderPagination(elementId, totalPages, currentPage, onPageChange) {
        const el = document.getElementById(elementId);
        if (!el) return;

        let html = '';
        // Previous page button
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        // Visible page numbers calculation
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

        // Next page button
        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;

        el.innerHTML = html;

        // Attach event listeners
        el.querySelectorAll('a.page-link').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const page = parseInt(a.getAttribute('data-page'));
                if (page && page >= 1 && page <= totalPages && page !== currentPage) {
                    onPageChange(page);
                }
            });
        });
    }

    // Sort column indicators update
    function updateSortIndicators(tableId, activeCol, activeOrder) {
        const headers = document.querySelectorAll(`#${tableId} .sortable-header`);
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

    // Export helpers (Complete sets)
    function exportSpecsToCSV() {
        let csvContent = "\ufeff"; // BOM
        csvContent += "رمز التخصص / Code,التخصص بالعربية / Spécialité AR,التخصص بالفرنسية / Spécialité FR,الشعبة بالعربية / Filière AR,الشعبة بالفرنسية / Filière FR,عدد السداسيات / Semestres\n";
        
        specialtiesData.forEach(s => {
            const row = [
                s.code || '',
                s.libelle_ar || '',
                s.libelle_fr || '',
                s.filiere_ar || '',
                s.filiere_fr || '',
                s.duree_semestres || ''
            ].map(val => `"${val.toString().replace(/"/g, '""')}"`);
            csvContent += row.join(",") + "\n";
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "specialites_complete.csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportSpecsToExcel() {
        let html = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
                <!--[if gte mso 9]>
                <xml>
                    <` + `x:ExcelWorkbook>
                        <` + `x:ExcelWorksheets>
                            <` + `x:ExcelWorksheet>
                                <` + `x:Name>Specialites</` + `x:Name>
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
                        <th>رمز التخصص / Code</th>
                        <th>التخصص بالعربية / Spécialité AR</th>
                        <th>التخصص بالفرنسية / Spécialité FR</th>
                        <th>الشعبة المهنية / Filière AR</th>
                        <th>الشعبة بالفرنسية / Filière FR</th>
                        <th>السداسيات / Semestres</th>
                    </tr>
        `;

        specialtiesData.forEach(s => {
            html += `
                <tr>
                    <td>${escapeHtml(s.code || '')}</td>
                    <td>${escapeHtml(s.libelle_ar || '')}</td>
                    <td>${escapeHtml(s.libelle_fr || '')}</td>
                    <td>${escapeHtml(s.filiere_ar || '')}</td>
                    <td>${escapeHtml(s.filiere_fr || '')}</td>
                    <td>${s.duree_semestres}</td>
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
        link.setAttribute("download", "specialites_complete.xls");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportOffresToCSV() {
        let csvContent = "\ufeff";
        csvContent += "رمز العرض / Offre ID,التخصص المقترح / Spécialité AR,الشهادة / Diplôme,نمط التكوين / Mode,المستوى المطلوب / Niveau,الطاقة الاستيعابية / Capacité,تاريخ البدء / Début,تاريخ الانتهاء / Fin,الدورة / Session\n";

        offresData.forEach(o => {
            const mode = o.mode_formation == 2 ? 'تمهين' : 'حضوري';
            const row = [
                o.code || '',
                o.spec_ar || '',
                o.diplome_vise || '',
                mode,
                o.niveau_requis || '',
                o.capacite || '0',
                o.date_debut || '',
                o.date_fin || '',
                o.session_name || ''
            ].map(val => `"${val.toString().replace(/"/g, '""')}"`);
            csvContent += row.join(",") + "\n";
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "offres_actives_complete.csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportOffresToExcel() {
        let html = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
                <!--[if gte mso 9]>
                <xml>
                    <` + `x:ExcelWorkbook>
                        <` + `x:ExcelWorksheets>
                            <` + `x:ExcelWorksheet>
                                <` + `x:Name>Offres</` + `x:Name>
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
                    <tr style="font-weight:bold; background-color:#006233; color:white;">
                        <th>رمز العرض / Offre ID</th>
                        <th>التخصص المقترح / Spécialité AR</th>
                        <th>الشهادة / Diplôme</th>
                        <th>نمط التكوين / Mode</th>
                        <th>المستوى المطلوب / Niveau</th>
                        <th>الطاقة الاستيعابية</th>
                        <th>تاريخ البدء</th>
                        <th>تاريخ الانتهاء</th>
                        <th>الدورة التكوينية / Session</th>
                    </tr>
        `;

        offresData.forEach(o => {
            const mode = o.mode_formation == 2 ? 'تمهين' : 'حضوري';
            html += `
                <tr>
                    <td>${escapeHtml(o.code || '')}</td>
                    <td>${escapeHtml(o.spec_ar || '')}</td>
                    <td>${escapeHtml(o.diplome_vise || '')}</td>
                    <td>${escapeHtml(mode)}</td>
                    <td>${escapeHtml(o.niveau_requis || 'بدون مستوى')}</td>
                    <td>${o.capacite}</td>
                    <td>${escapeHtml(o.date_debut || '')}</td>
                    <td>${escapeHtml(o.date_fin || '')}</td>
                    <td>${escapeHtml(o.session_name || '')}</td>
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
        link.setAttribute("download", "offres_actives_complete.xls");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // CSV Client-Side Parsing
    function parseCsvLine(text) {
        let p = '', r = [];
        let q = false;
        for (let i = 0; i < text.length; i++) {
            let c = text[i];
            if (c === '"') {
                q = !q;
            } else if (c === ',' && !q) {
                r.push(p);
                p = '';
            } else {
                p += c;
            }
        }
        r.push(p);
        return r;
    }

    function renderCsvPreview(rows, totalParsed) {
        const container = document.getElementById('csv-preview-container');
        if (!container) return;

        if (rows.length === 0) {
            container.innerHTML = `<div class="text-danger small mt-2"><i class="fa-solid fa-triangle-exclamation me-1"></i>ملف CSV غير صالح أو فارغ</div>`;
            return;
        }

        let html = `
            <div class="mt-3">
                <h6 class="fw-bold mb-2 small" style="color:var(--color-gov-purple-dark);">
                    <i class="fa-solid fa-table me-1"></i> معاينة البيانات (أول 3 أسطر) / Aperçu des données:
                </h6>
                <div class="table-responsive border rounded-3" style="max-height: 150px; overflow-y: auto;">
                    <table class="table table-sm table-striped text-center mb-0" style="font-size: 0.78rem;">
                        <thead class="table-light">
                            <tr>
                                <th>الرمز / Code</th>
                                <th>المعرف / ID</th>
                                <th>التسمية بالعربية</th>
                                <th>التسمية بالفرنسية</th>
                                <th>المدة بالشهور</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        rows.forEach(r => {
            html += `
                <tr>
                    <td class="fw-bold text-dark">${escapeHtml(r[0] || '')}</td>
                    <td>${escapeHtml(r[1] || '')}</td>
                    <td class="text-right">${escapeHtml(r[3] || '')}</td>
                    <td class="text-right">${escapeHtml(r[4] || '')}</td>
                    <td>${escapeHtml(r[9] || r[5] || '')}</td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
                <div class="form-text text-success mt-1 small">
                    <i class="fa-solid fa-circle-check me-1"></i> تم العثور على أسطر صالحة للاستيراد.
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    // Initialize Event Bindings
    document.addEventListener("DOMContentLoaded", function() {
        
        // Initial renders
        renderSpecsTable();
        renderOffresTable();

        // 1. Specialties events
        document.getElementById('searchSpec').addEventListener('input', e => {
            specState.search = e.target.value;
            specState.currentPage = 1;
            renderSpecsTable();
        });
        document.getElementById('filterFiliere').addEventListener('change', e => {
            specState.filiere = e.target.value;
            specState.currentPage = 1;
            renderSpecsTable();
        });
        document.getElementById('filterDuration').addEventListener('change', e => {
            specState.duration = e.target.value;
            specState.currentPage = 1;
            renderSpecsTable();
        });
        document.getElementById('pageSizeSpec').addEventListener('change', e => {
            specState.pageSize = e.target.value;
            specState.currentPage = 1;
            renderSpecsTable();
        });
        document.getElementById('resetSpecFilters').addEventListener('click', () => {
            document.getElementById('searchSpec').value = '';
            document.getElementById('filterFiliere').value = '';
            document.getElementById('filterDuration').value = '';
            specState.search = '';
            specState.filiere = '';
            specState.duration = '';
            specState.currentPage = 1;
            renderSpecsTable();
        });

        // Specs Column Sorting
        document.querySelectorAll('#specsTable .sortable-header').forEach(h => {
            h.addEventListener('click', () => {
                const col = h.getAttribute('data-col');
                if (specState.sortBy === col) {
                    specState.sortOrder = specState.sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    specState.sortBy = col;
                    specState.sortOrder = 'asc';
                }
                specState.currentPage = 1;
                renderSpecsTable();
            });
        });

        // 2. Offers events
        document.getElementById('searchOffre').addEventListener('input', e => {
            offreState.search = e.target.value;
            offreState.currentPage = 1;
            renderOffresTable();
        });
        document.getElementById('filterDiplome').addEventListener('change', e => {
            offreState.diplome = e.target.value;
            offreState.currentPage = 1;
            renderOffresTable();
        });
        document.getElementById('filterMode').addEventListener('change', e => {
            offreState.mode = e.target.value;
            offreState.currentPage = 1;
            renderOffresTable();
        });
        document.getElementById('filterNiveau').addEventListener('change', e => {
            offreState.niveau = e.target.value;
            offreState.currentPage = 1;
            renderOffresTable();
        });
        document.getElementById('pageSizeOffre').addEventListener('change', e => {
            offreState.pageSize = e.target.value;
            offreState.currentPage = 1;
            renderOffresTable();
        });
        document.getElementById('resetOffreFilters').addEventListener('click', () => {
            document.getElementById('searchOffre').value = '';
            document.getElementById('filterDiplome').value = '';
            document.getElementById('filterMode').value = '';
            document.getElementById('filterNiveau').value = '';
            offreState.search = '';
            offreState.diplome = '';
            offreState.mode = '';
            offreState.niveau = '';
            offreState.currentPage = 1;
            renderOffresTable();
        });

        // Offers Column Sorting
        document.querySelectorAll('#activeOffresTable .sortable-header').forEach(h => {
            h.addEventListener('click', () => {
                const col = h.getAttribute('data-col');
                if (offreState.sortBy === col) {
                    offreState.sortOrder = offreState.sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    offreState.sortBy = col;
                    offreState.sortOrder = 'asc';
                }
                offreState.currentPage = 1;
                renderOffresTable();
            });
        });

        // Drag & Drop / File Input parser preview
        const fileInput = document.getElementById('csv_file_input');
        const dropzone = document.getElementById('dropzone');
        if (fileInput && dropzone) {
            fileInput.addEventListener('change', function(e) {
                const file = fileInput.files[0];
                if (!file) return;

                const name = file.name;
                document.getElementById('selected-filename').textContent = name;
                document.querySelector('.selected-file-info').classList.remove('d-none');
                document.querySelector('.dropzone-info').classList.add('d-none');
                dropzone.style.borderColor = '#643edb';
                dropzone.style.backgroundColor = '#f5f3ff';

                // Client-side parser for preview table
                const reader = new FileReader();
                reader.onload = function(evt) {
                    const text = evt.target.result;
                    const lines = text.split(/\r\n|\r|\n/);
                    let previewRows = [];
                    let rowCount = 0;
                    
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i].trim();
                        if (line === '') continue;
                        
                        // Skip header
                        if (i === 0 && (line.includes('CodeSpec') || line.includes('الرمز') || line.includes('Code'))) {
                            continue;
                        }

                        const row = parseCsvLine(line);
                        if (row.length >= 4) {
                            previewRows.push(row);
                            rowCount++;
                            if (previewRows.length >= 3) break;
                        }
                    }

                    renderCsvPreview(previewRows, rowCount);
                };
                reader.readAsText(file, 'UTF-8');
            });

            dropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropzone.classList.add('drag-over');
            });

            dropzone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                dropzone.classList.remove('drag-over');
            });

            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropzone.classList.remove('drag-over');
            });
        }

        // Auto-calculation & Dynamic values logic for Offers Forms (Add/Edit)
        function updateEndDate(modalPrefix) {
            const specSelect = document.getElementById(modalPrefix + '_offre_specialite_id');
            const debutInput = document.getElementById(modalPrefix + '_offre_debut');
            const finInput   = document.getElementById(modalPrefix + '_offre_fin');
            if (!specSelect || !debutInput || !finInput) return;
            const selectedOption = specSelect.options[specSelect.selectedIndex];
            if (!selectedOption) return;
            const semesters = parseInt(selectedOption.getAttribute('data-duree') || 0);
            const startDateStr = debutInput.value;
            if (semesters > 0 && startDateStr) {
                const startDate = new Date(startDateStr);
                if (!isNaN(startDate.getTime())) {
                    const monthsToAdd = semesters * 6;
                    const targetDate = new Date(startDate.getFullYear(), startDate.getMonth() + monthsToAdd, startDate.getDate());
                    const yyyy = targetDate.getFullYear();
                    const mm   = String(targetDate.getMonth() + 1).padStart(2, '0');
                    const dd   = String(targetDate.getDate()).padStart(2, '0');
                    finInput.value = `${yyyy}-${mm}-${dd}`;
                }
            }
        }

        function updateDiplomeAndNiveau(modalPrefix) {
            const specSelect    = document.getElementById(modalPrefix + '_offre_specialite_id');
            const diplomeSelect = document.getElementById(modalPrefix + '_offre_diplome');
            const niveauSelect  = document.getElementById(modalPrefix + '_offre_niveau');
            if (!specSelect || !diplomeSelect || !niveauSelect) return;
            const selectedOption = specSelect.options[specSelect.selectedIndex];
            if (!selectedOption) return;
            
            const semesters = parseInt(selectedOption.getAttribute('data-duree') || 0);
            const specName = selectedOption.text || '';
            
            let inferredDiplome = '';
            let inferredDiplomeText = '';
            let inferredNiveau = '';
            let inferredNiveauText = '';
            
            if (specName.indexOf('تقني سامي') !== -1 || specName.toLowerCase().indexOf('ts') !== -1 || specName.toLowerCase().indexOf('supérieur') !== -1 || semesters === 5) {
                inferredDiplome = 'BTS';
                inferredDiplomeText = 'BTS (تقني سامي)';
                inferredNiveau = 'bac';
                inferredNiveauText = 'ثالثة ثانوي أو بكالوريا / Bac';
            } else if (specName.indexOf('تقني') !== -1 || specName.toLowerCase().indexOf('technicien') !== -1 || semesters === 4 || semesters === 3) {
                inferredDiplome = 'BP';
                inferredDiplomeText = 'BP (تحكم مهني)';
                inferredNiveau = 'bem';
                inferredNiveauText = 'تعليم متوسط / BEM';
            } else if (specName.indexOf('أهلية مهنية') !== -1 || specName.toLowerCase().indexOf('cap') !== -1 || semesters === 2 || semesters === 1) {
                inferredDiplome = 'CAP';
                inferredDiplomeText = 'CAP (أهلية مهنية)';
                inferredNiveau = 'sans_niveau';
                inferredNiveauText = 'بدون مستوى';
            } else {
                // Default fallback based on semesters duration
                if (semesters === 5) {
                    inferredDiplome = 'BTS';
                    inferredDiplomeText = 'BTS (تقني سامي)';
                    inferredNiveau = 'bac';
                    inferredNiveauText = 'ثالثة ثانوي أو بكالوريا / Bac';
                } else if (semesters >= 3) {
                    inferredDiplome = 'BP';
                    inferredDiplomeText = 'BP (تحكم مهني)';
                    inferredNiveau = 'bem';
                    inferredNiveauText = 'تعليم متوسط / BEM';
                } else if (semesters > 0) {
                    inferredDiplome = 'CAP';
                    inferredDiplomeText = 'CAP (أهلية مهنية)';
                    inferredNiveau = 'sans_niveau';
                    inferredNiveauText = 'بدون مستوى';
                }
            }
            
            if (inferredDiplome) {
                let diplomeExists = false;
                for (let i = 0; i < diplomeSelect.options.length; i++) {
                    if (diplomeSelect.options[i].value === inferredDiplome) {
                        diplomeExists = true;
                        diplomeSelect.selectedIndex = i;
                        break;
                    }
                }
                if (!diplomeExists) {
                    const newOpt = document.createElement('option');
                    newOpt.value = inferredDiplome;
                    newOpt.text = inferredDiplomeText || inferredDiplome;
                    diplomeSelect.appendChild(newOpt);
                    diplomeSelect.value = inferredDiplome;
                }
            }
            
            if (inferredNiveau) {
                let niveauExists = false;
                for (let i = 0; i < niveauSelect.options.length; i++) {
                    if (niveauSelect.options[i].value === inferredNiveau) {
                        niveauExists = true;
                        niveauSelect.selectedIndex = i;
                        break;
                    }
                }
                if (!niveauExists) {
                    const newOpt = document.createElement('option');
                    newOpt.value = inferredNiveau;
                    newOpt.text = inferredNiveauText || inferredNiveau;
                    niveauSelect.appendChild(newOpt);
                    niveauSelect.value = inferredNiveau;
                }
            }
        }

        function updateSessionName(modalPrefix) {
            const selectionInput = document.getElementById(modalPrefix + '_offre_debut_selection');
            const sessionInput   = document.getElementById(modalPrefix + '_offre_session_name');
            if (!selectionInput || !sessionInput) return;
            const selectionDateStr = selectionInput.value;
            if (selectionDateStr) {
                const date = new Date(selectionDateStr);
                if (!isNaN(date.getTime())) {
                    const year  = date.getFullYear();
                    const month = date.getMonth() + 1;
                    let sessionName = '';
                    if (month >= 1 && month <= 5)  sessionName = `دورة فيفري ${year}`;
                    else if (month >= 6 && month <= 9)  sessionName = `دورة سبتمبر ${year}`;
                    else if (month >= 10 && month <= 12) sessionName = `دورة أكتوبر ${year}`;
                    if (sessionName) sessionInput.value = sessionName;
                }
            }
        }

        // Attach calculate listeners
        ['add', 'edit'].forEach(prefix => {
            const specSelect     = document.getElementById(prefix + '_offre_specialite_id');
            const debutInput     = document.getElementById(prefix + '_offre_debut');
            const selectionInput = document.getElementById(prefix + '_offre_debut_selection');

            if (specSelect) {
                specSelect.addEventListener('change', function() {
                    updateDiplomeAndNiveau(prefix);
                    updateEndDate(prefix);
                });
            }
            if (debutInput) {
                debutInput.addEventListener('change', function() {
                    updateEndDate(prefix);
                });
            }
            if (selectionInput) {
                selectionInput.addEventListener('change', function() {
                    updateSessionName(prefix);
                });
            }
        });

    });
    </script>
</div>
@endsection
