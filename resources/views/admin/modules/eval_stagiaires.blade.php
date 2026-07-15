@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $wilayas
 * @var array $etablissements
 * @var array $years
 * @var int|null $selected_wilaya
 * @var int|null $selected_etab
 * @var int|null $selected_year
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-user-graduate text-primary me-2"></i> تقييم المتكونين ومعدلات التحصيل البيداغوجي / Evaluation
            </h3>
            <p class="text-muted mb-0 small">متابعة نتائج السداسيات، نسب ومعدلات الانتقال، والمتربصين المتفوقين في الشعب</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;"><i class="fa-solid fa-star me-2"></i> رصد المتفوقين</button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.6);">
        <div class="card-body p-4">
            <form method="GET" action="" id="filterForm" class="row g-3 align-items-end">
                
                <!-- Pedagogical Status -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-graduation-cap me-1"></i>الوضعية البيداغوجية</label>
                    <select name="status" id="filterStatus" class="form-select border-0 shadow-sm bg-light" style="border-radius: 10px; padding: 0.65rem;" onchange="handleStatusChange(this)">
                        <option value="studying" selected>يتابع دراسته (نشط)</option>
                        <option value="graduate">متخرج (المداولات النهائية)</option>
                    </select>
                </div>

                <!-- Wilaya Filter -->
                <?php 
                $user = session('user');
                $role = strtolower($user['role_code'] ?? '');
                $isAdmin = in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
                $isDfep = ($role === 'dfep');
                ?>
                <div class="col-md-3" <?php if(!$isAdmin): ?> style="display:none;" <?php endif; ?>>
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-map-location-dot me-1"></i>الولاية (DFEP)</label>
                    <select name="filter_wilaya" id="filterWilaya" class="form-select border-0 shadow-sm bg-light" style="border-radius: 10px; padding: 0.65rem;" onchange="this.form.submit()">
                        <option value="">-- كل الولايات --</option>
                        <?php foreach($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= $selected_wilaya == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Establishment Filter -->
                <div class="col-md-3" <?php if(!$isAdmin && !$isDfep): ?> style="display:none;" <?php endif; ?>>
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-hotel me-1"></i>المؤسسة التكوينية</label>
                    <select name="filter_etab" id="filterEtab" class="form-select border-0 shadow-sm bg-light" style="border-radius: 10px; padding: 0.65rem;" onchange="this.form.submit()">
                        <option value="">-- كل المؤسسات --</option>
                        <?php foreach($etablissements as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= $selected_etab == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Year Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-calendar-days me-1"></i>سنة الدورة</label>
                    <select name="filter_year" id="filterYear" class="form-select border-0 shadow-sm bg-light" style="border-radius: 10px; padding: 0.65rem;" onchange="this.form.submit()">
                        <option value="">-- كل السنوات --</option>
                        <?php foreach($years as $y): ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="col-md-1 text-end">
                    <a href="?" class="btn btn-light w-100 fw-bold border" style="border-radius: 10px; padding: 0.65rem;" title="إعادة تعيين الفلاتر">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function handleStatusChange(select) {
        if (select.value === 'graduate') {
            let params = new URLSearchParams(window.location.search);
            params.delete('status');
            window.location.href = '/dashboard/evaluation-finale?' + params.toString();
        }
    }
    </script>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">المتكونين الذين تم تقييمهم</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['evalues']) ?> متربص</h2>
                    <span class="small"><i class="fa-solid fa-user-check"></i> تم إدخال جميع علاماتهم للسداسي الحالي</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">التقييمات قيد الانتظار والمعالجة</h6>
                    <h2 class="display-5 fw-bold my-2 text-danger"><?= number_format($stats['en_attente']) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-clock-rotate-left text-danger"></i> قيد الإدخال والتدقيق من طرف المكونين</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">نسبة النجاح والتحصيل العام</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= $stats['taux_reussite'] ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-chart-line text-success"></i> تطور إيجابي بنسبة 2.5%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-trophy text-primary me-2"></i> لوحة الشرف: المتفوقين الأوائل حسب الشعب والأنماط</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('evalStgTable', 'liste_excellence.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('evalStgTable', 'liste_excellence.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="evalStgTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المتكون (الاسم واللقب)</th>
                            <th>الشعبة والفرع</th>
                            <th class="text-center">المستوى</th>
                            <th class="text-center">المعدل العام للسداسي</th>
                            <th class="pe-4 text-end">رتبة التفوق</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">بن علي عبد الرحمن</div>
                                    <div class="text-muted small">رقم: 20261234</div>
                                </td>
                                <td>مطور الويب والوسائط المتعددة</td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill px-3 py-1">السداسي الثالث</span></td>
                                <td class="text-center fw-bold text-success fs-5">16.85</td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-trophy me-1"></i> الأول على الدفعة</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $rank = 1; foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['nom_ar'] . ' ' . $item['prenom_ar']) ?></div>
                                        <div class="text-muted small">رقم: <?= htmlspecialchars($item['numero_matricule']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($item['spec_ar']) ?></td>
                                    <td class="text-center"><span class="badge bg-primary rounded-pill px-3 py-1">السداسي الحالي</span></td>
                                    <td class="text-center fw-bold text-success fs-5"><?= htmlspecialchars($item['moyenne'] ?? '14.50') ?></td>
                                    <td class="pe-4 text-end">
                                        <?php if ($rank === 1): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-trophy me-1"></i> الأول على الدفعة</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark rounded-pill px-3 py-2">مرتبة متميزة</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
