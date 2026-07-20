@extends('layouts.main')
@section('title', $title ?? 'التوزيع العام للمؤسسات')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $stats
 * @var array  $grouped  — keyed by DFEP id, each has: dfep_nom, dfep_nom_fr, wilaya_nom, institutions[]
 */
?>
<style>
    .dfep-header-row {
        background: linear-gradient(135deg, #1e3a5f 0%, #0d6efd 100%);
        color: #fff !important;
    }
    .dfep-header-row td {
        color: #fff !important;
        font-size: 0.92rem;
    }
    .insfp-row {
        background: rgba(111, 66, 193, 0.05);
        border-right: 3px solid #6f42c1;
    }
    .cfpa-row {
        background: #fff;
    }
    .badge-insfp {
        background: linear-gradient(135deg, #6f42c1, #4c2894);
        color: #fff;
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 30px;
    }
    .badge-cfpa {
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        color: #fff;
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 30px;
    }
    .institution-indent {
        padding-right: 28px !important;
    }
    tr.dfep-header-row td { padding: 10px 16px; }
</style>

<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i> التوزيع العام للمقاعد والمؤسسات / Distribution
            </h3>
            <p class="text-muted mb-0 small">خريطة توزيع المؤسسات التكوينية حسب كل مديرية ولائية — المؤسسات المنشطة فقط</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-print me-2"></i> طباعة التقرير العام
            </button>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">
                <i class="fa-solid fa-map-location-dot me-2"></i> خريطة التوزيع الجغرافي
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي المؤسسات التكوينية المنشطة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['centres']) ?></h2>
                    <span class="small"><i class="fa-solid fa-building-flag"></i> المنشطة فقط — تغطي كافة الولايات</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">المعاهد الوطنية المتخصصة (INSFP/IFP)</h6>
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
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-hotel text-primary me-2"></i>
                التفصيل حسب كل مديرية ولائية ومؤسساتها
            </h5>
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
            <!-- Legend -->
            <div class="d-flex gap-3 px-4 pb-3 no-print flex-wrap">
                <span class="badge-insfp">معهد وطني / INSFP</span>
                <span class="badge-cfpa">مركز تكوين / CFPA</span>
                <span class="text-muted small ms-2"><i class="fa-solid fa-circle-check text-success me-1"></i> المؤسسات المنشطة فقط</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="distGlobTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المديرية / المؤسسة التكوينية</th>
                            <th class="text-center">النوع</th>
                            <th class="text-center">القدرة البيداغوجية</th>
                            <th class="text-center">الأسرة (الداخلي)</th>
                            <th class="text-center">الوجبات اليومية</th>
                            <th class="text-center">المتربصين</th>
                            <th class="pe-4 text-end">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grouped)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-circle-info fa-2x mb-3 d-block text-primary opacity-50"></i>
                                    لا توجد مؤسسات منشطة حسب نطاق صلاحياتك
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($grouped as $dfepId => $group): ?>
                                {{-- ══ DFEP Header Row ══ --}}
                                <tr class="dfep-header-row">
                                    <td colspan="7" class="ps-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="fa-solid fa-building-columns fa-lg opacity-75"></i>
                                            <div>
                                                <div class="fw-bold" style="font-size:0.95rem;">
                                                    <?= htmlspecialchars($group['dfep_nom']) ?>
                                                </div>
                                                <?php if ($group['wilaya_nom']): ?>
                                                    <div style="font-size:0.78rem; opacity:0.8;">
                                                        <i class="fa-solid fa-location-dot me-1"></i>
                                                        ولاية <?= htmlspecialchars($group['wilaya_nom']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="ms-auto badge rounded-pill" style="background:rgba(255,255,255,0.2); font-size:0.75rem;">
                                                <?= count($group['institutions']) ?> مؤسسة منشطة
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                {{-- ══ Institutions under this DFEP ══ --}}
                                <?php if (empty($group['institutions'])): ?>
                                    <tr>
                                        <td colspan="7" class="ps-5 text-muted small py-2 fst-italic">
                                            لا توجد مؤسسات منشطة لهذه المديرية
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($group['institutions'] as $item):
                                        $isInsfp = !empty($item['is_insfp']);
                                        $capacity = (int)($item['total_capacite'] ?? ($isInsfp ? 300 : 250));
                                        $inscrits = (int)($item['total_inscrits'] ?? 0);
                                    ?>
                                        <tr class="<?= $isInsfp ? 'insfp-row' : 'cfpa-row' ?>">
                                            <td class="institution-indent">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa-solid <?= $isInsfp ? 'fa-university text-purple' : 'fa-building text-primary' ?> small opacity-75"
                                                       style="color: <?= $isInsfp ? '#6f42c1' : '#0d6efd' ?> !important;"></i>
                                                    <div>
                                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($item['nom_ar']) ?></div>
                                                        <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($item['nom_fr'] ?? '') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($isInsfp): ?>
                                                    <span class="badge-insfp">INSFP/IFP</span>
                                                <?php else: ?>
                                                    <span class="badge-cfpa">CFPA</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center fw-bold text-primary small"><?= number_format($capacity) ?> مقعد</td>
                                            <td class="text-center fw-bold small"><?= $isInsfp ? '150' : '100' ?> سرير</td>
                                            <td class="text-center text-muted small"><?= $isInsfp ? '300' : '200' ?> وجبة/يوم</td>
                                            <td class="text-center">
                                                <?php if ($inscrits > 0): ?>
                                                    <span class="fw-bold text-success small"><?= number_format($inscrits) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <span class="badge bg-success rounded-pill px-3 py-2" style="font-size:0.72rem;">
                                                    جاهز ومتاح
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
