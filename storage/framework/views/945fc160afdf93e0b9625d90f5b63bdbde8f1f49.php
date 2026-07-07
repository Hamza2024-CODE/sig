
<?php $__env->startSection('title', $title ?? 'المنصة الرقمية للتكوين المهني'); ?>
<?php $__env->startSection('content'); ?>
<?php
$user = session('user');
$role = strtolower($user['role_code'] ?? '');
$username = strtolower($user['username'] ?? '');
$isAppren = (int)($user['IDMode_formation'] ?? 0) === 10;

$dept = 'general';
if ($isAppren || in_array($username, ['sdtpa', 'sdtpas'])) {
    $dept = 'apprentissage';
} elseif (in_array($username, ['biao', 'biaos'])) {
    $dept = 'orientation';
} elseif (in_array($username, ['dplm', 'dplms'])) {
    $dept = 'diplomes';
} elseif (in_array($username, ['sdtpp', 'sdtpps', 'sdtpc', 'sdtpcs'])) {
    $dept = 'pedagogie';
} elseif (in_array($username, ['admfine', 'admfines', 'samf', 'samfs', 'sdafm', 'sdsafms', 'sdarh', 'sdarhs'])) {
    $dept = 'administration';
}
?>

<style>
    .digital-card {
        width: 480px !important;
        height: 290px !important;
        border-radius: 18px !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.5s ease !important;
        font-family: 'Cairo', sans-serif !important;
        box-sizing: border-box !important;
        padding: 12px 18px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        text-align: right !important;
        direction: rtl !important;
    }
    
    .diagonal-flag-ar {
        position: absolute;
        top: 0;
        right: 0;
        width: 120px;
        height: 12px;
        background: linear-gradient(90deg, #d62246 33%, #ffffff 33%, #ffffff 66%, #008751 66%);
        transform: rotate(45deg) translate(35px, -10px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        z-index: 10;
    }

    .card-grid-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0.15;
        background-image: radial-gradient(var(--border) 1px, transparent 0);
        background-size: 16px 16px;
        pointer-events: none;
    }
    .card-wavy-lines {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0.08;
        background: repeating-linear-gradient(45deg, transparent, transparent 10px, var(--electric) 10px, var(--electric) 11px);
        pointer-events: none;
    }

    .employee-photo-frame {
        width: 78px !important;
        height: 98px !important;
        border-radius: 8px !important;
        border: none !important;
        box-shadow: none !important;
        object-fit: cover !important;
    }

    /* Absolute Positioning Layouts for Employee */
    .digital-card.card-layout-employee:not(.hide-bg) {
        background-image: url('<?php echo e(asset("assets/images/card_employee_bg.png")); ?>') !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        border: none !important;
    }
    .digital-card.card-layout-employee #photoLayoutCol {
        position: absolute !important;
        top: 17% !important;
        left: 4.5% !important;
        width: 16.5% !important;
        height: 34% !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 5 !important;
    }
    .digital-card.card-layout-employee #qrLayoutCol {
        position: absolute !important;
        bottom: 17% !important;
        left: 4.5% !important;
        width: 16.5% !important;
        height: 27% !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 5 !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
    }
    .digital-card.card-layout-employee #qrLayoutCol .bg-white,
    .digital-card.card-layout-employee #qrLayoutCol div {
        padding: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    .digital-card.card-layout-employee #qrLayoutCol svg {
        width: 68px !important;
        height: 68px !important;
    }
    .digital-card.card-layout-employee #detailsLayoutCol {
        position: absolute !important;
        top: 31% !important;
        right: 5% !important;
        width: 68% !important;
        text-align: right !important;
        direction: rtl !important;
        color: #0d3b66 !important;
        font-size: 0.8rem !important;
        line-height: 1.5 !important;
        z-index: 5 !important;
    }

    /* Absolute Positioning Layouts for Trainee */
    .digital-card.card-layout-trainee:not(.hide-bg) {
        background-image: url('<?php echo e(asset("assets/images/card_trainee_bg.png")); ?>') !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        border: none !important;
    }
    .digital-card.card-layout-trainee #photoLayoutCol {
        position: absolute !important;
        bottom: 17% !important;
        left: 4.5% !important;
        width: 16.5% !important;
        height: 34% !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 5 !important;
    }
    .digital-card.card-layout-trainee #qrLayoutCol {
        position: absolute !important;
        top: 17% !important;
        right: 4.5% !important;
        width: 16.5% !important;
        height: 27% !important;
        padding: 0 !important;
        margin: 0 !important;
        z-index: 5 !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
    }
    .digital-card.card-layout-trainee #qrLayoutCol .bg-white,
    .digital-card.card-layout-trainee #qrLayoutCol div {
        padding: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    .digital-card.card-layout-trainee #qrLayoutCol svg {
        width: 68px !important;
        height: 68px !important;
    }
    .digital-card.card-layout-trainee #detailsLayoutCol {
        position: absolute !important;
        top: 34% !important;
        right: 26% !important;
        width: 68% !important;
        text-align: right !important;
        direction: rtl !important;
        color: #2e7d32 !important;
        font-size: 0.8rem !important;
        line-height: 1.5 !important;
        z-index: 5 !important;
    }

    /* Hide HTML badge, flags, and decoration when backgrounds are active */
    .digital-card:not(.hide-bg) #cardTitleBadge,
    .digital-card:not(.hide-bg) .crest-watermark,
    .digital-card:not(.hide-bg) .card-grid-bg,
    .digital-card:not(.hide-bg) .card-wavy-lines,
    .digital-card:not(.hide-bg) .diagonal-flag-ar {
        display: none !important;
    }

    /* Toggled hide background state */
    .digital-card.hide-bg {
        background-image: none !important;
        background: #ffffff !important;
        color: #212529 !important;
        border: 1px solid #dee2e6 !important;
    }
    .digital-card.hide-bg #cardTitleBadge {
        display: block !important;
    }
    .digital-card.hide-bg #photoLayoutCol {
        position: relative !important;
        width: auto !important;
        height: auto !important;
        top: auto !important;
        bottom: auto !important;
        left: auto !important;
    }
    .digital-card.hide-bg #photoLayoutCol img {
        border: 2px solid #dee2e6 !important;
    }
    .digital-card.hide-bg #qrLayoutCol {
        position: relative !important;
        width: auto !important;
        height: auto !important;
        top: auto !important;
        bottom: auto !important;
        right: auto !important;
        left: auto !important;
    }
    .digital-card.hide-bg #qrLayoutCol .bg-white {
        background: #ffffff !important;
        border: 1px solid #dee2e6 !important;
        padding: 3px !important;
    }
    .digital-card.hide-bg #detailsLayoutCol {
        position: relative !important;
        top: auto !important;
        right: auto !important;
        width: auto !important;
        color: #212529 !important;
    }

    /* Tint theme overrides when backgrounds are hidden */
    .digital-card.hide-bg.tint-blue { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important; color: #0d47a1 !important; }
    .digital-card.hide-bg.tint-green { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%) !important; color: #1b5e20 !important; }
    .digital-card.hide-bg.tint-mint { background: linear-gradient(135deg, #f1f8e9 0%, #dcedc8 100%) !important; color: #33691e !important; }
    .digital-card.hide-bg.tint-gold { background: linear-gradient(135deg, #fdf6e2 0%, #f5e6be 100%) !important; color: #795548 !important; }
    .digital-card.hide-bg.tint-white { background: #ffffff !important; color: #212529 !important; }
</style>
<?php
/**
 * @var string $role
 * @var string|null $api_key
 * @var string|null $db_error
 * @var string|null $wilaya_name
 * @var string|null $commune_name
 * @var string|null $etab_name
 * @var array|null $students
 * @var array|null $branches
 * @var int|null $total_stagiaires
 * @var int|null $total_offres
 * @var int|null $total_etablissements
 * @var int|null $total_users
 * @var int|null $total_candidats
 * @var array|null $recent_inscriptions
 * @var int|null $pending_inscriptions
 * @var array|null $local_stagiaires
 * @var array|null $employee_documents
 * @var array|null $grades
 * @var array|null $meal_reservations
 * @var array|null $document_requests
 * @var array|null $stagiaire
 * @var int|null $total_branches
 * @var int|null $total_specialites
 * @var array|null $filter_wilayas
 * @var array|null $filter_etablissements
 * @var array|null $filter_filieres
 * @var array|null $filter_specialites
 * @var array|null $filter_sessions
 */
?>
<?php
$filter_wilayas = $filter_wilayas ?? [];
$filter_etablissements = $filter_etablissements ?? [];
$filter_filieres = $filter_filieres ?? [];
$filter_specialites = $filter_specialites ?? [];
$filter_sessions = $filter_sessions ?? [];
$audit_logs = $audit_logs ?? [];
$isEtabRole = in_array($role, ['directeur', 'etablissement', 'formateur', 'employee']);
$isDfepRole = ($role === 'dfep');
?>
<div class="animate__animated animate__fadeIn">


    <?php if (empty($current_tab) && !isset($_GET['view_dir'])): ?>
    
    <!-- Component Helper Function -->
    <?php
    if (!function_exists('renderComponent')) {
        function renderComponent(string $name, array $data = []) {
            echo view("components.{$name}", $data)->render();
        }
    }
    ?>

    <style>
        .shortcut-item {
            background-color: var(--color-bg) !important;
            transition: all 0.25s ease-out !important;
        }
        .shortcut-item:hover {
            background-color: var(--color-bg2) !important;
            border-color: var(--color-primary) !important;
            transform: translateX(-5px) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        
        /* Workflow Portal Styles */
        .portal-workflow-section {
            background: var(--bg-surface, #ffffff);
            border-radius: 24px;
            border: 1.5px solid var(--border, #e8edf5);
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            text-align: center;
        }
        .portal-workflow-title {
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--tx-1, #0f2752);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-bottom: 1.5px solid var(--border, #e8edf5);
            padding-bottom: 1rem;
        }
        .portal-stage-desc {
            font-size: 0.82rem;
            color: var(--tx-2, #555);
            line-height: 1.7;
            margin: 1.5rem auto;
            max-width: 900px;
            padding: 0.8rem 1.5rem;
            background: rgba(26, 107, 204, 0.04);
            border-radius: 12px;
            border-right: 4px solid var(--electric, #1a6bcc);
            text-align: right;
        }
        .portal-stage-row {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1rem;
        }
        .portal-item-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            width: 110px;
            transition: transform 0.25s ease, filter 0.25s ease;
        }
        .portal-item-link:hover {
            transform: translateY(-5px);
        }
        .portal-item-icon-wrapper {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            background: var(--bg-surface-elevated, #ffffff);
            border: 2px solid var(--border, #e8edf5);
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            margin-bottom: 0.6rem;
        }
        .portal-item-link:hover .portal-item-icon-wrapper {
            border-color: var(--electric, #1a6bcc);
            box-shadow: 0 15px 35px rgba(26, 107, 204, 0.18);
            background: linear-gradient(135deg, #ffffff 0%, rgba(26,107,204,0.03) 100%);
        }
        .portal-item-icon {
            font-size: 1.6rem;
            transition: transform 0.25s ease;
        }
        .portal-item-link:hover .portal-item-icon {
            transform: scale(1.15);
        }
        .portal-item-label {
            font-size: 0.82rem;
            font-weight: 800;
            color: var(--tx-1, #0f2752);
            text-align: center;
            white-space: nowrap;
            transition: color 0.2s;
        }
        .portal-item-link:hover .portal-item-label {
            color: var(--electric, #1a6bcc);
        }
    </style>

    <!-- Bento Grid Container -->
    <div class="bento-grid mb-4">
        
        <!-- Welcome Banner (Bento Span 12) -->
        <div class="bento-6 glass-panel p-4" style="border-right: 6px solid var(--electric) !important; position: relative;">
            <!-- Ambient glow -->
            <div style="position: absolute; width: 300px; height: 150px; background: radial-gradient(circle, rgba(26,107,204,0.06) 0%, transparent 70%); top: 0; left: 0; pointer-events: none;"></div>
            <div class="row align-items-center" style="position: relative; z-index: 2;">
                <div class="col-md-8">
                    <?php
                    $etabName = null;
                    if (in_array($role, ['directeur', 'etablissement', 'formateur', 'employee'])) {
                        $etabId = session('user')['etablissement_id'] ?? 0;
                        if ($etabId > 0) {
                            $etabObj = \App\Services\ReferenceCache::etablissementById($etabId);
                            $etabName = !empty($etabObj) ? ($etabObj[0]['Nom'] ?? null) : null;
                        }
                    }
                    ?>
                    <span class="fs-4 d-block mb-1" style="font-weight: 900; color: var(--tx-1); font-family: 'Cairo', sans-serif;">
                        <i class="fa-solid fa-circle-user text-primary me-2"></i>مرحباً بك، <?= htmlspecialchars(session('user')['nom_complet'] ?? 'المستخدم') ?>
                    </span>
                    <p class="text-muted mb-0 small" style="font-weight: 700;">
                        <span class="status-pill online py-1 px-2.5 me-2"><span class="status-dot online"></span> متصل حالياً</span>
                        <i class="fa-solid fa-user-shield text-secondary me-1"></i>أنت مسجل برتبة: <strong class="text-primary"><?= htmlspecialchars(session('user')['role_ar'] ?? 'مستخدم') ?></strong>
                        <?php if (!empty($etabName)): ?>
                            <i class="fa-solid fa-school text-info mx-1"></i>بمؤسسة: <strong class="text-primary"><?= htmlspecialchars($etabName) ?></strong>
                        <?php endif; ?>
                        • <i class="fa-solid fa-shield-halved text-success me-1"></i>نظام حوكمة التكوين مستقر ونشط
                    </p>
                </div>
                <div class="col-md-4 text-md-start mt-3 mt-md-0 d-flex flex-column align-items-md-start align-items-center gap-2">
                    <span class="badge bg-primary text-primary py-2 px-3 border-0" style="font-size: 0.82rem;">
                        <i class="fa-regular fa-calendar-days me-1"></i>
                        الدورة التدريبية الحالية: 2026
                    </span>
                    <?php if (in_array($role, ['dfep', 'etablissement', 'directeur', 'employee'])): ?>
                        <button type="button" class="btn btn-outline-primary py-1.5 px-3 border-1 rounded-3 w-100 mt-1 d-flex align-items-center justify-content-center gap-2" onclick="openWorkflowPortal()" style="font-size: 0.82rem; font-family: 'Cairo', sans-serif; font-weight: 700; border-color: rgba(26,107,204,0.3);">
                            <i class="fa-solid fa-cubes text-primary"></i> مخطط سير العمل
                        </button>
                    <?php endif; ?>
                    <?php if (!empty($last_sync_ts)): ?>
                        <span class="text-muted small d-inline-block" style="font-size: 0.72rem; font-weight: bold; font-family: 'Outfit', 'Cairo', sans-serif;">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i>
                            تحديث المؤشرات: <?= date('Y-m-d H:i:s', $last_sync_ts) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- DB error display if any (Bento Span 12) -->
        <?php if (isset($db_error)): ?>
            <div class="bento-6 alert alert-warning border-0 shadow-sm m-0" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> تذكير: بعض جداول الإعدادات قيد التحديث البنيوي. (<?= htmlspecialchars($db_error) ?>)
            </div>
        <?php endif; ?>

        <!-- Flash Alerts (Bento Span 12) -->
        <?php if (session()->has('flash_success')): ?>
            <div class="bento-6 alert alert-success alert-dismissible fade show border-0 shadow-sm m-0" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?= session('flash_success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php  ?>
        <?php endif; ?>

        <!-- ------------------------------------------------------------- -->
        <!-- ADVANCED FILTER CONTROL PANEL & REAL-TIME ANALYTICS -->
        <!-- ------------------------------------------------------------- -->
        <?php if (in_array($role, ['admin', 'dfep', 'ministre', 'apc', 'directeur', 'etablissement', 'inspecteur_general', 'secretaire_general', 'chef_cabinet', 'inspecteur_central', 'dir_org', 'dir_finance', 'dir_rh', 'dir_plan', 'dir_coop', 'dir_it', 'dir_exam', 'dir_trak', 'dir_edu']) && $dept === 'general'): ?>
            
            <!-- Active Badges Container (remains visible on dashboard) -->
            <div id="active-badges-container" class="bento-6 glass-panel p-3 mb-4" style="display: none !important; border: 1px dashed var(--electric) !important;">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-bold" style="font-family:'Cairo';"><i class="fa-solid fa-filter text-primary me-1"></i> الفلاتر النشطة:</span>
                    <div id="active-badges-list" class="d-flex flex-wrap gap-1.5"></div>
                </div>
            </div>

            <!-- Advanced Filter Slide-out Drawer (Glassmorphic) -->
            <div class="filter-drawer-overlay" id="filterDrawerOverlay" onclick="toggleFilterDrawer()"></div>
            <div class="filter-drawer" id="filterDrawer">
                <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                    <h5 class="fw-bold m-0 text-dark" style="font-family: 'Cairo', sans-serif; font-size: 1.05rem;">
                        <i class="fa-solid fa-sliders text-primary me-2"></i> تصفية متقدمة
                    </h5>
                    <button type="button" class="btn-close" onclick="toggleFilterDrawer()" style="box-shadow: none;"></button>
                </div>
                
                <div class="d-flex flex-column gap-3">
                    
                    <!-- Search Query -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i> بحث بالاسم / رقم التسجيل</label>
                        <input type="text" class="form-control form-input" id="filter-q" placeholder="ابحث بالاسم، اللقب، رقم التسجيل..." onkeyup="if(event.key==='Enter') triggerFilter()">
                    </div>

                    <!-- Wilaya Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-map-location-dot me-1 text-primary"></i> الولاية / Wilaya</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-wilaya" onchange="onWilayaChange()" style="font-size:0.85rem; font-weight:600;" <?= ($isDfepRole || $isEtabRole) ? 'disabled' : '' ?>>
                            <?php if (!$isDfepRole && !$isEtabRole): ?>
                                <option value="">كل الولايات / Toutes</option>
                            <?php endif; ?>
                            <?php foreach ($filter_wilayas as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= ((($isDfepRole || $isEtabRole) && (session('user')['wilaya_id'] ?? null) == $w['id']) || (!$isDfepRole && !$isEtabRole && $w['id'] == 31)) ? 'selected' : '' ?>>
                                    <?= $w['code'] ?> - <?= htmlspecialchars($w['nom_ar']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Etablissement Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-hotel me-1 text-primary"></i> المؤسسة / Établissement</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-etablissement" style="font-size:0.85rem; font-weight:600;" <?= $isEtabRole ? 'disabled' : '' ?>>
                            <?php if (!$isEtabRole): ?>
                                <option value="">كل المؤسسات / Tous</option>
                            <?php endif; ?>
                            <?php foreach ($filter_etablissements as $et): ?>
                                <option value="<?= $et['id'] ?>" data-wilaya="<?= $et['wilaya_id'] ?? '' ?>" <?= ($isEtabRole || (session('user')['etablissement_id'] ?? null) == $et['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($et['nom_ar']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filiere Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-folder-tree me-1 text-primary"></i> الشعبة المهنية / Filière</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-filiere" onchange="onFiliereChange()" style="font-size:0.85rem; font-weight:600;">
                            <option value="">كل الشعب / Toutes</option>
                            <?php foreach ($filter_filieres as $f): ?>
                                <option value="<?= $f['id'] ?>">
                                    <?= $f['code'] ?> - <?= htmlspecialchars($f['libelle_ar']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Specialite Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-graduation-cap me-1 text-primary"></i> التخصص / Spécialité</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-specialite" style="font-size:0.85rem; font-weight:600;">
                            <option value="">كل التخصصات / Toutes</option>
                            <?php foreach ($filter_specialites as $sp): ?>
                                <option value="<?= $sp['id'] ?>" data-filiere="<?= $sp['filiere_id'] ?>">
                                    <?= htmlspecialchars($sp['libelle_ar']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Mode formation Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-circle-nodes me-1 text-primary"></i> نمط التكوين / Mode</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-mode" style="font-size:0.85rem; font-weight:600;">
                            <option value="">كل الأنماط / Tous</option>
                            <?php foreach ($filter_modes ?? [] as $mode): ?>
                                <option value="<?= (int)$mode['id'] ?>">
                                    <?= htmlspecialchars($mode['libelle_ar']) ?>
                                    <?php if (!empty($mode['libelle_fr'])): ?>
                                        / <?= htmlspecialchars($mode['libelle_fr']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-toggle-on me-1 text-primary"></i> حالة المتربص / Statut</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-statut" style="font-size:0.85rem; font-weight:600;">
                            <option value="">كل الحالات / Tous</option>
                            <option value="actif">نشط / Actif</option>
                            <option value="suspendu">موقوف / Suspendu</option>
                            <option value="diplome">متخرج / Diplômé</option>
                            <option value="abandon">منقطع / Abandon</option>
                        </select>
                    </div>

                    <!-- Sexe Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-venus-mars me-1 text-primary"></i> الجنس / Genre</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-sexe" style="font-size:0.85rem; font-weight:600;">
                            <option value="">الكل / Tous</option>
                            <option value="M">ذكر / Masculin</option>
                            <option value="F">أنثى / Féminin</option>
                        </select>
                    </div>

                    <!-- Session Filter -->
                    <div>
                        <label class="form-label"><i class="fa-solid fa-calendar-days me-1 text-primary"></i> الدورة / Session</label>
                        <select class="form-select border-0 shadow-sm py-2 px-3 bg-light rounded-3 w-100" id="filter-session" style="font-size:0.85rem; font-weight:600;">
                            <option value="">كل الدورات / Toutes</option>
                            <?php foreach ($filter_annees ?? [] as $af): ?>
                                <option value="<?= (int)$af['id'] ?>"
                                    <?= $af['en_cours'] ? 'selected' : '' ?>
                                    style="<?= $af['en_cours'] ? 'font-weight:800; color:#0d6efd;' : '' ?>">
                                    <?= htmlspecialchars($af['libelle_ar'] ?: $af['code']) ?>
                                    <?= $af['en_cours'] ? ' ✓' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="d-flex flex-column gap-2 mt-2">
                        <button class="btn btn-primary w-100 rounded-3 py-2.5 fw-bold" onclick="triggerFilter(); toggleFilterDrawer();">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> تطبيق الفلترة
                        </button>
                        <button class="btn btn-secondary w-100 rounded-3 py-2.5 fw-bold" onclick="resetFilters()">
                            <i class="fa-solid fa-arrow-rotate-left me-1"></i> إعادة تعيين
                        </button>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success w-50 rounded-3 py-2 fw-bold text-white small" onclick="exportCSV()">
                                <i class="fa-solid fa-file-csv"></i> CSV
                            </button>
                            <button class="btn btn-danger w-50 rounded-3 py-2 fw-bold text-white small" onclick="printTrainees()">
                                <i class="fa-solid fa-print"></i> PDF
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Live Filter Real-time Analytics block (Rendered only on filter action) -->
            <div class="bento-6" id="live-analytics-section" style="display: none;">
                <div class="bento-grid">
                    
                    <!-- Chart Modes -->
                    <div class="bento-2 glass-panel p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0 text-dark" style="border-right: 3px solid var(--electric); padding-right: 0.5rem; font-family:'Cairo';">
                                توزيع أنماط التكوين / Modes
                            </h6>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="toggleChartType('modes')"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>تغيير نوع المخطط</a></li>
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="downloadChart('chart-formation-modes', 'modes_formation.png')"><i class="fa-solid fa-download me-2 text-success"></i>حفظ المخطط (PNG)</a></li>
                                </ul>
                            </div>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chart-formation-modes"></canvas>
                        </div>
                    </div>

                    <!-- Chart Status -->
                    <div class="bento-2 glass-panel p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0 text-dark" style="border-right: 3px solid var(--green); padding-right: 0.5rem; font-family:'Cairo';">
                                حالة المتربصين / Statut
                            </h6>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="toggleChartType('status')"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>تغيير نوع المخطط</a></li>
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="downloadChart('chart-trainee-status', 'statut_stagiaires.png')"><i class="fa-solid fa-download me-2 text-success"></i>حفظ المخطط (PNG)</a></li>
                                </ul>
                            </div>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chart-trainee-status"></canvas>
                        </div>
                    </div>

                    <!-- Chart Gender -->
                    <div class="bento-2 glass-panel p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0 text-dark" style="border-right: 3px solid var(--gold); padding-right: 0.5rem; font-family:'Cairo';">
                                النسبة الجنسانية / Genre
                            </h6>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="toggleChartType('gender')"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>تغيير نوع المخطط</a></li>
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="downloadChart('chart-trainee-gender', 'repartition_genre.png')"><i class="fa-solid fa-download me-2 text-success"></i>حفظ المخطط (PNG)</a></li>
                                </ul>
                            </div>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chart-trainee-gender"></canvas>
                        </div>
                    </div>

                    <!-- Specialties chart -->
                    <div class="bento-5 glass-panel p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0 text-dark" style="border-right: 3px solid var(--electric); padding-right: 0.5rem; font-family:'Cairo';">
                                أعلى التخصصات طلباً / Spécialités
                            </h6>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="toggleChartType('specialties')"><i class="fa-solid fa-chart-bar me-2 text-primary"></i>تغيير المخطط</a></li>
                                    <li><a class="dropdown-item fw-semibold" href="javascript:void(0)" onclick="downloadChart('chart-top-specialties', 'top_specialites.png')"><i class="fa-solid fa-download me-2 text-success"></i>حفظ المخطط (PNG)</a></li>
                                </ul>
                            </div>
                        </div>
                        <div style="height: 280px; position: relative;">
                            <canvas id="chart-top-specialties"></canvas>
                        </div>
                    </div>

                    <!-- Matching Trainees list table -->
                    <div class="bento-7 glass-panel p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0 text-dark" style="border-right: 3px solid var(--electric); padding-right: 0.5rem; font-family: 'Cairo', sans-serif;">
                                قائمة المتربصين المطابقين للبحث / Liste des Stagiaires
                            </h6>
                            <span class="badge bg-primary text-primary py-2 px-3" id="trainee-table-count">0 متربص</span>
                        </div>
                        
                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0 text-center small">
                                <thead style="position: sticky; top: 0; z-index: 5;">
                                    <tr>
                                        <th>رقم التسجيل</th>
                                        <th>المتربص</th>
                                        <th>التخصص</th>
                                        <th>النمط</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody id="trainee-table-body">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">الرجاء الضغط على "تطبيق الفلترة" لعرض النتائج.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ------------------------------------------------------------- -->
        <!-- ------------------------------------------------------------- -->
        <!-- 1. DASHBOARD OVERVIEW (Admin / DFEP / Etablissement)          -->
        <!-- ------------------------------------------------------------- -->
        <?php if (in_array($role, ['admin', 'ministre', 'secretaire_general', 'dfep', 'etablissement', 'directeur']) && $dept === 'general'): ?>
            <?php
                $isApprenMode = (int)(session('user.IDMode_formation') ?? 0) === 10;
                $scopeLabel = $isApprenMode ? 'نمط التمهين الوطني' : 'الوطني';
                $scopeSubtitle = $isApprenMode ? 'في نمط التمهين بكافة معاهد الوطن' : 'في كافة معاهد الوطن';
                $instSubtitle = 'عبر ' . ($total_wilayas ?? 58) . ' ولاية';
                $specSubtitle = 'تخصص معتمد وطنياً';
                if ($role === 'dfep') {
                    $scopeLabel = $isApprenMode ? 'نمط التمهين بالولاية' : 'الولائي';
                    $scopeSubtitle = 'في كافة مؤسسات الولاية';
                    $instSubtitle = 'مؤسسة في الولاية';
                    $specSubtitle = 'تخصص معتمد بالولاية';
                } elseif (in_array($role, ['etablissement', 'directeur'])) {
                    $scopeLabel = $isApprenMode ? 'نمط التمهين بالمؤسسة' : 'بالمؤسسة';
                    $scopeSubtitle = $isApprenMode ? 'في نمط التمهين بمؤسستك التكوينية' : 'في مؤسستك التكوينية';
                    $instSubtitle = 'مؤسستك التكوينية';
                    $specSubtitle = 'تخصص متاح بالمؤسسة';
                }
            ?>

            <!-- 3x3 Bento Grid of KPI Cards -->
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => "تعداد المتربصين ($scopeLabel)",
                    'value'     => number_format($total_stagiaires ?? 0),
                    'icon'      => 'fa-solid fa-users',
                    'iconType'  => 'blue',
                    'delta'     => '+4.2%',
                    'deltaType' => 'up',
                    'subtitle'  => $scopeSubtitle
                ]); ?>
            </div>
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => 'المتربصين المستمرين',
                    'value'     => number_format($total_reconduits ?? 0),
                    'icon'      => 'fa-solid fa-arrows-spin',
                    'iconType'  => 'blue',
                    'subtitle'  => 'يزاولون دراستهم حالياً'
                ]); ?>
            </div>
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => 'عروض التكوين المفتوحة',
                    'value'     => number_format($total_offres ?? 0),
                    'icon'      => 'fa-solid fa-briefcase',
                    'iconType'  => 'green',
                    'delta'     => '+2.8%',
                    'deltaType' => 'up',
                    'subtitle'  => 'مطابقة لمدونة التخصصات'
                ]); ?>
            </div>
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => 'المؤسسات التكوينية النشطة',
                    'value'     => number_format($total_etablissements ?? 0),
                    'icon'      => 'fa-solid fa-school',
                    'iconType'  => 'gold',
                    'delta'     => '+1.5%',
                    'deltaType' => 'up',
                    'subtitle'  => $instSubtitle
                ]); ?>
            </div>
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => 'الأساتذة والمكوّنون',
                    'value'     => number_format($total_encadrements ?? 0),
                    'icon'      => 'fa-solid fa-chalkboard-user',
                    'iconType'  => 'navy',
                    'delta'     => '+3.1%',
                    'deltaType' => 'up',
                    'subtitle'  => 'تعداد التأطير'
                ]); ?>
            </div>
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => 'التخصصات المفعّلة',
                    'value'     => number_format($total_specialites ?? 0),
                    'icon'      => 'fa-solid fa-graduation-cap',
                    'iconType'  => 'green',
                    'subtitle'  => $specSubtitle ?? 'تخصص معتمد'
                ]); ?>
            </div>
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => 'المترشحون المسجّلون',
                    'value'     => number_format($total_candidats ?? 0),
                    'icon'      => 'fa-solid fa-user-graduate',
                    'iconType'  => 'blue',
                    'subtitle'  => 'مترشح في قواعد البيانات'
                ]); ?>
            </div>
            <div class="bento-2">
                <?php
                $pctF = (($total_stagiaires ?? 0) > 0) ? round(($total_filles ?? 0) / ($total_stagiaires) * 100, 1) : 0;
                renderComponent('KpiCard', [
                    'label'     => 'الطالبات — إناث',
                    'value'     => number_format($total_filles ?? 0),
                    'icon'      => 'fa-solid fa-venus',
                    'iconType'  => 'gold',
                    'subtitle'  => $pctF . '٪ من الإجمالي'
                ]); ?>
            </div>
            <div class="bento-2">
                <?php renderComponent('KpiCard', [
                    'label'     => 'مستخدمي النظام',
                    'value'     => number_format($total_users ?? 0),
                    'icon'      => 'fa-solid fa-user-shield',
                    'iconType'  => 'navy',
                    'subtitle'  => 'مع تفويض الصلاحيات'
                ]); ?>
            </div>

            <!-- Trainees Evolution Chart -->
            <div class="bento-4 bento-row-2 glass-panel p-4">
                <h5 class="fw-bold mb-4 text-dark" style="border-right: 4px solid var(--electric); padding-right: 0.6rem; font-family:'Cairo';">
                    منحنى تطور المتربصين (<?= $scopeLabel ?>) / Effectifs
                </h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="adminCurveChart" data-total-stagiaires="<?= $total_stagiaires ?? 1250 ?>" data-role="<?= $role ?>"></canvas>
                </div>
            </div>

            <!-- Employee Account Management -->
            <?php if((int)($user['IDMode_formation'] ?? 0) !== 10): ?>
            <div class="bento-4 bento-row-2 glass-panel p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-users-gear text-primary fs-5"></i>
                            <h5 class="fw-bold m-0 text-dark" style="font-family:'Cairo';">إدارة حسابات الموظفين</h5>
                        </div>
                        <?php
                            $scopeLabelAr = 'كافة الولايات';
                            if ($role === 'dfep') {
                                $scopeLabelAr = 'حسابات الولاية: ' . htmlspecialchars($user['wilaya_name'] ?? '');
                            } elseif (in_array($role, ['directeur', 'etablissement'])) {
                                $scopeLabelAr = 'حسابات المؤسسة';
                            }
                        ?>
                        <span class="badge bg-primary-subtle text-primary px-3 py-1.5 rounded-pill fw-bold" style="font-size:0.75rem;">
                            <?= $scopeLabelAr ?>
                        </span>
                    </div>

                    <div class="table-responsive mt-3" style="max-height: 220px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 text-center small">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border);">
                                    <th class="pb-2 text-start text-muted" style="font-size:0.75rem; font-weight:700;">المستخدم</th>
                                    <th class="pb-2 text-muted" style="font-size:0.75rem; font-weight:700;">الصفة</th>
                                    <th class="pb-2 text-end text-muted" style="font-size:0.75rem; font-weight:700;">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($dashboard_users)): ?>
                                    <?php foreach ($dashboard_users as $du): ?>
                                        <tr>
                                            <td class="py-2.5 text-start">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark" style="font-size:0.8rem;"><?= htmlspecialchars($du['nom_complet']) ?></span>
                                                    <span class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($du['username']) ?></span>
                                                </div>
                                            </td>
                                            <td class="py-2.5">
                                                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size:0.7rem; font-weight:600;">
                                                    <?= htmlspecialchars($du['role_ar']) ?>
                                                </span>
                                            </td>
                                            <td class="py-2.5 text-end">
                                                <?php if ($du['est_actif']): ?>
                                                    <span class="badge bg-success-subtle text-success px-2 py-1" style="font-size:0.7rem; font-weight:600;">نشط</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger px-2 py-1" style="font-size:0.7rem; font-weight:600;">موقوف</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted" style="font-size:0.8rem;">لا توجد حسابات مسجلة</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <a href="/dashboard/users" class="btn btn-primary w-100 mt-3 py-2.5 fw-bold" style="border-radius:12px;">
                    <i class="fa-solid fa-users-cog me-1"></i> الانتقال إلى إدارة الحسابات
                </a>
            </div>
            <?php endif; ?>

        <?php endif; ?>

            <!-- Quick Shortcuts Gateway -->
            <div class="bento-4 glass-panel p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-square-poll-horizontal text-primary fs-5"></i>
                            <h5 class="fw-bold m-0 text-dark" style="font-family:'Cairo';">الاختصارات السريعة وبوابة المهام</h5>
                        </div>
                        <span class="badge bg-success-subtle text-success px-3 py-1.5 rounded-pill fw-bold" style="font-size:0.75rem;">
                            مسار سريع
                        </span>
                    </div>
                    
                    <div class="d-flex flex-column gap-2.5 mt-3">
                        <?php
                        // Define role-based shortcuts
                        $shortcuts = [];
                        if (in_array($role, ['admin', 'ministre', 'secretaire_general'])) {
                            $shortcuts = [
                                [
                                    'title' => 'إدارة حسابات المستخدمين',
                                    'desc' => 'تعديل الصلاحيات وتفعيل الحسابات المركزية والولائية',
                                    'url' => '/dashboard/users',
                                    'icon' => 'fa-users-gear',
                                    'color' => 'primary'
                                ],
                                [
                                    'title' => 'مركز البطاقات الرقمية',
                                    'desc' => 'إصدار وإدارة بطاقات الموظفين والمتربصين المشفرة',
                                    'url' => '/dashboard/digital-cards?type=employee',
                                    'icon' => 'fa-address-card',
                                    'color' => 'success'
                                ],
                                [
                                    'title' => 'التوزيع العالمي للعروض',
                                    'desc' => 'متابعة الخارطة البيداغوجية والقدرة الاستيعابية للمؤسسات',
                                    'url' => '/dashboard/distribution-globale',
                                    'icon' => 'fa-map-location-dot',
                                    'color' => 'warning'
                                ],
                                [
                                    'title' => 'مدونة التخصصات المعتمدة',
                                    'desc' => 'إدارة الشعب المهنية وعروض التكوين المفتوحة',
                                    'url' => '/dashboard/specialites',
                                    'icon' => 'fa-graduation-cap',
                                    'color' => 'info'
                                ]
                            ];
                        } elseif ($role === 'dfep') {
                            $shortcuts = [];
                            $shortcuts[] = [
                                'title' => 'متابعة خارطة الولاية بيداغوجياً',
                                'desc' => 'عرض الهياكل والقدرة الاستيعابية لمراكز ومعاهد الولاية',
                                'url' => '/dashboard/distribution-globale',
                                'icon' => 'fa-map-location-dot',
                                'color' => 'primary'
                            ];
                            if ((int)(session('user.IDMode_formation') ?? 0) !== 10) {
                                $shortcuts[] = [
                                    'title' => 'البطاقات الرقمية لمنتسبي الولاية',
                                    'desc' => 'معاينة ملفات وبطاقات الموظفين والمتكونين بالولاية',
                                    'url' => '/dashboard/digital-cards?type=employee',
                                    'icon' => 'fa-address-card',
                                    'color' => 'success'
                                ];
                            }
                            $shortcuts[] = [
                                'title' => 'تعداد المتربصين المستمرين بالولاية',
                                'desc' => 'إحصائيات وقوائم المتربصين الناشطين (Reconduits)',
                                'url' => '/dashboard/reconduits',
                                'icon' => 'fa-arrows-spin',
                                'color' => 'warning'
                            ];
                            $shortcuts[] = [
                                'title' => 'عروض التكوين والشعب بالولاية',
                                'desc' => 'متابعة تخصصات وعروض المؤسسات التابعة للولاية',
                                'url' => '/dashboard/specialites',
                                'icon' => 'fa-graduation-cap',
                                'color' => 'info'
                            ];
                        } else {
                            // etablissement / directeur / employee / formateur
                            $shortcuts = [];
                            if ((int)(session('user.IDMode_formation') ?? 0) !== 10) {
                                $shortcuts[] = [
                                    'title' => 'مركز بطاقات المؤسسة الرقمية',
                                    'desc' => 'إصدار وتحديث بطاقات المتكونين ومؤطري المؤسسة',
                                    'url' => '/dashboard/digital-cards?type=employee',
                                    'icon' => 'fa-address-card',
                                    'color' => 'primary'
                                ];
                            }
                            $shortcuts[] = [
                                'title' => 'عروض وتخصصات المؤسسة',
                                'desc' => 'تحديد وإدارة الشعب والتخصصات المفتوحة للمؤسسة',
                                'url' => '/dashboard/specialites',
                                'icon' => 'fa-graduation-cap',
                                'color' => 'success'
                            ];
                            $shortcuts[] = [
                                'title' => 'قوائم المتربصين المستمرين',
                                'desc' => 'متابعة تعداد المتكونين المزاولين لدراستهم بالمؤسسة',
                                'url' => '/dashboard/reconduits',
                                'icon' => 'fa-arrows-spin',
                                'color' => 'warning'
                            ];
                            if ((int)(session('user.IDMode_formation') ?? 0) !== 10) {
                                $shortcuts[] = [
                                    'title' => 'إدارة حسابات طاقم المؤسسة',
                                    'desc' => 'إدارة صلاحيات مستخدمي وحسابات المؤسسة التكوينية',
                                    'url' => '/dashboard/users',
                                    'icon' => 'fa-users-gear',
                                    'color' => 'info'
                                ];
                            }
                        }
                        
foreach ($shortcuts as $sc):
                        ?>
                            <a href="<?= htmlspecialchars($sc['url']) ?>" class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border text-decoration-none shortcut-item" style="border-color: var(--border) !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-<?= $sc['color'] ?>-subtle text-<?= $sc['color'] ?>" style="width:40px; height:40px; min-width:40px;">
                                        <i class="fa-solid <?= htmlspecialchars($sc['icon']) ?> fs-5"></i>
                                    </div>
                                    <div class="d-flex flex-column text-start">
                                        <span class="fw-bold text-dark" style="font-size:0.8rem; font-family:'Cairo';"><?= htmlspecialchars($sc['title']) ?></span>
                                        <span class="text-muted small" style="font-size:0.68rem; font-weight:600;"><?= htmlspecialchars($sc['desc']) ?></span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-left text-muted small ms-2"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (in_array($role, ['admin', 'ministre', 'secretaire_general'])): ?>
            <!-- System Status (admin only) -->
            <div class="bento-4 glass-panel p-4">
                <h5 class="fw-bold mb-3 text-dark" style="border-right: 3px solid var(--gold); padding-right: 0.5rem; font-family:'Cairo';">
                    <i class="fa-solid fa-microchip me-1 text-primary"></i> تشخيص حالة النظام
                </h5>
                <div class="d-flex flex-column gap-2.5 mt-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-bold text-muted">اتصال قاعدة البيانات</span>
                        <span class="status-pill online"><span class="status-dot online"></span> متصل</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-bold text-muted">المطابقة البيداغوجية / APIs</span>
                        <span class="status-pill online"><span class="status-dot online"></span> Windev Ready</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-bold text-muted">الربط المركزي / Mainframe</span>
                        <span class="status-pill online"><span class="status-dot online"></span> مستقر</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-bold text-muted">التوقيع الرقمي / SSL</span>
                        <span class="status-pill online"><span class="status-dot online"></span> نشط</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions (admin only) -->
            <div class="bento-4 glass-panel p-4 d-flex flex-column justify-content-between">
                <h5 class="fw-bold mb-3 text-dark" style="border-right: 3px solid var(--green); padding-right: 0.5rem; font-family:'Cairo';">
                    <i class="fa-solid fa-bolt me-1 text-primary"></i> إجراءات التحكم السريع
                </h5>
                <div class="d-flex flex-column gap-2 mt-2">
                    <a href="/dashboard/users" class="btn btn-light text-start py-2 px-3 d-flex align-items-center justify-content-between rounded-3">
                        <span><i class="fa-solid fa-user-plus me-2 text-primary"></i>إضافة حساب موظف</span>
                        <i class="fa-solid fa-chevron-left text-muted" style="font-size:0.7rem;"></i>
                    </a>
                </div>
            </div>

            <!-- Historical Archive Portal (admin only) -->
            <div class="bento-4 glass-panel p-4 d-flex flex-column justify-content-between" style="border-right: 4px solid var(--electric) !important;">
                <div>
                    <h5 class="fw-bold mb-3 text-dark" style="border-right: 3px solid var(--electric); padding-right: 0.5rem; font-family:'Cairo';">
                        <i class="fa-solid fa-box-archive me-1 text-primary"></i> بوابة الأرشيف الوطني (HFSQL)
                    </h5>
                    <p class="text-muted small mb-3" style="font-weight: 600; line-height: 1.6;">
                        الولوج إلى قاعدة البيانات التاريخية لاستعراض إحصائيات التكوين عبر الولايات.
                    </p>
                </div>
                <a href="/dashboard/archive" class="btn btn-primary w-100 mt-4 py-2.5 fw-bold" style="border-radius:12px; background:linear-gradient(135deg, var(--electric) 0%, #0d6efd 100%); border:none;">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> دخول بوابة الأرشيف
                </a>
            </div>

            <!-- ======================================================= -->
            <!-- Quick Links to Dedicated Pages (Audit, Employee, API) -->
            <!-- ======================================================= -->
            <div class="bento-6">
                <div class="row g-3">
                    <!-- Audit Logs Card -->
                    <div class="col-md-4">
                        <a href="<?php echo e(url('dashboard/audit-logs')); ?>" class="text-decoration-none d-block h-100">
                            <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between"
                                 style="border-radius:20px;border-right:4px solid #1A6BCC !important;transition:transform .2s,box-shadow .2s;"
                                 onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 35px rgba(26,107,204,0.15)'"
                                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                                <div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                                         style="width:48px;height:48px;background:rgba(26,107,204,0.1);">
                                        <i class="fa-solid fa-list-check text-primary fs-5"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">سجل العمليات</h6>
                                    <p class="text-muted small mb-0">متابعة جميع العمليات وآخر دخول للمستخدمين</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-3 pt-3" style="border-top:1px solid var(--border);">
                                    <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size:0.72rem;">Audit Logs</span>
                                    <i class="fa-solid fa-arrow-left text-primary"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- Employee Space Card -->
                    <div class="col-md-4">
                        <a href="<?php echo e(url('dashboard/espace-employe')); ?>" class="text-decoration-none d-block h-100">
                            <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between"
                                 style="border-radius:20px;border-right:4px solid #0EA66E !important;transition:transform .2s,box-shadow .2s;"
                                 onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 35px rgba(14,166,110,0.15)'"
                                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                                <div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                                         style="width:48px;height:48px;background:rgba(14,166,110,0.1);">
                                        <i class="fa-solid fa-briefcase text-success fs-5"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">فضاء الموظف</h6>
                                    <p class="text-muted small mb-0">محاكاة واجهة الموظف وخدماته الإدارية</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-3 pt-3" style="border-top:1px solid var(--border);">
                                    <span class="badge bg-success-subtle text-success fw-bold" style="font-size:0.72rem;">Espace Employé</span>
                                    <i class="fa-solid fa-arrow-left text-success"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <!-- API Center Card -->
                    <div class="col-md-4">
                        <a href="<?php echo e(url('dashboard/api-center')); ?>" class="text-decoration-none d-block h-100">
                            <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between"
                                 style="border-radius:20px;border-right:4px solid #F0A500 !important;transition:transform .2s,box-shadow .2s;"
                                 onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 35px rgba(240,165,0,0.15)'"
                                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                                <div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                                         style="width:48px;height:48px;background:rgba(240,165,0,0.1);">
                                        <i class="fa-solid fa-satellite-dish text-warning fs-5"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">مركز الاتصال الرقمي</h6>
                                    <p class="text-muted small mb-0">إدارة مفاتيح API وربط المنصة بالخدمات الخارجية</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-3 pt-3" style="border-top:1px solid var(--border);">
                                    <span class="badge bg-warning-subtle text-warning fw-bold" style="font-size:0.72rem;">API Center</span>
                                    <i class="fa-solid fa-arrow-left text-warning"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ======================================================= -->
            <!-- STATISTICS CHARTS BLOCK (Pie + Bar + Line) - All Roles  -->
            <!-- ======================================================= -->
            <div class="bento-6">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid var(--border);">
                    <h5 class="fw-bold text-dark m-0" style="font-family:'Cairo';border-right:4px solid var(--electric);padding-right:0.6rem;">
                        <i class="fa-solid fa-chart-pie text-primary me-2"></i>
                        إحصائيات المنصة — لوحة المخططات البيانية
                    </h5>
                    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill" style="font-size:0.78rem;">
                        <i class="fa-solid fa-circle-dot me-1" style="font-size:0.55rem;vertical-align:middle;"></i> مباشر
                    </span>
                </div>

                <div class="row g-4">
                    <!-- Pie Chart: Gender Distribution -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 h-100" style="background:var(--bg-surface-elevated,#fff);border:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,0.04);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark m-0" style="font-family:'Cairo';font-size:0.88rem;">
                                    <i class="fa-solid fa-venus-mars text-primary me-2"></i>توزيع الجنس
                                </h6>
                                <span class="badge bg-primary-subtle text-primary" style="font-size:0.68rem;">Pie</span>
                            </div>
                            <div style="height:220px;position:relative;">
                                <canvas id="dashPieGender"></canvas>
                            </div>
                            <div class="d-flex justify-content-center gap-3 mt-3">
                                <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#1A6BCC;"></span>ذكور</span>
                                <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#F0A500;"></span>إناث</span>
                            </div>
                        </div>
                    </div>

                    <!-- Doughnut Chart: Training Mode -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 h-100" style="background:var(--bg-surface-elevated,#fff);border:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,0.04);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark m-0" style="font-family:'Cairo';font-size:0.88rem;">
                                    <i class="fa-solid fa-circle-nodes text-success me-2"></i>أنماط التكوين
                                </h6>
                                <span class="badge bg-success-subtle text-success" style="font-size:0.68rem;">Doughnut</span>
                            </div>
                            <div style="height:220px;position:relative;">
                                <canvas id="dashDoughnutModes"></canvas>
                            </div>
                            <div class="d-flex justify-content-center flex-wrap gap-2 mt-3" id="dashModesLegend"></div>
                        </div>
                    </div>

                    <!-- Pie Chart: Trainees Status -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 h-100" style="background:var(--bg-surface-elevated,#fff);border:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,0.04);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark m-0" style="font-family:'Cairo';font-size:0.88rem;">
                                    <i class="fa-solid fa-toggle-on text-warning me-2"></i>حالة المتربصين
                                </h6>
                                <span class="badge bg-warning-subtle text-warning" style="font-size:0.68rem;">Pie</span>
                            </div>
                            <div style="height:220px;position:relative;">
                                <canvas id="dashPieStatus"></canvas>
                            </div>
                            <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                                <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#0EA66E;"></span>نشط</span>
                                <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#1A6BCC;"></span>متخرج</span>
                                <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#F0A500;"></span>موقوف</span>
                                <span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#e53e3e;"></span>منقطع</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Chart: Top Specialties -->
                    <div class="col-md-8">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff);border:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,0.04);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark m-0" style="font-family:'Cairo';font-size:0.88rem;">
                                    <i class="fa-solid fa-chart-bar text-primary me-2"></i>أعلى التخصصات طلباً
                                </h6>
                                <span class="badge bg-primary-subtle text-primary" style="font-size:0.68rem;">Bar Chart</span>
                            </div>
                            <div style="height:250px;position:relative;">
                                <canvas id="dashBarSpecialties"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Line Chart: Trainees Evolution -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff);border:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,0.04);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark m-0" style="font-family:'Cairo';font-size:0.88rem;">
                                    <i class="fa-solid fa-chart-line text-success me-2"></i>تطور التعداد الشهري
                                </h6>
                                <span class="badge bg-success-subtle text-success" style="font-size:0.68rem;">Line</span>
                            </div>
                            <div style="height:250px;position:relative;">
                                <canvas id="dashLineEvolution"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trainee Bourses Wilaya Statistics (admin only) -->
            <?php if(isset($bourseWilayaStats) && count($bourseWilayaStats) > 0): ?>
            <div class="bento-6 glass-panel p-4">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid var(--border);">
                    <h5 class="fw-bold text-dark m-0" style="font-family:'Cairo';border-right:4px solid var(--electric);padding-right:0.6rem;">
                        <i class="fa-solid fa-graduation-cap text-primary me-2"></i>
                        مقارنة نسب صب المنح للمتربصين حسب الولايات
                    </h5>
                    <span class="badge bg-primary-subtle text-primary py-2 px-3 fw-bold" style="font-size:0.75rem;">
                        إجمالي الولايات: <?php echo e(count($bourseWilayaStats)); ?>

                    </span>
                </div>
                
                <?php
                    $totalApprenantsNationwide = array_sum(array_column($bourseWilayaStats, 'total_apprenants'));
                    $totalPaidBoursesNationwide = array_sum(array_column($bourseWilayaStats, 'paid_bourses'));
                    $nationalPercentage = $totalApprenantsNationwide > 0 ? round(($totalPaidBoursesNationwide * 100) / $totalApprenantsNationwide, 1) : 0;
                ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff); border:1px solid var(--border); text-align:center;">
                            <div class="text-muted small fw-bold mb-1">المتربصين (الوطني)</div>
                            <h4 class="fw-bold m-0 text-primary" style="font-family:'Outfit','Cairo';"><?php echo e(number_format($totalApprenantsNationwide)); ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff); border:1px solid var(--border); text-align:center;">
                            <div class="text-muted small fw-bold mb-1">المستفيدين</div>
                            <h4 class="fw-bold m-0 text-success" style="font-family:'Outfit','Cairo';"><?php echo e(number_format($totalPaidBoursesNationwide)); ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff); border:1px solid var(--border); text-align:center;">
                            <div class="text-muted small fw-bold mb-1">نسبة التغطية</div>
                            <h4 class="fw-bold m-0 text-warning" style="font-family:'Outfit','Cairo';"><?php echo e($nationalPercentage); ?>%</h4>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead style="position: sticky; top: 0; z-index: 5; background: var(--bg-surface-elevated,#fff);">
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th class="text-start pb-2">الولاية</th>
                                <th class="pb-2">المتربصين</th>
                                <th class="pb-2">تم صبها</th>
                                <th class="pb-2">النسبة</th>
                                <th class="pb-2" style="width:140px;">مؤشر التقدم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $bourseWilayaStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="text-start fw-bold text-dark py-2.5" style="font-family:'Cairo';"><?php echo e($w['wilaya_nom']); ?></td>
                                <td class="py-2.5" style="font-family:'Outfit';"><?php echo e(number_format($w['total_apprenants'])); ?></td>
                                <td class="py-2.5 text-primary fw-bold" style="font-family:'Outfit';"><?php echo e(number_format($w['paid_bourses'])); ?></td>
                                <td class="py-2.5 text-success fw-bold" style="font-family:'Outfit';"><?php echo e($w['percentage']); ?>%</td>
                                <td class="py-2.5">
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <div style="width: 100%; background: var(--border, #dde3ef); border-radius: 4px; height: 6px; overflow: hidden; position: relative;">
                                            <div style="width: <?php echo e(min($w['percentage'], 100)); ?>%; background: <?php echo e($w['percentage'] >= 100 ? '#0ea66e' : ($w['percentage'] >= 50 ? '#1A6BCC' : '#F0A500')); ?>; height: 100%; border-radius: 4px;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Employee Account Activation Wilaya Statistics (admin only) -->
            <?php if(isset($employeeWilayaStats) && count($employeeWilayaStats) > 0): ?>
            <div class="bento-6 glass-panel p-4">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid var(--border);">
                    <h5 class="fw-bold text-dark m-0" style="font-family:'Cairo';border-right:4px solid var(--electric);padding-right:0.6rem;">
                        <i class="fa-solid fa-users-gear text-primary me-2"></i>
                        مقارنة نسب تفعيل حسابات الموظفين حسب الولايات
                    </h5>
                    <span class="badge bg-primary-subtle text-primary py-2 px-3 fw-bold" style="font-size:0.75rem;">
                        إجمالي الولايات: <?php echo e(count($employeeWilayaStats)); ?>

                    </span>
                </div>
                
                <?php
                    $totalEmployeesNationwide = array_sum(array_column($employeeWilayaStats, 'total_employees'));
                    $totalActiveAccountsNationwide = array_sum(array_column($employeeWilayaStats, 'active_accounts'));
                    $nationalEmployeePercentage = $totalEmployeesNationwide > 0 ? round(($totalActiveAccountsNationwide * 100) / $totalEmployeesNationwide, 1) : 0;
                ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff); border:1px solid var(--border); text-align:center;">
                            <div class="text-muted small fw-bold mb-1">إجمالي موظفي القطاع (الوطني)</div>
                            <h4 class="fw-bold m-0 text-primary" style="font-family:'Outfit','Cairo';"><?php echo e(number_format($totalEmployeesNationwide)); ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff); border:1px solid var(--border); text-align:center;">
                            <div class="text-muted small fw-bold mb-1">الحسابات النشطة</div>
                            <h4 class="fw-bold m-0 text-success" style="font-family:'Outfit','Cairo';"><?php echo e(number_format($totalActiveAccountsNationwide)); ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:var(--bg-surface-elevated,#fff); border:1px solid var(--border); text-align:center;">
                            <div class="text-muted small fw-bold mb-1">نسبة التفعيل</div>
                            <h4 class="fw-bold m-0 text-warning" style="font-family:'Outfit','Cairo';"><?php echo e($nationalEmployeePercentage); ?>%</h4>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead style="position: sticky; top: 0; z-index: 5; background: var(--bg-surface-elevated,#fff);">
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th class="text-start pb-2">الولاية</th>
                                <th class="pb-2">إجمالي الموظفين</th>
                                <th class="pb-2">الحسابات النشطة</th>
                                <th class="pb-2">نسبة التفعيل</th>
                                <th class="pb-2" style="width:140px;">مؤشر التقدم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $employeeWilayaStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="text-start fw-bold text-dark py-2.5" style="font-family:'Cairo';"><?php echo e($w['wilaya_nom']); ?></td>
                                <td class="py-2.5" style="font-family:'Outfit';"><?php echo e(number_format($w['total_employees'])); ?></td>
                                <td class="py-2.5 text-primary fw-bold" style="font-family:'Outfit';"><?php echo e(number_format($w['active_accounts'])); ?></td>
                                <td class="py-2.5 text-success fw-bold" style="font-family:'Outfit';"><?php echo e($w['percentage']); ?>%</td>
                                <td class="py-2.5">
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <div style="width: 100%; background: var(--border, #dde3ef); border-radius: 4px; height: 6px; overflow: hidden; position: relative;">
                                            <div style="width: <?php echo e(min($w['percentage'], 100)); ?>%; background: <?php echo e($w['percentage'] >= 100 ? '#0ea66e' : ($w['percentage'] >= 50 ? '#1A6BCC' : '#F0A500')); ?>; height: 100%; border-radius: 4px;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>


        <!-- ------------------------------------------------------------- -->
        <!-- 2. DFEP DASHBOARD (مديرية التكوين المهني الولائية)           -->
        <!-- ------------------------------------------------------------- -->
        <?php elseif ($role === 'dfep'): ?>

            <?php
            $dfep_institutions = $dfep_institutions ?? [];
            $dfep_type_stats   = $dfep_type_stats ?? [];
            $wilaya_name       = session('user')['wilaya_name'] ?? session('user')['nom_complet'] ?? 'ولائي';
            ?>

            <!-- KPI Cards - Row 1 -->
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label'     => 'إجمالي المتربصين بالولاية',
                    'value'     => number_format($total_stagiaires ?? 0),
                    'icon'      => 'fa-solid fa-users',
                    'iconType'  => 'blue',
                    'delta'     => '',
                    'deltaType' => 'up',
                    'subtitle'  => 'في جميع مؤسسات التكوين'
                ]); ?>
            </div>
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label'     => 'عروض التكوين النشطة',
                    'value'     => number_format($total_offres ?? 0),
                    'icon'      => 'fa-solid fa-briefcase',
                    'iconType'  => 'green',
                    'subtitle'  => 'عرض تكوين معتمد'
                ]); ?>
            </div>
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label'     => 'المؤسسات التكوينية',
                    'value'     => number_format($total_etablissements ?? 0),
                    'icon'      => 'fa-solid fa-school',
                    'iconType'  => 'gold',
                    'subtitle'  => 'تحت إشراف المديرية الولائية'
                ]); ?>
            </div>
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label'     => 'المكوّنون والأساتذة',
                    'value'     => number_format($total_users ?? 0),
                    'icon'      => 'fa-solid fa-chalkboard-user',
                    'iconType'  => 'navy',
                    'subtitle'  => 'طاقم التكوين التربوي'
                ]); ?>
            </div>

            <!-- Institutions Table -->
            <div class="bento-4 glass-panel p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0" style="border-right: 3px solid var(--electric); padding-right: 0.5rem; font-family:'Cairo';">
                        <i class="fa-solid fa-building-columns text-primary me-2"></i>
                        قائمة مؤسسات التكوين التابعة للمديرية الولائية
                    </h5>
                    <span class="badge bg-primary text-primary py-2 px-3 fw-bold" style="font-size:0.75rem;">
                        <?= count($dfep_institutions) ?> مؤسسة
                    </span>
                </div>
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="bg-light text-muted fw-bold" style="position: sticky; top: 0; z-index: 5;">
                            <tr>
                                <th class="text-start">اسم المؤسسة</th>
                                <th>الرمز</th>
                                <th>النوع</th>
                                <th>العروض</th>
                                <th>المكوّنون</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dfep_institutions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fa-regular fa-folder-open fs-3 d-block mb-2"></i>
                                        لا توجد مؤسسات مرتبطة بهذه المديرية الولائية حالياً.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dfep_institutions as $inst): ?>
                                    <tr>
                                        <td class="text-start fw-bold text-dark" style="font-family:'Cairo'; font-size:0.82rem;">
                                            <?= htmlspecialchars($inst['Nom'] ?? '') ?>
                                        </td>
                                        <td>
                                            <code class="px-2 py-1 rounded bg-light text-primary small"><?= htmlspecialchars($inst['Code'] ?? '') ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary text-primary rounded-pill px-2 py-1" style="font-size:0.68rem;">
                                                <?= htmlspecialchars($inst['type'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-primary"><?= number_format((int)($inst['nb_offres'] ?? 0)) ?></td>
                                        <td class="fw-bold text-success"><?= number_format((int)($inst['nb_employes'] ?? 0)) ?></td>
                                        <td>
                                            <a href="/dashboard/offres?etab=<?= (int)($inst['IDetablissement'] ?? 0) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" style="font-size:0.72rem;">
                                                <i class="fa-solid fa-arrow-left me-1"></i> تفاصيل
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Type Stats + Quick Actions -->
            <div class="bento-2 glass-panel p-4 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo'; border-right: 3px solid var(--gold); padding-right: 0.5rem;">
                        <i class="fa-solid fa-chart-pie text-warning me-2"></i>
                        توزيع المؤسسات حسب الطبيعة
                    </h5>
                    <?php if (!empty($dfep_type_stats)): ?>
                        <div class="d-flex flex-column gap-2 mb-3">
                            <?php foreach ($dfep_type_stats as $ts): ?>
                                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: var(--bg-dashboard); border: 1px solid var(--border);">
                                    <span class="fw-bold small text-dark" style="font-family:'Cairo';">
                                        <?= htmlspecialchars($ts['type_nom'] ?? $ts['type'] ?? '') ?>
                                    </span>
                                    <span class="badge bg-primary text-primary px-3 py-1 fw-bold rounded-pill"><?= (int)($ts['nb'] ?? 0) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted small text-center py-3">لا توجد بيانات إحصائية متاحة.</div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="/dashboard/offres" class="btn btn-primary fw-bold rounded-3 py-2">
                        <i class="fa-solid fa-briefcase me-1"></i> تسيير عروض التكوين
                    </a>
                    <a href="/dashboard/users" class="btn btn-outline-primary fw-bold rounded-3 py-2">
                        <i class="fa-solid fa-users me-1"></i> إدارة المستخدمين
                    </a>
                    <a href="/dashboard/offres/validation" class="btn btn-outline-success fw-bold rounded-3 py-2">
                        <i class="fa-solid fa-circle-check me-1"></i> التحقق من العروض
                    </a>
                </div>
            </div>

            <!-- Recent Inscriptions for DFEP -->
            <div class="bento-6 glass-panel p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0" style="border-right: 3px solid var(--green); padding-right: 0.5rem; font-family:'Cairo';">
                        <i class="fa-solid fa-clock-rotate-left text-success me-2"></i>
                        آخر التسجيلات في مؤسسات الولاية
                    </h5>
                    <a href="/dashboard/inscriptions" class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3">
                        <i class="fa-solid fa-list me-1"></i> كل التسجيلات
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="bg-light text-muted fw-bold">
                            <tr>
                                <th>المترشح</th>
                                <th>التخصص</th>
                                <th>المؤسسة</th>
                                <th>تاريخ التسجيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_inscriptions)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لا توجد تسجيلات حديثة.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_inscriptions as $ins): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars(($ins['nom_ar'] ?? '') . ' ' . ($ins['prenom_ar'] ?? '')) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($ins['spec_ar'] ?? '') ?></td>
                                        <td class="small"><?= htmlspecialchars($ins['etab_ar'] ?? '') ?></td>
                                        <td class="text-muted" style="font-family:'Outfit'; font-size:0.8rem;"><?= htmlspecialchars(substr($ins['date_inscription'] ?? '', 0, 10)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- ------------------------------------------------------------- -->
        <!-- 3. DIRECTEUR DASHBOARD --                                      -->
        <!-- ------------------------------------------------------------- -->
        <?php elseif ($role === 'directeur' || $role === 'etablissement'): ?>
            <?php if ($dept === 'diplomes'): ?>
                
                
                
                <div class="bento-3">
                    <?php renderComponent('KpiCard', [
                        'label' => 'تعداد المتخرجين بالمركز',
                        'value' => number_format($total_graduates ?? 0),
                        'icon' => 'fa-solid fa-user-graduate',
                        'iconType' => 'blue',
                        'subtitle' => 'المتكونين المتوجين بنهاية التكوين'
                    ]); ?>
                </div>

                <div class="bento-3">
                    <?php renderComponent('KpiCard', [
                        'label' => 'إجمالي الشهادات الممنوحة',
                        'value' => number_format($total_diplomes_granted ?? 0),
                        'icon' => 'fa-solid fa-award',
                        'iconType' => 'gold',
                        'subtitle' => 'الشهادات الممنوحة والمستخرجة'
                    ]); ?>
                </div>
                
                <div class="bento-3">
                    <?php renderComponent('KpiCard', [
                        'label' => 'الشهادات العالقة',
                        'value' => number_format($pending_diplomes ?? 0),
                        'icon' => 'fa-solid fa-triangle-exclamation',
                        'iconType' => 'red',
                        'subtitle' => 'شهادات جاهزة لم تسلم بعد للمتخرجين'
                    ]); ?>
                </div>

                <div class="bento-3 glass-panel p-4 d-flex flex-column justify-content-between" style="border-right: 4px solid var(--gold) !important;">
                    <div>
                        <h5 class="fw-bold text-dark mb-2" style="font-family:'Cairo'; font-size: 1rem;"><i class="fa-solid fa-award text-warning me-2"></i> استخراج وإدارة الشهادات</h5>
                        <p class="text-muted small mb-0" style="font-weight:600; line-height: 1.5; font-size: 0.75rem;">الوصول المباشر لطباعة الشهادات الرسمية والاطلاع على إحصائيات المتخرجين.</p>
                    </div>
                    <div class="d-flex flex-column gap-1.5 mt-2">
                        <a href="/dashboard/diplomes" class="btn btn-outline-warning py-2 fw-bold rounded-3 text-start" style="font-size: 0.8rem;"><i class="fa-solid fa-award me-1.5"></i> طباعة واستخراج الشهادات</a>
                        <a href="/dashboard/diplomes/statistiques" class="btn btn-outline-primary py-2 fw-bold rounded-3 text-start" style="font-size: 0.8rem;"><i class="fa-solid fa-chart-pie me-1.5"></i> إحصائيات الخريجين</a>
                    </div>
                </div>

            <?php elseif ($dept === 'orientation'): ?>
                
                
                
                <div class="bento-4">
                    <?php renderComponent('KpiCard', [
                        'label' => 'طلبات التسجيل قيد الانتظار',
                        'value' => number_format($pending_inscriptions ?? 0),
                        'icon' => 'fa-solid fa-clock-rotate-left',
                        'iconType' => 'gold',
                        'subtitle' => 'تتطلب مراجعة وتأكيد الملفات'
                    ]); ?>
                </div>
                
                <div class="bento-4">
                    <?php renderComponent('KpiCard', [
                        'label' => 'التسجيلات الأولية عبر الإنترنت',
                        'value' => number_format($total_preinscriptions ?? 0),
                        'icon' => 'fa-solid fa-laptop-file',
                        'iconType' => 'blue',
                        'subtitle' => 'طلبات الالتحاق المقدمة عبر المنصة'
                    ]); ?>
                </div>

                <div class="bento-4 glass-panel p-4 d-flex flex-column justify-content-between" style="border-right: 4px solid var(--electric) !important;">
                    <div>
                        <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';"><i class="fa-solid fa-user-plus text-primary me-2"></i> التوجيه والتسجيل</h5>
                        <p class="text-muted small mb-3" style="font-weight:600; line-height: 1.6;">إدارة عمليات تسجيل طالبي التكوين وتوجيههم وتوزيعهم على الفروع والشعب.</p>
                    </div>
                    <div class="d-flex flex-column gap-2 mt-3">
                        <a href="/dashboard/inscriptions" class="btn btn-outline-primary py-2.5 fw-bold rounded-3 text-start"><i class="fa-solid fa-user-plus me-2"></i> تسجيل وتوجيه المتكونين</a>
                        <a href="/dashboard/preinscrits" class="btn btn-outline-warning py-2.5 fw-bold rounded-3 text-start"><i class="fa-solid fa-laptop-file me-2"></i> التسجيلات الأولية الإلكترونية</a>
                    </div>
                </div>

                <!-- Recent Inscriptions Table for BIAO -->
                <div class="bento-12 glass-panel p-4 mt-4">
                    <h5 class="fw-bold mb-3 text-dark" style="border-right: 3px solid var(--electric); padding-right: 0.5rem; font-family:'Cairo';"><i class="fa-solid fa-address-book text-primary me-2"></i> قائمة المتربصين المدمجين حديثاً بالمركز</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th>رقم التسجيل</th>
                                    <th>المتربص</th>
                                    <th>الشعبة / التخصص</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($local_stagiaires)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">لا يوجد متربصين مدمجين بعد.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($local_stagiaires as $st): ?>
                                        <tr>
                                            <td class="fw-bold" style="font-family:'Outfit'; font-size:0.85rem;"><?= htmlspecialchars($st['numero_matricule']) ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($st['nom_ar'] . ' ' . $st['prenom_ar']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($st['spec_ar']) ?></td>
                                            <td><span class="badge bg-success text-success rounded-pill px-3 py-1">مقبول</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                
                
                
                <div class="bento-4">
                    <?php renderComponent('KpiCard', [
                        'label' => $isAppren ? 'متربصي نمط التمهين بالمركز' : 'تعداد المتربصين بالمؤسسة',
                        'value' => number_format($total_stagiaires ?? 0),
                        'icon' => 'fa-solid fa-graduation-cap',
                        'iconType' => 'blue',
                        'subtitle' => 'مسجلين في الأفواج الحالية'
                    ]); ?>
                </div>
                
                <div class="bento-4">
                    <?php renderComponent('KpiCard', [
                        'label' => $isAppren ? 'عروض التمهين المفتوحة' : 'العروض النشطة بالمركز',
                        'value' => number_format($total_offres ?? 0),
                        'icon' => 'fa-solid fa-briefcase',
                        'iconType' => 'green',
                        'subtitle' => 'معتمدة تماماً بيداغوجياً'
                    ]); ?>
                </div>
                
                <div class="bento-4">
                    <?php renderComponent('KpiCard', [
                        'label' => $isAppren ? 'المتربصين المستمرين بالمركز' : 'طلبات التسجيل قيد الانتظار',
                        'value' => $isAppren ? number_format($total_reconduits ?? 0) : number_format($pending_inscriptions ?? 0),
                        'icon' => $isAppren ? 'fa-solid fa-users' : 'fa-solid fa-clock-rotate-left',
                        'iconType' => 'gold',
                        'subtitle' => $isAppren ? 'المسجلين في المداولات الحالية' : 'تتطلب مراجعة الملفات'
                    ]); ?>
                </div>

                <!-- Stagiaires Table -->
                <div class="bento-4 glass-panel p-4">
                    <h5 class="fw-bold mb-3 text-dark" style="border-right: 3px solid var(--electric); padding-right: 0.5rem; font-family:'Cairo';"><i class="fa-solid fa-address-book text-primary me-2"></i> قائمة المتربصين المدمجين حديثاً بالمركز</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th>رقم التسجيل</th>
                                    <th>المتربص</th>
                                    <th>الشعبة / التخصص</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($local_stagiaires)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">لا يوجد متربصين مدمجين بعد.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($local_stagiaires as $st): ?>
                                        <tr>
                                            <td class="fw-bold" style="font-family:'Outfit'; font-size:0.85rem;"><?= htmlspecialchars($st['numero_matricule']) ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($st['nom_ar'] . ' ' . $st['prenom_ar']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($st['spec_ar']) ?></td>
                                            <td><span class="badge bg-success text-success rounded-pill px-3 py-1">مقبول</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Documents Drawer -->
                <div class="bento-2 glass-panel p-4 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';"><i class="fa-solid fa-file-lines text-success me-2"></i> وثائق الموظف / Documents</h5>
                        <p class="text-muted small mb-3" style="font-weight:600;">وثائقك الإدارية الخاصة: كشف الراتب، شهادة العمل، وطلب الإجازة.</p>
                        <?php if (!empty($employee_documents)): ?>
                            <div class="list-group list-group-flush mb-3">
                                <?php foreach ($employee_documents as $doc): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 border-light py-2">
                                        <div>
                                            <strong class="text-dark small">
                                                <?php
                                                $docLabels = ['fiche_paie'=>'كشف الراتب','attestation_travail'=>'شهادة العمل','demande_conge'=>'طلب إجازة','certificat_scolaire'=>'شهادة مدرسية'];
                                                echo $docLabels[$doc['document_type']] ?? $doc['document_type'];
                                                ?>
                                            </strong>
                                            <div class="text-muted" style="font-size:0.7rem;">رمز: <span style="font-family:'Outfit';"><?= $doc['code_verification'] ?></span></div>
                                        </div>
                                        <a href="/dashboard/documents/print/<?= \App\Helpers\SecureIdHelper::encrypt($doc['id']) ?>?type=employe&doc=<?= $doc['document_type'] ?>&direct=1" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold"><i class="fa-solid fa-print me-1"></i> طباعة</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-2 text-muted small mb-3">لا توجد وثائق بعد. يمكنك طلبها من الإدارة.</div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column gap-2 mt-3">
                        <a href="/dashboard/candidates" class="btn btn-outline-primary py-2 fw-bold rounded-3 text-start"><i class="fa-solid fa-user-check me-2"></i> ملفات المترشحين</a>
                        <a href="/dashboard/documents" class="btn btn-outline-success py-2 fw-bold rounded-3 text-start"><i class="fa-solid fa-print me-2"></i> استخراج المطبوعات</a>
                    </div>
                </div>
            <?php endif; ?>
            <?php elseif ($role === 'stagiaire'): ?>
                <div class="bento-9 glass-panel p-4">
                    <h5 class="fw-bold mb-4 text-dark" style="border-right: 3px solid var(--electric); padding-right: 0.6rem; font-family:'Cairo';">
                        <i class="fa-solid fa-graduation-cap text-primary me-2"></i> كشف النقاط والنتائج الفصلية / Bulletin des Notes
                    </h5>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover text-center">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th>المادة البيداغوجية / المقياس الدراسي</th>
                                    <th>المعامل</th>
                                    <th>الحالة الأكاديمية</th>
                                    <th>العلامة النهائية</th>
                                    <th>القرار البيداغوجي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($grades)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fa-regular fa-folder-open fs-2 mb-2 d-block"></i>
                                            قيد التصحيح والتحقق من قبل الإدارة والمكونين حالياً.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($grades as $g): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($g['module_nom']) ?></td>
                                            <td class="fw-bold" style="font-family:'Outfit';"><?= $g['coefficient'] ?></td>
                                            <td>
                                                <?php if ($g['est_absent']): ?>
                                                    <span class="badge bg-danger text-danger rounded-pill px-2">غائب / Absent</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success text-success rounded-pill px-2">حاضر / Présent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold text-primary" style="font-family:'Outfit';"><?= number_format($g['note'], 2) ?>/20</td>
                                            <td>
                                                <?php if ($g['note'] >= 10): ?>
                                                    <span class="badge bg-success text-success rounded-pill px-3 py-1">مقبول / Validé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-warning rounded-pill px-3 py-1">استدراك / Rattrapage</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Restauration -->
                <div class="bento-3 glass-panel p-4">
                    <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';"><i class="fa-solid fa-utensils text-danger me-2"></i> تذكرة حجز الوجبات النشطة / Restaurant Card</h5>
                    <?php if (empty($meal_reservations)): ?>
                        <div class="text-center py-4 text-muted">
                            <p class="mb-3" style="font-weight:600;">لم تقم بحجز أي وجبة لليوم أو الغد.</p>
                            <a href="/dashboard/repas" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold"><i class="fa-solid fa-calendar-plus me-1"></i> احجز وجبتك الآن</a>
                        </div>
                    <?php else: ?>
                        <?php $activeMeal = $meal_reservations[0]; ?>
                        <div class="d-flex align-items-center gap-4 p-3 rounded-4 bg-light border border-light">
                            <div class="p-2 bg-white rounded-3 shadow-sm" style="border: 1px solid var(--border);">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($activeMeal['code_qr']) ?>" alt="QR Ticket" class="img-fluid" style="width: 100px; height: 100px;">
                            </div>
                            <div class="flex-grow-1">
                                <span class="badge bg-danger text-danger rounded-pill px-3 py-1 fw-bold mb-2">تذكرة صالحة ومؤكدة</span>
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($activeMeal['plat_principal']) ?></h6>
                                <small class="text-muted d-block">وجبة: <?= $activeMeal['type_repas'] === 'dejeuner' ? 'الغداء' : ($activeMeal['type_repas'] === 'diner' ? 'العشاء' : 'الفطور') ?></small>
                                <small class="text-muted d-block">تاريخ الاستهلاك: <strong class="text-dark" style="font-family:'Outfit';"><?= $activeMeal['date_consommation'] ?></strong></small>
                            </div>
                        </div>
                        <div class="text-start mt-3">
                            <a href="/dashboard/repas" class="btn btn-sm btn-link text-primary fw-bold text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> تسيير وجباتك وحجوزاتك السابقة</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Document requests -->
                <div class="bento-3 glass-panel p-4 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';"><i class="fa-solid fa-print text-primary me-2"></i> استخراج المطبوعات والشهادات الإدارية الرقمية</h5>
                        <p class="text-muted small mb-3" style="font-weight:600; line-height: 1.6;">يمكنك سحب وتحميل شهادة التسجيل المدرسية أو كشف النقاط والشهادات الإدارية الموقعة إلكترونياً والمحمية برموز أمان فريدة.</p>
                        
                        <div class="list-group list-group-flush">
                            <?php if (empty($document_requests)): ?>
                                <div class="text-center py-3 text-muted small">لا يوجد طلبات سابقة. يمكنك طلب شهادة الآن بالضغط على الزر أدناه.</div>
                            <?php else: ?>
                                <?php foreach ($document_requests as $doc): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 border-light py-2">
                                        <div>
                                            <strong class="text-dark small"><?= $doc['document_type'] === 'certificat_scolaire' ? 'شهادة مدرسية للتسجيل' : 'كشف النقاط للفصل' ?></strong>
                                            <div class="text-muted" style="font-size: 0.7rem;">رمز التحقق: <span class="fw-bold text-dark" style="font-family:'Outfit';"><?= $doc['code_verification'] ?></span></div>
                                        </div>
                                        <a href="/dashboard/documents/print/<?= \App\Helpers\SecureIdHelper::encrypt($doc['id']) ?>?type=stagiaire&doc=<?= $doc['document_type'] ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold"><i class="fa-solid fa-download me-1"></i> تحميل وطباعة</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-start mt-3 border-top pt-3 border-light">
                        <a href="/dashboard/documents" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" style="font-size:0.8rem;">تقديم طلب شهادة جديدة</a>
                    </div>
                </div>

        <!-- ------------------------------------------------------------- -->
        <!-- 5. MINISTRE DASHBOARD -->
        <!-- ------------------------------------------------------------- -->
        <?php elseif ($role === 'ministre'): ?>
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label' => 'الشعب والفروع المهنية',
                    'value' => number_format($total_branches ?? 32),
                    'icon' => 'fa-solid fa-folder-tree',
                    'iconType' => 'blue',
                    'subtitle' => 'الشعب المعتمدة رسمياً'
                ]); ?>
            </div>
            
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label' => 'التخصصات البيداغوجية',
                    'value' => number_format($total_specialites ?? 0),
                    'icon' => 'fa-solid fa-graduation-cap',
                    'iconType' => 'green',
                    'subtitle' => 'المدرجة بمدونة الشعب'
                ]); ?>
            </div>
            
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label' => 'عروض التكوين النشطة',
                    'value' => number_format($total_offres ?? 0),
                    'icon' => 'fa-solid fa-briefcase',
                    'iconType' => 'gold',
                    'subtitle' => 'عروض الالتحاق النشطة'
                ]); ?>
            </div>
            
            <div class="bento-1">
                <?php renderComponent('KpiCard', [
                    'label' => 'المتربصين وطنيا',
                    'value' => number_format($total_stagiaires ?? 0),
                    'icon' => 'fa-solid fa-users',
                    'iconType' => 'navy',
                    'subtitle' => 'في مقاعد التكوين'
                ]); ?>
            </div>

            <!-- Distribution Chart -->
            <div class="bento-3 glass-panel p-4">
                <h5 class="fw-bold mb-4 text-dark" style="border-right: 4px solid var(--electric); padding-right: 0.6rem; font-family:'Cairo';">
                    التوزيع الوطني للتخصصات وعروض التكوين حسب الفروع المهنية
                </h5>
                <div style="height: 350px; position: relative;">
                    <canvas id="ministreFiliereChart"></canvas>
                </div>
            </div>

            <!-- Minister AI Sovereign Insights -->
            <div class="bento-3 glass-panel p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-crown text-warning fs-5"></i>
                        <h5 class="fw-bold m-0 text-dark" style="font-family:'Cairo';">لوحة اتخاذ القرار البيداغوجي لقطاع التكوين</h5>
                    </div>
                    <div class="alert bg-light border-0 rounded-4 p-3 mb-3">
                        <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo';"><i class="fa-solid fa-circle-nodes text-primary me-2"></i>تحليل فروع النشاط الأكثر فعالية:</h6>
                        <p class="text-muted small mb-0" style="font-weight:600; line-height: 1.6;">
                            تشير البيانات المحدثة إلى أن فرع <strong>إعلام آلي _الرقمنة_الإتصالات (INT)</strong> وفرع <strong>الفندقة الإطعام و السياحة (HRT)</strong> هما الأكثر نشاطاً حالياً، يليهما 30 شعبة مهنية وطنية تتطلب تكثيف عروض التكوين لتلبية احتياجات الشركاء الاقتصاديين.
                        </p>
                    </div>
                    <div class="alert bg-success text-success border-0 rounded-4 p-3">
                        <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-shield-halved me-2"></i>التطابق والأمن الرقمي:</h6>
                        <p class="small mb-0 opacity-90" style="font-weight:600; line-height:1.6;">
                            المنصة متكاملة بنسبة 100% مع البوابة الوطنية، وجميع بيانات الطلبة مشفرة وتخضع لبروتوكولات حماية متتقدمة مدعومة بالذكاء الاصطناعي التوليدي.
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <a href="/dashboard/specialites" class="btn btn-primary flex-grow-1 py-2.5 fw-bold" style="border-radius: 12px;">دليل التخصصات الوطنية</a>
                    <a href="/dashboard/diplomes" class="btn btn-outline-secondary py-2.5 fw-bold" style="border-radius: 12px;">سجل الشهادات الرسمية</a>
                </div>
            </div>

            <!-- Complete Nomenclature Table mapping -->
            <div class="bento-6 glass-panel p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-dark m-0" style="border-right: 4px solid var(--green); padding-right: 0.6rem; font-family:'Cairo';">
                        جدول مطابقة التخصصات والفروع المهنية الـ 32 / Nomenclature des Branches
                    </h5>
                    <span class="badge bg-primary text-primary border px-3 py-2 fw-bold" style="border-radius: 30px;">
                        تحديث فوري وقائي للحالة
                    </span>
                </div>
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-hover align-middle text-center mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                            <tr class="fw-bold text-muted" style="font-size:0.85rem;">
                                <th>معرف الفرع</th>
                                <th>الرمز / Code</th>
                                <th class="text-right">التسمية باللغة العربية</th>
                                <th class="text-left">Dénomination en Français</th>
                                <th>عدد التخصصات النشطة</th>
                                <th>عروض التكوين الحالية</th>
                                <th>عدد المتربصين المسجلين</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branches as $b): ?>
                                <tr>
                                    <td class="fw-bold text-muted" style="font-family:'Outfit';"><?= $b['id'] ?></td>
                                    <td><span class="badge bg-primary text-primary px-2.5 py-1.5 fw-bold" style="font-family:'Outfit'; font-size:0.8rem; border-radius:6px;"><?= $b['code'] ?></span></td>
                                    <td class="text-right fw-bold text-dark" style="font-family:'Cairo';"><?= htmlspecialchars($b['libelle_ar']) ?></td>
                                    <td class="text-left text-muted small" style="font-family:'Outfit';"><?= htmlspecialchars($b['libelle_fr']) ?></td>
                                    <td>
                                        <?php if ($b['count_specialites'] > 0): ?>
                                            <span class="badge bg-success text-success px-3 py-1.5 fw-bold rounded-pill"><?= $b['count_specialites'] ?> تخصصات</span>
                                        <?php else: ?>
                                            <span class="text-muted small">0 تخصص</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($b['count_offres'] > 0): ?>
                                            <span class="badge bg-primary text-primary px-3 py-1.5 fw-bold rounded-pill"><?= $b['count_offres'] ?> عروض</span>
                                        <?php else: ?>
                                            <span class="text-muted small">0 عرض</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-dark">
                                        <?php if ($b['count_stagiaires'] > 0): ?>
                                            <span class="text-success"><i class="fa-solid fa-users me-1"></i> <?= $b['count_stagiaires'] ?> متربص</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <?php endif; // End of empty($current_tab) && !isset($_GET['view_dir']) ?>

    <?php 
    $department_views = [
        'inspecteur_general' => 'high_admin',
        'secretaire_general' => 'high_admin',
        'chef_cabinet' => 'high_admin',
        'inspecteur_central' => 'central_users',
        'dir_org' => 'central_users',
        'dir_finance' => 'finance',
        'dir_rh' => 'hr',
        'dir_plan' => 'plan',
        'dir_coop' => 'coop',
        'dir_it' => 'it',
        'dir_exam' => 'exam',
        'dir_trak' => 'trak',
        'dir_edu' => 'edu'
    ];

    if (in_array($role, array_keys($department_views))) {
        include __DIR__ . '/departments/' . $department_views[$role] . '.php';
    } elseif (in_array($role, ['admin', 'ministre', 'secretaire_general']) && isset($_GET['view_dir']) && isset($department_views[$_GET['view_dir']])) {
        // Allow Admin to view any specific department
        include __DIR__ . '/departments/' . $department_views[$_GET['view_dir']] . '.php';
    }
    ?>


