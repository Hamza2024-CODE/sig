@extends('layouts.main')
@section('title', 'نظام دعم القرار الاستراتيجي (DSS)')
@section('content')

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.45);
        --glass-border: rgba(26, 107, 204, 0.12);
        --card-shadow: 0 10px 30px rgba(2, 9, 26, 0.05);
        --primary-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #047857 100%);
        --danger-gradient: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
        --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
        --info-gradient: linear-gradient(135deg, #06b6d4 0%, #0e7490 100%);
        --purple-gradient: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    }

    [data-theme="dark"] {
        --glass-bg: rgba(15, 23, 42, 0.45);
        --glass-border: rgba(255, 255, 255, 0.08);
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    }

    .dss-container {
        font-family: 'Cairo', sans-serif;
    }

    .glass-panel-premium {
        background: var(--glass-bg);
        backdrop-filter: blur(25px) saturate(180%);
        -webkit-backdrop-filter: blur(25px) saturate(180%);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .glass-panel-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(2, 9, 26, 0.08);
    }

    .kpi-premium-card {
        border-radius: 24px;
        padding: 1.75rem;
        position: relative;
        overflow: hidden;
        border: none;
        box-shadow: var(--card-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: white;
    }
    .kpi-premium-card:hover {
        transform: translateY(-4px) scale(1.01);
    }
    .kpi-premium-card .bg-icon {
        position: absolute;
        bottom: -20px;
        left: -10px;
        font-size: 6.5rem;
        opacity: 0.12;
        transform: rotate(-15deg);
        pointer-events: none;
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    .alert-premium {
        border-radius: 16px;
        padding: 1.25rem;
        border-right: 6px solid;
    }

    /* Timeline style for recommendations */
    .recommendation-item {
        position: relative;
        padding-right: 2.5rem;
        margin-bottom: 1.5rem;
    }
    .recommendation-item::before {
        content: '';
        position: absolute;
        right: 0.8rem;
        top: 0;
        bottom: -1.5rem;
        width: 2px;
        background: var(--glass-border);
    }
    .recommendation-item:last-child::before {
        display: none;
    }
    .recommendation-icon {
        position: absolute;
        right: 0;
        top: 0;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-gradient);
        color: white;
        z-index: 1;
    }

    .custom-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    /* Premium Centered Modal styling */
    .premium-modal-content {
        background: rgba(255, 255, 255, 0.85) !important;
        backdrop-filter: blur(30px) saturate(200%);
        -webkit-backdrop-filter: blur(30px) saturate(200%);
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        box-shadow: 0 25px 50px -12px rgba(2, 9, 26, 0.25) !important;
        border-radius: 24px !important;
        overflow: hidden;
    }
    
    [data-theme="dark"] .premium-modal-content {
        background: rgba(15, 23, 42, 0.85) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
    }

    .drawer-kpi-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        padding: 1.25rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .drawer-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--card-shadow);
        border-color: rgba(2, 132, 199, 0.2);
    }

    .drawer-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
    }

    .bg-light-success {
        background: rgba(16, 185, 129, 0.1) !important;
        color: #10b981 !important;
    }
    .bg-light-primary {
        background: rgba(2, 132, 199, 0.1) !important;
        color: #0284c7 !important;
    }
    .bg-light-purple {
        background: rgba(139, 92, 246, 0.1) !important;
        color: #8b5cf6 !important;
    }
    .bg-light-warning {
        background: rgba(245, 158, 11, 0.1) !important;
        color: #f59e0b !important;
    }

    .verdict-pulse-dot {
        width: 8px;
        height: 8px;
        background-color: currentColor;
        border-radius: 50%;
        display: inline-block;
        animation: pulse-verdict 1.8s infinite ease-in-out;
        margin-right: 8px;
    }

    @keyframes pulse-verdict {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(26, 107, 204, 0.5); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(26, 107, 204, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(26, 107, 204, 0); }
    }
</style>

