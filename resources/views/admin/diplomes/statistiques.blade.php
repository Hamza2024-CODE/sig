@extends('layouts.main')
@section('title', $title ?? 'إحصائيات خريجي قطاع التكوين المهني')
@section('content')
<?php
$filter_wilaya = $filter_wilaya ?? 0;
$filter_etab   = $filter_etab ?? 0;
$filter_mode   = $filter_mode ?? 0;
$filter_annee  = $filter_annee ?? 0;
$filter_spec   = $filter_spec ?? 0;

$branchStats   = $branchStats ?? [];
$specStats     = $specStats ?? [];
$etabStats     = $etabStats ?? [];
$wilayaStats   = $wilayaStats ?? [];
$yearStats     = $yearStats ?? [];
$modeStats     = $modeStats ?? [];

$isAdmin       = $isAdmin ?? false;
$isDfep        = $isDfep ?? false;
$isEtab        = $isEtab ?? false;

$title         = $title ?? 'إحصائيات خريجي قطاع التكوين المهني';
$kpi           = $kpi ?? ['total_graduates' => 0, 'issued_diplomas' => 0, 'pending_diplomas' => 0];

$filterQs = http_build_query(array_filter([
    'filter_wilaya' => $filter_wilaya ?: null,
    'filter_etab'   => $filter_etab ?: null,
    'filter_mode'   => $filter_mode ?: null,
    'filter_annee'  => $filter_annee ?: null,
    'filter_spec'   => $filter_spec ?: null,
]));
?>
<div class="container-fluid animate__animated animate__fadeIn" style="font-family: 'Cairo', sans-serif;">

    <!-- Title Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary border-opacity-10 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--color-gov-purple-dark, #4a154b);">إحصائيات ومؤشرات خريجي قطاع التكوين المهني / Statistiques des Diplômés</h3>
            <p class="text-muted small mb-0">المؤشرات العامة للنجاح، توزيع الخريجين حسب الولايات، التخصصات، أنماط التكوين وسنوات التخرج</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-print me-1"></i> طباعة التقرير
            </button>
        </div>
    </div>

    <!-- Advanced Filter Bar -->
    <div class="card border-0 shadow-sm p-4 mb-4 no-print" style="border-radius:16px; background:var(--card-bg, #ffffff); border:1px solid var(--card-border, #f1f5f9)!important;">
        <form method="GET" action="{{ url('dashboard/diplomes/statistiques') }}" class="row g-3 align-items-end">

            <!-- Wilaya dropdown (Only for Admin) -->
            @if($isAdmin)
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">الولاية (DFEP)</label>
                <select name="filter_wilaya" id="filter_wilaya" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;" onchange="this.form.submit()">
                    <option value="0">كل الولايات</option>
                    @foreach($wilayas as $w)
                    <option value="{{ $w['id'] }}" {{ $filter_wilaya == $w['id'] ? 'selected' : '' }}>
                        {{ $w['code'] }} - {{ $w['nom_ar'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Etablissement dropdown (Admin & DFEP) -->
            @if($isAdmin || $isDfep)
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية</label>
                <select name="filter_etab" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل المؤسسات</option>
                    @foreach($etablissements as $e)
                    <option value="{{ $e['id'] }}" {{ $filter_etab == $e['id'] ? 'selected' : '' }}>
                        {{ $e['nom_ar'] ?? $e['nom'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية</label>
                <input type="text" class="form-control border-0 rounded text-muted" readonly 
                       value="{{ !empty($etablissements) ? ($etablissements[0]['nom_ar'] ?? $etablissements[0]['nom'] ?? '') : 'المؤسسة الخاصة بك' }}"
                       style="background: var(--input-bg, #e2e8f0); font-size: 0.88rem;">
            </div>
            @endif

            <!-- Mode of formation dropdown -->
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">نمط التكوين</label>
                <select name="filter_mode" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل الأنماط</option>
                    @foreach($modes as $m)
                    <option value="{{ $m['id'] }}" {{ $filter_mode == $m['id'] ? 'selected' : '' }}>
                        {{ $m['libelle_ar'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Year dropdown -->
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">السنة التكوينية</label>
                <select name="filter_annee" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل السنوات</option>
                    @foreach($annees as $an)
                    <option value="{{ $an['id'] }}" {{ $filter_annee == $an['id'] ? 'selected' : '' }}>
                        {{ $an['libelle_ar'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Specialty dropdown -->
            <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">التخصص / الشهادة</label>
                <select name="filter_spec" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل التخصصات / الشهادات</option>
                    @foreach($specialites as $sp)
                    <option value="{{ $sp['id'] }}" {{ $filter_spec == $sp['id'] ? 'selected' : '' }}>
                        {{ $sp['code'] }} - {{ $sp['libelle_ar'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit and Reset Buttons -->
            <div class="col-12 col-md-3 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold flex-grow-1" style="font-size:0.88rem; background: var(--color-gov-purple-dark, #4a154b); border: none;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية الإحصائيات
                </button>
                <a href="{{ url('dashboard/diplomes/statistiques') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold" style="font-size:0.88rem;">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    @if($is_generating)
        <!-- Dynamic Loading Screen -->
        <div class="card border-0 shadow-sm p-5 text-center my-4" style="border-radius:20px; background:var(--card-bg, #ffffff); border: 1px solid var(--card-border, #e2e8f0)!important;">
            <div class="py-5 d-flex flex-column align-items-center">
                <div class="spinner-border mb-4" role="status" style="width: 3.5rem; height: 3.5rem; color: var(--color-gov-purple-dark, #4a154b);">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="fw-bold mb-2 text-dark">جاري حساب وتوليد الإحصائيات العامة للقطاع...</h4>
                <p class="text-muted mb-4 max-w-md" style="font-size:0.95rem; max-width:600px;">
                    نظراً لإحصاء أكثر من <strong>1.2 مليون متخرج</strong> و <strong>2.8 مليون متربص</strong>، يستغرق محرك المؤشرات بضع ثوانٍ لتجميع البيانات وتصفيتها بالكامل. سيتم تحديث هذه الصفحة تلقائياً بمجرد جهوزية التقرير.
                </p>
                <div class="progress w-50 rounded-pill" style="height: 6px; background-color: #f1f5f9;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill" role="progressbar" style="width: 100%; background-color: var(--color-gov-purple-dark, #4a154b);"></div>
                </div>
            </div>
        </div>
    @else
        <!-- KPI Metric Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Graduates -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-4 text-right position-relative overflow-hidden" style="border-radius:20px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color:#fff;">
                    <div class="position-relative" style="z-index: 2;">
                        <span class="d-block small text-white-50 fw-bold mb-1">إجمالي خريجي القطاع / Total Diplômés</span>
                        <h2 class="fw-bold mb-0 text-white" style="font-family:'Outfit', sans-serif;">{{ number_format($kpi['total_graduates'] ?? 0) }}</h2>
                        <small class="text-white-50">متخرج ناجح ومؤهل بصفة رسمية</small>
                    </div>
                    <i class="fa-solid fa-graduation-cap position-absolute opacity-10" style="font-size: 8rem; left: -10px; bottom: -20px; color:#fff; z-index: 1;"></i>
                </div>
            </div>

            <!-- Issued Diplomas -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-4 text-right position-relative overflow-hidden" style="border-radius:20px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color:#fff;">
                    <div class="position-relative" style="z-index: 2;">
                        <span class="d-block small text-white-50 fw-bold mb-1">الشهادات المحررة والموقعة / Validés</span>
                        <h2 class="fw-bold mb-0 text-white" style="font-family:'Outfit', sans-serif;">{{ number_format($kpi['issued_diplomas'] ?? 0) }}</h2>
                        <small class="text-white-50">جاهزة للتسليم وبها رقم تسجيل رسمي</small>
                    </div>
                    <i class="fa-solid fa-award position-absolute opacity-10" style="font-size: 8rem; left: -10px; bottom: -20px; color:#fff; z-index: 1;"></i>
                </div>
            </div>

            <!-- Pending Diplomas -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-4 text-right position-relative overflow-hidden" style="border-radius:20px; background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color:#fff;">
                    <div class="position-relative" style="z-index: 2;">
                        <span class="d-block small text-white-50 fw-bold mb-1">الشهادات قيد التحرير / En Attente</span>
                        <h2 class="fw-bold mb-0 text-white" style="font-family:'Outfit', sans-serif;">{{ number_format($kpi['pending_diplomas'] ?? 0) }}</h2>
                        <small class="text-white-50">قيد المراجعة وإدخال الأرقام التسلسلية</small>
                    </div>
                    <i class="fa-solid fa-hourglass-half position-absolute opacity-10" style="font-size: 8rem; left: -10px; bottom: -20px; color:#fff; z-index: 1;"></i>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
            <!-- Graduates by Year -->
            <div class="col-12 col-md-8">
                <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px; min-height: 400px;">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-line text-primary me-2"></i> منحنى التخرج السنوي للقطاع / Évolution Annuelle</h5>
                    @if(empty($yearStats))
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-muted">
                            <i class="fa-solid fa-chart-area fs-1 mb-2 opacity-25"></i>
                            <span>لا توجد بيانات لسنوات التخرج حالياً</span>
                        </div>
                    @else
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="yearChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Graduates by Training Mode -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px; min-height: 400px;">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-pie text-success me-2"></i> توزيع الخريجين حسب نمط التكوين</h5>
                    @if(empty($modeStats))
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-muted">
                            <i class="fa-solid fa-chart-pie fs-1 mb-2 opacity-25"></i>
                            <span>لا توجد بيانات أنماط التكوين</span>
                        </div>
                    @else
                        <div style="position: relative; height: 300px; width: 100%;" class="d-flex align-items-center justify-content-center">
                            <canvas id="modeChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Graduates by Wilaya (If Admin) -->
            @if($isAdmin && !empty($wilayaStats))
            <div class="col-12 col-md-12">
                <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-map-location-dot text-danger me-2"></i> إحصائيات الخريجين حسب المديريات الولائية (DFEP)</h5>
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="wilayaChart"></canvas>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="row g-4 mb-4">
            <!-- Graduates by Branch (Filiere) -->
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px; height: 100%;">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-diagram-project text-info me-2"></i> الخريجون حسب الشعب المهنية (Branches / Filières)</h5>
                    <div class="d-flex flex-column gap-3 mt-2">
                        @if(empty($branchStats))
                            <p class="text-center py-5 text-muted">لا توجد بيانات متاحة للشعب</p>
                        @else
                            <?php 
                            $maxBranch = count($branchStats) > 0 ? max(array_column($branchStats, 'count')) : 1; 
                            foreach(array_slice($branchStats, 0, 7) as $b):
                                $pct = round(($b['count'] / $maxBranch) * 100);
                            ?>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark">{{ $b['branch_nom'] }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary small fw-bold" style="font-family:'Outfit';">{{ number_format($b['count']) }}</span>
                                </div>
                                <div class="progress" style="height: 10px; border-radius: 20px; background-color: #f1f5f9;">
                                    <div class="progress-bar rounded-pill" role="progressbar" style="<?php echo 'width: ' . $pct . '%; background-color:#3b82f6;'; ?>" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Specialties table -->
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px; height: 100%;">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-ol text-warning me-2"></i> التخصصات العشرة الأولى من حيث عدد الخريجين</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr class="fw-bold text-muted small">
                                    <th>الترتيب</th>
                                    <th class="text-right">التخصص البيداغوجي</th>
                                    <th>عدد المتخرجين</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(empty($specStats))
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">لا توجد إحصائيات متوفرة لتخصصات التخرج.</td>
                                </tr>
                                @else
                                @foreach($specStats as $idx => $sp)
                                <tr>
                                    <td class="fw-bold text-muted" style="font-family:'Outfit';">{{ $idx + 1 }}</td>
                                    <td class="text-right fw-bold text-dark small">{{ $sp['spec_nom'] }}</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary px-3 py-1.5 fw-bold" style="border-radius: 20px; font-family:'Outfit';">
                                            {{ number_format($sp['count']) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Institutions table (Only for Admin & DFEP) -->
        @if(($isAdmin || $isDfep) && !empty($etabStats))
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-house-chimney-user text-primary me-2"></i> ترتيب المؤسسات التكوينية الأكثر تخريجاً للطلبة</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr class="fw-bold text-muted small">
                                    <th>#</th>
                                    <th class="text-right">اسم المؤسسة التكوينية / Centre ou Institut</th>
                                    <th>الولاية</th>
                                    <th>إجمالي الخريجين</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($etabStats as $idx => $et)
                                <tr>
                                    <td class="fw-bold text-muted" style="font-family:'Outfit';">{{ $idx + 1 }}</td>
                                    <td class="text-right fw-bold text-dark">{{ $et['etab_nom'] }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark px-3 py-1.5" style="border-radius: 20px;">
                                            {{ !empty($et['wilaya_nom']) ? $et['wilaya_nom'] : 'محلية' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success px-3 py-1.5 fw-bold" style="border-radius: 20px; font-family:'Outfit';">
                                            {{ number_format($et['count']) }} خريج
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif

</div>

<!-- Stats Data Store (for JS injection to avoid IDE script parser issues) -->
<div id="stats-data-store" style="display: none;"
     data-is-admin="{{ $isAdmin ? 'true' : 'false' }}"
     data-is-generating="{{ $is_generating ? 'true' : 'false' }}"
     data-query-string="{{ $filterQs }}"
     data-year-stats="{{ json_encode($yearStats) }}"
     data-mode-stats="{{ json_encode($modeStats) }}"
     data-wilaya-stats="{{ json_encode($wilayaStats) }}">
</div>

<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var store = document.getElementById('stats-data-store');
    if (!store) return;

    var isGenerating = store.getAttribute('data-is-generating') === 'true';
    if (isGenerating) {
        var queryString = store.getAttribute('data-query-string') || '';
        var pollInterval = setInterval(function() {
            var url = window.location.pathname + '?' + queryString + '&ajax=1';
            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(res) {
                    if (res.status === 'ready') {
                        clearInterval(pollInterval);
                        window.location.reload();
                    }
                })
                .catch(function(err) {
                    console.error('Error polling stats:', err);
                });
        }, 3000);
        return; // Halt execution while generating
    }

    var isAdmin = store.getAttribute('data-is-admin') === 'true';
    
    var yearStats = [];
    try { yearStats = JSON.parse(store.getAttribute('data-year-stats') || '[]'); } catch(e) {}
    
    var modeStats = [];
    try { modeStats = JSON.parse(store.getAttribute('data-mode-stats') || '[]'); } catch(e) {}
    
    var wilayaStats = [];
    try { wilayaStats = JSON.parse(store.getAttribute('data-wilaya-stats') || '[]'); } catch(e) {}

    // ── 1. Year Chart (Line Chart) ──
    if (yearStats.length > 0) {
        var yearCtx = document.getElementById('yearChart').getContext('2d');
        var yearLabels = yearStats.map(function(y) { return y.year_name; });
        var yearData = yearStats.map(function(y) { return y.count; });
        
        new Chart(yearCtx, {
            type: 'line',
            data: {
                labels: yearLabels,
                datasets: [{
                    label: 'عدد الخريجين',
                    data: yearData,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 4
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
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // ── 2. Mode Chart (Doughnut Chart) ──
    if (modeStats.length > 0) {
        var modeCtx = document.getElementById('modeChart').getContext('2d');
        var modeLabels = modeStats.map(function(m) { return m.mode_nom; });
        var modeData = modeStats.map(function(m) { return m.count; });
        var colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1'];

        new Chart(modeCtx, {
            type: 'doughnut',
            data: {
                labels: modeLabels,
                datasets: [{
                    data: modeData,
                    backgroundColor: colors.slice(0, modeData.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { family: 'Cairo' } }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // ── 3. Wilaya Chart (Bar Chart) ──
    if (isAdmin && wilayaStats.length > 0) {
        var wilayaCtx = document.getElementById('wilayaChart').getContext('2d');
        var wilayaLabels = wilayaStats.map(function(w) { return w.wilaya_nom; });
        var wilayaData = wilayaStats.map(function(w) { return w.count; });

        new Chart(wilayaCtx, {
            type: 'bar',
            data: {
                labels: wilayaLabels,
                datasets: [{
                    label: 'عدد الخريجين',
                    data: wilayaData,
                    backgroundColor: '#3b82f6',
                    borderRadius: 5
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
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Cairo', size: 10 }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
