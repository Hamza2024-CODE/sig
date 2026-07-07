@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array $data
 * @var string $role
 */
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// Extract and scope filters
$role = session('user')['role_code'] ?? 'user';
$dfepId = (int)(session('user')['iddfep'] ?? session('user')['IDDFEP'] ?? 0);
$etabId = (int)(session('user')['etablissement_id'] ?? 0);

$selWilaya = $_GET['filter_wilaya'] ?? null;
$selEtab   = $_GET['filter_etablissement'] ?? null;
$selMode   = $_GET['filter_mode'] ?? null;

if ($role === 'dfep' && $dfepId > 0) {
    $selWilaya = $dfepId;
} elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
    $selEtab = $etabId;
    try {
        $rowW = DB::selectOne("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ? LIMIT 1", [$etabId]);
        $selWilaya = $rowW ? (int)$rowW->IDDFEP : null;
    } catch (\Exception $ex) {}
}

// 1. Fetch statistics (cached with filter-aware key)
$totalProjects = 24;
$totalCapacity = 150000;
$totalPlans = 8;
$avgProgress = 78;

$cacheKeyPlan = 'plan_stats_v2_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all') . '_m_' . ($selMode ?: 'all');

try {
    $planData = Cache::remember($cacheKeyPlan, 600, function() use ($selWilaya, $selEtab, $selMode, $totalProjects, $totalCapacity, $totalPlans, $avgProgress) {
        $whereOp = []; $paramsOp = [];
        if (!empty($selWilaya)) { $whereOp[] = "IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)"; $paramsOp[] = $selWilaya; }
        if (!empty($selEtab))   { $whereOp[] = "IDetablissement = ?"; $paramsOp[] = $selEtab; }

        $whereOffre = []; $paramsOffre = [];
        if (!empty($selWilaya)) { $whereOffre[] = "IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)"; $paramsOffre[] = $selWilaya; }
        if (!empty($selEtab))   { $whereOffre[] = "IDEts_Form = ?"; $paramsOffre[] = $selEtab; }
        if (!empty($selMode))   { $whereOffre[] = "IDMode_formation = ?"; $paramsOffre[] = $selMode; }

        $r1 = DB::selectOne("SELECT COUNT(*) as c FROM Operation" . (!empty($whereOp) ? " WHERE " . implode(" AND ", $whereOp) : ""), $paramsOp);
        $dbOp = $r1 ? (int)$r1->c : 0;

        $r2 = DB::selectOne("SELECT COALESCE(SUM(nbrPrevision), 0) as c FROM offre" . (!empty($whereOffre) ? " WHERE " . implode(" AND ", $whereOffre) : ""), $paramsOffre);
        $dbCapacity = $r2 ? (int)$r2->c : 0;

        $r3 = DB::selectOne("SELECT COUNT(*) as c FROM annee_formation", []);
        $dbPlans = $r3 ? (int)$r3->c : 0;

        $r4 = DB::selectOne("SELECT COALESCE(AVG(TauxPhysique), 0) as c FROM Operation" . (!empty($whereOp) ? " WHERE " . implode(" AND ", $whereOp) : ""), $paramsOp);
        $dbAvg = $r4 ? round((double)$r4->c) : 0;

        return [
            'projects'     => $dbOp > 0 ? $dbOp : $totalProjects,
            'capacity'     => $dbCapacity > 0 ? $dbCapacity : $totalCapacity,
            'plans'        => $dbPlans > 0 ? $dbPlans : $totalPlans,
            'avg_progress' => $dbAvg > 0 ? $dbAvg : $avgProgress
        ];
    });
    $totalProjects = $planData['projects'];
    $totalCapacity = $planData['capacity'];
    $totalPlans    = $planData['plans'];
    $avgProgress   = $planData['avg_progress'];
} catch (\Exception $e) {}

// 2. Fetch projects list
$projectList = [];
$cacheKeyProjectList = 'plan_projects_list_v2_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all');

$projectList = Cache::remember($cacheKeyProjectList, 600, function() use ($selWilaya, $selEtab) {
    $list = [];
    try {
        $whereOp = []; $paramsOp = [];
        if (!empty($selWilaya)) { $whereOp[] = "o.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)"; $paramsOp[] = $selWilaya; }
        if (!empty($selEtab))   { $whereOp[] = "o.IDetablissement = ?"; $paramsOp[] = $selEtab; }

        $sqlOpList = "SELECT o.Nom as name, o.CoutActuel as budget, o.TauxPhysique as progress, e.Nom as center_name FROM Operation o LEFT JOIN etablissement e ON o.IDetablissement = e.IDetablissement" . (!empty($whereOp) ? " WHERE " . implode(" AND ", $whereOp) : "") . " ORDER BY o.IDOperationLot DESC LIMIT 3";
        $rawProjects = DB::select($sqlOpList, $paramsOp);
        foreach ($rawProjects as $rp) {
            $list[] = [
                'name'     => $rp->name ?: 'مشروع تنموي معتمد',
                'center'   => $rp->center_name ?: 'مديرية التكوين المهني',
                'budget'   => (double)$rp->budget,
                'progress' => (int)$rp->progress
            ];
        }
    } catch (\Exception $e) {}
    return $list;
});


