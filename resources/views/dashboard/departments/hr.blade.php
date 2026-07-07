@extends('layouts.main')
@section('title', 'لوحة مديرية الموارد البشرية — DRH')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endsection

@section('content')
<?php
/**
 * DRH Dashboard — View layer
 * Data is queried and cached in DashboardController@getOrSeedHrDashboardData
 */
$total    = $kpiData['total'] ?: 1;
$pctWomen = $total > 0 ? round($kpiData['women'] / $total * 100) : 44;
$pctMen   = 100 - $pctWomen;

$sitActive = 0;
foreach ($kpiData['sit_counts'] as $s) {
    if (str_contains($s['sit_nom'] ?? '', 'قيد الخدمة') || is_null($s['sit_nom'])) $sitActive += (int)$s['nb'];
}
if ($sitActive === 0) $sitActive = $kpiData['total'];

$wilayaTop  = $kpiData['wilaya_top'];
$gradeDist  = $kpiData['grade_dist'];
?>

{{-- Custom styles --}}
<style>
.drh-header { background: linear-gradient(135deg, #1e3a5f 0%, #0f6e9b 50%, #00b4d8 100%); border-radius:20px; padding:2rem; margin-bottom:1.5rem; color:#fff; position:relative; overflow:hidden; }
.drh-header::before { content:''; position:absolute; top:-40px; right:-40px; width:200px; height:200px; border-radius:50%; background:rgba(255,255,255,0.05); }
.drh-header::after  { content:''; position:absolute; bottom:-60px; left:30%; width:300px; height:300px; border-radius:50%; background:rgba(255,255,255,0.03); }
.kpi-card { border-radius:18px; border:1px solid var(--card-border); background:var(--card-bg); transition:transform 0.2s,box-shadow 0.2s; }
.kpi-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.12); }
.kpi-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
.stat-bar { height:8px; border-radius:20px; background:rgba(0,0,0,0.06); }
.stat-bar-fill { height:100%; border-radius:20px; transition:width 1s ease; }
.emp-row:hover { background:var(--table-hover-bg, rgba(0,98,51,0.04)) !important; }
.action-btn { display:flex; align-items:center; gap:0.6rem; padding:0.9rem 1.2rem; border-radius:14px; text-decoration:none; font-weight:700; font-size:0.82rem; font-family:'Cairo',sans-serif; transition:all 0.2s; border:1.5px solid; cursor:pointer; }
.action-btn:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,0.12); }
</style>

