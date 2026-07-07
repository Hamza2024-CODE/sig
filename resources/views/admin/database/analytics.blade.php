@extends('layouts.main')
@section('title', $title ?? 'تحليلات قاعدة البيانات')
@section('content')

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.45);
        --glass-border: rgba(26, 107, 204, 0.12);
        --card-shadow: 0 8px 32px rgba(2, 9, 26, 0.04);
        --primary-gradient: linear-gradient(135deg, #1a6bcc 0%, #6f42c1 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
        --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        --info-gradient: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    }

    [data-theme="dark"] {
        --glass-bg: rgba(15, 23, 42, 0.45);
        --glass-border: rgba(255, 255, 255, 0.08);
        --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }

    .analytics-container {
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
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(26, 107, 204, 0.08);
    }

    .kpi-gradient-card {
        border-radius: 20px;
        color: white;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        border: none;
        box-shadow: var(--card-shadow);
        transition: transform 0.2s ease;
    }
    .kpi-gradient-card:hover {
        transform: scale(1.02);
    }
    .kpi-gradient-card .bg-icon {
        position: absolute;
        bottom: -20px;
        left: -10px;
        font-size: 6rem;
        opacity: 0.12;
        transform: rotate(-15deg);
        pointer-events: none;
    }

    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
    }

    .custom-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .warning-row {
        background: rgba(245, 158, 11, 0.05);
        border-right: 4px solid #f59e0b;
    }

    .danger-row {
        background: rgba(239, 68, 68, 0.05);
        border-right: 4px solid #ef4444;
    }

    .explain-recommendation {
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        font-size: 0.88rem;
    }

    .explain-code {
        font-family: 'Courier New', Courier, monospace;
        background: #0f172a;
        color: #38bdf8;
        padding: 1rem;
        border-radius: 12px;
        font-size: 0.85rem;
        direction: ltr;
        text-align: left;
        overflow-x: auto;
    }
    [data-theme="dark"] .explain-code {
        background: #020617;
    }
</style>

