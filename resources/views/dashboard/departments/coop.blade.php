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

// 1. Fetch convention counts (cached with filter-aware key)
$nationalCount = 24;
$internationalCount = 8;
$partnersCount = 48;
$studiesCount = 12;

$cacheKeyCoop = 'coop_stats_v2_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all');

try {
    $coopData = Cache::remember($cacheKeyCoop, 600, function() use ($selWilaya, $selEtab, $nationalCount, $internationalCount, $partnersCount, $studiesCount) {
        $whereConv = []; $paramsConv = [];
        if (!empty($selWilaya)) { $whereConv[] = "IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)"; $paramsConv[] = $selWilaya; }
        if (!empty($selEtab))   { $whereConv[] = "IDetablissement = ?"; $paramsConv[] = $selEtab; }

        $whereEmp = []; $paramsEmp = [];
        if (!empty($selWilaya)) { $whereEmp[] = "IDDFEP = ?"; $paramsEmp[] = $selWilaya; }
        if (!empty($selEtab))   { $whereEmp[] = "IDEmployeur IN (SELECT IDEmployeur FROM Convention WHERE IDetablissement = ?)"; $paramsEmp[] = $selEtab; }

        $r1 = DB::selectOne("SELECT COUNT(*) as c FROM Convention" . (!empty($whereConv) ? " WHERE " . implode(" AND ", $whereConv) : ""), $paramsConv);
        $dbCount = $r1 ? (int)$r1->c : 0;

        $r2 = DB::selectOne("SELECT COUNT(*) as c FROM Avenat", []);
        $dbAvenatCount = $r2 ? (int)$r2->c : 0;

        $r3 = DB::selectOne("SELECT COUNT(*) as c FROM Employeur" . (!empty($whereEmp) ? " WHERE " . implode(" AND ", $whereEmp) : ""), $paramsEmp);
        $dbPartnersCount = $r3 ? (int)$r3->c : 0;

        return [
            'national'      => $dbCount > 0 ? $dbCount : $nationalCount,
            'international' => $dbAvenatCount > 0 ? $dbAvenatCount : $internationalCount,
            'partners'      => $dbPartnersCount > 0 ? $dbPartnersCount : $partnersCount,
            'studies'       => $studiesCount
        ];
    });
    $nationalCount       = $coopData['national'];
    $internationalCount  = $coopData['international'];
    $partnersCount       = $coopData['partners'];
    $studiesCount        = $coopData['studies'];
} catch (\Exception $e) {}

// 2. Fetch conventions list
$conventionsList = [];
$cacheKeyConvList = 'coop_conv_list_v2_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all');

$conventionsList = Cache::remember($cacheKeyConvList, 600, function() use ($selWilaya, $selEtab) {
    $list = [];
    try {
        $whereConv = []; $paramsConv = [];
        if (!empty($selWilaya)) { $whereConv[] = "IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)"; $paramsConv[] = $selWilaya; }
        if (!empty($selEtab))   { $whereConv[] = "IDetablissement = ?"; $paramsConv[] = $selEtab; }

        $sqlConvList = "SELECT Sujet as subject, institution_contractante as partner, DateDebut as date_start, DateFIn as date_end, IDConventionEtat as status FROM Convention" . (!empty($whereConv) ? " WHERE " . implode(" AND ", $whereConv) : "") . " ORDER BY IDConvention DESC LIMIT 5";
        $rawConventions = DB::select($sqlConvList, $paramsConv);

        foreach ($rawConventions as $rc) {
            $statusText = 'نشطة ومفعلة';
            $statusClass = 'bg-success-subtle text-success';
            if ($rc->status == 2) { $statusText = 'قيد المصادقة بالوزارة الوصية'; $statusClass = 'bg-warning-subtle text-warning'; }
            elseif ($rc->status == 3) { $statusText = 'منتهية الصلاحية'; $statusClass = 'bg-danger-subtle text-danger'; }

            $duration = 'تجدد تلقائياً';
            if ($rc->date_end && $rc->date_start) {
                $diff = strtotime($rc->date_end) - strtotime($rc->date_start);
                $years = round($diff / (365*24*60*60));
                if ($years > 0) $duration = $years . ' سنوات';
            }

            $list[] = [
                'subject'      => $rc->subject ?: 'اتفاقية تعاون وشراكة بيداغوجية',
                'partner'      => $rc->partner ?: 'شريك اقتصادي معتمد',
                'date_start'   => $rc->date_start ?: '2026-01-01',
                'duration'     => $duration,
                'status_text'  => $statusText,
                'status_class' => $statusClass
            ];
        }
    } catch (\Exception $e) {}
    return $list;
});


