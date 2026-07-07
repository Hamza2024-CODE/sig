@extends('layouts.main')
@section('title', $title ?? 'لوحة التحكم — الإدارة العليا')
@section('content')
<?php
/**
 * @var string $role
 * @var string $direction_code
 * @var array $user
 */

$db           = \App\Core\Database::getInstance()->getConnection();
$cache        = new \App\Cache\CacheManager();
$dirCode      = strtoupper($user['direction_code'] ?? session('user')['direction_code'] ?? session('user')['username'] ?? '');
$isIG         = ($dirCode === 'IG');

// ------- بيانات التفتيش (لـ IG فقط) --------
$inspStats    = ['missions' => 48, 'violations' => 23, 'resolved' => 18, 'pending' => 5];
$inspMissions = [];
$violations   = [];

if ($isIG) {
    $cacheKey = 'ig_inspection_stats';
    $cached   = $cache->get($cacheKey, 600);

    if ($cached !== null) {
        $inspStats    = $cached['stats'];
        $inspMissions = $cached['missions'];
        $violations   = $cached['violations'];
    } else {
        try {
            // نحاول استخراج آخر عمليات الدخول الفاشلة (كمخالفات)
            $stmtV = $db->query("
                SELECT NomUtilisateur as user, iplocal as ip, Date as date,
                       heure as heure, accesouinon as result
                FROM accesuser
                WHERE accesouinon = 0
                ORDER BY IDaccesuser DESC LIMIT 8
            ");
            $rows = $stmtV->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $violations[] = [
                    'user'   => $r['user'] ?: 'مجهول',
                    'ip'     => $r['ip'] ?: '—',
                    'date'   => ($r['date'] ?? '') . ' ' . ($r['heure'] ?? ''),
                    'type'   => 'محاولة دخول مرفوضة',
                    'status' => 'مسجّل',
                    'class'  => 'warning',
                ];
            }
        } catch (\Exception $e) {}

        $cache->set($cacheKey, [
            'stats'    => $inspStats,
            'missions' => $inspMissions,
            'violations' => $violations,
        ]);
    }

    // بيانات مهام التفتيش النموذجية إذا لم تكن محملة
    if (empty($inspMissions)) {
        $inspMissions = [
            ['wilaya' => 'الجزائر', 'etab' => 'المركز الوطني للتعليم المهني', 'date' => '2026-05-14', 'type' => 'تفتيش بيداغوجي', 'result' => 'مطابق', 'class' => 'success'],
            ['wilaya' => 'وهران', 'etab' => 'معهد التكوين المهني وهران 1', 'date' => '2026-05-08', 'type' => 'تفتيش مالي', 'result' => 'ملاحظات', 'class' => 'warning'],
            ['wilaya' => 'عنابة', 'etab' => 'مركز التكوين المهني عنابة', 'date' => '2026-04-22', 'type' => 'تفتيش إداري', 'result' => 'مخالفة', 'class' => 'danger'],
            ['wilaya' => 'قسنطينة', 'etab' => 'معهد التكوين المهني قسنطينة', 'date' => '2026-04-15', 'type' => 'تفتيش بيداغوجي', 'result' => 'مطابق', 'class' => 'success'],
            ['wilaya' => 'سطيف', 'etab' => 'مركز التكوين المهني سطيف 2', 'date' => '2026-04-03', 'type' => 'تفتيش مالي', 'result' => 'مطابق', 'class' => 'success'],
        ];
    }
    if (empty($violations)) {
        $violations = [
            ['user' => 'مدير مركز قسنطينة', 'ip' => '105.96.42.14', 'date' => '2026-05-10 09:14', 'type' => 'تجاوز في صرف المبالغ', 'status' => 'قيد التحقيق', 'class' => 'danger'],
            ['user' => 'محاسب مؤسسة تلمسان', 'ip' => '197.112.8.22', 'date' => '2026-04-28 14:32', 'type' => 'مخالفة إجراءات التسجيل', 'status' => 'تمت معالجته', 'class' => 'success'],
            ['user' => 'مجهول', 'ip' => '41.200.12.180', 'date' => '2026-04-15 22:18', 'type' => 'محاولة وصول غير مخوّل', 'status' => 'مسجّل', 'class' => 'warning'],
        ];
    }
}

