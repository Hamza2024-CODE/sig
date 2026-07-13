@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $pending_offres
 * @var array  $processed_offres
 * @var array  $stats
 * @var string $wilaya_name
 * @var string $role_code
 * @var array  $sessions
 * @var array  $etablissements
 */

$is_admin = ($role_code === 'admin');
$is_central = ($role_code === 'central' || $is_admin);
$is_wilaya = ($role_code === 'dfep');
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
body {
    background-color: #f1f5f9;
}
.card {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    border-radius: 20px !important;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02) !important;
}

/* Flat cohesive premium table layout */
.table-card-container {
    background: #ffffff;
    border-radius: 24px !important;
    padding: 1.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.02), 0 4px 6px -2px rgba(0, 0, 0, 0.01) !important;
}
#pendingTable, #historyTable {
    border-collapse: collapse !important;
    width: 100% !important;
    min-width: 100% !important;
}
#pendingTable thead th, #historyTable thead th {
    background-color: #f8fafc !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.8rem !important;
    border-bottom: 2px solid #e2e8f0 !important;
    padding: 16px 10px !important;
    text-align: center !important;
    vertical-align: middle !important;
}
#pendingTable tbody tr, #historyTable tbody tr {
    border-bottom: 1px solid #f1f5f9 !important;
    transition: all 0.2s ease-in-out !important;
}
#pendingTable tbody tr:hover, #historyTable tbody tr:hover {
    background-color: #fcfdfe !important;
    transform: scale(1.001) !important;
}
#pendingTable tbody td, #historyTable tbody td {
    padding: 16px 10px !important;
    font-size: 0.88rem !important;
    color: #334155 !important;
    border-bottom: 1px solid #f1f5f9 !important;
    text-align: center !important;
    vertical-align: middle !important;
}

/* Freeze/Sticky first columns for better readability */
#pendingTable th:nth-child(2), #pendingTable td:nth-child(2),
#historyTable th:first-child, #historyTable td:first-child {
    position: sticky !important;
    left: 0 !important;
    background-color: #ffffff !important;
    z-index: 10 !important;
    box-shadow: 4px 0 10px rgba(0,0,0,0.03) !important;
    text-align: right !important;
}
#pendingTable th:nth-child(3), #pendingTable td:nth-child(3),
#historyTable th:nth-child(2), #historyTable td:nth-child(2) {
    position: sticky !important;
    left: 110px !important;
    background-color: #ffffff !important;
    z-index: 10 !important;
    box-shadow: 4px 0 10px rgba(0,0,0,0.03) !important;
    text-align: right !important;
}
#pendingTable tbody tr:hover td:nth-child(2),
#pendingTable tbody tr:hover td:nth-child(3),
#historyTable tbody tr:hover td:first-child,
#historyTable tbody tr:hover td:nth-child(2) {
    background-color: #fcfdfe !important;
}

/* Interactive elements */
.form-control, .form-select {
    border-radius: 30px !important;
    border: 1.5px solid #e2e8f0 !important;
    padding: 0.65rem 1.25rem !important;
    background-color: #f8fafc !important;
    transition: all 0.2s ease-in-out;
}
.form-control:focus, .form-select:focus {
    background-color: #ffffff !important;
    border-color: #643edb !important;
    box-shadow: 0 0 0 4px rgba(100, 62, 219, 0.12) !important;
}
</style>