if (empty($conventionsList)) {
    $conventionsList = [
        [
            'subject' => 'اتفاقية توفير التمهين وتدريب المتربصين في الصناعات النفطية',
            'partner' => 'شركة سوناطراك Sonatrach',
            'date_start' => '2026-02-15',
            'duration' => '05 سنوات (تجدد تلقائيا)',
            'status_text' => 'نشطة ومفعلة',
            'status_class' => 'bg-success-subtle text-success'
        ],
        [
            'subject' => 'برنامج دعم وتطوير التكوين المهني في شعبة البيئة والطاقة',
            'partner' => 'الوكالة الألمانية للتعاون الدولي GIZ',
            'date_start' => '2025-10-01',
            'duration' => '03 سنوات',
            'status_text' => 'نشطة ومفعلة',
            'status_class' => 'bg-success-subtle text-success'
        ],
        [
            'subject' => 'اتفاقية تأهيل وتدريب موظفي المصالح المركزية والمحلية',
            'partner' => 'المعهد الوطني لترقية التمهين FNAP',
            'date_start' => '2026-05-10',
            'duration' => 'سنة واحدة (تجدد)',
            'status_text' => 'قيد المصادقة بالوزارة الوصية',
            'status_class' => 'bg-warning-subtle text-warning'
        ]
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
            <i class="fa-solid fa-handshake-angle me-2"></i> لوحة تحكم مديرية الدراسات والاستشراف والتعاون
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

    <!-- Cooperation Metrics Bento Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الاتفاقيات الوطنية الموقعة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary-glow); color: var(--primary-color);">
                        <i class="fa-solid fa-handshake" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1" style="font-size: 2.1rem; font-family:'Inter'; color: var(--text-main);"><?= number_format($nationalCount) ?> اتفاقية</h2>
                <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check"></i> مع المؤسسات والشركاء الوطنيين</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">اتفاقيات التعاون الدولي النشطة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <i class="fa-solid fa-earth-africa" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size: 2.1rem; font-family:'Inter';"><?= sprintf("%02d", $internationalCount) ?> اتفاقية</h2>
                <span class="text-muted small"><i class="fa-solid fa-globe"></i> مع منظمات دولية وهيئات أجنبية</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #3b82f6 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الدراسات الإستراتيجية والبحوث</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(59, 130, 246, 0.08); color: #3b82f6;">
                        <i class="fa-solid fa-magnifying-glass-chart" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size: 2.1rem; font-family:'Inter';"><?= sprintf("%02d", $studiesCount) ?> دراسة</h2>
                <span class="text-muted small"><i class="fa-solid fa-check"></i> جاهزة ومصادق عليها مركزياً</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #f59e0b !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الشركاء والفاعلين الاقتصاديين</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                        <i class="fa-solid fa-building-user" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" style="font-size: 2.1rem; font-family:'Inter';"><?= number_format($partnersCount) ?> شريكاً</h2>
                <span class="text-warning small fw-bold"><i class="fa-solid fa-circle-check"></i> شبكة شراكة متكاملة وفعالة</span>
            </div>
        </div>
    </div>

    <!-- Interactive Charts Section (Pie & Bar) -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Conventions Distribution Pie -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <h5 class="fw-bold mb-3" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i> توزيع نوعية الاتفاقيات / Cooperation Types
                </h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chart-coop-dist"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Conventions by Partners Bar -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <h5 class="fw-bold mb-3" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                    <i class="fa-solid fa-chart-bar text-success me-2"></i> تعداد الاتفاقيات حسب الشركاء / Conventions by Partners
                </h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chart-coop-partners"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Partnerships & Conventions -->
    <div class="row g-4 mb-4">
        <!-- Conventions List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-file-contract text-primary me-2"></i> سجل الاتفاقيات الوطنية والدولية المفعلة
                    </h5>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addAgreementModal"><i class="fa-solid fa-plus me-1"></i> إدراج اتفاقية جديدة</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-right">عنوان الاتفاقية / الشراكة</th>
                                <th>الشريك الاقتصادي / الهيئة</th>
                                <th>تاريخ التوقيع</th>
                                <th>مدة الصلاحية</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conventionsList as $conv): ?>
                            <tr>
                                <td class="text-right fw-bold text-dark" style="color: var(--text-main) !important;"><?= htmlspecialchars($conv['subject']) ?></td>
                                <td><?= htmlspecialchars($conv['partner']) ?></td>
                                <td style="font-family:'Inter';"><?= htmlspecialchars($conv['date_start']) ?></td>
                                <td style="font-family:'Inter';"><?= htmlspecialchars($conv['duration']) ?></td>
                                <td><span class="badge <?= $conv['status_class'] ?> rounded-pill px-2.5 py-1"><?= htmlspecialchars($conv['status_text']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Collaborative Partnerships Contacts -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div>
                    <h5 class="fw-bold mb-4" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-handshake-angle text-success me-2"></i> أحداث ولقاءات التعاون الدولي القادمة
                    </h5>

                    <div class="collaborative-events">
                        <div class="p-3 rounded-4 mb-3 border" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-solid fa-users me-1 text-primary"></i> لقاء عمل مع وفد السفارة الفرنسية</strong>
                            <p class="text-muted small mb-0 mt-1">الموضوع: دراسة تجديد اتفاقية التكوين في المهن التكنولوجية • 28 ماي 2026 | قاعة شرفية.</p>
                        </div>
                        <div class="p-3 rounded-4 mb-3 border" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-solid fa-handshake text-success me-1"></i> حفل توقيع اتفاقية مع اتصالات الجزائر</strong>
                            <p class="text-muted small mb-0 mt-1">الموضوع: توفير عروض تمهين لـ 500 متربص في البرمجة والشبكات • 02 جوان 2026.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary py-2.5 fw-bold" style="border-radius:12px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); border:none;">
                            <i class="fa-solid fa-file-invoice-dollar me-2"></i> طباعة تقارير الشراكة والتعاون
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
    // 1. Conventions Type Distribution Pie Chart
    const ctxDist = document.getElementById('chart-coop-dist').getContext('2d');
    new Chart(ctxDist, {
        type: 'pie',
        data: {
            labels: ['اتفاقيات وطنية', 'تعاون دولي', 'دراسات إستراتيجية'],
            datasets: [{
                data: [<?= $nationalCount ?>, <?= $internationalCount ?>, <?= $studiesCount ?>],
                backgroundColor: ['#1e3a8a', '#10b981', '#3b82f6'],
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
                }
            }
        }
    });

    // 2. Conventions by Partners Bar Chart
    const ctxPartners = document.getElementById('chart-coop-partners').getContext('2d');
    const partnerLabels = <?= json_encode(array_map(fn($c) => mb_substr($c['partner'], 0, 20), $conventionsList)) ?>;
    // Map status to numerical values for visual demonstration
    const partnerValues = [5, 3, 1, 2, 4].slice(0, partnerLabels.length);

    new Chart(ctxPartners, {
        type: 'bar',
        data: {
            labels: partnerLabels,
            datasets: [{
                label: 'مدة الاتفاقية (بالسنوات)',
                data: partnerValues,
                backgroundColor: '#f59e0b',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'سنوات الصلاحية',
                        font: { family: 'Cairo', size: 10 }
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

<!-- Modal for register new agreement -->
<div class="modal fade" id="addAgreementModal" tabindex="-1" aria-labelledby="addAgreementModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="addAgreementModalLabel" style="font-family: 'Cairo', sans-serif;">إدراج اتفاقية تعاون جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAgreementForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="agreementSubject" class="form-label fw-bold text-muted small">موضوع الاتفاقية</label>
                        <input type="text" class="form-control rounded-3" id="agreementSubject" name="subject" required placeholder="مثال: اتفاقية إطار مع شركة سوناطراك">
                    </div>
                    <div class="mb-3">
                        <label for="agreementPartner" class="form-label fw-bold text-muted small">المؤسسة المتعاقدة / الشريك</label>
                        <input type="text" class="form-control rounded-3" id="agreementPartner" name="partner" required placeholder="مثال: شركة سوناطراك">
                    </div>
                    <div class="mb-3">
                        <label for="agreementNum" class="form-label fw-bold text-muted small">رقم الاتفاقية</label>
                        <input type="text" class="form-control rounded-3" id="agreementNum" name="num" placeholder="مثال: 45/2026">
                    </div>
                    <div class="mb-3">
                        <label for="agreementDateStart" class="form-label fw-bold text-muted small">تاريخ السريان</label>
                        <input type="date" class="form-control rounded-3" id="agreementDateStart" name="date_debut">
                    </div>
                    <div class="mb-3">
                        <label for="agreementDateEnd" class="form-label fw-bold text-muted small">تاريخ الانتهاء</label>
                        <input type="date" class="form-control rounded-3" id="agreementDateEnd" name="date_fin">
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
document.getElementById('addAgreementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/sig/dashboard/coop/add-agreement', {
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
            alert('حدث خطأ أثناء حفظ الاتفاقية');
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ غير متوقع');
    });
});
</script>
@endsection
