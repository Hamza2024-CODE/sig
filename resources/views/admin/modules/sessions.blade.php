<?php
/**
 * @var array $stats
 * @var array $list
 */
?>
@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')

@section('styles')
<style>
    /* Design System and Interactive Tokens */
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

    /* Bento Statistics Panel */
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
    .stat-card-orange { background: var(--color-orange-gradient); }
    .stat-card-teal { background: var(--color-teal-gradient); }
    .stat-card-green { background: var(--color-green-gradient); }

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

    /* Glassmorphic Filter Card */
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

    /* Custom Badges */
    .badge-status {
        padding: 0.5rem 0.85rem;
        font-weight: 600;
        border-radius: 50px;
        font-size: 0.78rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .badge-status-active {
        background-color: rgba(16, 185, 129, 0.12);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    .badge-status-planned {
        background-color: rgba(249, 115, 22, 0.12);
        color: #f97316;
        border: 1px solid rgba(249, 115, 22, 0.25);
    }
    .badge-status-closed {
        background-color: rgba(100, 116, 139, 0.12);
        color: #64748b;
        border: 1px solid rgba(100, 116, 139, 0.25);
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        animation: pulse-animation 1.5s infinite;
    }
    @keyframes pulse-animation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Premium Pagination */
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

    /* Modal Glassmorphism */
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
                <i class="fa-solid fa-calendar-alt text-primary me-2" style="color: #643edb;"></i> تخطيط الدورات وتوزيع الفترات الزمنية / Sessions
            </h3>
            <p class="text-muted small mb-0">جدولة دورات الدخول المهني وتوزيع العروض بالتوافق مع التوجيهات الوزارية المنظمة للقطاع</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; font-size: 0.88rem;" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                <i class="fa-solid fa-calendar-plus"></i> برمجة دورة جديدة
            </button>
        </div>
    </div>

    <!-- Bento Stats Grid -->
    <div class="stats-bento-grid mb-4">
        <div class="stat-card stat-card-purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">إجمالي الدورات</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['total_sessions']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-calendar-days fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-info-circle me-1"></i> الدورات الكلية المسجلة</span>
        </div>

        <div class="stat-card stat-card-blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">الدورات النشطة</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['en_cours']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-spinner fa-spin fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-play-circle me-1"></i> قيد الاستقبال والتسيير حالياً</span>
        </div>

        <div class="stat-card stat-card-orange">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">دورات مخطط لها</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['planifiees']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-hourglass-start fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-clock me-1"></i> مبرمجة في الفترات القادمة</span>
        </div>

        <div class="stat-card stat-card-teal">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">دورات منتهية</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['terminees']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-circle-check fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-lock me-1"></i> مغلقة ومؤرشفة نهائياً</span>
        </div>

        <div class="stat-card stat-card-green">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-white text-opacity-75 small fw-bold mb-1">المتخرجين المتوقعين</h6>
                    <h3 class="fw-bold my-1" style="font-size: 1.8rem; color: #fff;"><?= number_format($stats['diplomes_prevus']) ?></h3>
                </div>
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-graduation-cap fs-5 text-white"></i>
                </div>
            </div>
            <span class="small text-white text-opacity-75"><i class="fa-solid fa-user-graduate me-1"></i> إجمالي الطلبة النشطين بالكامل</span>
        </div>
    </div>

    <!-- Filters & Table Console -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-primary"></i> سجل الجدولة الزمنية للدورات
                </h5>
            </div>
            <div class="d-flex gap-2 align-self-end">
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
                <div class="col-md-6">
                    <div class="position-relative">
                        <input type="text" id="searchConsole" oninput="handleSearch(this.value)" class="form-control form-control-premium pe-5 ps-3" placeholder="البحث برمز الدورة، عنوانها بالعربية أو الفرنسية...">
                        <i class="fa-solid fa-search text-muted position-absolute" style="top: 50%; right: 1.25rem; transform: translateY(-50%);"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" onchange="handleStatusFilter(this.value)" class="form-select form-control-premium">
                        <option value="all">كل الحالات (جميع الدورات)</option>
                        <option value="en_cours">جارية حالياً (En cours)</option>
                        <option value="planifie">مخطط لها (Planifiées)</option>
                        <option value="terminee">منتهية ومغلقة (Clôturées)</option>
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

            <!-- Dynamic Table container -->
            <div class="table-responsive rounded-3 border">
                <table class="table table-hover align-middle mb-0" id="sessTable">
                    <thead class="bg-light text-muted small fw-bold text-center">
                        <tr>
                            <th class="ps-4 sortable-header text-start py-3" onclick="handleSort('code_session')">
                                رمز الدورة <i id="sort_icon_code_session" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header text-start py-3" onclick="handleSort('intitule_ar')">
                                اسم الدورة بالعربية <i id="sort_icon_intitule_ar" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header text-start py-3" onclick="handleSort('intitule_fr')">
                                الاسم بالفرنسية <i id="sort_icon_intitule_fr" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header py-3" onclick="handleSort('mois_entree')">
                                شهر الدخول <i id="sort_icon_mois_entree" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header py-3" onclick="handleSort('date_debut')">
                                تاريخ الدخول الفعلي <i id="sort_icon_date_debut" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="sortable-header py-3" onclick="handleSort('statut')">
                                الحالة <i id="sort_icon_statut" class="fa-solid fa-sort ms-1 text-muted text-opacity-50"></i>
                            </th>
                            <th class="pe-4 py-3" style="width: 120px;">العمليات</th>
                        </tr>
                    </thead>
                    <tbody id="sessionsTableBody">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Table Footer Controls -->
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

<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-labelledby="addSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="addSessionModalLabel">
                    <i class="fa-solid fa-calendar-plus text-primary me-2"></i> برمجة دورة بيداغوجية جديدة
                </h5>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/sessions/store" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="code_session" class="form-label small fw-bold text-secondary">رمز الدورة / Code *</label>
                        <input type="text" class="form-control form-control-premium" id="code_session" name="code_session" placeholder="مثال: FEB-2026 أو S2025" required>
                        <div class="invalid-feedback">يرجى كتابة رمز الدورة بشكل صحيح.</div>
                    </div>
                    <div class="mb-3">
                        <label for="intitule_ar" class="form-label small fw-bold text-secondary">عنوان الدورة بالعربية *</label>
                        <input type="text" class="form-control form-control-premium" id="intitule_ar" name="intitule_ar" placeholder="مثال: دورة فيفري 2026" required>
                        <div class="invalid-feedback">يرجى كتابة عنوان الدورة بالعربية.</div>
                    </div>
                    <div class="mb-3">
                        <label for="intitule_fr" class="form-label small fw-bold text-secondary">عنوان الدورة بالفرنسية *</label>
                        <input type="text" class="form-control form-control-premium" id="intitule_fr" name="intitule_fr" placeholder="مثال: Session Février 2026" required>
                        <div class="invalid-feedback">يرجى كتابة عنوان الدورة بالفرنسية.</div>
                    </div>
                    <div class="mb-3">
                        <label for="date_debut" class="form-label small fw-bold text-secondary">تاريخ البداية (الدخول الفعلي) *</label>
                        <input type="date" class="form-control form-control-premium" id="date_debut" name="date_debut" required>
                        <div class="invalid-feedback">يرجى اختيار تاريخ البداية.</div>
                    </div>
                    <div class="mb-3">
                        <label for="statut" class="form-label small fw-bold text-secondary">الحالة الأولية للدورة *</label>
                        <select class="form-select form-control-premium" id="statut" name="statut" required>
                            <option value="planifie">مخطط لها (Planifiée)</option>
                            <option value="en_cours" selected>جارية حالياً (En cours)</option>
                            <option value="terminee">منتهية ومغلقة (Clôturée)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ وبرمجة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Session Modal -->
<div class="modal fade" id="editSessionModal" tabindex="-1" aria-labelledby="editSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="editSessionModalLabel">
                    <i class="fa-solid fa-edit text-primary me-2"></i> تعديل بيانات الدورة البيداغوجية
                </h5>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/sessions/update" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_code_session" class="form-label small fw-bold text-secondary">رمز الدورة / Code *</label>
                        <input type="text" class="form-control form-control-premium" id="edit_code_session" name="code_session" required>
                        <div class="invalid-feedback">يرجى كتابة رمز الدورة بشكل صحيح.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_intitule_ar" class="form-label small fw-bold text-secondary">عنوان الدورة بالعربية *</label>
                        <input type="text" class="form-control form-control-premium" id="edit_intitule_ar" name="intitule_ar" required>
                        <div class="invalid-feedback">يرجى كتابة عنوان الدورة بالعربية.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_intitule_fr" class="form-label small fw-bold text-secondary">عنوان الدورة بالفرنسية *</label>
                        <input type="text" class="form-control form-control-premium" id="edit_intitule_fr" name="intitule_fr" required>
                        <div class="invalid-feedback">يرجى كتابة عنوان الدورة بالفرنسية.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_date_debut" class="form-label small fw-bold text-secondary">تاريخ البداية (الدخول الفعلي) *</label>
                        <input type="date" class="form-control form-control-premium" id="edit_date_debut" name="date_debut" required>
                        <div class="invalid-feedback">يرجى اختيار تاريخ البداية.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_statut" class="form-label small fw-bold text-secondary">الحالة الحالية للدورة *</label>
                        <select class="form-select form-control-premium" id="edit_statut" name="statut" required>
                            <option value="planifie">مخطط لها (Planifiée)</option>
                            <option value="en_cours">جارية حالياً (En cours)</option>
                            <option value="terminee">منتهية ومغلقة (Clôturée)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="sessions-json-data" data-sessions="<?= htmlspecialchars(json_encode($list), ENT_QUOTES, 'UTF-8') ?>" style="display: none;"></div>

<!-- Scripts Section for Interactive Console -->
<script>
    // Load PHP session list data as local JS dataset
    const rawSessionsData = JSON.parse(document.getElementById('sessions-json-data').getAttribute('data-sessions') || '[]');
    let sessionsData = [...rawSessionsData];

    // Local state variables
    let currentPage = 1;
    let itemsPerPage = 10;
    let sortColumn = 'date_debut';
    let sortDirection = 'desc';
    let searchQuery = '';
    let statusFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        // Run initial render
        refreshConsole();

        // Form validaiton scripts
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
        const modalIds = ['addSessionModal', 'editSessionModal'];
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
        let filtered = rawSessionsData.filter(function(row) {
            let matchesSearch = true;
            if (searchQuery) {
                let code = (row.code_session || '').toLowerCase();
                let nomAr = (row.intitule_ar || '').toLowerCase();
                let nomFr = (row.intitule_fr || '').toLowerCase();
                let term = searchQuery.toLowerCase();
                matchesSearch = code.includes(term) || nomAr.includes(term) || nomFr.includes(term);
            }

            let matchesStatus = true;
            if (statusFilter !== 'all') {
                matchesStatus = (row.statut === statusFilter);
            }

            return matchesSearch && matchesStatus;
        });

        // Save filtered reference for exports
        sessionsData = [...filtered];

        // 2. Sort
        filtered.sort(function(a, b) {
            let valA = a[sortColumn] || '';
            let valB = b[sortColumn] || '';

            // Handle date strings
            if (sortColumn === 'date_debut') {
                valA = new Date(valA);
                valB = new Date(valB);
            } else {
                valA = valA.toString().toLowerCase();
                valB = valB.toString().toLowerCase();
            }

            if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        // Update Sorting Indicators
        const sortableCols = ['code_session', 'intitule_ar', 'intitule_fr', 'mois_entree', 'date_debut', 'statut'];
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
        const tbody = document.getElementById('sessionsTableBody');
        tbody.innerHTML = '';

        if (paginated.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-circle-info fs-3 mb-2 text-secondary text-opacity-50"></i>
                        <div>لا توجد دورات تكوينية تطابق معايير البحث الحالية.</div>
                    </td>
                </tr>
            `;
        } else {
            paginated.forEach(function(row) {
                let badgeHTML = '';
                if (row.statut === 'en_cours') {
                    badgeHTML = `<span class="badge-status badge-status-active"><span class="pulse-dot"></span> جارية الآن</span>`;
                } else if (row.statut === 'planifie') {
                    badgeHTML = `<span class="badge-status badge-status-planned"><i class="fa-solid fa-clock"></i> مخطط لها</span>`;
                } else {
                    badgeHTML = `<span class="badge-status badge-status-closed"><i class="fa-solid fa-check-double"></i> منتهية</span>`;
                }

                let escRow = JSON.stringify(row).replace(/"/g, '&quot;');

                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="ps-4 fw-bold text-primary text-start">${escapeHtml(row.code_session)}</td>
                    <td class="text-start"><span class="fw-semibold text-dark">${escapeHtml(row.intitule_ar)}</span></td>
                    <td class="text-start"><span class="text-muted small">${escapeHtml(row.intitule_fr)}</span></td>
                    <td class="text-center fw-semibold text-dark">${escapeHtml(row.mois_entree || 'غير محدد')}</td>
                    <td class="text-center text-muted small">${escapeHtml(row.date_debut || 'غير محدد')}</td>
                    <td class="text-center">${badgeHTML}</td>
                    <td class="pe-4 text-center">
                        <div class="d-flex justify-content-center gap-1.5">
                            <button onclick="editSession(${escRow})" class="action-btn action-btn-edit" title="تعديل">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            <button onclick="deleteSession(${row.id})" class="action-btn action-btn-delete" title="حذف">
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
            statsLabel.innerHTML = `عرض السجلات <span class="text-primary fw-bold">${startIndex + 1}</span> إلى <span class="text-primary fw-bold">${endItem}</span> من أصل <span class="text-dark fw-bold">${totalItems}</span> دورة مبرمجة`;
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

        // Calculate visible page range
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

    // Interactive event handlers
    function handleSearch(val) {
        searchQuery = val;
        currentPage = 1;
        refreshConsole();
    }

    function handleStatusFilter(val) {
        statusFilter = val;
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

    // Modal triggering
    function editSession(session) {
        document.getElementById('edit_id').value = session.id;
        document.getElementById('edit_code_session').value = session.code_session || '';
        document.getElementById('edit_intitule_ar').value = session.intitule_ar || '';
        document.getElementById('edit_intitule_fr').value = session.intitule_fr || '';
        document.getElementById('edit_date_debut').value = session.date_debut || '';
        document.getElementById('edit_statut').value = session.statut || 'planifie';

        // Clear validation classes
        const form = document.querySelector('#editSessionModal form');
        form.classList.remove('was-validated');

        // Show modal
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editSessionModal'));
        modal.show();
    }

    function deleteSession(id) {
        if (confirm('هل أنت متأكد من رغبتك في حذف هذه الدورة التكوينية نهائياً؟')) {
            // Create a temporary form to submit the request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/dashboard/sessions/delete/${id}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = 'csrf_token';
            csrfToken.value = '<?= csrf_token() ?? '' ?>';
            form.appendChild(csrfToken);

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Helpers
    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Export Excel Client-Side Function (with bypassed compilation tag concat)
    function exportToExcel() {
        let xmlRows = '';
        sessionsData.forEach(function(item) {
            let statusText = 'مخطط لها';
            if (item.statut === 'en_cours') statusText = 'جارية الآن';
            else if (item.statut === 'terminee') statusText = 'منتهية ومغلقة';
            
            xmlRows += `
                <` + `ss:Row>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.code_session)}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.intitule_ar)}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.intitule_fr)}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.mois_entree || 'غير محدد')}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(item.date_debut)}<` + `/ss:Data><` + `/ss:Cell>
                    <` + `ss:Cell><` + `ss:Data ss:Type="String">${escapeXml(statusText)}<` + `/ss:Data><` + `/ss:Cell>
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
                <` + `ss:Worksheet ss:Name="Sessions">
                    <` + `ss:Table>
                        <` + `ss:Column ss:Width="100"/>
                        <` + `ss:Column ss:Width="180"/>
                        <` + `ss:Column ss:Width="180"/>
                        <` + `ss:Column ss:Width="100"/>
                        <` + `ss:Column ss:Width="120"/>
                        <` + `ss:Column ss:Width="120"/>
                        <` + `ss:Row ss:Height="25">
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">رمز الدورة<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">اسم الدورة بالعربية<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">الاسم بالفرنسية<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">شهر الدخول<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">تاريخ البدء<` + `/ss:Data><` + `/ss:Cell>
                            <` + `ss:Cell ss:StyleID="HeaderStyle"><` + `ss:Data ss:Type="String">الحالة<` + `/ss:Data><` + `/ss:Cell>
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
        link.setAttribute('download', 'sessions_formation.xls');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Export CSV Client-Side Function
    function exportToCSV() {
        let csvContent = "\uFEFF"; // BOM for Arabic UTF-8
        csvContent += "رمز الدورة,اسم الدورة بالعربية,الاسم بالفرنسية,شهر الدخول,تاريخ البدء,الحالة\n";
        
        sessionsData.forEach(function(item) {
            let statusText = 'مخطط لها';
            if (item.statut === 'en_cours') statusText = 'جارية الآن';
            else if (item.statut === 'terminee') statusText = 'منتهية ومغلقة';
            
            let row = [
                `"${(item.code_session || '').replace(/"/g, '""')}"`,
                `"${(item.intitule_ar || '').replace(/"/g, '""')}"`,
                `"${(item.intitule_fr || '').replace(/"/g, '""')}"`,
                `"${(item.mois_entree || '').replace(/"/g, '""')}"`,
                `"${(item.date_debut || '').replace(/"/g, '""')}"`,
                `"${statusText}"`
            ];
            csvContent += row.join(",") + "\n";
        });

        let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', 'sessions_formation.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endsection
