
<?php $__env->startSection('title', 'إعدادات المنصة الشاملة — SGFEP'); ?>

<?php $__env->startSection('styles'); ?>
<style>
.settings-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
    position: sticky;
    top: 80px;
}
.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.83rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    color: var(--tx-2);
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    background: none;
    width: 100%;
    text-align: right;
    transition: all .18s ease;
}
.settings-nav-item i {
    width: 28px; height: 28px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(26,107,204,.07);
    color: var(--tx-3);
    font-size: .8rem;
    flex-shrink: 0;
    transition: all .18s;
}
.settings-nav-item:hover,
.settings-nav-item.active {
    background: rgba(26,107,204,.07);
    color: var(--electric);
    border-color: var(--electric-border);
}
.settings-nav-item.active i,
.settings-nav-item:hover i {
    background: rgba(26,107,204,.12);
    color: var(--electric);
}
.settings-nav-item.active {
    background: linear-gradient(135deg, rgba(26,107,204,.12), rgba(26,107,204,.05));
    box-shadow: 0 2px 8px rgba(26,107,204,.08);
}
.settings-panel { display: none; }
.settings-panel.active { display: block; }

.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    font-size: .85rem;
}
.info-row:last-child { border-bottom: none; }
.info-row .label { font-weight: 700; color: var(--tx-2); font-family: 'Cairo'; }
.info-row .value { font-family: 'Outfit', monospace; color: var(--tx-1); font-weight: 600; }

