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
                <i class="fa-solid fa-file-signature text-primary me-2"></i> تنظيم ورصد الامتحانات التقييمية الرسمية / Examens
            </h3>
            <p class="text-muted mb-0 small">إعداد جداول الاختبارات، توزيع الحراس، وتعيين مراكز وقاعات الامتحانات الوطنية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('dashboard/examens') }}?pdf=1" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm text-decoration-none" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; height: 38px;"><i class="fa-solid fa-file-pdf"></i> طباعة جداول الاختبارات</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">الامتحانات المجدولة الكلية</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['examens']) ?> امتحان</h2>
                    <span class="small"><i class="fa-solid fa-clipboard-list"></i> تشمل الاختبارات الكتابية والتطبيقية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">المواضيع المصادق عليها</h6>
                    <h2 class="display-5 fw-bold my-2 text-primary"><?= number_format($stats['sujets']) ?> موضوع</h2>
                    <span class="small text-muted"><i class="fa-solid fa-circle-check text-success"></i> من طرف اللجان البيداغوجية المتخصصة</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">قاعات الامتحانات المجهزة</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= number_format($stats['salles']) ?> قاعة</h2>
                    <span class="small text-muted"><i class="fa-solid fa-door-open text-primary"></i> تشمل المدرجات والمحترفات التطبيقية</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-check text-primary me-2"></i> الجدولة والرزنامة الأسبوعية للامتحانات</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('examsTable', 'calendrier_examens.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('examsTable', 'calendrier_examens.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="examsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المادة / الاختبار</th>
                            <th>التخصص / الفرع</th>
                            <th class="text-center">تاريخ وتوقيت الامتحان</th>
                            <th class="text-center">القاعة / المدرج</th>
                            <th class="pe-4 text-end">رئيس القاعة والمراقبون</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">خوارزميات وهياكل البيانات المعقدة</div>
                                    <div class="text-muted small">كتابي نظري (2 س)</div>
                                </td>
                                <td>مطور الويب والوسائط المتعددة</td>
                                <td class="text-center fw-bold text-primary">2026-05-24 09:00</td>
                                <td class="text-center">المدرج الكبير أ</td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-light text-primary border border-primary px-3 py-2">أ. حدادي مراد + 3 مراقبين</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['matiere_nom']) ?></div>
                                        <div class="text-muted small">تقييم رسمي</div>
                                    </td>
                                    <td><?= htmlspecialchars($item['spec_ar']) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= htmlspecialchars($item['date_examen']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($item['salle']) ?></td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-light text-primary border border-primary px-3 py-2">أ. <?= htmlspecialchars($item['examinateur'] ?? 'حدادي مراد') ?> + مراقبين</span>
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