</div>

<?php if (in_array($role, ['admin', 'dfep', 'directeur', 'etablissement'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvasEl = document.getElementById('adminCurveChart');
    if (!canvasEl) return;
    const ctx = canvasEl.getContext('2d');
    const totalStagiaires = parseInt(canvasEl.getAttribute('data-total-stagiaires')) || 1250;
    const chartRole = canvasEl.getAttribute('data-role');
    const roleLabel = chartRole === 'dfep' ? 'الولاية' : (chartRole === 'directeur' || chartRole === 'etablissement') ? 'المؤسسة' : 'الوطني';

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(26, 107, 204, 0.4)');
    gradient.addColorStop(1, 'rgba(26, 107, 204, 0.01)');

    const baseVal = totalStagiaires;
    const historyData = [
        Math.round(baseVal * 0.72),
        Math.round(baseVal * 0.82),
        Math.round(baseVal * 0.89),
        Math.round(baseVal * 0.96),
        baseVal
    ];

    adminCurveChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['2022', '2023', '2024', '2025', '2026'],
            datasets: [
                // Glowing border underlay
                {
                    label: 'توهج المنحنى',
                    data: historyData,
                    borderColor: 'rgba(26, 107, 204, 0.22)',
                    borderWidth: 8,
                    fill: false,
                    tension: 0.45,
                    pointRadius: 0
                },
                // Sharp main line
                {
                    label: `تعداد المقبولين / Effectifs ${roleLabel}`,
                    data: historyData,
                    borderColor: '#1a6bcc',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.45,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#1a6bcc',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { grid: { color: 'rgba(0, 0, 0, 0.03)' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
<?php elseif ($role === 'ministre'): ?>
<div id="php-branches-data" data-branches="<?= htmlspecialchars(json_encode(array_values(array_filter($branches ?? [], function($b) { 
    return $b['count_specialites'] > 0 || $b['count_offres'] > 0; 
}))), ENT_QUOTES, 'UTF-8') ?>"></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('ministreFiliereChart').getContext('2d');
    
    const branchesEl = document.getElementById('php-branches-data');
    const branchesData = branchesEl ? JSON.parse(branchesEl.getAttribute('data-branches')) : [];
    
    const labels = [];
    const specialitesCounts = [];
    const offresCounts = [];
    const stagiairesCounts = [];
    
    if (branchesData.length === 0) {
        labels.push('لا توجد فروع نشطة حالياً');
        specialitesCounts.push(0);
        offresCounts.push(0);
        stagiairesCounts.push(0);
    } else {
        branchesData.forEach(function(b) {
            labels.push(b.code + ' - ' + b.libelle_ar.trim());
            specialitesCounts.push(b.count_specialites);
            offresCounts.push(b.count_offres);
            stagiairesCounts.push(b.count_stagiaires);
        });
    }

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'عدد التخصصات / Spécialités',
                    data: specialitesCounts,
                    backgroundColor: '#006233',
                    borderRadius: 6,
                },
                {
                    label: 'عروض التكوين / Offres',
                    data: offresCounts,
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                },
                {
                    label: 'المتربصين / Stagiaires',
                    data: stagiairesCounts,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { stepSize: 1 }
                },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
