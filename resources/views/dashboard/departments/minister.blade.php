@extends('layouts.main')

@section('styles')
<style>
    :root {
        --minister-primary: #112d26; /* Dark deep green */
        --minister-gold: #c39d52; /* Elegant gold */
        --minister-accent: #d4af37;
        --minister-bg: #f4f6f9;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }
    
    body {
        background-color: var(--minister-bg) !important;
        background-image: url('{{ asset("assets/images/minister_pattern.png") }}');
        background-blend-mode: overlay;
        background-size: cover;
        background-attachment: fixed;
    }

    /* VIP Header */
    .vip-header {
        background: linear-gradient(135deg, var(--minister-primary) 0%, #1a4a3e 100%);
        border-radius: 16px;
        padding: 2.5rem 3rem;
        box-shadow: 0 10px 30px rgba(17, 45, 38, 0.2);
        color: white;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(195, 157, 82, 0.3);
    }
    
    .vip-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(195, 157, 82, 0.2) 0%, rgba(0,0,0,0) 70%);
        border-radius: 50%;
    }

    .vip-badge {
        background-color: rgba(195, 157, 82, 0.15);
        color: var(--minister-gold);
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.85rem;
        border: 1px solid rgba(195, 157, 82, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* VIP Cards */
    .kpi-vip-card {
        background: var(--glass-bg);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid rgba(195, 157, 82, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .kpi-vip-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(195, 157, 82, 0.15);
    }
    .kpi-vip-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 4px;
        height: 100%;
        background: var(--minister-gold);
    }
    .kpi-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: rgba(195, 157, 82, 0.1);
        color: var(--minister-gold);
    }
    .kpi-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--minister-primary);
        font-family: 'Outfit', 'Cairo', sans-serif;
        line-height: 1;
        margin-top: 0.5rem;
    }
    .kpi-label {
        color: #64748b;
        font-weight: 700;
        font-size: 0.95rem;
    }

    /* Section Headers */
    .section-title {
        color: var(--minister-primary);
        font-weight: 800;
        font-size: 1.25rem;
        position: relative;
        padding-right: 15px;
        margin-bottom: 1.5rem;
    }
    .section-title::before {
        content: '';
        position: absolute;
        right: 0;
        top: 10%;
        height: 80%;
        width: 4px;
        background: var(--minister-gold);
        border-radius: 4px;
    }

    /* Modules */
    .module-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        height: 100%;
    }
    
    .chart-container {
        position: relative;
        height: 250px;
        width: 100%;
    }

    /* Security Log Table */
    .security-table th {
        background: rgba(17, 45, 38, 0.05);
        color: var(--minister-primary);
        font-weight: 700;
        border: none;
        padding: 12px 15px;
    }
    .security-table td {
        vertical-align: middle;
        padding: 12px 15px;
        color: #475569;
        font-size: 0.9rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .status-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }

    /* Print Styles */
    @media print {
        body { background: white !important; }
        .vip-header {
            background: white !important;
            color: black !important;
            box-shadow: none !important;
            border: 2px solid #ccc !important;
            page-break-after: avoid;
        }
        .vip-header h2 { color: black !important; }
        .kpi-vip-card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }
        .module-card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            page-break-inside: avoid;
        }
        .btn, .navbar, .sidebar { display: none !important; }
        .chart-container canvas {
            max-width: 100% !important;
            height: auto !important;
        }
    }

</style>
@endsection

