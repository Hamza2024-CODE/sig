@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php 
/**
 * @var array $stats
 * @var array $dispositifs
 * @var array $filieres
 * @var array $offres_detail
 * @var string $wilaya_name
 * @var array $specialites
 * @var array $etablissements
 * @var array $sessions
 */
$role_code = session('user')['role_code'] ?? '';
$is_admin    = ($role_code === 'admin');
$is_central  = in_array($role_code, [
    'central', 'inspecteur_general', 'secretaire_general', 'chef_cabinet',
    'inspecteur_central', 'directeur_etudes', 'charge_etudes', 'attache_cabinet',
    'dir_finance', 'dir_rh', 'dir_plan', 'dir_coop', 'dir_it', 'dir_exam', 'dir_trak', 'dir_edu', 'dir_org'
]) || $is_admin;
$is_wilaya   = in_array($role_code, ['dfep', 'apc']);
// 'etablissement' = مراكز/معاهد التكوين المهني (يشمل الخاصة والعامة)
$is_etab     = in_array($role_code, ['etablissement', 'directeur', 'formateur']);
?>
<style>
/* Sleek custom scrollbars for table responsiveness */
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}
.table-responsive::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 8px;
}
.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 8px;
    border: 2px solid #f8fafc;
}
.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Premium dashboard designs */
.card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08) !important;
}

/* Statistics Card Redesign */
.kpi-card {
    position: relative;
    overflow: hidden;
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    z-index: 5;
}
.kpi-inscrits::before { background: #3b82f6 !important; }
.kpi-actifs::before { background: #10b981 !important; }
.kpi-couverture::before { background: #f59e0b !important; }
.kpi-laureats::before { background: #8b5cf6 !important; }

/* Flat cohesive premium table layout */
#offresTable {
    border-collapse: collapse !important;
    width: 100% !important;
    min-width: 100% !important;
}
#offresTable thead th {
    background-color: #f1f5f9 !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.78rem !important;
    border-bottom: 2px solid #e2e8f0 !important;
    padding: 14px 10px !important;
    text-align: center !important;
    vertical-align: middle !important;
}
#offresTable tbody tr {
    border-bottom: 1px solid #e2e8f0 !important;
    transition: all 0.15s ease-in-out !important;
}
#offresTable tbody tr:hover {
    background-color: #f8fafc !important;
}
#offresTable tbody td {
    padding: 12px 10px !important;
    font-size: 0.85rem !important;
    color: #334155 !important;
    border-bottom: 1px solid #e2e8f0 !important;
    text-align: center !important;
    vertical-align: middle !important;
}

/* Freeze/Sticky first columns for better readability */
#offresTable th:first-child, #offresTable td:first-child {
    position: sticky !important;
    left: 0 !important;
    background-color: #ffffff !important;
    z-index: 10 !important;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05) !important;
    text-align: right !important;
}
#offresTable th:nth-child(2), #offresTable td:nth-child(2) {
    position: sticky !important;
    left: 110px !important;
    background-color: #ffffff !important;
    z-index: 10 !important;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05) !important;
    text-align: right !important;
}
#offresTable tbody tr:hover td:first-child,
#offresTable tbody tr:hover td:nth-child(2) {
    background-color: #f8fafc !important;
}