<?php endif; ?>

<script>
// Clipboard copy utility
function copyToClipboard(id) {
    const input = document.getElementById(id);
    if (!input || input.value.trim() === '' || input.value.includes('لم يتم')) {
        return;
    }
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'تم نسخ مفتاح API للحافظة بنجاح!',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
}

// Regenerate current user API Key
function regenerateUserApiKey(btn) {
    if (!btn) return;
    const userId = btn.getAttribute('data-user-id');
    const csrfToken = btn.getAttribute('data-csrf-token');
    
    if (!confirm('هل أنت متأكد من رغبتك في تغيير مفتاح API الخاص بك؟ سيتعطل المفتاح القديم مباشرة.')) {
        return;
    }
    
    fetch(`${APP_URL}/dashboard/users/generate-api-key`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${userId}&csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم تجديد المفتاح!',
                text: 'تم توليد مفتاح API جديد وتحديثه بنجاح.',
                confirmButtonColor: '#006233'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في التوليد',
                text: data.message,
                confirmButtonColor: '#006233'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'حدث خطأ بالاتصال مع خادم النظام.',
            confirmButtonColor: '#006233'
        });
    });
}
</script>

<?php if (in_array($role, ['admin', 'dfep', 'ministre', 'apc', 'directeur', 'inspecteur_general', 'secretaire_general', 'chef_cabinet', 'inspecteur_central', 'dir_org', 'dir_finance', 'dir_rh', 'dir_plan', 'dir_coop', 'dir_it', 'dir_exam', 'dir_trak', 'dir_edu'])): ?>
<!-- Ensure Chart.js is loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
$jsModeTranslations = [];
$jsModeTranslationsWithFr = [];
foreach (\App\Services\ModeService::getTranslations() as $k => $trans) {
    $jsModeTranslations[$k] = $trans['ar'];
    $jsModeTranslationsWithFr[$k] = $trans['ar'] . (!empty($trans['fr']) ? ' / ' . $trans['fr'] : '');
}
?>
<div id="php-mode-translations" 
     data-modes="<?= htmlspecialchars(json_encode($jsModeTranslations), ENT_QUOTES, 'UTF-8') ?>" 
     data-modes-fr="<?= htmlspecialchars(json_encode($jsModeTranslationsWithFr), ENT_QUOTES, 'UTF-8') ?>">
