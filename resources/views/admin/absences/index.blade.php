@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-1">مراقبة الحضور والغيابات البيداغوجية</h3>
                <p class="text-muted mb-0">تسيير ومتابعة حضور المتربصين يومياً ورصد الغيابات غير المبررة</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/dashboard/absences/warnings" class="btn btn-warning text-white px-4" style="border-radius: 8px;">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> الإنذارات والإقصاءات
                </a>
                <a href="/dashboard/absences/add" class="btn text-white px-4" style="background-color: var(--color-gov-purple); border-radius: 8px;">
                    <i class="fa-solid fa-clipboard-user me-1"></i> تسجيل حضور اليوم
                </a>
            </div>
        </div>
    </div>

    <!-- Alert feeds -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; font-weight:600;">
            <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Overview Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="border-radius: 12px; border-right: 4px solid var(--color-gov-purple) !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-light text-primary rounded-circle" style="font-size: 1.5rem; color: var(--color-gov-purple) !important;"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <span class="text-muted d-block small">تعداد المتربصين الكلي</span>
                        <strong class="fs-4 text-dark"><?= $tCount ?> متربص</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="border-radius: 12px; border-right: 4px solid var(--color-gov-red) !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-light text-danger rounded-circle" style="font-size: 1.5rem;"><i class="fa-solid fa-user-minus"></i></div>
                    <div>
                        <span class="text-muted d-block small">إجمالي حالات الغياب المسجلة</span>
                        <strong class="fs-4 text-dark"><?= $aCount ?> غياب</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3" style="border-radius: 12px; border-right: 4px solid #ffd700 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-light text-warning rounded-circle" style="font-size: 1.5rem;"><i class="fa-solid fa-bell-slash"></i></div>
                    <div>
                        <span class="text-muted d-block small">متربصين في منطقة الخطر (>= 3 غيابات)</span>
                        <strong class="fs-4 text-dark"><?= $wCount ?> إنذارات</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History list -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-muted me-2"></i> آخر الغيابات المسجلة في المؤسسة</h5>
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
                    <thead class="table-light">
                        <tr class="fw-bold text-muted small">
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
                                    <td class="py-3 px-4 fw-bold text-dark"><?= htmlspecialchars($a['nom_ar'] . ' ' . $a['prenom_ar']) ?></td>
                                    <td><code class="text-secondary fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($a['numero_matricule']) ?></code></td>
                                    <td><span class="fw-semibold text-muted"><?= htmlspecialchars($a['date_absence']) ?></span></td>
                                    <td><span class="badge bg-light text-dark fw-semibold px-3 py-1.5"><?= htmlspecialchars($a['specialite_ar']) ?></span></td>
                                    <td><span class="text-muted small">08:00 - 10:00 (<?= (float)$a['duree_heures'] ?> س)</span></td>
                                    <td>
                                        <strong class="text-danger fs-6"><?= (float)$a['total_cumule_injustifie'] ?> ساعة</strong>
                                    </td>
                                    <td>
                                        <?php if ($a['est_justifiee']): ?>
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

@endsection