<div class="analytics-container animate__animated animate__fadeIn" dir="rtl">
    
    <!-- ===== HEADER ===== -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 mt-2">
        <div>
            <h3 class="fw-bold mb-1" style="color:var(--primary-color);">
                <i class="fa-solid fa-chart-pie me-2"></i> تحليلات وجودة بيانات المنصة
            </h3>
            <p class="text-muted mb-0 small">
                فحص هيكلي • تحليل سلامة الترميز (Mojibake) • تدقيق سرعة الكتابة • مستكشف الاستعلامات (EXPLAIN)
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/sig/dashboard/database" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-database me-2"></i> مدير البيانات
            </a>
            <a href="/sig/dashboard/database/analytics/refresh" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-arrows-rotate fa-spin-hover me-2"></i> إعادة التحليل والتشخيص
            </a>
        </div>
    </div>

    <!-- Generated Info Alert -->
    <div class="alert border-0 rounded-4 mb-4 d-flex align-items-center gap-2" style="background:rgba(26,107,204,0.08);color:var(--primary-color);">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>آخر تشخيص وتحديث للبيانات: <strong><?= date('Y-m-d H:i:s', strtotime($generated_at)) ?></strong> (يتم تحديثه تلقائياً كل 24 ساعة، أو عبر زر إعادة التحليل).</span>
    </div>

    <!-- ===== KPI ROW ===== -->
    <div class="row g-3 mb-4">
        <!-- DB Size -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-gradient-card" style="background: var(--primary-gradient);">
                <div class="bg-icon"><i class="fa-solid fa-hdd"></i></div>
                <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-database me-1"></i> حجم قاعدة البيانات</div>
                <div class="fs-2 fw-black font-inter"><?= number_format($total_size_mb, 2) ?> <span class="fs-5">MB</span></div>
                <div class="small opacity-90 mt-2">
                    <?= $total_tables ?> جدول نشط في المخطط
                </div>
            </div>
        </div>

        <!-- Row Counts -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="kpi-gradient-card" style="background: var(--info-gradient);">
                <div class="bg-icon"><i class="fa-solid fa-list"></i></div>
                <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-hashtag me-1"></i> إجمالي الصفوف التقريبي</div>
                <div class="fs-2 fw-black font-inter"><?= number_format($total_rows) ?></div>
                <div class="small opacity-90 mt-2">
                    متوسط <?= number_format($total_rows / max(1, $total_tables)) ?> صف لكل جدول
                </div>
            </div>
        </div>

        <!-- Mojibake Score -->
        <div class="col-12 col-md-6 col-lg-3">
            @php
                $healthColor = $mojibake_health_score >= 95 ? 'var(--success-gradient)' : ($mojibake_health_score >= 80 ? 'var(--warning-gradient)' : 'var(--danger-gradient)');
            @endphp
            <div class="kpi-gradient-card" style="background: <?= $healthColor ?>;">
                <div class="bg-icon"><i class="fa-solid fa-shield-heart"></i></div>
                <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-circle-check me-1"></i> مؤشر صحة الترميز</div>
                <div class="fs-2 fw-black font-inter"><?= $mojibake_health_score ?>%</div>
                <div class="small opacity-90 mt-2">
                    فحص <?= number_format($scanned_columns_count) ?> حقل عشوائي
                </div>
            </div>
        </div>

        <!-- Sync Health -->
        <div class="col-12 col-md-6 col-lg-3">
            @php
                $syncRate = $sync_metrics['success_rate'];
                $syncColor = $syncRate >= 95 ? 'var(--success-gradient)' : ($syncRate >= 80 ? 'var(--warning-gradient)' : 'var(--danger-gradient)');
            @endphp
            <div class="kpi-gradient-card" style="background: <?= $syncColor ?>;">
                <div class="bg-icon"><i class="fa-solid fa-sync"></i></div>
                <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-arrows-spin me-1"></i> نجاح مزامنة HFSQL</div>
                <div class="fs-2 fw-black font-inter"><?= $syncRate ?>%</div>
                <div class="small opacity-90 mt-2">
                    <?= number_format($sync_metrics['completed'] + $sync_metrics['failed']) ?> مهمة مجدولة
                </div>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS ROW 1 ===== -->
    <div class="row g-4 mb-4">
        <!-- Top tables by Size -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 glass-panel-premium p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-bar text-primary me-2"></i> أكبر 10 جداول من حيث الحجم (MB)</h5>
                <div class="chart-container">
                    <canvas id="sizeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top tables by Rows -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 glass-panel-premium p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-bar text-indigo me-2"></i> أكبر 10 جداول من حيث عدد الصفوف</h5>
                <div class="chart-container">
                    <canvas id="rowsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS ROW 2 ===== -->
    <div class="row g-4 mb-4">
        <!-- Storage Engine Distribution -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 glass-panel-premium p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-gear text-success me-2"></i> توزيع محركات التخزين</h5>
                <div class="chart-container" style="height:230px;">
                    <canvas id="engineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Index vs Data Ratio -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 glass-panel-premium p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-pie text-warning me-2"></i> نسبة الفهارس إلى البيانات</h5>
                <div class="chart-container" style="height:230px;">
                    <canvas id="ratioChart"></canvas>
                </div>
                <div class="text-center mt-2 small text-muted">
                    إجمالي الفهارس: <?= round(($total_size_mb * ($index_to_data_ratio / (1 + $index_to_data_ratio))), 2) ?> MB
                </div>
            </div>
        </div>

        <!-- Queue Status Summary -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 glass-panel-premium p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i> ملخص حالة الطوابير والتزامن</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success rounded-pill px-2.5 py-1.5"><i class="fa-solid fa-check"></i> ناجحة (Completed)</span></td>
                                <td class="text-end fw-bold font-inter"><?= number_format($sync_metrics['completed']) ?></td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger rounded-pill px-2.5 py-1.5"><i class="fa-solid fa-triangle-exclamation"></i> فاشلة (Failed)</span></td>
                                <td class="text-end fw-bold font-inter"><?= number_format($sync_metrics['failed']) ?></td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary rounded-pill px-2.5 py-1.5"><i class="fa-solid fa-rotate fa-spin"></i> تعمل حالياً (Running)</span></td>
                                <td class="text-end fw-bold font-inter"><?= number_format($sync_metrics['running']) ?></td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark rounded-pill px-2.5 py-1.5"><i class="fa-solid fa-clock"></i> في الانتظار (Pending)</span></td>
                                <td class="text-end fw-bold font-inter"><?= number_format($sync_metrics['pending']) ?></td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-secondary rounded-pill px-2.5 py-1.5"><i class="fa-solid fa-pause"></i> موقوفة مؤقتاً (Paused)</span></td>
                                <td class="text-end fw-bold font-inter"><?= number_format($sync_metrics['paused']) ?></td>
                            </tr>
                            <tr class="border-top">
                                <td class="fw-bold pt-2">إجمالي السجلات المستوردة:</td>
                                <td class="text-end fw-bold text-primary pt-2 font-inter"><?= number_format($sync_metrics['total_synced_rows']) ?> صف</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DATA QUALITY & STRUCTURAL HEALTH ===== -->
    <div class="row g-4 mb-4">
        <!-- PKs & Structure Issues -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 glass-panel-premium p-4 h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-exclamation text-danger me-2"></i> هيكل الجداول ومشاكل الأداء</h5>
                
                @if(empty($missing_pks) && empty($high_index_write_warnings))
                    <div class="alert alert-success border-0 rounded-4 d-flex align-items-center gap-2 mb-0">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                        <span>جميع الجداول مفهرسة بشكل صحيح ولا توجد مشاكل في نسبة الفهارس!</span>
                    </div>
                @else
                    <div style="max-height: 350px; overflow-y: auto; padding-left: 5px;">
                        <!-- Missing PKs -->
                        @if(!empty($missing_pks))
                            <div class="mb-3">
                                <div class="fw-bold text-danger mb-2 small"><i class="fa-solid fa-key me-1"></i> جداول تفتقر لمفتاح أساسي (Primary Key):</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($missing_pks as $tbl)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 font-monospace" style="font-size:0.75rem;"><?= $tbl ?></span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Index vs Data Ratio alerts -->
                        @if(!empty($high_index_write_warnings))
                            <div>
                                <div class="fw-bold text-warning mb-2 small"><i class="fa-solid fa-gauge-high me-1"></i> تنبيه فهارس فائقة الحجم (> 1:1) تؤثر على سرعة الكتابة (Insert/Update):</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle small">
                                        <thead>
                                            <tr class="text-muted">
                                                <th>الجدول</th>
                                                <th>الصفوف</th>
                                                <th>حجم البيانات</th>
                                                <th>حجم الفهارس</th>
                                                <th class="text-end">النسبة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($high_index_write_warnings as $warn)
                                                <tr class="warning-row">
                                                    <td class="font-monospace fw-bold"><?= $warn['table'] ?></td>
                                                    <td class="font-inter"><?= number_format($warn['rows']) ?></td>
                                                    <td class="font-inter"><?= $warn['data_mb'] ?> MB</td>
                                                    <td class="font-inter"><?= $warn['index_mb'] ?> MB</td>
                                                    <td class="text-end fw-bold text-warning font-inter"><?= $warn['ratio'] ?>:1</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Mojibake Warnings -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 glass-panel-premium p-4 h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-language text-warning me-2"></i> تقرير ترميز الحروف وجودة المدخلات (Mojibake)</h5>
                
                @if(empty($mojibake_warnings))
                    <div class="text-center p-4">
                        <div style="width:70px;height:70px;background:rgba(16,185,129,0.08);color:#10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="fa-solid fa-shield-halved fs-3"></i>
                        </div>
                        <h6 class="fw-bold text-success mb-1">الترميز سليم 100% database-wide</h6>
                        <p class="text-muted small mb-0">لم يتم العثور على أي قيم تحتوي على حروف مزدوجة الترميز (Mojibake) في العينات العشوائية.</p>
                    </div>
                @else
                    <div class="alert alert-warning border-0 rounded-4 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>تم اكتشاف ترميز مزدوج محتمل في الجداول التالية (بناءً على عينة عشوائية من 500 صف لكل جدول). يوصى بإصلاحها.</span>
                    </div>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle small">
                                <thead>
                                    <tr class="text-muted">
                                        <th>اسم الجدول</th>
                                        <th>العينة المفحوصة</th>
                                        <th>الأعمدة النصية</th>
                                        <th class="text-end">معدل الخطأ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mojibake_warnings as $mwarn)
                                        <tr class="danger-row">
                                            <td class="font-monospace fw-bold"><?= $mwarn['table'] ?></td>
                                            <td class="font-inter"><?= $mwarn['sample_size'] ?> صف</td>
                                            <td><small class="text-muted"><?= count($mwarn['columns']) ?> أعمدة</small></td>
                                            <td class="text-end text-danger fw-bold font-inter"><?= $mwarn['bad_fields_ratio'] ?>%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ===== INTERACTIVE EXPLAIN QUERY PLAN ANALYZER ===== -->
    <div class="card border-0 glass-panel-premium p-4 mb-4">
        <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-microscope text-primary me-2"></i> محلل أداء الجداول والاستعلامات الذكي (Query Plan EXPLAINer)</h5>
        <p class="text-muted small mb-4">اختر أي جدول من قاعدة البيانات للقيام بتحليل بنيوي فوري والتحقق من جودة الفهرسة وأداء الاستعلامات عليه.</p>
        
        <div class="row g-3 align-items-center mb-4">
            <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-table me-1"></i> حدد جدولاً للتحليل:</label>
                <select class="form-select rounded-pill bg-light border-light" id="explainTableSelector">
                    <option value="">-- اختر جدولاً --</option>
                    @foreach(array_keys($tables) as $tName)
                        <option value="<?= htmlspecialchars($tName) ?>"><?= htmlspecialchars($tName) ?> (<?= number_format($tables[$tName]['rows']) ?> صف)</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-3 align-self-end">
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="runTableExplain()">
                    <i class="fa-solid fa-magnifying-glass-chart me-2"></i> ابدأ التحليل الفوري
                </button>
            </div>
        </div>

        <!-- EXPLAIN RESULTS PANEL (Hidden by default) -->
        <div id="explainResultsPanel" class="d-none animate__animated animate__fadeIn">
            <hr class="my-4">

            <!-- Recommendations Alerts -->
            <div class="mb-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i> توصيات الأداء والتحسين:</h6>
                <div id="explainRecommendations"></div>
            </div>

            <div class="row g-4">
                <!-- Columns Structure -->
                <div class="col-12 col-lg-6">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-columns text-info me-2"></i> هيكل وفهارس الأعمدة (DESCRIBE):</h6>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>اسم الحقل</th>
                                    <th>النوع</th>
                                    <th>Null</th>
                                    <th>الفهرس</th>
                                    <th>الافتراضي</th>
                                    <th>إضافي</th>
                                </tr>
                            </thead>
                            <tbody id="describeTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Query Plan -->
                <div class="col-12 col-lg-6">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-route text-indigo me-2"></i> خطة تنفيذ الاستعلامات الافتراضية (EXPLAIN Plan):</h6>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>النوع</th>
                                    <th>الوصول (type)</th>
                                    <th>مفاتيح مقترحة</th>
                                    <th>مفتاح مستخدم</th>
                                    <th>الصفوف المفحوصة</th>
                                    <th>تفاصيل</th>
                                </tr>
                            </thead>
                            <tbody id="explainTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SQL Index Create Helper -->
            <div class="mt-4">
                <h6 class="fw-bold mb-2 small text-muted"><i class="fa-solid fa-terminal me-1"></i> كود SQL مقترح لإنشاء فهرس (إذا لزم الأمر):</h6>
                <div class="explain-code" id="sqlIndexHelper"></div>
            </div>
        </div>
    </div>

