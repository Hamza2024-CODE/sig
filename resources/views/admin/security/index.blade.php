@extends('layouts.main')
@section('title', 'مركز الأمان والرقابة — SGFEP')
@section('content')
<div class="animate__animated animate__fadeIn py-4">

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family:'Cairo';">
                <i class="fa-solid fa-shield-halved text-primary me-2"></i>
                مركز الأمان والرقابة الإلكترونية
            </h3>
            <p class="text-muted small mb-0">مراقبة حية للمصادقة، النفاذ للأجهزة، الأحداث الأمنية وتقييم الامتثال الأمني.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
            <a href="{{ request()->is('sig/*') ? '/sig/dashboard' : '/dashboard' }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة
            </a>
            <a href="{{ request()->is('sig/*') ? '/sig/admin/security/mfa' : '/admin/security/mfa' }}" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-user-shield me-1"></i> إدارة سياسات الـ MFA
            </a>
            <a href="{{ request()->is('sig/*') ? '/sig/admin/security/logs' : '/admin/security/logs' }}" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #0f172a, #2563eb); border: none;">
                <i class="fa-solid fa-list-check me-1"></i> سجلات التدقيق الكاملة
            </a>
        </div>
    </div>

    <!-- ── KPIs & Security Score ────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Security Score -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0) 90%);"></div>
                <div class="d-flex align-items-center justify-content-center mb-3 mx-auto rounded-circle" style="width: 60px; height: 60px; background: {{ $securityScore >= 80 ? '#10b98118' : ($securityScore >= 50 ? '#f59e0b18' : '#ef444418') }};">
                    <i class="fa-solid fa-gauge-high fs-3" style="color: {{ $securityScore >= 80 ? '#10b981' : ($securityScore >= 50 ? '#f59e0b' : '#ef4444') }};"></i>
                </div>
                <h3 class="fw-bold mb-1" style="font-family:'Outfit'; color: {{ $securityScore >= 80 ? '#10b981' : ($securityScore >= 50 ? '#f59e0b' : '#ef4444') }};">{{ $securityScore }}%</h3>
                <div class="text-dark fw-bold small mb-1" style="font-family:'Cairo';">مؤشر الامتثال الأمني</div>
                <span class="badge rounded-pill px-3 py-1 mx-auto small text-capitalize" style="font-size: 0.75rem; background: {{ $securityScore >= 80 ? '#10b98122' : ($securityScore >= 50 ? '#f59e0b22' : '#ef444422') }}; color: {{ $securityScore >= 80 ? '#047857' : ($securityScore >= 120 ? '#d97706' : '#b91c1c') }};">
                    {{ $securityScore >= 80 ? 'ممتاز وآمن' : ($securityScore >= 50 ? 'متوسط الحماية' : 'مخاطر عالية') }}
                </span>
            </div>
        </div>

        <!-- KPI 2: MFA Adoption Rate -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-center mb-3 mx-auto rounded-circle" style="width: 60px; height: 60px; background: #6366f118;">
                        <i class="fa-solid fa-user-shield text-indigo fs-3" style="color: #6366f1;"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1" style="font-family:'Outfit';">{{ $mfaAdoptionRate }}%</h3>
                    <div class="text-muted fw-bold small mb-1" style="font-family:'Cairo';">معدل تفعيل الـ MFA</div>
                    <div class="small text-muted mb-3">
                        تم تفعيل {{ $mfaEnabledAdmins }} من أصل {{ $totalAdmins }} مسؤولين
                    </div>
                </div>
                <div class="border-top pt-2">
                    <a href="{{ request()->is('sig/*') ? '/sig/admin/security/mfa' : '/admin/security/mfa' }}" class="text-indigo small fw-bold text-decoration-none" style="font-family:'Cairo'; color: #6366f1;">
                        إدارة سياسات المصادقة <i class="fa-solid fa-arrow-left ms-1" style="font-size:0.7rem;"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- KPI 3: Security Threats (Last 7 Days) -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center">
                <div class="d-flex align-items-center justify-content-center mb-3 mx-auto rounded-circle" style="width: 60px; height: 60px; background: {{ $highAlertsCount > 0 ? '#ef444418' : '#10b98118' }};">
                    <i class="fa-solid fa-triangle-exclamation fs-3" style="color: {{ $highAlertsCount > 0 ? '#ef4444' : '#10b981' }};"></i>
                </div>
                <h3 class="fw-bold mb-1" style="font-family:'Outfit'; color: {{ $highAlertsCount > 0 ? '#ef4444' : '#10b981' }};">{{ $highAlertsCount }}</h3>
                <div class="text-muted fw-bold small mb-1" style="font-family:'Cairo';">أحداث أمنية نشطة</div>
                <div class="small text-muted">
                    {{ $highAlertsCount > 0 ? 'مستوى تهديد متوسط إلى حرج' : 'لا توجد مخاطر نشطة حالياً' }}
                </div>
            </div>
        </div>

        <!-- KPI 4: Trusted Devices -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center">
                <div class="d-flex align-items-center justify-content-center mb-3 mx-auto rounded-circle" style="width: 60px; height: 60px; background: #3b82f618;">
                    <i class="fa-solid fa-laptop-medical fs-3" style="color: #3b82f6;"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1" style="font-family:'Outfit';">{{ $trustedDevicesCount }}</h3>
                <div class="text-muted fw-bold small mb-1" style="font-family:'Cairo';">الأجهزة الموثوقة المسجلة</div>
                <div class="small text-muted">
                    أجهزة نشطة معفية من OTP مؤقتاً
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- ── Secondary Security KPIs ──────────────────────────────── -->
    <div class="row g-3 mb-4">
        <!-- Unprotected Accounts Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 d-flex flex-row align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: {{ $unprotectedAdminsCount > 0 ? '#ef444415' : '#10b98115' }}; color: {{ $unprotectedAdminsCount > 0 ? '#ef4444' : '#10b981' }};">
                        <i class="fa-solid fa-users-slash fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-5">{{ $unprotectedAdminsCount }}</div>
                        <div class="text-muted small fw-bold" style="font-family:'Cairo';">حسابات غير محمية بـ MFA</div>
                    </div>
                </div>
                @if($unprotectedAdminsCount > 0)
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#unprotectedAdminsModal" style="font-family:'Cairo'; font-size: 0.8rem;">
                        عرض الحسابات
                    </button>
                @else
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-bold small" style="font-family:'Cairo';">مؤمن بالكامل</span>
                @endif
            </div>
        </div>

        <!-- Hacking Attempts Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: {{ $hackingAttemptsCount > 0 ? '#f59e0b15' : '#10b98115' }}; color: {{ $hackingAttemptsCount > 0 ? '#f59e0b' : '#10b981' }};">
                    <i class="fa-solid fa-user-ninja fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark fs-5">{{ $hackingAttemptsCount }}</div>
                    <div class="text-muted small fw-bold" style="font-family:'Cairo';">محاولات الاختراق والتخمين</div>
                </div>
            </div>
        </div>

        <!-- Untrusted Devices Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 d-flex flex-row align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: {{ $untrustedDevicesCount > 0 ? '#ef444415' : '#10b98115' }}; color: {{ $untrustedDevicesCount > 0 ? '#ef4444' : '#10b981' }};">
                    <i class="fa-solid fa-ban fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark fs-5">{{ $untrustedDevicesCount }}</div>
                    <div class="text-muted small fw-bold" style="font-family:'Cairo';">الأجهزة غير الموثوقة / المشبوهة</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Settings Row: IP Shield Control ──────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-10 text-white" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-shield-halved fs-3 text-warning"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 text-white" style="font-family:'Cairo';">
                        جدار حماية عناوين الـ IP (IP Ban Shield)
                    </h5>
                    <p class="text-white-50 small mb-0" style="font-family:'Cairo';">
                        عند تفعيل هذه الميزة، سيتم حظر عناوين IP تلقائياً لمدة 24 ساعة عند تجاوز 5 محاولات دخول فاشلة متتالية.
                    </p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge rounded-pill px-3 py-2 fw-bold" style="font-family:'Cairo'; font-size: 0.85rem; {{ $ipBanningEnabled ? 'background-color: #10b981; color: #fff;' : 'background-color: #ef4444; color: #fff;' }}">
                    <i class="fa-solid {{ $ipBanningEnabled ? 'fa-circle-check' : 'fa-circle-xmark' }} me-1"></i>
                    {{ $ipBanningEnabled ? 'الخاصية نشطة ومفعلة' : 'الخاصية معطلة (مفتوح للجميع)' }}
                </span>
                
                <form method="POST" action="{{ request()->is('sig/*') ? '/sig/admin/security/ip-ban/toggle' : '/admin/security/ip-ban/toggle' }}">
                    @csrf
                    <input type="hidden" name="ip_banning_enabled" value="{{ $ipBanningEnabled ? '0' : '1' }}">
                    <button type="submit" class="btn {{ $ipBanningEnabled ? 'btn-danger' : 'btn-success' }} rounded-pill px-4 fw-bold shadow-sm" style="font-family:'Cairo';">
                        <i class="fa-solid {{ $ipBanningEnabled ? 'fa-toggle-on' : 'fa-toggle-off' }} me-1"></i>
                        {{ $ipBanningEnabled ? 'إيقاف تشغيل الخاصية' : 'تفعيل تشغيل الخاصية' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Charts Row ───────────────────────────────────────────── -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Threat and Access Trends -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';">
                    <i class="fa-solid fa-chart-line text-primary me-2"></i>
                    مخطط النفاذ والتهديدات (آخر 7 أيام)
                </h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Risk distribution -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>
                    توزيع الأحداث حسب الخطورة
                </h5>
                <div style="height: 250px; position: relative;" class="d-flex align-items-center justify-content-center">
                    <canvas id="severityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Geographical Threats Map Row ─────────────────────────── -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';">
                    <i class="fa-solid fa-map-location-dot text-danger me-2"></i>
                    التحليل الجغرافي لمصادر التهديدات (Threat Origin Density Map)
                </h5>
                <p class="text-muted small">كثافة ونطاقات محاولات الدخول المشبوهة والولوج غير المصرح به المكتشفة موزعة جغرافياً:</p>
                
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
                <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
                
                <div id="security-threat-map" style="height: 420px; width: 100%; border-radius: 18px; border: 1px solid var(--border); z-index: 1;"></div>
            </div>
        </div>
    </div>

    <!-- ── Discovered Vulnerabilities ───────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-dark mb-0" style="font-family:'Cairo';">
                <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                الثغرات ونقاط الضعف المكتشفة (Audited Vulnerabilities)
            </h5>
            <span class="badge bg-{{ empty($vulnerabilities) ? 'success' : 'danger' }}-subtle text-{{ empty($vulnerabilities) ? 'success' : 'danger' }} border border-{{ empty($vulnerabilities) ? 'success' : 'danger' }}-subtle px-3 py-1 rounded-pill fw-bold" style="font-size: 0.8rem; font-family: 'Cairo';">
                {{ empty($vulnerabilities) ? 'النظام خالي من الثغرات' : 'تم اكتشاف ' . count($vulnerabilities) . ' نقاط ضعف' }}
            </span>
        </div>

        @if(empty($vulnerabilities))
            <div class="text-center py-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-shield-halved text-success fs-3"></i>
                </div>
                <p class="text-muted mb-0 fw-bold" style="font-family:'Cairo';">لم يتم اكتشاف أي ثغرات أو نقاط ضعف نشطة في إعدادات المنصة.</p>
                <p class="text-muted small mt-1">كافة إعدادات الـ Security Cookies، الـ APP_DEBUG والـ MFA متوافقة مع التوصيات الأمنية.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach($vulnerabilities as $v)
                    <?php
                        $badgeColor = $v['severity'] === 'critical' ? 'danger' : ($v['severity'] === 'danger' ? 'warning' : 'info');
                        $badgeText = $v['severity'] === 'critical' ? 'حرجة جداً' : ($v['severity'] === 'danger' ? 'عالية الخطورة' : 'متوسطة الخطورة');
                    ?>
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 border h-100 d-flex gap-3 position-relative overflow-hidden" style="background: #fafafa;">
                            <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 45px; height: 45px; background: #cbd5e125; color: #475569;">
                                <i class="fa-solid {{ $v['icon'] ?? 'fa-bug' }} fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <h6 class="fw-bold text-dark mb-0" style="font-family:'Cairo'; font-size:0.95rem;">{{ $v['title'] }}</h6>
                                    <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle rounded-pill px-2 py-0.5" style="font-size:0.68rem; font-family:'Cairo';">
                                        {{ $badgeText }}
                                    </span>
                                </div>
                                <p class="text-muted small mb-0" style="line-height: 1.5; font-size:0.8rem;">{{ $v['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ── Live Event Feed ──────────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0" style="font-family:'Cairo';">
                <i class="fa-solid fa-rss text-danger animate-pulse me-2"></i>
                التغذية الفورية للأحداث الأمنية (Live Threat Feed)
            </h5>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill fw-bold" style="font-size:0.8rem;">
                نشط فوري
            </span>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-family:'Outfit',sans-serif;">
                    <thead class="table-light text-muted small fw-bold" style="font-family:'Cairo';">
                        <tr>
                            <th class="px-4 py-3">المستخدم / المسؤول</th>
                            <th class="py-3">نوع الحدث</th>
                            <th class="py-3">مستوى الخطورة</th>
                            <th class="py-3">الوصف والبيانات</th>
                            <th class="py-3">عنوان IP</th>
                            <th class="py-3">التاريخ والوقت</th>
                            <th class="py-3 text-center">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($recentLogs->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fa-solid fa-circle-check text-success" style="font-size:2.5rem; opacity:0.4;"></i>
                                    <p class="text-muted mt-3 mb-0 fw-bold" style="font-family:'Cairo';">المنصة آمنة تماماً</p>
                                    <p class="text-muted small">لم يتم تسجيل أي خروقات أو خروقات أمنية في سجل الأمان</p>
                                </td>
                            </tr>
                        @else
                            @foreach($recentLogs as $log)
                                <?php
                                    $sevColors = [
                                        'info' => ['bg' => 'info', 'text' => 'info'],
                                        'warning' => ['bg' => 'warning', 'text' => 'warning'],
                                        'danger' => ['bg' => 'danger', 'text' => 'danger'],
                                        'critical' => ['bg' => 'danger', 'text' => 'danger']
                                    ];
                                    $sc = $sevColors[$log->severity] ?? ['bg' => 'secondary', 'text' => 'secondary'];
                                ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                 style="width:34px;height:34px;background:linear-gradient(135deg, #1e293b, #0f172a);color:#fff;font-family:'Cairo';font-weight:700;font-size:0.8rem;">
                                                {{ mb_strtoupper(mb_substr($log->user ? ($log->user->Nom ?? $log->user->NomUser) : 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark small">{{ $log->user ? ($log->user->Nom ?? $log->user->NomUser) : 'نظامي / تلقائي' }}</div>
                                                <div class="text-muted" style="font-size:0.75rem;">{{ $log->user ? $log->user->NomUser : 'system' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark small" style="font-family: 'Outfit';">{{ $log->event_type }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $sc['bg'] }}-subtle text-{{ $sc['text'] }} border border-{{ $sc['bg'] }}-subtle rounded-pill px-3 py-1 fw-bold small">
                                            {{ strtoupper($log->severity) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small" style="font-family:'Cairo';">{{ $log->description }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small" style="font-family:'Outfit';">
                                            <i class="fa-solid fa-network-wired text-primary me-1" style="font-size:0.7rem;"></i>
                                            {{ $log->ip_address }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small" style="font-family:'Outfit';">
                                            <i class="fa-regular fa-clock text-muted me-1" style="font-size:0.7rem;"></i>
                                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light rounded-pill px-3" 
                                                onclick="showLogDetail({{ json_encode($log) }})">
                                            <i class="fa-solid fa-eye text-primary"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 text-center">
            <a href="{{ request()->is('sig/*') ? '/sig/admin/security/logs' : '/admin/security/logs' }}" class="text-primary small fw-bold text-decoration-none" style="font-family: 'Cairo';">
                عرض وفلترة جميع السجلات الأمنية وتصديرها <i class="fa-solid fa-chevron-left ms-1" style="font-size:0.7rem;"></i>
            </a>
        </div>
    </div>

</div>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Trend Chart (Logins vs Threats)
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: {!! json_encode($days) !!},
            datasets: [
                {
                    label: 'تسجيل دخول ناجح',
                    data: {!! json_encode($loginTrend) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'تهديدات ومخاطر أمنية',
                    data: {!! json_encode($threatTrend) !!},
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            family: 'Cairo',
                            weight: 'bold'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // 2. Severity Distribution Chart
    const ctxSev = document.getElementById('severityChart').getContext('2d');
    const severityCounts = {!! json_encode($severityCounts) !!};
    
    new Chart(ctxSev, {
        type: 'doughnut',
        data: {
            labels: ['INFO', 'WARNING', 'DANGER', 'CRITICAL'],
            datasets: [{
                data: [
                    severityCounts['info'] || 0,
                    severityCounts['warning'] || 0,
                    severityCounts['danger'] || 0,
                    severityCounts['critical'] || 0
                ],
                backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444', '#7f1d1d'],
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
                        font: {
                            family: 'Cairo',
                            weight: 'bold',
                            size: 11
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // 3. Geographical Threat Origin Map
    try {
        const algeriaBounds = L.latLngBounds([18.0, -9.0], [38.0, 13.0]);
        const threatMap = L.map('security-threat-map', {
            center: [28.5, 2.5],
            zoom: 5,
            minZoom: 5,
            maxZoom: 8,
            maxBounds: algeriaBounds,
            maxBoundsViscosity: 1.0,
            scrollWheelZoom: false
        });

        // Background tile layer removed to hide Morocco/other surrounding countries.

        // Draw Algeria GeoJSON boundaries as the base layer
        function baseStyle(feature) {
            return {
                fillColor   : '#f8fafc',
                weight      : 1.0,
                opacity     : 1,
                color       : '#cbd5e1',
                fillOpacity : 0.8
            };
        }

        const geoData = {!! json_encode($geoJsonData) !!} || null;
        if (geoData) {
            L.geoJSON(geoData, {
                style: baseStyle
            }).addTo(threatMap);
        }

        const threats = {!! json_encode($threatLocations) !!} || [];
        threats.forEach(function(t) {
            let color = '#10b981'; // Green
            if (t.count > 10) {
                color = '#ef4444'; // Red
            } else if (t.count > 4) {
                color = '#f59e0b'; // Orange
            }

            const radius = Math.min(100000, Math.max(20000, t.count * 8000));

            const circle = L.circle([t.lat, t.lng], {
                color: color,
                fillColor: color,
                fillOpacity: 0.35,
                weight: 2,
                radius: radius
            }).addTo(threatMap);

            circle.bindPopup(`
                <div style="text-align:right; font-family:'Cairo';">
                    <h6 class="fw-bold mb-1 text-dark" style="font-size:0.9rem;">${t.name}</h6>
                    <p class="mb-0 small text-muted">عدد التهديدات / المحاولات الفاشلة: <strong class="text-danger">${t.count}</strong></p>
                </div>
            `);
        });
    } catch (err) {
        console.error('Threat map failed to load: ', err);
    }
});
</script>

<!-- Unprotected Admins Modal -->
<div class="modal fade" id="unprotectedAdminsModal" tabindex="-1" aria-labelledby="unprotectedAdminsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #ef4444, #b91c1c); border-bottom: none;">
                <h5 class="modal-title fw-bold" id="unprotectedAdminsModalLabel" style="font-family:'Cairo';">
                    <i class="fa-solid fa-user-shield me-2"></i>
                    حسابات المسؤولين غير المحمية بـ MFA
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <div class="alert alert-warning border-0 rounded-4 d-flex align-items-start gap-3 mb-4" style="background-color: #fef3c7; color: #92400e;">
                    <i class="fa-solid fa-triangle-exclamation fs-4 mt-1"></i>
                    <div>
                        <h6 class="fw-bold mb-1" style="font-family: 'Cairo';">تحذير أمني حرج!</h6>
                        <p class="small mb-0 opacity-90">الحسابات المدرجة أدناه تمتلك صلاحيات إدارية كاملة أو جزئية على النظام دون وجود طبقة حماية ثنائية. يُوصى بفرض تفعيل الـ MFA عليها فوراً لمنع مخاطر الاختراق.</p>
                    </div>
                </div>

                <!-- Global Action: Force MFA on all sensitive accounts -->
                <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded-4 border shadow-sm flex-wrap gap-2">
                    <span class="small text-dark fw-bold" style="font-family: 'Cairo';">أمن النظام بأكمله دفعة واحدة:</span>
                    <form method="POST" action="{{ request()->is('sig/*') ? '/sig/admin/security/mfa/global' : '/admin/security/mfa/global' }}">
                        @csrf
                        <input type="hidden" name="global_mode" value="sensitive">
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold" style="font-family: 'Cairo'; font-size: 0.8rem; background: linear-gradient(135deg, #ef4444, #dc2626); border: none;">
                            <i class="fa-solid fa-user-shield me-1"></i> فرض المصادقة على جميع الأدوار الحساسة
                        </button>
                    </form>
                </div>

                <div class="table-responsive rounded-4 border bg-white shadow-sm">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small fw-bold" style="font-family:'Cairo';">
                            <tr>
                                <th class="px-4 py-3">المسؤول</th>
                                <th class="py-3">اسم المستخدم (NomUser)</th>
                                <th class="py-3 text-center">الرمز التعريفي</th>
                                <th class="py-3 text-end px-4" style="width: 250px;">الإجراء الإداري</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($unprotectedAdmins->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small" style="font-family:'Cairo';">
                                        لا توجد حسابات غير محمية حالياً. جميع المسؤولين نشطوا المصادقة الثنائية!
                                    </td>
                                </tr>
                            @else
                                @foreach($unprotectedAdmins as $admin)
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                     style="width:34px;height:34px;background:linear-gradient(135deg, #ef4444, #991b1b);color:#fff;font-family:'Cairo';font-weight:700;font-size:0.8rem;">
                                                    {{ mb_strtoupper(mb_substr($admin->Nom ?? $admin->NomUser, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark small">{{ $admin->Nom ?? 'مسؤول غير محدد الاسم' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="text-danger fw-bold small" style="font-family: 'Outfit';">{{ $admin->NomUser }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 small" style="font-family:'Outfit';">#{{ $admin->IDUtilisateur }}</span>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick="copyAdminUsername('{{ $admin->NomUser }}')" style="font-family: 'Cairo'; font-size: 0.7rem;" title="نسخ اسم المستخدم">
                                                    <i class="fa-regular fa-copy"></i>
                                                </button>
                                                <form method="POST" action="{{ request()->is('sig/*') ? '/sig/admin/security/mfa/user/toggle' : '/admin/security/mfa/user/toggle' }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="user_key" value="utilisateur_{{ $admin->IDUtilisateur }}">
                                                    <button type="submit" name="action" value="force" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" style="font-family: 'Cairo'; font-size: 0.7rem; background: linear-gradient(135deg, #0f172a, #2563eb); border: none;">
                                                        <i class="fa-solid fa-lock me-1"></i> فرض الحماية
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" style="font-family:'Cairo';">إغلاق النافذة</button>
            </div>
        </div>
    </div>
</div>

<script>
function copyAdminUsername(username) {
    navigator.clipboard.writeText(username).then(function() {
        alert('تم نسخ اسم المستخدم بنجاح: ' + username);
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>

<!-- Details Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 text-white p-3" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                <h5 class="modal-title fw-bold" style="font-family:'Cairo';">
                    <i class="fa-solid fa-circle-info text-primary me-2"></i>
                    تفاصيل حدث التدقيق الأمني والملف الشخصي
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Event info table -->
                <div class="table-responsive rounded-4 border bg-white shadow-sm mb-4">
                    <table class="table table-hover align-middle mb-0 small">
                        <tbody>
                            <tr>
                                <th class="w-25 bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">معرف السجل</th>
                                <td id="modalId" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">نوع الحدث</th>
                                <td id="modalType" class="fw-bold text-dark" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">الخطورة</th>
                                <td id="modalSeverity"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">وصف الحدث</th>
                                <td id="modalDesc" class="text-muted" style="font-family:'Cairo';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">عنوان IP</th>
                                <td id="modalIp" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">بصمة الجهاز / المتصفح</th>
                                <td id="modalUa" class="small text-muted" style="font-family:'Outfit'; font-size: 0.75rem;"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">الوقت والتاريخ</th>
                                <td id="modalTime" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr id="modalHardwareRow" style="display: none;">
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">تفاصيل عتاد الجهاز</th>
                                <td class="p-0">
                                    <table class="table table-bordered table-sm mb-0 small border-0">
                                        <tbody>
                                            <tr>
                                                <th class="w-30 bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">نوع الجهاز / النظام</th>
                                                <td id="modalHardwarePlatform" style="font-family:'Outfit';"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">المعالج (CPU)</th>
                                                <td id="modalHardwareCpu" style="font-family:'Cairo'; font-size: 0.8rem;"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">الذاكرة (RAM)</th>
                                                <td id="modalHardwareRam" style="font-family:'Outfit'; font-weight: bold;"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">كرت الشاشة (GPU)</th>
                                                <td id="modalHardwareGpu" class="small text-muted" style="font-family:'Outfit'; font-size: 0.75rem;"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">دقة الشاشة</th>
                                                <td id="modalHardwareRes" style="font-family:'Outfit';"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr id="modalMetaRow">
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">بيانات تقنية إضافية</th>
                                <td>
                                    <pre class="bg-light rounded-3 p-3 small mb-0" id="modalMeta" style="font-family:'Outfit'; direction:ltr; text-align:left; overflow:auto; max-height:150px; font-size: 0.75rem;"></pre>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Rich User Profile Section -->
                <div id="modalUserSection" class="p-3 rounded-4 border bg-white shadow-sm" style="display: none;">
                    <h6 class="fw-bold mb-3 text-dark border-bottom pb-2" style="font-family:'Cairo';">
                        <i class="fa-solid fa-user-shield text-primary me-2"></i>
                        بيانات وملف المستخدم الكاملة (User Audited Profile)
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm mb-0 small">
                                <tbody>
                                    <tr>
                                        <th class="w-40 bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">الاسم الكامل:</th>
                                        <td id="userFullname" class="text-dark"></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">اسم المستخدم:</th>
                                        <td id="userUsername" class="fw-bold text-primary" style="font-family:'Outfit';"></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">الرمز التعريفي (ID):</th>
                                        <td id="userDbId" class="text-secondary" style="font-family:'Outfit';"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm mb-0 small">
                                <tbody>
                                    <tr>
                                        <th class="w-40 bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">جدول قاعدة البيانات:</th>
                                        <td><span id="userTableSource" class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded-pill" style="font-family:'Cairo'; font-size:0.75rem;"></span></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">الصفة والدور:</th>
                                        <td id="userRoleType" class="text-dark" style="font-family:'Cairo';"></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">حالة حماية الـ MFA:</th>
                                        <td><span id="userMfaStatus" style="font-size:0.75rem; font-family:'Cairo';"></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" style="font-family:'Cairo';">إغلاق النافذة</button>
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetail(log) {
    document.getElementById('modalId').textContent = log.id;
    document.getElementById('modalType').textContent = log.event_type;
    document.getElementById('modalSeverity').textContent = log.severity.toUpperCase();
    document.getElementById('modalDesc').textContent = log.description;
    document.getElementById('modalIp').textContent = log.ip_address;
    document.getElementById('modalUa').textContent = log.user_agent;
    document.getElementById('modalTime').textContent = log.created_at;

    // Severity badge style
    const severityEl = document.getElementById('modalSeverity');
    let badgeClass = 'badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1 fw-bold';
    if (log.severity === 'info') badgeClass = 'badge bg-info-subtle text-info border rounded-pill px-3 py-1 fw-bold';
    else if (log.severity === 'warning') badgeClass = 'badge bg-warning-subtle text-warning border rounded-pill px-3 py-1 fw-bold';
    else if (log.severity === 'danger' || log.severity === 'critical') badgeClass = 'badge bg-danger-subtle text-danger border rounded-pill px-3 py-1 fw-bold';
    severityEl.innerHTML = `<span class="${badgeClass}">${log.severity.toUpperCase()}</span>`;

    // Extract user details (from metadata user_details or fallback to relation)
    const userSection = document.getElementById('modalUserSection');
    let hasUserDetails = false;

    if (log.metadata && log.metadata.user_details) {
        hasUserDetails = true;
        const u = log.metadata.user_details;
        document.getElementById('userFullname').textContent = u.name || 'غير محدد';
        document.getElementById('userUsername').textContent = u.username || 'غير محدد';
        document.getElementById('userDbId').textContent = '#' + (u.id || '');
        document.getElementById('userTableSource').textContent = u.table === 'utilisateur' ? 'جدول المسؤولين (utilisateur)' : (u.table === 'etablissement' ? 'جدول المؤسسات (etablissement)' : 'جدول المؤطرين (encadrement)');
        document.getElementById('userRoleType').textContent = u.role || 'مستعمل المنصة';
        
        const mfaBadge = document.getElementById('userMfaStatus');
        mfaBadge.className = u.mfa === 'مفعل' ? 'badge bg-success-subtle text-success border px-2 py-0.5 rounded-pill' : 'badge bg-danger-subtle text-danger border px-2 py-0.5 rounded-pill';
        mfaBadge.textContent = u.mfa;
    } else if (log.user) {
        hasUserDetails = true;
        document.getElementById('userFullname').textContent = log.user.Nom || 'مسؤول النظام';
        document.getElementById('userUsername').textContent = log.user.NomUser || 'admin';
        document.getElementById('userDbId').textContent = '#' + log.user.IDUtilisateur;
        document.getElementById('userTableSource').textContent = 'جدول المسؤولين (utilisateur)';
        
        let roleName = 'مستعمل المنصة';
        if (log.user.IDNature == 1) roleName = 'مدير النظام (Admin)';
        else if (log.user.IDNature == 2) roleName = 'إدارة مركزية (Central)';
        else if (log.user.IDNature == 4) roleName = 'مديرية التكوين المهني (DFEP)';
        document.getElementById('userRoleType').textContent = roleName;
        
        const mfaBadge = document.getElementById('userMfaStatus');
        mfaBadge.className = log.user.mfa_enabled ? 'badge bg-success-subtle text-success border px-2 py-0.5 rounded-pill' : 'badge bg-danger-subtle text-danger border px-2 py-0.5 rounded-pill';
        mfaBadge.textContent = log.user.mfa_enabled ? 'مفعل' : 'غير مفعل';
    }

    if (hasUserDetails) {
        userSection.style.display = 'block';
    } else {
        userSection.style.display = 'none';
    }

    // Extract hardware details
    const hwRow = document.getElementById('modalHardwareRow');
    if (log.metadata && log.metadata.hardware_specs) {
        const hw = log.metadata.hardware_specs;
        hwRow.style.display = '';
        document.getElementById('modalHardwarePlatform').textContent = hw.platform || 'غير معروف';
        
        const cores = hw.cpu_cores || 'غير معروف';
        document.getElementById('modalHardwareCpu').textContent = cores !== 'غير معروف' ? (cores + ' أنوية منطقية (Logical Cores)') : 'غير معروف';
        
        document.getElementById('modalHardwareRam').textContent = hw.ram_size || 'غير معروف';
        document.getElementById('modalHardwareGpu').textContent = hw.gpu || 'غير معروف';
        document.getElementById('modalHardwareRes').textContent = hw.screen_res || 'غير معروف';
    } else {
        hwRow.style.display = 'none';
    }

    // Clean up metadata printing (omit user_details and hardware_specs payloads to avoid clutter)
    if (log.metadata) {
        const printMeta = { ...log.metadata };
        delete printMeta.user_details;
        delete printMeta.hardware_specs;
        
        if (Object.keys(printMeta).length > 0) {
            document.getElementById('modalMetaRow').style.display = '';
            document.getElementById('modalMeta').textContent = JSON.stringify(printMeta, null, 2);
        } else {
            document.getElementById('modalMetaRow').style.display = 'none';
        }
    } else {
        document.getElementById('modalMetaRow').style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
}
</script>

<style>
.text-indigo { color: #6366f1; }
.bg-indigo-subtle { background-color: #e0e7ff; }
.animate-pulse {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
</style>
@endsection
