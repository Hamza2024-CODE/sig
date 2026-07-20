@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $users
 * @var array $roles
 * @var array $etablissements
 * @var array $wilayas
 */

// Calculate quick stats
$totalUsers = $total_count ?? 0;
$activeUsers = $active_count ?? 0;
$apiKeysCount = $api_keys_count ?? 0;
$suspendedUsers = $suspended_count ?? 0;

// Group establishments by type (Public vs Private)
$publicEtabs = [];
$privateEtabs = [];
foreach ($etablissements as $et) {
    if (($et['type_code'] ?? '') === 'PRIVE') {
        $privateEtabs[] = $et;
    } else {
        $publicEtabs[] = $et;
    }
}
?>
<!-- DataTables CSS for modern practical tables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* Premium Hover Effects for Stat Cards */
    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0,0,0,0.03) !important;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
        border-color: rgba(0, 56, 112, 0.1) !important;
    }
    /* Modern DataTables Styling */
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        padding: 0.375rem 1rem;
        background-color: #f8fafc;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #482b8f;
        outline: none;
        box-shadow: 0 0 0 3px rgba(72, 43, 143, 0.1);
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    table.dataTable.table-hover > tbody > tr:hover > * {
        box-shadow: inset 0 0 0 9999px rgba(72, 43, 143, 0.03);
    }
