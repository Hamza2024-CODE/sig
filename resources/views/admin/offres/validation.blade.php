@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $pending_offres
 * @var array  $processed_offres
 * @var array  $stats
 * @var string $wilaya_name
 * @var string $role_code
 * @var array  $sessions
 * @var array  $etablissements
 */

$is_admin = ($role_code === 'admin');
$is_central = ($role_code === 'central' || $is_admin);
$is_wilaya = ($role_code === 'dfep');
?>
<div class="animate__animated animate__fadeIn">
    
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-stamp text-primary me-2"></i> المصادقة على عروض التكوين / Validation des Offres
            </h3>
            <p class="text-muted mb-0 small">
                <?php if ($is_wilaya): ?>
                    مديرية التكوين والتعليم المهنيين لولاية <?= htmlspecialchars($wilaya_name) ?> — مصادقة ولائية
                <?php else: ?>
                    الإدارة المركزية (الوزارة) — مصادقة مركزية نهائية
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #e6f7ed; color: #006233;">
            <i class="fa-solid fa-circle-check me-2"></i> <?= session('flash_success');  ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #fdf2f2; color: #9b1c1c;">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= session('flash_error');  ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Dashboard Cards -->
    <div class="row g-4 mb-4">
        <!-- Pending Card -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; color: white !important; border: none !important;">
                <div class="card-body p-4 position-relative overflow-hidden">
                    <i class="fa-solid fa-clock position-absolute" style="font-size: 100px; opacity: 0.15; top: -10px; left: -10px; color: white !important;"></i>
                    <h6 class="fw-bold mb-1" style="color: rgba(255, 255, 255, 0.85) !important;">عروض قيد الدراسة والانتظار</h6>
                    <h1 class="display-4 fw-bold mb-0" style="color: white !important;"><?= $stats['total_pending'] ?></h1>
                    <p class="mb-0 small mt-2" style="color: rgba(255, 255, 255, 0.8) !important;"><i class="fa-solid fa-triangle-exclamation me-1"></i> تتطلب اتخاذ إجراء مصادقة أو رفض</p>
                </div>
            </div>
        </div>

        <!-- Approved Card -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: white !important; border: none !important;">
                <div class="card-body p-4 position-relative overflow-hidden">
                    <i class="fa-solid fa-circle-check position-absolute" style="font-size: 100px; opacity: 0.15; top: -10px; left: -10px; color: white !important;"></i>
                    <h6 class="fw-bold mb-1" style="color: rgba(255, 255, 255, 0.85) !important;">إجمالي العروض المقبولة</h6>
                    <h1 class="display-4 fw-bold mb-0" style="color: white !important;"><?= $stats['total_approved'] ?></h1>
                    <p class="mb-0 small mt-2" style="color: rgba(255, 255, 255, 0.8) !important;"><i class="fa-solid fa-check-double me-1"></i> تمت المصادقة عليها بنجاح</p>
                </div>
            </div>
        </div>

        <!-- Rejected Card -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important; color: white !important; border: none !important;">
                <div class="card-body p-4 position-relative overflow-hidden">
                    <i class="fa-solid fa-circle-xmark position-absolute" style="font-size: 100px; opacity: 0.15; top: -10px; left: -10px; color: white !important;"></i>
                    <h6 class="fw-bold mb-1" style="color: rgba(255, 255, 255, 0.85) !important;">العروض المرفوضة والمسترجعة</h6>
                    <h1 class="display-4 fw-bold mb-0" style="color: white !important;"><?= $stats['total_rejected'] ?></h1>
                    <p class="mb-0 small mt-2" style="color: rgba(255, 255, 255, 0.8) !important;"><i class="fa-solid fa-reply me-1"></i> أرسلت للمؤسسات لتعديلها وإعادة رفعها</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <ul class="nav nav-pills gap-2 mb-3" id="validationTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 fw-bold" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-panel" type="button" role="tab" aria-controls="pending-panel" aria-selected="true">
                        <i class="fa-solid fa-clock me-2"></i> قيد الانتظار (<?= count($pending_offres) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab" aria-controls="history-panel" aria-selected="false">
                        <i class="fa-solid fa-archive me-2"></i> أرشيف المصادقة (<?= count($processed_offres) ?>)
                    </button>
                </li>
            </ul>

            <!-- Advanced Filter Collapse -->
            <div class="no-print border-top border-light pt-3 pb-2 mt-3">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <input type="text" id="search_offre" onkeyup="applyFilters()" class="form-control rounded-pill bg-light border-0 px-4" placeholder="بحث سريع عن تخصص أو مؤسسة...">
                    </div>
                    <div class="col-12 col-md-3">
                        <select id="filter_session" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                            <option value="">كل الدورات التكوينية</option>
                            <?php foreach($sessions as $s): ?>
                                <option value="<?= htmlspecialchars($s['intitule_ar']) ?>"><?= htmlspecialchars($s['intitule_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($is_central): ?>
                    <div class="col-12 col-md-3">
                        <select id="filter_etab" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                            <option value="">كل المؤسسات التكوينية</option>
                            <?php foreach($etablissements as $e): ?>
                                <option value="<?= htmlspecialchars($e['nom_ar']) ?>"><?= htmlspecialchars($e['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12 col-md-3">
                        <select id="filter_mode" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                            <option value="">كل أنماط التكوين</option>
                            <option value="تمهين / Apprentissage">تمهين / Apprentissage</option>
                            <option value="حضوري / Présentiel">حضوري / Présentiel</option>
                            <option value="إقامي / Résidentiel">إقامي / Résidentiel</option>
                            <option value="تكوين متواصل / Continu">تكوين متواصل / Continu</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0 mt-2">
            <div class="tab-content" id="validationTabContent">
                
                <!-- 1. PENDING OFFERS PANEL -->
                <div class="tab-pane fade show active" id="pending-panel" role="tabpanel" aria-labelledby="pending-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="pendingTable" style="min-width:1100px;">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-4">رمز العرض</th>
                                    <th>الدورة التكوينية</th>
                                    <th>الفرع والتخصص المهني</th>
                                    <th>المؤسسة التكوينية</th>
                                    <th class="text-center">نمط التكوين</th>
                                    <th class="text-center">المقاعد</th>
                                    <th class="text-center">التواريخ</th>
                                    <th class="text-center">الاعتمادات</th>
                                    <th class="pe-4 text-end">اتخاذ القرار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pending_offres)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-circle-check text-success fs-2 mb-3 d-block"></i>
                                            لا توجد عروض معلقة بانتظار المصادقة حالياً.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($pending_offres as $o): ?>
                                    <tr style="transition: background 0.2s;">
                                        <td class="ps-4">
                                            <span class="badge bg-light text-primary border border-primary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= $o['code'] ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark font-session"><?= htmlspecialchars($o['session_name']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark mb-1 font-spec"><?= htmlspecialchars($o['spec_ar']) ?></div>
                                            <div class="text-muted small" style="font-size:0.8rem;"><?= htmlspecialchars($o['spec_fr']) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-muted fw-bold font-etab"><i class="fa-solid fa-building-flag me-1"></i> <?= htmlspecialchars($o['centre']) ?></div>
                                        </td>
                                        <td class="text-center font-mode">
                                            <span class="badge bg-light text-dark border rounded-pill px-2.5"><?= htmlspecialchars($o['mode_formation']) ?></span>
                                        </td>
                                        <td class="text-center fw-bold text-primary fs-6"><?= $o['places'] ?></td>
                                        <td>
                                            <div class="text-center small text-muted" style="font-size: 0.78rem;">
                                                <div>البدء: <?= $o['date_debut'] ?></div>
                                                <div>الانتهاء: <?= $o['date_fin'] ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1 align-items-center">
                                                <!-- Pedagogical Staff -->
                                                <span class="badge rounded-pill <?= $o['toggle_encadrement'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="font-size:0.7rem; padding: 0.3em 0.7em;">
                                                    <i class="fa-solid <?= $o['toggle_encadrement'] ? 'fa-check' : 'fa-xmark' ?> me-1"></i> التأطير
                                                </span>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <div class="d-flex justify-content-end gap-1.5">
                                                <!-- Approve button -->
                                                <form action="/dashboard/offres/<?= $is_wilaya ? 'valider-direction' : 'valider-centrale' ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من المصادقة والقبول على هذا العرض؟')">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                                    <input type="hidden" name="action" value="approuver">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold shadow-sm">
                                                        <i class="fa-solid fa-circle-check me-1"></i> مصادقة
                                                    </button>
                                                </form>

                                                <!-- Reject trigger button -->
                                                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold shadow-sm" onclick="showRejectionModal(<?= $o['id'] ?>)">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i> رفض
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. HISTORY / PROCESSED OFFERS PANEL -->
                <div class="tab-pane fade" id="history-panel" role="tabpanel" aria-labelledby="history-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="historyTable" style="min-width:1100px;">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-4">رمز العرض</th>
                                    <th>الدورة التكوينية</th>
                                    <th>الفرع والتخصص المهني</th>
                                    <th>المؤسسة التكوينية</th>
                                    <th class="text-center">نمط التكوين</th>
                                    <th class="text-center">المقاعد</th>
                                    <th class="text-center">الحالة والملاحظة</th>
                                    <th class="pe-4 text-end">التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($processed_offres)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            لا توجد عروض في سجل الأرشيف والعمليات السابقة.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($processed_offres as $o): ?>
                                    <tr style="transition: background 0.2s;">
                                        <td class="ps-4">
                                            <span class="badge bg-light text-primary border border-primary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= $o['code'] ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark font-session"><?= htmlspecialchars($o['session_name']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark mb-1 font-spec"><?= htmlspecialchars($o['spec_ar']) ?></div>
                                            <div class="text-muted small" style="font-size:0.8rem;"><?= htmlspecialchars($o['spec_fr']) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-muted fw-bold font-etab"><i class="fa-solid fa-building-flag me-1"></i> <?= htmlspecialchars($o['centre']) ?></div>
                                        </td>
                                        <td class="text-center font-mode">
                                            <span class="badge bg-light text-dark border rounded-pill px-2.5"><?= htmlspecialchars($o['mode_formation']) ?></span>
                                        </td>
                                        <td class="text-center fw-bold text-muted"><?= $o['places'] ?></td>
                                        <td class="text-center">
                                            <?php
                                            $status = $o['statut_offre'];
                                            if ($status === 'مقبول مركزيا') {
                                                echo '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-3"><i class="fa-solid fa-award me-1"></i> مقبول مركزيا</span>';
                                            } elseif ($status === 'مصادق عليه ولائيا') {
                                                echo '<span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3"><i class="fa-solid fa-circle-check me-1"></i> مصادق عليه ولائيا</span>';
                                            } elseif ($status === 'مرفوض ولائيا') {
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($o['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض ولائيا <i class="fa-solid fa-circle-info ms-1 text-danger"></i></span>';
                                            } elseif ($status === 'مرفوض مركزيا') {
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($o['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض مركزيا <i class="fa-solid fa-circle-info ms-1 text-danger"></i></span>';
                                            } else {
                                                echo '<span class="badge bg-secondary rounded-pill px-3">' . htmlspecialchars($status) . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <span class="text-muted small">تحديث: <?= $o['date_debut'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal: Rejection Reason -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="rejectionModalLabel">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> تحديد سبب رفض عرض التكوين
                </h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectionForm" method="POST" action="/dashboard/offres/<?= $is_wilaya ? 'valider-direction' : 'valider-centrale' ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="id" id="rejection_offre_id">
                <input type="hidden" name="action" value="rejeter">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">سبب الرفض بالتفصيل (Motif de rejet) *</label>
                        <textarea name="motif_rejet" required class="form-control rounded-3 border-light bg-light py-2" rows="4" placeholder="يرجى كتابة سبب الرفض بوضوح ليتمكن المركز من تعديل العرض وإعادة تقديمه..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                    <button type="submit" class="btn btn-danger px-4 py-2" style="border-radius:10px;">تأكيد الرفض وإرجاع العرض</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize Tooltips
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function showRejectionModal(id) {
    document.getElementById('rejection_offre_id').value = id;
    var modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
    modal.show();
}

function applyFilters() {
    var searchQuery = document.getElementById('search_offre').value.toLowerCase();
    var sessionVal = document.getElementById('filter_session').value;
    var etabValSelect = document.getElementById('filter_etab');
    var etabVal = etabValSelect ? etabValSelect.value : '';
    var modeVal = document.getElementById('filter_mode').value;

    // Filter both pending and history tables
    ['pendingTable', 'historyTable'].forEach(function(tableId) {
        var table = document.getElementById(tableId);
        if (!table) return;
        var rows = table.querySelectorAll('tbody tr');

        rows.forEach(function(row) {
            if (row.querySelector('td[colspan]')) return;

            var textContent = row.textContent.toLowerCase();
            
            var rowSession = row.querySelector('.font-session') ? row.querySelector('.font-session').textContent : '';
            var rowEtab = row.querySelector('.font-etab') ? row.querySelector('.font-etab').textContent : '';
            var rowMode = row.querySelector('.font-mode') ? row.querySelector('.font-mode').textContent : '';

            var matchesSearch = textContent.indexOf(searchQuery) > -1;
            var matchesSession = sessionVal === '' || rowSession.indexOf(sessionVal) > -1;
            var matchesEtab = etabVal === '' || rowEtab.indexOf(etabVal) > -1;
            var matchesMode = modeVal === '' || rowMode.indexOf(modeVal) > -1;

            if (matchesSearch && matchesSession && matchesEtab && matchesMode) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}
</script>

@endsection
