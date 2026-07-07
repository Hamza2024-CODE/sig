<?php
/**
 * @var array $stats
 * @var array $wilayas
 * @var array $partenaires
 */
?>
@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')

@section('styles')
<style>
    /* Premium Design System and CSS Variables */
    :root {
        --transition-premium: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow-premium: 0 10px 30px -10px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-premium-hover: 0 20px 40px -15px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.05);
        
        --color-purple-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --color-blue-gradient: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
        --color-green-gradient: linear-gradient(135deg, #047857 0%, #10b981 100%);
        --color-orange-gradient: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
    }

    /* Bento Statistics Panel */
    .stats-bento-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }
    @media (max-width: 992px) {
        .stats-bento-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
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
        min-height: 125px;
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-premium-hover) !important;
    }
    .stat-card-purple { background: var(--color-purple-gradient); }
    .stat-card-blue { background: var(--color-blue-gradient); }
    .stat-card-green { background: var(--color-green-gradient); }
    .stat-card-orange { background: var(--color-orange-gradient); }

    .stat-icon-wrapper {
        width: 44px;
        height: 44px;
        border-radius: 12px;
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

    /* Filters Console */
    .filter-card {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.05);
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

    /* Column Header Sorting */
    .sortable-header {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.2s ease;
    }
    .sortable-header:hover {
        background-color: rgba(100, 62, 219, 0.05) !important;
        color: #643edb !important;
    }

    /* Sector Badges */
    .badge-sector {
        padding: 0.45rem 0.8rem;
        font-weight: 600;
        border-radius: 50px;
        font-size: 0.76rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .badge-sector-public {
        background-color: rgba(30, 64, 175, 0.1);
        color: #1d4ed8;
        border: 1px solid rgba(30, 64, 175, 0.2);
    }
    .badge-sector-private {
        background-color: rgba(4, 120, 87, 0.1);
        color: #047857;
        border: 1px solid rgba(4, 120, 87, 0.2);
    }

    /* Pagination */
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

    /* Glassmorphic Modals */
    .modal-content-glass {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    .modal-header { border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important; }
    .modal-footer { border-top: 1px solid rgba(0, 0, 0, 0.05) !important; }

    /* Action Buttons */
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-premium);
        border: none;
    }
    .action-btn-edit {
        background-color: rgba(99, 102, 241, 0.08);
        color: #6366f1;
    }
    .action-btn-edit:hover {
        background-color: #6366f1;
        color: #fff;
        transform: translateY(-2px);
    }
    .action-btn-delete {
        background-color: rgba(239, 68, 68, 0.08);
        color: #ef4444;
    }
    .action-btn-delete:hover {
        background-color: #ef4444;
        color: #fff;
        transform: translateY(-2px);
    }

    @keyframes pulseRed {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
</style>
@endsection

@section('content')
<div class="container-fluid animate__animated animate__fadeIn" style="font-family: 'Cairo', sans-serif; direction: rtl;">
    <!-- Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 p-3" role="alert" style="background-color: rgba(16, 185, 129, 0.12); color: #047857; border: 1px solid rgba(16, 185, 129, 0.25);">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-4 me-2"></i>
                <div class="fw-bold fs-6 ms-2"><?= session('flash_success');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 p-3" role="alert" style="background-color: rgba(239, 68, 68, 0.12); color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.25);">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
                <div class="fw-bold fs-6 ms-2"><?= session('flash_error');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header Block -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-3 border-bottom border-secondary border-opacity-10 gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-building text-primary me-2" style="color: #643edb;"></i> المؤسسات الاقتصادية الشريكة
            </h3>
            <p class="text-muted small mb-0">سجل الشركاء الاقتصاديين المستقبِلين للمتربصين والمتمهنين لدعم التكوين الميداني والتمهين</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="/dashboard/maitres-apprentissage" class="btn btn-outline-dark d-flex align-items-center px-4 fw-bold gap-2" style="border-radius: 50px; border: 1.5px solid #cbd5e1; font-size: 0.88rem;">
                <i class="fa-solid fa-person-chalkboard text-muted"></i> معلمو التمهين
            </a>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; font-size: 0.88rem;" data-bs-toggle="modal" data-bs-target="#addPartenaireModal">
                <i class="fa-solid fa-plus"></i> إضافة مؤسسة شريكة
            </button>
        </div>
    </div>

    <!-- Bento Stats Grid (Fixes white-on-white text visibility) -->
    <div class="stats-bento-grid mb-4">
        <div class="stat-card stat-card-purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">إجمالي المؤسسات الشريكة</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['total']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-building fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-info-circle me-1"></i> الشركاء النشطين في قاعدة البيانات</span>
        </div>

        <div class="stat-card stat-card-blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">القطاع العام</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['publiques']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-landmark fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-landmark me-1"></i> مؤسسات حكومية وعامة شريكة</span>
        </div>

        <div class="stat-card stat-card-green">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">القطاع الخاص</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['privees']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-industry fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-industry me-1"></i> مؤسسات وشركات خاصة</span>
        </div>

        <div class="stat-card stat-card-orange">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">الولايات النشطة</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['wilayas']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-map-location-dot fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-map me-1"></i> تغطية جغرافية للشركاء</span>
        </div>
    </div>

    <!-- Filters & Partners Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-table-list text-primary"></i> سجل وعروض الشركاء الاقتصاديين
                </h5>
            </div>
            <div class="d-flex gap-2 align-self-end no-print">
                <button onclick="exportToExcel()" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-1.5" style="font-size: 0.82rem;">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </button>
                <button onclick="exportToCSV()" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-1.5" style="font-size: 0.82rem;">
                    <i class="fa-solid fa-file-csv"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-1.5" style="font-size: 0.82rem;">
                    <i class="fa-solid fa-print"></i> طباعة
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="position-relative">
                        <input type="text" id="searchConsole" oninput="handleSearch(this.value)" class="form-control form-control-premium pe-5 ps-3" placeholder="البحث باسم المؤسسة، النشاط، العنوان...">
                        <i class="fa-solid fa-search text-muted position-absolute" style="top: 50%; right: 1.25rem; transform: translateY(-50%);"></i>
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="sectorFilter" onchange="handleSectorFilter(this.value)" class="form-select form-control-premium">
                        <option value="all">كل القطاعات</option>
                        <option value="public">قطاع عام</option>
                        <option value="prive">قطاع خاص</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="wilayaFilter" onchange="handleWilayaFilter(this.value)" class="form-select form-control-premium">
                        <option value="all">كل الولايات</option>
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= htmlspecialchars($w['nom_ar']) ?>"><?= htmlspecialchars($w['code']) ?> - <?= htmlspecialchars($w['nom_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="pageSizeSelect" onchange="handlePageSize(this.value)" class="form-select form-control-premium">
                        <option value="10" selected>10 سجلات للصفحة</option>
                        <option value="25">25 سجل للصفحة</option>
                        <option value="50">50 سجل للصفحة</option>
                        <option value="all">كل السجلات دفعة واحدة</option>
                    </select>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive rounded-3 border">
                <table class="table table-hover align-middle mb-0" id="partenairesTable">
                    <thead class="bg-light text-muted small fw-bold text-center">
                        <tr>
                            <th class="ps-4 sortable-header text-start py-3" onclick="handleSort('Nom')">
                                اسم المؤسسة الشريكة <i id="sort_icon_Nom" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header text-start py-3" onclick="handleSort('Activite')">
                                النشاط / قطاع الأعمال <i id="sort_icon_Activite" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header py-3" onclick="handleSort('wilaya_ar')">
                                الولاية الجغرافية <i id="sort_icon_wilaya_ar" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header py-3" onclick="handleSort('Tel')">
                                رقم الهاتف <i id="sort_icon_Tel" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header py-3" onclick="handleSort('Secteur')">
                                طبيعة القطاع <i id="sort_icon_Secteur" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="pe-4 py-3 no-print" style="width: 120px;">العمليات</th>
                        </tr>
                    </thead>
                    <tbody id="partnersTableBody">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Footer Pagination Controls -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 gap-3">
                <div class="text-muted small fw-semibold" id="tableStatsInfo">
                    جاري تحميل البيانات...
                </div>
                <nav aria-label="Page navigation" class="no-print">
                    <ul class="pagination pagination-sm mb-0 justify-content-center" id="tablePagination">
                        <!-- Filled by JS -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Add Partner Modal -->
<div class="modal fade" id="addPartenaireModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-plus-circle text-primary me-2"></i> إضافة مؤسسة اقتصادية شريكة جديدة
                </h5>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/partenaires/store" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="add_nom" class="form-label small fw-bold text-secondary">اسم المؤسسة بالعربية *</label>
                            <input type="text" id="add_nom" name="nom" class="form-control form-control-premium" placeholder="مثال: المؤسسة الوطنية للترقية العقارية" required>
                            <div class="invalid-feedback">يرجى إدخال اسم المؤسسة بالعربية.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="add_nom_fr" class="form-label small fw-bold text-secondary">الاسم بالفرنسية</label>
                            <input type="text" id="add_nom_fr" name="nom_fr" class="form-control form-control-premium" placeholder="Ex: ENPI Spécialités">
                        </div>
                        <div class="col-md-6">
                            <label for="add_activite" class="form-label small fw-bold text-secondary">النشاط الاقتصادي الرئيسي</label>
                            <input type="text" id="add_activite" name="activite" class="form-control form-control-premium" placeholder="مثال: البناء والأشغال العمومية، تكنولوجيا المعلومات">
                        </div>
                        <div class="col-md-6">
                            <label for="add_secteur" class="form-label small fw-bold text-secondary">طبيعة القطاع *</label>
                            <select id="add_secteur" name="secteur" class="form-select form-control-premium" required>
                                <option value="prive" selected>قطاع خاص (Privé)</option>
                                <option value="public">قطاع عام (Public)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_tel" class="form-label small fw-bold text-secondary">رقم الهاتف</label>
                            <input type="text" id="add_tel" name="tel" class="form-control form-control-premium" placeholder="مثال: 021XX XX XX">
                        </div>
                        <div class="col-md-6">
                            <?php 
                                $user = session('user');
                                $role = strtolower($user['role_code'] ?? '');
                                $userWilayaId = $user['wilaya_id'] ?? null;
                                if (empty($userWilayaId) && !in_array($role, ['admin', 'central']) && !empty($user['iddfep']) && $user['iddfep'] > 0) {
                                    $userWilayaId = (int)$user['iddfep'];
                                }
                                $isScoped = !in_array($role, ['admin', 'central']) && $userWilayaId > 0;
                            ?>
                            <label for="add_wilaya_id" class="form-label small fw-bold text-secondary">الولاية الجغرافية *</label>
                            <?php if ($isScoped): ?>
                                <select id="add_wilaya_id_disabled" class="form-select form-control-premium text-muted" disabled>
                                    <?php foreach ($wilayas as $w): ?>
                                        <option value="<?= $w['id'] ?>" <?= $w['id'] == $userWilayaId ? 'selected' : '' ?>><?= $w['code'] ?> - <?= $w['nom_ar'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="wilaya_id" value="<?= $userWilayaId ?>">
                            <?php else: ?>
                                <select id="add_wilaya_id" name="wilaya_id" class="form-select form-control-premium" required>
                                    <option value="" disabled selected>اختر الولاية...</option>
                                    <?php foreach ($wilayas as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= $w['code'] ?> - <?= $w['nom_ar'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">يرجى اختيار الولاية الجغرافية.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label for="add_adresse" class="form-label small fw-bold text-secondary">العنوان الكامل للمؤسسة</label>
                            <input type="text" id="add_adresse" name="adresse" class="form-control form-control-premium" placeholder="العنوان الكامل للمؤسسة الاقتصادية">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ وإضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Partner Modal -->
<div class="modal fade" id="editPartenaireModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-edit text-warning me-2"></i> تعديل بيانات المؤسسة الشريكة
                </h5>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/partenaires/update" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="id" id="edit_p_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_p_nom" class="form-label small fw-bold text-secondary">اسم المؤسسة بالعربية *</label>
                            <input type="text" id="edit_p_nom" name="nom" class="form-control form-control-premium" required>
                            <div class="invalid-feedback">يرجى إدخال اسم المؤسسة بالعربية.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_p_nom_fr" class="form-label small fw-bold text-secondary">الاسم بالفرنسية</label>
                            <input type="text" id="edit_p_nom_fr" name="nom_fr" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_p_activite" class="form-label small fw-bold text-secondary">النشاط الاقتصادي</label>
                            <input type="text" id="edit_p_activite" name="activite" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_p_secteur" class="form-label small fw-bold text-secondary">طبيعة القطاع *</label>
                            <select id="edit_p_secteur" name="secteur" class="form-select form-control-premium" required>
                                <option value="prive">قطاع خاص (Privé)</option>
                                <option value="public">قطاع عام (Public)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_p_tel" class="form-label small fw-bold text-secondary">رقم الهاتف</label>
                            <input type="text" id="edit_p_tel" name="tel" class="form-control form-control-premium">
                        </div>
                        <div class="col-12">
                            <label for="edit_p_adresse" class="form-label small fw-bold text-secondary">العنوان الكامل للمؤسسة</label>
                            <input type="text" id="edit_p_adresse" name="adresse" class="form-control form-control-premium">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-white">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Premium Glassmorphic Design) -->
<div class="modal fade animate__animated animate__fadeIn" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content modal-content-glass border-0">
            <div class="modal-body p-4 text-center">
                <!-- Pulsing Warning Icon -->
                <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; border-radius: 50%; background-color: rgba(239, 68, 68, 0.12); color: #ef4444; animation: pulseRed 2s infinite;">
                    <i class="fa-solid fa-trash-can fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" style="font-family: 'Cairo', sans-serif;">تأكيد الحذف نهائياً</h5>
                <p class="text-secondary small mb-4 px-2" style="line-height: 1.6;">
                    هل أنت متأكد من رغبتك في حذف هذه المؤسسة الاقتصادية الشريكة نهائياً؟ <br>
                    <strong class="text-danger">تنبيه: لا يمكن التراجع عن هذا الإجراء بعد إتمامه.</strong>
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal" style="font-size: 0.88rem;">إلغاء</button>
                    <button type="button" onclick="executeDeletePartenaire()" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); border: none; font-size: 0.88rem;">
                        <i class="fa-solid fa-trash-can"></i> تأكيد الحذف
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="partenaires-json-data" data-partners="<?= htmlspecialchars(json_encode($partenaires), ENT_QUOTES, 'UTF-8') ?>" style="display: none;"></div>

<!-- Scripts Section for Interactive Partners Console -->
<script>
    // Load PHP partners list data into local JS memory
    const rawPartenairesData = JSON.parse(document.getElementById('partenaires-json-data').getAttribute('data-partners') || '[]');
    let partnersData = [...rawPartenairesData];

    // Helper to check if a sector is public
    function isPublicSector(secteur) {
        if (secteur === undefined || secteur === null) return false;
        let s = secteur.toString().toLowerCase().trim();
        return s.includes('public') || s.includes('عام') || s === '1';
    }

    // Local state variables
    let currentPage = 1;
    let itemsPerPage = 10;
    let sortColumn = 'Nom';
    let sortDirection = 'asc';
    let searchQuery = '';
    let sectorFilter = 'all';
    let wilayaFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        // Run initial table render
        refreshConsole();

        // Form validation scripts
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Ensure backdrop cleanup on modals
        const modalIds = ['addPartenaireModal', 'editPartenaireModal'];
        modalIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('hidden.bs.modal', function() {
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                });
            }
        });
    });

    // Main render engine
    function refreshConsole() {
        // 1. Filter
        let filtered = rawPartenairesData.filter(function(row) {
            let matchesSearch = true;
            if (searchQuery) {
                let nom = (row.Nom || '').toLowerCase();
                let nomFr = (row.NomFr || '').toLowerCase();
                let act = (row.Activite || '').toLowerCase();
                let adr = (row.Adresse || '').toLowerCase();
                let term = searchQuery.toLowerCase();
                matchesSearch = nom.includes(term) || nomFr.includes(term) || act.includes(term) || adr.includes(term);
            }

            let matchesSector = true;
            if (sectorFilter !== 'all') {
                let isPublic = isPublicSector(row.Secteur);
                if (sectorFilter === 'public') {
                    matchesSector = isPublic;
                } else {
                    matchesSector = !isPublic;
                }
            }

            let matchesWilaya = true;
            if (wilayaFilter !== 'all') {
                matchesWilaya = (row.wilaya_ar === wilayaFilter);
            }

            return matchesSearch && matchesSector && matchesWilaya;
        });

        // Save filtered reference for exports
        partnersData = [...filtered];

        // 2. Sort
        filtered.sort(function(a, b) {
            let valA = a[sortColumn] || '';
            let valB = b[sortColumn] || '';

            valA = valA.toString().toLowerCase();
            valB = valB.toString().toLowerCase();

            if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        // Update Sorting Indicators
        const sortableCols = ['Nom', 'Activite', 'wilaya_ar', 'Tel', 'Secteur'];
        sortableCols.forEach(col => {
            const icon = document.getElementById('sort_icon_' + col);
            if (icon) {
                if (col === sortColumn) {
                    icon.className = sortDirection === 'asc' ? 'fa-solid fa-sort-up ms-1 text-primary' : 'fa-solid fa-sort-down ms-1 text-primary';
                } else {
                    icon.className = 'fa-solid fa-sort ms-1 text-muted text-opacity-50';
                }
            }
        });

        // 3. Paginate
        let totalItems = filtered.length;
        let totalPages = Math.ceil(totalItems / itemsPerPage);
        if (itemsPerPage === 'all') {
            totalPages = 1;
            currentPage = 1;
        } else {
            if (currentPage > totalPages) currentPage = totalPages || 1;
        }

        let startIndex = (currentPage - 1) * itemsPerPage;
        let paginated = (itemsPerPage === 'all') ? filtered : filtered.slice(startIndex, startIndex + parseInt(itemsPerPage));

        // 4. Render Table rows
        const tbody = document.getElementById('partnersTableBody');
        tbody.innerHTML = '';

        if (paginated.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-building-circle-xmark fs-3 mb-2 text-secondary text-opacity-50"></i>
                        <div>لا توجد مؤسسات شريكة تطابق معايير البحث الحالية.</div>
                    </td>
                </tr>
            `;
        } else {
            paginated.forEach(function(row) {
                let badgeHTML = '';
                if (isPublicSector(row.Secteur)) {
                    badgeHTML = `<span class="badge-sector badge-sector-public"><i class="fa-solid fa-landmark"></i> عام</span>`;
                } else {
                    badgeHTML = `<span class="badge-sector badge-sector-private"><i class="fa-solid fa-industry"></i> خاص</span>`;
                }

                let escRow = JSON.stringify(row).replace(/"/g, '&quot;');

                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="ps-4 text-start">
                        <div class="fw-bold text-dark">${escapeHtml(row.Nom)}</div>
                        ${row.NomFr ? `<div class="text-muted small">${escapeHtml(row.NomFr)}</div>` : ''}
                    </td>
                    <td class="text-start"><span class="text-muted small">${escapeHtml(row.Activite || '—')}</span></td>
                    <td class="text-center fw-semibold text-dark">${escapeHtml(row.wilaya_ar || '—')}</td>
                    <td class="text-center text-muted small">${escapeHtml(row.Tel || '—')}</td>
                    <td class="text-center">${badgeHTML}</td>
                    <td class="pe-4 text-center no-print">
                        <div class="d-flex justify-content-center gap-1.5">
                            <button onclick="editPartenaire(${escRow})" class="action-btn action-btn-edit" title="تعديل">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            <button onclick="deletePartenaire(${row.IDEntreprise})" class="action-btn action-btn-delete" title="حذف">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 5. Update Status Info label
        const statsLabel = document.getElementById('tableStatsInfo');
        if (totalItems === 0) {
            statsLabel.innerHTML = 'لا توجد سجلات لعرضها';
        } else {
            let endItem = (itemsPerPage === 'all') ? totalItems : Math.min(startIndex + parseInt(itemsPerPage), totalItems);
            statsLabel.innerHTML = `عرض السجلات <span class="text-primary fw-bold">${startIndex + 1}</span> إلى <span class="text-primary fw-bold">${endItem}</span> من أصل <span class="text-dark fw-bold">${totalItems}</span> مؤسسة شريكة مسجلة`;
        }

        // 6. Update Pagination links
        renderPaginationControls(totalPages);
    }

    function renderPaginationControls(totalPages) {
        const pagUl = document.getElementById('tablePagination');
        pagUl.innerHTML = '';
        if (totalPages <= 1 || itemsPerPage === 'all') return;

        // Previous button
        let prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;" aria-label="Previous"><i class="fa-solid fa-chevron-right small"></i></a>`;
        pagUl.appendChild(prevLi);

        // Page numbers visible range
        let startPage = Math.max(1, currentPage - 1);
        let endPage = Math.min(totalPages, currentPage + 1);

        if (startPage > 1) {
            let firstLi = document.createElement('li');
            firstLi.className = `page-item`;
            firstLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(1); return false;">1</a>`;
            pagUl.appendChild(firstLi);

            if (startPage > 2) {
                let ellLi = document.createElement('li');
                ellLi.className = `page-item disabled`;
                ellLi.innerHTML = `<span class="page-link">...</span>`;
                pagUl.appendChild(ellLi);
            }
        }

        for (let p = startPage; p <= endPage; p++) {
            let pLi = document.createElement('li');
            pLi.className = `page-item ${p === currentPage ? 'active' : ''}`;
            pLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${p}); return false;">${p}</a>`;
            pagUl.appendChild(pLi);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                let ellLi = document.createElement('li');
                ellLi.className = `page-item disabled`;
                ellLi.innerHTML = `<span class="page-link">...</span>`;
                pagUl.appendChild(ellLi);
            }

            let lastLi = document.createElement('li');
            lastLi.className = `page-item`;
            lastLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>`;
            pagUl.appendChild(lastLi);
        }

        // Next button
        let nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;" aria-label="Next"><i class="fa-solid fa-chevron-left small"></i></a>`;
        pagUl.appendChild(nextLi);
    }

    // Interactivity event handlers
    function handleSearch(val) {
        searchQuery = val;
        currentPage = 1;
        refreshConsole();
    }

    function handleSectorFilter(val) {
        sectorFilter = val;
        currentPage = 1;
        refreshConsole();
    }

    function handleWilayaFilter(val) {
        wilayaFilter = val;
        currentPage = 1;
        refreshConsole();
    }

    function handlePageSize(val) {
        itemsPerPage = val;
        currentPage = 1;
        refreshConsole();
    }

    function handleSort(col) {
        if (sortColumn === col) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn = col;
            sortDirection = 'asc';
        }
        refreshConsole();
    }

    function changePage(p) {
        currentPage = p;
        refreshConsole();
    }

    // Edit Partenaire Modal filling
    function editPartenaire(p) {
        document.getElementById('edit_p_id').value = p.IDEntreprise;
        document.getElementById('edit_p_nom').value = p.Nom || '';
        document.getElementById('edit_p_nom_fr').value = p.NomFr || '';
        document.getElementById('edit_p_activite').value = p.Activite || '';
        document.getElementById('edit_p_tel').value = p.Tel || '';
        document.getElementById('edit_p_adresse').value = p.Adresse || '';
        
        const sect = document.getElementById('edit_p_secteur');
        sect.value = isPublicSector(p.Secteur) ? 'public' : 'prive';

        // Clear validation
        const form = document.querySelector('#editPartenaireModal form');
        form.classList.remove('was-validated');

        // Show edit modal
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editPartenaireModal'));
        modal.show();
    }

    // Delete confirmation state
    let partnerIdToDelete = null;

    function deletePartenaire(id) {
        partnerIdToDelete = id;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    function executeDeletePartenaire() {
        if (!partnerIdToDelete) return;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/dashboard/partenaires/delete/${partnerIdToDelete}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= csrf_token() ?? '' ?>';
        form.appendChild(csrfToken);

        document.body.appendChild(form);
        form.submit();
    }

    // Escape HTML strings helper
    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Safe Excel Exporter (escaped tags concatenation)
    function exportToExcel() {
        let xmlRows = '';
        partnersData.forEach(function(item) {
            let sectorText = isPublicSector(item.Secteur) ? 'عام' : 'خاص';
            
            xmlRows += `
                <` + `ss:Row>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.Nom)}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.NomFr || '')}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.Activite || '—')}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.wilaya_ar || '—')}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.Tel || '—')}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(sectorText)}<` + `/ss:Data><` + `/ss:Cell>
                <` + `/ss:Row>
            `;
        });

        let xlsTemplate = `\x3C?xml version="1.0" encoding="utf-8"?>
            \x3Cmso-application progid="Excel.Sheet"?>
            <` + `ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
                           xmlns:x="urn:schemas-microsoft-com:office:excel"
                           xmlns:o="urn:schemas-microsoft-com:office:office"
                           xmlns:html="http://www.w3.org/TR/REC-html40">
                <` + `ss:Styles>
                    <` + `ss:Style ss:ID="HeaderStyle">
                        <` + `ss:Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11" ss:Name="Cairo"/>
                        <` + `ss:Interior ss:Color="#4F46E5" ss:Pattern="Solid"/>
                        <` + `ss:Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                    <` + `/ss:Style>
                    <` + `ss:Style ss:ID="Default">
                        <` + `ss:Font ss:Name="Cairo" ss:Size="10"/>
                        <` + `ss:Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                    <` + `/ss:Style>
                <` + `/ss:Styles>
                <` + `ss:Worksheet ss:Name="EconomicPartners">
                    <` + `ss:Table>
                        <` + `ss:Column ss:Width="200"/>
                        <` + `ss:Column ss:Width="180"/>
                        <` + `ss:Column ss:Width="180"/>
                        <` + `ss:Column ss:Width="100"/>
                        <` + `ss:Column ss:Width="120"/>
                        <` + `ss:Column ss:Width="80"/>
                        <` + `ss:Row ss:Height="25">
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">اسم المؤسسة بالعربية<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">الاسم بالفرنسية<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">النشاط الاقتصادي<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">الولاية<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">الهاتف<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">القطاع<` + `/ss:Data><` + `/ss:Cell>
                        <` + `/ss:Row>
                        ${xmlRows}
                    <` + `/ss:Table>
                <` + `/ss:Worksheet>
            <` + `/ss:Workbook>
        `;

        function escapeXml(str) {
            if (!str) return '';
            return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');
        }

        let blob = new Blob([xlsTemplate], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', 'partenaires_economiques.xls');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Safe CSV Exporter
    function exportToCSV() {
        let csvContent = "\uFEFF"; // BOM for Excel Arabic UTF-8
        csvContent += "اسم المؤسسة بالعربية,الاسم بالفرنسية,النشاط الاقتصادي,الولاية,الهاتف,القطاع\n";
        
        partnersData.forEach(function(item) {
            let sectorText = isPublicSector(item.Secteur) ? 'عام' : 'خاص';
            
            let row = [
                `"${(item.Nom || '').replace(/"/g, '""')}"`,
                `"${(item.NomFr || '').replace(/"/g, '""')}"`,
                `"${(item.Activite || '').replace(/"/g, '""')}"`,
                `"${(item.wilaya_ar || '').replace(/"/g, '""')}"`,
                `"${(item.Tel || '').replace(/"/g, '""')}"`,
                `"${sectorText}"`
            ];
            csvContent += row.join(",") + "\n";
        });

        let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', 'partenaires_economiques.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endsection