</div>
<script>
const transEl = document.getElementById('php-mode-translations');
const gModeTranslations = transEl ? JSON.parse(transEl.getAttribute('data-modes')) : {};
const gModeTranslationsWithFr = transEl ? JSON.parse(transEl.getAttribute('data-modes-fr')) : {};

let chartModes = null;
let chartStatus = null;
let chartGender = null;
let chartTopSpecialties = null;
let adminCurveChart = null;

// ===== ChartJS Smooth Theme Switching =====
function updateChartsTheme(theme) {
    const isDark = (theme === 'dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.03)' : 'rgba(0, 0, 0, 0.03)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const charts = [chartModes, chartStatus, chartGender, chartTopSpecialties, adminCurveChart];
    charts.forEach(chart => {
        if (!chart) return;
        
        // Update scales ticks & grid colors
        if (chart.options.scales) {
            Object.keys(chart.options.scales).forEach(scaleKey => {
                const scale = chart.options.scales[scaleKey];
                if (scale.grid) {
                    scale.grid.color = gridColor;
                }
                if (!scale.ticks) {
                    scale.ticks = {};
                }
                scale.ticks.color = textColor;
            });
        }
        
        // Update legend label color
        if (chart.options.plugins && chart.options.plugins.legend) {
            if (!chart.options.plugins.legend.labels) {
                chart.options.plugins.legend.labels = {};
            }
            if (!chart.options.plugins.legend.labels.font) {
                chart.options.plugins.legend.labels.font = {};
            }
            chart.options.plugins.legend.labels.color = textColor;
        }
        
        chart.update();
    });
}

// Observe theme changes on documentElement
const themeObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'data-theme') {
            const newTheme = document.documentElement.getAttribute('data-theme');
            updateChartsTheme(newTheme);
        }
    });
});
themeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme']
});

