@extends('layouts.main')
@section('title', $title ?? 'مراقبة الحضور والغيابات - SGFEP')
@section('content')
<?php
/**
 * @var string $title
 * @var int $tCount
 * @var int $aCount
 * @var int $wCount
 * @var array $absences
 * @var array $sections
 * @var array $specialites
 * @var array $trainees
 * @var int $displayed_new
 * @var int $displayed_continuing
 * @var array $filters
 */

$selectedSection    = $filters['sectionId'] ?? null;
$selectedSpecialite = $filters['specialiteId'] ?? null;
$selectedType       = $filters['traineeType'] ?? 'all';
$selectedSearch     = $filters['search'] ?? '';
$dateAbsence        = date('Y-m-d');
?>

<style>
    .filter-card {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        border-radius: 16px;
    }
    .badge-status-new {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
        font-size: 0.72rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 700;
    }
    .badge-status-continuing {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #fff;
        font-size: 0.72rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 700;
    }
    .attendance-btn-group .btn-check:checked + .btn-outline-success {
        background-color: #10b981 !important;
        color: #fff !important;
    }
    .attendance-btn-group .btn-check:checked + .btn-outline-danger {
        background-color: #ef4444 !important;
        color: #fff !important;
    }
    .attendance-btn-group .btn-check:checked + .btn-outline-warning {
        background-color: #f59e0b !important;
        color: #fff !important;
    }
    .trainee-card-row {
        transition: all 0.2s ease;
    }
    .trainee-card-row:hover {
        background-color: rgba(72, 43, 143, 0.03) !important;
    }
    .btn-pill-filter {
        border-radius: 30px;
        padding: 6px 16px;
        font-weight: 600;
        font-size: 0.85rem;
    }