.stat-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    font-family: 'Outfit';
}
.stat-badge.green { background: rgba(14,166,110,.1); color: #0EA66E; }
.stat-badge.red   { background: rgba(220,53,69,.1);  color: #dc3545; }
.stat-badge.blue  { background: rgba(26,107,204,.1); color: var(--electric); }
.stat-badge.gold  { background: rgba(240,165,0,.1);  color: #F0A500; }

.danger-zone {
    border: 1.5px solid rgba(220,53,69,.3);
    border-radius: 14px;
    padding: 1.25rem;
    background: rgba(220,53,69,.03);
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
$platform_info   = $platform_info   ?? [];
$db_stats        = $db_stats        ?? [];
$cache_stats     = $cache_stats     ?? [];
$takwin_settings = $takwin_settings ?? [];
$active_tab      = $active_tab      ?? 'general';
$doc_stats       = $doc_stats       ?? [];
$preview_docs    = $preview_docs    ?? [];
$wilayas         = $wilayas         ?? [];
$etablissements  = $etablissements  ?? [];
$selected_table  = $selected_table  ?? 'candidat_document';
$selected_wilaya = $selected_wilaya ?? null;
$is_activation_required = $is_activation_required ?? false;
$is_shield_active       = $is_shield_active ?? true;
$is_captcha_active      = $is_captcha_active ?? false;
$hide_other_login_portals = \App\Helpers\SovereignLicensingHelper::getSetting('hide_other_login_portals', '0') === '1';
$patrimoine_media_actions_enabled = \App\Helpers\SovereignLicensingHelper::getSetting('patrimoine_media_actions_enabled', '1') === '1';
$feature_print_actions_enabled    = \App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') === '1';
$feature_complex_stats_enabled    = \App\Helpers\SovereignLicensingHelper::getSetting('feature_complex_stats_enabled', '1') === '1';
$feature_photo_sorting_enabled    = \App\Helpers\SovereignLicensingHelper::getSetting('feature_photo_sorting_enabled', '1') === '1';
$feature_background_sync_enabled  = \App\Helpers\SovereignLicensingHelper::getSetting('feature_background_sync_enabled', '1') === '1';
$feature_large_memos_query_enabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_large_memos_query_enabled', '1') === '1';
$license_keys           = $license_keys ?? [];
$master_key             = $master_key ?? '';
$employee_leaves        = $employee_leaves ?? [];
$employee_docs          = $employee_docs ?? [];
$portal_pages           = $portal_pages ?? [];
$ministry               = $ministry ?? null;
$allSemesters           = $allSemesters ?? [];
$currentSemesterId      = $currentSemesterId ?? 1;
?>

<div class="animate__animated animate__fadeIn">

    <!-- Page Header -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--electric),#0d6efd);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-gear text-white fs-5"></i>
        </div>
        <div>
            <h1 class="fw-black m-0" style="font-size:1.3rem;font-family:'Cairo';color:var(--tx-1);">إعدادات المنصة الشاملة</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;font-weight:600;">Paramètres Généraux — SGFEP Admin</p>
        </div>
        <div class="me-auto"></div>
        <!-- Flash Messages -->
        <?php if (session()->has('flash_success')): ?>
            <div class="alert alert-success border-0 py-2 px-3 mb-0 small fw-bold d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> <?= session('flash_success') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->has('flash_error')): ?>
            <div class="alert alert-danger border-0 py-2 px-3 mb-0 small fw-bold d-flex align-items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= session('flash_error') ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">

        <!-- ── Sidebar Navigation ── -->
        <div class="col-12 col-lg-3">
            <div class="glass-panel p-3">
                <p class="text-muted mb-2" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding-right:14px;">الأقسام</p>
                <nav class="settings-nav" id="settingsNav">
                    <button class="settings-nav-item <?= $active_tab==='general' ? 'active':'' ?>" onclick="switchTab('general', this)">
                        <i class="fa-solid fa-circle-info"></i> معلومات المنصة
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='performance' ? 'active':'' ?>" onclick="switchTab('performance', this)">
                        <i class="fa-solid fa-gauge-high"></i> الأداء والكاش
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='database' ? 'active':'' ?>" onclick="switchTab('database', this)">
                        <i class="fa-solid fa-database"></i> قاعدة البيانات
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='api' ? 'active':'' ?>" onclick="switchTab('api', this)">
                        <i class="fa-solid fa-plug-circle-bolt"></i> Takwin API
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='api-center' ? 'active':'' ?>" onclick="switchTab('api-center', this)">
                        <i class="fa-solid fa-satellite-dish"></i> مركز الاتصال الرقمي (APIs)
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='diploma' ? 'active':'' ?>" onclick="switchTab('diploma', this)">
                        <i class="fa-solid fa-award"></i> الشهادات والطباعة
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='documents' ? 'active':'' ?>" onclick="switchTab('documents', this)">
                        <i class="fa-solid fa-folder-open"></i> إدارة الوثائق والملفات
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='backup' ? 'active':'' ?>" onclick="switchTab('backup', this)">
                        <i class="fa-solid fa-file-zipper"></i> النسخ الاحتياطي لقاعدة البيانات
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='sovereign' ? 'active':'' ?>" onclick="switchTab('sovereign', this)">
                        <i class="fa-solid fa-shield-halved"></i> الترخيص ودرع الحماية
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='security' ? 'active':'' ?>" onclick="switchTab('security', this)">
                        <i class="fa-solid fa-key-skeleton"></i> الأمان والميزات المتقدمة
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='requests' ? 'active':'' ?>" onclick="switchTab('requests', this)">
                        <i class="fa-solid fa-clipboard-list"></i> إدارة طلبات الموظفين
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='ministry' ? 'active':'' ?>" onclick="switchTab('ministry', this)">
                        <i class="fa-solid fa-server"></i> إعدادات خوادم ومزامنة الوزارة
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='landing' ? 'active':'' ?>" onclick="switchTab('landing', this)">
                        <i class="fa-solid fa-palette"></i> إعدادات المظهر والواجهة
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='portal_cms' ? 'active':'' ?>" onclick="switchTab('portal_cms', this)">
                        <i class="fa-solid fa-window-restore"></i> إدارة صفحات البوابة (CMS)
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='feature_flags' ? 'active':'' ?>" onclick="switchTab('feature_flags', this)">
                        <i class="fa-solid fa-toggle-on"></i> إدارة الميزات والاستعلامات
                    </button>
                    <button class="settings-nav-item <?= $active_tab==='enrollment_permissions' ? 'active':'' ?>" onclick="switchTab('enrollment_permissions', this)">
                        <i class="fa-solid fa-user-lock"></i> صلاحيات لوحة الالتحاق
                    </button>
                    <div style="border-top:1px solid var(--border);margin:8px 0;"></div>
                    <a href="<?php echo e(url('dashboard/settings/takwin')); ?>" class="settings-nav-item">
                        <i class="fa-solid fa-plug-circle-bolt"></i> إعدادات Takwin المتقدمة
                    </a>
                    <a href="<?php echo e(url('dashboard/settings/diplome')); ?>" class="settings-nav-item">
                        <i class="fa-solid fa-palette"></i> تخصيص الشهادات المتقدم
                    </a>
                    <a href="<?php echo e(url('dashboard/roles')); ?>" class="settings-nav-item">
                        <i class="fa-solid fa-user-shield"></i> الأدوار والصلاحيات
                    </a>
                    <a href="<?php echo e(url('dashboard/users')); ?>" class="settings-nav-item">
                        <i class="fa-solid fa-users-gear"></i> إدارة الحسابات
                    </a>
                    <a href="<?php echo e(url('dashboard/sync')); ?>" class="settings-nav-item">
                        <i class="fa-solid fa-rotate"></i> مزامنة HFSQL
                    </a>
                    <a href="<?php echo e(url('dashboard/database')); ?>" class="settings-nav-item">
                        <i class="fa-solid fa-table"></i> مدير قاعدة البيانات
                    </a>
                </nav>
            </div>
        </div>

        <!-- ── Main Content Area ── -->
        <div class="col-12 col-lg-9">

            <!-- ═══ TAB: معلومات المنصة ═══ -->
            <div class="settings-panel <?= $active_tab==='general' ? 'active':'' ?>" id="tab-general">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i> معلومات المنصة والبيئة
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-row"><span class="label">اسم التطبيق</span><span class="value"><?= htmlspecialchars($platform_info['app_name'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="label">البيئة</span>
                                <span class="stat-badge <?= ($platform_info['app_env'] ?? '') === 'production' ? 'green' : 'gold' ?>">
                                    <i class="fa-solid fa-circle" style="font-size:.45rem;"></i>
                                    <?= htmlspecialchars($platform_info['app_env'] ?? '—') ?>
                                </span>
                            </div>
                            <div class="info-row"><span class="label">وضع التصحيح (Debug)</span>
                                <span class="stat-badge <?= ($platform_info['app_debug'] ?? false) ? 'red' : 'green' ?>">
                                    <?= ($platform_info['app_debug'] ?? false) ? '⚠️ مُفعَّل' : '✅ معطَّل' ?>
                                </span>
                            </div>
                            <div class="info-row"><span class="label">إصدار PHP</span><span class="value"><?= htmlspecialchars($platform_info['php_version'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="label">إصدار Laravel</span><span class="value"><?= htmlspecialchars($platform_info['laravel_ver'] ?? '—') ?></span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-row"><span class="label">قاعدة البيانات</span><span class="value"><?= htmlspecialchars($platform_info['db_name'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="label">خادم DB</span><span class="value"><?= htmlspecialchars($platform_info['db_host'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="label">محرك الكاش</span><span class="value"><?= htmlspecialchars($platform_info['cache_driver'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="label">محرك الجلسة</span><span class="value"><?= htmlspecialchars($platform_info['session_drv'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="label">Sentry Monitoring</span><span class="value"><?= htmlspecialchars($platform_info['sentry_dsn'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="label">درع حماية المحتوى</span>
                                <span class="stat-badge <?= $is_shield_active ? 'green' : 'red' ?>">
                                    <?= $is_shield_active ? '✅ نشط / Activé' : '❌ معطّل / Désactivé' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--green);padding-right:.6rem;">
                        <i class="fa-solid fa-bolt text-success me-2"></i> روابط الإدارة السريعة
                    </h5>
                    <div class="row g-3">
                        <?php
                        $quickLinks = [
                            ['url' => url('dashboard/users'),       'icon' => 'fa-users-gear',   'label' => 'إدارة الحسابات',       'color' => 'blue'],
                            ['url' => url('dashboard/roles'),       'icon' => 'fa-user-shield',  'label' => 'الأدوار والصلاحيات',   'color' => 'gold'],
                            ['url' => url('dashboard/sync'),        'icon' => 'fa-rotate',       'label' => 'مزامنة HFSQL',         'color' => 'green'],
                            ['url' => url('dashboard/database'),    'icon' => 'fa-database',     'label' => 'مدير قاعدة البيانات',  'color' => 'blue'],
                            ['url' => url('dashboard/import'),      'icon' => 'fa-file-import',  'label' => 'استيراد البيانات',     'color' => 'gold'],
                            ['url' => url('dashboard/archive'),     'icon' => 'fa-box-archive',  'label' => 'بوابة الأرشيف',        'color' => 'green'],
                            ['url' => url('dashboard/api-center'),  'icon' => 'fa-satellite-dish', 'label' => 'مركز الاتصال الرقمي (APIs)', 'color' => 'gold'],
                            ['url' => url('dashboard/settings?tab=sovereign'), 'icon' => 'fa-shield-halved', 'label' => 'إعدادات درع الحماية والترخيص', 'color' => 'blue'],
                        ];
                        ?>
                        <?php $__currentLoopData = $quickLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-6 col-md-4">
                            <a href="<?php echo e($link['url']); ?>" class="d-flex align-items-center gap-2 p-3 rounded-3 text-decoration-none"
                               style="background:var(--bg-surface-elevated);border:1px solid var(--border);transition:all .18s;"
                               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'"
                               onmouseout="this.style.transform='';this.style.boxShadow=''">
                                <i class="fa-solid <?php echo e($link['icon']); ?> text-primary" style="width:20px;"></i>
                                <span style="font-size:.83rem;font-weight:700;font-family:'Cairo';color:var(--tx-1);"><?php echo e($link['label']); ?></span>
                            </a>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: الأداء والكاش ═══ -->
            <div class="settings-panel <?= $active_tab==='performance' ? 'active':'' ?>" id="tab-performance">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-gauge-high text-primary me-2"></i> حالة الكاش والأداء
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center">
                                <div style="font-size:1.6rem;font-weight:900;font-family:'Outfit';color:var(--electric);">
                                    <?= is_numeric($cache_stats['file_count'] ?? 0) ? number_format((float)($cache_stats['file_count'] ?? 0)) : htmlspecialchars($cache_stats['file_count'] ?? '0') ?>
                                </div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">ملفات الكاش</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center">
                                <div style="font-size:1.6rem;font-weight:900;font-family:'Outfit';color:var(--green);">
                                    <?= is_numeric($cache_stats['size_kb'] ?? 0) ? number_format((float)($cache_stats['size_kb'] ?? 0)) : htmlspecialchars($cache_stats['size_kb'] ?? '0') ?> KB
                                </div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">حجم الكاش</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center">
                                <span class="stat-badge <?= ($cache_stats['ref_warmed'] ?? false) ? 'green' : 'red' ?>">
                                    <?= ($cache_stats['ref_warmed'] ?? false) ? '✅ جاهز' : '❌ بارد' ?>
                                </span>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';margin-top:6px;">ReferenceCache</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center">
                                <span class="stat-badge <?= ($cache_stats['kpi_warmed'] ?? false) ? 'green' : 'gold' ?>">
                                    <?= ($cache_stats['kpi_warmed'] ?? false) ? '✅ جاهز' : '⏳ بارد' ?>
                                </span>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';margin-top:6px;">KPI Cache</div>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات الكاش -->
                    <div class="info-row"><span class="label">محرك الكاش</span><span class="value"><?= htmlspecialchars($cache_stats['driver'] ?? 'file') ?></span></div>
                    <div class="info-row"><span class="label">مسار التخزين</span><span class="value" style="font-size:.78rem;">storage/framework/cache/data</span></div>
                    <div class="info-row">
                        <span class="label">TTL البيانات المرجعية (Wilayas, Modes…)</span>
                        <span class="stat-badge blue">24 ساعة</span>
                    </div>
                    <div class="info-row">
                        <span class="label">TTL الـ KPIs (أرقام الإحصاء)</span>
                        <span class="stat-badge blue">5 - 15 دقيقة</span>
                    </div>
                </div>

                <!-- أدوات إدارة الكاش -->
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--gold);padding-right:.6rem;">
                        <i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i> أدوات إدارة الكاش
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3" style="background:var(--bg-surface-elevated);border:1px solid var(--border);">
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';">مسح الكاش الكامل</h6>
                                <p class="text-muted small mb-3">يمسح كاش الولايات والمؤسسات والأنماط وكاش KPIs. سيُعاد تحميل البيانات من قاعدة البيانات عند الطلب التالي.</p>
                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" onsubmit="return confirm('هل أنت متأكد من مسح الكاش الكامل؟')">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="section" value="cache">
                                    <input type="hidden" name="redirect_tab" value="performance">
                                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 rounded-3" style="font-family:'Cairo';">
                                        <i class="fa-solid fa-trash-can me-1"></i> مسح الكاش الآن
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3" style="background:var(--bg-surface-elevated);border:1px solid var(--border);">
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';">تسخين الكاش (Warm)</h6>
                                <p class="text-muted small mb-3">يُعيد تحميل جميع البيانات المرجعية في الكاش مسبقاً لضمان سرعة التحميل لجميع المستخدمين.</p>
                                <a href="<?php echo e(url('dashboard/settings/takwin')); ?>" class="btn btn-primary w-100 fw-bold py-2 rounded-3" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-fire-flame-curved me-1"></i> الذهاب لإعدادات التزامن
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: قاعدة البيانات ═══ -->
            <div class="settings-panel <?= $active_tab==='database' ? 'active':'' ?>" id="tab-database">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-database text-primary me-2"></i> إحصائيات قاعدة البيانات
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-4">
                            <div class="glass-panel p-3 text-center">
                                <div style="font-size:1.8rem;font-weight:900;font-family:'Outfit';color:var(--electric);"><?= number_format($db_stats['total_tables'] ?? 0) ?></div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">عدد الجداول</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="glass-panel p-3 text-center">
                                <div style="font-size:1.8rem;font-weight:900;font-family:'Outfit';color:var(--green);"><?= number_format($db_stats['db_size_mb'] ?? 0) ?> MB</div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">حجم قاعدة البيانات</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="glass-panel p-3 text-center">
                                <div style="font-size:1.8rem;font-weight:900;font-family:'Outfit';color:var(--gold);"><?= number_format($db_stats['total_rows'] ?? 0) ?></div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">سجلات أكبر 10 جداول</div>
                            </div>
                        </div>
                    </div>

                    <!-- أكبر الجداول -->
                    <h6 class="fw-bold mb-2" style="font-family:'Cairo';">أكبر الجداول حجماً</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="bg-light text-muted fw-bold">
                                <tr>
                                    <th class="text-start">اسم الجدول</th>
                                    <th class="text-center">عدد السجلات</th>
                                    <th class="text-center">الحجم (MB)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($db_stats['top_tables'] ?? [] as $tbl): ?>
                                <tr>
                                    <td class="fw-bold" style="font-family:'Outfit';"><?= htmlspecialchars($tbl['name'] ?? '') ?></td>
                                    <td class="text-center fw-bold text-primary"><?= number_format((int)($tbl['rows'] ?? 0)) ?></td>
                                    <td class="text-center text-muted"><?= number_format((float)($tbl['size_mb'] ?? 0), 2) ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo e(url('dashboard/database')); ?>?table=<?= urlencode($tbl['name'] ?? '') ?>"
                                           class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:.72rem;font-family:'Cairo';">
                                            <i class="fa-solid fa-eye me-1"></i>عرض
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <a href="<?php echo e(url('dashboard/database')); ?>" class="btn btn-primary fw-bold py-2 px-4 rounded-3" style="font-family:'Cairo';">
                            <i class="fa-solid fa-table me-1"></i> فتح مدير قاعدة البيانات
                        </a>
                        <a href="<?php echo e(url('dashboard/sync')); ?>" class="btn btn-outline-secondary fw-bold py-2 px-4 rounded-3" style="font-family:'Cairo';">
                            <i class="fa-solid fa-rotate me-1"></i> مزامنة HFSQL
                        </a>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: Takwin API ═══ -->
            <div class="settings-panel <?= $active_tab==='api' ? 'active':'' ?>" id="tab-api">
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-plug-circle-bolt text-primary me-2"></i> إعدادات Takwin API
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="glass-panel p-3 text-center">
                                <span class="stat-badge <?= !empty($takwin_settings['api_url']) ? 'green' : 'red' ?>">
                                    <?= !empty($takwin_settings['api_url']) ? '✅ مُعدَّل' : '❌ غير مُعدَّل' ?>
                                </span>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);margin-top:6px;font-family:'Cairo';">رابط API</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-panel p-3 text-center">
                                <span class="stat-badge <?= ($takwin_settings['sync_enabled'] ?? 0) ? 'green' : 'gold' ?>">
                                    <?= ($takwin_settings['sync_enabled'] ?? 0) ? '✅ مُفعَّلة' : '⏸️ موقوفة' ?>
                                </span>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);margin-top:6px;font-family:'Cairo';">المزامنة التلقائية</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-panel p-3 text-center">
                                <span class="stat-badge blue">
                                    <?= !empty($takwin_settings['last_sync']) ? date('d/m/Y H:i', strtotime($takwin_settings['last_sync'])) : 'لم تتم بعد' ?>
                                </span>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);margin-top:6px;font-family:'Cairo';">آخر مزامنة</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="takwin">
                        <input type="hidden" name="redirect_tab" value="api">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold" style="font-family:'Cairo';">رابط API (URL)</label>
                                <input type="url" name="api_url" class="form-control"
                                       value="<?= htmlspecialchars($takwin_settings['api_url'] ?? '') ?>"
                                       placeholder="https://api.takwin.dz/v1" style="font-family:'Outfit';">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold" style="font-family:'Cairo';">المزامنة التلقائية</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="sync_enabled" value="1"
                                           <?= ($takwin_settings['sync_enabled'] ?? 0) ? 'checked' : '' ?> style="width:2.5rem;height:1.3rem;">
                                    <label class="form-check-label fw-bold" style="font-family:'Cairo';">تفعيل المزامنة</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold" style="font-family:'Cairo';">مفتاح API (Token)</label>
                                <input type="password" name="api_token" class="form-control"
                                       placeholder="اتركه فارغاً لعدم التغيير..." style="font-family:'Outfit';">
                                <small class="text-muted" style="font-family:'Cairo';">اتركه فارغاً إذا لم ترد تغيير المفتاح الحالي.</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary fw-bold py-2 px-5 rounded-3" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> حفظ إعدادات API
                                </button>
                                <a href="<?php echo e(url('dashboard/settings/takwin')); ?>" class="btn btn-outline-secondary fw-bold py-2 px-4 rounded-3 me-2" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> الإعدادات المتقدمة
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ TAB: مركز الاتصال الرقمي (APIs) ═══ -->
            <div class="settings-panel <?= $active_tab==='api-center' ? 'active':'' ?>" id="tab-api-center">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-1" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-shield-halved text-primary me-2"></i> مفتاح API الحالي للمسؤول (Bearer Token)
                    </h5>
                    <p class="text-muted small mb-4">هذا المفتاح سري وخاص بحسابك كمسؤول للنظام. لا تشاركه مع أي طرف خارجي.</p>

                    <div class="p-3 rounded-4 mb-4" style="background:var(--bg-surface-elevated, #f8fafc);border:1px solid var(--border);">
                        <label class="form-label fw-bold text-muted small">
                            <i class="fa-solid fa-lock text-muted me-1"></i> مفتاح API الخاص (API Bearer Key)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-light">
                                <i class="fa-solid fa-key text-warning"></i>
                            </span>
                            <input type="text" class="form-control border-light bg-white fw-bold text-truncate"
                                   id="user-apiKey"
                                   value="<?php echo e($user_api_key ?? 'لم يتم توليد مفتاح بعد.'); ?>" readonly
                                   style="font-family:'Outfit';font-size:0.85rem;color:var(--primary-color, #006233);">
                            <button class="btn btn-outline-secondary border-light bg-white px-3"
                                    type="button" onclick="copyApiKey()" title="نسخ إلى الحافظة">
                                <i class="fa-regular fa-copy text-primary"></i> نسخ
                            </button>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap mb-4">
                        <button class="btn btn-outline-primary rounded-pill px-4 fw-bold"
                                type="button"
                                data-user-id="<?php echo e(session('user')['id'] ?? ''); ?>"
                                data-csrf-token="<?php echo e(csrf_token()); ?>"
                                onclick="regenerateUserApiKey(this)">
                            <i class="fa-solid fa-arrows-rotate me-2 text-warning"></i> تجديد مفتاح API الخاص بك
                        </button>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo'; font-size: 0.9rem;">
                            <i class="fa-solid fa-code text-success me-1"></i> دليل الاستخدام السريع لمفتاح المسؤول الحالي:
                        </h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <select class="form-select form-select-sm rounded-3 fw-bold text-dark" id="admin-doc-select" onchange="showAdminDocSnippet()">
                                    <option value="">-- اختر الجدول لعرض طريقة الاستخدام وأمر cURL --</option>
                                    <option value="stagiaires">جدول المتربصين (Stagiaires)</option>
                                    <option value="offres">جدول عروض التكوين (Offres)</option>
                                    <option value="employees">جدول الموظفين والمؤطرين (Employees)</option>
                                    <option value="formateurs">ساعات التكوين الشاغرة (Vacant Hours)</option>
                                    <option value="finance">التقارير المالية والميزانية (Finance/Budget)</option>
                                    <option value="assets">طلبات العتاد والورشات (Assets/Requests)</option>
                                </select>
                            </div>
                        </div>
                        <div id="admin-doc-snippet-container" style="display:none;" class="animate__animated animate__fadeIn">
                            <div id="admin-doc-snippet"></div>
                        </div>
                    </div>
                </div>

                <!-- External Integration Platforms -->
                <div class="glass-panel p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                                <i class="fa-solid fa-server text-primary me-2"></i> منصات الربط الخارجية (External APIs)
                            </h5>
                            <p class="text-muted small mb-0 mt-1">تسيير مفاتيح API للمنصات الخارجية الحكومية أو الشركاء الاقتصاديين.</p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-3 fw-bold btn-sm"
                                type="button" data-bs-toggle="modal" data-bs-target="#createClientModal">
                            <i class="fa-solid fa-plus me-1"></i> إضافة منصة جديدة
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>اسم الجهة / المنصة</th>
                                    <th>الجداول المسموحة (APIs)</th>
                                    <th>IPs المسموح بها</th>
                                    <th>آخر استخدام</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>الحالة</th>
                                    <th class="text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $api_clients ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr id="client-row-<?php echo e($client->id); ?>">
                                    <td>
                                        <span class="fw-bold text-dark"><?php echo e($client->client_name); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $endpoints = $client->allowed_endpoints ?? [];
                                        ?>
                                        <?php if(empty($endpoints)): ?>
                                            <span class="badge bg-danger-subtle text-danger border px-2 py-1" style="font-size: 0.72rem;">لا توجد صلاحيات</span>
                                        <?php else: ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if(in_array('stagiaires', $endpoints)): ?>
                                                    <span class="badge bg-success-subtle text-success border px-2 py-1" style="font-size: 0.72rem;">المتربصين</span>
                                                <?php endif; ?>
                                                <?php if(in_array('offres', $endpoints)): ?>
                                                    <span class="badge bg-info-subtle text-info border px-2 py-1" style="font-size: 0.72rem;">عروض التكوين</span>
                                                <?php endif; ?>
                                                <?php if(in_array('employees', $endpoints)): ?>
                                                    <span class="badge bg-primary-subtle text-primary border px-2 py-1" style="font-size: 0.72rem;">الموظفين</span>
                                                <?php endif; ?>
                                                <?php if(in_array('formateurs', $endpoints)): ?>
                                                    <span class="badge bg-warning-subtle text-warning border px-2 py-1" style="font-size: 0.72rem;">ساعات التكوين</span>
                                                <?php endif; ?>
                                                <?php if(in_array('finance', $endpoints)): ?>
                                                    <span class="badge bg-danger-subtle text-danger border px-2 py-1" style="font-size: 0.72rem;">المالية والميزانية</span>
                                                <?php endif; ?>
                                                <?php if(in_array('assets', $endpoints)): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 0.72rem;">طلب العتاد</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(empty($client->allowed_ips)): ?>
                                            <span class="text-muted italic small">مفتوح (أي IP)</span>
                                        <?php else: ?>
                                            <code class="text-primary small" style="font-size: 0.75rem;"><?php echo e($client->allowed_ips); ?></code>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($client->last_used_at): ?>
                                            <span class="text-dark small"><i class="fa-regular fa-clock me-1"></i> <?php echo e(($client->last_used_at instanceof \DateTimeInterface ? $client->last_used_at->format('Y/m/d H:i') : date('Y/m/d H:i', strtotime((string)$client->last_used_at)))); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">لم يستخدم بعد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?php echo e(($client->created_at instanceof \DateTimeInterface ? $client->created_at->format('Y/m/d') : date('Y/m/d', strtotime((string)$client->created_at)))); ?></span>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch p-0 m-0 d-flex justify-content-center">
                                            <input class="form-check-input ms-0" type="checkbox" role="switch" 
                                                   id="switch-<?php echo e($client->id); ?>"
                                                   <?php echo e($client->is_active ? 'checked' : ''); ?>

                                                   onchange="toggleClientStatus(<?php echo e($client->id); ?>, '<?php echo e(csrf_token()); ?>')">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-info rounded-circle" 
                                                    onclick="showClientDocsModal('<?php echo e($client->client_name); ?>', <?php echo e(json_encode($client->allowed_endpoints ?? [])); ?>)" 
                                                    title="عرض التوثيق والتعليمات">
                                                <i class="fa-solid fa-book" style="font-size: 0.75rem;"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary rounded-circle" 
                                                    onclick="showEditClientModal(<?php echo e($client->id); ?>, '<?php echo e($client->client_name); ?>', '<?php echo e($client->allowed_ips); ?>', <?php echo e(json_encode($client->allowed_endpoints ?? [])); ?>)" 
                                                    title="تعديل">
                                                <i class="fa-solid fa-pen" style="font-size: 0.75rem;"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                                    onclick="deleteClient(<?php echo e($client->id); ?>, '<?php echo e(csrf_token()); ?>')" 
                                                    title="حذف وسحب المفتاح">
                                                <i class="fa-solid fa-trash" style="font-size: 0.75rem;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-circle-nodes fs-3 mb-2 d-block text-black-50"></i>
                                        لا توجد منصات ربط خارجية مسجلة حالياً.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: الشهادات ═══ -->
            <div class="settings-panel <?= $active_tab==='diploma' ? 'active':'' ?>" id="tab-diploma">
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--gold);padding-right:.6rem;">
                        <i class="fa-solid fa-award text-warning me-2"></i> تخصيص الشهادات والطباعة
                    </h5>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="diploma">
                        <input type="hidden" name="redirect_tab" value="diploma">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-family:'Cairo';">رابط خلفية الشهادة (URL الصورة)</label>
                                <input type="url" name="diploma_bg_url" class="form-control"
                                       value="<?= htmlspecialchars($takwin_settings['diploma_bg_url'] ?? '') ?>"
                                       placeholder="https://..." style="font-family:'Outfit';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-family:'Cairo';">رابط العلامة المائية</label>
                                <input type="url" name="diploma_watermark_url" class="form-control"
                                       value="<?= htmlspecialchars($takwin_settings['diploma_watermark_url'] ?? '') ?>"
                                       placeholder="https://..." style="font-family:'Outfit';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-family:'Cairo';">اللون الأساسي (لون الإطار والنصوص)</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" name="diploma_primary_color" class="form-control form-control-color"
                                           value="<?= htmlspecialchars($takwin_settings['diploma_primary_color'] ?? '#1e3a8a') ?>" style="width:60px;height:42px;">
                                    <input type="text" class="form-control" style="font-family:'Outfit';"
                                           value="<?= htmlspecialchars($takwin_settings['diploma_primary_color'] ?? '#1e3a8a') ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold" style="font-family:'Cairo';">لون الإطار</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" name="diploma_border_color" class="form-control form-control-color"
                                           value="<?= htmlspecialchars($takwin_settings['diploma_border_color'] ?? '#1e3a8a') ?>" style="width:60px;height:42px;">
                                    <input type="text" class="form-control" style="font-family:'Outfit';"
                                           value="<?= htmlspecialchars($takwin_settings['diploma_border_color'] ?? '#1e3a8a') ?>" readonly>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning fw-bold py-2 px-5 rounded-3 text-dark" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> حفظ إعدادات الشهادة
                                </button>
                                <a href="<?php echo e(url('dashboard/settings/diplome')); ?>" class="btn btn-outline-primary fw-bold py-2 px-4 rounded-3 me-2" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-palette me-1"></i> التخصيص المتقدم
                                </a>
                                <a href="<?php echo e(url('dashboard/diplomes')); ?>" class="btn btn-outline-secondary fw-bold py-2 px-4 rounded-3 me-2" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-award me-1"></i> إدارة الشهادات
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ TAB: إدارة الوثائق والملفات ═══ -->
            <div class="settings-panel <?= $active_tab==='documents' ? 'active':'' ?>" id="tab-documents">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-folder-open text-primary me-2"></i> إدارة وثائق ملفات المترشحين والإطارات (External Storage)
                    </h5>
                    
                    <p class="text-muted small mb-4" style="font-family:'Cairo'; font-weight: 600;">
                        يقوم هذا القسم بإدارة وحوكمة وثائق المترشحين والمكونين والإطارات. يمكنك تصفية الوثائق حسب الولاية والمؤسسة والجدول المستهدف ثم مزامنتها من قاعدة البيانات إلى خادم الملفات لتحسين الأداء وتقليل حجم قاعدة البيانات.
                    </p>

                    <!-- ── Filters Panel ── -->
                    <div class="p-3 mb-4 rounded-3" style="background:rgba(26,107,204,.05); border:1.5px solid rgba(26,107,204,.15);">
                        <h6 class="fw-bold mb-3" style="font-family:'Cairo';color:var(--electric);">
                            <i class="fa-solid fa-filter me-1"></i> فلاتر البحث والتصفية
                        </h6>
                        <form method="GET" action="<?php echo e(url('dashboard/settings')); ?>" id="docFilterForm" class="row g-3 align-items-end">
                            <input type="hidden" name="tab" value="documents">
                            
                            <!-- الجدول المستهدف -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold" style="font-family:'Cairo';">الجدول / المصدر</label>
                                <select name="doc_table" id="docTableSelect" class="form-select form-select-sm" style="font-family:'Cairo';" onchange="this.form.submit()">
                                    <option value="candidat_document" <?php echo e(($selected_table ?? 'candidat_document') === 'candidat_document' ? 'selected' : ''); ?>>وثائق المترشح (candidat_document)</option>
                                    <option value="candidat_certifscol" <?php echo e(($selected_table ?? '') === 'candidat_certifscol' ? 'selected' : ''); ?>>الشهادات المدرسية (certifscol)</option>
                                    <option value="candidat_contratapp" <?php echo e(($selected_table ?? '') === 'candidat_contratapp' ? 'selected' : ''); ?>>عقود التمهين (contratapp)</option>
                                    <option value="encadremen_memo" <?php echo e(($selected_table ?? '') === 'encadremen_memo' ? 'selected' : ''); ?>>ملفات الإطارات (encadremen_memo)</option>
                                </select>
                            </div>

                            <!-- الولاية -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold" style="font-family:'Cairo';">الولاية</label>
                                <select name="wilaya_id" id="docWilayaSelect" class="form-select form-select-sm" style="font-family:'Cairo';" onchange="filterEtabs(); setTimeout(() => this.form.submit(), 50)">
                                    <option value="">— كل الولايات —</option>
                                    <?php $__currentLoopData = $wilayas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($w->IDWilayaa); ?>" <?php echo e(($selected_wilaya ?? null) == $w->IDWilayaa ? 'selected' : ''); ?>>
                                            <?php echo e($w->Nom); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <!-- المؤسسة -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold" style="font-family:'Cairo';">المؤسسة / المركز</label>
                                <select name="etab_id" id="docEtabSelect" class="form-select form-select-sm" style="font-family:'Cairo';">
                                    <option value="">— كل المؤسسات —</option>
                                    <?php $__currentLoopData = $etablissements ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $etab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($etab->IDEts_Form); ?>"
                                                data-wilaya="<?php echo e($etab->IDDFEP); ?>"
                                                <?php echo e(($selected_etab ?? null) == $etab->IDEts_Form ? 'selected' : ''); ?>>
                                            <?php echo e($etab->Nom); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm fw-bold w-100 rounded-pill" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> تصفية
                                </button>
                            </div>
                        </form>
                        <?php if(($selected_wilaya ?? null) || ($selected_etab ?? null) || ($selected_table ?? 'candidat_document') !== 'candidat_document'): ?>
                        <div class="mt-2">
                            <a href="<?php echo e(url('dashboard/settings')); ?>?tab=documents" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-family:'Cairo'; font-size:.78rem;">
                                <i class="fa-solid fa-xmark me-1"></i> إلغاء الفلاتر
                            </a>
                            <span class="text-muted small ms-2" style="font-family:'Cairo';">
                                النتائج مصفاة
                                <?php if($selected_wilaya ?? null): ?> · حسب الولاية <?php endif; ?>
                                <?php if($selected_etab ?? null): ?> · حسب المؤسسة <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Stats Widgets -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center" style="background: var(--bg-surface-elevated, #f8fafc);">
                                <div style="font-size:1.6rem;font-weight:900;font-family:'Outfit';color:var(--electric);">
                                    <?php echo e(number_format($doc_stats['total_candidates'] ?? 0)); ?>

                                </div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">إجمالي السجلات</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center" style="background: var(--bg-surface-elevated, #f8fafc);">
                                <div style="font-size:1.6rem;font-weight:900;font-family:'Outfit';color:#0EA66E;">
                                    <?php echo e(number_format($doc_stats['synced_documents'] ?? 0)); ?>

                                </div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">مزامَن ✅ (على القرص)</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center" style="background: var(--bg-surface-elevated, #f8fafc);">
                                <div style="font-size:1.6rem;font-weight:900;font-family:'Outfit';color:#dc3545;">
                                    <?php echo e(number_format($doc_stats['unsynced_documents'] ?? 0)); ?>

                                </div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">غير مزامَن ❌ (blob)</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="glass-panel p-3 text-center" style="background: var(--bg-surface-elevated, #f8fafc);">
                                <div style="font-size:1.6rem;font-weight:900;font-family:'Outfit';color:#F0A500;">
                                    <?php echo e($doc_stats['storage_size_mb'] ?? '0.00'); ?> MB
                                </div>
                                <div style="font-size:.78rem;font-weight:700;color:var(--tx-2);font-family:'Cairo';">حجم مجلد التخزين</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Tools -->
                    <div class="p-3 mb-4 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1px solid var(--border);">
                        <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-rotate text-primary me-1"></i> مزامنة وتحويل الوثائق (Blobs ⟶ Files)</h6>
                        <p class="text-muted small mb-3">تقوم هذه العملية باستخراج ملفات الوثائق المخزنة كـ Blobs وحفظها بشكل منظم على القرص، وتحديث روابطها في الجداول، ثم مسحها من قاعدة البيانات لتحرير المساحة. يتم تطبيق الفلاتر المحددة أعلاه.</p>
                        
                        <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" id="docSyncForm" class="row g-2 align-items-center">
                            <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="section" value="documents_sync">
                            <input type="hidden" name="redirect_tab" value="documents">
                            <input type="hidden" name="doc_table" value="<?php echo e($selected_table ?? 'candidat_document'); ?>">
                            <?php if($selected_wilaya ?? null): ?>
                                <input type="hidden" name="wilaya_id" value="<?php echo e($selected_wilaya); ?>">
                            <?php endif; ?>
                            <?php if($selected_etab ?? null): ?>
                                <input type="hidden" name="etab_id" value="<?php echo e($selected_etab); ?>">
                            <?php endif; ?>
                            
                            <div class="col-auto">
                                <label class="small fw-bold text-muted me-2">حجم الدفعة:</label>
                            </div>
                            <div class="col-auto">
                                <select name="sync_limit" class="form-select form-select-sm border-0" style="background: var(--input-bg, #f1f5f9); font-size: 0.85rem; border-radius: 8px;">
                                    <option value="100">100 وثيقة</option>
                                    <option value="500" selected>500 وثيقة (موصى به)</option>
                                    <option value="1000">1000 وثيقة</option>
                                    <option value="5000">5000 وثيقة</option>
                                </select>
                            </div>
                            <div class="col-auto ms-auto">
                                <button type="submit" class="btn btn-primary btn-sm fw-bold px-4 py-2 rounded-pill" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-play me-1"></i> بدء معالجة الدفعة
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Preview Documents Table -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3" style="font-family:'Cairo';">
                            <i class="fa-solid fa-eye text-info me-1"></i> معاينة الوثائق (أول 10 سجلات)
                            <span class="text-muted small fw-normal">— يعرض أصحاب الوثائق وحالة المزامنة</span>
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0" style="font-family:'Cairo';">
                                <thead style="background:rgba(26,107,204,.07); color:var(--tx-2);">
                                    <tr>
                                        <th style="font-size:.78rem;">صاحب الوثيقة</th>
                                        <th style="font-size:.78rem;">الدور</th>
                                        <th style="font-size:.78rem;">الولاية</th>
                                        <th style="font-size:.78rem;">المؤسسة</th>
                                        <th style="font-size:.78rem;">نوع الوثيقة</th>
                                        <th class="text-center" style="font-size:.78rem;">الحالة</th>
                                        <th style="font-size:.78rem;">المسار</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $previewDocs = $preview_docs ?? []; ?>
                                <?php if(empty($previewDocs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4 fw-bold">
                                            <i class="fa-regular fa-folder-open me-1"></i>
                                            لا توجد وثائق تطابق الفلاتر المحددة أو الجداول فارغة حالياً.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $__currentLoopData = $previewDocs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo e($doc['owner_name'] ?: '—'); ?></td>
                                        <td>
                                            <span class="stat-badge <?php echo e($doc['owner_type'] === 'مكون / إطار' ? 'gold' : 'blue'); ?>" style="font-size:.7rem;">
                                                <?php echo e($doc['owner_type']); ?>

                                            </span>
                                        </td>
                                        <td class="text-muted"><?php echo e($doc['wilaya']); ?></td>
                                        <td class="text-muted" style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo e($doc['institution']); ?>">
                                            <?php echo e($doc['institution']); ?>

                                        </td>
                                        <td>
                                            <span class="stat-badge green" style="font-size:.7rem;">
                                                <i class="fa-solid fa-file-alt me-1"></i><?php echo e($doc['doc_label']); ?>

                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if($doc['status'] === 'synced'): ?>
                                                <span class="stat-badge green" style="font-size:.7rem;">✅ مزامَن</span>
                                            <?php else: ?>
                                                <span class="stat-badge red" style="font-size:.7rem;">❌ Blob</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-family:'Outfit'; font-size:.75rem; color:var(--tx-3);" title="<?php echo e($doc['path']); ?>">
                                            <?php echo e($doc['path']); ?>

                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Maintenance Tools -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 h-100 text-right" style="background:var(--bg-surface-elevated); border:1px solid var(--border);">
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-shield-halved text-success me-1"></i> حماية وتأمين المجلد (.htaccess)</h6>
                                <p class="text-muted small mb-3">كتابة ملف إعدادات Apache داخل مجلد الوثائق لتعطيل تفسير أو تشغيل أي نصوص برمجية (PHP/CGI) يتم رفعها بالخطأ لحماية السيرفر من ثغرات الاختراق.</p>
                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="section" value="documents_secure">
                                    <input type="hidden" name="redirect_tab" value="documents">
                                    <button type="submit" class="btn btn-sm btn-outline-success fw-bold px-3 py-2 rounded-pill w-100" style="font-family:'Cairo';">
                                        <i class="fa-solid fa-lock me-1"></i> كتابة ملف .htaccess والتأمين
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 h-100 text-right" style="background:var(--bg-surface-elevated); border:1px solid var(--border);">
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-broom text-warning me-1"></i> تنظيف الملفات اليتيمة (Orphans Cleanup)</h6>
                                <p class="text-muted small mb-3">مسح دوري للبحث عن أي ملفات مخزنة على القرص ولا توجد لها روابط مطابقة في قاعدة البيانات لمترشحين تم حذفهم أو وثائق ملغاة لتوفير مساحة التخزين.</p>
                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" onsubmit="return confirm('هل أنت متأكد من بدء عملية فحص وتنظيف الملفات اليتيمة؟ قد تستغرق بعض الوقت.')">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="section" value="documents_cleanup">
                                    <input type="hidden" name="redirect_tab" value="documents">
                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-bold px-3 py-2 rounded-pill w-100" style="font-family:'Cairo';">
                                        <i class="fa-solid fa-trash-can me-1"></i> فحص وتنظيف المجلد
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: النسخ الاحتياطي لقاعدة البيانات ═══ -->
            <div class="settings-panel <?= $active_tab==='backup' ? 'active':'' ?>" id="tab-backup">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-file-zipper text-primary me-2"></i> النسخ الاحتياطي لقاعدة البيانات (Database Backup)
                    </h5>
                    
                    <p class="text-muted small mb-4" style="font-family:'Cairo'; font-weight: 600;">
                        تتيح لك هذه الأداة إنشاء نسخ احتياطية كاملة ومضغوطة من قاعدة البيانات وتنزيلها بشكل آمن. 
                        تمت تهيئة محرك النسخ لدعم الجداول الضخمة (مثل جدول المترشحين) عبر المعالجة التدريجية دون استهلاك ذاكرة السيرفر.
                    </p>

                    <div class="p-3 mb-4 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1px solid var(--border);">
                        <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-download text-primary me-1"></i> إنشاء نسخة جديدة (Create Backup)</h6>
                        <p class="text-muted small mb-3">تقوم هذه العملية بإنشاء ملف SQL كامل لهيكل وبيانات الجداول ثم ضغطه مباشرة في أرشيف ZIP لتوفير مساحة التخزين وسرعة التحميل.</p>
                        
                        <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                            <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="section" value="backup_create">
                            <input type="hidden" name="redirect_tab" value="backup">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold px-4 py-2 rounded-pill" style="font-family:'Cairo';">
                                <i class="fa-solid fa-play me-1"></i> بدء عملية النسخ الاحتياطي الآن
                            </button>
                        </form>
                    </div>

                    <!-- Backups List -->
                    <h6 class="fw-bold mb-3" style="font-family:'Cairo';"><i class="fa-solid fa-list text-muted me-1"></i> ملفات النسخ الاحتياطي المتوفرة</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="bg-light text-muted fw-bold">
                                <tr>
                                    <th class="text-start">اسم ملف الأرشيف</th>
                                    <th class="text-center">الحجم (MB)</th>
                                    <th class="text-center">تاريخ الإنشاء</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($backups)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4 fw-bold">لا توجد ملفات نسخ احتياطي حالياً.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($backups as $bkp): ?>
                                        <tr>
                                            <td class="fw-bold text-start" style="font-family:'Outfit'; direction: ltr;">
                                                <i class="fa-regular fa-file-zip text-warning me-2"></i><?= htmlspecialchars($bkp['filename']) ?>
                                            </td>
                                            <td class="text-center fw-bold text-primary"><?= number_format($bkp['size_mb'], 2) ?> MB</td>
                                            <td class="text-center text-muted" style="font-family:'Outfit';"><?= htmlspecialchars($bkp['created_at']) ?></td>
                                            <td class="text-center d-flex justify-content-center gap-2">
                                                <a href="<?php echo e(url('dashboard/settings/backup/download/' . urlencode($bkp['filename']))); ?>" 
                                                   class="btn btn-sm btn-success py-1 px-3 rounded-pill" style="font-size:.78rem;font-family:'Cairo';">
                                                    <i class="fa-solid fa-download me-1"></i> تحميل
                                                </a>
                                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" onsubmit="return confirm('هل أنت متأكد من حذف هذا الملف نهائياً؟')" style="display:inline;">
                                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                    <input type="hidden" name="section" value="backup_delete">
                                                    <input type="hidden" name="filename" value="<?= htmlspecialchars($bkp['filename']) ?>">
                                                    <input type="hidden" name="redirect_tab" value="backup">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-3 rounded-pill" style="font-size:.78rem;font-family:'Cairo';">
                                                        <i class="fa-solid fa-trash-can me-1"></i> حذف
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: التحكم في الترخيص ═══ -->
            <div class="settings-panel <?= $active_tab==='sovereign' ? 'active':'' ?>" id="tab-sovereign">
                <!-- 1. Global Activation Status -->
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-shield-halved text-primary me-2"></i> إعدادات الترخيص ونظام التشغيل (Licensing Control)
                    </h5>
                    <p class="text-muted small mb-4" style="font-family:'Cairo'; font-weight:600;">
                        يتحكم هذا القسم بفرض شروط الترخيص والتحكم في منصة GFEP. عند التفعيل، يتم حظر دخول أي مستخدم (باستثناء مشرفي النظام والوزارة) ما لم يكن حسابه أو مؤسسته مرتبطة برمز تفعيل صالح.
                    </p>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" class="p-3 mb-3 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="sovereign_toggle">
                        <input type="hidden" name="redirect_tab" value="sovereign">
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';">فرض التفعيل بالرخصة (Enforce Licensing)</h6>
                                <p class="text-muted small mb-0">تفعيل جدار الحماية (Middleware) لتقييد الوصول إلى لوحة التحكم بالكامل.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_activation_required" value="1"
                                       <?= $is_activation_required ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" class="p-3 mb-3 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="shield_toggle">
                        <input type="hidden" name="redirect_tab" value="sovereign">
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';">تفعيل درع حماية المحتوى (Content Protection Shield)</h6>
                                <p class="text-muted small mb-0">تفعيل حماية محتوى المنصة من التقاط صور الشاشة أو تصوير الفيديو (Anti-Screenshot/Recording).</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_shield_active" value="1"
                                       <?= $is_shield_active ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" class="p-3 mb-3 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="captcha_toggle">
                        <input type="hidden" name="redirect_tab" value="sovereign">
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';">تفعيل التحقق البشري (CAPTCHA) في تسجيل الدخول</h6>
                                <p class="text-muted small mb-0">تفعيل عملية حسابية بسيطة لحماية المنصة من محاولات التخمين الآلي للمتسللين (Brute Force).</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="login_captcha_active" value="1"
                                       <?= $is_captcha_active ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" class="p-3 mb-3 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="hide_other_logins_toggle">
                        <input type="hidden" name="redirect_tab" value="sovereign">
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';">إخفاء بقية بوابات الدخول (موظف، متربص، حساب خاص)</h6>
                                <p class="text-muted small mb-0">عند التفعيل، سيظهر خيار "المؤسسة التكوينية" فقط في واجهة الدخول العامة، مع إخفاء باقي البوابات للحماية، وإمكانية الدخول للحساب الخاص عبر الرابط المخفي ببيانات الاتصال بالأسفل.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="hide_other_login_portals" value="1"
                                       <?= $hide_other_login_portals ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" class="p-3 mb-3 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="patrimoine_media_toggle">
                        <input type="hidden" name="redirect_tab" value="sovereign">
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo';">السماح للمستخدمين بإدارة صور الممتلكات (إضافة، تعديل، حذف)</h6>
                                <p class="text-muted small mb-0">تمكين مدراء المؤسسات والمستخدمين من رفع وتحديث أو حذف صور التجهيزات، المركبات، والسكنات الوظيفية مباشرة.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="patrimoine_media_actions_enabled" value="1"
                                       <?= $patrimoine_media_actions_enabled ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>

                    <!-- Sovereign Protective Banner -->
                    <div class="alert alert-warning border-0 d-flex align-items-center gap-3 py-3 px-4 mb-0 rounded-3" style="background:rgba(240,165,0,.08);border-right:4px solid #F0A500 !important;">
                        <i class="fa-solid fa-user-lock fs-4 text-warning"></i>
                        <div class="small fw-bold text-right" style="font-family:'Cairo';color:#785200;line-height:1.6;">
                            <strong>درع الحماية (Shield) نشط برمجياً:</strong>
                            المنظومة مصممة لتشفير وحماية شاشات البيانات والتقاط الشاشة (Anti-Screenshot/Recording) بشكل تلقائي وتنشط فورياً لجميع المستخدمين الذين تم التحقق من ترخيصهم.
                        </div>
                    </div>
                </div>

                <!-- 2. Master Emergency Key & Batch Generator -->
                <div class="row g-4 mb-4">
                    <!-- Generator -->
                    <div class="col-md-7">
                        <div class="glass-panel p-4 h-100">
                            <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--green);padding-right:.6rem;">
                                <i class="fa-solid fa-circle-plus text-success me-2"></i> توليد حزمة مفاتيح تفعيل جديدة
                            </h5>
                            <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                                <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                <input type="hidden" name="section" value="sovereign_generate">
                                <input type="hidden" name="redirect_tab" value="sovereign">

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold" style="font-family:'Cairo';">عدد المفاتيح</label>
                                        <input type="number" name="generate_count" class="form-control form-control-sm" value="1" min="1" max="100" style="font-family:'Outfit';">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold" style="font-family:'Cairo';">الولاية (اختياري للفحص)</label>
                                        <select id="genWilayaSelect" class="form-select form-select-sm" style="font-family:'Cairo';">
                                            <option value="">— كل الولايات —</option>
                                            <?php $__currentLoopData = $wilayas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($w->IDWilayaa); ?>"><?php echo e($w->Nom); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold" style="font-family:'Cairo';">ربط بمؤسسة معينة</label>
                                        <select name="generate_ets_id" id="genEtabSelect" class="form-select form-select-sm" style="font-family:'Cairo';">
                                            <option value="">— مفتاح عام (غير مرتبط) —</option>
                                            <?php $__currentLoopData = $etablissements ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $etab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($etab->IDetablissement); ?>" data-wilaya="<?php echo e($etab->IDDFEP); ?>">
                                                    <?php echo e($etab->Nom); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold" style="font-family:'Cairo';">ربط بمستخدم محدد</label>
                                        <select name="generate_user_id" id="genUserSelect" class="form-select form-select-sm" style="font-family:'Cairo';">
                                            <option value="">— مفتاح عام (غير مرتبط) —</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 rounded-pill" style="font-family:'Cairo';">
                                            <i class="fa-solid fa-gears me-1"></i> توليد المفاتيح عبر البصمة المشفرة
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Master Key Info -->
                    <div class="col-md-5">
                        <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between" style="border: 1px solid rgba(220,53,69,.25); background: rgba(220,53,69,.02);">
                            <div>
                                <h5 class="fw-black mb-2 text-danger" style="font-family:'Cairo';border-right:3px solid var(--red);padding-right:.6rem;">
                                    <i class="fa-solid fa-triangle-exclamation me-2"></i> مفتاح الطوارئ (Master Key)
                                </h5>
                                <p class="text-muted mb-3" style="font-size:.78rem; font-family:'Cairo'; font-weight:600; line-height:1.6;">
                                    مفتاح تشغيل استثنائي معرّف برمجياً ومحمي ببصمة المطور. يسمح بتجاوز الحظر فورياً وإصلاح النظام دون الاتصال بقاعدة البيانات في حالات الطوارئ.
                                </p>
                            </div>
                            
                            <div class="p-3 rounded-3 text-center mb-0" style="background:rgba(0,0,0,.03); border:1px solid var(--border);">
                                <code class="fw-bold fs-6 text-danger" style="font-family:'Outfit', monospace; letter-spacing:0.05em; user-select: all; -webkit-user-select: all;">
                                    <?php echo e($master_key); ?>

                                </code>
                                <div class="text-muted small mt-2" style="font-size:.7rem; font-family:'Cairo'; font-weight:700;">
                                    مفتاح المطور
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. License Keys List Table -->
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';"><i class="fa-solid fa-key me-1"></i> سجل مفاتيح الترخيص الصادرة</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="bg-light text-muted fw-bold">
                                <tr>
                                    <th class="text-start">مفتاح الترخيص</th>
                                    <th class="text-start">المؤسسة المرتبطة</th>
                                    <th class="text-center">حالة المفتاح</th>
                                    <th class="text-center">المستخدم / تاريخ التفعيل</th>
                                    <th class="text-center">تاريخ الانتهاء</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($license_keys)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4 fw-bold">لم يتم توليد أي مفاتيح تفعيل بعد.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($license_keys as $lk): 
                                        $isActivated = !empty($lk->activated_at);
                                        $isExpired = $isActivated && strtotime($lk->expires_at) < time();
                                        
                                        $statusClass = 'blue';
                                        $statusText = 'جاهز للاستخدام';
                                        if ($isExpired) {
                                            $statusClass = 'red';
                                            $statusText = 'منتهي الصلاحية';
                                        } elseif ($isActivated) {
                                            $statusClass = 'green';
                                            $statusText = 'نشط ومفعل';
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-start" style="font-family:'Outfit'; direction: ltr;">
                                                <code class="fw-bold" style="font-size:.82rem; color:var(--tx-1);"><?php echo e($lk->license_key); ?></code>
                                            </td>
                                            <td class="text-start fw-bold" style="font-family:'Cairo';">
                                                <?php echo e($lk->etablissement_name); ?>

                                            </td>
                                            <td class="text-center">
                                                <span class="stat-badge <?php echo e($statusClass); ?>">
                                                    <i class="fa-solid fa-circle" style="font-size:.45rem;"></i>
                                                    <?php echo e($statusText); ?>

                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if($isActivated): ?>
                                                    <span class="fw-bold text-primary"><?php echo e($lk->user_nom ?? $lk->username); ?></span>
                                                    <div class="text-muted" style="font-size:.72rem; font-family:'Outfit';"><?php echo e(date('Y-m-d H:i', strtotime($lk->activated_at))); ?></div>
                                                <?php elseif($lk->user_id): ?>
                                                    <span class="fw-bold text-secondary"><?php echo e($lk->user_nom ?? $lk->username); ?></span>
                                                    <div class="badge bg-warning text-dark small d-inline-block" style="font-size:.65rem; font-family:'Cairo';">محجوز للمستخدم (غير نشط)</div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center fw-bold" style="font-family:'Outfit';">
                                                <?php if($lk->expires_at): ?>
                                                    <span class="<?php echo e($isExpired ? 'text-danger' : 'text-success'); ?>">
                                                        <?php echo e(date('Y-m-d', strtotime($lk->expires_at))); ?>

                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <!-- Extend Action -->
                                                    <?php if($isActivated): ?>
                                                        <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" style="display:inline;">
                                                            <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="section" value="sovereign_extend">
                                                            <input type="hidden" name="key_id" value="<?php echo e($lk->id); ?>">
                                                            <input type="hidden" name="extend_days" value="365">
                                                            <input type="hidden" name="redirect_tab" value="sovereign">
                                                            <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill" style="font-size:.72rem;font-family:'Cairo';" title="تمديد 365 يوم">
                                                                <i class="fa-solid fa-calendar-plus"></i> تمديد عام
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <!-- Delete Action -->
                                                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" onsubmit="return confirm('هل أنت متأكد من إلغاء/حذف هذا المفتاح نهائياً؟')" style="display:inline;">
                                                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                        <input type="hidden" name="section" value="sovereign_delete">
                                                        <input type="hidden" name="key_id" value="<?php echo e($lk->id); ?>">
                                                        <input type="hidden" name="redirect_tab" value="sovereign">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" style="font-size:.72rem;font-family:'Cairo';">
                                                            <i class="fa-solid fa-trash-can"></i> حذف
                                                        </button>
                                                    </form>
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

            <!-- ═══ TAB: الأمان والميزات المتقدمة ═══ -->
            <div class="settings-panel <?= $active_tab==='security' ? 'active':'' ?>" id="tab-security">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-key-skeleton text-primary me-2"></i> الأمان والميزات المتقدمة (Security & Advanced Features)
                    </h5>
                    <p class="text-muted small mb-4" style="font-family:'Cairo'; font-weight:600;">
                        يمكنك هنا تفعيل أو تعطيل ميزات الأمان المتقدمة والتكامل الرقمي لمنصة SGFEP لضمان أقصى درجات الفعالية والحماية.
                    </p>

                    <div class="row g-4">
                        <!-- 1. SSO / OAuth -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 text-right h-100 d-flex flex-column justify-content-between" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-brands fa-google text-primary me-1"></i> تسجيل الدخول الموحد (SSO / OAuth)</h6>
                                    <p class="text-muted small mb-3">السماح للمستخدمين بتسجيل الدخول الفوري والآمن باستخدام حسابات Google Workspace أو Microsoft Azure AD.</p>
                                </div>
                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="section" value="sso_toggle">
                                    <input type="hidden" name="redirect_tab" value="security">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge <?php echo e($is_sso_enabled ? 'bg-success' : 'bg-secondary'); ?> small">
                                            <?php echo e($is_sso_enabled ? 'نشط / Activé' : 'معطل / Désactivé'); ?>

                                        </span>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="sso_enabled" value="1"
                                                   <?= $is_sso_enabled ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- 2. Push Notifications -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 text-right h-100 d-flex flex-column justify-content-between" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-bell text-warning me-1"></i> إشعارات المتصفح الفورية (Push Notifications)</h6>
                                    <p class="text-muted small mb-3">إرسال إشعارات فورية مباشرة إلى شريط إشعارات هاتف أو متصفح المستخدم عبر بروتوكول VAPID المطور.</p>
                                </div>
                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="section" value="push_toggle">
                                    <input type="hidden" name="redirect_tab" value="security">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge <?php echo e($is_push_enabled ? 'bg-success' : 'bg-secondary'); ?> small">
                                            <?php echo e($is_push_enabled ? 'نشط / Activé' : 'معطل / Désactivé'); ?>

                                        </span>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="push_notifications_enabled" value="1"
                                                   <?= $is_push_enabled ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- 3. SSE Real-Time KPIs -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 text-right h-100 d-flex flex-column justify-content-between" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-bolt text-danger me-1"></i> البث الفوري للمؤشرات (SSE KPIs)</h6>
                                    <p class="text-muted small mb-3">تحديث لوحة تحكم الإدارة ومؤشرات الأداء فورياً وبدون انقطاع عبر تقنية Server-Sent Events (SSE).</p>
                                </div>
                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="section" value="sse_toggle">
                                    <input type="hidden" name="redirect_tab" value="security">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge <?php echo e($is_sse_enabled ? 'bg-success' : 'bg-secondary'); ?> small">
                                            <?php echo e($is_sse_enabled ? 'نشط / Activé' : 'معطل / Désactivé'); ?>

                                        </span>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="sse_realtime_kpis_enabled" value="1"
                                                   <?= $is_sse_enabled ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- 4. Rate Limiting -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 text-right h-100 d-flex flex-column justify-content-between" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.15);">
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-family:'Cairo';"><i class="fa-solid fa-hand-stop text-info me-1"></i> محدد معدل الطلبات المتقدم (Rate Limiting)</h6>
                                    <p class="text-muted small mb-3">حماية البوابة من هجمات الحرمان من الخدمة (DDoS) والطلبات المتكررة لضمان ثبات المنصة.</p>
                                </div>
                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="section" value="rate_limiting_toggle">
                                    <input type="hidden" name="redirect_tab" value="security">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge <?php echo e($is_rate_limiting_enabled ? 'bg-success' : 'bg-secondary'); ?> small">
                                            <?php echo e($is_rate_limiting_enabled ? 'نشط / Activé' : 'معطل / Désactivé'); ?>

                                        </span>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="rate_limiting_enabled" value="1"
                                                   <?= $is_rate_limiting_enabled ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. VAPID Keys & Test Push -->
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--green);padding-right:.6rem;">
                        <i class="fa-solid fa-tower-broadcast text-success me-2"></i> شهادة التوقيع والمفاتيح الأمنية لخدمة البث (VAPID Configuration)
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">المفتاح العام للبث (VAPID Public Key)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-key"></i></span>
                                <input type="text" class="form-control form-control-sm" readonly style="font-family:'Outfit'; font-size:.78rem;" value="<?php echo e(env('VAPID_PUBLIC_KEY', '— غير مهيأ —')); ?>">
                            </div>
                            <p class="text-muted small mt-1" style="font-size:.72rem;">يستخدم هذا المفتاح للتحقق من هوية الخادم داخل متصفح العميل عند الاشتراك.</p>
                        </div>
                        <div class="col-md-5 d-flex flex-column justify-content-end">
                            <button type="button" class="btn btn-outline-success btn-sm fw-bold py-2 w-100 rounded-pill" onclick="sendTestPush()" style="font-family:'Cairo';">
                                <i class="fa-solid fa-paper-plane me-1"></i> إرسال إشعار تجريبي (Test Push)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: إدارة طلبات الموظفين ═══ -->
            <div class="settings-panel <?= $active_tab==='requests' ? 'active':'' ?>" id="tab-requests">
                <!-- 1. Leave Requests Table -->
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-calendar-day text-primary me-2"></i> طلبات الإجازات المقدمة من طرف الموظفين
                    </h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="bg-light text-muted fw-bold">
                                <tr>
                                    <th class="text-start">الموظف</th>
                                    <th class="text-start">المؤسسة / المركز</th>
                                    <th class="text-center">نوع الإجازة</th>
                                    <th class="text-center">تاريخ البدء</th>
                                    <th class="text-center">تاريخ الانتهاء</th>
                                    <th class="text-center">السبب</th>
                                    <th class="text-center">حالة الطلب</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employee_leaves)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4 fw-bold">لا توجد طلبات إجازة مقدمة حالياً.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employee_leaves as $el): 
                                        $statusClass = 'gold';
                                        $statusText = 'قيد الانتظار';
                                        if ($el->status === 'approved') {
                                            $statusClass = 'green';
                                            $statusText = 'مقبولة';
                                        } elseif ($el->status === 'rejected') {
                                            $statusClass = 'red';
                                            $statusText = 'مرفوضة';
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-start fw-bold" style="font-family:'Cairo';">
                                                <?php echo e($el->emp_nom); ?> <?php echo e($el->emp_prenom); ?>

                                            </td>
                                            <td class="text-start text-muted" style="font-family:'Cairo';">
                                                <?php echo e($el->etab_nom ?? '—'); ?>

                                            </td>
                                            <td class="text-center fw-bold" style="font-family:'Cairo';">
                                                <?php echo e($el->leave_type); ?>

                                            </td>
                                            <td class="text-center" style="font-family:'Outfit';">
                                                <?php echo e(date('Y-m-d', strtotime($el->start_date))); ?>

                                            </td>
                                            <td class="text-center" style="font-family:'Outfit';">
                                                <?php echo e(date('Y-m-d', strtotime($el->end_date))); ?>

                                            </td>
                                            <td class="text-center text-muted" style="font-family:'Cairo'; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo e($el->reason); ?>">
                                                <?php echo e($el->reason ?: '—'); ?>

                                            </td>
                                            <td class="text-center">
                                                <span class="stat-badge <?php echo e($statusClass); ?>">
                                                    <i class="fa-solid fa-circle" style="font-size:.45rem;"></i>
                                                    <?php echo e($statusText); ?>

                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($el->status === 'pending'): ?>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" style="display:inline;">
                                                            <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="section" value="leave_action">
                                                            <input type="hidden" name="id" value="<?php echo e($el->id); ?>">
                                                            <input type="hidden" name="status" value="approved">
                                                            <input type="hidden" name="redirect_tab" value="requests">
                                                            <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill" style="font-size:.72rem;font-family:'Cairo';">
                                                                <i class="fa-solid fa-circle-check"></i> قبول
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" style="display:inline;">
                                                            <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="section" value="leave_action">
                                                            <input type="hidden" name="id" value="<?php echo e($el->id); ?>">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <input type="hidden" name="redirect_tab" value="requests">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" style="font-size:.72rem;font-family:'Cairo';">
                                                                <i class="fa-solid fa-circle-xmark"></i> رفض
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Document Requests Table -->
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--green);padding-right:.6rem;">
                        <i class="fa-solid fa-file-signature text-success me-2"></i> طلبات الوثائق الإدارية والمهنية
                    </h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="bg-light text-muted fw-bold">
                                <tr>
                                    <th class="text-start">الموظف</th>
                                    <th class="text-start">المؤسسة / المركز</th>
                                    <th class="text-center">نوع الوثيقة المطلوبة</th>
                                    <th class="text-center">رمز التحقق الرقمي</th>
                                    <th class="text-center">تاريخ الطلب</th>
                                    <th class="text-center">حالة الطلب</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employee_docs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4 fw-bold">لا توجد طلبات وثائق مقدمة حالياً.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employee_docs as $ed): 
                                        $statusClass = 'gold';
                                        $statusText = 'قيد الانتظار';
                                        if ($ed->status === 'approved') {
                                            $statusClass = 'green';
                                            $statusText = 'تم التجهيز';
                                        } elseif ($ed->status === 'rejected') {
                                            $statusClass = 'red';
                                            $statusText = 'مرفوض';
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-start fw-bold" style="font-family:'Cairo';">
                                                <?php echo e($ed->emp_nom); ?> <?php echo e($ed->emp_prenom); ?>

                                            </td>
                                            <td class="text-start text-muted" style="font-family:'Cairo';">
                                                <?php echo e($ed->etab_nom ?? '—'); ?>

                                            </td>
                                            <td class="text-center fw-bold" style="font-family:'Cairo';">
                                                <?php echo e($ed->document_type); ?>

                                            </td>
                                            <td class="text-center" style="font-family:'Outfit';">
                                                <code class="fw-bold text-dark"><?php echo e($ed->code_verification); ?></code>
                                            </td>
                                            <td class="text-center" style="font-family:'Outfit';">
                                                <?php echo e(date('Y-m-d H:i', strtotime($ed->created_at))); ?>

                                            </td>
                                            <td class="text-center">
                                                <span class="stat-badge <?php echo e($statusClass); ?>">
                                                    <i class="fa-solid fa-circle" style="font-size:.45rem;"></i>
                                                    <?php echo e($statusText); ?>

                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($ed->status === 'pending'): ?>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" style="display:inline;">
                                                            <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="section" value="doc_action">
                                                            <input type="hidden" name="id" value="<?php echo e($ed->id); ?>">
                                                            <input type="hidden" name="status" value="approved">
                                                            <input type="hidden" name="redirect_tab" value="requests">
                                                            <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill" style="font-size:.72rem;font-family:'Cairo';">
                                                                <i class="fa-solid fa-circle-check"></i> تم التجهيز
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" style="display:inline;">
                                                            <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="section" value="doc_action">
                                                            <input type="hidden" name="id" value="<?php echo e($ed->id); ?>">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <input type="hidden" name="redirect_tab" value="requests">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" style="font-size:.72rem;font-family:'Cairo';">
                                                                <i class="fa-solid fa-circle-xmark"></i> رفض
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
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

            <!-- ═══ TAB: إعدادات خوادم الوزارة والمزامنة ═══ -->
            <div class="settings-panel <?= $active_tab==='ministry' ? 'active':'' ?>" id="tab-ministry">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-server text-primary me-2"></i> إعدادات خوادم ومزامنة الوزارة الوصية
                    </h5>
                    
                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="ministry_sync">
                        <input type="hidden" name="redirect_tab" value="ministry">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">اسم الوزارة بالعربية</label>
                                <input type="text" name="ministry_name" class="form-control"
                                       value="<?php echo e($ministry->Nom ?? ''); ?>" style="font-family:'Cairo';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">Nom du Ministère (Fr)</label>
                                <input type="text" name="ministry_name_fr" class="form-control"
                                       value="<?php echo e($ministry->NomFr ?? ''); ?>" style="font-family:'Outfit';">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">خادم قاعدة بيانات HFSQL (IP)</label>
                                <input type="text" name="ipsrvhfsql" class="form-control"
                                       value="<?php echo e($ministry->ipsrvhfsql ?? ''); ?>" style="font-family:'Outfit';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">منفذ HFSQL (Port)</label>
                                <input type="number" name="portsrvhfsql" class="form-control"
                                       value="<?php echo e($ministry->portsrvhfsql ?? 4900); ?>" style="font-family:'Outfit';">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">خادم قاعدة بيانات MySQL (IP)</label>
                                <input type="text" name="ipsrvmysql" class="form-control"
                                       value="<?php echo e($ministry->ipsrvmysql ?? ''); ?>" style="font-family:'Outfit';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">منفذ MySQL (Port)</label>
                                <input type="number" name="portsrvmusql" class="form-control"
                                       value="<?php echo e($ministry->portsrvmusql ?? 3306); ?>" style="font-family:'Outfit';">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">خادم FTP للملفات (IP)</label>
                                <input type="text" name="ipsrvftp" class="form-control"
                                       value="<?php echo e($ministry->ipsrvftp ?? ''); ?>" style="font-family:'Outfit';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">منفذ FTP (Port)</label>
                                <input type="number" name="portsrvftp" class="form-control"
                                       value="<?php echo e($ministry->portsrvftp ?? 21); ?>" style="font-family:'Outfit';">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">رابط الخدمة HTTP (URL)</label>
                                <input type="text" name="ipsrvhttp" class="form-control"
                                       value="<?php echo e($ministry->ipsrvhttp ?? ''); ?>" style="font-family:'Outfit';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">عنوان DNS HTTP</label>
                                <input type="text" name="dnssrvhttp" class="form-control"
                                       value="<?php echo e($ministry->dnssrvhttp ?? ''); ?>" style="font-family:'Outfit';">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">السداسي الجاري النشط (Période en cours)</label>
                                <select name="active_semester_id" class="form-select" style="font-family:'Cairo';">
                                    <?php $__currentLoopData = $allSemesters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($sem->IDPeriodeencour); ?>" <?php echo e(($currentSemesterId == $sem->IDPeriodeencour) ? 'selected' : ''); ?>>
                                            <?php echo e($sem->Nom); ?> <?php echo e($sem->NomFr ? '('.$sem->NomFr.')' : ''); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">اسم المستخدم للمزامنة</label>
                                <input type="text" name="nom_user" class="form-control"
                                       value="<?php echo e($ministry->NomUser ?? ''); ?>" style="font-family:'Outfit';">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">كلمة مرور المزامنة</label>
                                <input type="password" name="mot_de_pass" class="form-control"
                                       placeholder="••••••••" style="font-family:'Outfit';">
                                <small class="text-muted" style="font-family:'Cairo';">اتركه فارغاً لعدم التغيير. لن يتم عرضه علناً لدواعي أمنية.</small>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary fw-bold py-2 px-5 rounded-3" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> حفظ إعدادات خوادم الوزارة
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ TAB: إعدادات المظهر والواجهة ═══ -->
            <div class="settings-panel <?= $active_tab==='landing' ? 'active':'' ?>" id="tab-landing">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-palette text-primary me-2"></i> تخصيص مظهر وألوان واجهة البوابة
                    </h5>
                    
                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="landing_settings">
                        <input type="hidden" name="redirect_tab" value="landing">

                        <!-- Section: Colors & Logo -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 text-secondary" style="font-family:'Cairo';"><i class="fa-solid fa-paintbrush me-1"></i> الألوان والشعار (Logo & Colors)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">اللون الأساسي للأكسنت (Primary Accent)</label>
                                    <div class="d-flex gap-2">
                                        <input type="color" name="landing_primary_color" class="form-control form-control-color" style="width: 50px; height: 38px; padding: 4px;"
                                               value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_primary_color', '#2563EB')); ?>">
                                        <input type="text" class="form-control" placeholder="#2563EB" style="font-family:'Outfit';"
                                               onchange="this.previousElementSibling.value = this.value"
                                               value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_primary_color', '#2563EB')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">اللون الثانوي للأكسنت (Secondary Accent)</label>
                                    <div class="d-flex gap-2">
                                        <input type="color" name="landing_secondary_color" class="form-control form-control-color" style="width: 50px; height: 38px; padding: 4px;"
                                               value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_secondary_color', '#0EA5E9')); ?>">
                                        <input type="text" class="form-control" placeholder="#0EA5E9" style="font-family:'Outfit';"
                                               onchange="this.previousElementSibling.value = this.value"
                                               value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_secondary_color', '#0EA5E9')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">لون الخلفية للبوابة (Background Color)</label>
                                    <div class="d-flex gap-2">
                                        <input type="color" name="landing_bg_color" class="form-control form-control-color" style="width: 50px; height: 38px; padding: 4px;"
                                               value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_bg_color', '#EEF2F6')); ?>">
                                        <input type="text" class="form-control" placeholder="#EEF2F6" style="font-family:'Outfit';"
                                               onchange="this.previousElementSibling.value = this.value"
                                               value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_bg_color', '#EEF2F6')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">شعار الموقع (Logo) - رفع ملف</label>
                                    <input type="file" name="landing_logo_file" class="form-control">
                                    <small class="text-muted" style="font-family:'Cairo';">أبعاد الشعار المفضلة: مربعة أو دائرية (PNG).</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">شعار الموقع (Logo) - رابط مباشر</label>
                                    <input type="text" name="landing_logo_url" class="form-control" placeholder="assets/images/logo.png" style="font-family:'Outfit';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_logo', 'assets/images/logo.png')); ?>">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Section: Hero & Header -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 text-secondary" style="font-family:'Cairo';"><i class="fa-solid fa-home me-1"></i> القسم الرئيسي للواجهة (Hero Section)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">العنوان الرئيسي الأول (Plain Text)</label>
                                    <input type="text" name="landing_hero_title" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_hero_title', 'المنصة الوطنية الموحدة')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">العنوان الرئيسي الملون (Gradient Accent)</label>
                                    <input type="text" name="landing_hero_title_gradient" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_hero_title_gradient', 'لتسيير التكوين المهني')); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">الوصف التعريفي للـ Hero</label>
                                    <textarea name="landing_hero_desc" class="form-control" rows="3" style="font-family:'Cairo';"><?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_hero_desc', 'البوابة الرقمية المعتمدة للتسيير الشامل لمسارات التكوين والتعليم المهنيين، تربط جميع المؤسسات عبر 58 ولاية بمركز البيانات الوطني المركزي.')); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">نص الزر الأول (Primary Action)</label>
                                    <input type="text" name="landing_hero_btn1_text" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_hero_btn1_text', 'استكشاف الخدمات')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">نص الزر الثاني (Secondary Action)</label>
                                    <input type="text" name="landing_hero_btn2_text" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_hero_btn2_text', 'عن المنصة')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">صورة اللابتوب التوضيحية (Laptop Mockup) - رفع ملف</label>
                                    <input type="file" name="landing_hero_image_file" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">صورة اللابتوب التوضيحية (Laptop Mockup) - رابط مباشر</label>
                                    <input type="text" name="landing_hero_image_url" class="form-control" placeholder="assets/images/hero_erp_generated.png" style="font-family:'Outfit';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_hero_image', 'assets/images/hero_erp_generated.png')); ?>">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Section: Features Promo -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 text-secondary" style="font-family:'Cairo';"><i class="fa-solid fa-list me-1"></i> قسم الخصائص الفرعية (Features Grid)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">العنوان الفوقي الصغير (Eyebrow)</label>
                                    <input type="text" name="landing_features_eyebrow" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_features_eyebrow', 'منصة تسيير الرقمية ERP')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">العنوان الرئيسي للقسم</label>
                                    <input type="text" name="landing_features_title" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_features_title', 'تسيير كامل للتكوين المهني')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">تكملة العنوان (الملونة)</label>
                                    <input type="text" name="landing_features_title_accent" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_features_title_accent', 'بـ نظام ذكي متكامل')); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">نص الوصف للقسم</label>
                                    <textarea name="landing_features_desc" class="form-control" rows="3" style="font-family:'Cairo';"><?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_features_desc', 'نظام معلوماتي مركزي موحد يعتمد على تقنيات السحابة لتسجيل المتربصين، توزيع الفروع، تسيير الغيابات والتقييمات، إدارة الأساتذة والموظفين، وحوكمة القطاع بمرونة وكفاءة.')); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small" style="font-family:'Cairo';">نص زر الميزات</label>
                                    <input type="text" name="landing_features_btn_text" class="form-control" style="font-family:'Cairo';"
                                           value="<?php echo e(\App\Helpers\SovereignLicensingHelper::getSetting('landing_features_btn_text', 'اكتشف كل الميزات')); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary fw-bold py-2 px-5 rounded-3" style="font-family:'Cairo';">
                                <i class="fa-solid fa-floppy-disk me-1"></i> حفظ إعدادات الواجهة والمظهر
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ TAB: إدارة صفحات البوابة (CMS) ═══ -->
            <div class="settings-panel <?= $active_tab==='portal_cms' ? 'active':'' ?>" id="tab-portal_cms">
                <!-- LIST VIEW -->
                <div class="glass-panel p-4 mb-4" id="portal-pages-list-card">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-black m-0" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                            <i class="fa-solid fa-window-restore text-primary me-2"></i> إدارة صفحات البوابة التعريفية (CMS)
                        </h5>
                        <button type="button" class="btn btn-success fw-bold btn-sm rounded-3 px-3 py-1.5" onclick="showAddPageForm()" style="font-family:'Cairo';">
                            <i class="fa-solid fa-plus me-1"></i> إضافة صفحة جديدة
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="font-family:'Cairo'; font-size:0.85rem;">
                            <thead>
                                <tr class="table-light">
                                    <th style="width: 50px;">الترتيب</th>
                                    <th>عنوان الصفحة (العربية)</th>
                                    <th>المعرف (Slug)</th>
                                    <th>الأيقونة</th>
                                    <th class="text-center" style="width: 180px;">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($portal_pages as $page): ?>
                                    <tr>
                                        <td class="fw-bold text-muted text-center"><?= $page->sort_order ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= $page->title ?></div>
                                            <small class="text-muted"><?= $page->title_fr ?></small>
                                        </td>
                                        <td><code class="px-2 py-1 bg-light rounded text-primary small" style="font-family:'Outfit';"><?= $page->slug ?></code></td>
                                        <td>
                                            <span class="badge bg-light text-secondary border px-3 py-1.5">
                                                <i class="fa-solid <?= $page->icon ?> me-1"></i> <?= $page->icon ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="showEditPageForm({
                                                    slug: '<?= addslashes($page->slug) ?>',
                                                    title: '<?= addslashes($page->title) ?>',
                                                    title_fr: '<?= addslashes($page->title_fr) ?>',
                                                    icon: '<?= addslashes($page->icon) ?>',
                                                    sort_order: '<?= $page->sort_order ?>',
                                                    content: document.getElementById('raw-content-<?= $page->slug ?>').value,
                                                })">
                                                    <i class="fa-solid fa-pen-to-square"></i> تعديل
                                                </button>
                                                
                                                <!-- Hidden textarea to store raw HTML safely to avoid escaping issues in JSON parsing -->
                                                <textarea id="raw-content-<?= $page->slug ?>" style="display:none;"><?= htmlspecialchars($page->content) ?></textarea>

                                                <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذه الصفحة نهائياً؟');">
                                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                    <input type="hidden" name="section" value="portal_cms_delete">
                                                    <input type="hidden" name="redirect_tab" value="portal_cms">
                                                    <input type="hidden" name="slug" value="<?= $page->slug ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger fw-bold">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ADD/EDIT FORM VIEW -->
                <div class="glass-panel p-4 mb-4" id="portal-pages-form-card" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-black m-0" id="portal-form-title" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                            <i class="fa-solid fa-pen-to-square text-primary me-2"></i> تعديل صفحة البوابة
                        </h5>
                        <button type="button" class="btn btn-light border btn-sm rounded-3 px-3 py-1.5" onclick="hidePageForm()" style="font-family:'Cairo';">
                            <i class="fa-solid fa-arrow-right me-1"></i> رجوع للقائمة
                        </button>
                    </div>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" id="portal-cms-form">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" id="portal-form-section" value="portal_cms_update">
                        <input type="hidden" name="redirect_tab" value="portal_cms">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">العنوان باللغة العربية (مطلوب)</label>
                                <input type="text" name="title" id="portal-field-title" class="form-control" required style="font-family:'Cairo';">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">العنوان باللغة الفرنسية (Nom en français)</label>
                                <input type="text" name="title_fr" id="portal-field-title-fr" class="form-control" style="font-family:'Outfit';">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">المعرف التعريفي (Slug) (مطلوب، إنجليزي فقط)</label>
                                <input type="text" name="slug" id="portal-field-slug" class="form-control" required placeholder="about-us" style="font-family:'Outfit';">
                                <small class="text-muted" style="font-family:'Cairo';">سيظهر في الرابط: `portal/slug`</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">رمز الأيقونة (FontAwesome Icon)</label>
                                <input type="text" name="icon" id="portal-field-icon" class="form-control" placeholder="fa-info-circle" style="font-family:'Outfit';">
                                <small class="text-muted" style="font-family:'Cairo';">أمثلة: `fa-star`, `fa-server`, `fa-book`</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">الترتيب في القائمة (Sort Order)</label>
                                <input type="number" name="sort_order" id="portal-field-sort-order" class="form-control" value="0" style="font-family:'Outfit';">
                            </div>

                            <div class="col-12 mt-3">
                                <label class="form-label fw-bold small" style="font-family:'Cairo';">محتوى الصفحة بالتنسيق (HTML Content)</label>
                                <textarea name="content" id="portal-field-content" class="form-control" rows="15" style="font-family: monospace, sans-serif; font-size: 0.85rem; direction: ltr; text-align: left;"></textarea>
                                <small class="text-muted d-block mt-1" style="font-family:'Cairo';">يمكنك إدخال نصوص عادية أو أكواد HTML كاملة لتخصيص الصفحة بصورة متكاملة مع التنسيقات (مثل `&lt;p&gt;`, `&lt;div class="portal-premium-box"&gt;`).</small>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-success fw-bold py-2 px-5 rounded-3" style="font-family:'Cairo';">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> حفظ الصفحة
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ TAB: إدارة الميزات والاستعلامات ═══ -->
            <div class="settings-panel <?= $active_tab==='feature_flags' ? 'active':'' ?>" id="tab-feature_flags">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-black mb-1" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-toggle-on me-2 text-primary"></i> إدارة الميزات والاستعلامات / Gestion des Fonctionnalités
                    </h5>
                    <p class="text-muted small mb-4" style="font-family:'Cairo';">
                        تحكم في تفعيل أو تعطيل الميزات الثقيلة التي قد تُبطئ الخادم أو تسبب انقطاعات. عطّل ما تريد وقت الذروة، وأعد تفعيله عند الحاجة.
                    </p>

                    <?php
                    $featureToggles = [
                        [
                            'key'     => 'feature_print_actions_enabled',
                            'section' => 'feature_print_toggle',
                            'label'   => 'أزرار الطباعة والتنزيل (Print / PDF / Excel)',
                            'desc'    => 'السماح للمستخدمين بطباعة وتنزيل السجلات (المتربصون، الموظفون، البطاقات الرقمية). تعطيلها يخفي الأزرار كلياً.',
                            'value'   => $feature_print_actions_enabled,
                            'icon'    => 'fa-print',
                            'color'   => '#007bff',
                        ],
                        [
                            'key'     => 'feature_complex_stats_enabled',
                            'section' => 'feature_stats_toggle',
                            'label'   => 'الإحصائيات والمخططات المتقدمة (Statistics & Charts)',
                            'desc'    => 'السماح بتحميل الإحصائيات الثقيلة في لوحة التحكم الرئيسية والتقارير. تعطيلها يُقلص حمل قاعدة البيانات بشكل كبير.',
                            'value'   => $feature_complex_stats_enabled,
                            'icon'    => 'fa-chart-bar',
                            'color'   => '#6f42c1',
                        ],
                        [
                            'key'     => 'feature_photo_sorting_enabled',
                            'section' => 'feature_photo_sort_toggle',
                            'label'   => 'الفرز الذكي حسب وجود الصور (Smart Photo Sorting)',
                            'desc'    => 'ترتيب الممتلكات والمتربصين بحيث تظهر السجلات التي تحتوي على صور أولاً. تعطيلها يسرّع استعلامات قاعدة البيانات.',
                            'value'   => $feature_photo_sorting_enabled,
                            'icon'    => 'fa-images',
                            'color'   => '#fd7e14',
                        ],
                        [
                            'key'     => 'feature_background_sync_enabled',
                            'section' => 'feature_sync_toggle',
                            'label'   => 'المزامنة الخلفية مع خادم HFSQL (Background Sync)',
                            'desc'    => 'السماح لأوامر المزامنة artisan hfsql:sync بالعمل. تعطيلها يوقف أي مهمة مزامنة مجدولة في الخلفية فوراً عند استدعائها.',
                            'value'   => $feature_background_sync_enabled,
                            'icon'    => 'fa-rotate',
                            'color'   => '#20c997',
                        ],
                        [
                            'key'     => 'feature_large_memos_query_enabled',
                            'section' => 'feature_memos_toggle',
                            'label'   => 'استعلامات الصور والوثائق الضخمة (Large Memo Queries)',
                            'desc'    => 'تحميل صور وملفات المذكرات الضخمة للمتربصين والموظفين (candidat_memo, encadremen_memo). تعطيلها يمنع الاستعلامات الثقيلة على هذه الجداول.',
                            'value'   => $feature_large_memos_query_enabled,
                            'icon'    => 'fa-database',
                            'color'   => '#dc3545',
                        ],
                    ];
                    ?>

                    <?php foreach ($featureToggles as $feat): ?>
                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>" class="p-3 mb-3 rounded-3 text-right" style="background:var(--bg-surface-elevated); border:1.5px solid rgba(26,107,204,.12);">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="<?= $feat['section'] ?>">
                        <input type="hidden" name="redirect_tab" value="feature_flags">

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1" style="font-family:'Cairo'; display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid <?= $feat['icon'] ?>" style="color: <?= $feat['color'] ?>; width:1.2rem; text-align:center;"></i>
                                    <?= $feat['label'] ?>
                                </h6>
                                <p class="text-muted small mb-0" style="font-family:'Cairo'; padding-right:1.8rem;"><?= $feat['desc'] ?></p>
                            </div>
                            <div class="form-check form-switch ms-3 flex-shrink-0">
                                <input class="form-check-input" type="checkbox" name="<?= $feat['key'] ?>" value="1"
                                       <?= $feat['value'] ? 'checked' : '' ?> style="width:3rem;height:1.5rem;cursor:pointer;" onchange="this.form.submit()">
                            </div>
                        </div>

                        <?php if (!$feat['value']): ?>
                        <div class="mt-2 d-flex align-items-center gap-2 p-2 rounded-2" style="background:rgba(220,53,69,.07); border-right:3px solid #dc3545;">
                            <i class="fa-solid fa-circle-xmark text-danger"></i>
                            <span class="text-danger small fw-bold" style="font-family:'Cairo';">هذه الميزة معطّلة حالياً — Désactivée</span>
                        </div>
                        <?php else: ?>
                        <div class="mt-2 d-flex align-items-center gap-2 p-2 rounded-2" style="background:rgba(25,135,84,.07); border-right:3px solid #198754;">
                            <i class="fa-solid fa-circle-check text-success"></i>
                            <span class="text-success small fw-bold" style="font-family:'Cairo';">هذه الميزة مفعّلة وتعمل — Active</span>
                        </div>
                        <?php endif; ?>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══ TAB: صلاحيات لوحة الالتحاق ═══ -->
            <div class="settings-panel <?= $active_tab==='enrollment_permissions' ? 'active':'' ?>" id="tab-enrollment_permissions">
                <div class="glass-panel p-4">
                    <h5 class="fw-black mb-3" style="font-family:'Cairo';border-right:3px solid var(--electric);padding-right:.6rem;">
                        <i class="fa-solid fa-user-lock text-primary me-2"></i> صلاحيات لوحة الالتحاق والتسجيل
                    </h5>
                    <p class="text-muted small mb-4" style="font-family:'Cairo';">
                        تحكم في تفعيل أو تعطيل عمليات الإدخال (إضافة)، التعديل، الحذف، والتصدير/الطباعة لملفات المترشحين والمتربصين على مستوى المنصة ككل، أو مخصصة بالتفصيل لكل ولاية.
                    </p>

                    <?php
                    $enrollAdd = \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_add_enabled', '1') === '1';
                    $enrollEdit = \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_edit_enabled', '1') === '1';
                    $enrollDelete = \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_delete_enabled', '1') === '1';
                    $enrollExport = \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_export_enabled', '1') === '1';

                    $disabledAdd = array_filter(array_map('trim', explode(',', \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_restricted_wilayas_add', ''))));
                    $disabledEdit = array_filter(array_map('trim', explode(',', \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_restricted_wilayas_edit', ''))));
                    $disabledDelete = array_filter(array_map('trim', explode(',', \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_restricted_wilayas_delete', ''))));
                    $disabledExport = array_filter(array_map('trim', explode(',', \App\Helpers\SovereignLicensingHelper::getSetting('enrollment_restricted_wilayas_export', ''))));
                    ?>

                    <form method="POST" action="<?php echo e(url('dashboard/settings/update')); ?>">
                        <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="section" value="enrollment_permissions">
                        <input type="hidden" name="redirect_tab" value="enrollment_permissions">

                        <!-- Global Toggles -->
                        <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark" style="font-family:'Cairo';">
                            <i class="fa-solid fa-globe text-muted me-1"></i> الصلاحيات العامة للمنصة (Global Permissions)
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light border text-right">
                                    <label class="form-check-label fw-bold d-block mb-2" style="font-family:'Cairo';">إدخال/إضافة جديد</label>
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="enrollment_add_enabled" value="1" <?= $enrollAdd ? 'checked' : '' ?> style="width:2.5rem;height:1.25rem;cursor:pointer;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light border text-right">
                                    <label class="form-check-label fw-bold d-block mb-2" style="font-family:'Cairo';">تعديل الملفات</label>
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="enrollment_edit_enabled" value="1" <?= $enrollEdit ? 'checked' : '' ?> style="width:2.5rem;height:1.25rem;cursor:pointer;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light border text-right">
                                    <label class="form-check-label fw-bold d-block mb-2" style="font-family:'Cairo';">حذف الملفات</label>
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="enrollment_delete_enabled" value="1" <?= $enrollDelete ? 'checked' : '' ?> style="width:2.5rem;height:1.25rem;cursor:pointer;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light border text-right">
                                    <label class="form-check-label fw-bold d-block mb-2" style="font-family:'Cairo';">تصدير وطباعة</label>
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="enrollment_export_enabled" value="1" <?= $enrollExport ? 'checked' : '' ?> style="width:2.5rem;height:1.25rem;cursor:pointer;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Per-Wilaya Permissions Grid -->
                        <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark" style="font-family:'Cairo';">
                            <i class="fa-solid fa-map-location-dot text-muted me-1"></i> تخصيص الصلاحيات حسب الولاية (Per-Wilaya Controls)
                        </h6>
                        <div class="table-responsive border rounded-3" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0 text-right" dir="rtl" style="font-size:0.83rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="py-2.5 px-3">الولاية</th>
                                        <th class="text-center py-2.5">إضافة جديد</th>
                                        <th class="text-center py-2.5">تعديل بيانات</th>
                                        <th class="text-center py-2.5">حذف سجل</th>
                                        <th class="text-center py-2.5">تصدير/طباعة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wilayas as $w):
                                        if (empty($w->IDWilayaa)) continue;
                                        $addOk = !in_array($w->IDWilayaa, $disabledAdd);
                                        $editOk = !in_array($w->IDWilayaa, $disabledEdit);
                                        $deleteOk = !in_array($w->IDWilayaa, $disabledDelete);
                                        $exportOk = !in_array($w->IDWilayaa, $disabledExport);
                                    ?>
                                    <tr style="border-bottom:1px solid #f1f5f9;">
                                        <td class="fw-bold text-dark py-2 px-3"><?php echo e($w->Nom); ?></td>
                                        <td class="text-center py-2">
                                            <input class="form-check-input" type="checkbox" name="wilaya_<?php echo e($w->IDWilayaa); ?>_add" value="1" <?= $addOk ? 'checked' : '' ?> style="cursor:pointer; width:1.1rem; height:1.1rem;">
                                        </td>
                                        <td class="text-center py-2">
                                            <input class="form-check-input" type="checkbox" name="wilaya_<?php echo e($w->IDWilayaa); ?>_edit" value="1" <?= $editOk ? 'checked' : '' ?> style="cursor:pointer; width:1.1rem; height:1.1rem;">
                                        </td>
                                        <td class="text-center py-2">
                                            <input class="form-check-input" type="checkbox" name="wilaya_<?php echo e($w->IDWilayaa); ?>_delete" value="1" <?= $deleteOk ? 'checked' : '' ?> style="cursor:pointer; width:1.1rem; height:1.1rem;">
                                        </td>
                                        <td class="text-center py-2">
                                            <input class="form-check-input" type="checkbox" name="wilaya_<?php echo e($w->IDWilayaa); ?>_export" value="1" <?= $exportOk ? 'checked' : '' ?> style="cursor:pointer; width:1.1rem; height:1.1rem;">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-start">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" style="font-family:'Cairo';">
                                <i class="fa-solid fa-circle-check me-1"></i> حفظ وتحديث الصلاحيات
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function sendTestPush() {
    if (!confirm('هل تريد إرسال إشعار تجريبي فوري إلى متصفحك الحالي؟')) return;
    
    // Simple simulation of a push notification send
    alert('جاري إرسال الإشعار التجريبي عبر نظام البث VAPID... سيتم تسجيل الحدث في نظام المراقبة في الخلفية.');
}

function switchTab(tabId, btn) {
    // إخفاء جميع الـ panels
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-nav-item').forEach(b => b.classList.remove('active'));

    // إظهار الـ panel المطلوب
    const panel = document.getElementById('tab-' + tabId);
    if (panel) panel.classList.add('active');
    if (btn) btn.classList.add('active');

    // تحديث الـ URL دون reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    // Keep doc filters if switching to documents tab
    if (tabId !== 'documents') {
        url.searchParams.delete('doc_table');
        url.searchParams.delete('wilaya_id');
        url.searchParams.delete('etab_id');
    }
    window.history.pushState({}, '', url);
}

function showAddPageForm() {
    document.getElementById('portal-form-title').innerHTML = '<i class="fa-solid fa-plus text-success me-2"></i> إضافة صفحة جديدة للبوابة';
    document.getElementById('portal-form-section').value = 'portal_cms_add';
    document.getElementById('portal-field-slug').value = '';
    document.getElementById('portal-field-slug').readOnly = false;
    document.getElementById('portal-field-title').value = '';
    document.getElementById('portal-field-title-fr').value = '';
    document.getElementById('portal-field-icon').value = 'fa-file-lines';
    document.getElementById('portal-field-sort-order').value = '0';
    document.getElementById('portal-field-content').value = '';

    document.getElementById('portal-pages-list-card').style.display = 'none';
    document.getElementById('portal-pages-form-card').style.display = 'block';
}

function showEditPageForm(page) {
    document.getElementById('portal-form-title').innerHTML = '<i class="fa-solid fa-pen-to-square text-primary me-2"></i> تعديل محتوى صفحة البوابة: ' + page.title;
    document.getElementById('portal-form-section').value = 'portal_cms_update';
    document.getElementById('portal-field-slug').value = page.slug;
    document.getElementById('portal-field-slug').readOnly = true;
    document.getElementById('portal-field-title').value = page.title;
    document.getElementById('portal-field-title-fr').value = page.title_fr || '';
    document.getElementById('portal-field-icon').value = page.icon || 'fa-file-lines';
    document.getElementById('portal-field-sort-order').value = page.sort_order || '0';
    document.getElementById('portal-field-content').value = page.content;

    document.getElementById('portal-pages-list-card').style.display = 'none';
    document.getElementById('portal-pages-form-card').style.display = 'block';
}

function hidePageForm() {
    document.getElementById('portal-pages-form-card').style.display = 'none';
    document.getElementById('portal-pages-list-card').style.display = 'block';
}

/**
 * Filters the institution (etablissement) dropdown based on the selected Wilaya.
 * Options with a data-wilaya attribute not matching the selection are hidden.
 */
function filterEtabs() {
    const wilayaSelect = document.getElementById('docWilayaSelect');
    const etabSelect = document.getElementById('docEtabSelect');
    if (!wilayaSelect || !etabSelect) return;

    const selectedWilaya = wilayaSelect.value;
    const options = etabSelect.querySelectorAll('option');
    let firstVisible = '';

    options.forEach(opt => {
        if (!opt.value) {
            // Keep the "all establishments" option always visible
            opt.style.display = '';
            return;
        }
        const optWilaya = opt.getAttribute('data-wilaya');
        if (!selectedWilaya || optWilaya === selectedWilaya) {
            opt.style.display = '';
            if (!firstVisible) firstVisible = opt.value;
        } else {
            opt.style.display = 'none';
        }
    });

    // If the currently selected etab no longer belongs to the selected wilaya, reset it
    const currentEtab = etabSelect.value;
    if (currentEtab) {
        const currentOpt = etabSelect.querySelector(`option[value="${currentEtab}"]`);
        if (currentOpt && currentOpt.style.display === 'none') {
            etabSelect.value = '';
        }
    }
}

// On page load, apply the filter if a wilaya is already selected (server-rendered)
document.addEventListener('DOMContentLoaded', () => {
    filterEtabs();

    // Sovereign target dynamic filtering
    const genWilaya = document.getElementById('genWilayaSelect');
    const genEtab = document.getElementById('genEtabSelect');
    const genUser = document.getElementById('genUserSelect');

    if (genWilaya && genEtab && genUser) {
        async function updateSovereignTargets() {
            const wilayaId = genWilaya.value;
            const etabId = genEtab.value;
            
            try {
                genUser.innerHTML = '<option value="">⏳ جاري التحميل... / Chargement...</option>';
                const response = await fetch('<?php echo e(url("dashboard/settings/sovereign/search-targets")); ?>?wilaya_id=' + wilayaId + '&etab_id=' + etabId);
                if (!response.ok) throw new Error('Failed to fetch targets');
                
                const data = await response.json();
                
                // Keep selected etab if it is still valid
                const currentEtsVal = genEtab.value;
                let etabHtml = '<option value="">— مفتاح عام (غير مرتبط) —</option>';
                data.establishments.forEach(etab => {
                    etabHtml += `<option value="${etab.id}" ${etab.id == currentEtsVal ? 'selected' : ''}>${etab.name}</option>`;
                });
                genEtab.innerHTML = etabHtml;
                
                let userHtml = '<option value="">— مفتاح عام (غير مرتبط) —</option>';
                data.users.forEach(user => {
                    userHtml += `<option value="${user.id}">${user.display_name}</option>`;
                });
                genUser.innerHTML = userHtml;
            } catch (err) {
                console.error('Error fetching sovereign targets:', err);
                genUser.innerHTML = '<option value="">⚠️ فشل تحميل المستخدمين</option>';
            }
        }

        genWilaya.addEventListener('change', () => {
            genEtab.value = '';
            updateSovereignTargets();
        });
        genEtab.addEventListener('change', updateSovereignTargets);
        
        // Populate target users initially
        updateSovereignTargets();
    }
});

// تزامن ألوان الـ color pickers مع الـ text inputs
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = colorInput.nextElementSibling;
    if (textInput) {
        colorInput.addEventListener('input', () => { textInput.value = colorInput.value; });
    }
});

// -----------------------------------------------------------------------------
// API Center JS CRUD Operations
// -----------------------------------------------------------------------------
function copyApiKey() {
    const input = document.getElementById('user-apiKey');
    if (!input || !input.value || input.value.includes('لم يتم')) return;
    navigator.clipboard.writeText(input.value).then(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ مفتاح API للحافظة!', showConfirmButton: false, timer: 2000, timerProgressBar: true });
        }
    });
}

function regenerateUserApiKey(btn) {
    if (!btn) return;
    const userId = btn.getAttribute('data-user-id');
    const csrfToken = btn.getAttribute('data-csrf-token');

    if (!confirm('هل أنت متأكد من رغبتك في تجديد مفتاح API الخاص بك؟ سيتعطل المفتاح القديم فوراً.')) return;

    fetch('/sig/dashboard/users/generate-api-key', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${userId}&_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('user-apiKey').value = data.api_key;
            Swal.fire({ icon: 'success', title: 'تم تجديد المفتاح!', text: 'تم توليد مفتاح API جديد بنجاح.', confirmButtonColor: '#006233' });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: data.message || 'فشل توليد المفتاح.', confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

function createApiClient(e) {
    e.preventDefault();
    const form = document.getElementById('createClientForm');
    const formData = new FormData(form);

    fetch('/sig/dashboard/api-center/store', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            const modalEl = document.getElementById('createClientModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            form.reset();

            // Display Plain API Key once securely in SweetAlert
            Swal.fire({
                icon: 'success',
                title: 'تم إنشاء المفتاح بنجاح!',
                html: `
                    <p class="text-danger small fw-bold">انسخ هذا المفتاح الآن! لن تتمكن من رؤيته مجدداً لأسباب أمنية:</p>
                    <div class="input-group my-3">
                        <input type="text" id="plain-api-key" class="form-control text-center fw-bold text-success border-primary bg-light" readonly value="${data.plain_key}" style="font-family:'Outfit';">
                        <button class="btn btn-primary" onclick="navigator.clipboard.writeText('${data.plain_key}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'تم نسخ المفتاح!',showConfirmButton:false,timer:1500});">
                            <i class="fa-regular fa-copy"></i> نسخ
                        </button>
                    </div>
                `,
                confirmButtonText: 'إغلاق ومتابعة',
                confirmButtonColor: '#006233',
                allowOutsideClick: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ في الحفظ', text: data.message, confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

const apiDocs = {
    stagiaires: {
        title_ar: "جدول المتربصين (Apprenant / Stagiaires)",
        title_en: "Trainees Endpoint",
        method: "GET",
        path: "/api/v1/stagiaires",
        description: "يجلب قائمة المتربصين مع إمكانية التصفية والبحث الفوري.",
        params: [
            { name: "valide", type: "integer", desc: "1 للنشطين، 0 لغير النشطين (Statut: active/inactive)" },
            { name: "etablissement_id", type: "integer", desc: "معرف المؤسسة التكوينية (Ets ID)" },
            { name: "specialite_id", type: "integer", desc: "معرف التخصص (Speciality ID)" },
            { name: "sexe", type: "string (M/F)", desc: "الجنس (M للذكور، F للإناث)" },
            { name: "q", type: "string", desc: "البحث بالاسم واللقب ورقم التسجيل" }
        ],
        body: null
    },
    offres: {
        title_ar: "جدول عروض التكوين (Offre / Offres)",
        title_en: "Training Offers Endpoint",
        method: "GET",
        path: "/api/v1/offres",
        description: "يجلب قائمة عروض التكوين المفتوحة والنشطة.",
        params: [
            { name: "etablissement_id", type: "integer", desc: "تصفية حسب معرف المؤسسة" },
            { name: "specialite_id", type: "integer", desc: "تصفية حسب معرف التخصص" },
            { name: "session_id", type: "integer", desc: "تصفية حسب معرف دورة التكوين" },
            { name: "valide_centrale", type: "integer (0/1)", desc: "1 لجلب العروض المعتمدة مركزياً فقط" }
        ],
        body: null
    },
    employees: {
        title_ar: "جدول الموظفين والمؤطرين (Encadrement / Employees)",
        title_en: "Employees / Staff Endpoint",
        method: "GET",
        path: "/api/v1/employees",
        description: "يجلب قائمة الموظفين، المؤطرين، الإداريين، والأساتذة.",
        params: [
            { name: "etablissement_id", type: "integer", desc: "تصفية حسب معرف المؤسسة" },
            { name: "grade", type: "string", desc: "تصفية حسب الرتبة" },
            { name: "fonction", type: "string", desc: "تصفية حسب الوظيفة" },
            { name: "q", type: "string", desc: "البحث بالاسم، اللقب، البريد الإلكتروني، أو NIN" }
        ],
        body: null
    },
    formateurs: {
        title_ar: "ساعات التكوين الشاغرة (Formateurs / Vacant Hours)",
        title_en: "Vacant Hours Endpoint",
        method: "GET",
        path: "/api/v1/hr/formateurs/vacant-hours",
        description: "حساب ساعات التكوين الشاغرة لجميع المؤطرين والأساتذة لجدولة الحصص.",
        params: [],
        body: null
    },
    finance: {
        title_ar: "التقارير المالية والميزانية (Finance / Budget Reports)",
        title_en: "Finance & Budget Endpoint",
        method: "GET",
        path: "/api/v1/finance/reports/budget",
        description: "تحميل التقارير المالية للمؤسسة مشفرة بخوارزمية AES-256-CBC لحماية البيانات الحساسة.",
        params: [],
        body: null
    },
    assets: {
        title_ar: "طلبات العتاد والورشات (Assets / Equipment Requests)",
        title_en: "Assets Request Endpoint",
        method: "POST",
        path: "/api/v1/assets/requests",
        description: "تقديم طلب عتاد تقني جديد أو تجهيز ورشات لفائدة المؤسسة التكوينية.",
        params: [],
        body: [
            { name: "designation", type: "string", required: true, desc: "تسمية العتاد المطلوب (مثال: جهاز عرض رقمي)" },
            { name: "specialite_id", type: "integer", required: true, desc: "معرف التخصص المرتبط بالعتاد" },
            { name: "etablissement_id", type: "integer", required: true, desc: "معرف المؤسسة المستفيدة (مطلوب لطلبات API)" },
            { name: "description", type: "string", required: false, desc: "تفاصيل ووصف إضافي للطلب" }
        ]
    }
};

function generateApiDocHtml(key, apiKey = 'YOUR_API_KEY') {
    const doc = apiDocs[key];
    if (!doc) return '';

    const baseHost = "<?php echo e(request()->getSchemeAndHttpHost()); ?>";
    const fullUrl = `${baseHost}${doc.path}`;
    
    let curlCmd = '';
    if (doc.method === 'GET') {
        const queryParams = doc.params.map(p => `${p.name}=value`).join('&');
        const urlWithQuery = queryParams ? `${fullUrl}?${queryParams}` : fullUrl;
        curlCmd = `curl -X GET "${urlWithQuery}" \\\n  -H "X-API-Key: ${apiKey}"`;
    } else {
        const bodyObj = {};
        if (doc.body) {
            doc.body.forEach(b => {
                bodyObj[b.name] = b.type === 'integer' ? 123 : "text";
            });
        }
        curlCmd = `curl -X POST "${fullUrl}" \\\n  -H "X-API-Key: ${apiKey}" \\\n  -H "Content-Type: application/json" \\\n  -d '${JSON.stringify(bodyObj, null, 2)}'`;
    }

    let paramsHtml = '';
    if (doc.params && doc.params.length > 0) {
        paramsHtml = `
            <div class="mt-2 text-warning small fw-bold" style="text-align:left; direction:ltr;">Query Parameters:</div>
            <ul class="list-unstyled ps-2 mb-2 text-white-50 text-start" style="font-size: 0.78rem; text-align:left; direction:ltr;">
                ${doc.params.map(p => `<li><code>${p.name}</code> (${p.type}): ${p.desc}</li>`).join('')}
            </ul>
        `;
    }

    let bodyHtml = '';
    if (doc.body && doc.body.length > 0) {
        bodyHtml = `
            <div class="mt-2 text-warning small fw-bold" style="text-align:left; direction:ltr;">Request Body (JSON):</div>
            <ul class="list-unstyled ps-2 mb-2 text-white-50 text-start" style="font-size: 0.78rem; text-align:left; direction:ltr;">
                ${doc.body.map(b => `<li><code>${b.name}</code> (${b.type})${b.required ? ' <span class="text-danger">*</span>' : ''}: ${b.desc}</li>`).join('')}
            </ul>
        `;
    }

    const docId = `doc-block-${key}-${Math.floor(Math.random() * 100000)}`;
    return `
        <div class="api-doc-item mb-3 p-3 rounded-3 border border-secondary" style="background: #111827; border-color: #374151; color: #fff;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge ${doc.method === 'GET' ? 'bg-success' : 'bg-primary'} fw-bold" style="font-size: 0.75rem;">${doc.method}</span>
                <span class="text-light fw-bold small" style="font-family: 'Cairo';">${doc.title_ar}</span>
            </div>
            <div class="text-white-50 small mb-2" style="font-family: 'Cairo';">${doc.description}</div>
            
            <div class="input-group input-group-sm mb-2" style="direction: ltr;">
                <span class="input-group-text bg-dark border-secondary text-light small" style="font-size:0.7rem;">Endpoint</span>
                <input type="text" class="form-control bg-dark border-secondary text-success fw-bold text-truncate" style="font-size:0.75rem; font-family:'Outfit';" readonly value="${doc.path}">
            </div>

            ${paramsHtml}
            ${bodyHtml}

            <div class="position-relative" style="direction: ltr;">
                <pre class="bg-black text-info p-2 rounded-2 mb-0 overflow-auto" id="${docId}" style="font-size:0.72rem; font-family:'Outfit'; max-height:120px; text-align:left; direction:ltr;">${curlCmd}</pre>
                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-1" type="button" style="font-size:0.6rem; padding:2px 6px;" onclick="copyCodeText('${docId}')">
                    <i class="fa-regular fa-copy"></i> نسخ
                </button>
            </div>
        </div>
    `;
}

window.copyCodeText = function(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ أمر cURL!', showConfirmButton: false, timer: 1500 });
    });
}

function handleEndpointCheckboxChange(containerId, listClass) {
    const container = document.getElementById(containerId + '-container');
    const displayDiv = document.getElementById(containerId);
    if (!container || !displayDiv) return;
    
    // Get all checked checkboxes
    const checked = Array.from(document.querySelectorAll('.' + listClass))
        .filter(chk => chk.checked)
        .map(chk => chk.value);
        
    if (checked.length === 0) {
        container.style.display = 'none';
        displayDiv.innerHTML = '';
        return;
    }
    
    let html = '';
    checked.forEach(key => {
        html += generateApiDocHtml(key);
    });
    
    displayDiv.innerHTML = html;
    container.style.display = 'block';
}

function showClientDocsModal(name, endpoints) {
    document.getElementById('viewDocsClientName').innerText = name;
    
    const container = document.getElementById('client-docs-content');
    if (!container) return;
    if (!Array.isArray(endpoints) || endpoints.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fa-solid fa-triangle-exclamation fs-3 mb-2 text-warning d-block"></i>
                لم يتم منح أي صلاحيات وصول لهذه المنصة بعد.
            </div>
        `;
    } else {
        let html = '';
        endpoints.forEach(ep => {
            html += generateApiDocHtml(ep, 'YOUR_API_KEY');
        });
        container.innerHTML = html;
    }
    
    const modalEl = document.getElementById('viewClientDocsModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function showAdminDocSnippet() {
    const select = document.getElementById('admin-doc-select');
    const container = document.getElementById('admin-doc-snippet-container');
    const display = document.getElementById('admin-doc-snippet');
    const adminKey = document.getElementById('user-apiKey').value;
    
    if (!select || !select.value || adminKey.includes('لم يتم')) {
        if (container) container.style.display = 'none';
        if (display) display.innerHTML = '';
        return;
    }
    
    const key = select.value;
    display.innerHTML = generateApiDocHtml(key, adminKey);
    container.style.display = 'block';
}

function showEditClientModal(id, name, ips, endpoints) {
    document.getElementById('edit_client_id').value = id;
    document.getElementById('edit_client_name').value = name;
    document.getElementById('edit_allowed_ips').value = (ips === 'null' || !ips) ? '' : ips;
    
    // Reset checkboxes
    document.querySelectorAll('.edit-endpoint-chk').forEach(chk => {
        chk.checked = false;
    });
    
    // Select correct checkboxes
    if (Array.isArray(endpoints)) {
        endpoints.forEach(ep => {
            const chk = document.getElementById('edit_chk_' + ep);
            if (chk) chk.checked = true;
        });
    }
    
    // Trigger instructions update
    handleEndpointCheckboxChange('edit-api-instructions', 'edit-endpoint-chk');
    
    const modalEl = document.getElementById('editClientModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function updateApiClient(e) {
    e.preventDefault();
    const id = document.getElementById('edit_client_id').value;
    const form = document.getElementById('editClientForm');
    const formData = new FormData(form);

    fetch(`/sig/dashboard/api-center/update/${id}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('editClientModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            Swal.fire({ icon: 'success', title: 'تم التحديث!', text: data.message, confirmButtonColor: '#006233' }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: data.message, confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

function toggleClientStatus(id, token) {
    fetch(`/sig/dashboard/api-center/toggle/${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `_token=${token}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000, timerProgressBar: true });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: data.message, confirmButtonColor: '#006233' });
            // Revert switch status
            const sw = document.getElementById(`switch-${id}`);
            sw.checked = !sw.checked;
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' });
        const sw = document.getElementById(`switch-${id}`);
        sw.checked = !sw.checked;
    });
}

function deleteClient(id, token) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيؤدي هذا إلى حذف منصة الربط وإلغاء صلاحية مفتاح الـ API المرتبط بها فوراً وكلياً!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف المفتاح!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/sig/dashboard/api-center/delete/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `_token=${token}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'تم الحذف!', text: data.message, confirmButtonColor: '#006233' }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'خطأ', text: data.message, confirmButtonColor: '#006233' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Add change listeners for Create checkboxes
    document.querySelectorAll('.create-endpoint-chk').forEach(chk => {
        chk.addEventListener('change', () => {
            handleEndpointCheckboxChange('create-api-instructions', 'create-endpoint-chk');
        });
    });
    
    // Add change listeners for Edit checkboxes
    document.querySelectorAll('.edit-endpoint-chk').forEach(chk => {
        chk.addEventListener('change', () => {
            handleEndpointCheckboxChange('edit-api-instructions', 'edit-endpoint-chk');
        });
    });
    
    // Trigger initial state for Create modal
    handleEndpointCheckboxChange('create-api-instructions', 'create-endpoint-chk');
});
</script>

<!-- Create Client Modal -->
<div class="modal fade" id="createClientModal" tabindex="-1" aria-labelledby="createClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; background: white;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="createClientModalLabel" style="font-family: 'Cairo';">
                    <i class="fa-solid fa-plus text-primary me-2"></i> إضافة منصة ربط خارجية
                </h5>
                <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createClientForm" onsubmit="createApiClient(event)">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="client_name" class="form-label fw-bold small">اسم الجهة أو المنصة الشريكة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="client_name" name="client_name" required placeholder="مثال: وزارة التكوين المهني، شريك اقتصادي X">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">صلاحيات الوصول الممنوحة (الجداول المسموحة) <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column gap-2 p-2 rounded-3 bg-light border border-light-subtle">
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="stagiaires" id="chk_stagiaires" checked>
                                <label class="form-check-label fw-bold text-dark small" for="chk_stagiaires">جدول المتربصين (Apprenant / Stagiaires)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="offres" id="chk_offres" checked>
                                <label class="form-check-label fw-bold text-dark small" for="chk_offres">جدول عروض التكوين (Offre / Offres)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="employees" id="chk_employees" checked>
                                <label class="form-check-label fw-bold text-dark small" for="chk_employees">جدول الموظفين والمؤطرين (Encadrement / Employees)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="formateurs" id="chk_formateurs">
                                <label class="form-check-label fw-bold text-dark small" for="chk_formateurs">ساعات التكوين الشاغرة (Formateurs / Vacant Hours)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="finance" id="chk_finance">
                                <label class="form-check-label fw-bold text-dark small" for="chk_finance">التقارير المالية والميزانية (Finance / Budget Reports)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="assets" id="chk_assets">
                                <label class="form-check-label fw-bold text-dark small" for="chk_assets">طلبات العتاد والورشات (Assets / Equipment Requests)</label>
                            </div>
                        </div>
                        
                        <!-- Container for dynamic instructions -->
                        <div id="create-api-instructions-container" class="mt-3" style="display:none;">
                            <h6 class="fw-bold text-dark mb-2" style="font-family: 'Cairo'; font-size: 0.85rem;">
                                <i class="fa-solid fa-code text-primary me-1"></i> دليل استخدام الـ API للجداول المحددة:
                            </h6>
                            <div id="create-api-instructions" class="p-2 rounded-3 bg-dark text-light border border-secondary" style="max-height: 250px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="allowed_ips" class="form-label fw-bold small">عناوين IP المسموح بها (اختياري)</label>
                        <input type="text" class="form-control rounded-3" id="allowed_ips" name="allowed_ips" placeholder="مثال: 197.112.10.25, 8.8.8.8">
                        <div class="form-text text-muted" style="font-size: 0.75rem;">افصل بين العناوين بفاصلة. اتركه فارغاً للسماح بالوصول من أي عنوان.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">توليد المفتاح</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; background: white;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editClientModalLabel" style="font-family: 'Cairo';">
                    <i class="fa-solid fa-pen text-primary me-2"></i> تعديل بيانات منصة الربط
                </h5>
                <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editClientForm" onsubmit="updateApiClient(event)">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="edit_client_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_client_name" class="form-label fw-bold small">اسم الجهة أو المنصة الشريكة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="edit_client_name" name="client_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">صلاحيات الوصول الممنوحة (الجداول المسموحة) <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column gap-2 p-2 rounded-3 bg-light border border-light-subtle">
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="stagiaires" id="edit_chk_stagiaires">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_stagiaires">جدول المتربصين (Apprenant / Stagiaires)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="offres" id="edit_chk_offres">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_offres">جدول عروض التكوين (Offre / Offres)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="employees" id="edit_chk_employees">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_employees">جدول الموظفين والمؤطرين (Encadrement / Employees)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="formateurs" id="edit_chk_formateurs">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_formateurs">ساعات التكوين الشاغرة (Formateurs / Vacant Hours)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="finance" id="edit_chk_finance">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_finance">التقارير المالية والميزانية (Finance / Budget Reports)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="assets" id="edit_chk_assets">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_assets">طلبات العتاد والورشات (Assets / Equipment Requests)</label>
                            </div>
                        </div>
                        
                        <!-- Container for dynamic instructions -->
                        <div id="edit-api-instructions-container" class="mt-3" style="display:none;">
                            <h6 class="fw-bold text-dark mb-2" style="font-family: 'Cairo'; font-size: 0.85rem;">
                                <i class="fa-solid fa-code text-primary me-1"></i> دليل استخدام الـ API للجداول المحددة:
                            </h6>
                            <div id="edit-api-instructions" class="p-2 rounded-3 bg-dark text-light border border-secondary" style="max-height: 250px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_allowed_ips" class="form-label fw-bold small">عناوين IP المسموح بها (اختياري)</label>
                        <input type="text" class="form-control rounded-3" id="edit_allowed_ips" name="allowed_ips">
                        <div class="form-text text-muted" style="font-size: 0.75rem;">افصل بين العناوين بفاصلة. اتركه فارغاً للسماح بالوصول من أي عنوان.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Client Docs Modal -->
<div class="modal fade" id="viewClientDocsModal" tabindex="-1" aria-labelledby="viewClientDocsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; background: white;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="viewClientDocsModalLabel" style="font-family: 'Cairo';">
                    <i class="fa-solid fa-book text-success me-2"></i> دليل الربط والتعليمات للمنصة الشريكة: <span id="viewDocsClientName" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3 animate__animated animate__fadeIn" style="font-family:'Cairo';">
                    <i class="fa-solid fa-circle-info me-1"></i> استخدم رأس الطلب HTTP التالي للمصادقة: <code>X-API-Key: YOUR_API_KEY</code>
                </div>
                <div id="client-docs-content" style="max-height: 450px; overflow-y: auto; padding-right: 5px;">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sig\resources\views/admin/settings/index.blade.php ENDPATH**/ ?>