<div class="dss-container animate__animated animate__fadeIn" dir="rtl">
    
    <!-- ===== HEADER ===== -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 mt-2">
        <div>
            <h3 class="fw-bold mb-1" style="color:var(--primary-color);">
                <i class="fa-solid fa-brain me-2"></i> نظام دعم القرار الاستراتيجي (DSS)
            </h3>
            <p class="text-muted mb-0 small">
                ذكاء الأعمال الإداري • تحليلات استهلاك الميزانية والنجاح البيداغوجي • التوقعات الرياضية والتوصيات الآلية
            </p>
        </div>
        <div class="d-flex gap-2">
            <!-- Filter Form -->
            <form method="GET" action="" class="d-flex gap-2 align-items-center">
                @if(in_array($roleCode, ['admin', 'central', 'ministre', 'dfep']))
                    <select name="filter_wilaya" id="filter_wilaya" class="form-select rounded-pill bg-light border-light" style="min-width: 150px;" onchange="this.form.submit()">
                        <option value="">-- كل الولايات --</option>
                        @foreach($wilayas as $w)
                            <option value="{{ $w->IDWilayaa }}" {{ $dfepId == $w->IDWilayaa ? 'selected' : '' }}>{{ $w->Nom }}</option>
                        @endforeach
                    </select>

                    <select name="filter_etablissement" id="filter_etablissement" class="form-select rounded-pill bg-light border-light" style="min-width: 200px;" onchange="this.form.submit()">
                        <option value="">-- كل المؤسسات --</option>
                        @foreach($etablissements as $etab)
                            <option value="{{ $etab->IDetablissement }}" {{ $etabId == $etab->IDetablissement ? 'selected' : '' }}>{{ $etab->Nom }}</option>
                        @endforeach
                    </select>
                @endif
                <a href="javascript:location.reload()" class="btn btn-outline-secondary rounded-circle" title="تحديث البيانات">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </form>
        </div>
    </div>

    <!-- ===== MIGRATION PENDING ALERT ===== -->
    @if($migrationsPending)
        <div class="alert alert-premium border-0 shadow-sm d-flex align-items-center gap-3 mb-4" style="background: rgba(245, 158, 11, 0.08); border-right-color: #f59e0b; color: #b45309;">
            <div style="font-size: 1.75rem;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <h6 class="fw-bold mb-1">الهيكل التخزيني الاستراتيجي معلّق (Migrations Pending)</h6>
                <p class="small mb-0 opacity-90">
                    لم يتم تشغيل جداول وعروض الـ KPI المادية (`kpi_snapshots` و `depenses`) في قاعدة البيانات. 
                    يقوم نظام القرار حالياً <strong>بحساب المؤشرات ديناميكياً في الوقت الفعلي</strong> لضمان عمل الصفحة. 
                    لتأمين الأداء وحفظ اللقطات التاريخية، يُرجى تفعيل الهيكل يدوياً عبر سطر الأوامر: <code>php artisan migrate</code> بعد مراجعة النسخة الاحتياطية.
                </p>
            </div>
        </div>
    @else
        <div class="alert border-0 rounded-4 mb-4 d-flex align-items-center gap-2" style="background:rgba(16,185,129,0.08);color:#047857;">
            <i class="fa-solid fa-circle-check"></i>
            <span>المخزن الاستراتيجي نشط. يتم تحديث لقطات الأداء تلقائياً كل ليلة الساعة 02:00 صباحاً عبر Artisan Scheduler.</span>
        </div>
    @endif

    <!-- ===== ROLE-BASED KPI CARDS ===== -->
    <div class="row g-4 mb-4">
        @foreach($scopedData as $domain => $metrics)
            @foreach($metrics as $key => $metric)
                <div class="col-12 col-md-6 col-lg-4">
                    @php
                        // Choose card color gradient based on key
                        $gradient = 'var(--primary-gradient)';
                        $icon = 'fa-chart-line';
                        if (str_contains($key, 'success') || str_contains($key, 'ratio')) {
                            $gradient = 'var(--success-gradient)';
                            $icon = 'fa-graduation-cap';
                        } elseif (str_contains($key, 'dropout') || str_contains($key, 'absence')) {
                            $gradient = 'var(--danger-gradient)';
                            $icon = 'fa-user-slash';
                        } elseif (str_contains($key, 'spending') || str_contains($key, 'absorption')) {
                            $gradient = 'var(--purple-gradient)';
                            $icon = 'fa-file-invoice-dollar';
                        } elseif (str_contains($key, 'budget')) {
                            $gradient = 'var(--info-gradient)';
                            $icon = 'fa-wallet';
                        } elseif (str_contains($key, 'staff') || str_contains($key, 'feminization')) {
                            $gradient = 'var(--warning-gradient)';
                            $icon = 'fa-users';
                        }
                    @endphp

                    <div class="kpi-premium-card" style="background: {{ $gradient }};">
                        <div class="bg-icon"><i class="fa-solid {{ $icon }}"></i></div>
                        <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-tags me-1"></i> {{ $metric['name'] }}</div>
                        <div class="fs-2 fw-black font-inter mb-2">
                            @if(is_numeric($metric['value']))
                                @if($metric['unit'] === 'دج')
                                    {{ number_format($metric['value'], 0, '.', ',') }} <span class="fs-6">{{ $metric['unit'] }}</span>
                                @else
                                    {{ number_format($metric['value'], ($metric['value'] == (int)$metric['value'] ? 0 : 2)) }}{{ $metric['unit'] }}
                                @endif
                            @else
                                {{ $metric['value'] }}
                            @endif
                        </div>

                        <!-- Trend Information (Linear Regression Semester-over-Semester) -->
                        @if(isset($metric['trend']))
                            <div class="border-top pt-2 mt-2 opacity-90 small d-flex flex-wrap justify-content-between align-items-center">
                                <div>
                                    <span class="opacity-75">السداسي السابق:</span>
                                    <strong class="font-inter">
                                        {{ is_numeric($metric['trend']['past_semester_avg']) ? number_format($metric['trend']['past_semester_avg'], ($metric['trend']['past_semester_avg'] == (int)$metric['trend']['past_semester_avg'] ? 0 : 2)) : $metric['trend']['past_semester_avg'] }}{{ $metric['unit'] }}
                                    </strong>
                                </div>
                                <div class="badge bg-white bg-opacity-20 px-2 py-1 rounded">
                                    <span class="fw-bold font-inter">
                                        {{ $metric['trend']['direction'] === 'up' ? '↗' : '↘' }} 
                                        {{ abs($metric['trend']['change_percent']) }}%
                                    </span>
                                </div>
                            </div>
                            <div class="mt-1 small opacity-75">
                                <i class="fa-solid fa-arrow-trend-up me-1"></i> 
                                التوقع للسداسي القادم: 
                                <strong class="font-inter">{{ number_format($metric['trend']['forecast_next_semester'], ($metric['trend']['forecast_next_semester'] == (int)$metric['trend']['forecast_next_semester'] ? 0 : 2)) }}{{ $metric['unit'] }}</strong>
                            </div>
                        @endif

                        @if(isset($metric['details']))
                            <div class="mt-2 border-top pt-2 opacity-90 small">
                                <div class="d-flex justify-content-between">
                                    @foreach($metric['details'] as $detName => $detVal)
                                        <span>{{ $detName }}: <strong class="font-inter">{{ number_format($detVal, 0) }} دج</strong></span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    <!-- ===== CHARTS & AI RECOMMENDATIONS ===== -->
    <div class="row g-4 mb-4">
        <!-- Main Predictive Forecasting Chart -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 glass-panel-premium p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-line text-primary me-2"></i> المحاكي والتحليل التنبئي للمؤشرات الاستراتيجية</h5>
                    <div class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">6 أشهر تاريخية</div>
                </div>
                <div class="chart-container">
                    <canvas id="dssForecastingChart"></canvas>
                </div>
                <div class="text-muted small mt-3">
                    <i class="fa-solid fa-circle-info me-1"></i> 
                    يستخدم هذا المخطط <strong>نموذج الانحدار الخطي (Linear Regression)</strong> ثنائي النقاط المعتمد على مقارنة متوسط أداء السداسي الماضي بالسداسي الحالي لرسم مسار الأداء المستقبلي المتوقع للمؤسسة.
                </div>
            </div>
        </div>

        <!-- AI Recommendation Widget -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 glass-panel-premium p-4 h-100">
                <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i> التوصيات الاستراتيجية المؤتمتة</h5>
                <div style="max-height: 290px; overflow-y: auto; padding-left: 5px;">
                    @foreach($recommendations as $rec)
                        @php
                            $recIcon = 'fa-lightbulb';
                            if (str_contains($rec['icon'], 'cap')) $recIcon = 'fa-graduation-cap';
                            elseif (str_contains($rec['icon'], 'exclamation')) $recIcon = 'fa-triangle-exclamation';
                            elseif (str_contains($rec['icon'], 'dollar') || str_contains($rec['icon'], 'currency')) $recIcon = 'fa-wallet';
                            elseif (str_contains($rec['icon'], 'users') || str_contains($rec['icon'], 'group')) $recIcon = 'fa-users';
                        @endphp
                        <div class="recommendation-item">
                            <div class="recommendation-icon">
                                <i class="fa-solid {{ $recIcon }} text-white" style="font-size:0.8rem;"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1 small">{{ $rec['title'] }}</h6>
                            <p class="text-muted mb-0" style="font-size: 0.85rem; line-height: 1.4;">{{ $rec['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ALERTS & DRILLDOWN COMPARISON ===== -->
    <div class="row g-4 mb-4">
        <!-- Smart Alerts Widget -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 glass-panel-premium p-4 h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-bell text-danger me-2"></i> تنبيهات الكفاءة والركود (Data Freshness & Auditing)</h5>
                <div style="max-height: 350px; overflow-y: auto;">
                    @foreach($alerts as $alert)
                        @php
                            $alertColor = 'rgba(26, 107, 204, 0.08)';
                            $alertBorder = '#1a6bcc';
                            $alertText = '#1a6bcc';
                            $alertIcon = 'fa-info-circle';
                            
                            if ($alert['severity'] === 'danger') {
                                $alertColor = 'rgba(239, 68, 68, 0.08)';
                                $alertBorder = '#ef4444';
                                $alertText = '#dc2626';
                                $alertIcon = 'fa-circle-xmark';
                            } elseif ($alert['severity'] === 'warning') {
                                $alertColor = 'rgba(245, 158, 11, 0.08)';
                                $alertBorder = '#f59e0b';
                                $alertText = '#d97706';
                                $alertIcon = 'fa-triangle-exclamation';
                            } elseif ($alert['severity'] === 'success') {
                                $alertColor = 'rgba(16, 185, 129, 0.08)';
                                $alertBorder = '#10b981';
                                $alertText = '#059669';
                                $alertIcon = 'fa-circle-check';
                            }
                        @endphp
                        <div class="alert border-0 mb-3 shadow-sm rounded-4" style="background: {{ $alertColor }}; border-right: 5px solid {{ $alertBorder }}; color: {{ $alertText }}; padding: 1rem;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid {{ $alertIcon }} mt-1"></i>
                                <div>
                                    <span class="fw-bold d-block small mb-1">{{ $alert['title'] }}</span>
                                    <span style="font-size:0.8rem; line-height: 1.3;">{{ $alert['message'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Regional & Etablissements Drilldown -->
        @if(in_array($roleCode, ['admin', 'central', 'ministre', 'dfep']))
            <div class="col-12 col-lg-8">
                <div class="card border-0 glass-panel-premium p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-building-columns text-primary me-2"></i> المقارنة والتفصيل بين المؤسسات (Drill-down Analytics)</h5>
                    <p class="text-muted small">انقر على أي مؤسسة لفتح لوحة التحليل المعمق واستخراج التوقعات الخاصة بها عبر الاستدعاء التفاعلي (AJAX).</p>
                    
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>اسم المؤسسة</th>
                                    <th>الولاية</th>
                                    <th>نسبة النجاح</th>
                                    <th>الميزانية المخصصة</th>
                                    <th>معدل الاستهلاك</th>
                                    <th>الطلاب/الأساتذة</th>
                                    <th class="text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($drilldownItems as $item)
                                    <tr>
                                        <td class="fw-bold">{{ $item['etab_name'] }}</td>
                                        <td>{{ $item['wilaya_name'] }}</td>
                                        <td><span class="badge bg-success-subtle text-success fw-bold font-inter">{{ $item['success_rate'] }}%</span></td>
                                        <td class="font-inter">{{ number_format($item['budget'], 0) }} دج</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress w-100" style="height: 6px; border-radius: 3px;">
                                                    <div class="progress-bar" role="progressbar" style="width: {{ min(100, $item['absorption']) }}%; background-color: var(--primary-color);" aria-valuenow="{{ $item['absorption'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <span class="font-inter fw-bold">{{ $item['absorption'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="font-inter">{{ $item['student_teacher_ratio'] }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#etabDrilldownModal" 
                                                    onclick="loadEtabDrilldown({{ $item['etab_id'] }})">
                                                <i class="fa-solid fa-magnifying-glass-chart"></i> تفصيل
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <!-- Non-admin fallback info panel -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 glass-panel-premium p-4 h-100 d-flex flex-column justify-content-center align-items-center text-center">
                    <div style="width: 80px; height: 80px; background: rgba(2, 132, 199, 0.08); color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-building-columns fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">الصلاحيات محدودة بالمؤسسة</h5>
                    <p class="text-muted small max-w-md">
                        لقد تم تقييد حسابك لعرض البيانات والمؤشرات الخاصة بمؤسستك التعليمية الحالية فقط. 
                        الوصول إلى جدول المقارنة والتحليلات عبر الولايات يتطلب صلاحيات إدارة مركزية أو ولائية.
                    </p>
                </div>
            </div>
        @endif
    </div>

</div>

<!-- ===== DRILLDOWN DIALOG MODAL ===== -->
<div class="modal fade" id="etabDrilldownModal" tabindex="-1" aria-labelledby="etabDrilldownModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content premium-modal-content border-0">
            <div class="modal-header border-bottom border-light py-3 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="etabDrilldownModalLabel">
                    <i class="fa-solid fa-chart-pie text-primary fs-4"></i>
                    <span>لوحة التحليل المعمق للقرار للمؤسسة</span>
                </h5>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                <!-- Profile info block -->
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-4" style="background: linear-gradient(135deg, rgba(2, 132, 199, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%); border: 1px solid var(--glass-border);">
                    <div class="bg-light-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.35rem; flex-shrink: 0;">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span class="text-muted small d-block mb-1">المؤسسة التعليمية المحددة</span>
                        <h6 class="fw-bold text-dark mb-0 text-truncate" id="drawerEtabName" style="font-size: 0.95rem; line-height: 1.4;">اسم المؤسسة التعليمية</h6>
                    </div>
                </div>

                <!-- Loading spinner -->
                <div id="drawerLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="text-muted small mt-2">جاري استيراد وتحليل بيانات القرار...</p>
                </div>

                <!-- Modal Content -->
                <div id="drawerContent" class="d-none">
                    <!-- Scoped stats -->
                    <div class="row g-3 mb-4">
                        <!-- Success rate -->
                        <div class="col-6">
                            <div class="drawer-kpi-card h-100">
                                <div class="drawer-icon-wrapper bg-light-success">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </div>
                                <span class="text-muted d-block small mb-1">نسبة النجاح</span>
                                <h4 class="fw-black text-success font-inter mb-2" id="drawerSuccessRate" style="font-size: 1.5rem;">0%</h4>
                                <div class="text-muted small pt-2 border-top" style="font-size:0.75rem;">
                                    التنبؤ القادم: <span class="font-inter fw-bold text-dark" id="drawerSuccessForecast">0%</span>
                                </div>
                            </div>
                        </div>
                        <!-- Budget absorption -->
                        <div class="col-6">
                            <div class="drawer-kpi-card h-100">
                                <div class="drawer-icon-wrapper bg-light-primary">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <span class="text-muted d-block small mb-1">استهلاك الميزانية</span>
                                <h4 class="fw-black text-primary font-inter mb-2" id="drawerBudgetAbs" style="font-size: 1.5rem;">0%</h4>
                                <div class="text-muted small pt-2 border-top" style="font-size:0.75rem;">
                                    التنبؤ القادم: <span class="font-inter fw-bold text-dark" id="drawerBudgetForecast">0%</span>
                                </div>
                            </div>
                        </div>
                        <!-- Staff and students -->
                        <div class="col-12">
                            <div class="drawer-kpi-card">
                                <div class="row g-2">
                                    <div class="col-6 border-start border-light ps-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="bg-light-purple rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem; flex-shrink: 0;">
                                                <i class="fa-solid fa-user-graduate"></i>
                                            </div>
                                            <span class="text-muted small">المتربصون النشطون</span>
                                        </div>
                                        <h5 class="fw-bold text-dark font-inter mb-0" id="drawerTrainees">0</h5>
                                    </div>
                                    <div class="col-6 ps-2">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="bg-light-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem; flex-shrink: 0;">
                                                <i class="fa-solid fa-chalkboard-user"></i>
                                            </div>
                                            <span class="text-muted small">موظفو التأطير</span>
                                        </div>
                                        <h5 class="fw-bold text-dark font-inter mb-0" id="drawerStaff">0</h5>
                                    </div>
                                </div>
                                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted small" style="font-size: 0.8rem;">
                                    <span>معدل الطلاب لكل مؤطر:</span>
                                    <strong class="text-dark font-inter" id="drawerRatio" style="font-size: 0.95rem;">0</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Costs -->
                    <div class="card border-0 rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.03) 0%, rgba(2, 132, 199, 0.03) 100%); border: 1px solid var(--glass-border) !important;">
                        <h6 class="fw-bold text-dark small mb-3"><i class="fa-solid fa-receipt text-purple me-1"></i> تفصيل نفقات السداسي الحالي:</h6>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small"><i class="fa-solid fa-money-bill-wave me-1 text-muted opacity-75" style="font-size: 0.8rem;"></i> نفقات الرواتب والأجور (الأعمدة):</span>
                            <strong class="text-dark font-inter small" id="drawerSalaryCost">0 دج</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small"><i class="fa-solid fa-house-chimney me-1 text-muted opacity-75" style="font-size: 0.8rem;"></i> تقدير نفقات السكن الوظيفي (Logement):</span>
                            <strong class="text-dark font-inter small" id="drawerHousingCost">0 دج</strong>
                        </div>
                        
                        <hr class="my-3 opacity-10">
                        
                        <div class="d-flex justify-content-between align-items-center fw-bold text-dark">
                            <span style="font-size: 0.85rem;">إجمالي الإنفاق التقديري:</span>
                            <h5 class="text-primary font-inter mb-0 fw-black" id="drawerTotalSpending">0 دج</h5>
                        </div>
                    </div>

                    <!-- Decision Verdict Alert -->
                    <div class="alert border-0 rounded-4 shadow-sm p-4 d-flex align-items-start gap-3" id="drawerVerdictBox" style="background: rgba(26, 107, 204, 0.08); color: #1a6bcc;">
                        <div class="fs-4 mt-1"><i class="fa-solid fa-robot"></i></div>
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0 small"><i class="fa-solid fa-brain me-1"></i> تقييم ذكاء القرار (DSS Verdict)</h6>
                                <div class="verdict-pulse-dot"></div>
                            </div>
                            <p class="mb-0 small" style="line-height: 1.5; font-size: 0.82rem;" id="drawerVerdictText">تحليل المؤشرات الإيجابية للمؤسسة مستمر.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ===== CHART: Predictive Forecasting (Success rate / Budget absorption) =====
    // Let's obtain the main metric's forecasting trend (we'll default to Success Rate)
    @php
        $mainMetric = isset($scopedData['pedagogical']) ? 'success_rate' : 'budget_absorption_rate';
        $mainData = $scopedData['pedagogical']['success_rate']['trend'] ?? ($scopedData['financial']['budget_absorption_rate']['trend'] ?? null);
        $metricLabel = isset($scopedData['pedagogical']) ? 'نسبة النجاح البيداغوجي (%)' : 'نسبة استهلاك الميزانية (%)';
    @endphp

    const forecastCtx = document.getElementById('dssForecastingChart').getContext('2d');
    
    // Create HSL gradient background
    const primaryGrad = forecastCtx.createLinearGradient(0, 0, 0, 300);
    primaryGrad.addColorStop(0, 'rgba(2, 132, 199, 0.4)');
    primaryGrad.addColorStop(1, 'rgba(2, 132, 199, 0.0)');

    const forecastChart = new Chart(forecastCtx, {
        type: 'line',
        data: {
            labels: ['السداسي السابق (الأشهر 6-12 الماضية)', 'السداسي الحالي (الـ 6 أشهر الحالية)', 'السداسي القادم (توقعات النموذج)'],
            datasets: [{
                label: '{{ $metricLabel }}',
                data: [
                    {{ $mainData ? $mainData['past_semester_avg'] : 76.5 }},
                    {{ $mainData ? $mainData['current_semester_avg'] : 78.5 }},
                    {{ $mainData ? $mainData['forecast_next_semester'] : 80.5 }}
                ],
                backgroundColor: primaryGrad,
                borderColor: '#0284c7',
                borderWidth: 3,
                pointBackgroundColor: ['#0284c7', '#0284c7', '#f43f5e'],
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.3
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
                    grid: { color: 'rgba(26, 107, 204, 0.05)' },
                    ticks: { font: { family: 'Cairo' } },
                    min: 0,
                    max: 100
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Cairo' } }
                }
            }
        }
    });

    // ===== AJAX DRILLDOWN HANDLER =====
    function loadEtabDrilldown(etabId) {
        // Reset display
        document.getElementById('drawerLoader').classList.remove('d-none');
        document.getElementById('drawerContent').classList.add('d-none');

        // Fetch data via AJAX
        const prefix = window.location.pathname.startsWith('/sig') ? '/sig' : '';
        const url = prefix + '/dashboard/dss/drilldown?etab_id=' + etabId;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    Swal.fire({ icon: 'error', title: 'خطأ في التحميل', text: data.error });
                    bootstrap.Modal.getInstance(document.getElementById('etabDrilldownModal'))?.hide();
                    return;
                }

                // Hide loader and show content
                document.getElementById('drawerLoader').classList.add('d-none');
                document.getElementById('drawerContent').classList.remove('d-none');

                // Fill data
                document.getElementById('drawerEtabName').innerText = data.etab_name;
                
                const successRate = parseFloat(data.kpis.success_rate);
                const successForecast = parseFloat(data.trends.success_rate.forecast_next_semester);
                document.getElementById('drawerSuccessRate').innerText = successRate.toFixed(2) + '%';
                document.getElementById('drawerSuccessForecast').innerText = successForecast.toFixed(2) + '%';

                const budgetAbs = parseFloat(data.kpis.budget_absorption_rate);
                const budgetForecast = parseFloat(data.trends.budget_absorption_rate.forecast_next_semester);
                document.getElementById('drawerBudgetAbs').innerText = budgetAbs.toFixed(2) + '%';
                document.getElementById('drawerBudgetForecast').innerText = budgetForecast.toFixed(2) + '%';

                document.getElementById('drawerTrainees').innerText = parseInt(data.kpis.trainees_count).toLocaleString();
                document.getElementById('drawerStaff').innerText = parseInt(data.kpis.staff_count).toLocaleString();
                document.getElementById('drawerRatio').innerText = data.kpis.student_teacher_ratio;

                // Costs
                document.getElementById('drawerSalaryCost').innerText = parseInt(data.kpis.salaries_cost).toLocaleString() + ' دج';
                document.getElementById('drawerHousingCost').innerText = parseInt(data.kpis.logement_cost).toLocaleString() + ' دج';
                document.getElementById('drawerTotalSpending').innerText = parseInt(data.kpis.total_spending).toLocaleString() + ' دج';

                // Evaluate Verdict
                let verdictText = '';
                let verdictColor = 'rgba(26, 107, 204, 0.08)';
                let verdictTextColor = '#1a6bcc';

                if (budgetAbs > 90 && successRate < 70) {
                    verdictText = 'تنبيه: المؤسسة تستهلك الميزانية بنسب قياسية بينما نتائج التحصيل البيداغوجي منخفضة بشكل ملحوظ. يُنصح بإيفاد لجنة تقييم فني وإعادة تخطيط الإنفاق.';
                    verdictColor = 'rgba(239, 68, 68, 0.08)';
                    verdictTextColor = '#dc2626';
                } else if (successRate > 85 && budgetAbs < 60) {
                    verdictText = 'ممتاز: كفاءة تشغيلية ممتازة. تحقق المؤسسة نتائج بيداغوجية باهرة بمعدل إنفاق متواضع. يُنصح بنقل نموذج التسيير المالي والإداري وتعميمه.';
                    verdictColor = 'rgba(16, 185, 129, 0.08)';
                    verdictTextColor = '#059669';
                } else {
                    verdictText = 'مستقر: توازن تشغيلي مقبول بين نسب استهلاك الموازنة التقديرية ونسب نجاح المتربصين. يوصى بالاستمرار في الرقابة الذاتية العادية وتحديث الجداول.';
                    verdictColor = 'rgba(26, 107, 204, 0.08)';
                    verdictTextColor = '#1a6bcc';
                }

                const vBox = document.getElementById('drawerVerdictBox');
                vBox.style.backgroundColor = verdictColor;
                vBox.style.color = verdictTextColor;
                document.getElementById('drawerVerdictText').innerText = verdictText;
            })
            .catch(err => {
                document.getElementById('drawerLoader').classList.add('d-none');
                Swal.fire({ icon: 'error', title: 'خطأ اتصال', text: 'فشل استدعاء الخادم لقراءة بيانات التحليل.' });
                bootstrap.Modal.getInstance(document.getElementById('etabDrilldownModal'))?.hide();
            });
    }
</script>

@endsection
