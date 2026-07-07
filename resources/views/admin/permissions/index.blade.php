@extends('layouts.main')
@section('title', $title ?? 'إدارة وتخصيص صلاحيات المستخدمين')
@section('content')
<?php
/**
 * @var string $title
 * @var array $users
 */

// Calculate quick stats
$totalUsers = count($users);
$customizedUsers = 0;
$grantedCount = 0;
$deniedCount = 0;

foreach ($users as $u) {
    $hasCustom = false;
    foreach ($u['permissions'] as $mod => $state) {
        if ($state !== 2) {
            $hasCustom = true;
            if ($state === 1) {
                $grantedCount++;
            } elseif ($state === 0) {
                $deniedCount++;
            }
        }
    }
    if ($hasCustom) {
        $customizedUsers++;
    }
}
?>

<!-- DataTables CSS for modern tables -->
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
    /* Modern Segmented Radio Buttons */
    .segmented-control {
        display: flex;
        background-color: var(--bg-dashboard);
        padding: 4px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        gap: 2px;
    }
    .segmented-control input[type="radio"] {
        display: none;
    }
    .segmented-control label {
        flex: 1;
        text-align: center;
        padding: 6px 12px;
        font-size: 0.78rem;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin: 0;
        color: var(--text-secondary);
        font-family: 'Cairo', sans-serif;
    }
    /* Inherit State */
    .segmented-control input[value="2"]:checked + label {
        background-color: rgba(59, 130, 246, 0.12);
        color: #2563eb;
    }
    /* Grant State */
    .segmented-control input[value="1"]:checked + label {
        background-color: rgba(16, 185, 129, 0.12);
        color: #059669;
    }
    /* Deny State */
    .segmented-control input[value="0"]:checked + label {
        background-color: rgba(239, 68, 68, 0.12);
        color: #dc2626;
    }
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        padding: 0.375rem 1rem;
        background-color: #f8fafc;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #003870;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 56, 112, 0.1);
    }
    .badge-inherit {
        background-color: rgba(100, 116, 139, 0.08);
        color: #64748b;
        border: 1px solid rgba(100, 116, 139, 0.15);
    }
    .badge-grant {
        background-color: rgba(16, 185, 129, 0.08);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.15);
    }
    .badge-deny {
        background-color: rgba(239, 68, 68, 0.08);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.15);
    }