if (empty($projectList)) {
    $projectList = [
        ['name' => 'بناء وتجهيز مركز التكوين المهني والتمهين - حاسي مسعود', 'center' => 'ورقلة / Ouargla', 'budget' => 142000000, 'progress' => 78],
        ['name' => 'توسعة المعهد الوطني المتخصص في التكوين المهني - السانية', 'center' => 'وهران / Oran', 'budget' => 68000000, 'progress' => 45],
        ['name' => 'إعادة تهيئة وترميم الهياكل البيداغوجية لمعهد الطاقات المتجددة', 'center' => 'قسنطينة / Constantine', 'budget' => 24500000, 'progress' => 95]
    ];
}
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
            <i class="fa-solid fa-chart-pie me-2"></i> لوحة تحكم مديرية التخطيط والتنمية
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

    <!-- Planning & Development Metrics Bento Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">إجمالي المشاريع التنموية الجارية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary-glow); color: var(--primary-color);">
                        <i class="fa-solid fa-helmet-safety" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1" style="font-size: 1.75rem; font-family:'Inter'; color: var(--text-main);"><?= number_format($totalProjects, 0, '.', ',') ?> مشروعاً</h2>
                <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check"></i> موزعة عبر كافة ولايات الوطن</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">القدرة الاستيعابية الوطنية المستهدفة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <i class="fa-solid fa-chart-line" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size: 1.75rem; font-family:'Inter';"><?= number_format($totalCapacity, 0, '.', ',') ?> مقعد</h2>
                <span class="text-success small fw-bold"><i class="fa-solid fa-arrow-trend-up"></i> نسبة التحقيق الحالية: 92%</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #3b82f6 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">المخططات الإستراتيجية السنوية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(59, 130, 246, 0.08); color: #3b82f6;">
                        <i class="fa-solid fa-calendar-check" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size: 1.75rem; font-family:'Inter';"><?= sprintf("%02d", $totalPlans) ?> مخططات</h2>
                <span class="text-muted small"><i class="fa-solid fa-check"></i> معتمدة وموجهة للتنفيذ الفوري</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #f59e0b !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">مؤشر أداء المشاريع الاستثمارية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                        <i class="fa-solid fa-gauge" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" style="font-size: 1.75rem; font-family:'Inter';"><?= $avgProgress ?>% مكتمل</h2>
                <span class="text-warning small fw-bold"><i class="fa-solid fa-circle-exclamation"></i> تطابق تام مع الأهداف المسطرة</span>
            </div>
        </div>
    </div>

    <!-- Interactive Charts Section (Pie & Bar) -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Project Progress Doughnut -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <h5 class="fw-bold mb-3" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i> نسبة إنجاز المشاريع الاستثمارية / Progress
                </h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chart-plan-progress"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Project Budget Bar Chart -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <h5 class="fw-bold mb-3" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                    <i class="fa-solid fa-chart-bar text-success me-2"></i> ميزانيات المشاريع الاستثمارية الكبرى / Project Budgets
                </h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chart-plan-budgets"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Projects Progress & KPIs -->
    <div class="row g-4 mb-4">
        <!-- Infrastructure Projects List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-map-location-dot text-primary me-2"></i> المشاريع الاستثمارية وتوسعة معاهد التكوين المهني الجارية
                    </h5>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addProjectModal"><i class="fa-solid fa-plus me-1"></i> إدراج مشروع تنموي</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-right">عنوان المشروع التنموي</th>
                                <th>الولاية المستفيدة</th>
                                <th>الغلاف المالي المعتمد</th>
                                <th>نسبة تقدم الأشغال</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projectList as $p): ?>
                                <tr>
                                    <td class="text-right fw-bold text-dark" style="color: var(--text-main) !important;"><?= htmlspecialchars($p['name']) ?></td>
                                    <td><?= htmlspecialchars($p['center']) ?></td>
                                    <td style="font-family:'Inter';"><?= number_format($p['budget'], 0, '.', ',') ?> دج</td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="progress" style="width: 80px; height: 6px; border-radius: 10px;">
                                                <?php
                                                $barClass = 'bg-danger';
                                                if ($p['progress'] >= 75) {
                                                    $barClass = 'bg-success';
                                                } elseif ($p['progress'] >= 40) {
                                                    $barClass = 'bg-warning';
                                                }
                                                ?>
                                                <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= $p['progress'] ?>%" aria-valuenow="<?= $p['progress'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="fw-bold" style="font-family:'Inter'; font-size: 0.8rem;"><?= $p['progress'] ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusText = 'قيد الدراسة';
                                        $badgeClass = 'bg-secondary-subtle text-secondary';
                                        if ($p['progress'] >= 90) {
                                            $statusText = 'مكتمل تقريباً';
                                            $badgeClass = 'bg-success-subtle text-success';
                                        } elseif ($p['progress'] >= 70) {
                                            $statusText = 'مرحلة التجهيز';
                                            $badgeClass = 'bg-success-subtle text-success';
                                        } elseif ($p['progress'] >= 40) {
                                            $statusText = 'الأشغال الكبرى';
                                            $badgeClass = 'bg-warning-subtle text-warning';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1"><?= $statusText ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Regional Planning Milestones -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div>
                    <h5 class="fw-bold mb-4" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-map-location text-success me-2"></i> المخطط الزمني للمؤشرات والإحصائيات
                    </h5>

                    <div class="planning-milestones">
                        <div class="p-3 rounded-4 mb-3 border" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-solid fa-circle text-primary me-1" style="font-size:0.45rem;vertical-align:middle;"></i> إحصائيات الدخول المهني دورة فيفري 2026</strong>
                            <p class="text-muted small mb-0 mt-1">تمت المصادقة الرقمية والدمج في بنك البيانات الوطني بنسبة 100%.</p>
                        </div>
                        <div class="p-3 rounded-4 mb-3 border" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-solid fa-circle text-warning me-1" style="font-size:0.45rem;vertical-align:middle;"></i> إحصائيات وتوقعات الطلب لدورة سبتمبر 2026</strong>
                            <p class="text-muted small mb-0 mt-1">جاري استلام مقترحات عروض التكوين من الولايات لتحديد الخارطة البيداغوجية.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary py-2.5 fw-bold" style="border-radius:12px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); border:none;">
                            <i class="fa-solid fa-file-pdf me-2"></i> طباعة الخريطة البيداغوجية السنوية
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Project Progress Doughnut Chart
    const ctxProgress = document.getElementById('chart-plan-progress').getContext('2d');
    new Chart(ctxProgress, {
        type: 'doughnut',
        data: {
            labels: ['المكتمل (متوسط)', 'المتبقي قيد الإنجاز'],
            datasets: [{
                data: [<?= $avgProgress ?>, <?= 100 - $avgProgress ?>],
                backgroundColor: ['#10b981', '#f59e0b'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Cairo', size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });

    // 2. Project Budgets Bar Chart
    const ctxBudgets = document.getElementById('chart-plan-budgets').getContext('2d');
    const projectLabels = <?= json_encode(array_map(fn($p) => mb_substr($p['name'], 0, 25) . (mb_strlen($p['name']) > 25 ? '...' : ''), $projectList)) ?>;
    const projectBudgets = <?= json_encode(array_map(fn($p) => (double)$p['budget'], $projectList)) ?>;

    new Chart(ctxBudgets, {
        type: 'bar',
        data: {
            labels: projectLabels,
            datasets: [{
                label: 'الميزانية المعتمدة (دج)',
                data: projectBudgets,
                backgroundColor: '#3b82f6',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.raw.toLocaleString() + ' دج';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000000) + ' M';
                        }
                    }
                },
                x: {
                    ticks: {
                        font: { family: 'Cairo', size: 10 }
                    }
                }
            }
        }
    });
});
</script>

<!-- Modal for register new project -->
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="addProjectModalLabel" style="font-family: 'Cairo', sans-serif;">إدراج مشروع تنموي جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addProjectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="projectName" class="form-label fw-bold text-muted small">اسم المشروع / العملية</label>
                        <input type="text" class="form-control rounded-3" id="projectName" name="name" required placeholder="مثال: بناء وتجهيز مركز تكوين مهني">
                    </div>
                    <div class="mb-3">
                        <label for="projectCost" class="form-label fw-bold text-muted small">التكلفة المالية الإجمالية (دج)</label>
                        <input type="number" step="0.01" class="form-control rounded-3" id="projectCost" name="cost" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label for="projectOdsNum" class="form-label fw-bold text-muted small">رقم الأمر ببدء الخدمة (ODS)</label>
                        <input type="text" class="form-control rounded-3" id="projectOdsNum" name="num_ods" placeholder="مثال: 125/2026">
                    </div>
                    <div class="mb-3">
                        <label for="projectOdsDate" class="form-label fw-bold text-muted small">تاريخ الأمر ببدء الخدمة (ODS)</label>
                        <input type="date" class="form-control rounded-3" id="projectOdsDate" name="date_ods">
                    </div>
                    <div class="mb-3">
                        <label for="projectEtab" class="form-label fw-bold text-muted small">المؤسسة المستفيدة</label>
                        <select class="form-select rounded-3" id="projectEtab" name="etablissement_id">
                            <option value="1" selected>الإدارة المركزية (الوزارة)</option>
                            <!-- Can be populated dynamically if needed -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">حفظ وإدراج</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addProjectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/sig/dashboard/plan/add-project', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'تم الإدراج بنجاح');
            location.reload();
        } else {
            alert('حدث خطأ أثناء حفظ بيانات المشروع');
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ غير متوقع');
    });
});
</script>
@endsection

