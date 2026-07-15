@extends('layouts.main')

@section('styles')
<style>
    :root {
        --adm-primary:  #1a2942;
        --adm-accent:   #3b7dd8;
        --adm-gold:     #f0a500;
        --adm-bg:       #f0f4fa;
    }
    body { background-color: var(--adm-bg) !important; }

    .admin-header {
        background: linear-gradient(135deg, var(--adm-primary) 0%, #243b5c 100%);
        border-radius: 16px;
        padding: 2rem 2.5rem;
        color: white;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(59,125,216,0.25);
        box-shadow: 0 8px 30px rgba(26,41,66,0.18);
    }
    .admin-header::before {
        content: '';
        position: absolute;
        top: -60%; right: -5%;
        width: 280px; height: 280px;
        background: radial-gradient(circle, rgba(59,125,216,0.2) 0%, transparent 70%);
        border-radius: 50%;
    }
    .admin-badge {
        background: rgba(59,125,216,0.15);
        color: #7eb3f5;
        padding: 0.4rem 1.2rem;
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 700;
        border: 1px solid rgba(59,125,216,0.3);
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    @keyframes cardIn {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .kpi-adm {
        background: #fff;
        border-radius: 14px;
        padding: 1.1rem 1.3rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        border: 1px solid #e2eaf5;
        border-right: 4px solid var(--adm-accent) !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        opacity: 0;
        animation: cardIn 0.45s ease forwards;
    }
    .kpi-adm:hover { transform: translateY(-4px); box-shadow: 0 8px 22px rgba(59,125,216,0.12); }
    .kpi-adm.gold  { border-right-color: var(--adm-gold) !important; }
    .kpi-adm.green { border-right-color: #10b981 !important; }
    .kpi-adm.red   { border-right-color: #ef4444 !important; }
    .kpi-adm.dark  { border-right-color: var(--adm-primary) !important; }
    .kpi-adm.cyan  { border-right-color: #06b6d4 !important; }

    .kpi-val {
        font-size: 1.85rem;
        font-weight: 800;
        font-family: 'Outfit','Cairo',sans-serif;
        line-height: 1.1;
        color: var(--adm-primary);
        margin-top: 0.25rem;
    }
    .kpi-lbl {
        font-size: 0.76rem;
        font-weight: 700;
        color: #64748b;
        font-family: 'Cairo', sans-serif;
    }
    .kpi-sub { font-size: 0.7rem; color: #94a3b8; margin-top: 3px; }

    .sec-card {
        background: #fff;
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        border: 1px solid #e2eaf5;
        margin-bottom: 1.5rem;
    }
    .sec-title {
        font-family: 'Cairo', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--adm-primary);
        padding-bottom: 0.6rem;
        border-bottom: 2px solid #e2eaf5;
        margin-bottom: 1rem;
    }
    .row-lbl {
        font-family: 'Cairo', sans-serif;
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--adm-accent);
        margin-bottom: 0.5rem;
    }

    .adm-table th {
        background: var(--adm-primary);
        color: white;
        font-family: 'Cairo',sans-serif;
        font-size: 0.8rem;
        padding: 9px 13px;
    }
    .adm-table td {
        vertical-align: middle;
        padding: 9px 13px;
        font-size: 0.87rem;
        border-bottom: 1px solid #f1f5f9;
    }
</style>
@endsection

@section('content')
<div class="container-fluid animate__animated animate__fadeIn" dir="rtl">

    {{-- ═══ Header ═══ --}}
    <div class="admin-header mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="admin-badge mb-2"><i class="fa-solid fa-shield-halved"></i> لوحة مدير النظام الإحصائية</div>
                <h2 class="fw-bold mb-1" style="font-family:'Cairo';">مرحباً، {{ $user['nom_complet'] ?? $user['username'] ?? 'المدير' }}</h2>
                <p class="mb-0" style="opacity:0.8;font-size:0.95rem;">نظرة شاملة وآنية على إحصائيات المنصة الوطنية لتسيير التكوين المهني.</p>
            </div>
            <div class="text-end">
                <div style="color:rgba(255,255,255,0.5);font-size:0.75rem;" class="mb-1">آخر تحديث</div>
                <div class="fw-bold" style="font-family:'Outfit';font-size:1.05rem;">{{ date('Y-m-d H:i') }}</div>
                <button onclick="window.print()" class="btn btn-sm btn-outline-light mt-2 rounded-pill px-3">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ SECTION 1: المتربصون ═══ --}}
    <div class="sec-card">
        <h4 class="sec-title"><i class="fa-solid fa-users text-primary me-2"></i> إحصائيات المتربصين والمتمهنين (Trainees & Apprentices)</h4>
        <div class="row g-3">
            <!-- Explain Card / Sessions Breakdown -->
            <div class="col-xl-4 col-12">
                <div class="card h-100 border-0 shadow-sm" style="background: #fff; border-right: 4px solid var(--adm-primary) !important; border: 1px solid rgba(26, 41, 66, 0.08);">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3" style="font-family:'Cairo'; color: var(--adm-primary);"><i class="fa-solid fa-chart-pie"></i> تفصيل المتربصين النشطين حسب الدورة</h6>
                        <div class="d-flex flex-column gap-2">
                            @foreach($sessions_breakdown ?? [] as $index => $sb)
                            @php
                                $sb = (object)$sb;
                                $sem = 1 + $index;
                                $badgeClass = match($sem) {
                                    1 => 'bg-primary',
                                    2 => 'bg-success',
                                    3 => 'bg-info text-dark',
                                    4 => 'bg-warning text-dark',
                                    default => 'bg-danger'
                                };
                            @endphp
                            <div class="d-flex justify-content-between align-items-center p-2 rounded-3" style="background: rgba(26, 41, 66, 0.03);">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $badgeClass }}">S{{ $sem }}</span>
                                    <span class="small fw-bold" style="font-family:'Cairo';">{{ $sb->Nom }}</span>
                                </div>
                                <span class="fw-bold text-dark small" style="font-family:'Outfit';">{{ number_format($sb->count) }}</span>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-3 pt-2 border-top text-muted" style="font-size: 0.78rem; line-height: 1.4;">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            <strong>ملاحظة:</strong> مجموع تفصيل الدورات أعلاه يمثل المتربصين في أهم 5 دورات نشطة (السنوات 2024، 2025، 2026). الفارق البسيط عن الإجمالي العام يعود لمتربصين مستمرين من دورات سابقة (ما قبل سنة 2024) لا تزال وضعيتهم البيداغوجية نشطة بصفة رسمية.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cards Grid -->
            <div class="col-xl-8 col-12">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="kpi-adm h-100" style="animation-delay:0ms">
                            <div class="kpi-lbl">إجمالي المتربصين النشطين</div>
                            <div class="kpi-val text-primary" data-counter="{{ $total_stagiaires ?? 0 }}">0</div>
                            <div class="kpi-sub"><i class="fa-solid fa-users text-primary"></i> مقيدون بالدراسة</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-adm green h-100" style="animation-delay:60ms">
                            <div class="kpi-lbl">المتربصون المستمرون S2→S5</div>
                            <div class="kpi-val text-success" data-counter="{{ $total_reconduits ?? 0 }}">0</div>
                            <div class="kpi-sub"><i class="fa-solid fa-arrows-spin text-success"></i> أقسام مستمرة</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-adm red h-100" style="animation-delay:120ms">
                            <div class="kpi-lbl">المتربصات — إناث</div>
                            <div class="kpi-val text-danger" data-counter="{{ $total_filles ?? 0 }}">0</div>
                            <div class="kpi-sub"><i class="fa-solid fa-venus text-danger"></i> نسبة تمثيل الإناث</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kpi-adm cyan h-100" style="animation-delay:180ms">
                            <div class="kpi-lbl">الأقسام الجديدة S1</div>
                            <div class="kpi-val text-info" data-counter="{{ $total_sections_s1 ?? 0 }}">0</div>
                            <div class="kpi-sub"><i class="fa-solid fa-folder-plus text-info"></i> الدورة الحالية</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="kpi-adm gold h-100" style="animation-delay:240ms">
                            <div class="kpi-lbl">إجمالي الخريجين (الناجحين)</div>
                            <div class="kpi-val text-warning" data-counter="{{ $total_graduates ?? 0 }}">0</div>
                            <div class="kpi-sub"><i class="fa-solid fa-graduation-cap text-warning"></i> حائزو شهادات التخرج</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ SECTION 2: الموارد البشرية ═══ --}}
    <div class="sec-card">
        <h4 class="sec-title"><i class="fa-solid fa-user-tie text-primary me-2"></i> الموارد البشرية (Human Resources)</h4>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="kpi-adm dark" style="animation-delay:240ms">
                    <div class="kpi-lbl">إجمالي المسجلين بالقطاع</div>
                    <div class="kpi-val" data-counter="{{ $total_encadrements ?? 0 }}">0</div>
                    <div class="kpi-sub"><i class="fa-solid fa-address-book"></i> إطار وموظف ومكوّن</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm green" style="animation-delay:300ms">
                    <div class="kpi-lbl">الحسابات المفعّلة (نشطة)</div>
                    <div class="kpi-val text-success" data-counter="{{ $count_active_staff ?? 0 }}">0</div>
                    <div class="kpi-sub"><i class="fa-solid fa-user-check text-success"></i> أتمّوا التسجيل</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm gold" style="animation-delay:360ms">
                    <div class="kpi-lbl">حسابات قيد التفعيل</div>
                    <div class="kpi-val text-warning" data-counter="{{ $count_inactive_staff ?? 0 }}">0</div>
                    <div class="kpi-sub"><i class="fa-solid fa-hourglass-half text-warning"></i> لم يستكملوا بعد</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ SECTION 3: الهياكل ═══ --}}
    <div class="sec-card">
        <h4 class="sec-title"><i class="fa-solid fa-building-columns text-primary me-2"></i> الهياكل التكوينية (Institutions)</h4>

        <div class="row-lbl"><i class="fa-solid fa-graduation-cap"></i> مؤسسات التكوين الأساسية:</div>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="kpi-adm" style="animation-delay:420ms">
                    <div class="kpi-lbl">مراكز التكوين والتمهين (CFPA)</div>
                    <div class="kpi-val text-primary" data-counter="{{ $count_cfpa ?? 0 }}">0</div>
                    <div class="kpi-sub">مراكز التكوين المهني والتمهين</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm dark" style="animation-delay:480ms">
                    <div class="kpi-lbl">المعاهد الوطنية المتخصصة (INSFP)</div>
                    <div class="kpi-val" data-counter="{{ $count_insfp ?? 0 }}">0</div>
                    <div class="kpi-sub">معاهد التكوين المتخصصة العالية</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm gold" style="animation-delay:540ms">
                    <div class="kpi-lbl">المؤسسات الخاصة المعتمدة</div>
                    <div class="kpi-val text-warning" data-counter="{{ $count_private ?? 0 }}">0</div>
                    <div class="kpi-sub">المدارس الخاصة المرخّصة</div>
                </div>
            </div>
        </div>

        <div class="row-lbl"><i class="fa-solid fa-book-open-reader"></i> معاهد التكوين البيداغوجي:</div>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="kpi-adm green" style="animation-delay:600ms">
                    <div class="kpi-lbl">معاهد التعليم المهني (IEP)</div>
                    <div class="kpi-val text-success" data-counter="{{ $count_iep ?? 0 }}">0</div>
                    <div class="kpi-sub">معاهد التعليم والتوجيه المهني</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm green" style="animation-delay:660ms">
                    <div class="kpi-lbl">معاهد تكوين الإطارات (IFEP)</div>
                    <div class="kpi-val text-success" data-counter="{{ $count_ifep ?? 0 }}">0</div>
                    <div class="kpi-sub">المعاهد البيداغوجية والتربوية</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm green" style="animation-delay:720ms">
                    <div class="kpi-lbl">المعهد الوطني للبحوث (INFEP)</div>
                    <div class="kpi-val text-success" data-counter="{{ $count_infep ?? 0 }}">0</div>
                    <div class="kpi-sub">المعهد الوطني للتكوين المهني</div>
                </div>
            </div>
        </div>

        <div class="row-lbl"><i class="fa-solid fa-circle-nodes"></i> هياكل الدعم والتسيير:</div>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="kpi-adm cyan" style="animation-delay:780ms">
                    <div class="kpi-lbl">فروع التكوين عن بعد (CNFEPD)</div>
                    <div class="kpi-val text-info" data-counter="{{ $count_cnfepd ?? 0 }}">0</div>
                    <div class="kpi-sub">فروع التعليم المهني عن بعد</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm cyan" style="animation-delay:840ms">
                    <div class="kpi-lbl">الملحقات والفروع المنتدبة</div>
                    <div class="kpi-val text-info" data-counter="{{ $count_annexes_fleurs ?? 0 }}">0</div>
                    <div class="kpi-sub">الفروع المنتدبة التابعة</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-adm dark" style="animation-delay:900ms">
                    <div class="kpi-lbl">المديريات الولائية (DFEP)</div>
                    <div class="kpi-val" data-counter="{{ $count_dfep ?? 0 }}">0</div>
                    <div class="kpi-sub">مديريات التكوين الولائية</div>
                </div>
            </div>
        </div>

        <div class="row-lbl"><i class="fa-solid fa-network-wired"></i> حالة الاتصال بالمنصة:</div>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="kpi-adm" style="animation-delay:960ms">
                    <div class="kpi-lbl">إجمالي الهياكل</div>
                    <div class="kpi-val text-primary" data-counter="{{ $total_etablissements ?? 0 }}">0</div>
                    <div class="kpi-sub">المسجّلة بقاعدة البيانات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-adm green" style="animation-delay:1020ms">
                    <div class="kpi-lbl">تمتلك حساب بالمنصة</div>
                    <div class="kpi-val text-success" data-counter="{{ $count_ets_with_account ?? 0 }}">0</div>
                    <div class="kpi-sub">حسابات دخول مفعّلة</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-adm green" style="animation-delay:1080ms">
                    <div class="kpi-lbl">مؤسسات نشطة</div>
                    <div class="kpi-val text-success" data-counter="{{ $count_ets_active ?? 0 }}">0</div>
                    <div class="kpi-sub">مفتوحة وتعمل حالياً</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-adm red" style="animation-delay:1140ms">
                    <div class="kpi-lbl">موقوفة إدارياً</div>
                    <div class="kpi-val text-danger" data-counter="{{ $count_ets_suspended ?? 0 }}">0</div>
                    <div class="kpi-sub">معلّقة ومجمّدة مؤقتاً</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ SECTION 4: قوائم المؤسسات ═══ --}}
    <div class="sec-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="sec-title mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> قوائم المؤسسات</h4>
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-3" data-bs-toggle="pill" data-bs-target="#adm-sus-pane" type="button" style="font-family:'Cairo';">
                        <i class="fa-solid fa-ban text-danger me-1"></i> موقوفة ({{ count($suspended_etabs ?? []) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3" data-bs-toggle="pill" data-bs-target="#adm-act-pane" type="button" onclick="loadAdminEtabs()" style="font-family:'Cairo';">
                        <i class="fa-solid fa-circle-check text-success me-1"></i> ناشطة ({{ number_format($count_ets_active ?? 0) }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            {{-- Suspended --}}
            <div class="tab-pane fade show active" id="adm-sus-pane">
                <div class="table-responsive" style="max-height:350px;overflow-y:auto;border:1px solid #e2eaf5;border-radius:8px;">
                    <table class="table table-hover table-bordered mb-0 adm-table text-end">
                        <thead><tr><th>اسم المؤسسة</th><th>الولاية</th><th>الطبيعة</th><th class="text-center">الحالة</th></tr></thead>
                        <tbody>
                            @forelse($suspended_etabs ?? [] as $se)
                            <tr>
                                <td class="fw-bold">{{ $se->nom }}</td>
                                <td>{{ $se->wilaya }}</td>
                                <td><span class="badge bg-secondary">{{ $se->nature }}</span></td>
                                <td class="text-center"><span class="badge bg-danger">موقوفة</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مؤسسات موقوفة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Active --}}
            <div class="tab-pane fade" id="adm-act-pane">
                <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
                    <input type="text" id="admSrch" class="form-control form-control-sm" placeholder="ابحث..." onkeyup="filterAdminEtabs()" style="max-width:260px;font-family:'Cairo';">
                    <select id="admAccF" class="form-select form-select-sm" onchange="filterAdminEtabs()" style="max-width:150px;font-family:'Cairo';">
                        <option value="all">كل الحسابات</option>
                        <option value="has">تمتلك حساب</option>
                        <option value="no">بدون حساب</option>
                    </select>
                </div>
                <div id="admSpinner" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted small">جاري التحميل...</p>
                </div>
                <div id="admTableWrap" class="table-responsive d-none" style="max-height:350px;overflow-y:auto;border:1px solid #e2eaf5;border-radius:8px;">
                    <table class="table table-hover table-bordered mb-0 adm-table text-end">
                        <thead><tr><th>اسم المؤسسة</th><th>الولاية</th><th>الطبيعة</th><th class="text-center">حالة الحساب</th></tr></thead>
                        <tbody id="admTbody"></tbody>
                    </table>
                </div>
                <div id="admEmpty" class="text-center py-4 text-muted d-none">
                    <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i> لا توجد نتائج.
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ SECTION: إحصائيات صب المنح وتفعيل الحسابات عبر الولايات ═══ --}}
    <div class="row g-3 mb-4">
        <!-- Trainee Bourses Wilaya Statistics -->
        @if(isset($bourseWilayaStats) && count($bourseWilayaStats) > 0)
        <div class="col-xl-6 col-12">
            <div class="sec-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid #e2eaf5;">
                    <h5 class="fw-bold text-dark m-0" style="font-family:'Cairo';border-right:4px solid var(--adm-accent);padding-right:0.6rem;">
                        <i class="fa-solid fa-graduation-cap text-primary me-2"></i>
                        مقارنة نسب صب المنح للمتربصين حسب الولايات
                    </h5>
                    <span class="badge bg-primary-subtle text-primary py-2 px-3 fw-bold" style="font-size:0.75rem;">
                        إجمالي الولايات: {{ count($bourseWilayaStats) }}
                    </span>
                </div>
                
                @php
                    $totalApprenantsNationwide = array_sum(array_column($bourseWilayaStats, 'total_apprenants'));
                    $totalPaidBoursesNationwide = array_sum(array_column($bourseWilayaStats, 'paid_bourses'));
                    $nationalPercentage = $totalApprenantsNationwide > 0 ? round(($totalPaidBoursesNationwide * 100) / $totalApprenantsNationwide, 1) : 0;
                @endphp

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:#fff; border:1px solid #e2eaf5; text-align:center;">
                            <div class="text-muted small fw-bold mb-1">المتربصين (الوطني)</div>
                            <h4 class="fw-bold m-0 text-primary" style="font-family:'Outfit','Cairo';">{{ number_format($totalApprenantsNationwide) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:#fff; border:1px solid #e2eaf5; text-align:center;">
                            <div class="text-muted small fw-bold mb-1">المستفيدين</div>
                            <h4 class="fw-bold m-0 text-success" style="font-family:'Outfit','Cairo';">{{ number_format($totalPaidBoursesNationwide) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:#fff; border:1px solid #e2eaf5; text-align:center;">
                            <div class="text-muted small fw-bold mb-1">نسبة التغطية</div>
                            <h4 class="fw-bold m-0 text-warning" style="font-family:'Outfit','Cairo';">{{ $nationalPercentage }}%</h4>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 280px; overflow-y: auto; border: 1px solid #e2eaf5; border-radius: 8px;">
                    <table class="table table-hover align-middle mb-0 text-center small text-end">
                        <thead style="position: sticky; top: 0; z-index: 5; background: #fff;">
                            <tr style="border-bottom: 2px solid #e2eaf5;">
                                <th class="text-end pb-2">الولاية</th>
                                <th class="pb-2 text-center">المتربصين</th>
                                <th class="pb-2 text-center">تم صبها</th>
                                <th class="pb-2 text-center">النسبة</th>
                                <th class="pb-2 text-center" style="width:120px;">مؤشر التقدم</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bourseWilayaStats as $w)
                            @php $w = (array)$w; @endphp
                            <tr>
                                <td class="text-end fw-bold text-dark py-2.5" style="font-family:'Cairo';">{{ $w['wilaya_nom'] }}</td>
                                <td class="py-2.5 text-center" style="font-family:'Outfit';">{{ number_format($w['total_apprenants']) }}</td>
                                <td class="py-2.5 text-center text-primary fw-bold" style="font-family:'Outfit';">{{ number_format($w['paid_bourses']) }}</td>
                                <td class="py-2.5 text-center text-success fw-bold" style="font-family:'Outfit';">{{ $w['percentage'] }}%</td>
                                <td class="py-2.5 text-center">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar {{ $w['percentage'] >= 100 ? 'bg-success' : ($w['percentage'] >= 50 ? 'bg-primary' : 'bg-warning') }}" role="progressbar" style="width: {{ min($w['percentage'], 100) }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Employee Account Activation Wilaya Statistics -->
        @if(isset($employeeWilayaStats) && count($employeeWilayaStats) > 0)
        <div class="col-xl-6 col-12">
            <div class="sec-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid #e2eaf5;">
                    <h5 class="fw-bold text-dark m-0" style="font-family:'Cairo';border-right:4px solid var(--adm-accent);padding-right:0.6rem;">
                        <i class="fa-solid fa-users-gear text-primary me-2"></i>
                        مقارنة نسب تفعيل حسابات الموظفين حسب الولايات
                    </h5>
                    <span class="badge bg-primary-subtle text-primary py-2 px-3 fw-bold" style="font-size:0.75rem;">
                        إجمالي الولايات: {{ count($employeeWilayaStats) }}
                    </span>
                </div>
                
                @php
                    $totalEmployeesNationwide = array_sum(array_column($employeeWilayaStats, 'total_employees'));
                    $totalActiveAccountsNationwide = array_sum(array_column($employeeWilayaStats, 'active_accounts'));
                    $nationalEmployeePercentage = $totalEmployeesNationwide > 0 ? round(($totalActiveAccountsNationwide * 100) / $totalEmployeesNationwide, 1) : 0;
                @endphp

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:#fff; border:1px solid #e2eaf5; text-align:center;">
                            <div class="text-muted small fw-bold mb-1">إجمالي موظفي القطاع (الوطني)</div>
                            <h4 class="fw-bold m-0 text-primary" style="font-family:'Outfit','Cairo';">{{ number_format($totalEmployeesNationwide) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:#fff; border:1px solid #e2eaf5; text-align:center;">
                            <div class="text-muted small fw-bold mb-1">الحسابات النشطة</div>
                            <h4 class="fw-bold m-0 text-success" style="font-family:'Outfit','Cairo';">{{ number_format($totalActiveAccountsNationwide) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4" style="background:#fff; border:1px solid #e2eaf5; text-align:center;">
                            <div class="text-muted small fw-bold mb-1">نسبة التفعيل</div>
                            <h4 class="fw-bold m-0 text-warning" style="font-family:'Outfit','Cairo';">{{ $nationalEmployeePercentage }}%</h4>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 280px; overflow-y: auto; border: 1px solid #e2eaf5; border-radius: 8px;">
                    <table class="table table-hover align-middle mb-0 text-center small text-end">
                        <thead style="position: sticky; top: 0; z-index: 5; background: #fff;">
                            <tr style="border-bottom: 2px solid #e2eaf5;">
                                <th class="text-end pb-2">الولاية</th>
                                <th class="pb-2 text-center">إجمالي الموظفين</th>
                                <th class="pb-2 text-center">الحسابات النشطة</th>
                                <th class="pb-2 text-center">نسبة التفعيل</th>
                                <th class="pb-2 text-center" style="width:120px;">مؤشر التقدم</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employeeWilayaStats as $w)
                            @php $w = (array)$w; @endphp
                            <tr>
                                <td class="text-end fw-bold text-dark py-2.5" style="font-family:'Cairo';">{{ $w['wilaya_nom'] }}</td>
                                <td class="py-2.5 text-center" style="font-family:'Outfit';">{{ number_format($w['total_employees']) }}</td>
                                <td class="py-2.5 text-center text-primary fw-bold" style="font-family:'Outfit';">{{ number_format($w['active_accounts']) }}</td>
                                <td class="py-2.5 text-center text-success fw-bold" style="font-family:'Outfit';">{{ $w['percentage'] }}%</td>
                                <td class="py-2.5 text-center">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar {{ $w['percentage'] >= 100 ? 'bg-success' : ($w['percentage'] >= 50 ? 'bg-primary' : 'bg-warning') }}" role="progressbar" style="width: {{ min($w['percentage'], 100) }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ═══ SECTION: إجراءات التحكم السريع والربط وبوابة الأرشيف ═══ --}}
    <div class="row g-3 mb-4">
        <!-- System Status -->
        <div class="col-xl-4 col-12">
            <div class="sec-card h-100">
                <h5 class="fw-bold mb-3 text-dark" style="border-right: 3px solid var(--adm-accent); padding-right: 0.5rem; font-family:'Cairo';">
                    <i class="fa-solid fa-server me-1 text-primary"></i> حالة اتصال النظام والربط
                </h5>
                <div class="d-flex flex-column gap-3 mt-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-bold text-muted">المطابقة البيداغوجية / APIs</span>
                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-circle-check me-1"></i> Windev Ready</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-bold text-muted">الربط المركزي / Mainframe</span>
                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-circle-check me-1"></i> مستقر</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-bold text-muted">التوقيع الرقمي / SSL</span>
                        <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-circle-check me-1"></i> نشط</span>
                    </div>
                </div>
                <div class="mt-4 pt-3" style="border-top:1px solid #e2eaf5;">
                    <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo';"><i class="fa-solid fa-bolt me-1 text-primary"></i> إجراءات التحكم السريع</h6>
                    <a href="/dashboard/users" class="btn btn-light w-100 text-start py-2 px-3 d-flex align-items-center justify-content-between rounded-3 border">
                        <span><i class="fa-solid fa-user-plus me-2 text-primary"></i>إضافة حساب موظف</span>
                        <i class="fa-solid fa-chevron-left text-muted" style="font-size:0.7rem;"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Historical Archive Portal -->
        <div class="col-xl-4 col-12">
            <div class="sec-card h-100 d-flex flex-column justify-content-between" style="border-right: 4px solid var(--adm-accent) !important;">
                <div>
                    <h5 class="fw-bold mb-3 text-dark" style="border-right: 3px solid var(--adm-accent); padding-right: 0.5rem; font-family:'Cairo';">
                        <i class="fa-solid fa-box-archive me-1 text-primary"></i> بوابة الأرشيف الوطني (HFSQL)
                    </h5>
                    <p class="text-muted small mb-3" style="font-weight: 600; line-height: 1.6; font-family:'Cairo';">
                        الولوج إلى قاعدة البيانات التاريخية لاستعراض إحصائيات التكوين عبر الولايات.
                    </p>
                </div>
                <a href="/dashboard/archive" class="btn btn-primary w-100 mt-4 py-2.5 fw-bold" style="border-radius:12px; background:linear-gradient(135deg, var(--adm-accent) 0%, #0d6efd 100%); border:none; font-family:'Cairo';">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> دخول بوابة الأرشيف
                </a>
            </div>
        </div>

        <!-- Quick Links cards -->
        <div class="col-xl-4 col-12">
            <div class="d-flex flex-column gap-2 h-100">
                <!-- Audit Logs Card -->
                <a href="{{ url('dashboard/audit-logs') }}" class="text-decoration-none d-block">
                    <div class="p-3 d-flex align-items-center justify-content-between border rounded-3 bg-white" style="transition:transform .2s,box-shadow .2s; border-right:4px solid #1A6BCC !important;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='';">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(26,107,204,0.1);">
                                <i class="fa-solid fa-list-check text-primary"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0" style="font-family:'Cairo'; font-size:0.88rem;">سجل العمليات (Audit Logs)</h6>
                                <p class="text-muted small mb-0" style="font-size:0.75rem;">متابعة جميع العمليات وآخر دخول للمستخدمين</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-left text-primary"></i>
                    </div>
                </a>

                <!-- Employee Space Card -->
                <a href="{{ url('dashboard/espace-employe') }}" class="text-decoration-none d-block">
                    <div class="p-3 d-flex align-items-center justify-content-between border rounded-3 bg-white" style="transition:transform .2s,box-shadow .2s; border-right:4px solid #0EA66E !important;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='';">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(14,166,110,0.1);">
                                <i class="fa-solid fa-briefcase text-success"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0" style="font-family:'Cairo'; font-size:0.88rem;">فضاء الموظف (Espace Employé)</h6>
                                <p class="text-muted small mb-0" style="font-size:0.75rem;">محاكاة واجهة الموظف وخدماته الإدارية</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-left text-success"></i>
                    </div>
                </a>

                <!-- API Center Card -->
                <a href="{{ url('dashboard/api-center') }}" class="text-decoration-none d-block">
                    <div class="p-3 d-flex align-items-center justify-content-between border rounded-3 bg-white" style="transition:transform .2s,box-shadow .2s; border-right:4px solid #F0A500 !important;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='';">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(240,165,0,0.1);">
                                <i class="fa-solid fa-satellite-dish text-warning"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0" style="font-family:'Cairo'; font-size:0.88rem;">مركز الاتصال الرقمي (API Center)</h6>
                                <p class="text-muted small mb-0" style="font-size:0.75rem;">إدارة مفاتيح API وربط المنصة بالخدمات الخارجية</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-left text-warning"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- ═══ SECTION 5: سجل الدخول ═══ --}}
    <div class="sec-card">
        <h4 class="sec-title"><i class="fa-solid fa-shield-halved text-primary me-2"></i> سجل الدخول الأخير (Audit Log)</h4>
        <div class="table-responsive">
            <table class="table table-hover adm-table mb-0">
                <thead>
                    <tr><th>المستخدم</th><th>التاريخ والوقت</th><th>النشاط</th><th>IP</th><th class="text-center">الحالة</th></tr>
                </thead>
                <tbody>
                    @forelse($audit_logs ?? [] as $log)
                    @php $log = (array)$log; @endphp
                    <tr>
                        <td class="fw-bold">{{ $log['nom_complet'] ?? $log['username'] ?? '—' }}</td>
                        <td style="font-family:'Outfit';">{{ $log['created_at'] ?? '' }}</td>
                        <td>{{ $log['action'] ?? 'دخول' }}</td>
                        <td style="font-family:'Outfit';">{{ $log['iplocal'] ?? '—' }}</td>
                        <td class="text-center">
                            @if(($log['status'] ?? '') === 'success')
                                <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> دخول ناجح</span>
                            @else
                                <span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i> دخول فاشل</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">لا توجد سجلات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Counter engine ───
    function animateCounter(el, target, duration) {
        const start = performance.now();
        const ease  = t => 1 - Math.pow(1 - t, 3);
        (function step(now) {
            const p = Math.min((now - start) / duration, 1);
            el.textContent = Math.round(ease(p) * target).toLocaleString('en');
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('en');
        })(start);
    }
    document.querySelectorAll('.kpi-adm').forEach((c, i) => { c.style.animationDelay = (i * 50) + 'ms'; });
    const counters = document.querySelectorAll('[data-counter]');
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const el = e.target, t = parseInt(el.dataset.counter, 10) || 0;
                    animateCounter(el, t, Math.min(600 + Math.sqrt(t) * 2, 1800));
                    obs.unobserve(el);
                }
            });
        }, { threshold: 0.2 });
        counters.forEach(el => obs.observe(el));
    } else {
        counters.forEach(el => animateCounter(el, parseInt(el.dataset.counter, 10) || 0, 1200));
    }

    // ─── Active Etabs AJAX ───
    let admData = [];
    window.loadAdminEtabs = function() {
        if (admData.length > 0) return;
        document.getElementById('admSpinner').style.display = 'block';
        fetch("{{ url('/dashboard?ajax_active_etabs=1') }}")
            .then(r => r.json())
            .then(data => {
                admData = data;
                document.getElementById('admSpinner').style.display = 'none';
                document.getElementById('admTableWrap').classList.remove('d-none');
                renderAdmEtabs(admData);
            })
            .catch(() => {
                document.getElementById('admSpinner').innerHTML = '<div class="text-danger">فشل التحميل. أعد المحاولة.</div>';
            });
    };
    function renderAdmEtabs(list) {
        const tbody = document.getElementById('admTbody');
        const empty = document.getElementById('admEmpty');
        const wrap  = document.getElementById('admTableWrap');
        tbody.innerHTML = '';
        if (!list.length) { wrap.classList.add('d-none'); empty.classList.remove('d-none'); return; }
        wrap.classList.remove('d-none'); empty.classList.add('d-none');
        list.forEach(item => {
            const tr = document.createElement('tr');
            const badge = item.has_account === 1
                ? '<span class="badge bg-success">تمتلك حساب</span>'
                : '<span class="badge bg-warning text-dark">بدون حساب</span>';
            tr.innerHTML = `<td class="fw-bold">${item.nom}</td><td>${item.wilaya}</td><td><span class="badge bg-secondary">${item.nature}</span></td><td class="text-center">${badge}</td>`;
            tbody.appendChild(tr);
        });
    }
    window.filterAdminEtabs = function() {
        const q   = document.getElementById('admSrch').value.toLowerCase().trim();
        const acc = document.getElementById('admAccF').value;
        let f = admData;
        if (q)         f = f.filter(i => (i.nom||'').toLowerCase().includes(q) || (i.wilaya||'').toLowerCase().includes(q));
        if (acc==='has') f = f.filter(i => i.has_account === 1);
        if (acc==='no')  f = f.filter(i => i.has_account !== 1);
        renderAdmEtabs(f);
    };
});
</script>
@endsection
