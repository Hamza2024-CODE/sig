@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array $roles
 * @var string $title
 */
?>
<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <!-- Header Page Title & Premium Cyber Sweep Line -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h1 class="h3 fw-bold mb-1" style="font-family: 'Cairo'; color: var(--text-main);">
                <i class="fa-solid fa-shield-halved text-primary me-2"></i>
                إدارة الأدوار وصلاحيات المنظومة الافتراضية
            </h1>
            <p class="text-muted mb-0 small">توزيع الصلاحيات والصلاحيات الافتراضية للمستويات المركزية والولائية والمحلية للمنصة</p>
        </div>
        <div class="no-print">
            <a href="/dashboard/users" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-users me-1"></i> إدارة الحسابات
            </a>
        </div>
    </div>
    <div class="cyber-line-pulse mb-4"></div>

    <!-- Stats Section -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 p-3 h-100" style="background: linear-gradient(135deg, var(--primary-glow) 0%, transparent 100%);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي الأدوار بالمنصة</span>
                        <h3 class="fw-bold m-0" style="color: var(--primary-color);"><?= count($roles) ?> دَوْرًا</h3>
                    </div>
                    <div class="bg-primary text-white p-3 rounded-3" style="--bs-bg-opacity: .15;">
                        <i class="fa-solid fa-user-shield fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 p-3 h-100" style="background: linear-gradient(135deg, var(--secondary-glow) 0%, transparent 100%);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">أدوار الإدارة والرقابة</span>
                        <?php
                        $adminRoles = 0;
                        foreach ($roles as $r) {
                            if (in_array($r['code'], ['admin', 'inspecteur_general', 'secretaire_general', 'chef_cabinet', 'ministre'])) {
                                $adminRoles++;
                            }
                        }
                        ?>
                        <h3 class="fw-bold m-0 text-success"><?= $adminRoles ?> أدوار</h3>
                    </div>
                    <div class="bg-success text-white p-3 rounded-3" style="--bs-bg-opacity: .15;">
                        <i class="fa-solid fa-users-gear fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">المصالح الداخلية للمديرية</span>
                        <?php
                        $dirRoles = 0;
                        foreach ($roles as $r) {
                            if (strpos($r['code'], 'dir_') === 0) {
                                $dirRoles++;
                            }
                        }
                        ?>
                        <h3 class="fw-bold m-0 text-primary"><?= $dirRoles ?> أقسام</h3>
                    </div>
                    <div class="bg-primary text-white p-3 rounded-3" style="--bs-bg-opacity: .1;">
                        <i class="fa-solid fa-sitemap fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">المؤسسات العمومية والخاصة</span>
                        <h3 class="fw-bold m-0" style="color: var(--primary-color);">3 مؤسسات</h3>
                    </div>
                    <div class="bg-primary text-white p-3 rounded-3" style="--bs-bg-opacity: .12;">
                        <i class="fa-solid fa-building-columns fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert and Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            <?= session('flash_success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php  ?>
    <?php endif; ?>

    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <?= session('flash_error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php  ?>
    <?php endif; ?>

    <!-- Roles Grid and List Tabs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-list-check text-primary"></i>
                <h5 class="fw-bold m-0" style="font-family: 'Cairo';">قائمة الأدوار والصلاحيات النشطة</h5>
            </div>
            <div class="nav nav-pills" id="roles-tab" role="tablist">
                <button class="nav-link active btn-sm ms-2 fw-bold" id="grid-tab-btn" data-bs-toggle="tab" data-bs-target="#roles-grid" type="button" role="tab">
                    <i class="fa-solid fa-table-cells-large me-1"></i> بانتو جريد / Bento Grid
                </button>
                <button class="nav-link btn-sm fw-bold" id="table-tab-btn" data-bs-toggle="tab" data-bs-target="#roles-table" type="button" role="tab">
                    <i class="fa-solid fa-list me-1"></i> جدول مفصل / Detailed List
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="roles-tab-content">
                <!-- Tab 1: Bento Grid View -->
                <div class="tab-pane fade show active" id="roles-grid" role="tabpanel">
                    <div class="row g-3">
                        <?php foreach ($roles as $role): ?>
                            <?php
                            $isCoreAdmin = ($role['code'] === 'admin');
                            $roleColor = 'var(--primary-color)';
                            $roleBg = 'var(--primary-glow)';
                            
                            if ($isCoreAdmin) {
                                $roleColor = 'var(--secondary-color)';
                                $roleBg = 'var(--secondary-glow)';
                            } elseif (strpos($role['code'], 'dir_') === 0) {
                                $roleColor = '#7209b7';
                                $roleBg = 'rgba(114, 9, 183, 0.08)';
                            } elseif (in_array($role['code'], ['dfep', 'directeur'])) {
                                $roleColor = '#4361ee';
                                $roleBg = 'rgba(67, 97, 238, 0.08)';
                            }
                            ?>
                            <div class="col-xl-4 col-md-6">
                                <div class="card h-100 border-0 p-3 position-relative" style="background-color: var(--bg-dashboard) !important; border: 1px solid var(--border-color) !important; border-radius: 16px !important;">
                                    <div class="d-flex align-items-start justify-content-between mb-3">
                                        <div>
                                            <span class="badge mb-2 fw-bold" style="background-color: <?= $roleBg ?>; color: <?= $roleColor ?>; font-size: 0.72rem; border: 1px solid rgba(131, 7, 228, 0.1);">
                                                <?= htmlspecialchars($role['code']) ?>
                                            </span>
                                            <h5 class="fw-bold mb-1" style="font-family: 'Cairo'; color: var(--text-main);"><?= htmlspecialchars($role['libelle_ar']) ?></h5>
                                            <small class="text-muted d-block" style="font-size:0.75rem;"><?= htmlspecialchars($role['libelle_fr'] ?? '') ?></small>
                                        </div>
                                        <?php if ($isCoreAdmin): ?>
                                            <span class="badge bg-success text-white px-2 py-1 small" style="font-size: 0.68rem; font-weight:700;">
                                                <i class="fa-solid fa-crown me-1"></i> وصول مطلق
                                            </span>
                                        <?php else: ?>
                                            <button class="btn btn-outline-primary btn-sm rounded-circle p-1" style="width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center;" onclick="openEditModal(<?= htmlspecialchars(json_encode($role)) ?>)" title="تعديل الصلاحيات الافتراضية">
                                                <i class="fa-solid fa-pen-to-square" style="font-size:0.85rem;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Selected permissions breakdown -->
                                    <div class="d-flex flex-wrap gap-1 mt-auto">
                                        <?php
                                        $perms = $role['permissions'];
                                        $labels = [
                                            'offres' => ['title' => 'عروض التكوين', 'icon' => 'fa-briefcase'],
                                            'inscriptions' => ['title' => 'التسجيلات والتوجيه', 'icon' => 'fa-user-plus'],
                                            'discipline' => ['title' => 'الانضباط والغياب', 'icon' => 'fa-user-check'],
                                            'grades' => ['title' => 'التقييمات والعلامات', 'icon' => 'fa-star-half-stroke'],
                                            'documents' => ['title' => 'الشهادات والمطبوعات', 'icon' => 'fa-print'],
                                            'repas' => ['title' => 'حجز الوجبات', 'icon' => 'fa-utensils']
                                        ];
                                        
                                        $hasAny = false;
                                        foreach ($labels as $key => $lbl) {
                                            $hasIt = $isCoreAdmin || (isset($perms[$key]) && $perms[$key] == '1');
                                            if ($hasIt) {
                                                $hasAny = true;
                                                echo '<span class="badge bg-primary text-primary small d-flex align-items-center gap-1" style="font-size: 0.65rem; font-weight:700;">';
                                                echo '<i class="fa-solid ' . $lbl['icon'] . '"></i> ' . $lbl['title'];
                                                echo '</span>';
                                            }
                                        }
                                        if (!$hasAny) {
                                            echo '<span class="badge text-muted small" style="font-size: 0.68rem; background: rgba(0,0,0,0.03); border:1px dashed var(--border-color);">لا يملك أي صلاحية افتراضية</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab 2: Detailed Table View -->
                <div class="tab-pane fade" id="roles-table" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">الرقم</th>
                                    <th>اسم الدور بالعربية</th>
                                    <th>Code</th>
                                    <th class="text-center" style="width: 100px;">عروض التكوين</th>
                                    <th class="text-center" style="width: 100px;">التسجيلات</th>
                                    <th class="text-center" style="width: 100px;">الانضباط</th>
                                    <th class="text-center" style="width: 100px;">التقييمات</th>
                                    <th class="text-center" style="width: 100px;">الشهادات</th>
                                    <th class="text-center" style="width: 100px;">حجز الوجبات</th>
                                    <th class="text-center" style="width: 100px;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $index => $role): ?>
                                    <?php $isCoreAdmin = ($role['code'] === 'admin'); ?>
                                    <tr>
                                        <td class="text-muted fw-bold"><?= $index + 1 ?></td>
                                        <td>
                                            <span class="fw-bold text-dark d-block mb-1"><?= htmlspecialchars($role['libelle_ar']) ?></span>
                                            <span class="text-muted small"><?= htmlspecialchars($role['libelle_fr'] ?? '') ?></span>
                                        </td>
                                        <td>
                                            <code class="px-2 py-1 rounded bg-light text-primary small" style="font-size:0.75rem;">
                                                <?= htmlspecialchars($role['code']) ?>
                                            </code>
                                        </td>
                                        <?php
                                        $perms = $role['permissions'];
                                        $keys = ['offres', 'inscriptions', 'discipline', 'grades', 'documents', 'repas'];
                                        foreach ($keys as $k) {
                                            $hasIt = $isCoreAdmin || (isset($perms[$k]) && $perms[$k] == '1');
                                            echo '<td class="text-center">';
                                            if ($hasIt) {
                                                echo '<span class="text-success" style="font-size:1.1rem;"><i class="fa-solid fa-circle-check"></i></span>';
                                            } else {
                                                echo '<span class="text-muted" style="opacity:0.3; font-size:1rem;"><i class="fa-solid fa-circle-xmark"></i></span>';
                                            }
                                            echo '</td>';
                                        }
                                        ?>
                                        <td class="text-center">
                                            <?php if ($isCoreAdmin): ?>
                                                <span class="badge bg-success text-white small" style="font-size:0.68rem; font-weight:700;">
                                                    <i class="fa-solid fa-crown"></i>
                                                </span>
                                            <?php else: ?>
                                                <button class="btn btn-outline-primary btn-sm rounded-circle p-1" style="width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center;" onclick="openEditModal(<?= htmlspecialchars(json_encode($role)) ?>)">
                                                    <i class="fa-solid fa-pen-to-square" style="font-size:0.8rem;"></i>
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
    </div>
</div>

<!-- Premium Edit Role Permissions Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); border-radius: 20px;">
            <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" id="editRoleModalLabel" style="font-family: 'Cairo'; color: var(--text-main);">
                        <i class="fa-solid fa-pen-to-square text-primary me-2"></i>
                        تعديل الصلاحيات الافتراضية للدور
                    </h5>
                    <p class="text-muted mb-0 small">سيتم تطبيق التغييرات فورياً على جميع حسابات المستخدمين التابعين لهذا الدور والذين لا يملكون تعديلاً خاصاً.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="/dashboard/roles/update" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="id" id="edit-role-id">
                
                <div class="modal-body px-4 py-3">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light" style="background-color: var(--bg-dashboard) !important; border: 1px solid var(--border-color);">
                                <small class="text-muted fw-bold d-block mb-1">اسم الدور بالكامل / Libellé</small>
                                <span class="fw-bold text-dark d-block" id="edit-role-libelle-ar"></span>
                                <span class="text-muted small" id="edit-role-libelle-fr"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light" style="background-color: var(--bg-dashboard) !important; border: 1px solid var(--border-color);">
                                <small class="text-muted fw-bold d-block mb-1">الرمز التعريفي / Identifier Code</small>
                                <code class="px-2 py-1 rounded bg-white text-primary small d-inline-block mt-1" id="edit-role-code" style="font-size: 0.85rem; border:1px solid var(--border-color);"></code>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3" style="font-family: 'Cairo'; color: var(--text-main);">تخصيص الصلاحيات الافتراضية:</h6>
                    
                    <div class="row g-3">
                        <!-- Card 1: Offres -->
                        <div class="col-md-6">
                            <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .15;">
                                        <i class="fa-solid fa-briefcase text-primary"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size:0.85rem;">عروض التكوين المهني</span>
                                        <small class="text-muted" style="font-size:0.7rem;">تسيير الشعب والتخصصات وعروض الدورة</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-0 m-0">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="permissions[offres]" value="1" id="perm-offres" style="width: 44px; height: 22px; cursor:pointer;">
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Inscriptions -->
                        <div class="col-md-6">
                            <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .15;">
                                        <i class="fa-solid fa-user-plus text-primary"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size:0.85rem;">التسجيل والتوجيه</span>
                                        <small class="text-muted" style="font-size:0.7rem;">إدارة ملفات المترشحين وإدماج الخريجين</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-0 m-0">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="permissions[inscriptions]" value="1" id="perm-inscriptions" style="width: 44px; height: 22px; cursor:pointer;">
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Discipline -->
                        <div class="col-md-6">
                            <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .15;">
                                        <i class="fa-solid fa-user-check text-primary"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size:0.85rem;">الانضباط والغياب</span>
                                        <small class="text-muted" style="font-size:0.7rem;">رصد الغيابات والمواظبة والمجالس التأديبية</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-0 m-0">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="permissions[discipline]" value="1" id="perm-discipline" style="width: 44px; height: 22px; cursor:pointer;">
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Grades -->
                        <div class="col-md-6">
                            <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .15;">
                                        <i class="fa-solid fa-star-half-stroke text-primary"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size:0.85rem;">التقييمات والعلامات</span>
                                        <small class="text-muted" style="font-size:0.7rem;">إدخال العلامات، كشوف النقاط والمداولات</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-0 m-0">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="permissions[grades]" value="1" id="perm-grades" style="width: 44px; height: 22px; cursor:pointer;">
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: Documents -->
                        <div class="col-md-6">
                            <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .15;">
                                        <i class="fa-solid fa-print text-primary"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size:0.85rem;">الشهادات والمطبوعات</span>
                                        <small class="text-muted" style="font-size:0.7rem;">استخراج الشهادات الرسمية والمطبوعات الموحدة</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-0 m-0">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="permissions[documents]" value="1" id="perm-documents" style="width: 44px; height: 22px; cursor:pointer;">
                                </div>
                            </div>
                        </div>

                        <!-- Card 6: Repas -->
                        <div class="col-md-6">
                            <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .15;">
                                        <i class="fa-solid fa-utensils text-primary"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size:0.85rem;">الخدمات وحجز الوجبات</span>
                                        <small class="text-muted" style="font-size:0.7rem;">تسيير وجبات الإطعام والخدمات المادية</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-0 m-0">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="permissions[repas]" value="1" id="perm-repas" style="width: 44px; height: 22px; cursor:pointer;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light btn-sm fw-bold px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit role modal handler
    function openEditModal(role) {
        document.getElementById('edit-role-id').value = role.id;
        document.getElementById('edit-role-libelle-ar').innerText = role.libelle_ar;
        document.getElementById('edit-role-libelle-fr').innerText = role.libelle_fr;
        document.getElementById('edit-role-code').innerText = role.code;

        // Reset all switch checkboxes
        document.getElementById('perm-offres').checked = false;
        document.getElementById('perm-inscriptions').checked = false;
        document.getElementById('perm-discipline').checked = false;
        document.getElementById('perm-grades').checked = false;
        document.getElementById('perm-documents').checked = false;
        document.getElementById('perm-repas').checked = false;

        // Populate values
        var perms = role.permissions;
        if (perms) {
            if (perms.offres == '1') document.getElementById('perm-offres').checked = true;
            if (perms.inscriptions == '1') document.getElementById('perm-inscriptions').checked = true;
            if (perms.discipline == '1') document.getElementById('perm-discipline').checked = true;
            if (perms.grades == '1') document.getElementById('perm-grades').checked = true;
            if (perms.documents == '1') document.getElementById('perm-documents').checked = true;
            if (perms.repas == '1') document.getElementById('perm-repas').checked = true;
        }

        // Show modal
        var editModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
        editModal.show();
    }
</script>

@endsection