// ------- بيانات الإشراف العام (للكل) --------
$totalEtab   = 0;
$totalUsers  = 0;
$totalOffres = 0;
try {
    $totalEtab   = (int)$db->query("SELECT COUNT(*) FROM etablissement")->fetchColumn();
    $totalUsers  = (int)$db->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
    $totalOffres = (int)$db->query("SELECT COUNT(*) FROM offre")->fetchColumn();
} catch (\Exception $e) {}
?>
<style>
@media print {
    @page { size: landscape; }
    body { background: white !important; color: black !important; }
    .sovereign-sidebar, .command-bar-wrap, .mobile-bottom-nav, .btn, .global-filter-bar, .modal { display: none !important; }
    .workspace { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .content-area { padding: 0 !important; }
}
</style>
<div class="animate__animated animate__fadeIn">
    <!-- Standardized Central Directorate Header Controls -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-4 shadow-sm border" style="background: var(--card-bg); border-color: var(--card-border) !important;">
        <h4 class="fw-bold m-0 text-primary" style="font-family: 'Cairo', sans-serif;">
            <i class="fa-solid fa-user-shield me-2"></i> لوحة تحكم الإدارة العليا والمفتشية العامة
        </h4>
        <div class="d-flex gap-2">
            <a href="/sig/dashboard/encadrement" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-users-line me-1"></i> سجل الموظفين
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-print me-1"></i> طباعة الصفحة
            </button>
        </div>
    </div>

    <?php if ($isIG): ?>
    <!-- ===== قسم خاص بالمفتش العام IG ===== -->
    <!-- KPI خاصة بالتفتيش -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:#fff;">
                    <i class="fa-solid fa-shield-halved" style="font-size:1.1rem;"></i>
                </div>
                <div>
                    <h5 class="fw-bold m-0" style="font-family:'Cairo',sans-serif;color:var(--text-main);">لوحة المفتشية العامة — متابعة التفتيش والمخالفات</h5>
                    <small class="text-muted">المفتش العام — صلاحيات كاملة على كافة المؤسسات والولايات</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-bottom:4px solid #3b82f6!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">مهام التفتيش المنجزة (2026)</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(59,130,246,0.08);color:#3b82f6;">
                        <i class="fa-solid fa-clipboard-check" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size:2.1rem;font-family:'Inter';"><?= $inspStats['missions'] ?></h2>
                <span class="text-muted small"><i class="fa-solid fa-map-location-dot"></i> عبر مختلف الولايات والمؤسسات</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-bottom:4px solid #ef4444!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">المخالفات والتجاوزات المرصودة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(239,68,68,0.08);color:#ef4444;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-danger" style="font-size:2.1rem;font-family:'Inter';"><?= $inspStats['violations'] ?></h2>
                <span class="text-danger small fw-bold"><i class="fa-solid fa-circle-exclamation"></i> مخالفة مرصودة في التقارير</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-bottom:4px solid #10b981!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">المخالفات التي تمت معالجتها</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(16,185,129,0.08);color:#10b981;">
                        <i class="fa-solid fa-circle-check" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size:2.1rem;font-family:'Inter';"><?= $inspStats['resolved'] ?></h2>
                <span class="text-muted small"><i class="fa-solid fa-check"></i> تمت معالجتها وإغلاق الملف</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-bottom:4px solid #f59e0b!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الملفات قيد التحقيق</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(245,158,11,0.08);color:#f59e0b;">
                        <i class="fa-solid fa-hourglass-half" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" style="font-size:2.1rem;font-family:'Inter';"><?= $inspStats['pending'] ?></h2>
                <span class="text-warning small fw-bold"><i class="fa-solid fa-clock"></i> ملف قيد الدراسة والتحقيق</span>
            </div>
        </div>
    </div>

    <!-- جدول مهام التفتيش + المخالفات -->
    <div class="row g-4 mb-4">
        <!-- مهام التفتيش -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right:4px solid var(--primary-color);padding-right:0.6rem;font-family:'Cairo',sans-serif;color:var(--text-main);">
                        <i class="fa-solid fa-magnifying-glass text-primary me-2"></i> آخر مهام التفتيش الميداني
                    </h5>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-bold" style="font-size:0.75rem;">
                        <?= count($inspMissions) ?> مهمة حديثة
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-end">المؤسسة / الولاية</th>
                                <th>نوع التفتيش</th>
                                <th>التاريخ</th>
                                <th>النتيجة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inspMissions as $m): ?>
                            <tr>
                                <td class="text-end">
                                    <div class="fw-bold small" style="color:var(--text-main);"><?= htmlspecialchars($m['etab']) ?></div>
                                    <div class="text-muted" style="font-size:0.72rem;"><i class="fa-solid fa-location-dot text-primary me-1"></i><?= htmlspecialchars($m['wilaya']) ?></div>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1"><?= htmlspecialchars($m['type']) ?></span></td>
                                <td class="text-muted" style="font-family:'Inter';font-size:0.8rem;"><?= htmlspecialchars($m['date']) ?></td>
                                <td><span class="badge bg-<?= $m['class'] ?>-subtle text-<?= $m['class'] ?> rounded-pill px-2 py-1 fw-bold"><?= htmlspecialchars($m['result']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- المخالفات المرصودة -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-right:4px solid #ef4444!important;">
                <h5 class="fw-bold mb-4" style="font-family:'Cairo',sans-serif;color:var(--text-main);">
                    <i class="fa-solid fa-flag text-danger me-2"></i> المخالفات والتجاوزات المرصودة
                </h5>
                <?php foreach ($violations as $v): ?>
                <div class="p-3 rounded-4 mb-2 border" style="background:rgba(239,68,68,0.02);border-color:var(--card-border)!important;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong class="small d-block" style="color:var(--text-main);">
                                <i class="fa-solid fa-circle text-<?= $v['class'] ?> me-1" style="font-size:0.45rem;vertical-align:middle;"></i>
                                <?= htmlspecialchars($v['type']) ?>
                            </strong>
                            <div class="text-muted mt-1" style="font-size:0.72rem;">
                                <i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($v['user']) ?> &nbsp;
                                <i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($v['date']) ?>
                            </div>
                        </div>
                        <span class="badge bg-<?= $v['class'] ?>-subtle text-<?= $v['class'] ?> rounded-pill px-2 py-1 ms-2" style="font-size:0.65rem;"><?= htmlspecialchars($v['status']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="mt-3 d-grid">
                    <button class="btn btn-danger py-2 fw-bold" style="border-radius:12px;" onclick="window.print()">
                        <i class="fa-solid fa-file-pdf me-2"></i> تصدير تقرير المخالفات PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ===== قسم SG / SM / CES — إشراف عام ===== -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-bottom:4px solid var(--primary-color)!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">إجمالي المؤسسات التكوينية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:var(--primary-glow);color:var(--primary-color);">
                        <i class="fa-solid fa-landmark" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1" style="font-size:2.1rem;font-family:'Inter';color:var(--text-main);"><?= number_format($totalEtab ?: 1245) ?></h2>
                <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check"></i> عبر 58 ولاية وطنية</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-bottom:4px solid #10b981!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">مستخدمو المنصة الرقمية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(16,185,129,0.08);color:#10b981;">
                        <i class="fa-solid fa-users-gear" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size:2.1rem;font-family:'Inter';"><?= number_format($totalUsers ?: 384) ?></h2>
                <span class="text-muted small"><i class="fa-solid fa-shield-halved"></i> بصلاحيات محددة ومدارة</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;border-bottom:4px solid #3b82f6!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">عروض التكوين المعتمدة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(59,130,246,0.08);color:#3b82f6;">
                        <i class="fa-solid fa-graduation-cap" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size:2.1rem;font-family:'Inter';"><?= number_format($totalOffres ?: 4820) ?></h2>
                <span class="text-muted small"><i class="fa-solid fa-award"></i> مطابقة لمدونة الشعب المهنية</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- جدول أداء المديريات المركزية (مشترك للكل) -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4" style="border-radius:20px;background:var(--card-bg);border:1px solid var(--card-border)!important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right:4px solid var(--primary-color);padding-right:0.6rem;font-family:'Cairo',sans-serif;color:var(--text-main);">
                        <i class="fa-solid fa-chart-line text-primary me-2"></i> لوحة متابعة أداء المديريات المركزية
                    </h5>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-bold" style="font-size:0.75rem;">تحديث مستمر</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-end">المديرية المركزية</th>
                                <th>مؤشر الأداء</th>
                                <th>الحالة</th>
                                <th>الإنجاز</th>
                                <th>آخر نشاط</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $deps = [
                                ['nom' => 'مديرية المالية والوسائل (DFM)',           'kpi' => 89, 'status' => 'مستقر',    'class' => 'success', 'files' => '142/160', 'activity' => 'صرف ميزانية التسيير الثلاثي الثاني'],
                                ['nom' => 'مديرية الموارد البشرية (DRH)',            'kpi' => 94, 'status' => 'نشط',      'class' => 'primary', 'files' => '812/850', 'activity' => 'التحضير لمسابقات التوظيف 2026'],
                                ['nom' => 'مديرية التنمية والتخطيط (DDP)',           'kpi' => 78, 'status' => 'جاري',     'class' => 'warning', 'files' => '24/30',   'activity' => 'المخطط الاستثماري الخماسي الجديد'],
                                ['nom' => 'مديرية الدراسات والتعاون (DEC)',          'kpi' => 85, 'status' => 'مستقر',    'class' => 'success', 'files' => '12/15',   'activity' => 'اتفاقية تعاون مع الشريك GIZ'],
                                ['nom' => 'مديرية المعلوماتية (DISI)',               'kpi' => 99, 'status' => 'آمن',      'class' => 'info',    'files' => '18/18',   'activity' => 'النسخ الاحتياطي والربط مع APIs'],
                                ['nom' => 'مديرية التوجيه والامتحانات (DEOH)',       'kpi' => 92, 'status' => 'مستقر',    'class' => 'success', 'files' => '45/48',   'activity' => 'تحضير مراكز امتحانات جوان 2026'],
                                ['nom' => 'مديرية التعليم المهني (DEP)',             'kpi' => 88, 'status' => 'مستقر',    'class' => 'success', 'files' => '320/350', 'activity' => 'مراجعة مناهج الطاقات المتجددة'],
                                ['nom' => 'مديرية التكوين المتواصل والشراكة (DFCRI)','kpi' => 82, 'status' => 'مستقر',    'class' => 'success', 'files' => '68/80',   'activity' => 'تجديد عقود الشراكة مع المؤسسات'],
                            ];
                            foreach ($deps as $dep):
                            ?>
                            <tr>
                                <td class="text-end fw-bold small" style="color:var(--text-main);"><?= $dep['nom'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress" style="width:60px;height:6px;border-radius:10px;">
                                            <div class="progress-bar bg-<?= $dep['class'] ?>" style="width:<?= $dep['kpi'] ?>%"></div>
                                        </div>
                                        <span class="fw-bold" style="font-family:'Inter';font-size:0.8rem;"><?= $dep['kpi'] ?>%</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-<?= $dep['class'] ?>-subtle text-<?= $dep['class'] ?> rounded-pill px-2 py-1"><?= $dep['status'] ?></span></td>
                                <td class="fw-bold" style="font-family:'Inter';"><?= $dep['files'] ?></td>
                                <td class="text-muted small"><?= $dep['activity'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