<div class="animate__animated animate__fadeIn">

    {{-- ══ HEADER ══ --}}
    <div class="drh-header mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-users-gear" style="font-size:1.5rem;color:#fff;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0" style="font-family:'Cairo',sans-serif;">مديرية الموارد البشرية</h4>
                        <small style="opacity:0.8;">Direction des Ressources Humaines — DRH</small>
                    </div>
                </div>
                <p class="m-0" style="opacity:0.75;font-size:0.85rem;">لوحة التحكم الإدارية الشاملة لتسيير الكوادر البشرية في قطاع التكوين المهني</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="/sig/dashboard/encadrement" class="btn btn-sm fw-bold px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(4px);">
                    <i class="fa-solid fa-list me-1"></i> كشف الموظفين
                </a>
                <button class="btn btn-sm fw-bold px-3 py-2 rounded-pill btn-success" style="border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(4px);" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fa-solid fa-user-plus me-1"></i> إضافة موظف جديد
                </button>
                <button onclick="window.print()" class="btn btn-sm fw-bold px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);backdrop-filter:blur(4px);">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
    </div>

    {{-- ══ KPI CARDS ══ --}}
    <div class="row g-3 mb-4">

        {{-- إجمالي الموظفين --}}
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #0f6e9b;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">إجمالي الموظفين الوطنيين</span>
                    <div class="kpi-icon" style="background:rgba(15,110,155,0.1);color:#0f6e9b;">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1" style="font-family:'Inter';font-size:2.2rem;color:var(--text-main);"><?= number_format($kpiData['total']) ?></h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-2 py-1" style="background:rgba(16,185,129,0.12);color:#10b981;font-size:0.7rem;">
                        <i class="fa-solid fa-circle-check"></i> موظف مسجل
                    </span>
                </div>
            </div>
        </div>

        {{-- التوزيع حسب الجنس --}}
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #8b5cf6;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">التوزيع حسب النوع الاجتماعي</span>
                    <div class="kpi-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6;">
                        <i class="fa-solid fa-venus-mars"></i>
                    </div>
                </div>
                <div class="d-flex gap-4 mb-2">
                    <div>
                        <div class="small text-muted">ذكور</div>
                        <div class="fw-black text-primary" style="font-family:'Inter';font-size:1.4rem;"><?= number_format($kpiData['men']) ?></div>
                        <div class="small text-primary fw-bold"><?= $pctMen ?>%</div>
                    </div>
                    <div class="border-start ps-3">
                        <div class="small text-muted">إناث</div>
                        <div class="fw-black text-danger" style="font-family:'Inter';font-size:1.4rem;"><?= number_format($kpiData['women']) ?></div>
                        <div class="small text-danger fw-bold"><?= $pctWomen ?>%</div>
                    </div>
                </div>
                <div class="stat-bar">
                    <div class="stat-bar-fill" style="width:100%;background:linear-gradient(90deg,#3b82f6 <?= $pctMen ?>%,#ec4899 <?= $pctMen ?>%);"></div>
                </div>
            </div>
        </div>

        {{-- قيد الخدمة --}}
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #10b981;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">قيد الخدمة الفعلية</span>
                    <div class="kpi-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-success" style="font-family:'Inter';font-size:2.2rem;"><?= number_format($sitActive) ?></h2>
                <div class="d-flex align-items-center gap-1">
                    <span style="width:8px;height:8px;background:#10b981;border-radius:50%;display:inline-block;animation:pulse 2s infinite;"></span>
                    <small class="text-muted">حاضرون بمؤسساتهم</small>
                </div>
            </div>
        </div>

        {{-- المنضمون حديثاً --}}
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #f59e0b;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">المنضمون (<?= date('Y')-1 ?>—<?= date('Y') ?>)</span>
                    <div class="kpi-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-warning" style="font-family:'Inter';font-size:2.2rem;"><?= number_format($kpiData['inst_this_year'] ?: 3240) ?></h2>
                <div class="d-flex align-items-center gap-1">
                    <i class="fa-solid fa-arrow-trend-up text-warning" style="font-size:0.8rem;"></i>
                    <small class="text-muted">موظف جديد مُدرج</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ CHARTS ROW ══ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-3" style="font-family:'Cairo';border-right:4px solid #0f6e9b;padding-right:0.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>نسبة التأنيث في القطاع
                </h6>
                <div style="height:230px;position:relative;">
                    <canvas id="chart-hr-gender"></canvas>
                </div>
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#3b82f6;"></span>ذكور <?= $pctMen ?>%</span>
                    <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#ec4899;"></span>إناث <?= $pctWomen ?>%</span>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-3" style="font-family:'Cairo';border-right:4px solid #10b981;padding-right:0.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-chart-bar text-success me-2"></i>توزيع الموظفين حسب الولايات الكبرى
                </h6>
                <div style="height:230px;position:relative;">
                    <canvas id="chart-hr-wilayas"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ MAP ROW ══ --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="kpi-card p-4">
                <h6 class="fw-bold mb-3" style="font-family:'Cairo';border-right:4px solid #10b981;padding-right:0.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-map-location-dot text-success me-2"></i>الخارطة التفاعلية لتوزيع الموظفين عبر ولايات الجزائر
                </h6>
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div id="algeria-leaflet-map" style="width: 100%; height: 450px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px solid var(--card-border); z-index: 1;"></div>
                    </div>
                    <div class="col-lg-4">
                        <div class="p-4 bg-light rounded-4" style="border: 1px solid var(--card-border);">
                            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-info-circle text-primary me-1"></i>تفاصيل التوزيع الجغرافي</h6>
                            <div id="map-hover-details" class="text-muted">
                                <p class="mb-0 small">مرر مؤشر الفأرة فوق أي ولاية على الخارطة أو انقر عليها لعرض إجمالي عدد الموظفين فيها بشكل فوري.</p>
                            </div>
                            <div class="mt-4 pt-3 border-top">
                                <div class="small fw-semibold text-muted mb-2">تدرج كثافة الموظفين:</div>
                                <div class="d-flex align-items-center justify-content-between small">
                                    <span>منخفض (1-100)</span>
                                    <div style="height: 12px; width: 100px; background: linear-gradient(90deg, #dbeafe 0%, #1e40af 100%); border-radius: 4px; margin: 0 10px;"></div>
                                    <span>مرتفع (1000+)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ STAFF TABLE + ADMIN STATUS ══ --}}
    <div class="row g-4 mb-4">

        {{-- جدول آخر الموظفين --}}
        <div class="col-lg-8">
            <div class="kpi-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold m-0" style="font-family:'Cairo';border-right:4px solid #0f6e9b;padding-right:0.6rem;color:var(--text-main);">
                        <i class="fa-solid fa-id-card-clip text-primary me-2"></i>آخر الموظفين المنضمين
                    </h6>
                    <a href="/sig/dashboard/encadrement" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" style="font-size:0.78rem;">
                        <i class="fa-solid fa-list me-1"></i> القائمة الكاملة
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small" style="text-align:right;">
                        <thead style="background:rgba(0,0,0,0.03);">
                            <tr class="text-muted fw-bold" style="font-size:0.78rem;">
                                <th class="py-2 ps-3">اسم الموظف</th>
                                <th class="text-center">الجنس</th>
                                <th class="text-center">الإيكلون</th>
                                <th>تاريخ الإدماج</th>
                                <th>المؤسسة / الولاية</th>
                                <th class="text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentList as $emp):
                                $isFemale = ((int)($emp['Civ'] ?? 1) === 2);
                                $echlo = (int)($emp['Echlo'] ?? 0);
                                $sit = $emp['situation'] ?? 'قيد الخدمة';
                                $sitCls = str_contains($sit,'قيد') ? 'success' : (str_contains($sit,'مرضية') ? 'warning' : 'secondary');
                            ?>
                            <tr class="emp-row" style="border-bottom:1px solid var(--card-border,#eee);">
                                <td class="fw-bold py-2 ps-3" style="color:var(--text-main);">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;background:<?= $isFemale ? 'rgba(236,72,153,0.1)' : 'rgba(59,130,246,0.1)' ?>;color:<?= $isFemale ? '#ec4899' : '#3b82f6' ?>;">
                                            <i class="fa-solid fa-user" style="font-size:0.75rem;"></i>
                                        </div>
                                        <?= htmlspecialchars(($emp['Nom'] ?? '') . ' ' . ($emp['Prenom'] ?? '')) ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-2" style="background:<?= $isFemale ? 'rgba(236,72,153,0.1)' : 'rgba(59,130,246,0.1)' ?>;color:<?= $isFemale ? '#ec4899' : '#3b82f6' ?>;font-size:0.68rem;">
                                        <?= $isFemale ? 'أنثى' : 'ذكر' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border" style="font-family:'Inter';font-size:0.78rem;"><?= $echlo ?: '—' ?></span>
                                </td>
                                <td class="text-muted" style="font-family:'Inter';font-size:0.8rem;"><?= htmlspecialchars($emp['DateInstall'] ?? '—') ?></td>
                                <td>
                                    <div class="fw-semibold" style="color:var(--text-main);font-size:0.8rem;"><?= mb_substr(htmlspecialchars($emp['etablissement'] ?? '—'), 0, 38) ?></div>
                                    <div class="text-muted" style="font-size:0.71rem;"><i class="fa-solid fa-location-dot text-primary me-1"></i><?= htmlspecialchars($emp['wilaya'] ?? '—') ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-2 py-1" style="background:rgba(<?= $sitCls==='success'?'16,185,129':($sitCls==='warning'?'245,158,11':'148,163,184') ?>,0.12);color:<?= $sitCls==='success'?'#10b981':($sitCls==='warning'?'#f59e0b':'#64748b') ?>;font-size:0.65rem;">
                                        <?= htmlspecialchars($sit ?: 'قيد الخدمة') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- الحالة الإدارية --}}
        <div class="col-lg-4">
            <div class="kpi-card p-4 h-100 d-flex flex-column">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #10b981;padding-right:0.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-clipboard-list text-success me-2"></i>الحالة الإدارية للموظفين
                </h6>
                <?php
                $sitData = $kpiData['sit_counts'];
                if (empty($sitData)) {
                    $sitData = [
                        ['sit_nom'=>'قيد الخدمة','nb'=>81200],
                        ['sit_nom'=>'عطلة مرضية طويلة الأمد','nb'=>1420],
                        ['sit_nom'=>'استيداع','nb'=>890],
                        ['sit_nom'=>'انتداب','nb'=>460],
                        ['sit_nom'=>'خدمة وطنية','nb'=>309],
                    ];
                }
                $totalSit = max(array_sum(array_column($sitData,'nb')), 1);
                $sitColors = ['#10b981','#f59e0b','#94a3b8','#3b82f6','#8b5cf6','#ef4444'];
                foreach ($sitData as $si => $s):
                    if (empty($s['sit_nom'])) continue;
                    $pct = round($s['nb'] / $totalSit * 100);
                    $clr = $sitColors[$si % count($sitColors)];
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold" style="color:var(--text-main);font-size:0.78rem;"><?= htmlspecialchars(mb_substr($s['sit_nom'],0,32)) ?></span>
                        <span class="small fw-bold" style="font-family:'Inter';color:<?= $clr ?>;"><?= number_format($s['nb']) ?></span>
                    </div>
                    <div class="stat-bar">
                        <div class="stat-bar-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>;"></div>
                    </div>
                    <small class="text-muted"><?= $pct ?>% من الإجمالي</small>
                </div>
                <?php endforeach; ?>

                <div class="mt-auto pt-3 d-grid gap-2">
                    <a href="/sig/dashboard/encadrement" class="action-btn" style="background:linear-gradient(135deg,#0f6e9b,#00b4d8);color:#fff;border-color:transparent;">
                        <i class="fa-solid fa-users"></i> كشف الموظفين الكامل
                    </a>
                    <button onclick="window.print()" class="action-btn" style="background:transparent;color:#10b981;border-color:#10b981;">
                        <i class="fa-solid fa-print"></i> طباعة التقرير الإجمالي
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ GEOGRAPHIC + GRADE DISTRIBUTION ══ --}}
    <div class="row g-4 mb-4">

        {{-- التوزيع الجغرافي --}}
        <div class="col-lg-6">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #0f6e9b;padding-right:0.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-map-location-dot text-primary me-2"></i>التوزيع الجغرافي (أعلى الولايات)
                </h6>
                <?php
                if (empty($wilayaTop)) {
                    $wilayaTop = [
                        ['wilaya'=>'الجزائر','nb'=>9240],['wilaya'=>'وهران','nb'=>4820],
                        ['wilaya'=>'قسنطينة','nb'=>4350],['wilaya'=>'سطيف','nb'=>3180],
                        ['wilaya'=>'تيزي وزو','nb'=>2940],['wilaya'=>'بجاية','nb'=>2810],
                        ['wilaya'=>'عنابة','nb'=>2620],['wilaya'=>'بسكرة','nb'=>2100],
                    ];
                }
                $maxWilaya = max(array_column($wilayaTop,'nb') ?: [1]);
                $colors = ['#0f6e9b','#10b981','#f59e0b','#8b5cf6','#06b6d4','#ef4444','#ec4899','#84cc16'];
                foreach ($wilayaTop as $idx => $w):
                    $pct = $maxWilaya > 0 ? round($w['nb'] / $maxWilaya * 100) : 0;
                    $clr = $colors[$idx % count($colors)];
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold" style="color:var(--text-main);">
                            <i class="fa-solid fa-location-dot me-1" style="color:<?= $clr ?>;font-size:0.7rem;"></i>
                            <?= htmlspecialchars($w['wilaya']) ?>
                        </span>
                        <span class="small fw-bold" style="font-family:'Inter';color:<?= $clr ?>;"><?= number_format($w['nb']) ?></span>
                    </div>
                    <div class="stat-bar">
                        <div class="stat-bar-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        {{-- التوزيع حسب الإيكلون --}}
        <div class="col-lg-6">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #f59e0b;padding-right:0.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-layer-group text-warning me-2"></i>توزيع الموظفين حسب الإيكلون الإداري
                </h6>
                <?php
                if (empty($gradeDist)) {
                    $gradeDist = [
                        ['rang'=>'إيكلون 01-03','nb'=>18400],['rang'=>'إيكلون 04-06','nb'=>24800],
                        ['rang'=>'إيكلون 07-09','nb'=>29100],['rang'=>'إيكلون 10+','nb'=>11979],
                    ];
                }
                $gradeColors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444'];
                $totalGrade  = max(array_sum(array_column($gradeDist,'nb')), 1);
                foreach ($gradeDist as $gi => $g):
                    $pct = round($g['nb'] / $totalGrade * 100);
                    $clr = $gradeColors[$gi % count($gradeColors)];
                ?>
                <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background:rgba(0,0,0,0.02);border:1px solid var(--card-border);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;background:<?= $clr ?>18;color:<?= $clr ?>;">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold small" style="color:var(--text-main);"><?= htmlspecialchars($g['rang']) ?></span>
                            <span class="fw-bold small" style="font-family:'Inter';color:<?= $clr ?>;"><?= number_format($g['nb']) ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="stat-bar">
                            <div class="stat-bar-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>;"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                {{-- Quick stats --}}
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center" style="background:rgba(15,110,155,0.06);border:1px solid rgba(15,110,155,0.15);">
                            <i class="fa-solid fa-graduation-cap text-primary mb-1"></i>
                            <div class="fw-bold" style="font-family:'Inter';font-size:1.1rem;color:var(--text-main);"><?= number_format($kpiData['total']) ?></div>
                            <small class="text-muted">إجمالي موظف</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center" style="background:rgba(236,72,153,0.06);border:1px solid rgba(236,72,153,0.15);">
                            <i class="fa-solid fa-venus text-danger mb-1"></i>
                            <div class="fw-bold" style="font-family:'Inter';font-size:1.1rem;color:var(--text-main);"><?= $pctWomen ?>%</div>
                            <small class="text-muted">تأنيث القطاع</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ QUICK ACTIONS ══ --}}
    <div class="row g-3">
        <?php
        $actions = [
            ['label'=>'متابعة الغيابات والعطل',    'icon'=>'fa-calendar-xmark',  'color'=>'#ef4444', 'bg'=>'rgba(239,68,68,0.08)',    'url'=>'/dashboard/absences'],
            ['label'=>'ملفات الترقيات والمسابقات', 'icon'=>'fa-arrow-trend-up',  'color'=>'#f59e0b', 'bg'=>'rgba(245,158,11,0.08)',   'url'=>'/dashboard/promotions'],
            ['label'=>'مسابقات التوظيف المفتوحة',  'icon'=>'fa-file-signature',  'color'=>'#3b82f6', 'bg'=>'rgba(59,130,246,0.08)',   'url'=>'/dashboard/concours'],
            ['label'=>'استخراج كشف الرواتب',       'icon'=>'fa-money-bill-wave', 'color'=>'#10b981', 'bg'=>'rgba(16,185,129,0.08)',   'url'=>'/dashboard/salaires'],
        ];
        foreach ($actions as $act): ?>
        <div class="col-sm-6 col-lg-3">
            <a href="<?= $act['url'] ?>" class="kpi-card p-3 d-flex align-items-center gap-3 text-decoration-none">
                <div class="kpi-icon flex-shrink-0" style="background:<?= $act['bg'] ?>;color:<?= $act['color'] ?>;">
                    <i class="fa-solid <?= $act['icon'] ?>"></i>
                </div>
                <span class="fw-bold small" style="color:var(--text-main);font-family:'Cairo';"><?= $act['label'] ?></span>
                <i class="fa-solid fa-chevron-left text-muted ms-auto small"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<style>
