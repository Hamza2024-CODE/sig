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
                <i class="fa-solid fa-flag-checkered text-primary me-2"></i> المداولات النهائية والتقييم الختامي للدورة / Deliberation
            </h3>
            <p class="text-muted mb-0 small">اعتماد محاضر المداولات النهائية للمتخرجين، وتحديد قائمة الحائزين على الشهادات</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/dashboard/diplomes" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;"><i class="fa-solid fa-award me-2"></i> إصدار الشهادات الرسمية</a>
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
                        <option value="studying">يتابع دراسته (نشط)</option>
                        <option value="graduate" selected>متخرج (المداولات النهائية)</option>
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
        if (select.value === 'studying') {
            let params = new URLSearchParams(window.location.search);
            params.delete('status');
            window.location.href = '/dashboard/evaluation-stagiaires?' + params.toString();
        }
    }
    </script>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">المداولات المعتمدة والمنجزة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['deliberations']) ?> سداسي</h2>
                    <span class="small"><i class="fa-solid fa-file-signature"></i> تمت المصادقة والتوقيع عليها كلياً</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">مداولات قيد الدراسة والمراجعة</h6>
                    <h2 class="display-5 fw-bold my-2 text-danger"><?= number_format($stats['en_attente']) ?> سداسيات</h2>
                    <span class="small text-muted"><i class="fa-solid fa-clock-rotate-left text-danger"></i> تتطلب مراجعة الطعون قبل القفل النهائي</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">نسبة القبول الكلية</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= $stats['taux_admission'] ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-circle-check text-success"></i> نسبة استيفاء متطلبات التخرج للمتربصين</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users-viewfinder text-primary me-2"></i> سجل المداولات الكبرى ومحاضر النجاح</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('evalFinTable', 'proces_verbaux_deliberation.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('evalFinTable', 'proces_verbaux_deliberation.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="evalFinTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المعهد / المركز</th>
                            <th>التخصص الدراسي المداوم</th>
                            <th class="text-center">تاريخ المداولة الرسمية</th>
                            <th class="text-center">أعضاء لجنة التحكيم المعتمدين</th>
                            <th class="pe-4 text-end">الحالة القانونية للمحضر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open d-block mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <div class="fw-bold text-dark fs-6">لا توجد محاضر مداولات نهائية مسجلة لهذه المؤسسة / المعايير المختارة</div>
                                    <div class="small text-muted mt-1">يرجى اختيار مؤسسة أو سنة دورة أخرى لعرض المداولات المتاحة.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['etab_nom'] ?? 'مؤسسة تكوينية') ?></div>
                                        <div class="text-muted small">رقم المحضر: <?= htmlspecialchars($item['numero_pv'] ?? 'PV-2026-104') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($item['spec_ar']) ?> (<?= htmlspecialchars($item['code_session'] ?? 'FEB-2026') ?>)</td>
                                    <td class="text-center fw-bold text-primary"><?= htmlspecialchars($item['date_deliberation']) ?></td>
                                    <td class="text-center">لجنة مداولات معتمدة بالولاية</td>
                                    <td class="pe-4 text-end">
                                        <?php if ($item['statut_pv'] === 'valide'): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-lock me-1"></i> مغلق ومصادق عليه</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-file-signature me-1"></i> مسودة قيد التوقيع</span>
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