</style>

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-family:'Cairo',sans-serif;">
                    <i class="fa-solid fa-clipboard-user text-primary me-2"></i> مراقبة الحضور والغيابات البيداغوجية
                </h3>
                <p class="text-muted mb-0 small">متابعة وتسجيل حضور المتربصين الجدد والمستمرين حسب الفرع، الفوج، والتاريخ اليومي</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/dashboard/absences/warnings" class="btn btn-warning text-white rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> سجل الإنذارات والإقصاءات
                </a>
            </div>
        </div>
    </div>

    <!-- Alert feeds -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Overview Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 rounded-4 h-100" style="background: linear-gradient(135deg,#482b8f 0%,#2e1c5b 100%); color:white;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle" style="font-size: 1.4rem;"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <span class="text-white-50 d-block small fw-bold">إجمالي المتربصين</span>
                        <strong class="fs-4 text-warning"><?= number_format($tCount) ?> متربص</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 rounded-4 h-100 bg-white" style="border-right: 4px solid #10b981 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle" style="font-size: 1.4rem;"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <span class="text-muted d-block small fw-bold">المتربصين الجدد (S1)</span>
                        <strong class="fs-4 text-success"><?= number_format($displayed_new) ?> جديد</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 rounded-4 h-100 bg-white" style="border-right: 4px solid #3b82f6 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle" style="font-size: 1.4rem;"><i class="fa-solid fa-user-check"></i></div>
                    <div>
                        <span class="text-muted d-block small fw-bold">المتربصين المستمرين (S2+)</span>
                        <strong class="fs-4 text-primary"><?= number_format($displayed_continuing) ?> مستمر</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 rounded-4 h-100 bg-white" style="border-right: 4px solid #ef4444 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle" style="font-size: 1.4rem;"><i class="fa-solid fa-user-minus"></i></div>
                    <div>
                        <span class="text-muted d-block small fw-bold">إجمالي الغيابات المسجلة</span>
                        <strong class="fs-4 text-danger"><?= number_format($aCount) ?> غياب</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section & Group Filters Bar -->
    <div class="card border-0 shadow-sm filter-card mb-4 p-3">
        <form method="GET" action="/dashboard/absences" id="attendanceFilterForm">
            <div class="row g-3 align-items-center">
                <!-- Branch / Specialty -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark mb-1">
                        <i class="fa-solid fa-graduation-cap text-primary me-1"></i> الفرع / التخصص
                    </label>
                    <select name="specialite_id" class="form-select rounded-pill shadow-sm" onchange="this.form.submit()">
                        <option value="">جميع الفروع والتخصصات</option>
                        <?php foreach ($specialites as $sp): ?>
                            <option value="<?= $sp['id'] ?>" <?= (string)$selectedSpecialite === (string)$sp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sp['specialite_ar']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Section / Group -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark mb-1">
                        <i class="fa-solid fa-layer-group text-primary me-1"></i> الفوج / القسم البيداغوجي
                    </label>
                    <select name="section_id" class="form-select rounded-pill shadow-sm" onchange="this.form.submit()">
                        <option value="">جميع الأفواج والأقسام</option>
                        <?php foreach ($sections as $sec): 
                            $semLabel = ((int)($sec['num_semestre'] ?? 1) === 1) ? 'جديد S1' : 'مستمر S' . $sec['num_semestre'];
                        ?>
                            <option value="<?= $sec['id'] ?>" <?= (string)$selectedSection === (string)$sec['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sec['specialite_ar'] . ' — ' . $sec['section_nom'] . ' (' . $semLabel . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Trainee Type (All / New / Continuing) -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark mb-1">
                        <i class="fa-solid fa-filter text-primary me-1"></i> نوع المتربصين
                    </label>
                    <select name="trainee_type" class="form-select rounded-pill shadow-sm" onchange="this.form.submit()">
                        <option value="all" <?= $selectedType === 'all' ? 'selected' : '' ?>>جميع المتربصين (الجدد والمستمرين)</option>
                        <option value="new" <?= $selectedType === 'new' ? 'selected' : '' ?>>🟢 المتربصين الجدد فقط (السداسي الأول S1)</option>
                        <option value="continuing" <?= $selectedType === 'continuing' ? 'selected' : '' ?>>🔵 المتربصين المستمرين فقط (السداسي S2+)</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark mb-1">
                        <i class="fa-solid fa-magnifying-glass text-primary me-1"></i> البحث بالاسم أو التسجيل
                    </label>
                    <div class="input-group shadow-sm rounded-pill overflow-hidden">
                        <input type="text" name="search" class="form-control border-0 px-3" placeholder="اسم، لقب، رقم التسجيل..." value="<?= htmlspecialchars($selectedSearch) ?>">
                        <button type="submit" class="btn btn-primary px-3"><i class="fa-solid fa-search"></i></button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Attendance Recording Table Section -->
    <form action="/dashboard/absences/store" method="POST" id="attendanceForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-user-check text-primary me-2"></i> جدول تسجيل الحضور والغياب لليوم
                    </h5>
                    <span class="badge bg-light text-dark fw-bold border px-3 py-1.5 rounded-pill">
                        المعروضين: <?= count($trainees) ?> متربص
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Date Picker -->
                    <div class="d-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill border">
                        <i class="fa-solid fa-calendar-day text-primary"></i>
                        <input type="date" name="date_absence" class="form-control form-control-sm border-0 bg-transparent fw-bold" value="<?= $dateAbsence ?>" required>
                    </div>

                    <!-- Hours Select -->
                    <div class="d-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill border">
                        <i class="fa-solid fa-clock text-primary"></i>
                        <select name="heure" class="form-select form-select-sm border-0 bg-transparent fw-bold" style="width: auto;">
                            <option value="08:00:00">حصة الصباح (08:00)</option>
                            <option value="13:00:00">حصة المساء (13:00)</option>
                        </select>
                    </div>

                    <!-- Mark All Present button -->
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold shadow-sm" onclick="markAllPresent()">
                        <i class="fa-solid fa-check-double me-1"></i> تحديد الكل حاضر
                    </button>

                    <!-- Save Attendance Button -->
                    <button type="submit" class="btn btn-sm text-white rounded-pill px-4 fw-bold shadow-sm" style="background-color: var(--color-gov-purple);">
                        <i class="fa-solid fa-floppy-disk me-1"></i> حفظ وتأكيد الحضور
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="traineesAttendanceTable">
                        <thead class="bg-light text-muted small fw-bold">
                            <tr>
                                <th class="ps-4" style="min-width: 220px;">المتربص</th>
                                <th>رقم التسجيل</th>
                                <th>الحالة (نوع التكوين)</th>
                                <th>الفرع / الفوج الدراسي</th>
                                <th class="text-center">الغيابات المتراكمة</th>
                                <th class="pe-4 text-center" style="min-width: 260px;">تسجيل حضور اليوم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($trainees)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash fa-2x mb-3 text-primary opacity-50 d-block"></i>
                                        <div class="fw-bold text-dark fs-6">لا يوجد متربصون مطابقون لمعايير التصفية المختارة</div>
                                        <div class="small text-muted mt-1">يرجى اختيار فرع أو فوج آخر من قائمة التصفية أعلاه.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trainees as $t): 
                                    $isNew = (($t['type_statut'] ?? '') === 'جديد' || (int)($t['num_semestre'] ?? 1) === 1);
                                    $tId   = $t['id'];
                                ?>
                                    <tr class="trainee-card-row">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-circle rounded-circle bg-light text-primary d-flex align-items-center justify-content-center fw-bold border" style="width:38px; height:38px; flex-shrink:0;">
                                                    <?= mb_substr($t['nom_ar'] ?? '', 0, 1) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars(($t['nom_ar'] ?? '') . ' ' . ($t['prenom_ar'] ?? '')) ?></div>
                                                    <div class="text-muted small" style="font-size:0.75rem;"><?= htmlspecialchars(strtoupper(($t['nom_fr'] ?? '') . ' ' . ($t['prenom_fr'] ?? ''))) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="text-dark fw-bold" style="font-size: 0.88rem;"><?= htmlspecialchars($t['matricule'] ?? '—') ?></code>
                                        </td>
                                        <td>
                                            <?php if ($isNew): ?>
                                                <span class="badge-status-new"><i class="fa-solid fa-sparkles me-1"></i>جديد (S1)</span>
                                            <?php else: ?>
                                                <span class="badge-status-continuing"><i class="fa-solid fa-arrows-rotate me-1"></i>مستمر (S<?= $t['num_semestre'] ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($t['specialite_ar'] ?? '') ?>">
                                                <?= htmlspecialchars($t['specialite_ar'] ?? 'عام') ?>
                                            </div>
                                            <div class="text-muted small" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-users me-1"></i> <?= htmlspecialchars($t['section_nom'] ?? 'قسم عام') ?> (فوج <?= htmlspecialchars($t['groupe'] ?? '1') ?>)
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((float)($t['total_absences_heures'] ?? 0) > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger fw-bold px-3 py-1.5 rounded-pill">
                                                    <?= number_format((float)$t['total_absences_heures']) ?> ساعة
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success px-3 py-1.5 rounded-pill">0 غياب</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="btn-group attendance-btn-group" role="group">
                                                <!-- Present -->
                                                <input type="radio" class="btn-check" name="attendance[<?= $tId ?>]" id="pres_<?= $tId ?>" value="present" checked>
                                                <label class="btn btn-sm btn-outline-success px-3 fw-bold" for="pres_<?= $tId ?>">
                                                    <i class="fa-solid fa-circle-check me-1"></i> حاضر
                                                </label>

                                                <!-- Absent -->
                                                <input type="radio" class="btn-check" name="attendance[<?= $tId ?>]" id="abs_<?= $tId ?>" value="absent">
                                                <label class="btn btn-sm btn-outline-danger px-3 fw-bold" for="abs_<?= $tId ?>">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i> غائب
                                                </label>

                                                <!-- Justified -->
                                                <input type="radio" class="btn-check" name="attendance[<?= $tId ?>]" id="just_<?= $tId ?>" value="justified">
                                                <label class="btn btn-sm btn-outline-warning px-3 fw-bold" for="just_<?= $tId ?>">
                                                    <i class="fa-solid fa-file-circle-check me-1"></i> مبرر
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-light p-3 text-start d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="fa-solid fa-info-circle me-1"></i> يتم حفظ وتوثيق الغيابات بالسجل العام للمؤسسة مباشرة بمجرد الضغط على زر الحفظ.
                </span>
                <button type="submit" class="btn text-white px-5 py-2.5 fw-bold shadow-sm rounded-pill" style="background-color: var(--color-gov-purple);">
                    <i class="fa-solid fa-floppy-disk me-1"></i> حفظ وتأكيد جدول الحضور
                </button>
            </div>
        </div>
    </form>

    <!-- History list section -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold m-0 text-dark">
                <i class="fa-solid fa-clock-rotate-left text-muted me-2"></i> آخر الغيابات المسجلة في المؤسسة مؤخراً
            </h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('absTable', 'absences.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('absTable', 'absences.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-right" id="absTable">
                    <thead class="table-light text-muted small fw-bold">
                        <tr>
                            <th class="py-3 px-4">المتربص</th>
                            <th>رقم التسجيل</th>
                            <th>تاريخ الغياب</th>
                            <th>الفرع الدراسي</th>
                            <th>حصة الغياب</th>
                            <th>المدة الكلية المتراكمة</th>
                            <th>الحالة مبرر/غير مبرر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($absences)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-clipboard-check d-block mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                    سجل الحضور نظيف! لا توجد غيابات مسجلة مؤخراً.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($absences as $a): ?>
                                <tr>
                                    <td class="py-3 px-4 fw-bold text-dark"><?= htmlspecialchars(($a['nom_ar'] ?? '') . ' ' . ($a['prenom_ar'] ?? '')) ?></td>
                                    <td><code class="text-secondary fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($a['numero_matricule'] ?? '—') ?></code></td>
                                    <td><span class="fw-semibold text-muted"><?= htmlspecialchars($a['date_absence'] ?? '—') ?></span></td>
                                    <td><span class="badge bg-light text-dark fw-semibold px-3 py-1.5"><?= htmlspecialchars($a['specialite_ar'] ?? 'عام') ?></span></td>
                                    <td><span class="text-muted small">08:00 - 10:00 (<?= (float)($a['duree_heures'] ?? 2) ?> س)</span></td>
                                    <td>
                                        <strong class="text-danger fs-6"><?= (float)($a['total_cumule_injustifie'] ?? 0) ?> ساعة</strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($a['est_justifiee'])): ?>
                                            <span class="badge bg-success-subtle text-success px-3 py-1.5"><i class="fa-solid fa-file-circle-check me-1"></i> مبرر</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-3 py-1.5"><i class="fa-solid fa-file-circle-xmark me-1"></i> غير مبرر</span>
                                        <?php endif; ?>
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

<script>
function markAllPresent() {
    document.querySelectorAll('input[type="radio"][value="present"]').forEach(el => {
        el.checked = true;
    });
}
</script>

@endsection
