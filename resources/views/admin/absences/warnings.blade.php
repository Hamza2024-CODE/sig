@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-1">سجل الإنذارات وقرارات الإقصاء</h3>
                <p class="text-muted mb-0">المتابعة الإدارية للطلاب الذين تجاوزوا نسب الغيابات المحددة قانوناً</p>
            </div>
            <div>
                <a href="/dashboard/absences" class="btn btn-outline-secondary px-4" style="border-radius: 8px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> العودة لدفتر الغيابات
                </a>
            </div>
        </div>
    </div>

    <!-- Warnings List Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-triangle-exclamation text-muted me-2"></i> قائمة الطلاب الموجهة لهم إشعارات</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('warnTable', 'notifications_absences.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('warnTable', 'notifications_absences.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-right" id="warnTable">
                    <thead class="table-light">
                        <tr class="fw-bold text-muted small">
                            <th class="py-3 px-4">رقم التسجيل</th>
                            <th>اسم المتربص</th>
                            <th>الفرع التكويني</th>
                            <th>مجموع الغيابات غير المبررة</th>
                            <th>الإجراء المستحق الحالي</th>
                            <th class="text-center no-print no-export">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($warnings)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-face-smile d-block mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                    لا يوجد أي متربص في منطقة خطر الإنذار حالياً.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($warnings as $w): ?>
                                <?php 
                                    $total = (float)$w['total_absences'];
                                    if ($total >= 8.00) {
                                        $badge = 'bg-danger text-white';
                                        $label = 'قرار إقصاء نهائي / Exclusion';
                                    } elseif ($total >= 5.00) {
                                        $badge = 'bg-warning text-dark';
                                        $label = 'إنذار ثاني / 2ème Avertissement';
                                    } else {
                                        $badge = 'bg-info text-dark';
                                        $label = 'إنذار أول / 1er Avertissement';
                                    }
                                ?>
                                <tr>
                                    <td class="py-3 px-4"><code class="text-dark fw-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($w['numero_matricule']) ?></code></td>
                                    <td>
                                        <strong class="text-primary fs-6"><?= htmlspecialchars($w['nom_ar'] . ' ' . $w['prenom_ar']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark px-3 py-1.5 fw-semibold"><?= htmlspecialchars($w['specialite_ar']) ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-danger fs-5"><?= $total ?> ساعة</strong>
                                    </td>
                                    <td>
                                        <span class="badge px-3 py-2 fw-bold <?= $badge ?>" style="font-size: 0.85rem;"><?= $label ?></span>
                                    </td>
                                    <td class="text-center no-print no-export">
                                        <a href="/dashboard/absences/print-warning/<?= $w['stagiaire_id'] ?>" target="_blank" class="btn btn-sm btn-dark px-3 py-1.5" style="border-radius: 6px;">
                                            <i class="fa-solid fa-print me-1"></i> طباعة الوثيقة الرسمية
                                        </a>
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