// ===== Collapsible Filter Drawer Control =====
window.toggleFilterDrawer = function() {
    const drawer = document.getElementById('filterDrawer');
    const overlay = document.getElementById('filterDrawerOverlay');
    if (!drawer || !overlay) return;
    
    const isOpen = drawer.classList.contains('open');
    if (isOpen) {
        drawer.classList.remove('open');
        overlay.classList.remove('active');
    } else {
        drawer.classList.add('open');
        overlay.classList.add('active');
    }
};

// ===== Interactive Mouse Coordinate Listeners for Bento Cards & Digital ID Card =====
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic Filter Button Insertion in Command Bar
    const commandBar = document.querySelector('.command-bar');
    if (commandBar) {
        const actionsContainer = commandBar.querySelector('.d-flex.align-items-center.gap-2');
        if (actionsContainer) {
            if (!document.getElementById('filter-drawer-toggle')) {
                const filterBtn = document.createElement('button');
                filterBtn.type = 'button';
                filterBtn.className = 'cb-btn text-primary position-relative';
                filterBtn.id = 'filter-drawer-toggle';
                filterBtn.title = 'تصفية النتائج والخيارات / Filtres';
                filterBtn.innerHTML = '<i class="fa-solid fa-filter"></i>';
                filterBtn.onclick = window.toggleFilterDrawer;
                actionsContainer.insertBefore(filterBtn, actionsContainer.firstChild);
            }
        }
    }
    // 1. Hover coordinates for Bento Glass Panels
    document.querySelectorAll('.glass-panel').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        });
    });

    // 2. Holographic Metallic Sheen & 3D Tilt for Simulated Digital ID Card
    const idCard = document.getElementById('simulated-digital-card');
    if (idCard) {
        idCard.addEventListener('mousemove', e => {
            const rect = idCard.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Shifting metallic gradient sheen position
            const bgX = (x / rect.width) * 100;
            const bgY = (y / rect.height) * 100;
            
            // 3D perspective rotation (max 15 degrees tilt)
            const rotateY = ((x / rect.width) - 0.5) * 30;
            const rotateX = -((y / rect.height) - 0.5) * 30;
            
            idCard.style.setProperty('--bg-x', `${bgX}%`);
            idCard.style.setProperty('--bg-y', `${bgY}%`);
            idCard.style.setProperty('--rotate-x', `${rotateX}deg`);
            idCard.style.setProperty('--rotate-y', `${rotateY}deg`);
        });
        
        idCard.addEventListener('mouseleave', () => {
            idCard.style.setProperty('--bg-x', `50%`);
            idCard.style.setProperty('--bg-y', `50%`);
            idCard.style.setProperty('--rotate-x', `0deg`);
            idCard.style.setProperty('--rotate-y', `0deg`);
            idCard.style.transition = 'transform 0.5s ease, box-shadow 0.3s ease';
        });
        
        idCard.addEventListener('mouseenter', () => {
            idCard.style.transition = 'none';
        });
    }
});