/* Interactive elements */
.form-control, .form-select {
    border-radius: 30px !important;
    border: 1px solid #cbd5e1 !important;
    transition: all 0.2s ease-in-out;
}
.form-control:focus, .form-select:focus {
    border-color: #643edb !important;
    box-shadow: 0 0 0 4px rgba(100, 62, 219, 0.15) !important;
}
</style>
<div class="animate__animated animate__fadeIn">
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;"><i class="fa-solid fa-briefcase text-primary me-2"></i> عروض التكوين / Offres de Formation</h3>
            <p class="text-muted mb-0 small">دورة فيفري 2026 - مديرية التكوين والتعليم المهنيين لولاية <?= htmlspecialchars($wilaya_name ?? 'سعيدة') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/dashboard/offres/print" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm"><i class="fa-solid fa-print me-2"></i> استخراج مخطط التكوين</a>
            <?php if ($is_etab): ?>
                <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;" data-bs-toggle="modal" data-bs-target="#addOffreModal"><i class="fa-solid fa-plus me-2"></i> إضافة عرض جديد</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert triggers -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #e6f7ed; color: #006233;">
            <i class="fa-solid fa-circle-check me-2"></i> <?= session('flash_success');  ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #fdf2f2; color: #9b1c1c;">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= session('flash_error');  ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Bento Grid High-Level Statistics -->
    <div class="row g-4 mb-4">
        <!-- Main Highlight: Total Offers -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.95);">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:48px; height:48px; background:rgba(72,43,143,0.1); color:#482b8f; flex-shrink:0; font-size: 1.2rem;">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <span class="badge rounded-pill" style="background:#f3e8ff; color:#7c3aed; font-size:.65rem; font-weight: 700;">كل الدورات</span>
                    </div>
                    
                    <h6 class="text-muted fw-bold mb-1" style="font-size:.85rem;">إجمالي عروض التكوين المبرمجة</h6>
                    <h1 class="display-4 fw-bold mb-1 text-primary" style="color: #482b8f !important;"><?= number_format($stats['total_offres']) ?></h1>
                    <p class="text-muted small mb-3">عرض تكوين مبرمج في قاعدة البيانات</p>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small"><i class="fa-solid fa-chair me-1"></i>المقاعد المتاحة (nbrPrevision)</span>
                        <span class="fw-bold fs-5 text-dark" dir="ltr"><?= number_format($stats['total_places'], 0, ',', ' ') ?></span>
                    </div>
                    <div class="progress mb-2" style="height: 6px; background-color: rgba(72,43,143,0.1);">
                        <div class="progress-bar" role="progressbar" style="width: 100%; background-color: #482b8f;"></div>
                    </div>
                    <div class="text-muted mb-3" style="font-size:.7rem;">
                        <i class="fa-solid fa-circle-info me-1 text-primary opacity-75"></i>
                        إجمالي المقاعد المخصصة في جميع عروض التكوين المدخلة
                    </div>

                    <!-- Breakdown by Year (2024 - 2026) -->
                    <div class="mt-2 pt-3 border-top" style="border-top: 1px dashed #e2e8f0 !important;">
                        <h6 class="fw-bold mb-2 small" style="color: #64748b !important;"><i class="fa-solid fa-calendar-days me-1" style="color: #482b8f !important;"></i>توزيع العروض والمقاعد المتاحة حسب السنة:</h6>
                        <div class="d-flex flex-column gap-2">
                            <?php if (!empty($stats['by_year'])): ?>
                                <?php foreach ($stats['by_year'] as $yr): ?>
                                    <?php if ($yr['year'] >= 2024 && $yr['year'] <= 2026): ?>
                                        <div class="d-flex justify-content-between align-items-center small">
                                            <span class="fw-bold" style="color: #1e293b !important; font-weight: 700 !important;"><?= htmlspecialchars($yr['year']) ?></span>
                                            <span>
                                                <span class="badge bg-primary-subtle text-primary bold px-2 py-1 me-2" style="background-color: rgba(72,43,143,0.1); color: #482b8f !important; font-size: 0.7rem; font-weight: 700; border-radius: 6px; display: inline-block;"><?= number_format($yr['count_offres']) ?> عرض</span>
                                                <span class="fw-bold" dir="ltr" style="color: #64748b !important; font-weight: 600 !important;"><?= number_format($yr['count_places'], 0, ',', ' ') ?> مقعد</span>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="small text-center" style="color: #64748b !important;">لا توجد بيانات متوفرة</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="col-12 col-xl-8">
            <div class="row g-4">

                <!-- Inscrits -->
                <div class="col-md-6 col-lg-3">
                    <div class="card kpi-card kpi-inscrits border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px; height:40px; background:rgba(59,130,246,0.1); color:#3b82f6; flex-shrink:0;">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <span class="badge rounded-pill" style="background:#e8f0fe; color:#1a73e8; font-size:.6rem;">آخر 5 دورات</span>
                            </div>
                            <h6 class="text-muted fw-bold mb-1" style="font-size:.8rem;">إجمالي المسجلين</h6>
                            <h3 class="fw-bold mb-0 text-dark" dir="ltr"><?= number_format($stats['total_inscrits'], 0, ',', ' ') ?></h3>
                            <small class="text-primary fw-bold mb-2">منهم <span dir="ltr"><?= number_format($stats['inscrits_femmes'], 0, ',', ' ') ?></span> إناث
                                <?php if ($stats['total_inscrits'] > 0): ?>
                                <span class="text-muted fw-normal">(<?= round(($stats['inscrits_femmes'] / $stats['total_inscrits']) * 100) ?>%)</span>
                                <?php endif; ?>
                            </small>
                            <div class="mt-auto pt-2 border-top" style="font-size:.65rem; color:#6c757d; line-height:1.4;">
                                <i class="fa-solid fa-circle-info me-1 text-primary opacity-75"></i>
                                المرشحون المسجلون في العروض خلال الدورات 2024-2026
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actifs -->
                <div class="col-md-6 col-lg-3">
                    <div class="card kpi-card kpi-actifs border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px; height:40px; background:rgba(16,185,129,0.1); color:#10b981; flex-shrink:0;">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <span class="badge rounded-pill" style="background:#e6f4ea; color:#137333; font-size:.6rem;">لم يتخرجوا بعد</span>
                            </div>
                            <h6 class="text-muted fw-bold mb-1" style="font-size:.8rem;">الطلاب النشطون</h6>
                            <h3 class="fw-bold mb-0 text-dark" dir="ltr"><?= number_format($stats['total_actifs'] ?? $stats['total_diplomes'] ?? 0, 0, ',', ' ') ?></h3>
                            <small class="text-success fw-bold mb-2">منهم <span dir="ltr"><?= number_format($stats['actifs_femmes'] ?? $stats['diplomes_femmes'] ?? 0, 0, ',', ' ') ?></span> إناث</small>
                            <div class="mt-auto pt-2 border-top" style="font-size:.65rem; color:#6c757d; line-height:1.4;">
                                <i class="fa-solid fa-circle-info me-1 text-success opacity-75"></i>
                                مسجلون في أقسام نشطة ولم يُسجَّل تخرجهم بعد (apprenant - apprenant_fin)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Taux Couverture -->
                <div class="col-md-6 col-lg-3">
                    <div class="card kpi-card kpi-couverture border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px; height:40px; background:rgba(245,158,11,0.1); color:#f59e0b; flex-shrink:0;">
                                    <i class="fa-solid fa-bullseye"></i>
                                </div>
                                <span class="badge rounded-pill" style="background:#fff8e1; color:#f59e0b; font-size:.6rem;">نسبة الاستيعاب</span>
                            </div>
                            <h6 class="text-muted fw-bold mb-1" style="font-size:.8rem;">نسبة التغطية</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $stats['taux_inscrits_prevu'] ?>%</h3>
                            <small class="text-warning fw-bold mb-2">من الطاقة الاستيعابية الإجمالية</small>
                            <div class="progress mb-1" style="height:4px;">
                                <div class="progress-bar bg-warning" style="width:<?= min(100, $stats['taux_inscrits_prevu']) ?>%;"></div>
                            </div>
                            <div class="mt-auto pt-2 border-top" style="font-size:.65rem; color:#6c757d; line-height:1.4;">
                                <i class="fa-solid fa-circle-info me-1 text-warning opacity-75"></i>
                                (المسجلون ÷ المقاعد المتاحة) × 100 — آخر 5 دورات
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Taux Activité -->
                <div class="col-md-6 col-lg-3">
                    <div class="card kpi-card kpi-laureats border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.95);">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px; height:40px; background:rgba(139,92,246,0.1); color:#8b5cf6; flex-shrink:0;">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <span class="badge rounded-pill" style="background:#f3e8ff; color:#7c3aed; font-size:.6rem;">معدل النشاط</span>
                            </div>
                            <h6 class="text-muted fw-bold mb-1" style="font-size:.8rem;">نسبة النشاط</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $stats['taux_actifs_prevu'] ?? $stats['taux_diplomes_prevu'] ?>%</h3>
                            <small class="fw-bold mb-2" style="color:#8b5cf6;">معدل الطلاب النشطين / المقاعد</small>
                            <div class="progress mb-1" style="height:4px;">
                                <div class="progress-bar" style="width:<?= min(100, $stats['taux_actifs_prevu'] ?? $stats['taux_diplomes_prevu'] ?? 0) ?>%; background:#8b5cf6;"></div>
                            </div>
                            <div class="mt-auto pt-2 border-top" style="font-size:.65rem; color:#6c757d; line-height:1.4;">
                                <i class="fa-solid fa-circle-info me-1 opacity-75" style="color:#8b5cf6;"></i>
                                (النشطون ÷ المقاعد المتاحة) × 100 — آخر 5 دورات
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <div class="row g-4 mb-4">
        <!-- Distribution by Dispositif -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-layer-group text-primary me-2"></i> التوزيع حسب الجهاز (Dispositifs)</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="text-muted small" style="border-bottom: 1px dashed #e2e8f0;">
                                <tr>
                                    <th>الجهاز</th>
                                    <th class="text-center">العروض</th>
                                    <th class="text-center">المقاعد</th>
                                    <th class="text-end">المسجلين</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hasDispositifs = false;
                                foreach($dispositifs as $d) {
                                    if ($d['inscrits'] > 0) $hasDispositifs = true;
                                }
                                if(!$hasDispositifs): ?>
                                    <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات متاحة</td></tr>
                                <?php else: ?>
                                    <?php foreach($dispositifs as $d): 
                                        if ($d['inscrits'] == 0) continue;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= $d['nom_ar'] ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= $d['nom_fr'] ?></div>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?= $d['count'] ?></td>
                                        <td class="text-center fw-bold text-muted"><?= $d['places'] ?></td>
                                        <td class="text-end">
                                            <span class="badge rounded-pill <?= $d['inscrits'] > 0 ? 'bg-success-subtle text-success' : 'bg-secondary' ?>" style="font-size: 0.8rem; padding: 0.4em 0.8em;">
                                                <?= $d['inscrits'] ?> مسجل
                                            </span>
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

        <!-- Distribution by Filiere -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-shapes text-primary me-2"></i> التوزيع حسب الشعبة (Filières)</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="text-muted small" style="border-bottom: 1px dashed #e2e8f0;">
                                <tr>
                                    <th>الشعبة المهنية</th>
                                    <th class="text-center">العروض</th>
                                    <th class="text-center">المقاعد</th>
                                    <th class="text-end">التغطية</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hasFilieres = false;
                                foreach($filieres as $f) {
                                    if ($f['inscrits'] > 0) $hasFilieres = true;
                                }
                                if(!$hasFilieres): ?>
                                    <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات متاحة</td></tr>
                                <?php else: ?>
                                    <?php foreach($filieres as $f): 
                                        if ($f['inscrits'] == 0) continue;
                                        $percent = ($f['inscrits'] / max(1, $f['places'])) * 100;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= $f['nom_ar'] ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= $f['nom_fr'] ?></div>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?= $f['count'] ?></td>
                                        <td class="text-center fw-bold text-muted"><?= $f['places'] ?></td>
                                        <td class="text-end" style="width: 120px;">
                                            <div class="d-flex justify-content-between mb-1" style="font-size:0.75rem; font-weight:700;">
                                                <span class="text-muted"><?= $f['inscrits'] ?></span>
                                                <span class="<?= $percent > 80 ? 'text-success' : 'text-warning' ?>"><?= round($percent) ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar <?= $percent > 80 ? 'bg-success' : 'bg-warning' ?>" role="progressbar" style="width: <?= min(100, $percent) ?>%;"></div>
                                            </div>
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
    </div>


    <!-- ===== TRAINEE STATISTICS SECTION ===== -->
    <?php $ts = $trainee_stats ?? []; ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print" id="traineeStatsSection">
        <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-users-line text-primary me-2"></i>
                إحصائيات المتربصين والطلاب
                <span class="badge bg-primary-subtle text-primary rounded-pill ms-2 fw-normal" style="font-size:.75rem;">بيانات حية</span>
            </h5>
            <button class="btn btn-sm btn-link text-muted p-0" onclick="document.getElementById('traineeStatsBody').classList.toggle('d-none')" title="إخفاء / إظهار">
                <i class="fa-solid fa-chevron-up"></i>
            </button>
        </div>
        <div class="card-body px-4 pb-4" id="traineeStatsBody">

            <!-- KPI Cards Row -->
            <div class="row g-3 mb-4">
                <!-- Total Actifs -->
                <div class="col-6 col-md-3">
                    <div class="rounded-4 p-3 h-100 text-white d-flex flex-column justify-content-between"
                         style="background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="bg-white bg-opacity-25 rounded-3 p-2"><i class="fa-solid fa-user-check fa-lg"></i></div>
                            <span class="badge bg-white text-primary fw-bold small rounded-pill">نشطون</span>
                        </div>
                        <div>
                            <div class="fw-bold mb-0" style="font-size:1.5rem; letter-spacing:-.5px;"><?= number_format($ts['total_actifs'] ?? 0) ?></div>
                            <div class="small opacity-75">الطلاب النشطين</div>
                            <div class="small mt-1 opacity-85">
                                <i class="fa-solid fa-venus me-1"></i>
                                <?= number_format($ts['actifs_femmes'] ?? 0) ?> إناث
                                <?php if (($ts['total_actifs'] ?? 0) > 0): ?>
                                    (<?= round(($ts['actifs_femmes'] / $ts['total_actifs']) * 100) ?>%)
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Inscrits -->
                <div class="col-6 col-md-3">
                    <div class="rounded-4 p-3 h-100 text-white d-flex flex-column justify-content-between"
                         style="background: linear-gradient(135deg, #00897b 0%, #00695c 100%);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="bg-white bg-opacity-25 rounded-3 p-2"><i class="fa-solid fa-user-graduate fa-lg"></i></div>
                            <span class="badge bg-white text-success fw-bold small rounded-pill">مسجلون</span>
                        </div>
                        <div>
                            <div class="fw-bold mb-0" style="font-size:1.5rem; letter-spacing:-.5px;"><?= number_format($ts['total_inscrits'] ?? 0) ?></div>
                            <div class="small opacity-75">إجمالي المسجلين</div>
                            <div class="small mt-1 opacity-85">
                                <i class="fa-solid fa-venus me-1"></i>
                                <?= number_format($ts['inscrits_femmes'] ?? 0) ?> إناث
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Taux Couverture -->
                <div class="col-6 col-md-3">
                    <div class="rounded-4 p-3 h-100 text-white d-flex flex-column justify-content-between"
                         style="background: linear-gradient(135deg, #f57c00 0%, #e65100 100%);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="bg-white bg-opacity-25 rounded-3 p-2"><i class="fa-solid fa-chart-pie fa-lg"></i></div>
                            <span class="badge bg-white text-warning fw-bold small rounded-pill">تغطية</span>
                        </div>
                        <div>
                            <div class="fw-bold mb-0" style="font-size:1.5rem; letter-spacing:-.5px;"><?= $ts['taux_couverture'] ?? 0 ?>%</div>
                            <div class="small opacity-75">نسبة التغطية</div>
                            <div class="small mt-1 opacity-85">من الطاقة الاستيعابية</div>
                        </div>
                    </div>
                </div>

                <!-- Total Diplômés -->
                <div class="col-6 col-md-3">
                    <div class="rounded-4 p-3 h-100 text-white d-flex flex-column justify-content-between"
                         style="background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="bg-white bg-opacity-25 rounded-3 p-2"><i class="fa-solid fa-award fa-lg"></i></div>
                            <span class="badge bg-white text-purple fw-bold small rounded-pill">خريجون</span>
                        </div>
                        <div>
                            <div class="fw-bold mb-0" style="font-size:1.5rem; letter-spacing:-.5px;"><?= number_format($ts['total_diplomes'] ?? 0) ?></div>
                            <div class="small opacity-75">إجمالي الخريجين الناجحين</div>
                            <div class="small mt-1 opacity-85">
                                <i class="fa-solid fa-venus me-1"></i>
                                <?= number_format($ts['diplomes_femmes'] ?? 0) ?> إناث
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Session Breakdown -->
            <?php if (!empty($ts['by_session'])): ?>
            <div>
                <h6 class="fw-bold text-secondary mb-3 small text-uppercase letter-spacing-1">
                    <i class="fa-solid fa-calendar-days me-2 text-primary"></i>
                    تفصيل المتربصين النشطين حسب الدورة
                </h6>
                <div class="row g-2">
                    <?php
                    $sessColors = ['#1a73e8','#00897b','#f57c00','#6a1b9a','#c62828','#0277bd','#2e7d32','#4e342e'];
                    foreach ($ts['by_session'] as $si => $sess):
                        $color = $sessColors[$si % count($sessColors)];
                        $year = !empty($sess['DateD']) ? date('Y', strtotime($sess['DateD'])) : '';
                        $actifsSess = (int)($sess['actifs'] ?? 0);
                        $totalActifs = max(1, (int)($ts['total_actifs'] ?? 1));
                        $pct = round(($actifsSess / $totalActifs) * 100);
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-4 p-3 h-100" style="border-color: <?= $color ?>22 !important; background: <?= $color ?>08;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge rounded-pill fw-bold" style="background:<?= $color ?>; font-size:.7rem;">
                                    S<?= $si + 1 ?>
                                </span>
                                <span class="text-muted" style="font-size:.7rem;"><?= $year ?></span>
                            </div>
                            <div class="fw-bold mb-0" style="font-size:1.1rem; color:<?= $color ?>;">
                                <?= number_format($actifsSess) ?>
                            </div>
                            <div class="small text-muted mt-1"><?= htmlspecialchars($sess['session_nom']) ?></div>
                            <div class="progress mt-2 rounded-pill" style="height:4px;">
                                <div class="progress-bar" role="progressbar" style="width:<?= $pct ?>%; background:<?= $color ?>;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted" style="font-size:.65rem;"><i class="fa-solid fa-venus"></i> <?= number_format($sess['actifs_femmes'] ?? 0) ?></span>
                                <span class="text-muted" style="font-size:.65rem;"><?= $pct ?>%</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary bar -->
                <div class="mt-3 p-3 rounded-3 d-flex flex-wrap gap-3 justify-content-between align-items-center"
                     style="background: linear-gradient(90deg, #f8f9fa 0%, #e8f4fd 100%); border: 1px solid #dee2e6;">
                    <div class="text-center">
                        <div class="fw-bold text-primary" style="font-size:1.1rem;"><?= number_format($ts['total_actifs'] ?? 0) ?></div>
                        <div class="small text-muted">إجمالي المتربصين النشطين</div>
                        <div class="small text-muted opacity-75">مقيدون بالدراسة</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-success" style="font-size:1.1rem;"><?= number_format($ts['total_reconduits'] ?? 0) ?></div>
                        <div class="small text-muted">المتربصون المستمرون S2→S<?= count($ts['by_session'] ?? []) ?></div>
                        <div class="small text-muted opacity-75">أقسام مستمرة</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-info" style="font-size:1.1rem;"><?= number_format($ts['total_filles'] ?? 0) ?></div>
                        <div class="small text-muted">الطالبات — إناث</div>
                        <?php if (($ts['total_actifs'] ?? 0) > 0): ?>
                        <div class="small text-muted opacity-75"><?= round(($ts['total_filles'] / $ts['total_actifs']) * 100) ?>% نسبة تمثيل الإناث</div>
                        <?php endif; ?>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-warning" style="font-size:1.1rem;"><?= number_format($ts['sections_nouvelles'] ?? 0) ?></div>
                        <div class="small text-muted">الأقسام الجديدة S1</div>
                        <div class="small text-muted opacity-75">الدورة الحالية</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold" style="font-size:1.1rem; color:#6a1b9a;"><?= number_format($ts['total_diplomes'] ?? 0) ?></div>
                        <div class="small text-muted">إجمالي الخريجين (الناجحين)</div>
                        <div class="small text-muted opacity-75">حائزو شهادات التخرج</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <!-- ===== END TRAINEE STATISTICS SECTION ===== -->

    <!-- Detailed Offers Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i> تفاصيل الفروع والتخصصات المبرمجة</h5>
                <?php
                $hasFilter = !empty($_GET['filter_etab']) || !empty($_GET['filter_session']) || !empty($_GET['filter_mode']) || !empty($_GET['filter_status']) || !empty($_GET['filter_wilaya']);
                if ($hasFilter): ?>
                    <a href="<?= route('offres.index') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold">
                        <i class="fa-solid fa-xmark me-1"></i> إلغاء الفلاتر
                    </a>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2 align-items-center no-print">
                <button onclick="exportTableToExcel('offresTable', 'offres_formation.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('offresTable', 'offres_formation.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
                <input type="text" id="search_offre" onkeyup="applyFilters()" class="form-control rounded-pill bg-light border-0 px-4" placeholder="بحث عن تخصص..." style="width: 220px;">
                <!-- Column Selector Dropdown -->
                <div class="dropdown d-inline-block no-print">
                    <button class="btn btn-light rounded-pill px-3 dropdown-toggle shadow-sm" type="button" id="columnSelectorBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="border: 1px solid #cbd5e1; font-weight:600; font-size:0.88rem;">
                        <i class="fa-solid fa-table-columns me-1 text-primary"></i> الأعمدة / Colonnes
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-3 fs-7" aria-labelledby="columnSelectorBtn" style="max-height: 400px; overflow-y: auto; border-radius: 12px; min-width: 260px; text-align: right; z-index: 1050;">
                        <h6 class="dropdown-header text-muted fw-bold p-0 mb-2 border-bottom pb-1">إظهار / إخفاء الأعمدة</h6>
                        <?php
                        $cols = [
                            1 => 'رمز التخصص (Code Spec)',
                            3 => 'الدورة التكوينية (Session)',
                            4 => 'تاريخ التكوين (Dates)',
                            7 => 'عدد الأفواج (Groupes)',
                            8 => 'المستوى (Niveau)',
                            9 => 'المستوى المطلوب (Niveau Requis)',
                            10 => 'الدوام (Régime)',
                            11 => 'صفة الفرع (Statut Branche)',
                            12 => 'التأطير (Encadrement)',
                            13 => 'البرنامج (Programme)',
                            14 => 'التجهيزات (Equipements)',
                            15 => 'المدة (Durée)',
                            18 => 'المسجلون إناث (Femmes Inscr)',
                            19 => 'الناجحون (Lauréats)',
                            20 => 'الناجحون إناث (Femmes Laur)',
                            21 => 'مصادقة المؤسسة (Valid Etab)',
                            22 => 'مصادقة المديرية (Valid Dfep)'
                        ];
                        foreach ($cols as $idx => $label):
                        ?>
                        <li class="mb-1">
                            <label class="dropdown-item d-flex align-items-center gap-2 rounded px-2 py-1 cursor-pointer">
                                <input type="checkbox" class="col-toggle-checkbox form-check-input m-0" data-column-index="<?= $idx ?>" onchange="toggleTableColumn(<?= $idx ?>, this.checked)">
                                <span><?= $label ?></span>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button class="btn btn-light rounded-circle <?= $hasFilter ? 'text-primary bg-primary-subtle border-primary' : '' ?>"
                        id="filterToggleBtn" title="تصفية متقدمة" type="button" onclick="offresToggleFilter()">
                    <i class="fa-solid fa-filter" id="filterToggleIcon"></i>
                </button>
            </div>
        </div>

        <!-- ════ Filter Bar (Server-Side GET Form) ════ -->
        <div id="filterCollapse" class="no-print border-bottom border-light px-4 pt-3 pb-3 bg-light-subtle"
             style="display:<?= $hasFilter ? 'block' : 'none' ?>;">
            <form method="GET" action="" id="offresFilterForm">
                <div class="row g-3 align-items-end">

                    <?php if ($is_central): ?>
                    <!-- ① الولاية (للمستوى المركزي فقط) -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fa-solid fa-map-marker-alt me-1 text-primary"></i>الولاية
                        </label>
                        <select name="filter_wilaya" id="filter_wilaya"
                                class="form-select rounded-pill border-light bg-light"
                                onchange="const etab = document.getElementById('filter_etab'); if(etab) etab.value = ''; const mode = document.getElementById('filter_mode'); if(mode) mode.value = ''; const sess = document.getElementById('filter_session'); if(sess) sess.value = ''; this.form.submit();">
                            <option value="">كل الولايات</option>
                            <?php
                            // Load wilayas (DFEPs) list
                            $dfepList = \App\Core\Database::getInstance()->getConnection()
                                ->query("SELECT IDDFEP as id, Nom as nom FROM dfep ORDER BY Nom ASC")
                                ->fetchAll(\PDO::FETCH_ASSOC);
                            foreach ($dfepList as $dfep):
                            ?>
                            <option value="<?= $dfep['id'] ?>"
                                <?= (isset($_GET['filter_wilaya']) && $_GET['filter_wilaya'] == $dfep['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dfep['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <?php if ($is_wilaya || $is_central): ?>
                    <!-- ② المؤسسة -->
                    <div class="col-12 col-md-3" id="filter_etab_wrapper">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fa-solid fa-building-flag me-1 text-success"></i>المؤسسة التكوينية
                        </label>
                        <select name="filter_etab" id="filter_etab"
                                class="form-select rounded-pill border-light bg-light"
                                onchange="const mode = document.getElementById('filter_mode'); if(mode) mode.value = ''; const sess = document.getElementById('filter_session'); if(sess) sess.value = ''; this.form.submit();">
                            <option value="">كل المؤسسات</option>
                            <?php foreach ($etablissements as $e): ?>
                            <option value="<?= htmlspecialchars($e['id']) ?>"
                                <?= (isset($_GET['filter_etab']) && $_GET['filter_etab'] == $e['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nom_ar']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- ③ نمط التكوين -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fa-solid fa-chalkboard-user me-1 text-warning"></i>نمط التكوين
                        </label>
                        <select name="filter_mode" id="filter_mode"
                                class="form-select rounded-pill border-light bg-light"
                                onchange="const sess = document.getElementById('filter_session'); if(sess) sess.value = ''; this.form.submit();"
                                <?= ((int)(session('user.IDMode_formation') ?? 0) === 10) ? 'disabled' : '' ?>>
                            <?php if ((int)(session('user.IDMode_formation') ?? 0) === 10): ?>
                                <option value="10" selected>تكوين عن طريق التمهين / Apprentissage</option>
                            <?php else: ?>
                                <option value="">كل الأنماط</option>
                                <?php foreach ($modes_formation ?? [] as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id']) ?>" <?= (isset($_GET['filter_mode']) && $_GET['filter_mode'] == $m['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nom_ar']) ?> / <?= htmlspecialchars($m['nom_fr']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- ④ الدورة التكوينية -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fa-solid fa-calendar-days me-1 text-info"></i>الدورة التكوينية
                        </label>
                        <select name="filter_session" id="filter_session"
                                class="form-select rounded-pill border-light bg-light"
                                onchange="this.form.submit();">
                            <option value="">كل الدورات</option>
                            <?php foreach ($sessions as $s): ?>
                            <option value="<?= $s['id'] ?>"
                                <?= (isset($_GET['filter_session']) && $_GET['filter_session'] == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['intitule_ar']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- ⑤ حالة العرض -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fa-solid fa-circle-check me-1 text-success"></i>الحالة
                        </label>
                        <select name="filter_status" id="filter_status"
                                class="form-select rounded-pill border-light bg-light"
                                onchange="this.form.submit();">
                            <option value="">كل الحالات</option>
                            <option value="brouillon"      <?= (($_GET['filter_status'] ?? '') === 'brouillon')      ? 'selected' : '' ?>>مسودة</option>
                            <option value="soumis"         <?= (($_GET['filter_status'] ?? '') === 'soumis')         ? 'selected' : '' ?>>مرفوع للولاية</option>
                            <option value="valide_wilaya"  <?= (($_GET['filter_status'] ?? '') === 'valide_wilaya')  ? 'selected' : '' ?>>مصادق عليه ولائياً</option>
                            <option value="valide_central" <?= (($_GET['filter_status'] ?? '') === 'valide_central') ? 'selected' : '' ?>>مقبول مركزياً</option>
                            <option value="rejete_wilaya"  <?= (($_GET['filter_status'] ?? '') === 'rejete_wilaya')  ? 'selected' : '' ?>>مرفوض ولائياً</option>
                            <option value="rejete_central" <?= (($_GET['filter_status'] ?? '') === 'rejete_central') ? 'selected' : '' ?>>مرفوض مركزياً</option>
                        </select>
                    </div>

                </div>
            </form>
        </div>

        <div class="card-body p-0 mt-2">
            <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="table table-hover align-middle mb-0" id="offresTable" style="width: 100%; table-layout: auto;">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4" style="min-width:110px;">رمز العرض</th>
                            <th style="min-width:90px;">رمز التخصص</th>
                            <th class="text-center" style="min-width:120px;">النمط</th>
                            <th style="min-width:130px;">الدورة التكوينية</th>
                            <th style="min-width:130px;">تاريخ التكوين</th>
                            <th style="min-width:260px;">الفرع / التخصص المهني</th>
                            <th style="min-width:200px;">المؤسسة التكوينية</th>
                            <th class="text-center">عدد الأفواج</th>
                            <th class="text-center">المستوى</th>
                            <th class="text-center">المستوى المطلوب</th>
                            <th class="text-center">الدوام</th>
                            <th class="text-center">صفة الفرع</th>
                            <th class="text-center">التأطير</th>
                            <th class="text-center">البرنامج</th>
                            <th class="text-center">التجهيزات</th>
                            <th class="text-center">المدة</th>
                            <th class="text-center">المقاعد</th>
                            <th class="text-center">المسجلين</th>
                            <th class="text-center">منهم إناث</th>
                            <th class="text-center">الناجحين</th>
                            <th class="text-center">منهم إناث</th>
                            <th class="text-center">مصادقة المؤسسة</th>
                            <th class="text-center">مصادقة المديرية</th>
                            <th class="text-center">الحالة</th>
                            <th class="pe-4 text-end no-print no-export">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($offres_detail)): ?>
                            <tr>
                                <td colspan="25" class="text-center py-4 text-muted">لا توجد عروض مبرمجة حالياً.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($offres_detail as $od): ?>
                            <tr style="transition: background 0.2s;" data-session-id="<?= htmlspecialchars($od['session_id'] ?? '') ?>" data-etab-id="<?= htmlspecialchars($od['etablissement_id'] ?? '') ?>">
                                <td class="ps-4">
                                    <span class="badge bg-light text-primary border border-primary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= $od['code'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border border-secondary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= htmlspecialchars($od['spec_code'] ?: '—') ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $modeId = (int)($od['mode_id'] ?? 1);
                                    $modeName = $od['mode_formation'] ?? '';
                                    // Pick badge color by mode
                                    $modeBadgeClass = match($modeId) {
                                        10 => 'bg-warning text-dark',   // تمهين
                                        2  => 'bg-info text-white',     // متواصل
                                        3  => 'bg-secondary text-white',// مسائي
                                        default => 'bg-primary text-white'
                                    };
                                    $modeIcon = match($modeId) {
                                        10 => 'fa-hammer',
                                        2  => 'fa-rotate',
                                        3  => 'fa-moon',
                                        default => 'fa-chalkboard-user'
                                    };
                                    ?>
                                    <span class="badge <?= $modeBadgeClass ?> rounded-pill px-2" style="font-size:0.72rem; white-space:nowrap;">
                                        <i class="fa-solid <?= $modeIcon ?> me-1"></i><?= htmlspecialchars($modeName ?: '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($od['session_name'] ?: 'غير محددة') ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-muted" style="font-size:0.75rem; white-space: nowrap;">
                                        <div>من: <?= $od['date_debut'] ? date('Y/m/d', strtotime($od['date_debut'])) : '—' ?></div>
                                        <div>إلى: <?= $od['date_fin'] ? date('Y/m/d', strtotime($od['date_fin'])) : '—' ?></div>
                                    </div>
                                </td>
                                <td style="max-width:260px; word-wrap:break-word; overflow-wrap:break-word; white-space:normal;">
                                    <?php
                                    // If mode is on-demand and custom names are set, use them
                                    $hasCustomName = !empty($od['nom_spec_custom_ar']) || !empty($od['nom_spec_custom_fr']);
                                    $dispAr = $hasCustomName && !empty($od['nom_spec_custom_ar'])
                                        ? $od['nom_spec_custom_ar'] : $od['spec_ar'];
                                    $dispFr = $hasCustomName && !empty($od['nom_spec_custom_fr'])
                                        ? $od['nom_spec_custom_fr'] : $od['spec_fr'];
                                    ?>
                                    <div class="fw-bold text-dark mb-1" style="font-size:0.88rem; line-height:1.4; word-break:break-word;">
                                        <?= htmlspecialchars($dispAr) ?>
                                        <?php if ($hasCustomName): ?>
                                            <span class="badge bg-purple-subtle text-purple border rounded-pill ms-1" style="font-size:0.65rem; background:#ede9fe; color:#7c3aed;"><i class="fa-solid fa-star fa-xs"></i> مخصص</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.78rem; line-height:1.35; word-break:break-word; white-space:normal; overflow-wrap:break-word;"><?= htmlspecialchars($dispFr) ?></div>
                                </td>
                                <td>
                                    <div class="text-muted fw-bold" style="font-size:0.8rem;"><i class="fa-solid fa-building-flag me-1"></i> <?= htmlspecialchars($od['centre']) ?></div>
                                    <?php if(!empty($od['centre_delegue'])): ?>
                                        <div class="small text-warning fw-bold mt-1"><i class="fa-solid fa-handshake me-1"></i> منتدبة: <?= htmlspecialchars($od['centre_delegue']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-dark fs-6"><?= $od['nbr_groupe'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-white rounded-pill px-3"><?= htmlspecialchars($od['level_name'] ?: '—') ?></span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <span class="badge bg-secondary text-white rounded-pill px-3"><?= htmlspecialchars($od['niveau_txt']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border border-light-subtle rounded-pill px-2"><?= htmlspecialchars($od['regime_cours'] ?: '—') ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2"><?= htmlspecialchars($od['type_branche'] ?: '—') ?></span>
                                </td>
                                <td class="text-center">
                                    <?= $od['toggle_encadrement'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                </td>
                                <td class="text-center">
                                    <?= $od['toggle_programme'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                </td>
                                <td class="text-center">
                                    <?= $od['toggle_equipement'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                </td>
                                <td class="text-center fw-bold text-dark" style="white-space: nowrap;"><?= $od['duree'] ?></td>
                                <td class="text-center fw-bold text-muted fs-6"><?= $od['places'] ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $od['inscrits'] >= $od['places'] ? 'bg-success' : ($od['inscrits'] > 0 ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill" style="font-size:0.85rem;">
                                        <?= $od['inscrits'] ?> / <?= $od['places'] ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-dark"><?= $od['inscrits_females'] ?></td>
                                <td class="text-center fw-bold text-success fs-6"><?= $od['laureats'] ?></td>
                                <td class="text-center fw-bold text-pink fs-6" style="color: #ec4899;"><?= $od['laureats_females'] ?></td>
                                <td class="text-center">
                                    <?= $od['valide'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-circle-check"></i> نعم</span>' : '<span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-2"><i class="fa-solid fa-circle-question"></i> لا</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?= $od['valid_dfp'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-circle-check"></i> نعم</span>' : '<span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-2"><i class="fa-solid fa-circle-question"></i> لا</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $status = $od['statut_offre'];
                                    switch ($status) {
                                        case 'مسودة':
                                            echo '<span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-3"><i class="fa-solid fa-pen-ruler me-1"></i> مسودة</span>';
                                            break;
                                        case 'مرفوع للولاية':
                                            echo '<span class="badge bg-info-subtle text-info border border-info rounded-pill px-3"><i class="fa-solid fa-paper-plane me-1"></i> مرفوع</span>';
                                            break;
                                        case 'مصادق عليه ولائيا':
                                            echo '<span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3"><i class="fa-solid fa-circle-check me-1"></i> مصادق ولائيا</span>';
                                            break;
                                        case 'مقبول مركزيا':
                                            echo '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-3"><i class="fa-solid fa-award me-1"></i> مقبول مركزيا</span>';
                                            break;
                                        case 'مرفوض ولائيا':
                                            echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($od['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض ولائيا</span>';
                                            break;
                                        case 'مرفوض مركزيا':
                                            echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($od['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض مركزيا</span>';
                                            break;
                                        case 'مغلق':
                                            echo '<span class="badge bg-secondary-subtle text-secondary border border-secondary rounded-pill px-3"><i class="fa-solid fa-lock me-1"></i> مغلق</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary rounded-pill px-3">' . htmlspecialchars($status) . '</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td class="pe-4 text-end no-print no-export">
                                    <!-- Hidden spans for client-side JS filtering -->
                                    <span class="d-none filter-session-id"><?= $od['session_id'] ?></span>
                                    <span class="d-none filter-mode-val"><?= (int)$od['mode_id'] ?></span>
                                    <span class="d-none filter-status-val"><?php
                                        switch($status) {
                                            case 'مسودة': echo 'brouillon'; break;
                                            case 'مرفوع للولاية': echo 'soumis'; break;
                                            case 'مصادق عليه ولائيا': echo 'valide_wilaya'; break;
                                            case 'مقبول مركزيا': echo 'valide_central'; break;
                                            case 'مرفوض ولائيا': echo 'rejete_wilaya'; break;
                                            case 'مرفوض مركزيا': echo 'rejete_central'; break;
                                            case 'مغلق': echo 'ferme'; break;
                                        }
                                    ?></span>

                                    <!-- Action buttons depending on role -->
                                    <?php if ($is_etab): ?>
                                        <?php if (in_array($status, ['مسودة', 'مرفوض ولائيا', 'مرفوض مركزيا'])): ?>
                                            <!-- Submit to Wilaya Button -->
                                            <form action="/dashboard/offres/soumettre" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                                <input type="hidden" name="id" value="<?= $od['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 me-1" title="تقديم للمصادقة" onclick="return confirm('هل أنت متأكد من تقديم هذا العرض للمديرية الولائية؟ بعد التقديم لن تتمكن من تعديله إلا إذا تم رفضه.')">
                                                    <i class="fa-solid fa-paper-plane me-1"></i> تقديم
                                                </button>
                                            </form>
                                            
                                            <!-- Edit/Delete -->
                                            <button class="btn btn-sm btn-outline-primary rounded-circle" style="width:32px; height:32px; padding:0;" onclick="editOffre(<?= htmlspecialchars(json_encode($od)) ?>)" title="تعديل العرض">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <form action="/dashboard/offres/delete/<?= $od['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العرض؟')">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle ms-1" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center;" title="حذف العرض">
        <i class="fa-solid fa-trash-can"></i>
    </button>
</form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary rounded-circle" style="width:32px; height:32px; padding:0;" disabled title="لا يمكن تعديل أو حذف العروض بعد تقديمها للمصادقة">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($is_wilaya): ?>
                                        <?php if ($status === 'مرفوع للولاية'): ?>
                                            <!-- Approve button -->
                                            <form action="/dashboard/offres/valider-direction" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من المصادقة الولائية على هذا العرض؟')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                                <input type="hidden" name="id" value="<?= $od['id'] ?>">
                                                <input type="hidden" name="action" value="approuver">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1 me-1 fw-bold shadow-sm">
                                                    <i class="fa-solid fa-circle-check me-1"></i> مصادقة
                                                </button>
                                            </form>
                                            <!-- Reject button trigger modal -->
                                            <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold shadow-sm" onclick="showRejectionModal(<?= $od['id'] ?>, 'direction')">
                                                <i class="fa-solid fa-circle-xmark me-1"></i> رفض
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($is_central): ?>
                                        <?php if ($status === 'مصادق عليه ولائيا'): ?>
                                            <!-- Approve button -->
                                            <form action="/dashboard/offres/valider-centrale" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من القبول المركزي لهذا العرض؟')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                                <input type="hidden" name="id" value="<?= $od['id'] ?>">
                                                <input type="hidden" name="action" value="approuver">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1 me-1 fw-bold shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                                    <i class="fa-solid fa-award me-1"></i> قبول مركزي
                                                </button>
                                            </form>
                                            <!-- Reject button trigger modal -->
                                            <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold shadow-sm" onclick="showRejectionModal(<?= $od['id'] ?>, 'centrale')">
                                                <i class="fa-solid fa-circle-xmark me-1"></i> رفض
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 px-4 text-center">
            <button class="btn btn-light rounded-pill px-4 text-primary fw-bold"><i class="fa-solid fa-arrow-down me-2"></i> عرض المزيد من التخصصات</button>
        </div>
    </div>
</div>

<!-- Styles: Redesigned Premium Form Styles -->
<style>
.premium-modal .modal-content {
    border: none;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(126, 34, 206, 0.25);
    overflow: hidden;
    transition: all 0.3s ease;
}

.premium-modal .modal-header {
    background: #7e22ce;
    padding: 1.25rem 2rem;
    border: none;
}

.premium-modal .modal-title {
    color: #ffffff;
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 1.25rem;
    letter-spacing: -0.5px;
}

.premium-modal .modal-body {
    background-color: #f8fafc;
}

.premium-modal .btn-close {
    filter: invert(1) grayscale(1) brightness(2);
    opacity: 0.8;
    transition: all 0.2s ease;
}

.premium-modal .btn-close:hover {
    transform: rotate(90deg);
    opacity: 1;
}

.form-section-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
}

.form-section-card:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.form-section-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #7e22ce;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px dashed #e2e8f0;
    padding-bottom: 0.5rem;
}

.premium-label {
    font-weight: 700;
    font-size: 0.82rem;
    color: #334155;
    margin-bottom: 0.5rem;
}

.premium-input-group {
    position: relative;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.premium-input-group .input-group-text {
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #64748b !important;
    border-radius: 0 12px 12px 0 !important; /* RTL */
    padding: 0.6rem 0.9rem;
    transition: all 0.3s ease;
}

.premium-input, .premium-select {
    border: 1px solid #e2e8f0 !important;
    background-color: #ffffff !important;
    color: #1e293b !important;
    border-radius: 12px 0 0 12px !important; /* RTL */
    padding: 0.65rem 1rem;
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.premium-select {
    padding-left: 2rem;
}

.premium-input-group:focus-within .input-group-text {
    border-color: #7e22ce !important;
    color: #7e22ce !important;
    background-color: rgba(126, 34, 206, 0.05) !important;
}

.premium-input:focus, .premium-select:focus {
    border-color: #7e22ce !important;
    box-shadow: 0 0 0 4px rgba(126, 34, 206, 0.15) !important;
    background-color: #ffffff !important;
    outline: none;
}

/* Live Preview Card Styles */
.live-preview-card {
    background: linear-gradient(135deg, rgba(131, 7, 228, 0.03) 0%, rgba(108, 6, 190, 0.05) 100%);
    border: 2px dashed var(--primary-color);
    border-radius: 20px;
    padding: 1.5rem;
    position: sticky;
    top: 20px;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.live-preview-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.preview-badge {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.45em 1em;
    border-radius: 30px;
}

.preview-timeline {
    position: relative;
    padding-right: 1.5rem;
    border-right: 2px solid var(--border-color);
}

.preview-timeline-item {
    position: relative;
    margin-bottom: 1.25rem;
}

.preview-timeline-item::after {
    content: '';
    position: absolute;
    right: -1.95rem;
    top: 0.25rem;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: var(--border-color);
    border: 2px solid var(--card-bg);
    transition: all 0.3s ease;
}

.preview-timeline-item.active::after {
    background-color: var(--primary-color);
    box-shadow: 0 0 0 4px var(--primary-glow);
}

.preview-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.8rem;
    font-weight: 700;
}

.preview-meta-row:last-child {
    border-bottom: none;
}

.requirements-panel {
    background-color: var(--bg-dashboard);
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid var(--border-color);
}

.requirement-pill {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.5rem 0.75rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    background-color: var(--border-color);
    color: var(--text-muted);
}

.requirement-pill.active {
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}
</style>

<!-- Modal: Add Offer -->
<div class="modal fade premium-modal" id="addOffreModal" tabindex="-1" aria-labelledby="addOffreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOffreModalLabel">
                    <i class="fa-solid fa-graduation-cap me-2"></i> إدراج عرض تكوين لدورة جديدة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/offres/store" method="POST" id="addOffreForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Left Panel: Form Inputs -->
                        <div class="col-lg-8">
                            
                            <!-- Section 1: Specialty & Session -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-book-open"></i> التخصص البيداغوجي والدورة التكوينية
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="premium-label">التخصص البيداغوجي المستهدف *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-award"></i></span>
                                            <select name="specialite_id" id="add_offre_specialite_id" required class="premium-select form-select">
                                                <option value="" disabled selected data-code="" data-duree="0">اختر التخصص البيداغوجي...</option>
                                                <?php foreach ($specialites as $s): ?>
                                                    <option value="<?= $s['id'] ?>" data-code="<?= htmlspecialchars($s['code']) ?>" data-duree="<?= (int)$s['duree_semestres'] ?>"><?= htmlspecialchars($s['libelle_ar']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="premium-label">رمز العرض (Code Offre) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                                            <input type="text" name="code" id="add_offre_code" required class="premium-input form-control" placeholder="رمز العرض (يملأ تلقائياً)">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="premium-label">الدورة التكوينية (Session) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-calendar-check"></i></span>
                                            <select name="session_id" id="add_offre_session_select" required class="premium-select form-select">
                                                <option value="" disabled selected>اختر الدورة...</option>
                                                <?php foreach ($sessions as $s_item): ?>
                                                    <option value="<?= $s_item['id'] ?>" 
                                                            data-name="<?= htmlspecialchars($s_item['intitule_ar']) ?>"
                                                            data-debut="<?= htmlspecialchars($s_item['date_debut']) ?>"
                                                            data-fin="<?= htmlspecialchars($s_item['date_fin']) ?>"
                                                            data-selection="<?= htmlspecialchars($s_item['date_fin_insc']) ?>">
                                                        <?= htmlspecialchars($s_item['intitule_ar']) ?> (البدء: <?= htmlspecialchars($s_item['date_debut']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="custom" class="fw-bold text-primary">+ إضافة دورة جديدة (إدخال يدوي)...</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Collapsible text field for manual input -->
                                        <div id="add_offre_session_custom_wrapper" class="mt-2 animate__animated animate__fadeIn d-none">
                                            <div class="input-group premium-input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-pen-fancy"></i></span>
                                                <input type="text" name="session_name" id="add_offre_session_name" class="premium-input form-control" placeholder="اكتب اسم الدورة الجديدة (مثال: دورة سبتمبر 2026)">
                                            </div>
                                            <small class="text-muted fs-7 mt-1 d-block"><i class="fa-solid fa-circle-info me-1"></i> سيتم إنشاء الدورة الجديدة تلقائياً في النظام عند الحفظ.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="premium-label">المؤسسة المنتدبة (Déléguée) <span class="text-muted">(اختياري)</span></label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-building-user"></i></span>
                                            <select name="etablissement_delegue_id" id="add_offre_delegue_id" class="premium-select form-select">
                                                <option value="">لا توجد مؤسسة منتدبة</option>
                                                <?php foreach ($etablissements as $etab_item): ?>
                                                    <option value="<?= $etab_item['id'] ?>"><?= htmlspecialchars($etab_item['nom_ar']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 2: Registration & System -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-circle-info"></i> شروط ونظام الدراسة
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="premium-label">الشهادة المستهدفة *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-graduation-cap"></i></span>
                                            <select name="diplome_vise" id="add_offre_diplome" required class="premium-select form-select">
                                                <?php foreach ($qualifications_diplomes ?? [] as $qdip): ?>
                                                    <option value="<?= htmlspecialchars($qdip['code'] ?: $qdip['abr_fr']) ?>" data-ar="<?= htmlspecialchars($qdip['nom_ar']) ?>"><?= htmlspecialchars($qdip['abr_fr'] ?: $qdip['code']) ?> — <?= htmlspecialchars($qdip['nom_ar']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">المستوى المطلوب *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-user-gear"></i></span>
                                            <select name="niveau_requis" id="add_offre_niveau" required class="premium-select form-select">
                                                <option value="bac">ثالثة ثانوي أو بكالوريا / Bac</option>
                                                <option value="bem">تعليم متوسط / BEM</option>
                                                <option value="3em_moy">الثالثة متوسط</option>
                                                <option value="sans_niveau">بدون مستوى</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">نمط التكوين *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-briefcase"></i></span>
                                            <select name="mode_formation" id="add_offre_mode" required class="premium-select form-select" <?= ((int)(session('user.IDMode_formation') ?? 0) === 10) ? 'disabled' : '' ?>>
                                                <?php foreach ($modes_formation ?? [] as $mf): ?>
                                                    <?php if (((int)(session('user.IDMode_formation') ?? 0) === 10 && (int)$mf['id'] === 10) || (int)(session('user.IDMode_formation') ?? 0) !== 10): ?>
                                                        <option value="<?= (int)$mf['id'] ?>" <?= ((int)(session('user.IDMode_formation') ?? 0) === 10) ? 'selected' : '' ?>><?= htmlspecialchars($mf['nom_ar']) ?><?= $mf['nom_fr'] ? ' / ' . htmlspecialchars($mf['nom_fr']) : '' ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ((int)(session('user.IDMode_formation') ?? 0) === 10): ?>
                                                <input type="hidden" name="mode_formation" value="10">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Custom Specialty Name Panel — shown only when mode requires it -->
                                    <div class="col-12" id="add_custom_spec_panel" style="display:none;">
                                        <div class="p-3 rounded-3 border border-2" style="border-color:#7c3aed!important; background:linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);">
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:#7c3aed; font-size:0.82rem;">
                                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> تسمية التخصص حسب الطلب
                                                </span>
                                                <small class="text-muted">— عندما يكون النمط "حسب الطلب"، يمكنك كتابة تسمية مخصصة للتخصص</small>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="premium-label" style="color:#7c3aed;">
                                                        <i class="fa-solid fa-language me-1"></i> تسمية التخصص بالعربية
                                                    </label>
                                                    <div class="input-group premium-input-group">
                                                        <span class="input-group-text" style="background:#ede9fe; border-color:#c4b5fd;"><i class="fa-solid fa-pen-nib" style="color:#7c3aed;"></i></span>
                                                        <input type="text" name="nom_spec_custom_ar" id="add_nom_spec_custom_ar"
                                                            class="premium-input form-control"
                                                            placeholder="مثال: تقني في الطبخ التقليدي الجزائري..."
                                                            dir="rtl">
                                                    </div>
                                                    <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info me-1"></i> التسمية بالعربية كما ستظهر في الوثائق الرسمية</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="premium-label" style="color:#7c3aed;">
                                                        <i class="fa-solid fa-language me-1"></i> Intitulé de la spécialité (Français)
                                                    </label>
                                                    <div class="input-group premium-input-group">
                                                        <span class="input-group-text" style="background:#ede9fe; border-color:#c4b5fd;"><i class="fa-solid fa-pen-nib" style="color:#7c3aed;"></i></span>
                                                        <input type="text" name="nom_spec_custom_fr" id="add_nom_spec_custom_fr"
                                                            class="premium-input form-control"
                                                            placeholder="Ex: Technicien en cuisine traditionnelle..."
                                                            dir="ltr">
                                                    </div>
                                                    <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info me-1"></i> L'intitulé en français tel qu'il apparaîtra sur les documents officiels</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="premium-label">الطاقة الاستيعابية *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                                            <input type="number" name="capacite" id="add_offre_capacite" required class="premium-input form-control" placeholder="30" value="30">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="premium-label">صفة الفرع *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-code-fork"></i></span>
                                            <select name="type_branche" id="add_offre_type_branche" required class="premium-select form-select">
                                                <option value="جديدة">جديدة (Nouvelle)</option>
                                                <option value="مجددة">مجددة (Renouvelée)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="premium-label">نظام الدراسة (Régime) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-clock"></i></span>
                                            <select name="regime_cours" id="add_offre_regime" required class="premium-select form-select">
                                                <?php foreach ($regimes_cours ?? [] as $rc): ?>
                                                    <option value="<?= htmlspecialchars($rc['nom_ar']) ?>"><?= htmlspecialchars($rc['nom_ar']) ?><?= $rc['nom_fr'] ? ' / ' . htmlspecialchars($rc['nom_fr']) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="premium-label">نظام الإيواء (Hébergement) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-hotel"></i></span>
                                            <select name="hebergement" id="add_offre_hebergement" required class="premium-select form-select">
                                                <?php foreach ($regimes_hebergement ?? [] as $rh): ?>
                                                    <option value="<?= htmlspecialchars($rh['nom_ar']) ?>"><?= htmlspecialchars($rh['nom_ar']) ?><?= $rh['nom_fr'] && $rh['nom_fr'] !== $rh['nom_ar'] ? ' / ' . htmlspecialchars($rh['nom_fr']) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 3: Dates & Schedule -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-calendar-days"></i> التواريخ والمواعيد
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="premium-label">تاريخ بدء الانتقاء والتوجيه (Sélection) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-user-check"></i></span>
                                            <input type="date" name="date_debut_selection" id="add_offre_debut_selection" required class="premium-input form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="premium-label">تاريخ الفحص الطبي (Examen Médical)</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-heart-pulse"></i></span>
                                            <input type="date" name="date_examen_medical" id="add_offre_examen_medical" class="premium-input form-control">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="premium-label">تاريخ زيارة الورشات (Visite)</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-eye"></i></span>
                                            <input type="date" name="date_visite_ateliers" id="add_offre_visite_ateliers" class="premium-input form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">تاريخ البداية (Start) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-play"></i></span>
                                            <input type="date" name="date_debut" id="add_offre_debut" required class="premium-input form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">تاريخ النهاية (End) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-stop"></i></span>
                                            <input type="date" name="date_fin" id="add_offre_fin" required class="premium-input form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 4: Checklist -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-check-double"></i> متطلبات الاعتماد والتحقق
                                </div>
                                <div class="row g-3 bg-light-subtle p-3 rounded-3 border">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                            <label class="form-check-label fw-bold small text-dark" for="add_toggle_encadrement">التأطير البيداغوجي متوفر</label>
                                            <input class="form-check-input" type="checkbox" name="toggle_encadrement" id="add_toggle_encadrement">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                            <label class="form-check-label fw-bold small text-dark" for="add_toggle_programme">البرنامج الدراسي متوفر</label>
                                            <input class="form-check-input" type="checkbox" name="toggle_programme" id="add_toggle_programme">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                            <label class="form-check-label fw-bold small text-dark" for="add_toggle_equipement">التجهيز التقني متوفر</label>
                                            <input class="form-check-input" type="checkbox" name="toggle_equipement" id="add_toggle_equipement">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Right Panel: Live Dynamic Preview Card -->
                        <div class="col-lg-4">
                            <div class="live-preview-card animate__animated animate__fadeIn">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="fw-bold mb-0 text-dark" style="font-family:'Cairo';"><i class="fa-solid fa-circle-nodes text-primary me-1 animate__animated animate__pulse animate__infinite"></i> معاينة مباشرة لعرض التكوين</h6>
                                    <span class="badge bg-warning text-dark preview-badge shadow-sm" style="font-size: 0.65rem;">مسودة / Draft</span>
                                </div>
                                
                                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-primary-subtle text-primary preview-badge" id="add_preview_code_badge">---</span>
                                        <span class="badge bg-purple text-white preview-badge" id="add_preview_session_badge" style="background:#8307e4;">---</span>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-1 text-dark" id="add_preview_spec_name" style="font-family:'Cairo'; font-size: 1.05rem;">اسم التخصص البيداغوجي</h5>
                                    
                                    <div class="preview-meta-row mt-2">
                                        <span class="text-muted"><i class="fa-solid fa-graduation-cap me-1"></i> الشهادة:</span>
                                        <span class="text-dark fw-bold" id="add_preview_diplome">BTS</span>
                                    </div>
                                    <div class="preview-meta-row">
                                        <span class="text-muted"><i class="fa-solid fa-user-graduate me-1"></i> المستوى:</span>
                                        <span class="text-dark fw-bold" id="add_preview_niveau">---</span>
                                    </div>
                                    <div class="preview-meta-row">
                                        <span class="text-muted"><i class="fa-solid fa-building me-1"></i> النمط:</span>
                                        <span class="text-dark fw-bold" id="add_preview_mode">---</span>
                                    </div>
                                    <div class="preview-meta-row">
                                        <span class="text-muted"><i class="fa-solid fa-users me-1"></i> المقاعد:</span>
                                        <span class="text-dark fw-bold text-primary" id="add_preview_capacite">30</span>
                                    </div>
                                </div>
                                
                                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                                    <h6 class="fw-bold mb-3 small text-dark"><i class="fa-solid fa-timeline text-primary me-1"></i> مخطط التواريخ والجدولة</h6>
                                    <div class="preview-timeline">
                                        <div class="preview-timeline-item active">
                                            <div class="fw-bold small text-dark">بدء الانتقاء والتوجيه</div>
                                            <div class="text-muted small" id="add_preview_date_selection">-- / -- / ----</div>
                                        </div>
                                        <div class="preview-timeline-item active">
                                            <div class="fw-bold small text-dark">بداية التكوين</div>
                                            <div class="text-muted small" id="add_preview_date_start">-- / -- / ----</div>
                                        </div>
                                        <div class="preview-timeline-item">
                                            <div class="fw-bold small text-dark">نهاية التكوين المتوقعة</div>
                                            <div class="text-muted small" id="add_preview_date_end">-- / -- / ----</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="requirements-panel">
                                    <h6 class="fw-bold mb-2.5 small text-dark"><i class="fa-solid fa-shield-halved text-success me-1"></i> الاعتمادات المتوفرة</h6>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="requirement-pill" id="add_preview_req_encadrement">
                                            <i class="fa-solid fa-circle-xmark"></i> التأطير البيداغوجي
                                        </div>
                                        <div class="requirement-pill" id="add_preview_req_programme">
                                            <i class="fa-solid fa-circle-xmark"></i> البرنامج الدراسي
                                        </div>
                                        <div class="requirement-pill" id="add_preview_req_equipement">
                                            <i class="fa-solid fa-circle-xmark"></i> التجهيز التقني
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold rounded-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-3 shadow" style="background: linear-gradient(135deg, #6c06be 0%, #8307e4 100%); border: none;">تأكيد الإضافة وحفظ كمسودة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Offer -->
<div class="modal fade premium-modal" id="editOffreModal" tabindex="-1" aria-labelledby="editOffreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOffreModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2"></i> تعديل عرض التكوين
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/offres/update" method="POST" id="editOffreForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="id" id="edit_offre_id">
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Left Panel: Form Inputs -->
                        <div class="col-lg-8">
                            
                            <!-- Section 1: Specialty & Session -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-book-open"></i> التخصص البيداغوجي والدورة التكوينية
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="premium-label">التخصص البيداغوجي المستهدف *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-award"></i></span>
                                            <select name="specialite_id" id="edit_offre_specialite_id" required class="premium-select form-select">
                                                <?php foreach ($specialites as $s): ?>
                                                    <option value="<?= $s['id'] ?>" data-code="<?= htmlspecialchars($s['code']) ?>" data-duree="<?= (int)$s['duree_semestres'] ?>"><?= htmlspecialchars($s['libelle_ar']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="premium-label">رمز العرض (Code Offre) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                                            <input type="text" name="code" id="edit_offre_code" required class="premium-input form-control" placeholder="رمز العرض">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="premium-label">الدورة التكوينية (Session) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-calendar-check"></i></span>
                                            <select name="session_id" id="edit_offre_session_select" required class="premium-select form-select">
                                                <option value="" disabled>اختر الدورة...</option>
                                                <?php foreach ($sessions as $s_item): ?>
                                                    <option value="<?= $s_item['id'] ?>" 
                                                            data-name="<?= htmlspecialchars($s_item['intitule_ar']) ?>"
                                                            data-debut="<?= htmlspecialchars($s_item['date_debut']) ?>"
                                                            data-fin="<?= htmlspecialchars($s_item['date_fin']) ?>"
                                                            data-selection="<?= htmlspecialchars($s_item['date_fin_insc']) ?>">
                                                        <?= htmlspecialchars($s_item['intitule_ar']) ?> (البدء: <?= htmlspecialchars($s_item['date_debut']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="custom" class="fw-bold text-primary">+ إضافة دورة جديدة (إدخال يدوي)...</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Collapsible text field for manual input -->
                                        <div id="edit_offre_session_custom_wrapper" class="mt-2 animate__animated animate__fadeIn d-none">
                                            <div class="input-group premium-input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-pen-fancy"></i></span>
                                                <input type="text" name="session_name" id="edit_offre_session_name" class="premium-input form-control" placeholder="اكتب اسم الدورة الجديدة">
                                            </div>
                                            <small class="text-muted fs-7 mt-1 d-block"><i class="fa-solid fa-circle-info me-1"></i> سيتم إنشاء الدورة الجديدة تلقائياً في النظام عند الحفظ.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="premium-label">المؤسسة المنتدبة (Déléguée) <span class="text-muted">(اختياري)</span></label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-building-user"></i></span>
                                            <select name="etablissement_delegue_id" id="edit_offre_delegue_id" class="premium-select form-select">
                                                <option value="">لا توجد مؤسسة منتدبة</option>
                                                <?php foreach ($etablissements as $etab_item): ?>
                                                    <option value="<?= $etab_item['id'] ?>"><?= htmlspecialchars($etab_item['nom_ar']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 2: Registration & System -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-circle-info"></i> شروط ونظام الدراسة
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="premium-label">الشهادة المستهدفة *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-graduation-cap"></i></span>
                                            <select name="diplome_vise" id="edit_offre_diplome" required class="premium-select form-select">
                                                <?php foreach ($qualifications_diplomes ?? [] as $qdip): ?>
                                                    <option value="<?= htmlspecialchars($qdip['code'] ?: $qdip['abr_fr']) ?>" data-ar="<?= htmlspecialchars($qdip['nom_ar']) ?>"><?= htmlspecialchars($qdip['abr_fr'] ?: $qdip['code']) ?> — <?= htmlspecialchars($qdip['nom_ar']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">المستوى المطلوب *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-user-gear"></i></span>
                                            <select name="niveau_requis" id="edit_offre_niveau" required class="premium-select form-select">
                                                <option value="bac">ثالثة ثانوي أو بكالوريا / Bac</option>
                                                <option value="bem">تعليم متوسط / BEM</option>
                                                <option value="3em_moy">الثالثة متوسط</option>
                                                <option value="sans_niveau">بدون مستوى</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">نمط التكوين *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-briefcase"></i></span>
                                            <select name="mode_formation" id="edit_offre_mode" required class="premium-select form-select" <?= ((int)(session('user.IDMode_formation') ?? 0) === 10) ? 'disabled' : '' ?>>
                                                <?php foreach ($modes_formation ?? [] as $mf): ?>
                                                    <?php if (((int)(session('user.IDMode_formation') ?? 0) === 10 && (int)$mf['id'] === 10) || (int)(session('user.IDMode_formation') ?? 0) !== 10): ?>
                                                        <option value="<?= (int)$mf['id'] ?>" <?= ((int)(session('user.IDMode_formation') ?? 0) === 10) ? 'selected' : '' ?>><?= htmlspecialchars($mf['nom_ar']) ?><?= $mf['nom_fr'] ? ' / ' . htmlspecialchars($mf['nom_fr']) : '' ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ((int)(session('user.IDMode_formation') ?? 0) === 10): ?>
                                                <input type="hidden" name="mode_formation" value="10">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Custom Specialty Name Panel — shown only when mode requires it -->
                                    <div class="col-12" id="edit_custom_spec_panel" style="display:none;">
                                        <div class="p-3 rounded-3 border border-2" style="border-color:#7c3aed!important; background:linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);">
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:#7c3aed; font-size:0.82rem;">
                                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> تسمية التخصص حسب الطلب
                                                </span>
                                                <small class="text-muted">— عندما يكون النمط "حسب الطلب"، يمكنك كتابة تسمية مخصصة للتخصص</small>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="premium-label" style="color:#7c3aed;">
                                                        <i class="fa-solid fa-language me-1"></i> تسمية التخصص بالعربية
                                                    </label>
                                                    <div class="input-group premium-input-group">
                                                        <span class="input-group-text" style="background:#ede9fe; border-color:#c4b5fd;"><i class="fa-solid fa-pen-nib" style="color:#7c3aed;"></i></span>
                                                        <input type="text" name="nom_spec_custom_ar" id="edit_nom_spec_custom_ar"
                                                            class="premium-input form-control"
                                                            placeholder="مثال: تقني في الطبخ التقليدي الجزائري..."
                                                            dir="rtl">
                                                    </div>
                                                    <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info me-1"></i> التسمية بالعربية كما ستظهر في الوثائق الرسمية</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="premium-label" style="color:#7c3aed;">
                                                        <i class="fa-solid fa-language me-1"></i> Intitulé de la spécialité (Français)
                                                    </label>
                                                    <div class="input-group premium-input-group">
                                                        <span class="input-group-text" style="background:#ede9fe; border-color:#c4b5fd;"><i class="fa-solid fa-pen-nib" style="color:#7c3aed;"></i></span>
                                                        <input type="text" name="nom_spec_custom_fr" id="edit_nom_spec_custom_fr"
                                                            class="premium-input form-control"
                                                            placeholder="Ex: Technicien en cuisine traditionnelle..."
                                                            dir="ltr">
                                                    </div>
                                                    <small class="text-muted d-block mt-1"><i class="fa-solid fa-circle-info me-1"></i> L'intitulé en français tel qu'il apparaîtra sur les documents officiels</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="premium-label">الطاقة الاستيعابية *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-users"></i></span>
                                            <input type="number" name="capacite" id="edit_offre_capacite" required class="premium-input form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="premium-label">صفة الفرع *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-code-fork"></i></span>
                                            <select name="type_branche" id="edit_offre_type_branche" required class="premium-select form-select">
                                                <option value="جديدة">جديدة (Nouvelle)</option>
                                                <option value="مجددة">مجددة (Renouvelée)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="premium-label">نظام الدراسة (Régime) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-clock"></i></span>
                                            <select name="regime_cours" id="edit_offre_regime" required class="premium-select form-select">
                                                <?php foreach ($regimes_cours ?? [] as $rc): ?>
                                                    <option value="<?= htmlspecialchars($rc['nom_ar']) ?>"><?= htmlspecialchars($rc['nom_ar']) ?><?= $rc['nom_fr'] ? ' / ' . htmlspecialchars($rc['nom_fr']) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="premium-label">نظام الإيواء (Hébergement) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-hotel"></i></span>
                                            <select name="hebergement" id="edit_offre_hebergement" required class="premium-select form-select">
                                                <?php foreach ($regimes_hebergement ?? [] as $rh): ?>
                                                    <option value="<?= htmlspecialchars($rh['nom_ar']) ?>"><?= htmlspecialchars($rh['nom_ar']) ?><?= $rh['nom_fr'] && $rh['nom_fr'] !== $rh['nom_ar'] ? ' / ' . htmlspecialchars($rh['nom_fr']) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 3: Dates & Schedule -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-calendar-days"></i> التواريخ والمواعيد
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="premium-label">تاريخ بدء الانتقاء والتوجيه (Sélection) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-user-check"></i></span>
                                            <input type="date" name="date_debut_selection" id="edit_offre_debut_selection" required class="premium-input form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="premium-label">تاريخ الفحص الطبي (Examen Médical)</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-heart-pulse"></i></span>
                                            <input type="date" name="date_examen_medical" id="edit_offre_examen_medical" class="premium-input form-control">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="premium-label">تاريخ زيارة الورشات (Visite)</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-eye"></i></span>
                                            <input type="date" name="date_visite_ateliers" id="edit_offre_visite_ateliers" class="premium-input form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">تاريخ البداية (Start) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-play"></i></span>
                                            <input type="date" name="date_debut" id="edit_offre_debut" required class="premium-input form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="premium-label">تاريخ النهاية (End) *</label>
                                        <div class="input-group premium-input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-stop"></i></span>
                                            <input type="date" name="date_fin" id="edit_offre_fin" required class="premium-input form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 4: Checklist -->
                            <div class="form-section-card">
                                <div class="form-section-title">
                                    <i class="fa-solid fa-check-double"></i> متطلبات الاعتماد والتحقق
                                </div>
                                <div class="row g-3 bg-light-subtle p-3 rounded-3 border">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                            <label class="form-check-label fw-bold small text-dark" for="edit_toggle_encadrement">التأطير البيداغوجي متوفر</label>
                                            <input class="form-check-input" type="checkbox" name="toggle_encadrement" id="edit_toggle_encadrement">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                            <label class="form-check-label fw-bold small text-dark" for="edit_toggle_programme">البرنامج الدراسي متوفر</label>
                                            <input class="form-check-input" type="checkbox" name="toggle_programme" id="edit_toggle_programme">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                            <label class="form-check-label fw-bold small text-dark" for="edit_toggle_equipement">التجهيز التقني متوفر</label>
                                            <input class="form-check-input" type="checkbox" name="toggle_equipement" id="edit_toggle_equipement">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Right Panel: Live Dynamic Preview Card -->
                        <div class="col-lg-4">
                            <div class="live-preview-card animate__animated animate__fadeIn">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="fw-bold mb-0 text-dark" style="font-family:'Cairo';"><i class="fa-solid fa-circle-nodes text-primary me-1 animate__animated animate__pulse animate__infinite"></i> معاينة مباشرة لعرض التكوين</h6>
                                    <span class="badge bg-warning text-dark preview-badge shadow-sm" style="font-size: 0.65rem;">تعديل العرض / Edit</span>
                                </div>
                                
                                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-primary-subtle text-primary preview-badge" id="edit_preview_code_badge">---</span>
                                        <span class="badge bg-purple text-white preview-badge" id="edit_preview_session_badge" style="background:#8307e4;">---</span>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-1 text-dark" id="edit_preview_spec_name" style="font-family:'Cairo'; font-size: 1.05rem;">اسم التخصص البيداغوجي</h5>
                                    
                                    <div class="preview-meta-row mt-2">
                                        <span class="text-muted"><i class="fa-solid fa-graduation-cap me-1"></i> الشهادة:</span>
                                        <span class="text-dark fw-bold" id="edit_preview_diplome">BTS</span>
                                    </div>
                                    <div class="preview-meta-row">
                                        <span class="text-muted"><i class="fa-solid fa-user-graduate me-1"></i> المستوى:</span>
                                        <span class="text-dark fw-bold" id="edit_preview_niveau">---</span>
                                    </div>
                                    <div class="preview-meta-row">
                                        <span class="text-muted"><i class="fa-solid fa-building me-1"></i> النمط:</span>
                                        <span class="text-dark fw-bold" id="edit_preview_mode">---</span>
                                    </div>
                                    <div class="preview-meta-row">
                                        <span class="text-muted"><i class="fa-solid fa-users me-1"></i> المقاعد:</span>
                                        <span class="text-dark fw-bold text-primary" id="edit_preview_capacite">30</span>
                                    </div>
                                </div>
                                
                                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                                    <h6 class="fw-bold mb-3 small text-dark"><i class="fa-solid fa-timeline text-primary me-1"></i> مخطط التواريخ والجدولة</h6>
                                    <div class="preview-timeline">
                                        <div class="preview-timeline-item active">
                                            <div class="fw-bold small text-dark">بدء الانتقاء والتوجيه</div>
                                            <div class="text-muted small" id="edit_preview_date_selection">-- / -- / ----</div>
                                        </div>
                                        <div class="preview-timeline-item active">
                                            <div class="fw-bold small text-dark">بداية التكوين</div>
                                            <div class="text-muted small" id="edit_preview_date_start">-- / -- / ----</div>
                                        </div>
                                        <div class="preview-timeline-item">
                                            <div class="fw-bold small text-dark">نهاية التكوين المتوقعة</div>
                                            <div class="text-muted small" id="edit_preview_date_end">-- / -- / ----</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="requirements-panel">
                                    <h6 class="fw-bold mb-2.5 small text-dark"><i class="fa-solid fa-shield-halved text-success me-1"></i> الاعتمادات المتوفرة</h6>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="requirement-pill" id="edit_preview_req_encadrement">
                                            <i class="fa-solid fa-circle-xmark"></i> التأطير البيداغوجي
                                        </div>
                                        <div class="requirement-pill" id="edit_preview_req_programme">
                                            <i class="fa-solid fa-circle-xmark"></i> البرنامج الدراسي
                                        </div>
                                        <div class="requirement-pill" id="edit_preview_req_equipement">
                                            <i class="fa-solid fa-circle-xmark"></i> التجهيز التقني
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold rounded-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-3 shadow" style="background: linear-gradient(135deg, #6c06be 0%, #8307e4 100%); border: none;">حفظ التغييرات وحفظ كمسودة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Rejection Reason -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="rejectionModalLabel"><i class="fa-solid fa-circle-exclamation me-2"></i> تحديد سبب رفض عرض التكوين</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectionForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="id" id="rejection_offre_id">
                <input type="hidden" name="action" value="rejeter">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">سبب الرفض بالتفصيل (Motif de rejet) *</label>
                        <textarea name="motif_rejet" required class="form-control rounded-3 border-light bg-light py-2" rows="4" placeholder="يرجى كتابة سبب الرفض بوضوح ليتمكن المركز من تعديل العرض وإعادة تقديمه..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:10px;">إلغاء</button>
                    <button type="submit" class="btn btn-danger px-4 py-2" style="border-radius:10px;">تأكيد الرفض وإرجاع العرض</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Dynamic table column selector functions
function toggleTableColumn(index, show) {
    const table = document.getElementById('offresTable');
    if (!table) return;
    const rows = table.rows;
    for (let i = 0; i < rows.length; i++) {
        const cell = rows[i].cells[index];
        if (cell) {
            if (show) {
                cell.style.setProperty('display', '', 'important');
            } else {
                cell.style.setProperty('display', 'none', 'important');
            }
        }
    }
    // Save to localStorage
    let hiddenCols = JSON.parse(localStorage.getItem('hidden_offres_cols') || '[]');
    if (show) {
        hiddenCols = hiddenCols.filter(c => c !== index);
    } else {
        if (!hiddenCols.includes(index)) {
            hiddenCols.push(index);
        }
    }
    localStorage.setItem('hidden_offres_cols', JSON.stringify(hiddenCols));
}

document.addEventListener('DOMContentLoaded', function() {
    const defaultHidden = [1, 3, 4, 7, 8, 9, 10, 11, 12, 13, 14, 15, 18, 19, 20, 21, 22];
    const savedHidden = localStorage.getItem('hidden_offres_cols');
    const hiddenCols = savedHidden ? JSON.parse(savedHidden) : defaultHidden;
    
    document.querySelectorAll('.col-toggle-checkbox').forEach(cb => {
        const idx = parseInt(cb.getAttribute('data-column-index'));
        const show = !hiddenCols.includes(idx);
        cb.checked = show;
        toggleTableColumn(idx, show);
    });
});

// Initialize Tooltips
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

/**
 * Determines if a given mode_id allows custom specialty naming.
 * The on-demand / à la demande modes allow free-form specialty names.
 * We use the select option text to detect "حسب الطلب" substring,
 * but also fall back to a known ID list if needed.
 */
function isOnDemandMode(modeId) {
    const select = document.getElementById('add_offre_mode') || document.getElementById('edit_offre_mode');
    if (select) {
        for (let i = 0; i < select.options.length; i++) {
            if (parseInt(select.options[i].value) === parseInt(modeId)) {
                const label = select.options[i].textContent || '';
                if (label.includes('حسب الطلب') || label.toLowerCase().includes('demande') || label.toLowerCase().includes('demand')) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Show or hide the custom specialty name panel based on the selected mode.
 * @param {string} prefix - 'add' or 'edit'
 * @param {number} modeId - numeric mode ID
 */
function toggleCustomSpecPanel(prefix, modeId) {
    const panel = document.getElementById(prefix + '_custom_spec_panel');
    if (!panel) return;
    const shouldShow = isOnDemandMode(modeId);
    panel.style.display = shouldShow ? 'block' : 'none';
    // Clear fields when hiding
    if (!shouldShow) {
        const arField = document.getElementById(prefix + '_nom_spec_custom_ar');
        const frField = document.getElementById(prefix + '_nom_spec_custom_fr');
        if (arField) arField.value = '';
        if (frField) frField.value = '';
    }
    // Animate in
    if (shouldShow) {
        panel.style.opacity = '0';
        panel.style.transform = 'translateY(-8px)';
        setTimeout(() => {
            panel.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            panel.style.opacity = '1';
            panel.style.transform = 'translateY(0)';
        }, 10);
    }
}

// Attach mode change listeners to both add and edit modal mode selects
document.addEventListener('DOMContentLoaded', function() {
    const addModeSelect = document.getElementById('add_offre_mode');
    if (addModeSelect) {
        addModeSelect.addEventListener('change', function() {
            toggleCustomSpecPanel('add', parseInt(this.value));
        });
        // Initialize on page load
        toggleCustomSpecPanel('add', parseInt(addModeSelect.value) || 1);
    }

    const editModeSelect = document.getElementById('edit_offre_mode');
    if (editModeSelect) {
        editModeSelect.addEventListener('change', function() {
            toggleCustomSpecPanel('edit', parseInt(this.value));
        });
    }
});

function editOffre(o) {
    document.getElementById('edit_offre_id').value = o.id;
    document.getElementById('edit_offre_code').value = o.code;
    document.getElementById('edit_offre_specialite_id').value = o.specialite_id;
    document.getElementById('edit_offre_diplome').value = o.diplome_vise;
    // Set mode using numeric IDMode_formation directly (DB-driven)
    document.getElementById('edit_offre_mode').value = o.mode_id || 1;
    document.getElementById('edit_offre_niveau').value = o.niveau;
    document.getElementById('edit_offre_capacite').value = o.places;
    document.getElementById('edit_offre_debut').value = o.date_debut;
    document.getElementById('edit_offre_fin').value = o.date_fin;
    
    // New fields
    document.getElementById('edit_offre_session_name').value = o.session_name || '';
    document.getElementById('edit_offre_delegue_id').value = o.etablissement_delegue_id || '';
    document.getElementById('edit_offre_visite_ateliers').value = o.date_visite_ateliers || '';
    document.getElementById('edit_offre_debut_selection').value = o.date_debut_selection || '';
    document.getElementById('edit_offre_examen_medical').value = o.date_examen_medical || '';
    document.getElementById('edit_offre_regime').value = o.regime_cours || 'فردي';
    document.getElementById('edit_offre_type_branche').value = o.type_branche || 'جديدة';
    document.getElementById('edit_offre_hebergement').value = o.hebergement || 'خارجي';
    document.getElementById('edit_toggle_encadrement').checked = parseInt(o.toggle_encadrement) === 1;
    document.getElementById('edit_toggle_programme').checked = parseInt(o.toggle_programme) === 1;
    document.getElementById('edit_toggle_equipement').checked = parseInt(o.toggle_equipement) === 1;
    
    // Session Hybrid Selection matching for edit
    const selectEl = document.getElementById('edit_offre_session_select');
    const customWrapper = document.getElementById('edit_offre_session_custom_wrapper');
    if (selectEl) {
        let matched = false;
        for (let i = 0; i < selectEl.options.length; i++) {
            if (selectEl.options[i].getAttribute('data-name') === o.session_name) {
                selectEl.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched) {
            selectEl.value = 'custom';
            if (customWrapper) customWrapper.classList.remove('d-none');
            const customInput = document.getElementById('edit_offre_session_name');
            if (customInput) {
                customInput.value = o.session_name || '';
                customInput.setAttribute('required', 'required');
            }
        } else {
            if (customWrapper) customWrapper.classList.add('d-none');
            const customInput = document.getElementById('edit_offre_session_name');
            if (customInput) {
                customInput.value = '';
                customInput.removeAttribute('required');
            }
        }
    }
    
    // Populate custom specialty name fields
    document.getElementById('edit_nom_spec_custom_ar').value = o.nom_spec_custom_ar || '';
    document.getElementById('edit_nom_spec_custom_fr').value = o.nom_spec_custom_fr || '';
    
    // Show/hide custom spec panel based on mode loaded
    toggleCustomSpecPanel('edit', parseInt(o.mode_id) || 1);
    
    // Trigger Live Preview Update
    if (typeof window.updateLivePreview === 'function') {
        window.updateLivePreview('edit');
    }
    
    var modal = new bootstrap.Modal(document.getElementById('editOffreModal'));
    modal.show();
}

function showRejectionModal(id, type) {
    document.getElementById('rejection_offre_id').value = id;
    var form = document.getElementById('rejectionForm');
    if (type === 'direction') {
        form.action = '/sig/dashboard/offres/valider-direction';
    } else {
        form.action = '/sig/dashboard/offres/valider-centrale';
    }
    var modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
    modal.show();
}

// ── Filter Panel Toggle ───────────────────────────────────────────────────
function toggleFilterPanel(btn) {
    var panel = document.getElementById('filterCollapse');
    var icon  = document.getElementById('filterToggleIcon');
    if (!panel) return;

    var isHidden = (panel.style.display === 'none' || panel.style.display === '');
    if (isHidden) {
        panel.style.display = 'block';
        if (icon) { icon.classList.add('text-primary'); }
        if (btn)  { btn.classList.add('bg-primary-subtle'); }
    } else {
        panel.style.display = 'none';
        if (icon) { icon.classList.remove('text-primary'); }
        if (btn)  { btn.classList.remove('bg-primary-subtle'); }
    }
}

// applyFilters: بحث نصي فقط في الجدول (باقي الفلاتر server-side)
function applyFilters() {
    var searchQuery = (document.getElementById('search_offre')?.value ?? '').toLowerCase().trim();
    var rows = document.querySelectorAll('#offresTable tbody tr');
    var visibleCount = 0;

    rows.forEach(function(row) {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        var matches = !searchQuery || row.textContent.toLowerCase().indexOf(searchQuery) > -1;
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
    });

    var emptyMsg = document.getElementById('offresEmptyMsg');
    if (emptyMsg) emptyMsg.style.display = visibleCount === 0 ? '' : 'none';
}


// ── Filter Panel Toggle (No Bootstrap) ─────────────────────────────
function offresToggleFilter() {
    var p = document.getElementById('filterCollapse');
    if (!p) return;
    // getComputedStyle to handle both inline style and CSS class display values
    var isVisible = window.getComputedStyle(p).display !== 'none';
    p.style.display = isVisible ? 'none' : 'block';
    var icon = document.getElementById('filterToggleIcon');
    if (icon) icon.style.color = isVisible ? '' : '#0d6efd';
}


document.addEventListener("DOMContentLoaded", function() {
    var urlParams = new URLSearchParams(window.location.search);

    // Restore search text
    var searchVal = urlParams.get('search');
    if (searchVal) {
        var input = document.getElementById('search_offre');
        if (input) { input.value = searchVal; applyFilters(); }
    }

    // Auto-open filter panel if any filter was active
    var hasActiveFilter = urlParams.get('filter_session') || urlParams.get('filter_etab') ||
                          urlParams.get('filter_mode')    || urlParams.get('filter_status') ||
                          urlParams.get('filter_open');
    if (hasActiveFilter) {
        var panel = document.getElementById('filterCollapse');
        var icon  = document.getElementById('filterToggleIcon');
        var btn   = document.getElementById('filterToggleBtn');
        if (panel) panel.style.display = 'block';
        if (icon)  icon.classList.add('text-primary');
        if (btn)   btn.classList.add('bg-primary-subtle');
    }
});
</script>

<script>
// Auto-calculation, dynamic preview, and field auto-filling for Add/Edit modals
document.addEventListener("DOMContentLoaded", function() {
    
    // Mapping arrays for friendly labels in preview card
    // Keys are numeric IDMode_formation values from the mode_formation DB table
    const modeLabels = {
        '1':  'حضوري أولي / Présentiel',
        '2':  'التكوين المهني المتواصل / Formation Continue',
        '3':  'الدروس المسائية / Cours du Soir',
        '5':  'تكوين المرأة الماكثة في البيت / Femme au Foyer',
        '7':  'التكوين عن طريق المعابر / Passerelle',
        '8':  'التعليم المهني / Enseignement professionnel',
        '9':  'تأهيلي أولي / Qualifiante Initiale',
        '10': 'تكوين عن طريق التمهين / Apprentissage'
    };
    
    const levelLabels = {
        'bac': 'ثالثة ثانوي أو بكالوريا',
        'bem': 'تعليم متوسط / BEM',
        '3em_moy': 'الثالثة متوسط',
        'sans_niveau': 'بدون مستوى'
    };
    
    // Session Selection Hybrid Switcher Helper
    function initSessionHybrid(modalPrefix) {
        const selectEl = document.getElementById(modalPrefix + '_offre_session_select');
        const customWrapper = document.getElementById(modalPrefix + '_offre_session_custom_wrapper');
        const customInput = document.getElementById(modalPrefix + '_offre_session_name');
        const debutInput = document.getElementById(modalPrefix + '_offre_debut');
        const selectionInput = document.getElementById(modalPrefix + '_offre_debut_selection');
        
        if (!selectEl || !customWrapper || !customInput) return;
        
        selectEl.addEventListener('change', function() {
            const val = this.value;
            if (val === 'custom') {
                customWrapper.classList.remove('d-none');
                customInput.setAttribute('required', 'required');
                customInput.classList.remove('is-invalid');
                
                // Auto-suggest immediately using selection date or today
                if (!customInput.value.trim()) {
                    suggestSessionName(modalPrefix);
                }
                
                // Focus the input so the user can edit or confirm
                setTimeout(() => customInput.focus(), 50);
                updateLivePreview(modalPrefix);
            } else {
                customWrapper.classList.add('d-none');
                customInput.removeAttribute('required');
                customInput.classList.remove('is-invalid');
                
                const opt = this.options[this.selectedIndex];
                if (opt) {
                    customInput.value = '';
                    if (opt.getAttribute('data-debut') && debutInput) {
                        debutInput.value = opt.getAttribute('data-debut');
                    }
                    if (opt.getAttribute('data-selection') && selectionInput) {
                        selectionInput.value = opt.getAttribute('data-selection');
                    }
                    updateEndDate(modalPrefix);
                    updateLivePreview(modalPrefix);
                }
            }
        });
    }

    // Auto-calculate Session Name based on Selection Date (if in custom mode)
    // Falls back to today's date when no selection date is set
    function suggestSessionName(modalPrefix) {
        const selectEl = document.getElementById(modalPrefix + '_offre_session_select');
        if (selectEl && selectEl.value !== 'custom') return;
        
        const selectionInput = document.getElementById(modalPrefix + '_offre_debut_selection');
        const sessionInput = document.getElementById(modalPrefix + '_offre_session_name');
        
        if (!sessionInput) return;
        
        // Use the selection date if available, otherwise fall back to today
        const selectionDateStr = selectionInput ? selectionInput.value : '';
        const date = selectionDateStr ? new Date(selectionDateStr) : new Date();
        
        if (!isNaN(date.getTime())) {
            const year = date.getFullYear();
            const month = date.getMonth() + 1; // 1-indexed
            
            let sessionName = '';
            if (month >= 1 && month <= 5) {
                sessionName = `دورة فيفري ${year}`;
            } else if (month >= 6 && month <= 9) {
                sessionName = `دورة سبتمبر ${year}`;
            } else if (month >= 10 && month <= 12) {
                sessionName = `دورة أكتوبر ${year}`;
            }
            
            if (sessionName) {
                sessionInput.value = sessionName;
                updateLivePreview(modalPrefix);
            }
        }
    }

    // Helper to calculate End Date based on Start Date and Specialty duration
    function updateEndDate(modalPrefix) {
        const specSelect = document.getElementById(modalPrefix + '_offre_specialite_id');
        const debutInput = document.getElementById(modalPrefix + '_offre_debut');
        const finInput = document.getElementById(modalPrefix + '_offre_fin');
        
        if (!specSelect || !debutInput || !finInput) return;
        
        const selectedOption = specSelect.options[specSelect.selectedIndex];
        if (!selectedOption) return;
        
        const semesters = parseInt(selectedOption.getAttribute('data-duree') || 0);
        const startDateStr = debutInput.value;
        
        if (semesters > 0 && startDateStr) {
            const startDate = new Date(startDateStr);
            if (!isNaN(startDate.getTime())) {
                const monthsToAdd = semesters * 6;
                const targetDate = new Date(startDate.getFullYear(), startDate.getMonth() + monthsToAdd, startDate.getDate());
                
                const yyyy = targetDate.getFullYear();
                const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
                const dd = String(targetDate.getDate()).padStart(2, '0');
                finInput.value = `${yyyy}-${mm}-${dd}`;
            }
        }
    }

    // Helper to auto-fill Diploma and Level based on specialty duration
    function updateDiplomeAndNiveau(modalPrefix) {
        const specSelect = document.getElementById(modalPrefix + '_offre_specialite_id');
        const diplomeSelect = document.getElementById(modalPrefix + '_offre_diplome');
        const niveauSelect = document.getElementById(modalPrefix + '_offre_niveau');
        
        if (!specSelect || !diplomeSelect || !niveauSelect) return;
        
        const selectedOption = specSelect.options[specSelect.selectedIndex];
        if (!selectedOption) return;
        
        const semesters = parseInt(selectedOption.getAttribute('data-duree') || 0);
        const specName = selectedOption.text || '';
        
        let inferredDiplome = '';
        let inferredDiplomeText = '';
        let inferredNiveau = '';
        let inferredNiveauText = '';
        
        if (specName.indexOf('تقني سامي') !== -1 || specName.toLowerCase().indexOf('ts') !== -1 || specName.toLowerCase().indexOf('supérieur') !== -1 || semesters === 5) {
            inferredDiplome = 'BTS';
            inferredDiplomeText = 'BTS (تقني سامي)';
            inferredNiveau = 'bac';
            inferredNiveauText = 'ثالثة ثانوي أو بكالوريا / Bac';
        } else if (specName.indexOf('تقني') !== -1 || specName.toLowerCase().indexOf('technicien') !== -1 || semesters === 4 || semesters === 3) {
            inferredDiplome = 'BP';
            inferredDiplomeText = 'BP (تحكم مهني)';
            inferredNiveau = 'bem';
            inferredNiveauText = 'تعليم متوسط / BEM';
        } else if (specName.indexOf('أهلية مهنية') !== -1 || specName.toLowerCase().indexOf('cap') !== -1 || semesters === 2 || semesters === 1) {
            inferredDiplome = 'CAP';
            inferredDiplomeText = 'CAP (أهلية مهنية)';
            inferredNiveau = 'sans_niveau';
            inferredNiveauText = 'بدون مستوى';
        } else {
            // Default fallback based on semesters duration
            if (semesters === 5) {
                inferredDiplome = 'BTS';
                inferredDiplomeText = 'BTS (تقني سامي)';
                inferredNiveau = 'bac';
                inferredNiveauText = 'ثالثة ثانوي أو بكالوريا / Bac';
            } else if (semesters >= 3) {
                inferredDiplome = 'BP';
                inferredDiplomeText = 'BP (تحكم مهني)';
                inferredNiveau = 'bem';
                inferredNiveauText = 'تعليم متوسط / BEM';
            } else if (semesters > 0) {
                inferredDiplome = 'CAP';
                inferredDiplomeText = 'CAP (أهلية مهنية)';
                inferredNiveau = 'sans_niveau';
                inferredNiveauText = 'بدون مستوى';
            }
        }
        
        if (inferredDiplome) {
            let diplomeExists = false;
            for (let i = 0; i < diplomeSelect.options.length; i++) {
                if (diplomeSelect.options[i].value === inferredDiplome) {
                    diplomeExists = true;
                    diplomeSelect.selectedIndex = i;
                    break;
                }
            }
            if (!diplomeExists) {
                const newOpt = document.createElement('option');
                newOpt.value = inferredDiplome;
                newOpt.text = inferredDiplomeText || inferredDiplome;
                diplomeSelect.appendChild(newOpt);
                diplomeSelect.value = inferredDiplome;
            }
        }
        
        if (inferredNiveau) {
            let niveauExists = false;
            for (let i = 0; i < niveauSelect.options.length; i++) {
                if (niveauSelect.options[i].value === inferredNiveau) {
                    niveauExists = true;
                    niveauSelect.selectedIndex = i;
                    break;
                }
            }
            if (!niveauExists) {
                const newOpt = document.createElement('option');
                newOpt.value = inferredNiveau;
                newOpt.text = inferredNiveauText || inferredNiveau;
                niveauSelect.appendChild(newOpt);
                niveauSelect.value = inferredNiveau;
            }
        }
    }

    // Live Dynamic Preview Card Updater
    window.updateLivePreview = function(modalPrefix) {
        const specSelect = document.getElementById(modalPrefix + '_offre_specialite_id');
        const codeInput = document.getElementById(modalPrefix + '_offre_code');
        const sessionInput = document.getElementById(modalPrefix + '_offre_session_name');
        const diplomeSelect = document.getElementById(modalPrefix + '_offre_diplome');
        const levelSelect = document.getElementById(modalPrefix + '_offre_niveau');
        const modeSelect = document.getElementById(modalPrefix + '_offre_mode');
        const capInput = document.getElementById(modalPrefix + '_offre_capacite');
        const dateSelInput = document.getElementById(modalPrefix + '_offre_debut_selection');
        const dateStartInput = document.getElementById(modalPrefix + '_offre_debut');
        const dateEndInput = document.getElementById(modalPrefix + '_offre_fin');
        
        const switchEnc = document.getElementById(modalPrefix + '_toggle_encadrement');
        const switchProg = document.getElementById(modalPrefix + '_toggle_programme');
        const switchEquip = document.getElementById(modalPrefix + '_toggle_equipement');
        
        const prSpecName = document.getElementById(modalPrefix + '_preview_spec_name');
        const prCode = document.getElementById(modalPrefix + '_preview_code_badge');
        const prSession = document.getElementById(modalPrefix + '_preview_session_badge');
        const prDiplome = document.getElementById(modalPrefix + '_preview_diplome');
        const prNiveau = document.getElementById(modalPrefix + '_preview_niveau');
        const prMode = document.getElementById(modalPrefix + '_preview_mode');
        const prCap = document.getElementById(modalPrefix + '_preview_capacite');
        const prDateSel = document.getElementById(modalPrefix + '_preview_date_selection');
        const prDateStart = document.getElementById(modalPrefix + '_preview_date_start');
        const prDateEnd = document.getElementById(modalPrefix + '_preview_date_end');
        
        const pillEnc = document.getElementById(modalPrefix + '_preview_req_encadrement');
        const pillProg = document.getElementById(modalPrefix + '_preview_req_programme');
        const pillEquip = document.getElementById(modalPrefix + '_preview_req_equipement');
        
        if (specSelect && prSpecName) {
            const opt = specSelect.options[specSelect.selectedIndex];
            prSpecName.textContent = opt && opt.value !== "" ? opt.textContent : 'اسم التخصص البيداغوجي';
        }
        
        if (codeInput && prCode) prCode.textContent = codeInput.value || '---';
        
        let sessionText = 'دورة غير محددة';
        const selectEl = document.getElementById(modalPrefix + '_offre_session_select');
        if (selectEl) {
            if (selectEl.value !== 'custom' && selectEl.selectedIndex > 0) {
                const opt = selectEl.options[selectEl.selectedIndex];
                sessionText = opt ? (opt.getAttribute('data-name') || opt.textContent.trim()) : 'دورة غير محددة';
            } else if (sessionInput && sessionInput.value) {
                sessionText = sessionInput.value;
            }
        }
        if (prSession) prSession.textContent = sessionText;
        
        if (diplomeSelect && prDiplome) prDiplome.textContent = diplomeSelect.value || '---';
        if (levelSelect && prNiveau) prNiveau.textContent = levelLabels[levelSelect.value] || '---';
        if (modeSelect && prMode) prMode.textContent = modeLabels[modeSelect.value] || '---';
        if (capInput && prCap) prCap.textContent = capInput.value || '0';
        
        if (dateSelInput && prDateSel) prDateSel.textContent = dateSelInput.value || '-- / -- / ----';
        if (dateStartInput && prDateStart) prDateStart.textContent = dateStartInput.value || '-- / -- / ----';
        if (dateEndInput && prDateEnd) prDateEnd.textContent = dateEndInput.value || '-- / -- / ----';
        
        if (pillEnc && switchEnc) {
            if (switchEnc.checked) {
                pillEnc.className = 'requirement-pill req-success';
                pillEnc.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i> التأطير البيداغوجي متوفر';
            } else {
                pillEnc.className = 'requirement-pill text-muted bg-light';
                pillEnc.innerHTML = '<i class="fa-solid fa-circle-xmark text-secondary me-1"></i> التأطير البيداغوجي غير متوفر';
            }
        }
        
        if (pillProg && switchProg) {
            if (switchProg.checked) {
                pillProg.className = 'requirement-pill req-success';
                pillProg.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i> البرنامج الدراسي متوفر';
            } else {
                pillProg.className = 'requirement-pill text-muted bg-light';
                pillProg.innerHTML = '<i class="fa-solid fa-circle-xmark text-secondary me-1"></i> البرنامج الدراسي غير متوفر';
            }
        }
        
        if (pillEquip && switchEquip) {
            if (switchEquip.checked) {
                pillEquip.className = 'requirement-pill req-success';
                pillEquip.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i> التجهيز التقني متوفر';
            } else {
                pillEquip.className = 'requirement-pill text-muted bg-light';
                pillEquip.innerHTML = '<i class="fa-solid fa-circle-xmark text-secondary me-1"></i> التجهيز التقني غير متوفر';
            }
        }
    };

    // Attach listeners for both modals: Add & Edit
    ['add', 'edit'].forEach(prefix => {
        const specSelect = document.getElementById(prefix + '_offre_specialite_id');
        const codeInput = document.getElementById(prefix + '_offre_code');
        const debutInput = document.getElementById(prefix + '_offre_debut');
        const selectionInput = document.getElementById(prefix + '_offre_debut_selection');
        const sessionInput = document.getElementById(prefix + '_offre_session_name');
        
        if (specSelect) {
            specSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt && codeInput) {
                    codeInput.value = opt.getAttribute('data-code') || '';
                }
                updateDiplomeAndNiveau(prefix);
                updateEndDate(prefix);
                updateLivePreview(prefix);
            });
        }
        
        if (debutInput) {
            debutInput.addEventListener('change', function() {
                updateEndDate(prefix);
                updateLivePreview(prefix);
            });
        }
        
        if (selectionInput) {
            selectionInput.addEventListener('change', function() {
                suggestSessionName(prefix);
                updateLivePreview(prefix);
            });
        }
        
        if (sessionInput) {
            sessionInput.addEventListener('input', function() {
                updateLivePreview(prefix);
            });
        }
        
        initSessionHybrid(prefix);
        
        const inputsToWatch = [
            prefix + '_offre_code',
            prefix + '_offre_diplome',
            prefix + '_offre_niveau',
            prefix + '_offre_mode',
            prefix + '_offre_capacite',
            prefix + '_offre_fin',
            prefix + '_toggle_encadrement',
            prefix + '_toggle_programme',
            prefix + '_toggle_equipement'
        ];
        
        inputsToWatch.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', () => updateLivePreview(prefix));
                el.addEventListener('input', () => updateLivePreview(prefix));
            }
        });
        
        updateLivePreview(prefix);
        
        // Form submit guard: ensure session_name is filled when custom is selected
        const formId = (prefix === 'add') ? 'addOffreForm' : 'editOffreForm';
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(e) {
                const sel = document.getElementById(prefix + '_offre_session_select');
                if (sel && sel.value === 'custom') {
                    const nameInput = document.getElementById(prefix + '_offre_session_name');
                    if (!nameInput || !nameInput.value.trim()) {
                        e.preventDefault();
                        if (nameInput) {
                            nameInput.classList.add('is-invalid');
                            nameInput.focus();
                            // Show inline error message
                            let errEl = document.getElementById(prefix + '_session_name_error');
                            if (!errEl) {
                                errEl = document.createElement('div');
                                errEl.id = prefix + '_session_name_error';
                                errEl.className = 'invalid-feedback d-block fw-bold';
                                nameInput.parentElement.after(errEl);
                            }
                            errEl.textContent = '⚠️ يرجى إدخال اسم الدورة التكوينية الجديدة (حقل إجباري)';
                            nameInput.addEventListener('input', function() {
                                nameInput.classList.remove('is-invalid');
                                if (errEl) errEl.textContent = '';
                            }, { once: true });
                        }
                        return false;
                    }
                }
            });
        }
    });
});
</script>




@endsection
