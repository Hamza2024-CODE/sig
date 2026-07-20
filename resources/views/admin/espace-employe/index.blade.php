@extends('layouts.main')
@section('title', 'فضاء الموظفين — SGFEP')
@section('content')
<?php
/**
 * @var array $employees
 * @var array $filter_wilayas
 * @var array $filter_etablissements
 * @var int $page
 * @var int $totalPages
 * @var int $totalCount
 * @var string $api_key
 * @var array $scope
 * @var array $selected_filters
 */
$isSuperAdmin = in_array($scope['role'] ?? '', ['admin', 'superadmin', 'central']);
$isDfep = $scope['role'] === 'dfep';
$selectedSearch = $selected_filters['search'] ?? '';
$selectedWilaya = $selected_filters['wilaya'] ?? '';
$selectedType = $selected_filters['type'] ?? '';
$selectedEtab = $selected_filters['etab'] ?? '';

// Marital status mapping helper
$sitFamilleMap = [
    1 => 'أعزب / عزباء',
    2 => 'متزوج / متزوجة',
    3 => 'مطلق / مطلقة',
    4 => 'أرمل / أرملة',
];
?>
<style>
.glass-panel {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
}
.employee-row {
    cursor: pointer;
    transition: all 0.2s ease;
}
.employee-row:hover {
    background-color: rgba(72, 43, 143, 0.03) !important;
}
.employee-row.active-row {
    background-color: rgba(72, 43, 143, 0.06) !important;
    border-right: 4px solid #482b8f;
}
.digital-id-card {
    background: linear-gradient(135deg, #482b8f 0%, #1e1b4b 100%);
    border-radius: 20px;
    padding: 24px;
    color: white;
    position: relative;
    overflow: hidden;
    min-height: 290px;
    box-shadow: 0 10px 25px rgba(72, 43, 143, 0.2);
}
.digital-id-card::before {
    content: '';
    position: absolute;
    top: -40px;
    right: -40px;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 50%;
}
.card-chip {
    position: absolute;
    top: 20px;
    left: 20px;
    width: 40px;
    height: 30px;
    background: linear-gradient(135deg, #ffd700, #c8a400);
    border-radius: 5px;
}
.card-seal {
    position: absolute;
    bottom: 15px;
    left: 20px;
    font-size: 2.5rem;
    opacity: 0.12;
    color: white;
}
.avatar-preview-wrapper {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 3px solid #e2e8f0;
    overflow: hidden;
    position: relative;
    background-color: #f8fafc;
}
.avatar-preview-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.avatar-upload-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #482b8f;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 2px solid white;
    transition: all 0.2s ease;
}
.avatar-upload-btn:hover {
    background-color: #643edb;
    transform: scale(1.1);
}
</style>

<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom border-light no-print">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-briefcase text-primary me-2"></i> فضاء موظفي قطاع التكوين والتعليم المهنيين
            </h3>
            <p class="text-muted mb-0 small mt-1">منصة تسيير البيانات الشخصية والمهنية للموظفين ومؤطري قطاع التكوين المهني</p>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold" style="font-size:0.8rem;">
                <i class="fa-solid fa-shield-halved text-success me-1"></i> إجمالي المسجلين: <?= number_format($totalCount) ?> موظف
            </span>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print glass-panel">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-secondary" style="font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-filter text-primary me-2"></i> معايير البحث والتصفية المتقدمة
            </h6>
            <form method="GET" action="/dashboard/espace-employe" id="filterForm">
                <div class="row g-3">
                    <!-- Search Input -->
                    <div class="<?= ($isSuperAdmin || $isDfep) ? 'col-md-3' : 'col-md-12' ?>">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="filter_search" class="form-control bg-light border-0 rounded-end-3" placeholder="ابحث باسم الموظف أو NIN أو التخصص..." value="<?= htmlspecialchars($selectedSearch) ?>">
                        </div>
                    </div>
                    <!-- Wilaya Filter (Only for SuperAdmin) -->
                    <?php if ($isSuperAdmin): ?>
                    <div class="col-md-3">
                        <select name="filter_wilaya" id="filter_wilaya" class="form-select bg-light border-0 rounded-3">
                            <option value="">كل الولايات</option>
                            <?php foreach ($filter_wilayas as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= $selectedWilaya == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['code'] . ' - ' . $w['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($isSuperAdmin || $isDfep): ?>
                        <!-- Establishment Type Filter -->
                        <div class="col-md-3">
                            <select name="filter_type" id="filter_type" class="form-select bg-light border-0 rounded-3">
                                <option value="">كل أنواع المؤسسات</option>
                                <option value="directorate" <?= $selectedType === 'directorate' ? 'selected' : '' ?>>المديريات الولائية</option>
                                <option value="centre" <?= $selectedType === 'centre' ? 'selected' : '' ?>>مراكز التكوين المهني</option>
                                <option value="institute" <?= $selectedType === 'institute' ? 'selected' : '' ?>>المعاهد الوطنية المتخصصة</option>
                                <option value="private" <?= $selectedType === 'private' ? 'selected' : '' ?>>المؤسسات الخاصة المعتمدة</option>
                            </select>
                        </div>
                        <!-- Establishment Filter -->
                        <div class="col-md-3">
                            <select name="filter_etab" id="filter_etab" class="form-select bg-light border-0 rounded-3">
                                <option value="">كل المؤسسات التكوينية</option>
                                <?php foreach ($filter_etablissements as $et): ?>
                                    <option value="<?= $et['id'] ?>" data-wilaya="<?= $et['wilaya_id'] ?? '' ?>" <?= $selectedEtab == $et['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($et['nom_ar']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                    <div class="small text-muted">
                        تم العثور على: <span class="fw-bold text-primary"><?= number_format($totalCount) ?></span> موظفين في هذا النطاق
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">
                            <i class="fa-solid fa-search me-1"></i> تصفية
                        </button>
                        <a href="/dashboard/espace-employe" class="btn btn-outline-danger rounded-pill px-4 btn-sm fw-bold shadow-sm">
                            <i class="fa-solid fa-eraser me-1"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content Workspace -->
    <div class="row g-4">
        <!-- Employees List (Left Column) -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-users text-primary me-2"></i> قائمة الموظفين
                    </h5>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="employeesTable">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-4">الموظف</th>
                                    <th>المؤسسة التكوينية</th>
                                    <th>الوظيفة والتخصص</th>
                                    <th class="pe-4 text-end">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-users-slash mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                            <div class="fw-bold text-dark">لا توجد سجلات مطابقة</div>
                                            <div class="small mt-1">يرجى تعديل معايير البحث أو اختيار ولاية/مؤسسة مختلفة.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $emp): 
                                        $empType = 'other';
                                        $etabNom = $emp['etab_nom'] ?? '';
                                        if (mb_stripos($etabNom, 'مديرية') !== false || (int)($emp['IDNature_etsF'] ?? 0) === 5) {
                                            $empType = 'directorate';
                                        } elseif (mb_stripos($etabNom, 'معهد') !== false) {
                                            $empType = 'institute';
                                        } elseif (mb_stripos($etabNom, 'مركز') !== false) {
                                            $empType = 'centre';
                                        } elseif (mb_stripos($etabNom, 'خاصة') !== false || (int)($emp['IDNature_etsF'] ?? 0) === 12) {
                                            $empType = 'private';
                                        }
                                    ?>
                                        <tr class="employee-row" data-emp-id="<?= $emp['IDEncadrement'] ?>" onclick="selectEmployee(<?= $emp['IDEncadrement'] ?>, this)">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar-wrapper shadow-sm rounded-circle border border-2 border-white overflow-hidden d-flex justify-content-center align-items-center" style="width: 45px; height: 45px; flex-shrink: 0; background-color: #f1f5f9;">
                                                        <?php if (!empty($emp['photo'])): ?>
                                                            <img src="<?= asset(ltrim($emp['photo'], '/')) ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <?php if (in_array(strtolower($emp['Civ'] ?? ''), ['f', '2'])): ?>
                                                                <i class="fa-solid fa-venus fa-lg" style="color: #ec4899;"></i>
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-mars fa-lg" style="color: #6366f1;"></i>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark emp-name-ar"><?= htmlspecialchars(($emp['Nom'] ?? '') . ' ' . ($emp['Prenom'] ?? '')) ?></div>
                                                        <div class="text-muted small" style="font-size:0.75rem;"><?= htmlspecialchars(strtoupper(($emp['NomFr'] ?? '') . ' ' . ($emp['PrenomFr'] ?? ''))) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 200px; font-size:0.82rem;" title="<?= htmlspecialchars($emp['TachesPrincipale'] ?? '') ?>">
                                                    <?= htmlspecialchars($emp['TachesPrincipale'] ?? 'لا توجد مهام محددة') ?>
                                                </div>
                                                <div class="text-primary small fw-bold mt-1">
                                                    <i class="fa-solid fa-graduation-cap me-1"></i> <?= htmlspecialchars($emp['Specialite'] ?? 'بدون تخصص') ?>
                                                </div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick="event.stopPropagation(); openEditModal(<?= $emp['IDEncadrement'] ?>)">
                                                    <i class="fa-solid fa-user-pen me-1"></i> التعديل
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination (Standard server-side with filter carry) -->
            <?php if ($totalPages > 1): 
                $queryString = http_build_query(request()->except('page'));
            ?>
                <nav aria-label="Page navigation" class="mt-4 no-print">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link rounded-pill px-3 mx-1" href="?page=<?= $page - 1 ?>&<?= $queryString ?>"><i class="fa-solid fa-chevron-right small"></i> السابق</a></li>
                        <?php endif; ?>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($p = $startPage; $p <= $endPage; $p++): 
                        ?>
                            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                                <a class="page-link rounded-circle mx-1 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" href="?page=<?= $p ?>&<?= $queryString ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link rounded-pill px-3 mx-1" href="?page=<?= $page + 1 ?>&<?= $queryString ?>">التالي <i class="fa-solid fa-chevron-left small"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>

        <!-- Sidebar Detailed Profile (Right Column) -->
        <div class="col-lg-4">
            <!-- Digital ID Card -->
            <div class="digital-id-card mb-4" id="employeeDigitalCard">
                <div class="card-chip"></div>
                <div class="card-seal"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="text-center py-2" style="position:relative;z-index:3;" id="sidebar-card-loading">
                    <p class="text-white-50 py-5 my-3"><i class="fa-solid fa-user-tie fa-2xl mb-3 d-block"></i>اختر موظفاً من الجدول لمعاينة هويته الرقمية</p>
                </div>
                
                <!-- Inside layout populated via AJAX -->
                <div id="sidebar-card-content" class="d-none" style="position:relative;z-index:3;">
                    <div class="text-center py-2">
                        <div class="position-relative d-inline-block">
                            <img src="" id="sidebar-avatar" class="rounded-circle border border-3 border-light shadow-sm" style="width:80px;height:80px; object-fit: cover;" alt="Avatar">
                            <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-2"></span>
                        </div>
                        <h6 class="fw-bold mt-3 mb-1" id="sidebar-name" style="font-family:'Cairo';color:white;">-</h6>
                        <span class="badge bg-light rounded-pill px-3 py-1 fw-bold text-dark" id="sidebar-task-badge" style="font-family:'Cairo';font-size:0.75rem;">-</span>
                        <div class="text-white-50 small mt-2" id="sidebar-etab-name" style="font-size:0.75rem;">-</div>
                    </div>
                    <hr class="my-3 opacity-25">
                    <div class="row g-2 text-right small" style="color:white;">
                        <div class="col-6">
                            <span class="d-block" style="font-size:0.7rem;opacity:0.7;">معرف الموظف (ID):</span>
                            <strong id="sidebar-id" style="font-family:'Outfit';">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="d-block" style="font-size:0.7rem;opacity:0.7;">تاريخ التوظيف الأول:</span>
                            <strong id="sidebar-hire-date">-</strong>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="d-block" style="font-size:0.7rem;opacity:0.7;">البريد الإلكتروني المهني:</span>
                            <strong class="text-truncate d-block" id="sidebar-email" style="font-family:'Outfit';font-size:0.75rem;">-</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Balance & Family quick stats -->
            <div class="card border-0 p-4 bg-white shadow-sm rounded-4 mb-4 d-none" id="sidebar-stats-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0 text-dark" style="font-family:'Cairo';font-size:0.85rem;">
                        <i class="fa-solid fa-plane-arrival text-success me-1"></i> رصيد العطل السنوية
                    </h6>
                    <span class="badge bg-success-subtle text-success fw-bold" id="sidebar-leave-days" style="font-size:0.75rem;">30 يوم عطلة</span>
                </div>
                <div class="progress mb-2" style="height:8px; border-radius: 4px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="sidebar-leave-bar" role="progressbar" style="width: 50%"></div>
                </div>
                
                <hr class="my-3 border-light">
                
                <!-- Quick stats -->
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <span class="text-muted small d-block">عدد الأولاد</span>
                        <h4 class="fw-bold text-dark mt-1 mb-0" id="sidebar-children-count">0</h4>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">منهم متمدرسين</span>
                        <h4 class="fw-bold text-primary mt-1 mb-0" id="sidebar-school-children">0</h4>
                    </div>
                </div>
            </div>

            <!-- Workspace dynamic widgets -->
            <div class="card border-0 p-4 bg-white shadow-sm rounded-4 d-none" id="sidebar-widget-card">
                <ul class="nav nav-tabs nav-justified border-0 p-1 bg-light rounded-4 mb-3 gap-1 shadow-sm" id="sidebarTabs" role="tablist" style="font-family:'Cairo';font-size:0.78rem;font-weight:700;">
                    <li class="nav-item">
                        <button class="nav-link active rounded-3 py-2" id="sidebar-career-tab" data-bs-toggle="tab" data-bs-target="#side-tab-pane-career" type="button">التأطير</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-3 py-2" id="sidebar-docs-tab" data-bs-toggle="tab" data-bs-target="#side-tab-pane-docs" type="button">مطبوعات مؤمنة</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-3 py-2" id="sidebar-path-tab" data-bs-toggle="tab" data-bs-target="#side-tab-pane-path" type="button">المسار</button>
                    </li>
                </ul>
                <div class="tab-content" id="sidebarTabContent" style="font-size: 0.85rem;">
                    <!-- Tab 1 -->
                    <div class="tab-pane fade show active" id="side-tab-pane-career" role="tabpanel">
                        <div id="sidebar-widget-body">-</div>
                    </div>
                    <!-- Tab 2 -->
                    <div class="tab-pane fade" id="side-tab-pane-docs" role="tabpanel">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item bg-transparent px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size:0.8rem;">كشف الراتب الشهري المؤمن</div>
                                        <span class="text-muted small" style="font-size:0.68rem;">الرمز: <strong class="text-primary" id="sidebar-fp-code">-</strong></span>
                                    </div>
                                    <button class="btn btn-xs btn-outline-success rounded-pill px-2 py-0" style="font-size: 0.72rem;" onclick="printSecuredDoc()"><i class="fa-solid fa-print"></i></button>
                                </div>
                            </div>
                            <div class="list-group-item bg-transparent px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size:0.8rem;">شهادة عمل رسمية</div>
                                        <span class="text-muted small" style="font-size:0.68rem;">الرمز: <strong class="text-primary" id="sidebar-at-code">-</strong></span>
                                    </div>
                                    <button class="btn btn-xs btn-outline-success rounded-pill px-2 py-0" style="font-size: 0.72rem;" onclick="printSecuredDoc()"><i class="fa-solid fa-print"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Tab 3 -->
                    <div class="tab-pane fade" id="side-tab-pane-path" role="tabpanel">
                        <div class="position-relative text-right" id="sidebar-timeline-body" style="border-right:2px solid #e2e8f0;margin-right:0.5rem;padding-right:1rem;">
                            -
                        </div>
                    </div>
                </div>
                
                <!-- Action Edit Profile -->
                <div class="mt-4 pt-3 border-top border-light">
                    <button class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm" id="sidebar-edit-btn" onclick="openSelectedEditModal()">
                        <i class="fa-solid fa-user-pen me-2"></i> تعديل ملف الموظف بالكامل
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Edit Modal (Comprehensive Multi-Tab Form) -->
<div class="modal fade" id="employeeEditModal" tabindex="-1" style="font-family:'Cairo', sans-serif;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0 justify-content-between">
                <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-user-pen text-primary me-2"></i>تعديل بيانات الملف الشخصي للموظف</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="employeeEditForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit-emp-id" name="id">
                    
                    <!-- Avatar Upload Section -->
                    <div class="d-flex align-items-center justify-content-center flex-column mb-4 pb-2 border-bottom border-light">
                        <div class="avatar-preview-wrapper shadow-sm">
                            <img src="" id="edit-avatar-preview" alt="Preview">
                            <label for="edit-photo-input" class="avatar-upload-btn">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input type="file" id="edit-photo-input" name="photo" accept="image/*" class="d-none" onchange="previewSelectedPhoto(this)">
                        </div>
                        <span class="text-muted small mt-2">انقر على الكاميرا لتحديث الصورة الشخصية (JPG/PNG)</span>
                    </div>

                    <!-- Modal Tabs -->
                    <ul class="nav nav-tabs nav-justified border-0 p-1 bg-light rounded-4 mb-4 gap-1 shadow-sm" id="editModalTabs" role="tablist" style="font-size:0.85rem;font-weight:700;">
                        <li class="nav-item">
                            <button class="nav-link active rounded-3 py-2" id="edit-personal-tab" data-bs-toggle="tab" data-bs-target="#edit-tab-personal" type="button">البيانات الشخصية</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-3 py-2" id="edit-family-tab" data-bs-toggle="tab" data-bs-target="#edit-tab-family" type="button">الحالة العائلية والمدنية</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-3 py-2" id="edit-career-tab" data-bs-toggle="tab" data-bs-target="#edit-tab-career" type="button">البيانات الوظيفية</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="editModalTabContent">
                        <!-- Tab 1: Personal Details -->
                        <div class="tab-pane fade show active" id="edit-tab-personal" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">الاسم (بالعربية)</label>
                                    <input type="text" name="prenom" id="edit-prenom" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">اللقب (بالعربية)</label>
                                    <input type="text" name="nom" id="edit-nom" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">الاسم (بالفرنسية)</label>
                                    <input type="text" name="prenom_fr" id="edit-prenom_fr" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">اللقب (بالفرنسية)</label>
                                    <input type="text" name="nom_fr" id="edit-nom_fr" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">تاريخ الميلاد</label>
                                    <input type="text" name="date_nais" id="edit-date_nais" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold" placeholder="YYYY/MM/DD" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">مكان الميلاد</label>
                                    <input type="text" name="lieu_nais" id="edit-lieu_nais" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">الجنس (الحالة المدنية)</label>
                                    <select name="civ" id="edit-civ" class="form-select bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                        <option value="1">ذكر</option>
                                        <option value="2">أنثى</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">رقم الهاتف</label>
                                    <input type="text" name="tel" id="edit-tel" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">البريد الإلكتروني</label>
                                    <input type="email" name="email" id="edit-email" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold" placeholder="name@domain.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">العنوان الشخصي</label>
                                    <input type="text" name="adres" id="edit-adres" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Family Status -->
                        <div class="tab-pane fade" id="edit-tab-family" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">الوضعية العائلية</label>
                                    <select name="sitfamille" id="edit-sitfamille" class="form-select bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                        <option value="1">أعزب / عزباء</option>
                                        <option value="2">متزوج / متزوجة</option>
                                        <option value="3">مطلق / مطلقة</option>
                                        <option value="4">أرمل / أرملة</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">إجمالي عدد الأولاد</label>
                                    <input type="number" name="nbr_enfants" id="edit-nbr_enfants" min="0" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">منهم الأولاد المتمدرسين</label>
                                    <input type="number" name="nbr_enfants_scol" id="edit-nbr_enfants_scol" min="0" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Professional details -->
                        <div class="tab-pane fade" id="edit-tab-career" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <!-- NIN field is disabled and readonly to block editing as requested -->
                                    <label class="form-label text-muted small fw-bold">الرقم الوطني للتعريف الإلكتروني (NIN) <i class="fa-solid fa-lock text-danger ms-1"></i></label>
                                    <input type="text" id="edit-nin" class="form-control border border-2 border-danger-subtle bg-white py-2 px-3 fw-bold text-danger" readonly disabled style="cursor: not-allowed; opacity: 0.8;">
                                    <span class="text-danger small mt-1 d-block" style="font-size:0.7rem;">حقل مغلق - غير قابل للتعديل نهائياً.</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">رقم الضمان الاجتماعي (NSS)</label>
                                    <input type="text" name="nss" id="edit-nss" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">تاريخ التوظيف</label>
                                    <input type="date" name="daterecr" id="edit-daterecr" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">التخصص المهني</label>
                                    <input type="text" name="specialite" id="edit-specialite" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">المهام الأساسية / الرتبة</label>
                                    <input type="text" name="taches_principale" id="edit-taches_principale" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">الدرجة المهنية (Echelon)</label>
                                    <input type="number" name="echelon" id="edit-echelon" min="1" max="15" class="form-control bg-light border-0 rounded-3 py-2 px-3 fw-bold">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top border-light">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Select first employee row automatically on load if available
document.addEventListener("DOMContentLoaded", function() {
    const firstRow = document.querySelector("#employeesTable tbody tr.employee-row");
    if (firstRow) {
        firstRow.click();
    }
});

// Select Employee & Update Sidebar UI dynamically
let selectedEmployeeId = null;

function selectEmployee(empId, element) {
    selectedEmployeeId = empId;
    
    // Highlight selected row
    document.querySelectorAll("#employeesTable tbody tr.employee-row").forEach(r => r.classList.remove("active-row"));
    if (element) {
        element.classList.add("active-row");
    }

    // Show loading
    document.getElementById("sidebar-card-loading").classList.remove("d-none");
    document.getElementById("sidebar-card-content").classList.add("d-none");
    document.getElementById("sidebar-stats-card").classList.add("d-none");
    document.getElementById("sidebar-widget-card").classList.add("d-none");

    // Fetch details
    fetch(`/dashboard/espace-employe/get/${empId}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const emp = data.employee;

            // Update card UI
            document.getElementById("sidebar-name").textContent = `${emp.Nom} ${emp.Prenom}`;
            document.getElementById("sidebar-task-badge").textContent = emp.TachesPrincipale || 'لا توجد مهام محددة';
            document.getElementById("sidebar-etab-name").textContent = `${emp.etab_nom} (${emp.wilaya_nom || 'غير محددة'})`;
            document.getElementById("sidebar-id").textContent = `#${emp.IDEncadrement}`;
            document.getElementById("sidebar-hire-date").textContent = emp.Daterecr || 'غير متوفر';
            document.getElementById("sidebar-email").textContent = emp.Email || 'لا يوجد بريد مهني';
            
            // Set Avatar
            const avatarImg = document.getElementById("sidebar-avatar");
            if (emp.photo) {
                avatarImg.src = emp.photo;
            } else {
                const isFemale = ["f", "2"].includes(String(emp.Civ).toLowerCase());
                avatarImg.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.Nom + ' ' + emp.Prenom)}&background=${isFemale ? 'ec4899' : '6366f1'}&color=fff&size=100`;
            }

            // Update stats
            document.getElementById("sidebar-children-count").textContent = emp.nbrEnf || 0;
            document.getElementById("sidebar-school-children").textContent = emp.nbrenfscol || 0;
            
            // Calculate a random leave bar percent based on ID
            const leavePercent = (empId % 20) + 40;
            document.getElementById("sidebar-leave-bar").style.width = `${leavePercent}%`;

            // Document codes
            document.getElementById("sidebar-fp-code").textContent = emp.paystub_code;
            document.getElementById("sidebar-at-code").textContent = emp.work_certificate_code;

            // Widget & Timeline
            document.getElementById("sidebar-widget-body").innerHTML = emp.widget_html;
            document.getElementById("sidebar-timeline-body").innerHTML = emp.timeline_html;

            // Reveal UI panels
            document.getElementById("sidebar-card-loading").classList.add("d-none");
            document.getElementById("sidebar-card-content").classList.remove("d-none");
            document.getElementById("sidebar-stats-card").classList.remove("d-none");
            document.getElementById("sidebar-widget-card").classList.remove("d-none");
        }
    })
    .catch(() => {
        document.getElementById("sidebar-card-loading").innerHTML = '<p class="text-danger py-5"><i class="fa-solid fa-triangle-exclamation d-block fa-2xl mb-3"></i>حدث خطأ أثناء تحميل تفاصيل الموظف.</p>';
    });
}

// Open Edit Modal for sidebar selected employee
function openSelectedEditModal() {
    if (selectedEmployeeId) {
        openEditModal(selectedEmployeeId);
    }
}

// Fetch and open edit modal
function openEditModal(empId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'جاري تحميل الملف...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    fetch(`/dashboard/espace-employe/get/${empId}`)
    .then(r => r.json())
    .then(data => {
        if (typeof Swal !== 'undefined') { Swal.close(); }
        if (data.success) {
            const emp = data.employee;

            // Populate form fields
            document.getElementById("edit-emp-id").value = emp.IDEncadrement;
            document.getElementById("edit-nom").value = emp.Nom || '';
            document.getElementById("edit-prenom").value = emp.Prenom || '';
            document.getElementById("edit-nom_fr").value = emp.NomFr || '';
            document.getElementById("edit-prenom_fr").value = emp.PrenomFr || '';
            document.getElementById("edit-date_nais").value = emp.DateNais || '';
            document.getElementById("edit-lieu_nais").value = emp.LieuNais || '';
            document.getElementById("edit-civ").value = emp.Civ || '1';
            document.getElementById("edit-tel").value = emp.Tel || '';
            document.getElementById("edit-email").value = emp.Email || '';
            document.getElementById("edit-adres").value = emp.Adres || '';
            
            // Family details
            document.getElementById("edit-sitfamille").value = emp.IDSitfamille || '1';
            document.getElementById("edit-nbr_enfants").value = emp.nbrEnf || 0;
            document.getElementById("edit-nbr_enfants_scol").value = emp.nbrenfscol || 0;

            // Career details
            document.getElementById("edit-nin").value = emp.nin || '0';
            document.getElementById("edit-nss").value = emp.nss || '';
            document.getElementById("edit-daterecr").value = emp.Daterecr || '';
            document.getElementById("edit-specialite").value = emp.Specialite || '';
            document.getElementById("edit-taches_principale").value = emp.TachesPrincipale || '';
            document.getElementById("edit-echelon").value = emp.Echlo || 1;

            // Avatar Preview
            const preview = document.getElementById("edit-avatar-preview");
            if (emp.photo) {
                preview.src = emp.photo;
            } else {
                const isFemale = ["f", "2"].includes(String(emp.Civ).toLowerCase());
                preview.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.Nom + ' ' + emp.Prenom)}&background=${isFemale ? 'ec4899' : '6366f1'}&color=fff&size=100`;
            }

            // Reset file input
            document.getElementById("edit-photo-input").value = "";

            // Reset to first tab
            const triggerEl = document.querySelector('#editModalTabs button[data-bs-target="#edit-tab-personal"]');
            if (triggerEl) {
                bootstrap.Tab.getInstance(triggerEl)?.show() || new bootstrap.Tab(triggerEl).show();
            }

            // Show Modal
            new bootstrap.Modal(document.getElementById("employeeEditModal")).show();
        }
    })
    .catch(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'خطأ!', text: 'حدث خطأ أثناء تحميل ملف الموظف' });
        }
    });
}

// Preview uploaded photo instantly
function previewSelectedPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById("edit-avatar-preview").src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Form Submission handling
document.getElementById("employeeEditForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const empId = document.getElementById("edit-emp-id").value;
    const formData = new FormData(this);

    // Add CSRF token
    formData.append('_token', '<?= csrf_token() ?>');

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'جاري حفظ البيانات...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    fetch(`/dashboard/espace-employe/update/${empId}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Hide Modal
            bootstrap.Modal.getInstance(document.getElementById("employeeEditModal")).hide();
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'تم الحفظ!',
                    text: 'تم تحديث بيانات الملف التعريفي للموظف وصورته بنجاح.',
                    confirmButtonColor: '#482b8f'
                }).then(() => {
                    // Reload the page to update the table lists
                    location.reload();
                });
            } else {
                location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'فشل التحديث', text: data.message });
            }
        }
    })
    .catch(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'خطأ!', text: 'حدث خطأ أثناء إرسال البيانات.' });
        }
    });
});

function printSecuredDoc() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: 'طباعة الوثيقة المؤمنة',
            text: 'يتم الآن توليد ملف PDF المؤمن للموظف مع ختم QR كود...',
            confirmButtonColor: '#482b8f'
        });
    }
}
</script>
@endsection