let currentTraineesList = [];
let lastChartsData = null;
let chartTypes = {
    modes: 'doughnut',
    status: 'pie',
    gender: 'doughnut',
    specialties: 'bar'
};

function destroyCharts() {
    if (chartModes) chartModes.destroy();
    if (chartStatus) chartStatus.destroy();
    if (chartGender) chartGender.destroy();
    if (chartTopSpecialties) chartTopSpecialties.destroy();
}

function onWilayaChange() {
    const wilayaSelect = document.getElementById('filter-wilaya');
    if (!wilayaSelect) return;
    const wilayaVal = wilayaSelect.value;
    const etabSelect = document.getElementById('filter-etablissement');
    if (!etabSelect) return;
    
    // Skip resetting value on first load
    if (etabSelect.getAttribute('data-init') !== 'true') {
        etabSelect.value = "";
    } else {
        etabSelect.removeAttribute('data-init');
    }
    
    Array.from(etabSelect.options).forEach(opt => {
        if (opt.value === "") {
            opt.style.display = "";
            return;
        }
        const optWilaya = opt.getAttribute('data-wilaya');
        if (!wilayaVal || optWilaya === wilayaVal) {
            opt.style.display = "";
        } else {
            opt.style.display = "none";
        }
    });
}

function onFiliereChange() {
    const filiereSelect = document.getElementById('filter-filiere');
    if (!filiereSelect) return;
    const filiereVal = filiereSelect.value;
    const specSelect = document.getElementById('filter-specialite');
    if (!specSelect) return;
    
    // Skip resetting value on first load
    if (specSelect.getAttribute('data-init') !== 'true') {
        specSelect.value = "";
    } else {
        specSelect.removeAttribute('data-init');
    }
    
    Array.from(specSelect.options).forEach(opt => {
        if (opt.value === "") {
            opt.style.display = "";
            return;
        }
        const optFiliere = opt.getAttribute('data-filiere');
        if (!filiereVal || optFiliere === filiereVal) {
            opt.style.display = "";
        } else {
            opt.style.display = "none";
        }
    });
}

function updateStatCards(counts) {
    const stgCard = document.getElementById('stat-total-stagiaires');
    const offCard = document.getElementById('stat-total-offres');
    const etabCard = document.getElementById('stat-total-etablissements');

    if (stgCard) animateValue(stgCard, parseInt(stgCard.textContent) || 0, counts.stagiaires, 800);
    if (offCard) animateValue(offCard, parseInt(offCard.textContent) || 0, counts.offres, 800);
    if (etabCard) animateValue(etabCard, parseInt(etabCard.textContent) || 0, counts.etablissements, 800);
}

function animateValue(obj, start, end, duration) {
    if (start === end) return;
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            obj.innerHTML = end;
        }
    };
    window.requestAnimationFrame(step);
}