<div class="animate__animated animate__fadeIn">
    
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-stamp text-primary me-2"></i> المصادقة على عروض التكوين / Validation des Offres
            </h3>
            <p class="text-muted mb-0 small">
                <?php if ($is_wilaya): ?>
                    مديرية التكوين والتعليم المهنيين لولاية <?= htmlspecialchars($wilaya_name) ?> — مصادقة ولائية
                <?php else: ?>
                    الإدارة المركزية (الوزارة) — مصادقة مركزية نهائية
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Flash Messages -->
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

    <!-- KPI Dashboard Cards -->
    <div class="row g-4 mb-4">
        <!-- Pending Card -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; color: white !important; border: none !important;">
                <div class="card-body p-4 position-relative overflow-hidden">
                    <i class="fa-solid fa-clock position-absolute" style="font-size: 100px; opacity: 0.15; top: -10px; left: -10px; color: white !important;"></i>
                    <h6 class="fw-bold mb-1" style="color: rgba(255, 255, 255, 0.85) !important;">عروض قيد الدراسة والانتظار</h6>
                    <h1 class="display-4 fw-bold mb-0" style="color: white !important;"><?= $stats['total_pending'] ?></h1>
                    <p class="mb-0 small mt-2" style="color: rgba(255, 255, 255, 0.8) !important;"><i class="fa-solid fa-triangle-exclamation me-1"></i> تتطلب اتخاذ إجراء مصادقة أو رفض</p>
                </div>
            </div>
        </div>

        <!-- Approved Card -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: white !important; border: none !important;">
                <div class="card-body p-4 position-relative overflow-hidden">
                    <i class="fa-solid fa-circle-check position-absolute" style="font-size: 100px; opacity: 0.15; top: -10px; left: -10px; color: white !important;"></i>
                    <h6 class="fw-bold mb-1" style="color: rgba(255, 255, 255, 0.85) !important;">إجمالي العروض المقبولة</h6>
                    <h1 class="display-4 fw-bold mb-0" style="color: white !important;"><?= $stats['total_approved'] ?></h1>
                    <p class="mb-0 small mt-2" style="color: rgba(255, 255, 255, 0.8) !important;"><i class="fa-solid fa-check-double me-1"></i> تمت المصادقة عليها بنجاح</p>
                </div>
            </div>
        </div>

        <!-- Rejected Card -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important; color: white !important; border: none !important;">
                <div class="card-body p-4 position-relative overflow-hidden">
                    <i class="fa-solid fa-circle-xmark position-absolute" style="font-size: 100px; opacity: 0.15; top: -10px; left: -10px; color: white !important;"></i>
                    <h6 class="fw-bold mb-1" style="color: rgba(255, 255, 255, 0.85) !important;">العروض المرفوضة والمسترجعة</h6>
                    <h1 class="display-4 fw-bold mb-0" style="color: white !important;"><?= $stats['total_rejected'] ?></h1>
                    <p class="mb-0 small mt-2" style="color: rgba(255, 255, 255, 0.8) !important;"><i class="fa-solid fa-reply me-1"></i> أرسلت للمؤسسات لتعديلها وإعادة رفعها</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls and Actions Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 no-print mb-3">
        <!-- Tab pills -->
        <ul class="nav nav-pills gap-2" id="validationTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-4 fw-bold shadow-sm" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-panel" type="button" role="tab" aria-controls="pending-panel" aria-selected="true">
                    <i class="fa-solid fa-clock me-2"></i> قيد الانتظار (<?= count($pending_offres) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4 fw-bold shadow-sm" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab" aria-controls="history-panel" aria-selected="false">
                    <i class="fa-solid fa-box-archive me-2"></i> أرشيف المصادقة (<?= count($processed_offres) ?>)
                </button>
            </li>
        </ul>

        <!-- Global/Bulk action and Export controls -->
        <div class="d-flex align-items-center gap-2">
            <!-- Bulk Action Form -->
            <div id="bulkActionsContainer" class="d-flex align-items-center gap-2">
                <button type="button" onclick="executeBulkApprove()" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" id="btnBulkApprove" disabled>
                    <i class="fa-solid fa-check-double me-1"></i> مصادقة على المحدد (<span id="bulkCount">0</span>)
                </button>
            </div>

            <!-- Export Buttons -->
            <button onclick="exportTableToExcel('pendingTable', 'offres_attente_validation.xls')" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-file-excel me-1"></i> Excel
            </button>
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-print me-1"></i> طباعة
            </button>

            <!-- Column Selector Dropdown -->
            <div class="dropdown d-inline-block">
                <button class="btn btn-sm btn-light rounded-pill px-3 dropdown-toggle shadow-sm" type="button" id="columnSelectorBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="border: 1px solid #cbd5e1; font-weight:600; font-size:0.82rem;">
                    <i class="fa-solid fa-table-columns me-1 text-primary"></i> الأعمدة / Colonnes
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-3 fs-7" aria-labelledby="columnSelectorBtn" style="max-height: 400px; overflow-y: auto; border-radius: 12px; min-width: 280px; text-align: right; z-index: 1050;">
                    <h6 class="dropdown-header text-muted fw-bold p-0 mb-2 border-bottom pb-1">إظهار / إخفاء الأعمدة</h6>
                    <?php
                    $cols = [
                        2 => 'رمز التخصص (Code Spec)',
                        3 => 'نمط التكوين (Mode)',
                        4 => 'الدورة التكوينية (Session)',
                        5 => 'تاريخ التكوين (Dates)',
                        6 => 'الفرع والتخصص المهني (Spécialité)',
                        7 => 'المؤسسة التكوينية (Etablissement)',
                        8 => 'عدد الأفواج (Groupes)',
                        9 => 'المستوى (Niveau)',
                        10 => 'المستوى المطلوب (Niveau Requis)',
                        11 => 'الدوام (Régime)',
                        12 => 'صفة الفرع (Statut Branche)',
                        13 => 'التأطير (Encadrement)',
                        14 => 'البرنامج (Programme)',
                        15 => 'التجهيزات (Equipement)',
                        16 => 'المدة (Durée)',
                        17 => 'المقاعد (Capacité)',
                        18 => 'المسجلين (Inscrits)',
                        19 => 'منهم إناث (Inscrits F)',
                        20 => 'الناجحين (Diplômés)',
                        21 => 'منهم إناث (Diplômés F)'
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
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="card table-card-container border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <!-- Advanced Filter Collapse -->
            <div class="no-print border-top border-light pt-3 pb-2 mt-1">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <input type="text" id="search_offre" onkeyup="applyFilters()" class="form-control rounded-pill bg-light border-0 px-4" placeholder="بحث سريع عن تخصص أو مؤسسة...">
                    </div>
                    <div class="col-12 col-md-3">
                        <select id="filter_session" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                            <option value="">كل الدورات التكوينية</option>
                            <?php foreach($sessions as $s): ?>
                                <option value="<?= htmlspecialchars($s['intitule_ar']) ?>"><?= htmlspecialchars($s['intitule_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($is_central): ?>
                    <div class="col-12 col-md-3">
                        <select id="filter_etab" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                            <option value="">كل المؤسسات التكوينية</option>
                            <?php foreach($etablissements as $e): ?>
                                <option value="<?= htmlspecialchars($e['nom_ar']) ?>"><?= htmlspecialchars($e['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12 col-md-3">
                        <select id="filter_mode" class="form-select rounded-pill border-light bg-light" onchange="applyFilters()">
                            <option value="">كل أنماط التكوين</option>
                            <option value="تمهين">تمهين / Apprentissage</option>
                            <option value="حضوري">حضوري / Présentiel</option>
                            <option value="متواصل">تكوين متواصل / Continu</option>
                            <option value="مسائي">تكوين مسائي / Soir</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0 mt-2">
            <div class="tab-content" id="validationTabContent">
                
                <!-- 1. PENDING OFFERS PANEL -->
                <div class="tab-pane fade show active" id="pending-panel" role="tabpanel" aria-labelledby="pending-tab">
                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                        <table class="table table-hover align-middle mb-0" id="pendingTable" style="width: 100%; table-layout: auto;">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-3 no-export no-print text-center" style="width: 40px;">
                                        <input type="checkbox" id="selectAllPending" onchange="toggleSelectAllPending(this.checked)" class="form-check-input">
                                    </th>
                                    <th style="min-width:110px;">رمز العرض</th>
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
                                    <th class="pe-4 text-end no-print no-export" style="min-width:180px;">اتخاذ القرار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pending_offres)): ?>
                                    <tr>
                                        <td colspan="23" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-circle-check text-success fs-2 mb-3 d-block"></i>
                                            لا توجد عروض معلقة بانتظار المصادقة حالياً.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($pending_offres as $o): ?>
                                    <tr style="transition: background 0.2s;">
                                        <td class="ps-3 no-export no-print text-center">
                                            <input type="checkbox" class="row-select-checkbox form-check-input" value="<?= $o['id'] ?>" onchange="updateBulkButtons()">
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-primary border border-primary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= $o['code'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border border-secondary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= htmlspecialchars($o['spec_code'] ?: '—') ?></span>
                                        </td>
                                        <td class="text-center font-mode">
                                            <?php
                                            $modeId = (int)($o['mode_id'] ?? 1);
                                            $modeName = $o['mode_formation'] ?? '';
                                            $modeBadgeClass = match($modeId) {
                                                10 => 'bg-warning text-dark',
                                                2  => 'bg-info text-white',
                                                3  => 'bg-secondary text-white',
                                                default => 'bg-primary text-white'
                                            };
                                            $modeIcon = match($modeId) {
                                                10 => 'fa-hammer',
                                                2  => 'fa-rotate',
                                                3  => 'fa-moon',
                                                default => 'fa-chalkboard-user'
                                            };
                                            ?>
                                            <span class="badge <?= $modeBadgeClass ?> rounded-pill px-2.5" style="font-size:0.75rem; white-space:nowrap;">
                                                <i class="fa-solid <?= $modeIcon ?> me-1"></i><?= htmlspecialchars($modeName) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark font-session" style="font-size:0.85rem;"><?= htmlspecialchars($o['session_name'] ?: 'غير محددة') ?></div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-muted" style="font-size:0.75rem; white-space: nowrap;">
                                                <div>من: <?= $o['date_debut'] ? date('Y/m/d', strtotime($o['date_debut'])) : '—' ?></div>
                                                <div>إلى: <?= $o['date_fin'] ? date('Y/m/d', strtotime($o['date_fin'])) : '—' ?></div>
                                            </div>
                                        </td>
                                        <td style="max-width:260px; word-wrap:break-word; overflow-wrap:break-word; white-space:normal;">
                                            <?php
                                            $hasCustomName = !empty($o['nom_spec_custom_ar']) || !empty($o['nom_spec_custom_fr']);
                                            $dispAr = $hasCustomName && !empty($o['nom_spec_custom_ar']) ? $o['nom_spec_custom_ar'] : $o['spec_ar'];
                                            $dispFr = $hasCustomName && !empty($o['nom_spec_custom_fr']) ? $o['nom_spec_custom_fr'] : $o['spec_fr'];
                                            ?>
                                            <div class="fw-bold text-dark mb-1 font-spec" style="font-size:0.88rem; line-height:1.4; word-break:break-word;">
                                                <?= htmlspecialchars($dispAr) ?>
                                                <?php if ($hasCustomName): ?>
                                                    <span class="badge bg-purple-subtle text-purple border rounded-pill ms-1" style="font-size:0.65rem; background:#ede9fe; color:#7c3aed;"><i class="fa-solid fa-star fa-xs"></i> مخصص</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size:0.78rem; line-height:1.35; word-break:break-word; white-space:normal; overflow-wrap:break-word;"><?= htmlspecialchars($dispFr) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-muted fw-bold font-etab" style="font-size:0.8rem;"><i class="fa-solid fa-building-flag me-1"></i> <?= htmlspecialchars($o['centre']) ?></div>
                                            <?php if(!empty($o['centre_delegue'])): ?>
                                                <div class="small text-warning fw-bold mt-1"><i class="fa-solid fa-handshake me-1"></i> منتدبة: <?= htmlspecialchars($o['centre_delegue']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-dark fs-6"><?= $o['nbr_groupe'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-white rounded-pill px-3"><?= htmlspecialchars($o['level_name'] ?: '—') ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <span class="badge bg-secondary text-white rounded-pill px-3"><?= htmlspecialchars($o['niveau_txt'] ?? '—') ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border border-light-subtle rounded-pill px-2"><?= htmlspecialchars($o['regime_cours'] ?: '—') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2"><?= htmlspecialchars($o['type_branche'] ?: '—') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?= $o['toggle_encadrement'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $o['toggle_programme'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $o['toggle_equipement'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                        </td>
                                        <td class="text-center fw-bold text-dark" style="white-space: nowrap;"><?= $o['duree'] ?></td>
                                        <td class="text-center fw-bold text-muted fs-6"><?= $o['places'] ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $o['inscrits'] >= $o['places'] ? 'bg-success' : ($o['inscrits'] > 0 ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill" style="font-size:0.85rem;">
                                                <?= $o['inscrits'] ?> / <?= $o['places'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold text-dark"><?= $o['inscrits_females'] ?></td>
                                        <td class="text-center fw-bold text-success fs-6"><?= $o['laureats'] ?></td>
                                        <td class="text-center fw-bold text-pink fs-6" style="color: #ec4899;"><?= $o['laureats_females'] ?></td>
                                        <td class="pe-4 text-end no-print no-export">
                                            <div class="d-flex justify-content-end gap-1.5">
                                                <!-- Approve button -->
                                                <form action="/dashboard/offres/<?= $is_wilaya ? 'valider-direction' : 'valider-centrale' ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من المصادقة والقبول على هذا العرض؟')">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                                    <input type="hidden" name="action" value="approuver">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold shadow-sm">
                                                        <i class="fa-solid fa-circle-check me-1"></i> مصادقة
                                                    </button>
                                                </form>

                                                <!-- Reject trigger button -->
                                                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold shadow-sm" onclick="showRejectionModal(<?= $o['id'] ?>)">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i> رفض
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- 2. HISTORY / PROCESSED OFFERS PANEL -->
                <div class="tab-pane fade" id="history-panel" role="tabpanel" aria-labelledby="history-tab">
                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                        <table class="table table-hover align-middle mb-0" id="historyTable" style="width: 100%; table-layout: auto;">
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
                                    <th class="pe-4 text-center" style="min-width:180px;">الحالة والملاحظة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($processed_offres)): ?>
                                    <tr>
                                        <td colspan="22" class="text-center py-5 text-muted">
                                            لا توجد عروض في سجل الأرشيف والعمليات السابقة.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($processed_offres as $o): ?>
                                    <tr style="transition: background 0.2s;">
                                        <td class="ps-4">
                                            <span class="badge bg-light text-primary border border-primary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= $o['code'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border border-secondary fw-bold" style="font-family:'Outfit'; font-size:0.8rem;"><?= htmlspecialchars($o['spec_code'] ?: '—') ?></span>
                                        </td>
                                        <td class="text-center font-mode">
                                            <?php
                                            $modeId = (int)($o['mode_id'] ?? 1);
                                            $modeName = $o['mode_formation'] ?? '';
                                            $modeBadgeClass = match($modeId) {
                                                10 => 'bg-warning text-dark',
                                                2  => 'bg-info text-white',
                                                3  => 'bg-secondary text-white',
                                                default => 'bg-primary text-white'
                                            };
                                            $modeIcon = match($modeId) {
                                                10 => 'fa-hammer',
                                                2  => 'fa-rotate',
                                                3  => 'fa-moon',
                                                default => 'fa-chalkboard-user'
                                            };
                                            ?>
                                            <span class="badge <?= $modeBadgeClass ?> rounded-pill px-2.5" style="font-size:0.75rem; white-space:nowrap;">
                                                <i class="fa-solid <?= $modeIcon ?> me-1"></i><?= htmlspecialchars($modeName) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark font-session" style="font-size:0.85rem;"><?= htmlspecialchars($o['session_name'] ?: 'غير محددة') ?></div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-muted" style="font-size:0.75rem; white-space: nowrap;">
                                                <div>من: <?= $o['date_debut'] ? date('Y/m/d', strtotime($o['date_debut'])) : '—' ?></div>
                                                <div>إلى: <?= $o['date_fin'] ? date('Y/m/d', strtotime($o['date_fin'])) : '—' ?></div>
                                            </div>
                                        </td>
                                        <td style="max-width:260px; word-wrap:break-word; overflow-wrap:break-word; white-space:normal;">
                                            <?php
                                            $hasCustomName = !empty($o['nom_spec_custom_ar']) || !empty($o['nom_spec_custom_fr']);
                                            $dispAr = $hasCustomName && !empty($o['nom_spec_custom_ar']) ? $o['nom_spec_custom_ar'] : $o['spec_ar'];
                                            $dispFr = $hasCustomName && !empty($o['nom_spec_custom_fr']) ? $o['nom_spec_custom_fr'] : $o['spec_fr'];
                                            ?>
                                            <div class="fw-bold text-dark mb-1 font-spec" style="font-size:0.88rem; line-height:1.4; word-break:break-word;">
                                                <?= htmlspecialchars($dispAr) ?>
                                                <?php if ($hasCustomName): ?>
                                                    <span class="badge bg-purple-subtle text-purple border rounded-pill ms-1" style="font-size:0.65rem; background:#ede9fe; color:#7c3aed;"><i class="fa-solid fa-star fa-xs"></i> مخصص</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size:0.78rem; line-height:1.35; word-break:break-word; white-space:normal; overflow-wrap:break-word;"><?= htmlspecialchars($dispFr) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-muted fw-bold font-etab" style="font-size:0.8rem;"><i class="fa-solid fa-building-flag me-1"></i> <?= htmlspecialchars($o['centre']) ?></div>
                                            <?php if(!empty($o['centre_delegue'])): ?>
                                                <div class="small text-warning fw-bold mt-1"><i class="fa-solid fa-handshake me-1"></i> منتدبة: <?= htmlspecialchars($o['centre_delegue']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-dark fs-6"><?= $o['nbr_groupe'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-white rounded-pill px-3"><?= htmlspecialchars($o['level_name'] ?: '—') ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <span class="badge bg-secondary text-white rounded-pill px-3"><?= htmlspecialchars($o['niveau_txt'] ?? '—') ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border border-light-subtle rounded-pill px-2"><?= htmlspecialchars($o['regime_cours'] ?: '—') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2"><?= htmlspecialchars($o['type_branche'] ?: '—') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?= $o['toggle_encadrement'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $o['toggle_programme'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= $o['toggle_equipement'] ? '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="fa-solid fa-check"></i></span>' : '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="fa-solid fa-xmark"></i></span>' ?>
                                        </td>
                                        <td class="text-center fw-bold text-dark" style="white-space: nowrap;"><?= $o['duree'] ?></td>
                                        <td class="text-center fw-bold text-muted fs-6"><?= $o['places'] ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $o['inscrits'] >= $o['places'] ? 'bg-success' : ($o['inscrits'] > 0 ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill" style="font-size:0.85rem;">
                                                <?= $o['inscrits'] ?> / <?= $o['places'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold text-dark"><?= $o['inscrits_females'] ?></td>
                                        <td class="text-center fw-bold text-success fs-6"><?= $o['laureats'] ?></td>
                                        <td class="text-center fw-bold text-pink fs-6" style="color: #ec4899;"><?= $o['laureats_females'] ?></td>
                                        <td class="text-center">
                                            <?php
                                            $status = $o['statut_offre'];
                                            if ($status === 'مقبول مركزيا') {
                                                echo '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-3"><i class="fa-solid fa-award me-1"></i> مقبول مركزيا</span>';
                                            } elseif ($status === 'مصادق عليه ولائيا') {
                                                echo '<span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3"><i class="fa-solid fa-circle-check me-1"></i> مصادق عليه ولائيا</span>';
                                            } elseif ($status === 'مرفوض ولائيا') {
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($o['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض ولائيا <i class="fa-solid fa-circle-info ms-1 text-danger"></i></span>';
                                            } elseif ($status === 'مرفوض مركزيا') {
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 cursor-pointer" data-bs-toggle="tooltip" data-bs-html="true" title="سبب الرفض: ' . htmlspecialchars($o['motif_rejet'] ?? '') . '"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض مركزيا <i class="fa-solid fa-circle-info ms-1 text-danger"></i></span>';
                                            } else {
                                                echo '<span class="badge bg-secondary rounded-pill px-3">' . htmlspecialchars($status) . '</span>';
                                            }
                                            ?>
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
</div>

<!-- Modal: Rejection Reason -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="rejectionModalLabel">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> تحديد سبب رفض عرض التكوين
                </h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectionForm" method="POST" action="/dashboard/offres/<?= $is_wilaya ? 'valider-direction' : 'valider-centrale' ?>">
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
    ['pendingTable', 'historyTable'].forEach(function(tableId) {
        const table = document.getElementById(tableId);
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
    });
    // Save to localStorage
    let hiddenCols = JSON.parse(localStorage.getItem('hidden_validation_cols') || '[]');
    if (show) {
        hiddenCols = hiddenCols.filter(c => c !== index);
    } else {
        if (!hiddenCols.includes(index)) {
            hiddenCols.push(index);
        }
    }
    localStorage.setItem('hidden_validation_cols', JSON.stringify(hiddenCols));
}

document.addEventListener('DOMContentLoaded', function() {
    const defaultHidden = [2, 4, 5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 19, 20, 21];
    const savedHidden = localStorage.getItem('hidden_validation_cols');
    const hiddenCols = savedHidden ? JSON.parse(savedHidden) : defaultHidden;
    
    document.querySelectorAll('.col-toggle-checkbox').forEach(cb => {
        const idx = parseInt(cb.getAttribute('data-column-index'));
        const show = !hiddenCols.includes(idx);
        cb.checked = show;
        toggleTableColumn(idx, show);
    });
});

function toggleSelectAllPending(checked) {
    document.querySelectorAll('#pendingTable tbody .row-select-checkbox').forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = checked;
        }
    });
    updateBulkButtons();
}

function updateBulkButtons() {
    const selectedCbs = document.querySelectorAll('#pendingTable tbody .row-select-checkbox:checked');
    const count = selectedCbs.length;
    
    const btnApprove = document.getElementById('btnBulkApprove');
    if (btnApprove) {
        btnApprove.disabled = count === 0;
    }
    
    const labelCount = document.getElementById('bulkCount');
    if (labelCount) {
        labelCount.textContent = count;
    }
}

function executeBulkApprove() {
    const selectedCbs = document.querySelectorAll('#pendingTable tbody .row-select-checkbox:checked');
    const ids = Array.from(selectedCbs).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    if (confirm('هل أنت متأكد من المصادقة والقبول على ' + ids.length + ' عروض تكوين محددة؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/dashboard/offres/<?= $is_wilaya ? "valider-direction" : "valider-centrale" ?>';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= csrf_token() ?? "" ?>';
        form.appendChild(csrfInput);
        
        // Add Action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'approuver';
        form.appendChild(actionInput);
        
        // Add IDs
        const idsInput = document.createElement('input');
        idsInput.type = 'hidden';
        idsInput.name = 'ids';
        idsInput.value = ids.join(',');
        form.appendChild(idsInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize Tooltips
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function showRejectionModal(id) {
    document.getElementById('rejection_offre_id').value = id;
    var modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
    modal.show();
}

function applyFilters() {
    var searchQuery = document.getElementById('search_offre').value.toLowerCase();
    var sessionVal = document.getElementById('filter_session').value;
    var etabValSelect = document.getElementById('filter_etab');
    var etabVal = etabValSelect ? etabValSelect.value : '';
    var modeVal = document.getElementById('filter_mode').value;

    // Filter both pending and history tables
    ['pendingTable', 'historyTable'].forEach(function(tableId) {
        var table = document.getElementById(tableId);
        if (!table) return;
        var rows = table.querySelectorAll('tbody tr');

        rows.forEach(function(row) {
            if (row.querySelector('td[colspan]')) return;

            var textContent = row.textContent.toLowerCase();
            
            var rowSession = row.querySelector('.font-session') ? row.querySelector('.font-session').textContent : '';
            var rowEtab = row.querySelector('.font-etab') ? row.querySelector('.font-etab').textContent : '';
            var rowMode = row.querySelector('.font-mode') ? row.querySelector('.font-mode').textContent : '';

            var matchesSearch = textContent.indexOf(searchQuery) > -1;
            var matchesSession = sessionVal === '' || rowSession.indexOf(sessionVal) > -1;
            var matchesEtab = etabVal === '' || rowEtab.indexOf(etabVal) > -1;
            var matchesMode = modeVal === '' || rowMode.indexOf(modeVal) > -1;

            if (matchesSearch && matchesSession && matchesEtab && matchesMode) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}
</script>

@endsection