</div>

<script>
    // ===== CHART 1: Size Chart =====
    const sizeCtx = document.getElementById('sizeChart').getContext('2d');
    const sizeData = {
        labels: <?= json_encode(array_keys($top_by_size)) ?>,
        datasets: [{
            label: 'الحجم الكلي (MB)',
            data: <?= json_encode(array_column($top_by_size, 'size_mb')) ?>,
            backgroundColor: 'rgba(26, 107, 204, 0.75)',
            borderColor: 'rgb(26, 107, 204)',
            borderWidth: 1,
            borderRadius: 8
        }]
    };
    new Chart(sizeCtx, {
        type: 'bar',
        data: sizeData,
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
        }
    });

    // ===== CHART 2: Rows Chart =====
    const rowsCtx = document.getElementById('rowsChart').getContext('2d');
    const rowsData = {
        labels: <?= json_encode(array_keys($top_by_rows)) ?>,
        datasets: [{
            label: 'عدد الصفوف',
            data: <?= json_encode(array_column($top_by_rows, 'rows')) ?>,
            backgroundColor: 'rgba(111, 66, 193, 0.75)',
            borderColor: 'rgb(111, 66, 193)',
            borderWidth: 1,
            borderRadius: 8
        }]
    };
    new Chart(rowsCtx, {
        type: 'bar',
        data: rowsData,
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
        }
    });

    // ===== CHART 3: Engines Chart =====
    const engineCtx = document.getElementById('engineChart').getContext('2d');
    new Chart(engineCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($engines)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($engines)) ?>,
                backgroundColor: ['#1a6bcc', '#10b981', '#f59e0b', '#6f42c1', '#6b7280']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });

    // ===== CHART 4: Ratio Chart =====
    const ratioCtx = document.getElementById('ratioChart').getContext('2d');
    new Chart(ratioCtx, {
        type: 'doughnut',
        data: {
            labels: ['حجم البيانات (Data Size)', 'حجم الفهارس (Index Size)'],
            datasets: [{
                data: [
                    <?= round(($total_size_mb / (1 + $index_to_data_ratio)), 2) ?>,
                    <?= round(($total_size_mb * ($index_to_data_ratio / (1 + $index_to_data_ratio))), 2) ?>
                ],
                backgroundColor: ['#1a6bcc', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });

    // ===== INTERACTIVE EXPLAIN QUERY PLAN ANALYZER =====
    function runTableExplain() {
        const table = document.getElementById('explainTableSelector').value;
        if (!table) {
            Swal.fire({ icon: 'warning', title: 'تنبيه', text: 'يرجى اختيار جدول أولاً.' });
            return;
        }

        // Show loading spinner
        Swal.fire({
            title: 'جاري تشغيل التشخيص...',
            html: 'جاري قراءة خطة الاستعلام وتفصيل البنية الهيكلية لجدول <b>' + table + '</b>...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const url = '/sig/dashboard/database/analytics/explain?table=' + encodeURIComponent(table);
        fetch(url)
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'خطأ أثناء فحص خطة الاستعلام', text: data.message });
                    return;
                }

                // Show Results Panel
                document.getElementById('explainResultsPanel').classList.remove('d-none');

                // Render Recommendations
                let recHtml = '';
                data.recommendations.forEach(rec => {
                    let typeClass = 'alert-info text-info bg-info-subtle';
                    if (rec.includes('تنبيه') || rec.includes('تحذير')) {
                        typeClass = 'alert-warning text-warning bg-warning-subtle';
                    }
                    if (rec.includes('خطأ') || rec.includes('مفقود')) {
                        typeClass = 'alert-danger text-danger bg-danger-subtle';
                    }
                    recHtml += `<div class="alert border-0 rounded-3 ${typeClass} mb-2 small"><i class="fa-solid fa-circle-info me-2"></i>${rec}</div>`;
                });
                document.getElementById('explainRecommendations').innerHTML = recHtml;

                // Render Describe body
                let descHtml = '';
                data.columns.forEach(col => {
                    const isPk = col.Key === 'PRI' ? '<i class="fa-solid fa-key text-warning me-1"></i>' : '';
                    descHtml += `<tr>
                        <td class="font-monospace fw-bold">${isPk}${col.Field}</td>
                        <td class="font-monospace"><span class="badge bg-light text-dark font-monospace">${col.Type}</span></td>
                        <td class="font-monospace">${col.Null}</td>
                        <td class="font-monospace"><span class="badge bg-primary-subtle text-primary">${col.Key || '—'}</span></td>
                        <td class="font-monospace">${col.Default !== null ? col.Default : 'NULL'}</td>
                        <td class="font-monospace"><small class="text-muted">${col.Extra || '—'}</small></td>
                    </tr>`;
                });
                document.getElementById('describeTableBody').innerHTML = descHtml;

                // Render Explain body
                let explainHtml = '';
                data.explain.forEach(exp => {
                    explainHtml += `<tr>
                        <td class="font-monospace">${exp.select_type}</td>
                        <td class="font-monospace"><span class="badge bg-info-subtle text-info">${exp.type}</span></td>
                        <td class="font-monospace small">${exp.possible_keys || '—'}</td>
                        <td class="font-monospace small fw-bold text-success">${exp.key || '—'}</td>
                        <td class="font-monospace font-inter">${exp.rows ? Number(exp.rows).toLocaleString() : '0'}</td>
                        <td class="font-monospace small">${exp.Extra || '—'}</td>
                    </tr>`;
                });
                document.getElementById('explainTableBody').innerHTML = explainHtml;

                // Index Suggestion Code Helper
                let missingKeyCols = [];
                data.columns.forEach(col => {
                    if ((col.Field.startsWith('ID') || col.Field.endsWith('_id') || col.Field.endsWith('id')) && col.Key === '') {
                        missingKeyCols.push(col.Field);
                    }
                });
                
                if (missingKeyCols.length > 0) {
                    let helperSql = `-- كود مقترح لإضافة فهارس لتسريع الربط (JOINs):\n`;
                    missingKeyCols.forEach(col => {
                        helperSql += `ALTER TABLE \`${table}\` ADD INDEX \`idx_${table}_${col.toLowerCase()}\` (\`${col}\`);\n`;
                    });
                    document.getElementById('sqlIndexHelper').textContent = helperSql;
                } else {
                    document.getElementById('sqlIndexHelper').textContent = `-- بنية الفهارس مكتملة تماماً، لا توجد حاجة فهارس إضافية لـ \`${table}\`.`;
                }

                // Smooth scroll to results
                document.getElementById('explainResultsPanel').scrollIntoView({ behavior: 'smooth' });
            })
            .catch(err => {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'خطأ اتصال بالخادم', text: err.message });
            });
    }
</script>

@endsection