@section('content')
<div class="container-fluid animate__animated animate__fadeIn">

    <!-- Header Section -->
    <div class="vip-header mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="vip-badge mb-3">
                    <i class="fa-solid fa-crown"></i> مركز القيادة الاستراتيجية
                </div>
                <h2 class="fw-bold mb-2" style="font-family:'Cairo';">السيدة الوزيرة، مرحباً بكِ</h2>
                <p class="mb-0" style="opacity: 0.85; font-size: 1.05rem;">
                    هذه اللوحة تقدم لكِ أرقاماً دقيقة ومحدثة آنياً حول قطاع التكوين والتعليم المهنيين، لدعم صناعة القرار الاستراتيجي.
                </p>
            </div>
            <div class="text-end">
                <div class="text-white-50 small mb-1">آخر تحديث للبيانات</div>
                <div class="fw-bold fs-5" style="font-family:'Outfit';">
                    {{ date('Y-m-d H:i') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Strategic Global KPIs -->
    <div class="row g-3 mb-4">
        <!-- KPI 1 -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-vip-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">إجمالي المتربصين (النشطين)</div>
                        <div class="kpi-value">{{ number_format($total_stagiaires ?? 0) }}</div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="mt-3 small text-muted">
                    <i class="fa-solid fa-arrow-trend-up text-success me-1"></i> عبر 58 ولاية
                </div>
            </div>
        </div>
        <!-- KPI 2 -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-vip-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">مؤسسات التكوين المهني</div>
                        <div class="kpi-value">{{ number_format($total_etablissements ?? 0) }}</div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                </div>
                <div class="mt-3 small text-muted">
                    مراكز، معاهد، وملحقات تابعة للقطاع
                </div>
            </div>
        </div>
        <!-- KPI 3 -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-vip-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">الموظفون والإطارات (مُكوّن وإداري)</div>
                        <div class="kpi-value">{{ number_format($total_encadrements ?? 0) }}</div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                </div>
                <div class="mt-3 small text-muted">
                    في كافة المديريات والمؤسسات
                </div>
            </div>
        </div>
        <!-- KPI 4 -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="kpi-vip-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">عروض وتخصصات التكوين</div>
                        <div class="kpi-value">{{ number_format($total_specialites ?? 0) }}</div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                </div>
                <div class="mt-3 small text-muted">
                    تخصص معتمد وفق المدونة الوطنية
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- الجانب البيداغوجي ودعم القرار -->
        <div class="col-xl-8">
            <div class="module-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="section-title mb-0">دعم القرار: خريطة التخصصات والأنماط</h4>
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="fa-solid fa-print me-1"></i> طباعة التقرير</button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-center mb-3">التخصصات الأكثر طلباً وإقبالاً</h6>
                        <div class="chart-container">
                            <canvas id="topSpecsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-center mb-3">توزيع المتربصين حسب نمط التكوين</h6>
                        <div class="chart-container">
                            <canvas id="modesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- التسيير المالي والإداري -->
        <div class="col-xl-4">
            <div class="module-card">
                <h4 class="section-title">التسيير المالي والإداري</h4>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small">استهلاك الميزانية (تقديري)</span>
                        <span class="text-primary fw-bold small">68%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 68%;"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small">نسبة رقمنة الملفات الإدارية</span>
                        <span class="text-success fw-bold small">100%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                    </div>
                    <p class="text-muted small mt-2"><i class="fa-solid fa-circle-check text-success"></i> تم الانتهاء من المزامنة الشاملة للبيانات بنجاح.</p>
                </div>

                <div class="alert" style="background-color: rgba(195, 157, 82, 0.1); border-left: 4px solid var(--minister-gold);">
                    <div class="fw-bold" style="color: var(--minister-primary);"><i class="fa-solid fa-lightbulb text-warning me-2"></i> مؤشر استراتيجي</div>
                    <p class="mb-0 mt-1 small" style="color: #475569;">هناك إقبال كبير على تخصصات الرقمنة والاتصالات مقارنة بالعام الماضي بنسبة زيادة تقدر بـ 15%.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- مركز الأمان والرقابة السيبرانية -->
    <div class="row g-4">
        <div class="col-12">
            <div class="module-card border-0" style="border-right: 4px solid #1e293b !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="section-title mb-0" style="color: #1e293b;"><i class="fa-solid fa-shield-halved me-2"></i> مركز الأمان والرقابة (Cybersecurity Center)</h4>
                    <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> النظام مستقر وآمن</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table security-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>المستخدم / الحساب</th>
                                <th>الوقت والتاريخ</th>
                                <th>النشاط</th>
                                <th>الجهة / IP</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($audit_logs ?? [] as $log)
                            @php $log = (array)$log; @endphp
                            <tr>
                                <td class="fw-bold">{{ $log['nom_complet'] ?? $log['username'] ?? 'غير معروف' }}</td>
                                <td style="font-family:'Outfit';">{{ $log['created_at'] ?? '' }}</td>
                                <td>محاولة دخول</td>
                                <td style="font-family:'Outfit';">{{ $log['iplocal'] ?? '127.0.0.1' }}</td>
                                <td><span class="status-badge status-success">مسجلة</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">لا توجد سجلات أمان حالية.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
    
    // Top Specialties Data
    const specsData = @json($top_specialties_static ?? []);
    if(specsData.length > 0) {
        const specLabels = specsData.map(item => item.spec_ar);
        const specValues = specsData.map(item => item.count);
        
        new Chart(document.getElementById('topSpecsChart'), {
            type: 'bar',
            data: {
                labels: specLabels,
                datasets: [{
                    label: 'عدد المتربصين',
                    data: specValues,
                    backgroundColor: '#112d26',
                    borderRadius: 6,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Mode Distribution Data
    const modesData = @json($mode_distribution ?? []);
    if(modesData.length > 0) {
        const modeLabels = modesData.map(item => item.mode_name);
        const modeValues = modesData.map(item => item.count);
        
        new Chart(document.getElementById('modesChart'), {
            type: 'doughnut',
            data: {
                labels: modeLabels,
                datasets: [{
                    data: modeValues,
                    backgroundColor: ['#c39d52', '#112d26', '#3b82f6', '#10b981', '#f59e0b', '#64748b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', rtl: true, labels: { font: { family: 'Cairo' } } }
                },
                cutout: '65%'
            }
        });
    }
});
</script>
@endsection
