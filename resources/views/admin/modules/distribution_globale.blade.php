@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i> التوزيع العام للمقاعد والمؤسسات / Distribution
            </h3>
            <p class="text-muted mb-0 small">خريطة توزيع المؤسسات التكوينية، الداخليات، والقدرات البيداغوجية</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm"><i class="fa-solid fa-print me-2"></i> طباعة التقرير العام</button>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;"><i class="fa-solid fa-map-location-dot me-2"></i> خريطة التوزيع الجغرافي</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي مراكز التكوين المهني</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['centres']) ?></h2>
                    <span class="small"><i class="fa-solid fa-building-flag"></i> تغطي كافة بلديات الولاية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">المعاهد الوطنية المتخصصة (INSFP)</h6>
                    <h2 class="display-5 fw-bold my-2 text-primary"><?= number_format($stats['insfp']) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-graduation-cap text-primary"></i> تقدم شهادات تقني سامي (BTS)</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">القدرة الاستيعابية الكلية</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= number_format($stats['capacite']) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-bed text-success"></i> تشمل الأسرّة الداخلية والمقاعد</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-hotel text-primary me-2"></i> قدرة الإيواء والإطعام حسب المؤسسات</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('distGlobTable', 'distribution_globale.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('distGlobTable', 'distribution_globale.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="distGlobTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المؤسسة التكوينية</th>
                            <th class="text-center">القدرة البيداغوجية</th>
                            <th class="text-center">الأسرة المتوفرة (الداخلي)</th>
                            <th class="text-center">الوجبات اليومية</th>
                            <th class="pe-4 text-end">حالة المرفق</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">المعهد الوطني المتخصص في التكوين المهني سعيدة</div>
                                    <div class="text-muted small">الشهيد عماري قادة</div>
                                </td>
                                <td class="text-center fw-bold text-primary">800 مقعد</td>
                                <td class="text-center fw-bold">120 سرير</td>
                                <td class="text-center text-muted">240 وجبة/يوم</td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-success rounded-pill px-3 py-2">ممتلئ</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['nom_ar']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($item['nom_fr']) ?></div>
                                    </td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($item['total_capacite'] ?? 25) ?> مقعد</td>
                                    <td class="text-center fw-bold">150 سرير</td>
                                    <td class="text-center text-muted">300 وجبة/يوم</td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-success rounded-pill px-3 py-2">جاهز ومتاح</span>
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