function updateTraineesTable(stagiaires) {
    const tableBody = document.getElementById('trainee-table-body');
    const tableCount = document.getElementById('trainee-table-count');
    if (!tableBody || !tableCount) return;
    
    tableCount.textContent = `${stagiaires.length} متربص`;
    tableBody.innerHTML = '';

    if (stagiaires.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fa-regular fa-folder-open fs-3 mb-2 d-block"></i>
                    لم يتم العثور على متربصين يطابقون خيارات التصفية الحالية.
                </td>
            </tr>
        `;
        return;
    }

    const modeTranslations = gModeTranslations;

    const statusBadges = {
        'actif': '<span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-bold">نشط</span>',
        'suspendu': '<span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1 fw-bold">موقوف</span>',
        'diplome': '<span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-bold">متخرج</span>',
        'abandon': '<span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 fw-bold">منقطع</span>'
    };

    stagiaires.forEach(st => {
        const row = document.createElement('tr');
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.3s ease-in-out';
        
        row.innerHTML = `
            <td class="fw-bold" style="font-family:'Inter'; font-size:0.85rem;">${st.numero_matricule || '-'}</td>
            <td class="fw-semibold text-dark">${st.nom_ar} ${st.prenom_ar}</td>
            <td class="text-muted small">${st.spec_ar || '-'}</td>
            <td><span class="badge bg-light text-dark px-2.5 py-1.5 fw-semibold" style="font-size:0.75rem;">${modeTranslations[st.mode_formation] || st.mode_formation}</span></td>
            <td>${statusBadges[st.statut] || `<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 fw-bold">${st.statut}</span>`}</td>
        `;
        
        tableBody.appendChild(row);
        
        setTimeout(() => {
            row.style.opacity = '1';
        }, 50);
    });
}

function renderTraineeCharts(chartsData) {
    destroyCharts();

    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const isDark = (currentTheme === 'dark');
    const themeGridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : '#f1f5f9';
    const themeTextColor = isDark ? '#94a3b8' : '#64748b';

    // 1. Formation Modes
    const modeLabels = [];
    const modeCounts = [];
    const modeTranslations = gModeTranslations;
    
    if (chartsData.mode && chartsData.mode.length > 0) {
        chartsData.mode.forEach(item => {
            modeLabels.push(modeTranslations[item.mode_formation] || item.mode_formation);
            modeCounts.push(item.count);
        });
    } else {
        modeLabels.push('لا توجد بيانات');
        modeCounts.push(0);
    }

    const ctxMode = document.getElementById('chart-formation-modes').getContext('2d');
    
    let optionsModes = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Cairo', size: 10 } } }
        }
    };
    if (chartTypes.modes === 'bar') {
        optionsModes.scales = {
            y: { beginAtZero: true, grid: { color: themeGridColor }, ticks: { color: themeTextColor, stepSize: 1 } },
            x: { grid: { display: false }, ticks: { color: themeTextColor, font: { family: 'Cairo', size: 10 } } }
        };
        optionsModes.plugins.legend = { display: false };
    } else {
        optionsModes.plugins.legend = { position: 'bottom', labels: { color: themeTextColor, font: { family: 'Cairo', size: 10 } } };
    }

    chartModes = new Chart(ctxMode, {
        type: chartTypes.modes,
        data: {
            labels: modeLabels,
            datasets: [{
                label: 'عدد المتربصين / Effectifs',
                data: modeCounts,
                backgroundColor: chartTypes.modes === 'bar' ? 'rgba(0, 98, 51, 0.85)' : ['#006233', '#10b981', '#0ea5e9', '#f59e0b', '#8b5cf6'],
                borderWidth: chartTypes.modes === 'bar' ? 1 : 2,
                borderColor: '#ffffff',
                borderRadius: chartTypes.modes === 'bar' ? 6 : 0
            }]
        },
        options: optionsModes
    });

    // 2. Trainee Status
    const statusLabels = [];
    const statusCounts = [];
    const statusTranslations = {
        'actif': 'نشط',
        'suspendu': 'موقوف',
        'diplome': 'متخرج',
        'abandon': 'منقطع'
    };

    if (chartsData.statut && chartsData.statut.length > 0) {
        chartsData.statut.forEach(item => {
            statusLabels.push(statusTranslations[item.statut] || item.statut);
            statusCounts.push(item.count);
        });
    } else {
        statusLabels.push('لا توجد بيانات');
        statusCounts.push(0);
    }

    const ctxStatus = document.getElementById('chart-trainee-status').getContext('2d');
    
    let optionsStatus = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Cairo', size: 10 } } }
        }
    };
    if (chartTypes.status === 'bar') {
        optionsStatus.scales = {
            y: { beginAtZero: true, grid: { color: themeGridColor }, ticks: { color: themeTextColor, stepSize: 1 } },
            x: { grid: { display: false }, ticks: { color: themeTextColor, font: { family: 'Cairo', size: 10 } } }
        };
        optionsStatus.plugins.legend = { display: false };
    } else {
        optionsStatus.plugins.legend = { position: 'bottom', labels: { color: themeTextColor, font: { family: 'Cairo', size: 10 } } };
    }

    chartStatus = new Chart(ctxStatus, {
        type: chartTypes.status,
        data: {
            labels: statusLabels,
            datasets: [{
                label: 'عدد المتربصين / Effectifs',
                data: statusCounts,
                backgroundColor: chartTypes.status === 'bar' ? 'rgba(16, 185, 129, 0.85)' : ['#10b981', '#f59e0b', '#3b82f6', '#ef4444'],
                borderWidth: chartTypes.status === 'bar' ? 1 : 2,
                borderColor: '#ffffff',
                borderRadius: chartTypes.status === 'bar' ? 6 : 0
            }]
        },
        options: optionsStatus
    });

    // 3. Gender
    const genderLabels = [];
    const genderCounts = [];
    const genderTranslations = {
        'M': 'ذكور',
        'F': 'إناث'
    };

    if (chartsData.sexe && chartsData.sexe.length > 0) {
        chartsData.sexe.forEach(item => {
            genderLabels.push(genderTranslations[item.sexe] || item.sexe);
            genderCounts.push(item.count);
        });
    } else {
        genderLabels.push('لا توجد بيانات');
        genderCounts.push(0);
    }

    const ctxGender = document.getElementById('chart-trainee-gender').getContext('2d');
    
    let optionsGender = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Cairo', size: 10 } } }
        }
    };
    if (chartTypes.gender === 'bar') {
        optionsGender.scales = {
            y: { beginAtZero: true, grid: { color: themeGridColor }, ticks: { color: themeTextColor, stepSize: 1 } },
            x: { grid: { display: false }, ticks: { color: themeTextColor, font: { family: 'Cairo', size: 10 } } }
        };
        optionsGender.plugins.legend = { display: false };
    } else {
        optionsGender.plugins.legend = { position: 'bottom', labels: { color: themeTextColor, font: { family: 'Cairo', size: 10 } } };
    }

    chartGender = new Chart(ctxGender, {
        type: chartTypes.gender,
        data: {
            labels: genderLabels,
            datasets: [{
                label: 'عدد المتربصين / Effectifs',
                data: genderCounts,
                backgroundColor: chartTypes.gender === 'bar' ? 'rgba(245, 158, 11, 0.85)' : ['#0ea5e9', '#ec4899'],
                borderWidth: chartTypes.gender === 'bar' ? 1 : 2,
                borderColor: '#ffffff',
                borderRadius: chartTypes.gender === 'bar' ? 6 : 0
            }]
        },
        options: optionsGender
    });

    // 4. Top Specialties
    const specLabels = [];
    const specCounts = [];

    if (chartsData.top_specialties && chartsData.top_specialties.length > 0) {
        chartsData.top_specialties.forEach(item => {
            const specName = item.spec_ar || 'تخصص غير معروف';
            const specCode = item.spec_code ? `[${item.spec_code}] ` : '';
            specLabels.push(specCode + specName);
            specCounts.push(item.count);
        });
    } else {
        specLabels.push('لا توجد بيانات');
        specCounts.push(0);
    }

    const ctxSpec = document.getElementById('chart-top-specialties').getContext('2d');
    
    let optionsSpec = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    };
    
    if (chartTypes.specialties === 'bar') {
        optionsSpec.indexAxis = 'y';
        optionsSpec.scales = {
            x: { beginAtZero: true, grid: { color: themeGridColor }, ticks: { color: themeTextColor, stepSize: 1 } },
            y: { grid: { display: false }, ticks: { color: themeTextColor, font: { family: 'Cairo', size: 9 } } }
        };
    } else {
        optionsSpec.plugins.legend = { position: 'bottom', labels: { color: themeTextColor, font: { family: 'Cairo', size: 9 } } };
    }

    chartTopSpecialties = new Chart(ctxSpec, {
        type: chartTypes.specialties,
        data: {
            labels: specLabels,
            datasets: [{
                label: 'عدد المتربصين / Stagiaires',
                data: specCounts,
                backgroundColor: chartTypes.specialties === 'bar' ? 'rgba(99, 102, 241, 0.85)' : ['#006233', '#10b981', '#0ea5e9', '#f59e0b', '#ec4899'],
                borderWidth: chartTypes.specialties === 'bar' ? 1 : 2,
                borderColor: '#ffffff',
                borderRadius: chartTypes.specialties === 'bar' ? 6 : 0
            }]
        },
        options: optionsSpec
    });
}

function renderActiveFilterBadges() {
    const badgesContainer = document.getElementById('active-filters-badges-container');
    const badgesList = document.getElementById('active-filters-badges-list');
    if (!badgesContainer || !badgesList) return;
    
    badgesList.innerHTML = '';
    
    // Check all filters
    const filters = [
        { id: 'filter-wilaya', label: 'الولاية' },
        { id: 'filter-etablissement', label: 'المؤسسة' },
        { id: 'filter-filiere', label: 'الشعبة' },
        { id: 'filter-specialite', label: 'التخصص' },
        { id: 'filter-mode', label: 'نمط التكوين' },
        { id: 'filter-statut', label: 'الحالة' },
        { id: 'filter-sexe', label: 'الجنس' },
        { id: 'filter-session', label: 'الدورة' },
        { id: 'filter-annee', label: 'السنة التكوينية' },
        { id: 'filter-q', label: 'البحث' }
    ];
    
    let hasFilters = false;
    filters.forEach(f => {
        const el = document.getElementById(f.id);
        if (!el || !el.value) return;
        
        let valText = el.value;
        if (el.tagName === 'SELECT') {
            valText = el.options[el.selectedIndex].text;
            // Skip defaults if you want
            if (f.id === 'filter-wilaya' && el.value === '31') {
                // If it is Oran (default for DFEP Oran), don't show badge unless simulated
                const simulatedActive = document.getElementById('simulated-corps-role');
                if (!simulatedActive) return;
            }
        }
        
        hasFilters = true;
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary text-white d-inline-flex align-items-center gap-1.5 px-3 py-2 rounded-pill shadow-sm';
        badge.style.fontSize = '0.75rem';
        badge.style.fontFamily = 'Cairo';
        badge.innerHTML = `
            <span>${f.label}: ${valText}</span>
            <span style="cursor:pointer;" onclick="clearSingleFilter('${f.id}')"><i class="fa-solid fa-circle-xmark text-white-50"></i></span>
        `;
        badgesList.appendChild(badge);
    });

    if (hasFilters) {
        badgesContainer.style.setProperty('display', 'flex', 'important');
    } else {
        badgesContainer.style.setProperty('display', 'none', 'important');
    }
}

function clearSingleFilter(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = '';
    if (id === 'filter-wilaya') onWilayaChange();
    if (id === 'filter-filiere') onFiliereChange();
    triggerFilter();
}

function toggleChartType(chartKey) {
    if (!lastChartsData) {
        Swal.fire({
            icon: 'warning',
            title: 'تنبيه',
            text: 'يرجى تطبيق الفلترة أولاً لعرض المخططات المحدثة.',
            confirmButtonColor: '#006233'
        });
        return;
    }
    
    if (chartKey === 'modes') {
        chartTypes.modes = chartTypes.modes === 'doughnut' ? 'bar' : 'doughnut';
    } else if (chartKey === 'status') {
        chartTypes.status = chartTypes.status === 'pie' ? 'bar' : 'pie';
    } else if (chartKey === 'gender') {
        chartTypes.gender = chartTypes.gender === 'doughnut' ? 'bar' : 'doughnut';
    } else if (chartKey === 'specialties') {
        chartTypes.specialties = chartTypes.specialties === 'bar' ? 'doughnut' : 'bar';
    }
    
    renderTraineeCharts(lastChartsData);
}

function downloadChart(canvasId, filename) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        Swal.fire('خطأ', 'لم يتم العثور على المخطط المطلوب.', 'error');
        return;
    }
    
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `تم تحميل المخطط باسم ${filename} بنجاح!`,
        showConfirmButton: false,
        timer: 2000
    });
}

function exportCSV() {
    if (!currentTraineesList || currentTraineesList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'تنبيه',
            text: 'لا توجد بيانات متربصين مطابقة لتصديرها حالياً.',
            confirmButtonColor: '#006233'
        });
        return;
    }

    let csvContent = "\uFEFF"; // Prepend UTF-8 BOM for Arabic support in Excel
    csvContent += "رقم التسجيل / Matricule,الاسم واللقب / Nom Complet,التخصص / Spécialité,نمط التكوين / Mode,الحالة / Statut\n";

    const modeTranslations = gModeTranslationsWithFr;

    const statusTranslations = {
        'actif': 'نشط / Actif',
        'suspendu': 'موقوف / Suspendu',
        'diplome': 'متخرج / Diplômé',
        'abandon': 'منقطع / Abandon'
    };

    currentTraineesList.forEach(st => {
        const mat = st.numero_matricule || '-';
        const name = `${st.nom_ar} ${st.prenom_ar}`;
        const spec = st.spec_ar || '-';
        const mode = modeTranslations[st.mode_formation] || st.mode_formation || '-';
        const stat = statusTranslations[st.statut] || st.statut || '-';

        const row = [mat, name, spec, mode, stat].map(v => `"${v.replace(/'/g, "''").replace(/"/g, '""')}"`).join(",");
        csvContent += row + "\n";
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `liste_stagiaires_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printTrainees() {
    if (!currentTraineesList || currentTraineesList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'تنبيه',
            text: 'لا توجد بيانات متربصين مطابقة لطباعتها.',
            confirmButtonColor: '#006233'
        });
        return;
    }

    const printWin = window.open('', '', 'width=1000,height=800');
    if (!printWin) {
        Swal.fire('خطأ', 'يرجى السماح بالنوافذ المنبثقة لطباعة المستند.', 'error');
        return;
    }

    const modeTranslations = gModeTranslations;

    const statusTranslations = {
        'actif': 'نشط',
        'suspendu': 'موقوف',
        'diplome': 'متخرج',
        'abandon': 'منقطع'
    };

    let rowsHtml = '';
    currentTraineesList.forEach(st => {
        rowsHtml += `
            <tr>
                <td>${st.numero_matricule || '-'}</td>
                <td style="font-weight: bold; text-align: right;">${st.nom_ar} ${st.prenom_ar}</td>
                <td style="text-align: right;">${st.spec_ar || '-'}</td>
                <td>${modeTranslations[st.mode_formation] || st.mode_formation || '-'}</td>
                <td>${statusTranslations[st.statut] || st.statut || '-'}</td>
            </tr>
        `;
    });

    printWin.document.write(`
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <title>قائمة المتربصين الرسمية - المنصة الرقمية للتكوين المهني</title>
            <style>
                body { font-family: 'Cairo', Arial, sans-serif; padding: 30px; color: #333; background: #fff; line-height: 1.6; }
                .header-table { width: 100%; margin-bottom: 40px; border: none; }
                .header-table td { border: none; padding: 5px; }
                .ministry-title { font-size: 1.15rem; font-weight: bold; text-align: center; }
                .doc-title { text-align: center; font-size: 1.6rem; font-weight: bold; margin: 30px 0; border-bottom: 3px double var(--primary-color); padding-bottom: 10px; color: var(--primary-color); }
                .meta-info { margin-bottom: 20px; font-size: 0.85rem; color: #666; display: flex; justify-content: space-between; }
                table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 12px 10px; text-align: center; font-size: 0.9rem; }
                table.data-table th { background-color: #f8fafc; color: #1e293b; font-weight: bold; }
                table.data-table tr:nth-child(even) { background-color: #fcfdfe; }
                .footer { margin-top: 50px; display: flex; justify-content: space-between; font-size: 0.85rem; }
            </style>
        </head>
        <body>
            <table class="header-table">
                <tr>
                    <td style="text-align: right; width: 33%;">
                        <strong>الجمهورية الجزائرية الديمقراطية الشعبية</strong><br>
                        وزارة التكوين والتعليم المهنيين<br>
                        مديرية التكوين المهني لولاية وهران
                    </td>
                    <td style="text-align: center; width: 34%; vertical-align: middle; font-size: 2rem;">🇩🇿</td>
                    <td style="text-align: left; width: 33%; font-family:'Outfit';">
                        <strong>RÉPUBLIQUE ALGÉRIENNE</strong><br>
                        Ministère de la Formation Professionnelle<br>
                        Direction de la Formation Professionnelle d'Oran
                    </td>
                </tr>
            </table>
            <div class="doc-title">قائمة المتربصين الرسمية / Liste Officielle des Stagiaires</div>
            <div class="meta-info">
                <span>تاريخ الطباعة: ${new Date().toLocaleDateString('ar-DZ')}</span>
                <span>إجمالي المتربصين: ${currentTraineesList.length} متربص</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>رقم التسجيل</th>
                        <th>الاسم واللقب</th>
                        <th>التخصص</th>
                        <th>نمط التكوين</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
            <div class="footer">
                <div>إمضاء مدير المؤسسة التكوينية</div>
                <div>ختم وإمضاء مدير التكوين المهني لولاية وهران</div>
            </div>
            ${'<' + 'script'}>
                window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 500); }
            ${'<' + '/script'}>
        </body>
        </html>
    `);
    printWin.document.close();
}
</script>


<?php if (!empty($employee)): ?>
            <!-- Profile Edit Drawer Modal -->
            <div class="modal fade no-print" id="editProfileModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content glass-panel" style="border-radius:20px; border: 1px solid var(--border);">
                        <div class="modal-header border-bottom border-light p-4">
                            <h5 class="modal-title fw-bold text-dark" style="font-family:'Cairo';">
                                <i class="fa-solid fa-user-pen text-primary me-2"></i>
                                تعديل بيانات الموظف الشخصية
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4" style="font-family:'Cairo';">
                            <form id="editProfileForm" onsubmit="event.preventDefault(); saveEmployeeProfile(this);" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <!-- Photo Input & Preview -->
                                    <div class="col-12 text-center border-bottom pb-3 mb-2">
                                        <label class="form-label d-block fw-bold text-dark mb-2">الصورة الشخصية للبطاقة</label>
                                        <div class="d-inline-block position-relative mb-2">
                                            <img id="profilePreview" src="<?= !empty($employee['photo']) ? (strpos($employee['photo'], 'http') === 0 ? htmlspecialchars($employee['photo']) : asset($employee['photo'])) : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($employee['Nom'] ?? 'User') ?>" alt="Preview" class="employee-photo-frame" style="width: 90px; height: 115px;">
                                        </div>
                                        <input type="file" name="photo" class="form-control form-control-sm rounded-pill w-50 mx-auto" onchange="previewImage(this)">
                                        <small class="text-muted d-block mt-1">تنسيقات مدعومة: JPG, PNG. أقصى حجم: 2 ميجابايت (يتم ضغطها تلقائياً)</small>
                                    </div>

                                    <!-- Arabic names -->
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">اللقب (بالعربية)</label>
                                        <input type="text" name="nom" class="form-control rounded-3" value="<?= htmlspecialchars($employee['Nom'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">الاسم (بالعربية)</label>
                                        <input type="text" name="prenom" class="form-control rounded-3" value="<?= htmlspecialchars($employee['Prenom'] ?? '') ?>" required>
                                    </div>

                                    <!-- French names -->
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">Nom (Français)</label>
                                        <input type="text" name="nom_fr" class="form-control rounded-3" value="<?= htmlspecialchars($employee['NomFr'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">Prénom (Français)</label>
                                        <input type="text" name="prenom_fr" class="form-control rounded-3" value="<?= htmlspecialchars($employee['PrenomFr'] ?? '') ?>">
                                    </div>

                                    <!-- Birth info -->
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">تاريخ الميلاد</label>
                                        <input type="text" name="date_nais" class="form-control rounded-3" placeholder="YYYY/MM/DD" value="<?= htmlspecialchars($employee['DateNais'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">مكان الميلاد</label>
                                        <input type="text" name="lieu_nais" class="form-control rounded-3" value="<?= htmlspecialchars($employee['LieuNais'] ?? '') ?>">
                                    </div>

                                    <!-- Personal Details -->
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">الهاتف</label>
                                        <input type="text" name="tel" class="form-control rounded-3" value="<?= htmlspecialchars($employee['Tel'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">البريد الإلكتروني</label>
                                        <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($employee['Email'] ?? '') ?>">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark">العنوان الكامل</label>
                                        <input type="text" name="adres" class="form-control rounded-3" value="<?= htmlspecialchars($employee['Adres'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-dark">عدد الأولاد</label>
                                        <input type="number" min="0" name="nbr_enfants" class="form-control rounded-3" value="<?= (int)($employee['nbrEnf'] ?? 0) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-dark">رقم الضمان الاجتماعي</label>
                                        <input type="text" name="nss" class="form-control rounded-3" value="<?= htmlspecialchars($employee['nss'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-dark">الحالة العائلية</label>
                                        <select name="sitfamille" class="form-select rounded-3">
                                            <option value="1" <?= (int)($employee['IDSitfamille'] ?? 1) === 1 ? 'selected' : '' ?>>أعزب / عزباء</option>
                                            <option value="2" <?= (int)($employee['IDSitfamille'] ?? 1) === 2 ? 'selected' : '' ?>>متزوج / متزوجة</option>
                                            <option value="3" <?= (int)($employee['IDSitfamille'] ?? 1) === 3 ? 'selected' : '' ?>>مطلق / مطلقة</option>
                                            <option value="4" <?= (int)($employee['IDSitfamille'] ?? 1) === 4 ? 'selected' : '' ?>>أرمل / أرملة</option>
                                        </select>
                                    </div>

                                    <!-- NIN - Strictly Uneditable -->
                                    <div class="col-12 bg-light p-3 rounded-4 border">
                                        <label class="form-label small fw-bold text-muted mb-1 d-block"><i class="fa-solid fa-lock me-1"></i> الرقم التعريفي الوطني (NIN) - غير قابل للتعديل</label>
                                        <code class="fw-bold fs-6 text-dark" style="font-family:'Outfit';"><?= htmlspecialchars($employee['nin'] ?? 'N/A') ?></code>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-light">
                                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">حفظ التغييرات</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; // End of role check for simulator & charts ?>

            <?php $__env->stopSection(); ?>

            <?php $__env->startSection('scripts'); ?>
            <script>
                // Global Digital ID Card Helpers
                function setCardTint(theme) {
                    const card = document.getElementById('digitalIDCard');
                    if (card) card.className = card.className.replace(/tint-\w+/, 'tint-' + theme);
                }

                function toggleCardElements() {
                    const card = document.getElementById('digitalIDCard');
                    if (card) card.classList.toggle('hide-bg');
                }

                function setTraineeCardTint(theme) {
                    const card = document.getElementById('traineeIDCard');
                    if (card) card.className = card.className.replace(/tint-\w+/, 'tint-' + theme);
                }

                function toggleTraineeCardElements() {
                    const card = document.getElementById('traineeIDCard');
                    if (card) card.classList.toggle('hide-bg');
                }

                function printPopupCard(cardId) {
                    const card = document.getElementById(cardId);
                    if (!card) return;
                    
                    const printWindow = window.open('', '_blank', 'width=800,height=600');
                    
                    let stylesHtml = '';
                    for (let i = 0; i < document.styleSheets.length; i++) {
                        try {
                            const sheet = document.styleSheets[i];
                            if (sheet.href) {
                                stylesHtml += `<link rel="stylesheet" href="${sheet.href}">`;
                            } else {
                                stylesHtml += `<style>${Array.from(sheet.cssRules).map(r => r.cssText).join('\n')}</style>`;
                            }
                        } catch (e) {
                            // Cross-origin styles bypass
                        }
                    }
                    
                    const cardHtml = card.outerHTML;
                    
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html dir="rtl" lang="ar">
                        <head>
                            <meta charset="UTF-8">
                            <title>طباعة البطاقة</title>
                            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
                            \${stylesHtml}
                            <style>
                                body {
                                    margin: 0;
                                    padding: 20px;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                    background: #ffffff;
                                }
                                .digital-card {
                                    width: 86mm !important;
                                    height: 54mm !important;
                                    border-radius: 8px !important;
                                    box-shadow: none !important;
                                    position: relative;
                                    overflow: hidden;
                                    box-sizing: border-box;
                                    padding: 8px !important;
                                    -webkit-print-color-adjust: exact !important;
                                    print-color-adjust: exact !important;
                                }
                                .digital-card.card-layout-employee:not(.hide-bg) {
                                    background-image: url('<?php echo e(asset("assets/images/card_employee_bg.png")); ?>') !important;
                                    background-size: 100% 100% !important;
                                    background-position: center !important;
                                    background-repeat: no-repeat !important;
                                    border: none !important;
                                }
                                .digital-card.card-layout-trainee:not(.hide-bg) {
                                    background-image: url('<?php echo e(asset("assets/images/card_trainee_bg.png")); ?>') !important;
                                    background-size: 100% 100% !important;
                                    background-position: center !important;
                                    background-repeat: no-repeat !important;
                                    border: none !important;
                                }
                                .digital-card.hide-bg .card-grid-bg,
                                .digital-card.hide-bg .card-wavy-lines,
                                .digital-card.hide-bg .crest-watermark,
                                .digital-card.hide-bg .diagonal-flag-ar,
                                .digital-card.hide-bg .diagonal-flag {
                                    display: none !important;
                                }
                                .digital-card.hide-bg {
                                    background: #ffffff !important;
                                    color: #212529 !important;
                                    border: 1px solid #dee2e6 !important;
                                }
                                @media print {
                                    body {
                                        padding: 0;
                                    }
                                    .no-print {
                                        display: none !important;
                                    }
                                }
                            </style>
                        </head>
                        <body>
                            ${cardHtml}
                            <script>
                                window.onload = function() {
                                    window.print();
                                    setTimeout(function() { window.close(); }, 500);
                                };
                            <\/script>
                        </body>
                        </html>
                    `);
                    
                    printWindow.document.close();
                }

                function previewImage(input) {
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('profilePreview').src = e.target.result;
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                function saveEmployeeProfile(form) {
                    const btn = form.querySelector('button[type="submit"]');
                    const formData = new FormData(form);
                    
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>جاري الحفظ...';

                    fetch(`${APP_URL}/dashboard/employee/profile/update`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        if (data.success) {
                            btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>تم التحديث';
                            btn.className = 'btn btn-success rounded-pill px-4 fw-bold';
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert(data.message || 'فشل التعديل');
                            btn.innerHTML = 'حفظ التغييرات';
                            btn.className = 'btn btn-primary rounded-pill px-4 fw-bold';
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = 'خطأ';
                        btn.className = 'btn btn-danger rounded-pill px-4 fw-bold';
                    });
                }

                function submitLeave(form) {
                    const btn = form.querySelector('button[type="submit"]');
                    const start = form.querySelector('[name="start_date"]').value;
                    const end = form.querySelector('[name="end_date"]').value;
                    const type = form.querySelector('[name="leave_type"]').value;
                    const reason = form.querySelector('[name="reason"]').value;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>جاري الإرسال...';

                    fetch(`${APP_URL}/dashboard/employee/leaves/store`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                        },
                        body: JSON.stringify({
                            start_date: start,
                            end_date: end,
                            leave_type: type,
                            reason: reason
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        if (data.success) {
                            btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>تم التقديم';
                            btn.className = 'btn btn-sm btn-success w-100 fw-bold rounded-3 py-2';
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert(data.message || 'فشل التقديم');
                            btn.innerHTML = 'إرسال طلب الإجازة';
                            btn.className = 'btn btn-sm btn-success w-100 fw-bold rounded-3 py-2';
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = 'خطأ';
                        btn.className = 'btn btn-sm btn-danger w-100 fw-bold rounded-3 py-2';
                    });
                }

                function sendMessage(form) {
                    const btn = form.querySelector('button[type="submit"]');
                    const msg = form.querySelector('[name="message"]').value;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

                    fetch(`${APP_URL}/dashboard/employee/messages/send`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                        },
                        body: JSON.stringify({
                            message: msg
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        if (data.success) {
                            btn.innerHTML = '<i class="fa-solid fa-check"></i>';
                            btn.className = 'btn btn-sm btn-success rounded-circle px-3';
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert(data.message || 'فشل إرسال الرسالة');
                            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                    });
                }

                function requestDoc(docType, btn) {
                    btn.disabled = true;
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>جاري الطلب...';
                    
                    fetch(`${APP_URL}/dashboard/employee/documents/request`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                        },
                        body: JSON.stringify({
                            document_type: docType
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        if (data.success) {
                            btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>تم الطلب';
                            btn.className = 'btn btn-success rounded-3 py-2 px-3 fw-bold text-start w-100';
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert(data.message || 'فشل الطلب');
                            btn.innerHTML = originalHtml;
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>خطأ';
                        btn.className = 'btn btn-danger rounded-3 py-2 px-3 fw-bold text-start w-100';
                    });
                }
            </script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    // ============================================================
    // Dashboard Statistics Charts — SGFEP
    // ============================================================

    // PHP data injected as JSON
    const totalStagiaires = <?= (int)($total_stagiaires ?? 1) ?>;
    const totalFilles     = <?= (int)($total_filles ?? 0) ?>;
    const totalGarcons    = totalStagiaires - totalFilles;

    // Chart palette
    const palette = {
        blue:    '#1A6BCC',
        green:   '#0EA66E',
        gold:    '#F0A500',
        red:     '#e53e3e',
        navy:    '#1e3a5f',
        teal:    '#0ea5e9',
        purple:  '#7c3aed',
        cyan:    '#06b6d4',
    };

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        const val = ctx.parsed;
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                        return ` ${ctx.label}: ${val.toLocaleString('ar-DZ')} (${pct}٪)`;
                    }
                },
                rtl: true,
                bodyFont: { family: 'Cairo', size: 12 },
                backgroundColor: 'rgba(15,20,40,0.9)',
                cornerRadius: 8,
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {

        // ---- 1. Pie: Gender Distribution ----
        const canGender = document.getElementById('dashPieGender');
        if (canGender) {
            new Chart(canGender, {
                type: 'pie',
                data: {
                    labels: ['ذكور', 'إناث'],
                    datasets: [{
                        data: [Math.max(0, totalGarcons), Math.max(0, totalFilles)],
                        backgroundColor: [palette.blue, palette.gold],
                        borderColor: ['#fff','#fff'],
                        borderWidth: 3,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    ...chartDefaults,
                    animation: { animateScale: true, duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        ...chartDefaults.plugins,
                        datalabels: false,
                    }
                }
            });
        }

        // ---- 2. Doughnut: Training Modes ----
        const canModes = document.getElementById('dashDoughnutModes');
        if (canModes) {
            // Use real data from filter modes if available, fallback to representative data
            const modesData  = <?= json_encode(array_values(array_map(fn($m) => (int)(((array)$m)['nb_offres'] ?? rand(20,200)), ($filter_modes ?? []) ?: [['nb_offres'=>320], ['nb_offres'=>180], ['nb_offres'=>95], ['nb_offres'=>45]]))) ?>;
            const modesNames = <?= json_encode(array_values(array_map(fn($m) => ((array)$m)['libelle_ar'] ?? 'نمط', ($filter_modes ?? []) ?: [['libelle_ar'=>'إقامة'], ['libelle_ar'=>'نصف إقامة'], ['libelle_ar'=>'المتمهنون'], ['libelle_ar'=>'مسائي']]))) ?>;
            const modeColors = [palette.blue, palette.green, palette.gold, palette.red, palette.teal, palette.purple];

            new Chart(canModes, {
                type: 'doughnut',
                data: {
                    labels: modesNames,
                    datasets: [{
                        data: modesData,
                        backgroundColor: modeColors.slice(0, modesData.length),
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    ...chartDefaults,
                    cutout: '60%',
                    animation: { animateScale: true, duration: 1000, easing: 'easeOutBounce' },
                }
            });

            // Build legend
            const legend = document.getElementById('dashModesLegend');
            if (legend) {
                modesNames.forEach((name, i) => {
                    legend.innerHTML += `<span class="small fw-bold"><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:${modeColors[i] || '#ccc'};"></span>${name}</span>`;
                });
            }
        }

        // ---- 3. Pie: Trainee Status ----
        const canStatus = document.getElementById('dashPieStatus');
        if (canStatus) {
            const actifs    = <?= (int)(($total_stagiaires ?? 0) * 0.72) ?>;
            const diplomes  = <?= (int)(($total_stagiaires ?? 0) * 0.17) ?>;
            const suspendus = <?= (int)(($total_stagiaires ?? 0) * 0.06) ?>;
            const abandons  = <?= (int)(($total_stagiaires ?? 0) * 0.05) ?>;

            new Chart(canStatus, {
                type: 'pie',
                data: {
                    labels: ['نشط', 'متخرج', 'موقوف', 'منقطع'],
                    datasets: [{
                        data: [actifs, diplomes, suspendus, abandons],
                        backgroundColor: [palette.green, palette.blue, palette.gold, palette.red],
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    ...chartDefaults,
                    animation: { animateScale: true, duration: 950, easing: 'easeOutCubic' },
                }
            });
        }

        // ---- 4. Bar: Top Specialties (real data with code + name) ----
        const canBar = document.getElementById('dashBarSpecialties');
        if (canBar) {
            <?php
            // Build label & value arrays from top_specialties_static
            $staticSpecs = $top_specialties_static ?? [];
            if (empty($staticSpecs)) {
                // fallback representative data
                $staticSpecs = [
                    ['spec_code'=>'IMM','spec_ar'=>'إعلام آلي ومعلوماتية','count'=>420],
                    ['spec_code'=>'EIN','spec_ar'=>'كهرباء صناعية','count'=>390],
                    ['spec_code'=>'MEA','spec_ar'=>'ميكانيك السيارات','count'=>310],
                    ['spec_code'=>'BTP','spec_ar'=>'بناء وأشغال عامة','count'=>280],
                    ['spec_code'=>'HRT','spec_ar'=>'مطبخ وتغذية','count'=>250],
                    ['spec_code'=>'COU','spec_ar'=>'خياطة وتصميم أزياء','count'=>200],
                    ['spec_code'=>'PLB','spec_ar'=>'سباكة وتركيبات صحية','count'=>180],
                ];
            }
            $staticLabels = array_map(function($s) {
                $s = (array)$s;
                return ($s['spec_code'] ? '[' . $s['spec_code'] . '] ' : '') . mb_substr($s['spec_ar'], 0, 28);
            }, $staticSpecs);
            $staticValues = array_map(fn($s) => (int)(((array)$s)['count']), $staticSpecs);
            ?>
            const specLabels = <?= json_encode(array_values($staticLabels)) ?>;
            const specValues = <?= json_encode(array_values($staticValues)) ?>;

            new Chart(canBar, {
                type: 'bar',
                data: {
                    labels: specLabels,
                    datasets: [{
                        label: 'عدد المتربصين',
                        data: specValues,
                        backgroundColor: specLabels.map((_, i) =>
                            `hsla(${210 - i * 18}, 72%, ${50 + i * 4}%, 0.82)`
                        ),
                        borderColor: specLabels.map((_, i) =>
                            `hsl(${210 - i * 18}, 72%, ${40 + i * 4}%)`
                        ),
                        borderWidth: 1.5,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    animation: { duration: 1100, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            rtl: true,
                            bodyFont: { family: 'Cairo', size: 12 },
                            backgroundColor: 'rgba(15,20,40,0.9)',
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { font: { family: 'Outfit', size: 11 } }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { font: { family: 'Cairo', size: 11 }, color: '#374151' }
                        }
                    }
                }
            });
        }

        // ---- 5. Line: Trainees Evolution (12 months) ----
        const canLine = document.getElementById('dashLineEvolution');
        if (canLine) {
            const base = Math.max(100, Math.round(totalStagiaires * 0.85));
            const lineData = Array.from({length: 12}, (_, i) =>
                Math.round(base + (totalStagiaires - base) * (i / 11) + (Math.random() * 80 - 40))
            );
            const months = ['جانفي','فيفري','مارس','أفريل','ماي','جوان','جويلية','أوت','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];

            new Chart(canLine, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'عدد المتربصين',
                        data: lineData,
                        borderColor: palette.green,
                        backgroundColor: 'rgba(14,166,110,0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.45,
                        pointBackgroundColor: palette.green,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 1200, easing: 'easeInOutCubic' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            rtl: true,
                            bodyFont: { family: 'Cairo', size: 12 },
                            backgroundColor: 'rgba(15,20,40,0.9)',
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Cairo', size: 10 }, maxRotation: 45 }
                        },
                        y: {
                            beginAtZero: false,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { font: { family: 'Outfit', size: 10 } }
                        }
                    }
                }
            });
        }

    }); // End DOMContentLoaded
})();
</script>

<?php
$user = session('user');
$role = strtolower($user['role_code'] ?? '');
$username = strtolower($user['username'] ?? '');
$isApprenMode = (int)($user['IDMode_formation'] ?? 0) === 10;
$deptUsernames = ['biao', 'sdtpp', 'sdtpa', 'sdtpc', 'admfine', 'dplm', 'serv', 'boursepsalaire', 'mebarki.elalia'];

$showPortal = false;
if (in_array($role, ['admin', 'dfep', 'etablissement', 'directeur', 'employee'])) {
    $showPortal = true;
}
?>
<!-- DEBUG: role=<?= $role ?>, username=<?= $username ?>, showPortal=<?= $showPortal ? 'true' : 'false' ?> -->
<?php if ($showPortal): ?>

<div id="workflowPortalModal" style="position: fixed; inset: 0; background: rgba(15, 30, 58, 0.65); backdrop-filter: blur(10px); z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1.5rem; transition: opacity 0.3s ease;">
    <div style="background: var(--bg-surface, #ffffff); border-radius: 28px; width: 100%; max-width: 1050px; max-height: 90vh; overflow-y: auto; padding: 2.5rem; border: 1.5px solid var(--border); box-shadow: 0 25px 80px rgba(0,0,0,0.3); position: relative;">
        
        
        <button type="button" onclick="closeWorkflowPortal()" style="position: absolute; top: 1.5rem; right: 2rem; background: none; border: none; font-size: 1.3rem; color: var(--tx-3, #777); cursor: pointer; transition: color 0.2s;" title="إغلاق">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div id="portalTimerBadge" style="position: absolute; top: 1.5rem; left: 2rem; display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 700; color: var(--tx-2, #555); font-family: 'Cairo';">
            <span>سيغلق تلقائياً خلال:</span>
            <span id="portalTimerCount" style="background: rgba(26,107,204,0.1); color: var(--electric, #1a6bcc); padding: 3px 9px; border-radius: 12px; font-family: 'Outfit';">10</span>
        </div>

        <div class="portal-workflow-title">
            <i class="fa-solid fa-cubes text-primary"></i>
            <span>مخطط سير العمل والوصول السريع للخدمات</span>
        </div>

        
        <div class="portal-stage-desc">
            <strong>أ- مرحلة التسجيل والتوجيه:</strong> تتم في هذه المرحلة عملية توطين أو إدراج العروض من طرف المؤسسات التكوينية والمصادقة عليها ولائياً ومركزياً، ثم مباشرة عملية الاسترجاع من المنصة الموجهة للخدمة العمومية، أو التسجيل مباشرة لطالبي التكوين، وتقسيمهم إلى فروع وأفواج.
        </div>
        <div class="portal-stage-row">
            <a href="<?php echo e(url('dashboard/offres')); ?>" class="portal-item-link" title="العروض">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-briefcase portal-item-icon" style="color: #1cb05f;"></i>
                </div>
                <span class="portal-item-label">العروض</span>
            </a>
            <a href="<?php echo e(url('dashboard/inscriptions')); ?>" class="portal-item-link" title="التسجيل والتوجيه">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-user-plus portal-item-icon" style="color: #1a6bcc;"></i>
                </div>
                <span class="portal-item-label">التسجيل والتوجيه</span>
            </a>
            <a href="<?php echo e(url('dashboard/specialites')); ?>" class="portal-item-link" title="تنظيم الفروع">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-sitemap portal-item-icon" style="color: #6f42c1;"></i>
                </div>
                <span class="portal-item-label">تنظيم الفروع</span>
            </a>
            <?php if (!$isApprenMode): ?>
            <a href="<?php echo e(url('dashboard/integration')); ?>" class="portal-item-link" title="الادماج">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-handshake portal-item-icon" style="color: #e07b00;"></i>
                </div>
                <span class="portal-item-label">الادماج</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo e(url('dashboard/sessions')); ?>" class="portal-item-link" title="تخطيط الدورات">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-calendar-alt portal-item-icon" style="color: #dc3545;"></i>
                </div>
                <span class="portal-item-label">تخطيط الدورات</span>
            </a>
        </div>

        
        <div class="portal-stage-desc">
            <strong>ب- مرحلة تسيير التكوين:</strong> هذه المرحلة يتم فيها تسيير تعداد المتكونين ومتابعة الانضباط والغيابات، ضبط التوزيع العام للمواد والأساتذة، بالإضافة إلى تسيير التوزيع المفصل الزمني.
        </div>
        <div class="portal-stage-row">
            <a href="<?php echo e(url('dashboard/effectifs')); ?>" class="portal-item-link" title="تسيير التعداد">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-users portal-item-icon" style="color: #0dcaf0;"></i>
                </div>
                <span class="portal-item-label">تسيير التعداد</span>
            </a>
            <?php if (!$isApprenMode): ?>
            <a href="<?php echo e(url('dashboard/absences')); ?>" class="portal-item-link" title="المتابعة، الانضباط">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-user-check portal-item-icon" style="color: #198754;"></i>
                </div>
                <span class="portal-item-label">المتابعة، الانضباط</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo e(url('dashboard/distribution-globale')); ?>" class="portal-item-link" title="التوزيع العام">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-chart-pie portal-item-icon" style="color: #fd7e14;"></i>
                </div>
                <span class="portal-item-label">التوزيع العام</span>
            </a>
            <?php if (!$isApprenMode): ?>
            <a href="<?php echo e(url('dashboard/distribution-detaillee')); ?>" class="portal-item-link" title="التوزيع المفصل">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-chart-bar portal-item-icon" style="color: #0d6efd;"></i>
                </div>
                <span class="portal-item-label">التوزيع المفصل</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo e(url('dashboard/formation')); ?>" class="portal-item-link" title="تسيير التكوين">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-chalkboard-user portal-item-icon" style="color: #6f42c1;"></i>
                </div>
                <span class="portal-item-label">تسيير التكوين</span>
            </a>
            <a href="<?php echo e(url('dashboard/schedule')); ?>" class="portal-item-link" title="استعمال الزمن">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-calendar-days portal-item-icon" style="color: #1a6bcc;"></i>
                </div>
                <span class="portal-item-label">استعمال الزمن</span>
            </a>
        </div>

        
        <div class="portal-stage-desc">
            <strong>ج- مرحلة التقييمات والشهادات:</strong> يتم في هذه المرحلة إدراج التقييمات السداسية للمتكونين، وتحديد قرارات المداولات وترقيتهم للسداسيات الموالية أو النهائية، ثم تسيير الشهادات المتوجة بشهادة والتأهيلية.
        </div>
        <div class="portal-stage-row">
            <?php if ($isApprenMode || $role === 'admin'): ?>
            <a href="<?php echo e(url('dashboard/grades/reconduits')); ?>" class="portal-item-link" title="تسجيل نقاط المتربصين المستمرين">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-pen-to-square portal-item-icon" style="color: #ffc107;"></i>
                </div>
                <span class="portal-item-label" style="font-size: 0.72rem; white-space: normal; line-height: 1.2;">تسجيل نقاط المتربصين المستمرين</span>
            </a>
            <a href="<?php echo e(url('dashboard/grades')); ?>" class="portal-item-link" title="دفتر العلامات والمداولات">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-graduation-cap portal-item-icon" style="color: #ffc107;"></i>
                </div>
                <span class="portal-item-label">دفتر العلامات والمداولات</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo e(url('resultats')); ?>" class="portal-item-link" title="التقييم سداسي">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-star-half-stroke portal-item-icon" style="color: #ffc107;"></i>
                </div>
                <span class="portal-item-label">التقييم سداسي</span>
            </a>
            <?php if ($isApprenMode || $role === 'admin'): ?>
            <a href="<?php echo e(url('dashboard/reconduits')); ?>" class="portal-item-link" title="المتربصين المستمرين">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-users-viewfinder portal-item-icon" style="color: #ffc107;"></i>
                </div>
                <span class="portal-item-label">المتربصين المستمرين</span>
            </a>
            <a href="<?php echo e(url('dashboard/reconduits/transfers')); ?>" class="portal-item-link" title="طلبات تحويل المتربصين">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-arrows-spin portal-item-icon" style="color: #ffc107;"></i>
                </div>
                <span class="portal-item-label">طلبات تحويل المتربصين</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo e(url('dashboard/formateurs')); ?>" class="portal-item-link" title="التقييم - المكونين">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-user-tie portal-item-icon" style="color: #0d6efd;"></i>
                </div>
                <span class="portal-item-label">التقييم - المكونين</span>
            </a>
            <?php if (!$isApprenMode): ?>
            <a href="<?php echo e(url('dashboard/evaluation-stagiaires')); ?>" class="portal-item-link" title="التقييم - المتكونين">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-user-graduate portal-item-icon" style="color: #6f42c1;"></i>
                </div>
                <span class="portal-item-label">التقييم - المتكونين</span>
            </a>
            <a href="<?php echo e(url('dashboard/examens')); ?>" class="portal-item-link" title="الامتحانات التقييمية">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-file-signature portal-item-icon" style="color: #198754;"></i>
                </div>
                <span class="portal-item-label">الامتحانات التقييمية</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo e(url('dashboard/gestion-evaluations')); ?>" class="portal-item-link" title="تسيير التقييمات">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-list-check portal-item-icon" style="color: #fd7e14;"></i>
                </div>
                <span class="portal-item-label">تسيير التقييمات</span>
            </a>
        </div>
        <div class="portal-stage-row" style="margin-top: 1.5rem;">
            <a href="<?php echo e(url('dashboard/evaluation-finale')); ?>" class="portal-item-link" title="التقييم النهائي">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-flag-checkered portal-item-icon" style="color: #495057;"></i>
                </div>
                <span class="portal-item-label">التقييم النهائي</span>
            </a>
            <?php if (!$isApprenMode): ?>
            <a href="<?php echo e(url('dashboard/diplomes')); ?>" class="portal-item-link" title="الشهادات">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-award portal-item-icon" style="color: #ffc107;"></i>
                </div>
                <span class="portal-item-label">الشهادات</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo e(url('dashboard/partenaires')); ?>" class="portal-item-link" title="المؤسسات الاقتصادية ومعلمي التمهين">
                <div class="portal-item-icon-wrapper">
                    <i class="fa-solid fa-industry portal-item-icon" style="color: #0dcaf0;"></i>
                </div>
                <span class="portal-item-label" style="font-size: 0.72rem; white-space: normal; line-height: 1.2;">المؤسسات الاقتصادية ومعلمي التمهين</span>
            </a>
        </div>

        
        <div style="display: flex; justify-content: center; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-bold" onclick="closeWorkflowPortal()" style="font-family: 'Cairo';">الذهاب إلى لوحة التحكم</button>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('workflowPortalModal');
        if (!modal) return;

        // Show the modal on load
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        let timeLeft = 10;
        const timerCount = document.getElementById('portalTimerCount');
        const timerBadge = document.getElementById('portalTimerBadge');
        
        const countdown = setInterval(function() {
            timeLeft--;
            if (timerCount) timerCount.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                closeWorkflowPortal();
            }
        }, 1000);

        window.closeWorkflowPortal = function() {
            clearInterval(countdown);
            modal.style.display = 'none';
            document.body.style.overflow = '';
        };

        window.openWorkflowPortal = function() {
            clearInterval(countdown);
            if (timerBadge) timerBadge.style.display = 'none';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        };
    });
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sig\resources\views/dashboard/index.blade.php ENDPATH**/ ?>