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
                <i class="fa-solid fa-chart-bar text-primary me-2"></i> التوزيع المفصل حسب الشعب المهنية / Distribution Détail
            </h3>
            <p class="text-muted mb-0 small">إحصائيات الشعب التكوينية النشطة، ونسب التغطية والتنوع البيداغوجي</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm"><i class="fa-solid fa-print me-2"></i> طباعة التقرير المفصل</button>
            <a href="{{ url('dashboard/distribution-detaillee') }}?pdf=1" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm text-decoration-none" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; height: 38px;"><i class="fa-solid fa-chart-line"></i> تقرير التنوع المهني</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي الشعب المهنية المفتوحة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= $stats['filieres'] ?> شعبة</h2>
                    <span class="small"><i class="fa-solid fa-shapes"></i> تغطي مجالات تكنولوجية وخدماتية وفلاحية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">التخصصات النشطة والمدونة</h6>
                    <h2 class="display-5 fw-bold my-2 text-primary"><?= $stats['specialites'] ?> تخصص</h2>
                    <span class="small text-muted"><i class="fa-solid fa-list-ol text-primary"></i> حسب المدونة الوطنية الرسمية للتخصصات</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">نسبة التغطية والتوجيه</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= $stats['taux_couverture'] ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-circle-check text-success"></i> نسبة كفاءة استغلال المقاعد المفتوحة</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-percent text-primary me-2"></i> نسب استقطاب المتكونين حسب الشعب الكبرى</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('distDetTable', 'distribution_detaillee.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('distDetTable', 'distribution_detaillee.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="distDetTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">الشعبة المهنية (Filière)</th>
                            <th class="text-center">التخصصات المقترحة</th>
                            <th class="text-center">التعداد الفعلي</th>
                            <th class="text-center">إناث %</th>
                            <th class="pe-4 text-end">التوجه الاستراتيجي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">إعلام آلي ورقمنة واتصالات (INT)</div>
                                    <div class="text-muted small">Informatique et Numérique</div>
                                </td>
                                <td class="text-center fw-bold text-primary">12 تخصص</td>
                                <td class="text-center fw-bold">549 متربص</td>
                                <td class="text-center text-danger fw-bold">52.6%</td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-primary rounded-pill px-3 py-2">أولوية تنموية رقمنة</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['filiere_nom']) ?></div>
                                        <div class="text-muted small">شعبة مهنية رسمية</div>
                                    </td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($item['specialites_count']) ?> تخصص</td>
                                    <td class="text-center fw-bold"><?= number_format($item['total_stagiaires']) ?> متربص</td>
                                    <td class="text-center text-danger fw-bold">35.0%</td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-primary rounded-pill px-3 py-2">مفتوح ومسجل</span>
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