</style>

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h1 class="h3 fw-bold mb-1" style="font-family: 'Cairo'; color: var(--text-main);">
                <i class="fa-solid fa-key text-primary me-2"></i>
                إدارة وتخصيص صلاحيات الحسابات
            </h1>
            <p class="text-muted mb-0 small">تخصيص وتكييف صلاحيات الوصول بشكل منفرد ومستقل لكل مستخدم (سماح خاص أو حجب خاص)</p>
        </div>
        <div class="no-print">
            <a href="/dashboard/users" class="btn btn-outline-primary btn-sm ms-2">
                <i class="fa-solid fa-users me-1"></i> إدارة الحسابات
            </a>
            <a href="/dashboard/roles" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-user-shield me-1"></i> الصلاحيات الافتراضية
            </a>
        </div>
    </div>
    <div class="cyber-line-pulse mb-4"></div>

    <!-- Stats Bento Grid -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي الحسابات</span>
                        <h3 class="fw-bold m-0" style="color: var(--primary-color); font-family: 'Inter';"><?= $totalUsers ?></h3>
                    </div>
                    <div class="bg-primary text-white p-3 rounded-3" style="--bs-bg-opacity: .12;">
                        <i class="fa-solid fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">حسابات بصلاحيات مخصصة</span>
                        <h3 class="fw-bold m-0 text-info" style="font-family: 'Inter';"><?= $customizedUsers ?></h3>
                    </div>
                    <div class="bg-info text-white p-3 rounded-3" style="--bs-bg-opacity: .12;">
                        <i class="fa-solid fa-user-gear fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">صلاحيات ممنوحة صراحة</span>
                        <h3 class="fw-bold m-0 text-success" style="font-family: 'Inter';"><?= $grantedCount ?></h3>
                    </div>
                    <div class="bg-success text-white p-3 rounded-3" style="--bs-bg-opacity: .12;">
                        <i class="fa-solid fa-circle-check fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100 bg-white stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">صلاحيات محجوبة صراحة</span>
                        <h3 class="fw-bold m-0 text-danger" style="font-family: 'Inter';"><?= $deniedCount ?></h3>
                    </div>
                    <div class="bg-danger text-white p-3 rounded-3" style="--bs-bg-opacity: .12;">
                        <i class="fa-solid fa-circle-xmark fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('flash_success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            {!! session('flash_success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('flash_error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            {!! session('flash_error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Users Permissions Customization Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-sliders text-primary fs-5"></i>
                    <h5 class="fw-bold m-0" style="font-family: 'Cairo';">قائمة المستخدمين وتخصيص صلاحياتهم الحالية</h5>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="permissionsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th>اسم المستخدم</th>
                            <th>الرتبة والدور</th>
                            <th class="text-center">عروض التكوين</th>
                            <th class="text-center">التسجيل والتوجيه</th>
                            <th class="text-center">الانضباط والغياب</th>
                            <th class="text-center">النقاط والتقييم</th>
                            <th class="text-center">الشهادات والمطابع</th>
                            <th class="text-center">الخدمات والوجبات</th>
                            <th class="text-center" style="width: 120px;">تعديل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $u)
                            <?php $isSystemAdmin = ($u['role_code'] === 'admin'); ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; background: linear-gradient(135deg, #003870 0%, #0284c7 100%); font-size: 0.9rem;">
                                            <?= mb_strtoupper(mb_substr($u['nom_complet'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0.5" style="font-size:0.88rem;"><?= htmlspecialchars($u['nom_complet']) ?></div>
                                            <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-2.5 py-1.5 fw-bold <?= $u['role_code'] === 'admin' ? 'bg-danger-subtle text-danger' : ($u['role_code'] === 'directeur' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary') ?>" style="font-size: 0.72rem;">
                                        <?= htmlspecialchars($u['role_ar']) ?>
                                    </span>
                                </td>
                                @foreach (['offres', 'inscriptions', 'discipline', 'grades', 'documents', 'repas'] as $mod)
                                    <?php $state = $u['permissions'][$mod] ?? 2; ?>
                                    <td class="text-center">
                                        @if ($isSystemAdmin)
                                            <span class="badge rounded-pill px-2 py-1 badge-grant text-success" style="font-size: 0.65rem; font-weight:800;">
                                                <i class="fa-solid fa-crown me-0.5"></i> كاملة
                                            </span>
                                        @else
                                            @if ($state === 1)
                                                <span class="badge rounded-pill px-2 py-1 badge-grant" style="font-size: 0.65rem;" title="ممنوحة صراحة"><i class="fa-solid fa-check me-0.5"></i> سماح</span>
                                            @elseif ($state === 0)
                                                <span class="badge rounded-pill px-2 py-1 badge-deny" style="font-size: 0.65rem;" title="محجوبة صراحة"><i class="fa-solid fa-xmark me-0.5"></i> منع</span>
                                            @else
                                                <span class="badge rounded-pill px-2 py-1 badge-inherit" style="font-size: 0.65rem;" title="مورثة من الدور"><i class="fa-solid fa-rotate me-0.5"></i> افتراضي</span>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center">
                                    @if ($isSystemAdmin)
                                        <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold disabled" style="font-size:0.75rem;" disabled>
                                            <i class="fa-solid fa-ban me-1"></i> مقفل
                                        </button>
                                    @else
                                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" style="font-size:0.75rem;" onclick="openCustomizeModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                                            <i class="fa-solid fa-user-gear me-1"></i> تخصيص
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Customize User Permissions Modal -->
<div class="modal fade" id="customizePermModal" tabindex="-1" aria-labelledby="customizePermModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); border-radius: 20px;">
            <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" id="customizePermModalLabel" style="font-family: 'Cairo'; color: var(--text-main);">
                        <i class="fa-solid fa-sliders text-primary me-2"></i>
                        تخصيص وتكييف صلاحيات حساب المستخدم
                    </h5>
                    <p class="text-muted mb-0 small">تعديل سلوك الصلاحيات الفردية لهذا المستخدم. سيتم إبطال التخزين المؤقت فور الحفظ.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="/dashboard/permissions/update" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="user_id" id="custom-user-id">
                
                <div class="modal-body px-4 py-3">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: var(--bg-dashboard) !important; border: 1px solid var(--border-color);">
                                <small class="text-muted fw-bold d-block mb-1">اسم الموظف / الدور الحالي</small>
                                <span class="fw-bold text-dark d-block" id="custom-user-name" style="font-size:0.92rem;"></span>
                                <span class="badge bg-primary-subtle text-primary fw-bold mt-1" id="custom-user-role" style="font-size: 0.7rem;"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: var(--bg-dashboard) !important; border: 1px solid var(--border-color);">
                                <small class="text-muted fw-bold d-block mb-1">اسم المستخدم للنظام</small>
                                <code class="px-2 py-1 rounded bg-white text-primary small d-inline-block mt-1.5" id="custom-user-username" style="font-size: 0.85rem; border:1px solid var(--border-color);"></code>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3" style="font-family: 'Cairo'; color: var(--text-main);"><i class="fa-solid fa-gears text-secondary me-1"></i> تحديد خيارات الصلاحية لكل قسم:</h6>
                    
                    <div class="row g-3">
                        <?php
                        $modulesConfig = [
                            'offres'       => ['title' => 'عروض التكوين المهني', 'desc' => 'إدارة التخصصات، الشعب، ومخططات الدورة التكوينية', 'icon' => 'fa-briefcase'],
                            'inscriptions' => ['title' => 'التسجيل والتوجيه', 'desc' => 'تسيير ملفات المترشحين، وتوجيههم، وقبولهم وإدماجهم', 'icon' => 'fa-user-plus'],
                            'discipline'   => ['title' => 'الانضباط والغيابات', 'desc' => 'رصد الحضور والمواظبة والمجالس التأديبية للمتربصين', 'icon' => 'fa-user-check'],
                            'grades'       => ['title' => 'التقييمات ونقاط الاختبارات', 'desc' => 'إدخال نقاط الفروض والاختبارات والمداولات البيداغوجية', 'icon' => 'fa-star-half-stroke'],
                            'documents'    => ['title' => 'الشهادات والمطبوعات الرسمية', 'desc' => 'استخراج كشوف النقاط، الشهادات الرسمية ومستندات الانتساب', 'icon' => 'fa-print'],
                            'repas'        => ['title' => 'الخدمات المادية وحجز الوجبات', 'desc' => 'تسيير خدمات المطعم المدرسي والوجبات اليومية للطلاب', 'icon' => 'fa-utensils'],
                        ];
                        ?>
                        @foreach ($modulesConfig as $modKey => $cfg)
                            <div class="col-md-6">
                                <div class="p-3 rounded d-flex flex-column justify-content-between h-100" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color); border-radius:14px !important;">
                                    <div class="d-flex align-items-start gap-2.5 mb-3">
                                        <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .12;">
                                            <i class="fa-solid {{ $cfg['icon'] }} text-primary"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block" style="font-size:0.83rem;">{{ $cfg['title'] }}</span>
                                            <small class="text-muted d-block" style="font-size:0.7rem; line-height: 1.3;">{{ $cfg['desc'] }}</small>
                                        </div>
                                    </div>
                                    <div class="segmented-control">
                                        <input type="radio" name="permissions[{{ $modKey }}]" id="perm-{{ $modKey }}-inherit" value="2" checked>
                                        <label for="perm-{{ $modKey }}-inherit"><i class="fa-solid fa-rotate me-0.5"></i> افتراضي</label>

                                        <input type="radio" name="permissions[{{ $modKey }}]" id="perm-{{ $modKey }}-grant" value="1">
                                        <label for="perm-{{ $modKey }}-grant"><i class="fa-solid fa-check me-0.5"></i> سماح</label>

                                        <input type="radio" name="permissions[{{ $modKey }}]" id="perm-{{ $modKey }}-deny" value="0">
                                        <label for="perm-{{ $modKey }}-deny"><i class="fa-solid fa-xmark me-0.5"></i> منع</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light btn-sm fw-bold px-4 rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4 rounded-pill"><i class="fa-solid fa-floppy-disk me-1"></i> حفظ وتكييف الصلاحيات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS & JQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#permissionsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json'
            },
            pageLength: 10,
            responsive: true,
            order: [],
            columnDefs: [
                { orderable: false, targets: [2, 3, 4, 5, 6, 7, 8] }
            ]
        });
    });

    // Populate and open customization modal
    function openCustomizeModal(user) {
        document.getElementById('custom-user-id').value = user.id;
        document.getElementById('custom-user-name').innerText = user.nom_complet;
        document.getElementById('custom-user-role').innerText = user.role_ar;
        document.getElementById('custom-user-username').innerText = '@' + user.username;

        // Reset all segmented control states
        const modules = ['offres', 'inscriptions', 'discipline', 'grades', 'documents', 'repas'];
        modules.forEach(function(mod) {
            // Default state to Inherit (2)
            const state = (user.permissions && user.permissions[mod] !== undefined) ? user.permissions[mod] : 2;
            
            document.getElementById('perm-' + mod + '-inherit').checked = false;
            document.getElementById('perm-' + mod + '-grant').checked = false;
            document.getElementById('perm-' + mod + '-deny').checked = false;

            if (state === 1) {
                document.getElementById('perm-' + mod + '-grant').checked = true;
            } else if (state === 0) {
                document.getElementById('perm-' + mod + '-deny').checked = true;
            } else {
                document.getElementById('perm-' + mod + '-inherit').checked = true;
            }
        });

        // Show Modal
        const permModal = new bootstrap.Modal(document.getElementById('customizePermModal'));
        permModal.show();
    }
</script>
@endsection
