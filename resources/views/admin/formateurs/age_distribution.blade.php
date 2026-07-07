@extends('layouts.main')
@section('title', $title ?? 'إحصائيات المكونين حسب السن والشعب التكوينية')
@section('content')

<?php
/**
 * @var array $distribution
 * @var \Illuminate\Pagination\LengthAwarePaginator $paginatedTeachers
 * @var int $totalCount
 * @var float $averageAge
 * @var int $youngCount
 * @var int $seniorCount
 * @var array $wilayas
 * @var array $etablissements
 * @var array $branchesList
 * @var string $role_code
 * @var string $search
 * @var int $filter_wilaya
 * @var int $filter_etab
 * @var int $filter_branch
 * @var string $active_tab
 */
$youngPercent = $totalCount > 0 ? round(($youngCount / $totalCount) * 100, 1) : 0;
$seniorPercent = $totalCount > 0 ? round(($seniorCount / $totalCount) * 100, 1) : 0;
?>

<div class="animate__animated animate__fadeIn" style="font-family:'Cairo', sans-serif;">

    {{-- ══ HEADER ══ --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--text-main);">
                <i class="fa-solid fa-chart-line text-primary me-2"></i>
                إحصائيات السن والشعب للأساتذة / Statistiques d'Âge et Branches
            </h4>
            <p class="text-muted small mb-0">
                تحليل الفئات العمرية والتخصصات التكوينية لهيئة التأطير البيداغوجي
            </p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ url('dashboard/formateurs') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold btn-sm shadow-sm d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-arrow-right"></i> العودة لكشف الموظفين
            </a>
        </div>
    </div>

    {{-- ══ STATS CARDS ══ --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Total Teachers --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 position-relative overflow-hidden" 
                 style="border-radius:18px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color:#fff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold opacity-75">إجمالي الأساتذة والمكونين</span>
                    <i class="fa-solid fa-chalkboard-user fs-4 opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-1" style="font-family:'Inter';"><?= number_format($totalCount) ?></h3>
                <span class="small opacity-75">موظف مؤطر نشط</span>
            </div>
        </div>

        {{-- Card 2: Average Age --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 position-relative overflow-hidden" 
                 style="border-radius:18px; background: linear-gradient(135deg, #10b981 0%, #047857 100%); color:#fff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold opacity-75">متوسط العمر</span>
                    <i class="fa-solid fa-hourglass-half fs-4 opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-1" style="font-family:'Inter';"><?= $averageAge ?> <span style="font-size:0.9rem;">سنة</span></h3>
                <span class="small opacity-75">متوسط عمر الطاقم البيداغوجي</span>
            </div>
        </div>

        {{-- Card 3: Young Teachers --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 position-relative overflow-hidden" 
                 style="border-radius:18px; background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); color:#fff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold opacity-75">الأساتذة الشباب (أقل من 35 سنة)</span>
                    <i class="fa-solid fa-child-reaching fs-4 opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-1" style="font-family:'Inter';"><?= number_format($youngCount) ?></h3>
                <span class="small opacity-75">بنسبة تبلغ <strong style="font-family:'Inter';"><?= $youngPercent ?>%</strong> من الإجمالي</span>
            </div>
        </div>

        {{-- Card 4: Seniors --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 position-relative overflow-hidden" 
                 style="border-radius:18px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color:#fff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold opacity-75">ذوي الخبرة (50 سنة فما فوق)</span>
                    <i class="fa-solid fa-award fs-4 opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-1" style="font-family:'Inter';"><?= number_format($seniorCount) ?></h3>
                <span class="small opacity-75">بنسبة تبلغ <strong style="font-family:'Inter';"><?= $seniorPercent ?>%</strong> من الإجمالي</span>
            </div>
        </div>
    </div>

    {{-- ══ FILTER BAR ══ --}}
    <div class="card border-0 shadow-sm p-3 mb-4 no-print" style="border-radius:16px; background:var(--card-bg); border:1px solid var(--card-border)!important;">
        <form method="GET" action="{{ url('dashboard/formateurs/age-distribution') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">

            {{-- بحث نصي في التخصص --}}
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">بحث بالتخصص</label>
                <div class="input-group">
                    <span class="input-group-text border-0" style="background:var(--input-bg,#f8f9fa);">
                        <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           class="form-control border-0 rounded-end" placeholder="تخصص..."
                           style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                </div>
            </div>

            {{-- فلتر الشعبة التكوينية --}}
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">الشعبة التكوينية (Filière)</label>
                <select name="filter_branch" class="form-select border-0 rounded" onchange="this.form.submit()"
                        style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                    <option value="0">كل الشعب التكوينية</option>
                    <?php foreach ($branchesList as $br): ?>
                    <option value="<?= $br['id'] ?>" <?= $filter_branch == $br['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($br['nom_ar'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            {{-- فلتر الولاية --}}
            <?php if (in_array($role_code, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])): ?>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">الولاية</label>
                <select name="filter_wilaya" class="form-select border-0 rounded" onchange="this.form.submit()"
                        style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                    <option value="0">كل الولايات</option>
                    <?php foreach ($wilayas as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $filter_wilaya == $w['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['nom_ar'] ?? $w['nom'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            {{-- فلتر المؤسسة --}}
            <?php if (count($etablissements) > 1 || !empty($etablissements)): ?>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية</label>
                <select name="filter_etab" class="form-select border-0 rounded" onchange="this.form.submit()"
                        style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                    <option value="0">كل المؤسسات</option>
                    <?php foreach ($etablissements as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $filter_etab == $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nom_ar'] ?? $e['nom'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="font-size:0.88rem;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ url('dashboard/formateurs/age-distribution') }}?tab=<?= htmlspecialchars($active_tab) ?>" class="btn btn-outline-secondary rounded-pill px-3 fw-bold"
                   style="font-size:0.88rem;">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- ══ TABS NAVIGATION ══ --}}
    <ul class="nav nav-pills mb-3 no-print gap-2" id="statsTab" role="tablist" style="font-size:0.9rem;">
        <li class="nav-item" role="presentation">
            <a href="{{ url('dashboard/formateurs/age-distribution') }}?tab=aggregated&search={{ request('search') }}&filter_branch={{ request('filter_branch') }}&filter_wilaya={{ request('filter_wilaya') }}&filter_etab={{ request('filter_etab') }}"
               class="nav-link rounded-pill px-4 fw-bold d-flex align-items-center gap-2 {{ $active_tab === 'aggregated' ? 'active' : 'bg-white text-muted border' }}"
               style="{{ $active_tab === 'aggregated' ? 'background-color:#482b8f; color:#fff;' : '' }}">
                <i class="fa-solid fa-table-cells"></i>
                التوزيع الإجمالي (شعبة / تخصص / فئة عمرية)
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="{{ url('dashboard/formateurs/age-distribution') }}?tab=detailed&search={{ request('search') }}&filter_branch={{ request('filter_branch') }}&filter_wilaya={{ request('filter_wilaya') }}&filter_etab={{ request('filter_etab') }}"
               class="nav-link rounded-pill px-4 fw-bold d-flex align-items-center gap-2 {{ $active_tab === 'detailed' ? 'active' : 'bg-white text-muted border' }}"
               style="{{ $active_tab === 'detailed' ? 'background-color:#482b8f; color:#fff;' : '' }}">
                <i class="fa-solid fa-list-ol"></i>
                القائمة التفصيلية للأساتذة (حسب السن)
            </a>
        </li>
    </ul>

    {{-- ══ TAB CONTENTS ══ --}}
    <div class="tab-content">
        
        {{-- Tab 1: Aggregated Matrix --}}
        <?php if ($active_tab === 'aggregated'): ?>
        <div class="tab-pane fade show active" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
                <span class="small text-muted fw-bold">هيكل الفئات العمرية مقسم على 5 سنوات حسب التخصص والشعبة</span>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
                        <i class="fa-solid fa-print me-1"></i> طباعة الجدول
                    </button>
                    <button onclick="exportTableToExcel('aggregatedTable','توزيع_الأساتذة_حسب_السن.xls')"
                            class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius:18px; background:var(--card-bg); border:1px solid var(--card-border)!important;">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small text-center" id="aggregatedTable" style="text-align:right;">
                        <thead style="background:rgba(0,0,0,0.03);">
                            <tr class="text-muted fw-bold" style="font-size:0.75rem;">
                                <th class="py-3 ps-3 text-center" style="width:50px;">#</th>
                                <th style="text-align:right;">الشعبة التكوينية</th>
                                <th style="text-align:right;">التخصص التكويني</th>
                                <th>أقل من 25</th>
                                <th>25 - 29</th>
                                <th>30 - 34</th>
                                <th>35 - 39</th>
                                <th>40 - 44</th>
                                <th>45 - 49</th>
                                <th>50 - 54</th>
                                <th>55 - 59</th>
                                <th>60 فما فوق</th>
                                <th class="text-muted">غير محدد</th>
                                <th class="text-primary">المجموع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($distribution)): ?>
                            <tr>
                                <td colspan="14" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fs-2 d-block mb-3 opacity-25"></i>
                                    لا توجد نتائج متوفرة.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php 
                            $rowNum = 1;
                            $colTotals = [
                                '<25' => 0, '25-29' => 0, '30-34' => 0, '35-39' => 0, '40-44' => 0,
                                '45-49' => 0, '50-54' => 0, '55-59' => 0, '60+' => 0, 'غير محدد' => 0, 'total' => 0
                            ];
                            ?>
                            <?php foreach ($distribution as $branchName => $specs): ?>
                                <?php foreach ($specs as $specName => $brackets): ?>
                                    <tr style="border-bottom:1px solid var(--card-border,#f1f5f9); transition:background 0.1s;"
                                        onmouseover="this.style.background='rgba(0,0,0,0.015)'" onmouseout="this.style.background=''">
                                        <td class="py-2 ps-3 text-muted" style="font-family:'Inter'; font-size:0.75rem;"><?= $rowNum++ ?></td>
                                        <td style="text-align:right;" class="fw-bold text-dark"><?= htmlspecialchars($branchName) ?></td>
                                        <td style="text-align:right;" class="fw-semibold text-secondary"><?= htmlspecialchars($specName) ?></td>
                                        <td><?= $brackets['<25'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['25-29'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['30-34'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['35-39'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['40-44'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['45-49'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['50-54'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['55-59'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td><?= $brackets['60+'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td class="text-muted"><?= $brackets['غير محدد'] ?: '<span class="text-muted opacity-25">—</span>' ?></td>
                                        <td class="fw-bold text-primary" style="font-family:'Inter';"><?= number_format($brackets['total']) ?></td>
                                    </tr>
                                    <?php 
                                    foreach ($colTotals as $key => &$val) {
                                        $val += (int)$brackets[$key];
                                    }
                                    unset($val);
                                    ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            {{-- Row Total --}}
                            <tr class="fw-bold bg-light" style="border-top:2px solid #ddd;">
                                <td colspan="3" class="py-3 text-end ps-4">مجموع كلي / Total Général</td>
                                <td style="font-family:'Inter';"><?= $colTotals['<25'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['25-29'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['30-34'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['35-39'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['40-44'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['45-49'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['50-54'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['55-59'] ?: '0' ?></td>
                                <td style="font-family:'Inter';"><?= $colTotals['60+'] ?: '0' ?></td>
                                <td class="text-muted" style="font-family:'Inter';"><?= $colTotals['غير محدد'] ?: '0' ?></td>
                                <td class="text-primary fs-6" style="font-family:'Inter';"><?= number_format($colTotals['total']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        {{-- Tab 2: Detailed Sorted list --}}
        <?php else: ?>
        <div class="tab-pane fade show active" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
                <span class="small text-muted fw-bold">قائمة الأساتذة مرتبة تنازلياً من الأكبر سناً للأصغر</span>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
                        <i class="fa-solid fa-print me-1"></i> طباعة الجدول
                    </button>
                    {{-- Excel button streams from backend to support 84k rows sorting --}}
                    <a href="{{ url('dashboard/formateurs/age-distribution/export') }}?search={{ request('search') }}&filter_branch={{ request('filter_branch') }}&filter_wilaya={{ request('filter_wilaya') }}&filter_etab={{ request('filter_etab') }}"
                       class="btn btn-success btn-sm rounded-pill px-3 fw-bold text-white text-decoration-none d-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-file-csv"></i> تصدير Excel (قائمة مرتبة)
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" style="border-radius:18px; background:var(--card-bg); border:1px solid var(--card-border)!important;">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small" id="detailedTable" style="text-align:right;">
                        <thead style="background:rgba(0,0,0,0.03);">
                            <tr class="text-muted fw-bold" style="font-size:0.78rem;">
                                <th class="py-3 ps-4" style="width:60px;">#</th>
                                <th>اسم ولقب الأستاذ</th>
                                <th>الشعبة</th>
                                <th>التخصص</th>
                                <th>المؤسسة التكوينية</th>
                                <th class="text-center">السن (العمر)</th>
                                <th class="text-center">الفئة العمرية</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($paginatedTeachers->isEmpty()): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fs-2 d-block mb-3 opacity-25"></i>
                                    لا توجد سجلات مطابقة للبحث.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $idx = ($paginatedTeachers->currentPage() - 1) * $paginatedTeachers->perPage() + 1; ?>
                            <?php foreach ($paginatedTeachers as $t): ?>
                            <tr style="border-bottom:1px solid var(--card-border,#f1f5f9); transition:background 0.1s;"
                                onmouseover="this.style.background='rgba(0,0,0,0.015)'" onmouseout="this.style.background=''">
                                <td class="py-2 ps-4 text-muted" style="font-family:'Inter'; font-size:0.75rem;"><?= $idx++ ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($t['name']) ?></td>
                                <td class="text-secondary"><?= htmlspecialchars($t['branch']) ?></td>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($t['specialty']) ?></td>
                                <td class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars($t['etab']) ?></td>
                                <td class="text-center fw-bold" style="font-family:'Inter';">
                                    <?= $t['age'] !== null ? $t['age'] . ' سنة' : 'غير محدد' ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge px-2 py-1 bg-light text-dark border rounded-pill" style="font-size:0.75rem;">
                                        <?= htmlspecialchars($t['bracket']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PAGINATION --}}
            <div class="d-flex justify-content-center no-print">
                <?= $paginatedTeachers->links() ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

@endsection
