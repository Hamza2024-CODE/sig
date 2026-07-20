@extends('layouts.main')
@section('title', $title ?? 'التوزيع العام للمؤسسات')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $stats
 * @var array  $grouped  — keyed by DFEP id, each has: dfep_nom, dfep_nom_fr, wilaya_nom, institutions[]
 *
 * Nature types:
 *  4  = معهد التكوين والتعليم المهنيين (IFP)
 *  6  = معهد وطني متخصص في التكوين المهني (INSFP)
 *  7  = معهد التعليم المهني
 *  8  = مركز التكوين المهني والتمهين (CFPA)
 *  9  = ملحقة مركز التكوين المهني والتمهين
 * 10  = ملحقة مركز الوطني للتكوين عن بعد
 * 11  = ملحقة معهد وطني متخصص
 * 12  = مؤسسة خاصة معتمدة
 * 13  = معهد وطني للتكوين والتعليم المهنيين
 * 15  = فرع منتدب دون مرسوم
 */

// Color/icon map per nature
$natureConfig = [
    4  => ['label' => 'IFP',      'badge_class' => 'badge-ifp',     'icon' => 'fa-graduation-cap', 'color' => '#0d6efd'],
    6  => ['label' => 'INSFP',    'badge_class' => 'badge-insfp',   'icon' => 'fa-university',     'color' => '#6f42c1'],
    7  => ['label' => 'IEM',      'badge_class' => 'badge-iem',     'icon' => 'fa-school',         'color' => '#20c997'],
    8  => ['label' => 'CFPA',     'badge_class' => 'badge-cfpa',    'icon' => 'fa-building',       'color' => '#0d6efd'],
    9  => ['label' => 'ملحقة',   'badge_class' => 'badge-annexe',  'icon' => 'fa-code-branch',    'color' => '#fd7e14'],
    10 => ['label' => 'CNFEPD',   'badge_class' => 'badge-cnfepd',  'icon' => 'fa-wifi',           'color' => '#17a2b8'],
    11 => ['label' => 'ملحقة م', 'badge_class' => 'badge-annexe',  'icon' => 'fa-code-branch',    'color' => '#fd7e14'],
    12 => ['label' => 'خاصة',    'badge_class' => 'badge-prive',   'icon' => 'fa-store',          'color' => '#6c757d'],
    13 => ['label' => 'INFEP',    'badge_class' => 'badge-infep',   'icon' => 'fa-landmark',       'color' => '#dc3545'],
    15 => ['label' => 'فرع',     'badge_class' => 'badge-branch',  'icon' => 'fa-sitemap',        'color' => '#adb5bd'],
];
?>
<style>
    /* Nature type badges */
    .badge-insfp  { background: linear-gradient(135deg,#6f42c1,#4c2894); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-ifp    { background: linear-gradient(135deg,#0d6efd,#0a58ca); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-iem    { background: linear-gradient(135deg,#20c997,#13795b); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-cfpa   { background: linear-gradient(135deg,#3d8bfd,#0d6efd); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-annexe { background: linear-gradient(135deg,#fd7e14,#c45f00); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-cnfepd { background: linear-gradient(135deg,#17a2b8,#0d7a8a); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-prive  { background: linear-gradient(135deg,#6c757d,#495057); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-infep  { background: linear-gradient(135deg,#dc3545,#a71d2a); color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }
    .badge-branch { background: #adb5bd; color:#fff; font-size:.7rem; padding:3px 9px; border-radius:30px; display:inline-block; white-space:nowrap; }

    /* DFEP group header */
    .dfep-header-row td {
        background: linear-gradient(135deg,#1e3a5f 0%,#0d6efd 100%);
        color: #fff !important;
        font-size: .9rem;
        padding: 12px 16px;
        cursor: pointer;
    }
    .dfep-header-row td:hover { opacity:.92; }

    /* Nature sub-header */
    .nature-subheader td {
        background: #f0f4ff;
        color: #1e3a5f;
        font-size: .8rem;
        font-weight: 700;
        padding: 6px 16px;
        border-top: 1px solid #dce3f0;
    }
    .nature-subheader td:first-child { padding-right: 36px !important; }

    /* Institution row */
    .institution-row { border-bottom: 1px solid #f1f4fb; }
    .institution-row td { font-size: .83rem; padding: 8px 12px; }
    .institution-row:hover td { background: #f8f9ff; }
    .institution-indent { padding-right: 44px !important; }

    /* Collapse toggle arrow */
    .dfep-toggle-icon { transition: transform .3s ease; display:inline-block; }
    .collapsed .dfep-toggle-icon { transform: rotate(-90deg); }

    @media print {
        .no-print { display:none !important; }
        .dfep-header-row td { background: #1e3a5f !important; -webkit-print-color-adjust: exact; }
    }
</style>

<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color:#1e293b; font-family:'Cairo',sans-serif;">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i> التوزيع العام للمؤسسات التكوينية
            </h3>
            <p class="text-muted mb-0 small">تفصيل حسب كل مديرية ولائية ونوع المؤسسة — المؤسسات المنشطة فقط</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-print me-2"></i> طباعة
            </button>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:linear-gradient(135deg,#482b8f,#2e1c5b);color:#fff;">
                <div class="card-body p-3 text-center">
                    <div class="small text-white-50 fw-bold">إجمالي المؤسسات المنشطة</div>
                    <div class="display-6 fw-bold text-warning mt-1"><?= number_format($stats['centres']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:#fff;">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted fw-bold">المعاهد الوطنية (INSFP/IFP)</div>
                    <div class="display-6 fw-bold text-primary mt-1"><?= number_format($stats['insfp']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:#fff;">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted fw-bold">إجمالي الأقسام (sections)</div>
                    <div class="display-6 fw-bold text-success mt-1"><?= number_format($stats['sections']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:#fff;">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted fw-bold">إجمالي المتربصين</div>
                    <div class="display-6 fw-bold text-danger mt-1"><?= number_format($stats['total_inscrits']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="d-flex flex-wrap gap-2 mb-3 no-print">
        <span class="badge-insfp">معهد وطني متخصص (INSFP)</span>
        <span class="badge-ifp">معهد التكوين (IFP)</span>
        <span class="badge-iem">معهد التعليم المهني (IEM)</span>
        <span class="badge-cfpa">مركز التكوين (CFPA)</span>
        <span class="badge-annexe">ملحقة</span>
        <span class="badge-cnfepd">تكوين عن بعد (CNFEPD)</span>
        <span class="badge-prive">مؤسسة خاصة</span>
        <span class="text-muted small align-self-center ms-2"><i class="fa-solid fa-circle-check text-success me-1"></i> منشطة فقط</span>
    </div>

    <!-- Main Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-sitemap text-primary me-2"></i>
                التفصيل حسب المديرية الولائية ونوع المؤسسة
            </h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('distGlobTable','distribution_globale.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('distGlobTable','distribution_globale.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="expandAll()" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold shadow-sm no-print">
                    <i class="fa-solid fa-expand me-1"></i> توسيع الكل
                </button>
                <button onclick="collapseAll()" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold shadow-sm no-print">
                    <i class="fa-solid fa-compress me-1"></i> طي الكل
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-2">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="distGlobTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4" style="min-width:280px;">المديرية / المؤسسة</th>
                            <th class="text-center" style="min-width:90px;">النوع</th>
                            <th class="text-center">المتربصون</th>
                            <th class="text-center">الأقسام</th>
                            <th class="text-center">القدرة</th>
                            <th class="pe-4 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody id="distGlobBody">
                        <?php if (empty($grouped)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-circle-info fa-2x mb-3 d-block text-primary opacity-50"></i>
                                    لا توجد مؤسسات منشطة حسب نطاق صلاحياتك
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($grouped as $dfepId => $group):
                                // Sub-group institutions by nature type
                                $byNature = [];
                                foreach ($group['institutions'] as $inst) {
                                    $nat = (int)($inst['IDNature_etsF'] ?? 0);
                                    $byNature[$nat][] = $inst;
                                }
                                ksort($byNature);

                                // Build summary chips for DFEP header
                                $typeSummary = [];
                                foreach ($byNature as $nat => $insts) {
                                    $cfg = $natureConfig[$nat] ?? null;
                                    $lbl = $cfg ? $cfg['label'] : "N{$nat}";
                                    $typeSummary[] = count($insts) . ' ' . $lbl;
                                }

                                $groupId = 'dfep-' . $dfepId;
                                $totalInGroup = count($group['institutions']);
                                $totalInscrits = array_sum(array_column($group['institutions'], 'total_inscrits'));
                                $totalSections = array_sum(array_column($group['institutions'], 'nb_sections'));
                            ?>
                                {{-- DFEP Header --}}
                                <tr class="dfep-header-row" onclick="toggleGroup('<?= $groupId ?>', this)" data-group="<?= $groupId ?>">
                                    <td colspan="6">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="dfep-toggle-icon"><i class="fa-solid fa-chevron-down"></i></span>
                                            <i class="fa-solid fa-building-columns fa-lg opacity-75"></i>
                                            <div class="flex-grow-1">
                                                <span class="fw-bold"><?= htmlspecialchars($group['dfep_nom']) ?></span>
                                                <?php if ($group['wilaya_nom']): ?>
                                                    <span class="ms-2 opacity-75" style="font-size:.8rem;">
                                                        <i class="fa-solid fa-location-dot me-1"></i>ولاية <?= htmlspecialchars($group['wilaya_nom']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 ms-auto no-print">
                                                <?php foreach ($typeSummary as $chip): ?>
                                                    <span style="background:rgba(255,255,255,0.2);color:#fff;font-size:.72rem;padding:2px 8px;border-radius:20px;">
                                                        <?= htmlspecialchars($chip) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <span style="background:rgba(255,255,255,0.35);color:#fff;font-size:.78rem;padding:2px 10px;border-radius:20px;font-weight:700;">
                                                    المجموع: <?= $totalInGroup ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Nature sub-groups --}}
                                <?php foreach ($byNature as $nat => $insts):
                                    $cfg = $natureConfig[$nat] ?? ['label' => "نوع {$nat}", 'badge_class' => 'badge-branch', 'icon' => 'fa-circle', 'color' => '#adb5bd'];
                                    $natInscrits = array_sum(array_column($insts, 'total_inscrits'));
                                    $natSections = array_sum(array_column($insts, 'nb_sections'));
                                ?>
                                    {{-- Nature sub-header --}}
                                    <tr class="nature-subheader group-row <?= $groupId ?>" data-group="<?= $groupId ?>">
                                        <td class="" colspan="6">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <i class="fa-solid <?= $cfg['icon'] ?>" style="color:<?= $cfg['color'] ?>;width:14px;"></i>
                                                <span><?= htmlspecialchars($insts[0]['nature_nom'] ?? $cfg['label']) ?></span>
                                                <span class="ms-1 <?= $cfg['badge_class'] ?>"><?= $cfg['label'] ?></span>
                                                <span class="ms-2 text-muted" style="font-size:.78rem;">(<?= count($insts) ?> مؤسسة)</span>
                                                <?php if ($natInscrits > 0): ?>
                                                    <span class="ms-auto text-success fw-bold" style="font-size:.78rem;"><?= number_format($natInscrits) ?> متربص</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Institutions under this nature type --}}
                                    <?php foreach ($insts as $item):
                                        $inscrits = (int)($item['total_inscrits'] ?? 0);
                                        $sections = (int)($item['nb_sections'] ?? 0);
                                        $capacity = (int)($item['total_capacite'] ?? 0);
                                    ?>
                                        <tr class="institution-row group-row <?= $groupId ?>" data-group="<?= $groupId ?>">
                                            <td class="institution-indent">
                                                <div class="fw-semibold text-dark" style="font-size:.84rem;"><?= htmlspecialchars($item['nom_ar']) ?></div>
                                                <?php if (!empty($item['nom_fr'])): ?>
                                                    <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($item['nom_fr']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="<?= $cfg['badge_class'] ?>"><?= $cfg['label'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($inscrits > 0): ?>
                                                    <span class="fw-bold text-success"><?= number_format($inscrits) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($sections > 0): ?>
                                                    <span class="fw-semibold text-primary"><?= $sections ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-muted" style="font-size:.8rem;">
                                                <?= $capacity > 0 ? number_format($capacity) : '—' ?>
                                            </td>
                                            <td class="text-center pe-4">
                                                <span class="badge bg-success rounded-pill px-2 py-1" style="font-size:.68rem;">منشطة</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleGroup(groupId, headerRow) {
    const rows = document.querySelectorAll('.' + groupId);
    const icon = headerRow.querySelector('.dfep-toggle-icon');
    const isHidden = rows.length > 0 && rows[0].style.display === 'none';
    rows.forEach(r => r.style.display = isHidden ? '' : 'none');
    if (icon) icon.style.transform = isHidden ? '' : 'rotate(-90deg)';
}
function expandAll() {
    document.querySelectorAll('.group-row').forEach(r => r.style.display = '');
    document.querySelectorAll('.dfep-toggle-icon').forEach(i => i.style.transform = '');
}
function collapseAll() {
    document.querySelectorAll('.group-row').forEach(r => r.style.display = 'none');
    document.querySelectorAll('.dfep-toggle-icon').forEach(i => i.style.transform = 'rotate(-90deg)');
}
</script>

@endsection
