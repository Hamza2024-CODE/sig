@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $schedules
 * @var array $offres
 * @var array $formateurs
 * @var array $matieres
 */
$user = session('user');
$role_code = $user['role_code'];
$canManage = in_array($role_code, ['admin', 'dfep', 'directeur']);
?>

<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-calendar-days text-primary me-2"></i> استعمال الزمن / Emploi du Temps
            </h3>
            <p class="text-muted mb-0 small">إدارة وتتبع جدول الحصص الأسبوعية للأساتذة والمتربصين</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="exportTableToExcel('scheduleTable', 'emploi_temps.xls')" class="btn btn-success rounded-pill px-3 fw-bold shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
            </button>
            <button onclick="exportTableToCSV('scheduleTable', 'emploi_temps.csv')" class="btn btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                <i class="fa-solid fa-file-csv me-1"></i> تصدير CSV
            </button>
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                <i class="fa-solid fa-print me-1"></i> طباعة الجدول
            </button>
            <?php if ($canManage): ?>
                <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">
                    <i class="fa-solid fa-plus me-1"></i> إضافة حصة جديدة
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm no-print mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= session('flash_success');  ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm no-print mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= session('flash_error');  ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Main Grid -->
    <div class="row g-4">
        <!-- List View Table -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-ol text-primary me-2"></i> جدول الحصص المدرجة</h5>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="scheduleTable">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-4">عرض التكوين / الفوج</th>
                                    <th>اليوم</th>
                                    <th>التوقيت</th>
                                    <th>المادة التكوينية</th>
                                    <th>الأستاذ المكوّن</th>
                                    <th>القاعة/الورشة</th>
                                    <?php if ($canManage): ?>
                                        <th class="pe-4 text-end no-print">العمليات</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($schedules)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fa-regular fa-calendar-minus display-6 d-block mb-2"></i>
                                            لا توجد حصص مسجلة في جدول استعمال الزمن حالياً.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($schedules as $s): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['offre_code']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($s['spec_ar']) ?></div>
                                            </td>
                                            <td class="fw-bold text-primary"><?= htmlspecialchars($s['jour']) ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border p-2">
                                                    <i class="fa-regular fa-clock text-warning me-1"></i>
                                                    <?= htmlspecialchars($s['heure_debut']) ?> - <?= htmlspecialchars($s['heure_fin']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-indigo"><?= htmlspecialchars($s['matiere_ar']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle-sm"><?= mb_substr($s['formateur_nom'], 0, 1) ?></div>
                                                    <span class="text-secondary small fw-bold"><?= htmlspecialchars($s['formateur_nom']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                                                    <i class="fa-solid fa-door-open me-1"></i> <?= htmlspecialchars($s['salle']) ?>
                                                </span>
                                            </td>
                                            <?php if ($canManage): ?>
                                                <td class="pe-4 text-end no-print">
                                                    <button class="btn btn-sm btn-light border rounded-pill px-3 me-1" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editScheduleModal<?= $s['id'] ?>">
                                                        <i class="fa-solid fa-edit text-warning me-1"></i> تعديل
                                                    </button>
                                                    <form action="/dashboard/schedule/delete/<?= $s['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الحصة؟')">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
    <button type="submit" class="btn btn-sm btn-light border rounded-pill px-3"><i class="fa-solid fa-trash-can text-danger me-1"></i> حذف</button>
</form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>

                                        <!-- Edit Modal for each row -->
                                        <?php if ($canManage): ?>
                                            <div class="modal fade" id="editScheduleModal<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border-0 shadow-lg">
                                                        <div class="modal-header border-light" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); color: white; border-radius: 12px 12px 0 0;">
                                                            <h5 class="fw-bold mb-0"><i class="fa-solid fa-edit me-2"></i> تعديل حصة استعمال الزمن</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="/dashboard/schedule/update" method="POST">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                            <div class="modal-body p-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small text-muted">عرض التكوين / الفوج</label>
                                                                    <select name="offre_id" class="form-select rounded-3" required>
                                                                        <?php foreach ($offres as $o): ?>
                                                                            <option value="<?= $o['id'] ?>" <?= $s['offre_id'] == $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['code']) ?> - <?= htmlspecialchars($o['spec_ar']) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small text-muted">المادة التكوينية</label>
                                                                    <select name="matiere_id" class="form-select rounded-3" required>
                                                                        <?php foreach ($matieres as $m): ?>
                                                                            <option value="<?= $m['id'] ?>" <?= $s['matiere_id'] == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['libelle_ar']) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small text-muted">الأستاذ المكوّن</label>
                                                                    <select name="formateur_id" class="form-select rounded-3" required>
                                                                        <?php foreach ($formateurs as $f): ?>
                                                                            <option value="<?= $f['id'] ?>" <?= $s['formateur_id'] == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nom_complet']) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="row g-3 mb-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-bold small text-muted">اليوم</label>
                                                                        <select name="jour" class="form-select rounded-3" required>
                                                                            <?php foreach (['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'] as $day): ?>
                                                                                <option value="<?= $day ?>" <?= $s['jour'] === $day ? 'selected' : '' ?>><?= $day ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-bold small text-muted">القاعة / الورشة</label>
                                                                        <input type="text" name="salle" class="form-control rounded-3" value="<?= htmlspecialchars($s['salle']) ?>" required>
                                                                    </div>
                                                                </div>
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-bold small text-muted">وقت البدء (ساعتين)</label>
                                                                        <input type="text" name="heure_debut" class="form-control rounded-3" placeholder="مثال: 08:00" value="<?= htmlspecialchars($s['heure_debut']) ?>" required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-bold small text-muted">وقت الانتهاء</label>
                                                                        <input type="text" name="heure_fin" class="form-control rounded-3" placeholder="مثال: 10:00" value="<?= htmlspecialchars($s['heure_fin']) ?>" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-light">
                                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                                                <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ التغييرات</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Visual Calendar Grid -->
        <div class="col-12 mt-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table-cells text-primary me-2"></i> المعاينة الأسبوعية لاستعمال الزمن</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <?php 
                        $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];
                        foreach ($days as $day): 
                            // Filter schedules for this day
                            $daySchedules = array_filter($schedules, function($s) use ($day) {
                                return $s['jour'] === $day;
                            });
                        ?>
                            <div class="col-lg-2-4 col-md-4 col-sm-6">
                                <div class="card border border-light h-100 rounded-3 shadow-xs">
                                    <div class="card-header text-center fw-bold py-2" style="background-color: #f8fafc; border-bottom: 2px solid #643edb; color: #482b8f;">
                                        <?= $day ?>
                                    </div>
                                    <div class="card-body p-2 d-flex flex-column gap-2" style="background-color: #fafbfc; min-height: 250px;">
                                        <?php if (empty($daySchedules)): ?>
                                            <div class="text-center text-muted small my-auto">لا توجد حصص</div>
                                        <?php else: ?>
                                            <?php foreach ($daySchedules as $ds): ?>
                                                <div class="p-2 rounded-3 border-start border-4 border-primary shadow-xs" style="background-color: white; font-size: 0.8rem; border-left-color: #643edb !important;">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="badge bg-light text-dark border small"><?= htmlspecialchars($ds['heure_debut']) ?>-<?= htmlspecialchars($ds['heure_fin']) ?></span>
                                                        <span class="text-muted small"><i class="fa-solid fa-door-open text-warning"></i> <?= htmlspecialchars($ds['salle']) ?></span>
                                                    </div>
                                                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($ds['matiere_ar']) ?></div>
                                                    <div class="text-secondary small" style="font-size:0.75rem;"><i class="fa-solid fa-user-tie me-1"></i> أ/ <?= htmlspecialchars($ds['formateur_nom']) ?></div>
                                                    <div class="text-muted small" style="font-size:0.7rem;"><i class="fa-solid fa-tag me-1"></i> <?= htmlspecialchars($ds['offre_code']) ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<?php if ($canManage): ?>
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-light" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); color: white; border-radius: 12px 12px 0 0;">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-plus me-2"></i> إضافة حصة استعمال زمن جديدة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/dashboard/schedule/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">عرض التكوين / الفوج</label>
                            <select name="offre_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>-- اختر الفوج البيداغوجي --</option>
                                <?php foreach ($offres as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['code']) ?> - <?= htmlspecialchars($o['spec_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">المادة التكوينية</label>
                            <select name="matiere_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>-- اختر المادة --</option>
                                <?php foreach ($matieres as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['libelle_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">الأستاذ المكوّن</label>
                            <select name="formateur_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>-- اختر الأستاذ --</option>
                                <?php foreach ($formateurs as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom_complet']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">اليوم</label>
                                <select name="jour" class="form-select rounded-3" required>
                                    <option value="الأحد">الأحد</option>
                                    <option value="الاثنين">الاثنين</option>
                                    <option value="الثلاثاء">الثلاثاء</option>
                                    <option value="الأربعاء">الأربعاء</option>
                                    <option value="الخميس">الخميس</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">القاعة / الورشة</label>
                                <input type="text" name="salle" class="form-control rounded-3" placeholder="مثال: القاعة 5" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">وقت البدء</label>
                                <input type="text" name="heure_debut" class="form-control rounded-3" placeholder="مثال: 08:00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">وقت الانتهاء</label>
                                <input type="text" name="heure_fin" class="form-control rounded-3" placeholder="مثال: 10:00" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-light">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">إضافة الحصة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    /* Styling elements for a premium layout */
    .avatar-circle-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #643edb 0%, #482b8f 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.85rem;
    }
    .col-lg-2-4 {
        flex: 0 0 20%;
        max-width: 20%;
    }
    @media (max-width: 991.98px) {
        .col-lg-2-4 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
    @media (max-width: 575.98px) {
        .col-lg-2-4 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }
</style>


@endsection
