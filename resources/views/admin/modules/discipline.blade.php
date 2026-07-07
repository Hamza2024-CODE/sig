@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $list
 * @var array $stagiaires
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-4 me-2"></i>
                <div><?= session('flash_success');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
                <div><?= session('flash_error');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-clipboard-user text-primary me-2"></i> المتابعة، الانضباط والمجلس التأديبي / Discipline
            </h3>
            <p class="text-muted mb-0 small">متابعة غيابات المتربصين، العقوبات الإدارية، وقرارات مجالس الانضباط</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addDisciplineModal">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> تسجيل عقوبة جديدة
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي الغيابات غير المبررة</h6>
                    <h2 class="display-5 fw-bold my-2 text-white"><?= number_format($stats['total_absences']) ?></h2>
                    <span class="small"><i class="fa-solid fa-circle-exclamation"></i> إحصائية سارية لهذا الشهر</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">الإنذارات الصادرة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['sanctions']) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-envelope-open-text text-warning"></i> إنذارات شفوية وكتابية رسمية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">قرارات الفصل الكلي</h6>
                    <h2 class="display-5 fw-bold my-2 text-dark"><?= number_format($stats['exclusions']) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-user-slash text-danger"></i> قرارات مصادق عليها من المجلس التأديبي</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-xmark text-primary me-2"></i> سجل المخالفات التأديبية النشطة</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('discTable', 'sanctions_discipline.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('discTable', 'sanctions_discipline.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="discTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المتربص</th>
                            <th>التخصص</th>
                            <th class="text-center">طبيعة المخالفة / السبب</th>
                            <th class="text-center">العقوبة الصادرة</th>
                            <th class="text-center">تاريخ القرار</th>
                            <th class="pe-4 text-end no-print no-export">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">لا توجد عقوبات تأديبية نشطة مسجلة.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['nom_ar'] . ' ' . $item['prenom_ar']) ?></div>
                                        <div class="text-muted small">رقم: <?= htmlspecialchars($item['numero_matricule']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($item['spec_ar']) ?></td>
                                    <td class="text-center fw-bold text-danger"><?= htmlspecialchars($item['motif']) ?></td>
                                    <td class="text-center">
                                        <?php if ($item['sanction'] === 'avertissement'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">إنذار (Avertissement)</span>
                                        <?php elseif ($item['sanction'] === 'blame'): ?>
                                            <span class="badge bg-orange text-white rounded-pill px-3 py-2" style="background-color: #f97316;">توبيخ (Blâme)</span>
                                        <?php elseif ($item['sanction'] === 'exclusion_temporaire'): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2">فصل مؤقت</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark rounded-pill px-3 py-2">فصل نهائي (Exclusion)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-muted"><?= htmlspecialchars($item['date_sanction']) ?></td>
                                    <td class="pe-4 text-end no-print no-export">
                                        <form action="/dashboard/discipline/delete/<?= $item['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذه العقوبة؟')">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fa-solid fa-trash me-1"></i> إلغاء</button>
</form>
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

<!-- Add Discipline Modal -->
<div class="modal fade" id="addDisciplineModal" tabindex="-1" aria-labelledby="addDisciplineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="addDisciplineModalLabel"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> تسجيل عقوبة تأديبية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/discipline/store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="stagiaire_id" class="form-label small fw-bold text-muted">اختر المتربص المخالف</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="stagiaire_id" name="stagiaire_id" required>
                            <?php foreach ($stagiaires as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom_ar'] . ' ' . $s['prenom_ar'] . ' - ' . $s['numero_matricule']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sanction" class="form-label small fw-bold text-muted">طبيعة العقوبة الصادرة</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="sanction" name="sanction" required>
                            <option value="avertissement" selected>إنذار (Avertissement)</option>
                            <option value="blame">توبيخ (Blâme)</option>
                            <option value="exclusion_temporaire">فصل مؤقت (Exclusion temporaire)</option>
                            <option value="exclusion_definitive">فصل كلي ونهائي (Exclusion définitive)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="motif" class="form-label small fw-bold text-muted">سبب العقوبة / موضوع المخالفة بالتفصيل</label>
                        <textarea class="form-control rounded-4 border-light-subtle shadow-sm px-3 py-2" id="motif" name="motif" rows="3" placeholder="مثال: غيابات غير مبررة تجاوزت 30 ساعة..." required></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="date_incident" class="form-label small fw-bold text-muted">تاريخ ارتكاب المخالفة</label>
                            <input type="date" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="date_incident" name="date_incident" required>
                        </div>
                        <div class="col-6">
                            <label for="date_sanction" class="form-label small fw-bold text-muted">تاريخ صدور القرار</label>
                            <input type="date" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="date_sanction" name="date_sanction" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">تسجيل وإرسال للمجلس</button>
                </div>
            </form>
        </div>
    </div>
</div>



@endsection
