@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php 
/**
 * @var string $title
 * @var string $icon
 * @var array $stats
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid <?= htmlspecialchars($icon) ?> text-primary me-2"></i> <?= htmlspecialchars($title) ?>
            </h3>
            <p class="text-muted mb-0 small">الصفحة قيد التطوير - البيانات الظاهرة أدناه هي بيانات تجريبية (Mock Data)</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm"><i class="fa-solid fa-download me-2"></i> تصدير البيانات</button>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;"><i class="fa-solid fa-plus me-2"></i> إضافة جديد</button>
        </div>
    </div>

    <!-- Bento Grid High-Level Statistics -->
    <div class="row g-4 mb-4">
        <?php foreach($stats as $label => $val): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                <div class="card-body p-4 text-center">
                    <h6 class="text-muted fw-bold mb-3"><?= htmlspecialchars($label) ?></h6>
                    <h2 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($val) ?></h2>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Dummy Data Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table text-primary me-2"></i> تفاصيل البيانات التجريبية</h5>
            
            <div class="d-flex gap-2 align-items-center no-print">
                <button onclick="exportTableToExcel('genericTable', 'data_export.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('genericTable', 'data_export.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
                <input type="text" class="form-control rounded-pill bg-light border-0 px-4" placeholder="بحث..." style="width: 250px;">
                <button class="btn btn-light rounded-circle"><i class="fa-solid fa-filter"></i></button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="genericTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المعرف</th>
                            <th>البيان / الوصف</th>
                            <th class="text-center">تاريخ التسجيل</th>
                            <th class="text-center">الكمية / العدد</th>
                            <th class="pe-4 text-end">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for($i=1; $i<=5; $i++): ?>
                        <tr style="cursor: pointer;">
                            <td class="ps-4 fw-bold text-primary">#<?= rand(1000, 9999) ?></td>
                            <td>
                                <div class="fw-bold text-dark mb-1">عنصر تجريبي رقم <?= $i ?> لصفحة <?= htmlspecialchars($title) ?></div>
                                <div class="text-muted small">هذا نص تجريبي لإظهار شكل الجدول والتنسيق.</div>
                            </td>
                            <td class="text-center text-muted fw-bold"><?= date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')) ?></td>
                            <td class="text-center fw-bold text-dark"><?= rand(10, 500) ?></td>
                            <td class="pe-4 text-end">
                                <?php if($i % 2 == 0): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i> مكتمل</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-clock me-1"></i> قيد المعالجة</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 px-4 text-center">
            <button class="btn btn-light rounded-pill px-4 text-primary fw-bold">عرض المزيد</button>
        </div>
    </div>
</div>

@endsection
