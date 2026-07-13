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
        <!-- Main Highlight -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white; position: relative; overflow: hidden;">
                <i class="fa-solid fa-chart-pie position-absolute" style="font-size: 150px; opacity: 0.05; top: -20px; left: -20px;"></i>
                <div class="card-body p-4 d-flex flex-column justify-content-center position-relative z-1">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي العروض المبرمجة</h6>
                    <h1 class="display-3 fw-bold mb-3 text-warning"><?= $stats['total_offres'] ?></h1>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-white-50 small">المقاعد المتاحة</span>
                        <span class="fw-bold fs-5" dir="ltr"><?= number_format($stats['total_places'], 0, ',', ' ') ?></span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: rgba(255,255,255,0.1);">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="col-12 col-xl-8">
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                <i class="fa-solid fa-users fs-4"></i>
                            </div>
                            <h6 class="text-muted fw-bold mb-1">إجمالي المسجلين</h6>
                            <h3 class="fw-bold mb-0 text-dark" dir="ltr"><?= number_format($stats['total_inscrits'], 0, ',', ' ') ?></h3>
                            <small class="text-primary fw-bold">منهم <span dir="ltr"><?= number_format($stats['inscrits_femmes'], 0, ',', ' ') ?></span> إناث</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="fa-solid fa-user-graduate fs-4"></i>
                            </div>
                            <h6 class="text-muted fw-bold mb-1" title="الناشطون = المستمرون في الأقسام (apprenant) + المسجلون المنتظرون — Active = Continuing + Registered">الطلاب النشطين</h6>
                            <h3 class="fw-bold mb-0 text-dark" dir="ltr"><?= number_format($stats['total_actifs'] ?? $stats['total_diplomes'] ?? 0, 0, ',', ' ') ?></h3>
                            <small class="text-success fw-bold">منهم <span dir="ltr"><?= number_format($stats['actifs_femmes'] ?? $stats['diplomes_femmes'] ?? 0, 0, ',', ' ') ?></span> إناث</small>
                        </div>
                    </div>
                </div>


                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                <i class="fa-solid fa-bullseye fs-4"></i>
                            </div>
                            <h6 class="text-muted fw-bold mb-1">نسبة التغطية</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $stats['taux_inscrits_prevu'] ?>%</h3>
                            <small class="text-warning fw-bold">من الطاقة الاستيعابية</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                        <div class="card-body p-4 text-center">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                <i class="fa-solid fa-chart-line fs-4"></i>
                            </div>
                            <h6 class="text-muted fw-bold mb-1">نسبة النشاط</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $stats['taux_diplomes_prevu'] ?>%</h3>
                            <small class="text-purple fw-bold" style="color:#8b5cf6;">معدل الطلاب للمقاعد</small>
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
                                <?php if(empty($dispositifs)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات متاحة</td></tr>
                                <?php else: ?>
                                    <?php foreach($dispositifs as $d): ?>
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
                                <?php if(empty($filieres)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات متاحة</td></tr>
                                <?php else: ?>
                                    <?php foreach($filieres as $f): 
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

    <!-- Detailed Offers Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i> تفاصيل الفروع والتخصصات المبرمجة</h5>
            
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
                <input type="text" id="search_offre" onkeyup="applyFilters()" class="form-control rounded-pill bg-light border-0 px-4" placeholder="بحث عن تخصص..." style="width: 250px;">
                <button class="btn btn-light rounded-circle" data-bs-toggle="collapse" data-bs-target="#filterCollapse" title="تصفية متقدمة"><i class="fa-solid fa-filter"></i></button>
            </div>
        </div>

        <!-- Collapsible Filters Bar -->
        <div class="collapse show no-print border-bottom border-light px-4 pt-3 pb-3 bg-light-subtle" id="filterCollapse">
            <div class="row g-3">
                <!-- Session Filter -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">الدورة التكوينية (Session)</label>
                    <select id="filter_session" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                        <option value="">كل الدورات</option>
                        <?php foreach($sessions as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (isset($_GET['filter_session']) && $_GET['filter_session'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['intitule_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Etablissement Filter -->
                <?php if ($is_wilaya || $is_central): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية (Etablissement)</label>
                    <select id="filter_etab" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                        <option value="">كل المؤسسات</option>
                        <?php foreach($etablissements as $e): ?>
                            <option value="<?= htmlspecialchars($e['id']) ?>" <?= (isset($_GET['filter_etab']) && $_GET['filter_etab'] == $e['id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['nom_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <!-- Mode Filter -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">نمط التكوين (Mode)</label>
                    <select id="filter_mode" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()" <?= ((int)(session('user.IDMode_formation') ?? 0) === 10) ? 'disabled' : '' ?>>
                        <?php if ((int)(session('user.IDMode_formation') ?? 0) === 10): ?>
                            <option value="apprentissage" selected>تمهين / Apprentissage</option>
                        <?php else: ?>
                            <option value="">كل الأنماط</option>
                            <option value="apprentissage" <?= (isset($_GET['filter_mode']) && $_GET['filter_mode'] === 'apprentissage') ? 'selected' : '' ?>>تمهين / Apprentissage</option>
                            <option value="presentiel" <?= (isset($_GET['filter_mode']) && $_GET['filter_mode'] === 'presentiel') ? 'selected' : '' ?>>حضوري / Présentiel</option>
                            <option value="residentiell" <?= (isset($_GET['filter_mode']) && $_GET['filter_mode'] === 'residentiell') ? 'selected' : '' ?>>إقامي / Résidentiel</option>
                            <option value="continu" <?= (isset($_GET['filter_mode']) && $_GET['filter_mode'] === 'continu') ? 'selected' : '' ?>>تكوين متواصل / Continu</option>
                        <?php endif; ?>
                    </select>
                </div>
                <!-- Status Filter -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">حالة العرض (Statut)</label>
                    <select id="filter_status" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                        <option value="">كل الحالات</option>
                        <option value="brouillon" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'brouillon') ? 'selected' : '' ?>>مسودة</option>
                        <option value="soumis" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'soumis') ? 'selected' : '' ?>>مرفوع للولاية</option>
                        <option value="valide_wilaya" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'valide_wilaya') ? 'selected' : '' ?>>مصادق عليه ولائيا</option>
                        <option value="valide_central" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'valide_central') ? 'selected' : '' ?>>مقبول مركزيا</option>
                        <option value="rejete_wilaya" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'rejete_wilaya') ? 'selected' : '' ?>>مرفوض ولائيا</option>
                        <option value="rejete_central" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'rejete_central') ? 'selected' : '' ?>>مرفوض مركزيا</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0 mt-2">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="offresTable" style="min-width: 1100px;">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">رمز العرض</th>
                            <th>الدورة التكوينية</th>
                            <th>الفرع / التخصص المهني</th>
                            <th>المؤسسة التكوينية</th>
                            <th class="text-center">المستوى المطلوب</th>
                            <th class="text-center">المدة</th>
                            <th class="text-center">المقاعد</th>
                            <th class="text-center">المسجلين</th>
                            <th class="text-center">الحالة</th>
                            <th class="pe-4 text-end no-print no-export">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($offres_detail)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">لا توجد عروض مبرمجة حالياً.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($offres_detail as $od): ?>
                            <tr style="transition: background 0.2s;">
                                <td class="ps-4">
                                    <span class="badge bg-light text-primary border border-primary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= $od['code'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($od['session_name'] ?: 'غير محددة') ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-1" style="font-size:0.95rem;"><?= htmlspecialchars($od['spec_ar']) ?></div>
                                    <div class="text-muted small" style="font-size:0.8rem;"><?= htmlspecialchars($od['spec_fr']) ?></div>
                                </td>
                                <td>
                                    <div class="text-muted fw-bold" style="font-size:0.8rem;"><i class="fa-solid fa-building-flag me-1"></i> <?= htmlspecialchars($od['centre']) ?></div>
                                    <?php if(!empty($od['centre_delegue'])): ?>
                                        <div class="small text-warning fw-bold mt-1"><i class="fa-solid fa-handshake me-1"></i> منتدبة: <?= htmlspecialchars($od['centre_delegue']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <span class="badge bg-secondary text-white rounded-pill px-3"><?= htmlspecialchars($od['niveau_txt']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center fw-bold text-dark"><?= $od['duree'] ?></td>
                                <td class="text-center fw-bold text-muted fs-6"><?= $od['places'] ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $od['inscrits'] >= $od['places'] ? 'bg-success' : ($od['inscrits'] > 0 ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill" style="font-size:0.85rem;">
                                        <?= $od['inscrits'] ?> / <?= $od['places'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $status = $od['statut_offre'];
                                    switch ($status) {
                                        case 'مسودة':
                                            echo '<span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-3"><i class="fa-solid fa-pen-ruler me-1"></i> مسودة</span>';
                                            break;
                                        case 'مرفوع للولاية':
                                            echo '<span class="badge bg-info-subtle text-info border border-info rounded-pill px-3"><i class="fa-solid fa-paper-plane me-1"></i> مرفوع للولاية</span>';
                                            break;
                                        case 'مصادق عليه ولائيا':
                                            echo '<span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3"><i class="fa-solid fa-circle-check me-1"></i> مصادق عليه ولائيا</span>';
                                            break;
                                        case 'مقبول مركزيا':
                                            echo '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-3"><i class="fa-solid fa-award me-1"></i> مقبول مركزيا</span>';
                                            break;
                                        case 'مرفوض ولائيا':
                                            echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($od['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض ولائيا <i class="fa-solid fa-circle-info ms-1 text-danger"></i></span>';
                                            break;
                                        case 'مرفوض مركزيا':
                                            echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($od['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض مركزيا <i class="fa-solid fa-circle-info ms-1 text-danger"></i></span>';
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
                                    <span class="d-none filter-mode-val"><?= $od['mode_formation'] ?></span>
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
// Initialize Tooltips
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
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

function applyFilters() {
    var searchQuery = document.getElementById('search_offre').value.toLowerCase();
    var sessionVal = document.getElementById('filter_session').value;
    var etabValSelect = document.getElementById('filter_etab');
    var etabVal = etabValSelect ? etabValSelect.value : '';
    var modeVal = document.getElementById('filter_mode').value;
    var statusVal = document.getElementById('filter_status').value;

    var url = new URL(window.location.href);
    var urlChanged = false;

    if (url.searchParams.get('filter_session') !== (sessionVal || null)) {
        if (sessionVal) url.searchParams.set('filter_session', sessionVal); else url.searchParams.delete('filter_session');
        urlChanged = true;
    }
    if (url.searchParams.get('filter_etab') !== (etabVal || null)) {
        if (etabVal) url.searchParams.set('filter_etab', etabVal); else url.searchParams.delete('filter_etab');
        urlChanged = true;
    }
    if (url.searchParams.get('filter_mode') !== (modeVal || null)) {
        if (modeVal) url.searchParams.set('filter_mode', modeVal); else url.searchParams.delete('filter_mode');
        urlChanged = true;
    }
    if (url.searchParams.get('filter_status') !== (statusVal || null)) {
        if (statusVal) url.searchParams.set('filter_status', statusVal); else url.searchParams.delete('filter_status');
        urlChanged = true;
    }

    if (urlChanged) {
        // Preserve search in URL when reloading if there's any
        if (searchQuery) url.searchParams.set('search', searchQuery); else url.searchParams.delete('search');
        window.location.href = url.toString();
        return;
    }

    // Client-side text search filtering on already loaded server-filtered rows
    var rows = document.querySelectorAll('#offresTable tbody tr');
    rows.forEach(function(row) {
        if (row.querySelector('td[colspan]')) return;
        var textContent = row.textContent.toLowerCase();
        var matchesSearch = textContent.indexOf(searchQuery) > -1;
        if (matchesSearch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Proactive restoration of search from URL on page load
document.addEventListener("DOMContentLoaded", function() {
    var urlParams = new URLSearchParams(window.location.search);
    var searchVal = urlParams.get('search');
    if (searchVal) {
        var input = document.getElementById('search_offre');
        if (input) {
            input.value = searchVal;
            applyFilters();
        }
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
