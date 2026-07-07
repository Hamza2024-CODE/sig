@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string|null $error
 * @var array $wilayas
 * @var string $current_wilaya
 * @var int $total_stagiaires
 * @var int $total_offres
 * @var int $total_etablissements
 * @var array $recent_offres
 * @var array $gender_breakdown
 * @var array $top_specialites
 */

// Find active wilaya details
$activeWilaya = null;
foreach ($wilayas as $w) {
    if ($w['id'] == $current_wilaya) {
        $activeWilaya = $w;
        break;
    }
}
$wilayaName = $activeWilaya ? $activeWilaya['nom_ar'] : 'الولاية';
?>
<!-- Chart.js for premium archive visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="animate__animated animate__fadeIn" style="font-family: 'Cairo', sans-serif;">
    
    <!-- Header with Sovereign Design -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #003870;">
                <i class="fa-solid fa-box-archive text-primary me-2" style="color: var(--color-gov-blue) !important;"></i> بوابة الأرشيف الوطني التاريخي (HFSQL)
            </h3>
            <p class="text-muted mb-0 small">تصفح لوحات التحكم والبيانات التاريخية المؤرشفة مباشرة من خادم HFSQL الموحد</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center">
                <i class="fa-solid fa-print me-2"></i> طباعة تقرير الأرشيف
            </button>
        </div>
    </div>

    <!-- Error state check -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger rounded-4 border-0 shadow-sm p-4 mb-4" role="alert">
            <h5 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> حدث خطأ أثناء الاتصال بقاعدة الأرشيف</h5>
            <p class="mb-0 mt-2 small"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php else: ?>

        <!-- Wilayas Boards Tab Navigation (Top Bar) -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <h6 class="fw-bold text-muted small mb-3"><i class="fa-solid fa-map-location-dot me-1 text-primary"></i> اختر لوحة الولاية لعرض بياناتها المؤرشفة (Boards):</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($wilayas as $w): ?>
                    <a href="/dashboard/archive?wilaya_id=<?= $w['id'] ?>" 
                       class="btn rounded-pill px-3 py-2 fw-bold text-truncate shadow-sm d-flex align-items-center gap-1.5 <?= $current_wilaya == $w['id'] ? 'btn-primary' : 'btn-light border bg-light-hover' ?>"
                       style="font-size: 0.82rem; min-width: 110px; justify-content: center; <?= $current_wilaya == $w['id'] ? 'background: linear-gradient(135deg, #003870 0%, #0284c7 100%); border: none;' : '' ?>">
                        <i class="fa-solid fa-location-dot" style="font-size: 0.72rem;"></i>
                        <?= htmlspecialchars($w['nom_ar']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Wilaya Header Banner -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden position-relative" style="background: linear-gradient(135deg, #003870 0%, #0369a1 100%); padding: 2.5rem 2rem;">
            <!-- Subtle backdrop sphere -->
            <div style="position: absolute; right: -50px; bottom: -50px; width: 220px; height: 220px; border-radius: 50%; background: rgba(255,255,255,0.05); pointer-events: none;"></div>
            <div style="position: absolute; left: 10%; top: -30px; width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,0.03); pointer-events: none;"></div>
            
            <div class="position-relative z-2">
                <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1.5 fw-bold mb-2 small" style="backdrop-filter: blur(10px);"><i class="fa-solid fa-shield-halved me-1"></i> أرشيف HFSQL النشط</span>
                <h2 class="fw-bold mb-1" style="font-family:'Cairo';">لوحة بيانات ولاية: <?= htmlspecialchars($wilayaName) ?></h2>
                <p class="mb-0 text-white text-opacity-80 small">تصفح لوحة التحكم الأرشيفية المخصصة للمتربصين، التخصصات، والمؤسسات التابعة للولاية</p>
            </div>
        </div>

        <!-- Stat KPI Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-2 bg-white stat-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background-color: rgba(0, 56, 112, 0.08); color: #003870;">
                            <i class="fa-solid fa-user-graduate fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted fw-bold small mb-1">المتربصون المؤرشفون</h6>
                            <h3 class="fw-bold mb-0 text-dark" style="font-family:'Inter';"><?= number_format($total_stagiaires) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-2 bg-white stat-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background-color: rgba(16, 185, 129, 0.08); color: #10b981;">
                            <i class="fa-solid fa-bullhorn fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted fw-bold small mb-1">عروض التكوين التاريخية</h6>
                            <h3 class="fw-bold mb-0 text-success" style="font-family:'Inter';"><?= number_format($total_offres) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-2 bg-white stat-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background-color: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                            <i class="fa-solid fa-building-columns fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted fw-bold small mb-1">المؤسسات التكوينية</h6>
                            <h3 class="fw-bold mb-0 text-warning" style="font-family:'Inter';"><?= number_format($total_etablissements) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
            <!-- Gender Chart Card -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-4 small"><i class="fa-solid fa-venus-mars text-primary me-2"></i> توزيع الجنسين بالأرشيف / Sexe</h5>
                    <?php if (empty($gender_breakdown)): ?>
                        <div class="text-center py-5 text-muted small">
                            <i class="fa-solid fa-chart-pie fs-3 mb-2 d-block text-muted opacity-50"></i> لا توجد بيانات إحصائية
                        </div>
                    <?php else: ?>
                        <div style="max-height: 250px; position: relative;">
                            <canvas id="genderChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Specialties Card -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-4 small"><i class="fa-solid fa-star text-primary me-2"></i> التخصصات الأكثر طلباً بالأرشيف / Top Spécialités</h5>
                    <?php if (empty($top_specialites)): ?>
                        <div class="text-center py-5 text-muted small">
                            <i class="fa-solid fa-chart-bar fs-3 mb-2 d-block text-muted opacity-50"></i> لا توجد بيانات إحصائية
                        </div>
                    <?php else: ?>
                        <div style="height: 250px; position: relative;">
                            <canvas id="specsChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Archived Offers Table Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
            <h5 class="fw-bold text-dark mb-3 small"><i class="fa-solid fa-list-check text-primary me-2"></i> سجل عروض التكوين المؤرشفة للولاية (HFSQL Database)</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead class="table-light">
                        <tr class="fw-bold text-muted" style="font-size:0.85rem;">
                            <th>رمز العرض</th>
                            <th class="text-right">التخصص البيداغوجي</th>
                            <th class="text-right">المؤسسة التكوينية</th>
                            <th>المقاعد البيداغوجية</th>
                            <th>تاريخ البداية والنهاية</th>
                            <th>حالة الأرشيف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_offres)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fs-1 mb-3 d-block text-muted opacity-50"></i>
                                    لا توجد عروض تكوين مؤرشفة مسجلة لهذه الولاية حالياً.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_offres as $o): ?>
                                <tr>
                                    <td class="fw-bold text-dark" style="font-family:'Outfit';"><?= htmlspecialchars($o['code']) ?></td>
                                    <td class="text-right"><strong><?= htmlspecialchars($o['spec_ar']) ?></strong></td>
                                    <td class="text-right"><span class="text-muted small"><?= htmlspecialchars($o['etab_ar'] ?? 'غير معروف') ?></span></td>
                                    <td class="fw-bold text-primary"><?= $o['capacite'] ?> مقعد</td>
                                    <td class="small text-muted" dir="ltr"><?= substr($o['date_debut'] ?? '', 0, 10) ?> / <?= substr($o['date_fin'] ?? '', 0, 10) ?></td>
                                    <td><span class="badge bg-secondary rounded-pill px-3 py-1.5 fw-bold"><i class="fa-solid fa-box-archive me-1"></i> مؤرشف</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Chart initialization scripts -->
<?php if (empty($error) && !empty($gender_breakdown)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Gender Chart Configuration
    const genderCtx = document.getElementById('genderChart');
    if (genderCtx) {
        const rawGenders = <?= json_encode($gender_breakdown) ?>;
        const labels = [];
        const values = [];
        rawGenders.forEach(g => {
            labels.push(g.sexe === 'F' || g.sexe == 2 ? 'إناث' : 'ذكور');
            values.push(g.count);
        });

        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#38bdf8', '#0284c7'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Cairo', size: 12 }
                        }
                    }
                }
            }
        });
    }

    // 2. Top Specialties Bar Chart
    const specsCtx = document.getElementById('specsChart');
    if (specsCtx) {
        const rawSpecs = <?= json_encode($top_specialites) ?>;
        const labels = [];
        const values = [];
        rawSpecs.forEach(s => {
            labels.push(s.spec_ar);
            values.push(s.count);
        });

        new Chart(specsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'عدد المتربصين',
                    data: values,
                    backgroundColor: 'rgba(3, 105, 161, 0.85)',
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: {
                            font: { family: 'Cairo', size: 10 }
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

@endsection