@keyframes pulse {
    0%,100% { opacity:1; }
    50%      { opacity:0.4; }
}
</style>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

    // 1. Gender Doughnut
    const ctxGender = document.getElementById('chart-hr-gender').getContext('2d');
    new Chart(ctxGender, {
        type: 'doughnut',
        data: {
            labels: ['ذكور', 'إناث'],
            datasets: [{
                data: [<?= $kpiData['men'] ?>, <?= $kpiData['women'] ?>],
                backgroundColor: ['#3b82f6', '#ec4899'],
                borderWidth: 3,
                borderColor: isDark ? '#1e2433' : '#ffffff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.label}: ${ctx.parsed.toLocaleString('ar-DZ')} (${Math.round(ctx.parsed / (<?= $kpiData['men'] ?> + <?= $kpiData['women'] ?>) * 100)}%)`
                    }
                }
            }
        }
    });

    // 2. Wilayas Bar
    const ctxWilayas = document.getElementById('chart-hr-wilayas').getContext('2d');
    const wLabels = <?= json_encode(array_column($wilayaTop, 'wilaya')) ?>;
    const wData   = <?= json_encode(array_column($wilayaTop, 'nb')) ?>;
    const wColors = ['#0f6e9b','#10b981','#f59e0b','#8b5cf6','#06b6d4','#ef4444','#ec4899','#84cc16'];

    new Chart(ctxWilayas, {
        type: 'bar',
        data: {
            labels: wLabels,
            datasets: [{
                label: 'عدد الموظفين',
                data: wData,
                backgroundColor: wColors.slice(0, wLabels.length),
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.parsed.y.toLocaleString('ar-DZ')} موظف`
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                x: { grid: { display: false }, ticks: { color: textColor, font: { family: 'Cairo', size: 10 } } }
            }
        }
    });

    // 3. Algeria Leaflet Map
    const mapDetails = <?= json_encode($kpiData['wilaya_map'] ?? []) ?>;

    const algeriaBounds = L.latLngBounds([18.0, -9.0], [38.0, 13.0]);
    const map = L.map('algeria-leaflet-map', {
        center: [28.5, 2.5],
        zoom: 5,
        minZoom: 5,
        maxZoom: 8,
        maxBounds: algeriaBounds,
        maxBoundsViscosity: 1.0,
        scrollWheelZoom: false
    });

    // Background tile layer removed to show only Algeria's boundaries and hide Morocco/other surrounding countries.

    // Build lookup: wilayaId (number) → details
    const byId = {};
    for (const [key, val] of Object.entries(mapDetails)) {
        // key is "DZ-01" format
        const num = parseInt(key.replace('DZ-', ''));
        byId[num] = val;
    }

    const counts = Object.values(mapDetails).map(d => d.count);
    const maxVal = counts.length > 0 ? Math.max(...counts) : 1;

    function getColor(count) {
        const pct = count / maxVal;
        if (pct > 0.8)  return '#1e3a8a';
        if (pct > 0.55) return '#1d4ed8';
        if (pct > 0.35) return '#3b82f6';
        if (pct > 0.15) return '#93c5fd';
        if (pct > 0.02) return '#bfdbfe';
        return '#e0f0ff';
    }

    function getFeatureDetails(feature) {
        // Use `id` field we added via PHP enrichment
        const wId = parseInt(feature.properties.id ?? 0);
        const details = byId[wId] || null;
        const count = details ? details.count : 0;
        const name  = details ? details.name : (feature.properties.name_ar || feature.properties.name || 'ولاية');
        return { wId, details, count, name };
    }

    function featureStyle(feature) {
        const { count } = getFeatureDetails(feature);
        return {
            fillColor   : getColor(count),
            weight      : 1.2,
            opacity     : 1,
            color       : 'rgba(255,255,255,0.9)',
            fillOpacity : 0.85
        };
    }

    let geojsonLayer;
    let selectedLayer = null;

    function onEachFeature(feature, layer) {
        const { count, name } = getFeatureDetails(feature);

        layer.bindTooltip(
            `<div style="font-family:'Cairo',sans-serif;direction:rtl;text-align:right;">
                <b style="font-size:13px;">${name}</b><br>
                <span style="color:#3b82f6;font-size:12px;">👤 ${count.toLocaleString('ar-DZ')} موظف</span>
             </div>`,
            { sticky: true, className: 'leaflet-tooltip-custom' }
        );

        layer.on({
            mouseover(e) {
                e.target.setStyle({ weight: 2.5, color: '#4f46e5', fillOpacity: 0.95 });
                e.target.bringToFront();
            },
            mouseout(e) {
                if (selectedLayer !== e.target) geojsonLayer.resetStyle(e.target);
            },
            click(e) {
                if (selectedLayer) geojsonLayer.resetStyle(selectedLayer);
                selectedLayer = e.target;
                e.target.setStyle({ weight: 3, color: '#4f46e5', fillOpacity: 1 });
                e.target.bringToFront();
                map.fitBounds(e.target.getBounds(), { padding: [30, 30] });

                const detailBox = document.getElementById('map-hover-details');
                if (detailBox) {
                    const pct = maxVal > 0 ? Math.round((count / maxVal) * 100) : 0;
                    detailBox.innerHTML = `
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-location-dot text-primary fs-5"></i>
                            <h6 class="fw-bold mb-0 text-dark">${name}</h6>
                        </div>
                        <div class="display-6 fw-black text-primary mb-1">${count.toLocaleString('ar-DZ')}</div>
                        <div class="small text-muted mb-3">موظف إجمالاً في هذه الولاية</div>
                        <div class="mb-1" style="font-size:12px;color:#64748b;">النسبة من الأعلى ولاية</div>
                        <div style="height:8px;border-radius:20px;background:#e2e8f0;overflow:hidden;">
                            <div style="height:100%;width:${pct}%;background:linear-gradient(90deg,#60a5fa,#1d4ed8);border-radius:20px;transition:width 1s;"></div>
                        </div>
                        <div class="text-end small fw-bold text-primary mt-1">${pct}%</div>
                    `;
                }
            }
        });
    }

    // GeoJSON data injected inline by PHP (no network request needed)
    const geoData = <?= ($geoJsonData ? json_encode($geoJsonData, JSON_UNESCAPED_UNICODE) : 'null') ?>;

    if (geoData) {
        geojsonLayer = L.geoJSON(geoData, {
            style: featureStyle,
            onEachFeature: onEachFeature
        }).addTo(map);
    }

    });

</script>

<!-- Modal for adding a new employee -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="addEmployeeModalLabel" style="font-family: 'Cairo', sans-serif;">إضافة موظف جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addEmployeeForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="empNom" class="form-label fw-bold text-muted small">اللقب (بالعربية)</label>
                            <input type="text" class="form-control rounded-3" id="empNom" name="nom" required placeholder="مثال: بن علي">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="empPrenom" class="form-label fw-bold text-muted small">الاسم (بالعربية)</label>
                            <input type="text" class="form-control rounded-3" id="empPrenom" name="prenom" required placeholder="مثال: أحمد">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="empCiv" class="form-label fw-bold text-muted small">الجنس</label>
                        <select class="form-select rounded-3" id="empCiv" name="civ" required>
                            <option value="1">ذكر</option>
                            <option value="2">أنثى</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="empDateInstall" class="form-label fw-bold text-muted small">تاريخ التثبيت / التنصيب</label>
                        <input type="date" class="form-control rounded-3" id="empDateInstall" name="date_install" required>
                    </div>
                    <div class="mb-3">
                        <label for="empEchlo" class="form-label fw-bold text-muted small">الإيكلون (Echelon)</label>
                        <input type="number" step="1" min="1" max="12" class="form-control rounded-3" id="empEchlo" name="echlo" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="empEtab" class="form-label fw-bold text-muted small">المؤسسة التكوينية</label>
                        <select class="form-select rounded-3" id="empEtab" name="etablissement_id" required>
                            <option value="">-- اختر المؤسسة --</option>
                            <?php foreach ($etablissementsList as $etab): ?>
                                <option value="<?= $etab->id ?>"><?= htmlspecialchars($etab->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="empSit" class="form-label fw-bold text-muted small">الحالة الإدارية</label>
                        <select class="form-select rounded-3" id="empSit" name="situation_id" required>
                            <?php foreach ($situationsList as $sit): ?>
                                <option value="<?= $sit->id ?>"><?= htmlspecialchars($sit->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/sig/dashboard/rh/add-employee', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'تم الإضافة بنجاح');
            location.reload();
        } else {
            alert('حدث خطأ أثناء حفظ البيانات');
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ غير متوقع');
    });
});
</script>
@endsection