</style>
<div class="animate__animated animate__fadeIn">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #003870; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-users-gear text-primary me-2" style="color: var(--color-gov-blue) !important;"></i> إدارة المستخدمين وصلاحيات المنصة / Users & Rights
            </h3>
            <p class="text-muted mb-0 small">التحكم في حسابات الموظفين والشركاء، إدارة الصلاحيات الدقيقة، وتوليد مفاتيح API المؤمنة</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/dashboard/users/print" target="_blank" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center">
                <i class="fa-solid fa-print me-2"></i> طباعة قائمة المستخدمين
            </a>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #003870 0%, #0284c7 100%); border: none;" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-user-plus me-2"></i> إضافة مستخدم جديد
            </button>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= session('flash_success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php  ?>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i> <?= session('flash_error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php  ?>
    <?php endif; ?>

    <!-- Bento Grid Stats Cards (Blue-themed) -->
    <div class="row g-4 mb-4 animate__animated animate__fadeInUp">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-2 stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background-color: rgba(0, 56, 112, 0.08); color: #003870;">
                        <i class="fa-solid fa-users fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-bold small mb-1">إجمالي المستخدمين</h6>
                        <h3 class="fw-bold mb-0 text-dark" style="font-family:'Inter';"><?= $totalUsers ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-2 stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background-color: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <i class="fa-solid fa-user-check fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-bold small mb-1">المستخدمين النشطين</h6>
                        <h3 class="fw-bold mb-0 text-success" style="font-family:'Inter';"><?= $activeUsers ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-2 stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background-color: rgba(239, 68, 68, 0.08); color: #ef4444;">
                        <i class="fa-solid fa-user-slash fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-bold small mb-1">حسابات موقوفة</h6>
                        <h3 class="fw-bold mb-0 text-danger" style="font-family:'Inter';"><?= $suspendedUsers ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-2 stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background-color: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                        <i class="fa-solid fa-key fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-bold small mb-1">مفاتيح API النشطة</h6>
                        <h3 class="fw-bold mb-0 text-warning" style="font-family:'Inter';"><?= $apiKeysCount ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ FILTER BAR ══ --}}
    <div class="card border-0 shadow-sm p-3 mb-4 no-print" style="border-radius:16px;background:var(--card-bg);border:1px solid var(--card-border)!important;">
        <form method="GET" action="{{ url('dashboard/users') }}" class="row g-2 align-items-end">
            {{-- بحث نصي --}}
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i> بحث نصي</label>
                <div class="input-group">
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>"
                           class="form-control border-0 rounded-3" placeholder="اسم المستخدم، الاسم الكامل للموظف..."
                           style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                </div>
            </div>

            {{-- تصفية حسب الولاية / مديرية التكوين --}}
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-building-user me-1"></i> الولاية / مديرية التكوين</label>
                <select name="wilaya_id" class="form-select border-0 rounded-3" style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;" onchange="this.form.submit()">
                    <option value="">جميع الولايات والمديريات الولائية</option>
                    <?php foreach ($wilayas as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= (isset($sel_wilaya) && (int)$sel_wilaya === (int)$w['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($w['nom_ar']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            {{-- تصفية حسب المؤسسة --}}
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-school me-1"></i> المؤسسة التكوينية</label>
                <select name="etablissement_id" class="form-select border-0 rounded-3" style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;" onchange="this.form.submit()">
                    <option value="">جميع المؤسسات</option>
                    <?php foreach ($etablissements as $et): ?>
                        <option value="<?= $et['id'] ?>" <?= (isset($sel_etab) && (int)$sel_etab === (int)$et['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($et['nom_ar']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            {{-- تصفية حسب حالة التفعيل --}}
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-toggle-on me-1"></i> حالة الحساب</label>
                <select name="status" class="form-select border-0 rounded-3" style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;" onchange="this.form.submit()">
                    <option value="">جميع الحالات</option>
                    <option value="active" <?= (isset($sel_status) && $sel_status === 'active') ? 'selected' : '' ?>>مفعل / نشط</option>
                    <option value="suspended" <?= (isset($sel_status) && $sel_status === 'suspended') ? 'selected' : '' ?>>موقوف / غير مفعل</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold" style="background: linear-gradient(135deg, #003870 0%, #0284c7 100%); border: none; font-size:0.85rem;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ url('dashboard/users') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold"
                   style="font-size:0.85rem;" title="إعادة تعيين">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-shield-halved text-primary me-2" style="color: var(--color-gov-blue) !important;"></i> سجل المستخدمين وصلاحيات النفاذ الممنوحة</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('usersTable', 'utilisateurs.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('usersTable', 'utilisateurs.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="usersTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المستخدم</th>
                            <th>معلومات الاتصال</th>
                            <th>الرتبة والدور السياسي</th>
                            <th>المؤسسة / ولاية الانتساب لها</th>
                            <th style="min-width: 250px;">مفتاح API الرقمي</th>
                            <th class="text-center">الحالة</th>
                            <th class="pe-4 text-end no-print no-export">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-slash fs-1 mb-3"></i>
                                    <p class="mb-0">لا يوجد مستخدمين مسجلين في النظام حالياً.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($u['avatar'])): ?>
                                                <img src="<?= asset($u['avatar']) ?>" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; background: linear-gradient(135deg, #003870 0%, #0284c7 100%); font-size: 1rem;">
                                                    <?= mb_strtoupper(mb_substr($u['nom_complet'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($u['nom_complet']) ?></div>
                                                <small class="text-muted">اسم الدخول: @<?= htmlspecialchars($u['username']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark small"><i class="fa-regular fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($u['email']) ?></div>
                                        <div class="text-muted small"><i class="fa-regular fa-calendar-plus me-1"></i> انضم: <?= substr($u['created_at'], 0, 10) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-1.5 fw-bold <?= $u['role_code'] === 'admin' ? 'bg-danger-subtle text-danger' : ($u['role_code'] === 'directeur' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary') ?>">
                                            <i class="fa-solid <?= $u['role_code'] === 'admin' ? 'fa-user-shield' : ($u['role_code'] === 'directeur' ? 'fa-hotel' : 'fa-chalkboard-user') ?> me-1"></i>
                                            <?= htmlspecialchars($u['role_ar']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['dep_nom'])): ?>
                                            <div class="text-primary fw-semibold small"><i class="fa-solid fa-building-user text-primary me-1"></i> <?= htmlspecialchars($u['dep_nom']) ?></div>
                                        <?php endif; ?>
                                        <div class="text-dark small"><?= htmlspecialchars($u['etab_nom'] ?? 'الإدارة الوطنية المركزية') ?></div>
                                        <small class="text-muted">الولاية: <?= $u['wilaya_id'] ? 'ولاية ' . $u['wilaya_id'] : 'كل الولايات' ?></small>
                                    </td>
                                    <td>
                                        <?php if ($u['source_table'] === 'utilisateur'): ?>
                                            <?php if (!empty($u['api_key'])): ?>
                                                <div class="input-group input-group-sm rounded-3 border-light" style="max-width: 250px;">
                                                    <input type="text" class="form-control bg-light border-0 small text-truncate" id="apiKey-<?= $u['id'] ?>" value="<?= htmlspecialchars($u['api_key']) ?>" readonly style="font-family:'Outfit'; font-size:0.75rem;">
                                                    <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="copyToClipboard('apiKey-<?= $u['id'] ?>')" title="نسخ المفتاح">
                                                        <i class="fa-regular fa-copy text-primary"></i>
                                                    </button>
                                                    <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="regenerateApiKey(<?= $u['id'] ?>)" title="تجديد المفتاح">
                                                        <i class="fa-solid fa-arrows-rotate text-warning"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold" onclick="regenerateApiKey(<?= $u['id'] ?>)">
                                                    <i class="fa-solid fa-key me-1"></i> توليد مفتاح API
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">— غير متاح / Non disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['est_actif']): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1.5 fw-bold"><i class="fa-solid fa-circle text-success me-1" style="font-size:0.55rem; vertical-align:middle;"></i> نشط</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1.5 fw-bold"><i class="fa-solid fa-circle text-danger me-1" style="font-size:0.55rem; vertical-align:middle;"></i> موقوف</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end no-print no-export">
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" style="width:32px; height:32px;" onclick="viewUserDetails(<?= htmlspecialchars(json_encode($u)) ?>)" title="عرض التفاصيل">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary rounded-circle ms-1" style="width:32px; height:32px;" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="تعديل الحساب وتغيير كلمة المرور وتفعيله">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php if ($u['source_table'] === 'utilisateur'): ?>
                                            <button class="btn btn-sm btn-outline-info rounded-circle ms-1" style="width:32px; height:32px;" onclick="generateResetToken(<?= $u['id'] ?>)" title="توليد رابط استعادة كلمة المرور المنسية">
                                                <i class="fa-solid fa-unlock-keyhole"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($u['source_table'] !== 'utilisateur' || $u['id'] !== (int)session('user')['id']): ?>
                                            <form action="/dashboard/users/delete/<?= $u['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('<?= $u['source_table'] === 'utilisateur' ? 'هل أنت متأكد من حذف هذا الحساب نهائيا من المنصة؟' : 'هل أنت متأكد من سحب صلاحية الدخول وتعطيل هذا الحساب؟' ?>')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                                <input type="hidden" name="source_table" value="<?= $u['source_table'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle ms-1" style="width:32px; height:32px;" title="<?= $u['source_table'] === 'utilisateur' ? 'حذف المستخدم' : 'سحب الصلاحيات' ?>">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- ══ PAGINATION ══ --}}
        <?php if ($total_pages > 1): ?>
        <div class="p-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-top no-print"
             style="border-color:var(--card-border)!important;">
            <small class="text-muted">
                عرض <strong><?= (($page - 1) * $per_page) + 1 ?></strong>–<strong><?= min($total_count, $page * $per_page) ?></strong>
                من <strong><?= number_format($total_count) ?></strong> مستخدم
                (صفحة <?= $page ?> / <?= $total_pages ?>)
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0 gap-1">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>">
                            <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($total_pages, $page + 2);
                    if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=1&search=<?= urlencode($search ?? '') ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=<?= $total_pages ?>&search=<?= urlencode($search ?? '') ?>">
                            <?= $total_pages ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>">
                            <i class="fa-solid fa-chevron-left" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="/dashboard/users/store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-header border-0 pt-4 px-4 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="addUserModalLabel"><i class="fa-solid fa-user-plus text-primary me-2" style="color: var(--color-gov-blue) !important;"></i> إضافة حساب مستخدم جديد وصلاحيات وصول</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom_complet" class="form-label fw-bold text-muted small">الاسم الكامل للموظف (العربية/الفرنسية)</label>
                            <input type="text" class="form-control rounded-3 border-light bg-light" id="nom_complet" name="nom_complet" placeholder="مثال: بلخير محمد" required>
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label fw-bold text-muted small">اسم المستخدم الفريد للولوج (Username)</label>
                            <input type="text" class="form-control rounded-3 border-light bg-light" id="username" name="username" placeholder="مثال: m.belkheir" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold text-muted small">البريد الإلكتروني المهني</label>
                            <input type="email" class="form-control rounded-3 border-light bg-light" id="email" name="email" placeholder="مثال: belkheir@mfep.gov.dz" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-bold text-muted small">كلمة المرور (اتركها فارغة لتوليد تلقائي)</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-3 border-light bg-light" id="password" name="password" placeholder="أو اتركه فارغاً للتوليد التلقائي">
                                <button type="button" class="btn btn-outline-warning fw-bold rounded-end-3" onclick="generateAutoPassword()" title="توليد كلمة مرور قوية تلقائياً">
                                    <i class="fa-solid fa-dice me-1"></i> توليد
                                </button>
                            </div>
                            <small class="text-muted">إذا تركت الحقل فارغاً سيتم توليد كلمة مرور قوية وعرضها بعد الإضافة.</small>
                        </div>
                        <div class="col-md-3">
                            <label for="role_id" class="form-label fw-bold text-muted small">الدور والرتبة الرسمية</label>
                            <select class="form-select rounded-3 border-light bg-light" id="role_id" name="role_id" required onchange="handleRoleChange(this.value, 'add')">
                                <option value="" disabled selected>-- اختر رتبة --</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" data-code="<?= $r['code'] ?>"><?= htmlspecialchars($r['libelle_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="add_dept_container">
                            <label for="department_id" class="form-label fw-bold text-muted small">المديرية / الإدارة التابع لها</label>
                            <select class="form-select rounded-3 border-light bg-light" id="department_id" name="department_id">
                                <option value="">بدون مديرية (أو دور خارجي)</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nom_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="wilaya_id" class="form-label fw-bold text-muted small">الانتساب الجغرافي (ولاية)</label>
                            <?php if (session('user')['role_code'] === 'dfep'): ?>
                                <select class="form-select rounded-3 border-light bg-light" id="wilaya_id" name="wilaya_id" required style="pointer-events: none; background-color: #e9ecef;">
                                    <?php foreach ($wilayas as $w): ?>
                                        <option value="<?= $w['id'] ?>" selected><?= htmlspecialchars($w['nom_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <select class="form-select rounded-3 border-light bg-light" id="wilaya_id" name="wilaya_id">
                                    <option value="">الإدارة المركزية (الكل)</option>
                                    <?php foreach ($wilayas as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['nom_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3" id="add_etab_container">
                            <label for="etablissement_id" class="form-label fw-bold text-muted small">المؤسسة التكوينية</label>
                            <select class="form-select rounded-3 border-light bg-light" id="etablissement_id" name="etablissement_id">
                                <option value="">المديرية الولائية (المصالح الداخلية / الدور الولائي)</option>
                                <?php if (!empty($publicEtabs)): ?>
                                    <optgroup label="المؤسسات العمومية / Établissements Publics">
                                        <?php foreach ($publicEtabs as $et): ?>
                                            <option value="<?= $et['id'] ?>" data-wilaya="<?= $et['wilaya_id'] ?? '' ?>"><?= htmlspecialchars($et['nom_ar']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <?php if (!empty($privateEtabs)): ?>
                                    <optgroup label="المؤسسات الخاصة / Établissements Privés">
                                        <?php foreach ($privateEtabs as $et): ?>
                                            <option value="<?= $et['id'] ?>" data-wilaya="<?= $et['wilaya_id'] ?? '' ?>"><?= htmlspecialchars($et['nom_ar']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Permissions Checkbox Matrix -->
                    <div class="mt-4 p-3 rounded-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i> مصفوفة الصلاحيات الدقيقة وتفويض النفاذ</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="perm_offres" name="permissions[offres]" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="perm_offres">إدارة عروض التكوين والفروع</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="perm_inscriptions" name="permissions[inscriptions]" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="perm_inscriptions">التسجيل والتوجيه والقبول</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="perm_discipline" name="permissions[discipline]" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="perm_discipline">متابعة الحضور والانضباط</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="perm_grades" name="permissions[grades]" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="perm_grades">رصد النقاط والتقييم البيداغوجي</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="perm_documents" name="permissions[documents]" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="perm_documents">استخراج المطبوعات والشهادات</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="perm_repas" name="permissions[repas]" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="perm_repas">حجز وإدارة الوجبات والكانتين</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #003870 0%, #0284c7 100%); border: none;">إضافة وتوليد الحساب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="/dashboard/users/update" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" id="edit_source_table" name="source_table">
                <div class="modal-header border-0 pt-4 px-4 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="editUserModalLabel"><i class="fa-solid fa-user-pen text-primary me-2" style="color: var(--color-gov-blue) !important;"></i> تعديل بيانات الحساب وتفويضات الصلاحيات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_nom_complet" class="form-label fw-bold text-muted small">الاسم الكامل للموظف</label>
                            <input type="text" class="form-control rounded-3 border-light bg-light" id="edit_nom_complet" name="nom_complet" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_username" class="form-label fw-bold text-muted small">اسم المستخدم (Username)</label>
                            <input type="text" class="form-control rounded-3 border-light bg-light" id="edit_username" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label fw-bold text-muted small">البريد الإلكتروني المهني</label>
                            <input type="email" class="form-control rounded-3 border-light bg-light" id="edit_email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_password" class="form-label fw-bold text-muted small">تعيين كلمة مرور جديدة (اتركه فارغاً لعدم التغيير)</label>
                            <input type="password" class="form-control rounded-3 border-light bg-light text-primary" id="edit_password" name="password" placeholder="أدخل كلمة المرور الجديدة لتغييرها فورياً" style="font-weight: bold;">
                        </div>
                        <div class="col-md-3">
                            <label for="edit_role_id" class="form-label fw-bold text-muted small">الدور والرتبة الرسمية</label>
                            <select class="form-select rounded-3 border-light bg-light" id="edit_role_id" name="role_id" required onchange="handleRoleChange(this.value, 'edit')">
                                <option value="" disabled selected>-- اختر رتبة --</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" data-code="<?= $r['code'] ?>"><?= htmlspecialchars($r['libelle_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="edit_dept_container">
                            <label for="edit_department_id" class="form-label fw-bold text-muted small">المديرية / الإدارة التابع لها</label>
                            <select class="form-select rounded-3 border-light bg-light" id="edit_department_id" name="department_id">
                                <option value="">بدون مديرية (أو دور خارجي)</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nom_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_wilaya_id" class="form-label fw-bold text-muted small">الانتساب الجغرافي (ولاية)</label>
                            <?php if (session('user')['role_code'] === 'dfep'): ?>
                                <select class="form-select rounded-3 border-light bg-light" id="edit_wilaya_id" name="wilaya_id" required style="pointer-events: none; background-color: #e9ecef;">
                                    <?php foreach ($wilayas as $w): ?>
                                        <option value="<?= $w['id'] ?>" selected><?= htmlspecialchars($w['nom_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <select class="form-select rounded-3 border-light bg-light" id="edit_wilaya_id" name="wilaya_id">
                                    <option value="">الإدارة المركزية (الكل)</option>
                                    <?php foreach ($wilayas as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['nom_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3" id="edit_etab_container">
                            <label for="edit_etablissement_id" class="form-label fw-bold text-muted small">المؤسسة التكوينية التابع لها</label>
                            <select class="form-select rounded-3 border-light bg-light" id="edit_etablissement_id" name="etablissement_id">
                                <option value="">المديرية الولائية (المصالح الداخلية / الدور الولائي)</option>
                                <?php if (!empty($publicEtabs)): ?>
                                    <optgroup label="المؤسسات العمومية / Établissements Publics">
                                        <?php foreach ($publicEtabs as $et): ?>
                                            <option value="<?= $et['id'] ?>" data-wilaya="<?= $et['wilaya_id'] ?? '' ?>"><?= htmlspecialchars($et['nom_ar']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <?php if (!empty($privateEtabs)): ?>
                                    <optgroup label="المؤسسات الخاصة / Établissements Privés">
                                        <?php foreach ($privateEtabs as $et): ?>
                                            <option value="<?= $et['id'] ?>" data-wilaya="<?= $et['wilaya_id'] ?? '' ?>"><?= htmlspecialchars($et['nom_ar']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="edit_est_actif" name="est_actif" value="1">
                                <label class="form-check-label fw-bold text-dark small" for="edit_est_actif">وضعية تفعيل الحساب: حساب نشط ومفعل وله صلاحية تسجيل الدخول</label>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Checkbox Matrix -->
                    <div class="mt-4 p-3 rounded-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i> مصفوفة الصلاحيات الدقيقة وتفويض النفاذ</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="edit_perm_offres" name="permissions[offres]" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="edit_perm_offres">إدارة عروض التكوين والفروع</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="edit_perm_inscriptions" name="permissions[inscriptions]" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="edit_perm_inscriptions">التسجيل والتوجيه والقبول</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="edit_perm_discipline" name="permissions[discipline]" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="edit_perm_discipline">متابعة الحضور والانضباط</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="edit_perm_grades" name="permissions[grades]" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="edit_perm_grades">رصد النقاط والتقييم البيداغوجي</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="edit_perm_documents" name="permissions[documents]" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="edit_perm_documents">استخراج المطبوعات والشهادات</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="edit_perm_repas" name="permissions[repas]" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="edit_perm_repas">حجز وإدارة الوجبات والكانتين</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #003870 0%, #0284c7 100%); border: none;">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
// Filter etablissements by wilaya inside modals
let addOriginalEtabs = [];
let editOriginalEtabs = [];

document.addEventListener("DOMContentLoaded", function() {
    const addSelect = document.getElementById('etablissement_id');
    const editSelect = document.getElementById('edit_etablissement_id');
    
    if (addSelect) {
        addSelect.querySelectorAll('option, optgroup').forEach(el => {
            if (el.tagName.toLowerCase() === 'option' && el.value === '') return;
            addOriginalEtabs.push({
                element: el.cloneNode(true),
                tagName: el.tagName.toLowerCase(),
                wilaya: el.getAttribute('data-wilaya'),
                parentLabel: el.parentNode.tagName.toLowerCase() === 'optgroup' ? el.parentNode.getAttribute('label') : null
            });
        });
    }
    
    if (editSelect) {
        editSelect.querySelectorAll('option, optgroup').forEach(el => {
            if (el.tagName.toLowerCase() === 'option' && el.value === '') return;
            editOriginalEtabs.push({
                element: el.cloneNode(true),
                tagName: el.tagName.toLowerCase(),
                wilaya: el.getAttribute('data-wilaya'),
                parentLabel: el.parentNode.tagName.toLowerCase() === 'optgroup' ? el.parentNode.getAttribute('label') : null
            });
        });
    }

    const addWilaya = document.getElementById('wilaya_id');
    if (addWilaya) {
        addWilaya.addEventListener('change', function() {
            filterEtabs('add', this.value);
        });
        if (addWilaya.value) {
            filterEtabs('add', addWilaya.value);
        }
    }
    
    const editWilaya = document.getElementById('edit_wilaya_id');
    if (editWilaya) {
        editWilaya.addEventListener('change', function() {
            filterEtabs('edit', this.value);
        });
    }
});

function filterEtabs(mode, wilayaId) {
    const select = document.getElementById(mode === 'add' ? 'etablissement_id' : 'edit_etablissement_id');
    if (!select) return;
    
    const original = mode === 'add' ? addOriginalEtabs : editOriginalEtabs;
    const currentValue = select.value;
    
    select.innerHTML = '';
    
    const emptyOpt = document.createElement('option');
    emptyOpt.value = '';
    emptyOpt.textContent = 'المديرية الولائية (المصالح الداخلية / الدور الولائي)';
    select.appendChild(emptyOpt);
    
    const groups = {};
    
    original.forEach(item => {
        if (item.tagName === 'option') {
            if (!wilayaId || item.wilaya === wilayaId) {
                const opt = item.element.cloneNode(true);
                if (item.parentLabel) {
                    if (!groups[item.parentLabel]) {
                        groups[item.parentLabel] = document.createElement('optgroup');
                        groups[item.parentLabel].setAttribute('label', item.parentLabel);
                    }
                    groups[item.parentLabel].appendChild(opt);
                } else {
                    select.appendChild(opt);
                }
            }
        }
    });
    
    for (const label in groups) {
        select.appendChild(groups[label]);
    }
    
    if (select.querySelector(`option[value="${currentValue}"]`)) {
        select.value = currentValue;
    } else {
        select.value = '';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    <?php if (!empty($users)): ?>
    $('#usersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
        },
        paging: false,
        searching: false,
        info: false,
        responsive: true,
        order: [], // keep original order
        columnDefs: [
            { orderable: false, targets: [4, 6] } // Disable sorting on API Key and Actions columns
        ]
    });
    <?php endif; ?>
});

// Clipboard copier
function copyToClipboard(id) {
    const input = document.getElementById(id);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    
    // Toast notification
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'تم نسخ مفتاح API للحافظة بنجاح!',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
}

// Regenerate API Key via AJAX
function regenerateApiKey(userId) {
    if (!confirm('هل أنت متأكد من توليد مفتاح API جديد؟ سيؤدي ذلك إلى تعطيل المفتاح القديم مباشرة.')) {
        return;
    }
    
    fetch('/sig/dashboard/users/generate-api-key', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + encodeURIComponent(userId) + '&csrf_token=<?= csrf_token() ?? '' ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم بنجاح!',
                text: 'تم توليد مفتاح API جديد بنجاح للمستخدم.',
                confirmButtonColor: '#003870'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'لم نتمكن من توليد المفتاح: ' + data.message,
                confirmButtonColor: '#003870'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'خطأ في الاتصال',
            text: 'حدث خطأ أثناء معالجة الطلب بالشبكة.',
            confirmButtonColor: '#003870'
        });
    });
}

// Handle role selection changes to toggle permissions or restrict selections
function handleRoleChange(roleId, mode) {
    const roleSelect = document.getElementById(mode === 'add' ? 'role_id' : 'edit_role_id');
    if (!roleSelect || roleSelect.selectedIndex === -1) return;
    const selectedOption = roleSelect.options[roleSelect.selectedIndex];
    const roleCode = selectedOption.getAttribute('data-code');

    // Auto check permissions based on selected standard role profiles
    const prefix = mode === 'add' ? 'perm_' : 'edit_perm_';
    const checkboxes = {
        offres: document.getElementById(prefix + 'offres'),
        inscriptions: document.getElementById(prefix + 'inscriptions'),
        discipline: document.getElementById(prefix + 'discipline'),
        grades: document.getElementById(prefix + 'grades'),
        documents: document.getElementById(prefix + 'documents'),
        repas: document.getElementById(prefix + 'repas')
    };

    if (roleCode === 'admin') {
        // Admin gets all by default
        Object.values(checkboxes).forEach(cb => cb.checked = true);
    } else if (roleCode === 'directeur') {
        checkboxes.offres.checked = true;
        checkboxes.inscriptions.checked = true;
        checkboxes.discipline.checked = true;
        checkboxes.grades.checked = false;
        checkboxes.documents.checked = true;
        checkboxes.repas.checked = true;
    } else if (roleCode === 'formateur') {
        checkboxes.offres.checked = false;
        checkboxes.inscriptions.checked = false;
        checkboxes.discipline.checked = true;
        checkboxes.grades.checked = true;
        checkboxes.documents.checked = false;
        checkboxes.repas.checked = false;
    } else if (roleCode === 'stagiaire') {
        checkboxes.offres.checked = false;
        checkboxes.inscriptions.checked = false;
        checkboxes.discipline.checked = false;
        checkboxes.grades.checked = false;
        checkboxes.documents.checked = false;
        checkboxes.repas.checked = true;
    } else if (roleCode && roleCode.startsWith('dir_')) {
        // Specialized internal department roles get basic appropriate permissions
        checkboxes.discipline.checked = roleCode === 'dir_rh';
        checkboxes.offres.checked = ['dir_plan', 'dir_coop', 'dir_edu', 'dir_org', 'dir_trak'].includes(roleCode);
        checkboxes.inscriptions.checked = ['dir_exam', 'dir_edu', 'dir_org'].includes(roleCode);
        checkboxes.grades.checked = ['dir_exam', 'dir_edu', 'dir_org', 'dir_trak'].includes(roleCode);
        checkboxes.documents.checked = ['dir_finance', 'dir_rh', 'dir_coop', 'dir_it', 'dir_exam', 'dir_trak'].includes(roleCode);
        checkboxes.repas.checked = roleCode === 'dir_finance';
    }

    // Select dropdowns and their containers
    const deptSelect = document.getElementById(mode === 'add' ? 'department_id' : 'edit_department_id');
    const etabSelect = document.getElementById(mode === 'add' ? 'etablissement_id' : 'edit_etablissement_id');
    const deptContainer = document.getElementById(mode === 'add' ? 'add_dept_container' : 'edit_dept_container');
    const etabContainer = document.getElementById(mode === 'add' ? 'add_etab_container' : 'edit_etab_container');

    const roleToDeptMap = {
        'dir_finance': 1,
        'dir_rh': 2,
        'dir_plan': 3,
        'dir_coop': 4,
        'dir_it': 5,
        'dir_exam': 6,
        'dir_trak': 7,
        'dir_edu': 8,
        'dir_org': 9
    };

    if (roleCode && roleCode.startsWith('dir_')) {
        // Internal Directorate service roles
        // Auto-select corresponding department
        const targetDeptId = roleToDeptMap[roleCode];
        if (targetDeptId && deptSelect) {
            deptSelect.value = targetDeptId;
        } else if (deptSelect) {
            deptSelect.value = '';
        }
        
        // Auto-set establishment to empty (Directorate itself) and disable it
        if (etabSelect) {
            etabSelect.value = '';
            etabSelect.disabled = true;
            etabSelect.required = false;
        }

        // Show department field, hide establishment field
        if (deptContainer) deptContainer.style.display = 'block';
        if (etabContainer) etabContainer.style.display = 'none';
        
    } else if (['directeur', 'formateur', 'stagiaire'].includes(roleCode)) {
        // Institutional roles
        // Clear and hide department field
        if (deptSelect) deptSelect.value = '';
        if (deptContainer) deptContainer.style.display = 'none';

        // Enable and show establishment field, and make it required
        if (etabSelect) {
            etabSelect.disabled = false;
            etabSelect.required = true;
        }
        if (etabContainer) etabContainer.style.display = 'block';
        
    } else {
        // Standard admin or central roles
        if (etabSelect) {
            etabSelect.disabled = false;
            etabSelect.required = false;
        }
        if (deptContainer) deptContainer.style.display = 'block';
        if (etabContainer) etabContainer.style.display = 'block';
    }
}

// Fill Edit Modal with user data
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_nom_complet').value = user.nom_complet;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role_id').value = user.role_id;
    document.getElementById('edit_est_actif').checked = user.est_actif == 1;
    document.getElementById('edit_source_table').value = user.source_table || 'utilisateur';

    // Disable role/org fields for non-system accounts (they are fixed in master data)
    const isUtilisateur = (user.source_table || 'utilisateur') === 'utilisateur';
    const roleSelect = document.getElementById('edit_role_id');
    const deptSelect = document.getElementById('edit_department_id');
    const etabSelect = document.getElementById('edit_etablissement_id');
    const wilayaSelect = document.getElementById('edit_wilaya_id');
    
    if (roleSelect) roleSelect.disabled = !isUtilisateur;
    if (deptSelect) deptSelect.disabled = !isUtilisateur;
    if (etabSelect) etabSelect.disabled = !isUtilisateur;
    if (wilayaSelect) wilayaSelect.disabled = !isUtilisateur;

    // Reset password field
    document.getElementById('edit_password').value = '';

    // Set field states dynamically using the role change handler
    handleRoleChange(user.role_id, 'edit');

    // Force database values into their respective selects
    document.getElementById('edit_department_id').value = user.department_id || '';
    document.getElementById('edit_wilaya_id').value = user.wilaya_id || '';
    
    // Dynamically filter establishments by selected wilaya first
    filterEtabs('edit', user.wilaya_id || '');
    
    document.getElementById('edit_etablissement_id').value = user.etablissement_id || '';

    // Set permissions checkboxes
    const prefix = 'edit_perm_';
    const permissions = user.permissions ? JSON.parse(user.permissions) : {};
    
    document.getElementById(prefix + 'offres').checked = permissions.offres == 1;
    document.getElementById(prefix + 'inscriptions').checked = permissions.inscriptions == 1;
    document.getElementById(prefix + 'discipline').checked = permissions.discipline == 1;
    document.getElementById(prefix + 'grades').checked = permissions.grades == 1;
    document.getElementById(prefix + 'documents').checked = permissions.documents == 1;
    document.getElementById(prefix + 'repas').checked = permissions.repas == 1;

    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
}

// توليد كلمة مرور قوية تلقائياً في المتصفح
function generateAutoPassword() {
    const upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower   = 'abcdefghjkmnpqrstuvwxyz';
    const digits  = '23456789';
    const special = '@#$!';
    const all     = upper + lower + digits + special;

    let pass = '';
    pass += upper[Math.floor(Math.random() * upper.length)];
    pass += lower[Math.floor(Math.random() * lower.length)];
    pass += digits[Math.floor(Math.random() * digits.length)];
    pass += special[Math.floor(Math.random() * special.length)];
    for (let i = 0; i < 6; i++) {
        pass += all[Math.floor(Math.random() * all.length)];
    }
    // Shuffle
    pass = pass.split('').sort(() => Math.random() - 0.5).join('');

    const pwField = document.getElementById('password');
    pwField.value = pass;
    pwField.type  = 'text';

    // Copy to clipboard
    navigator.clipboard.writeText(pass).catch(() => {});

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'كلمة مرور قوية: <b style="font-family:monospace;color:#1b5e20;">' + pass + '</b>',
        html: 'تم توليدها ونسخها تلقائياً ✅',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
    });
}

// Generate Password Reset Token
function generateResetToken(userId) {
    if (confirm('هل ترغب في توليد رابط استرجاع وإعادة تعيين كلمة المرور لهذا المستخدم؟')) {
        fetch('/sig/dashboard/users/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'user_id=' + userId + '&csrf_token=<?= csrf_token() ?? '' ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const link = window.location.origin + data.reset_link;
                navigator.clipboard.writeText(link).catch(() => {});
                Swal.fire({
                    icon: 'success',
                    title: 'تم التوليد بنجاح',
                    html: `تم توليد رابط استعادة كلمة المرور ونسخه إلى الحافظة.<br><br>الرابط:<br><code style="background:#f1f5f9;padding:4px 8px;border-radius:4px;word-break:break-all;">${link}</code><br><br><small class="text-danger fw-bold">الرجاء إرسال هذا الرابط المعزول للمستخدم لتغيير كلمة مروره.</small>`,
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#0ea5e9'
                });
            } else {
                Swal.fire('خطأ', data.message || 'فشل توليد الرابط', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('خطأ', 'حدث خطأ أثناء الاتصال بالخادم.', 'error');
        });
    }
}

function viewUserDetails(user) {
    const title = user.nom_complet || 'تفاصيل المستخدم';
    
    // Format fields with nice icons and Arabic labels
    const fields = [
        { label: 'الاسم الكامل', value: user.nom_complet, icon: 'fa-user' },
        { label: 'اسم المستخدم', value: '@' + user.username, icon: 'fa-at' },
        { label: 'البريد الإلكتروني', value: user.email || '— غير متوفر', icon: 'fa-envelope' },
        { label: 'الدور والرتبة', value: user.role_ar, icon: 'fa-shield-halved' },
        { label: 'المؤسسة', value: user.etab_nom || 'الإدارة الوطنية المركزية', icon: 'fa-hotel' },
        { label: 'الانتساب الجغرافي', value: user.wilaya_id ? 'ولاية ' + user.wilaya_id : 'كل الولايات', icon: 'fa-map-location-dot' },
        { label: 'تاريخ الإنشاء', value: user.created_at ? user.created_at.substring(0, 10) : '—', icon: 'fa-calendar' },
        { label: 'الحالة', value: user.est_actif == 1 ? 'نشط' : 'موقوف', icon: 'fa-circle-check', badge: user.est_actif == 1 ? 'success' : 'danger' }
    ];
    
    showDetailsModal(title, fields, user.avatar);
}

function showDetailsModal(title, fields, avatarPath) {
    // Set title
    document.getElementById('rowDetailsModalLabel').innerHTML = `<i class="fa-solid fa-circle-info text-primary me-2"></i> ${title}`;
    
    // Set avatar
    const imgEl = document.getElementById('detail_avatar');
    const fallbackEl = document.getElementById('detail_avatar_fallback');
    
    if (avatarPath) {
        imgEl.src = '/' + avatarPath.replace(/^\/+/, '');
        imgEl.style.display = 'inline-block';
        fallbackEl.style.display = 'none';
    } else {
        imgEl.style.display = 'none';
        fallbackEl.style.display = 'flex';
        fallbackEl.innerText = title.trim().substring(0, 1).toUpperCase();
    }
    
    // Set fields
    const container = document.getElementById('detail_fields_container');
    container.innerHTML = '';
    
    fields.forEach(field => {
        const row = document.createElement('div');
        row.className = 'd-flex justify-content-between align-items-center py-2 border-bottom border-light';
        
        let valHtml = '';
        if (field.badge) {
            valHtml = `<span class="badge bg-${field.badge}-subtle text-${field.badge} rounded-pill px-3 py-1 fw-bold">${field.value}</span>`;
        } else {
            valHtml = `<span class="text-dark fw-semibold">${field.value}</span>`;
        }
        
        row.innerHTML = `
            <span class="text-muted small"><i class="fa-solid ${field.icon} me-2 text-primary" style="width: 20px;"></i> ${field.label}</span>
            ${valHtml}
        `;
        container.appendChild(row);
    });
    
    const detailsModal = new bootstrap.Modal(document.getElementById('rowDetailsModal'));
    detailsModal.show();
}
</script>

<!-- Row Details Modal (Bootstrap 5) -->
<div class="modal fade" id="rowDetailsModal" tabindex="-1" aria-labelledby="rowDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="font-family: 'Cairo', sans-serif;">
            <div class="modal-header border-0 pt-4 px-4 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="rowDetailsModalLabel"><i class="fa-solid fa-circle-info text-primary me-2"></i> تفاصيل المستخدم / User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4 text-center">
                <!-- Avatar Preview -->
                <div class="mb-4">
                    <img id="detail_avatar" src="" class="rounded-circle shadow-sm border border-light" style="width: 100px; height: 100px; object-fit: cover; display: none;">
                    <div id="detail_avatar_fallback" class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm mx-auto" style="width: 100px; height: 100px; background: linear-gradient(135deg, #003870 0%, #0284c7 100%); font-size: 2.5rem;">
                        U
                    </div>
                </div>
                
                <!-- Fields Table / Container -->
                <div class="text-start" id="detail_fields_container" style="direction: rtl;">
                    <!-- Dynamically populated fields -->
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@endsection
