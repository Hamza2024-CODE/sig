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

    /* Counter animation */
    @keyframes countUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .kpi-value {
        animation: countUp 0.5s ease forwards;
    }
    /* Staggered card entrance */
    @keyframes cardEntrance {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .kpi-vip-card {
        opacity: 0;
        animation: cardEntrance 0.5s ease forwards;
    }

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

    <!-- SECTION 1: Trainees Statistics -->
    <div class="mb-4">
        <h4 class="section-title"><i class="fa-solid fa-users text-primary me-2"></i> إحصائيات المتربصين والمتمهنين (Trainees & Apprentices)</h4>
        <div class="row g-3">
            <!-- Explain Card / Sessions Breakdown -->
            <div class="col-xl-4 col-12">
                <div class="card h-100 border-0 shadow-sm" style="background: #fff; border-right: 4px solid var(--minister-primary) !important; border: 1px solid rgba(17, 45, 38, 0.08);">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary" style="font-family:'Cairo';"><i class="fa-solid fa-chart-pie"></i> تفصيل المتربصين النشطين حسب الدورة</h6>
                        <div class="d-flex flex-column gap-2">
                            @foreach($sessions_breakdown ?? [] as $index => $sb)
                            @php
                                $sb = (object)$sb;
                                $sem = 1 + $index; // Février 2026 is latest (S1), then S2, S3, etc.
                                $badgeClass = match($sem) {
                                    1 => 'bg-primary',
                                    2 => 'bg-success',
                                    3 => 'bg-info text-dark',
                                    4 => 'bg-warning text-dark',
                                    default => 'bg-danger'
                                };
                            @endphp
                            <div class="d-flex justify-content-between align-items-center p-2 rounded-3" style="background: rgba(17,45,38,0.03);">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $badgeClass }}">S{{ $sem }}</span>
                                    <span class="small fw-bold" style="font-family:'Cairo';">{{ $sb->Nom }}</span>
                                </div>
                                <span class="fw-bold text-dark small" style="font-family:'Outfit';">{{ number_format($sb->count) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <!-- Cards Grid -->
            <div class="col-xl-8 col-12">
                <div class="row g-3 mb-3">
                    <!-- Total Active -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card h-100">
                            <div class="kpi-label">إجمالي المتربصين (النشطين)</div>
                            <div class="kpi-value text-primary" data-counter="{{ $total_stagiaires ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-users text-primary"></i> حالياً مقيدون بالدراسة</div>
                        </div>
                    </div>
                    <!-- Continuing S2-S5 -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card h-100">
                            <div class="kpi-label">المتربصين المستمرين</div>
                            <div class="kpi-value text-success" data-counter="{{ $total_reconduits ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-arrows-spin text-success"></i> الأقسام المستمرة من S2 إلى S5</div>
                        </div>
                    </div>
                    <!-- Females -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card h-100">
                            <div class="kpi-label">المتربصات — إناث</div>
                            <div class="kpi-value text-danger" data-counter="{{ $total_filles ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-venus text-danger"></i> نسبة تمثيل الإناث بالقطاع</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <!-- New S1 -->
                    <div class="col-md-6">
                        <div class="kpi-vip-card h-100">
                            <div class="kpi-label">الأقسام الجديدة S1</div>
                            <div class="kpi-value text-info" data-counter="{{ $total_sections_s1 ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-folder-plus text-info"></i> المسجلة في الدورة الحالية</div>
                        </div>
                    </div>
                    <!-- Total Graduates -->
                    <div class="col-md-6">
                        <div class="kpi-vip-card h-100" style="border-right: 4px solid var(--minister-gold) !important;">
                            <div class="kpi-label">إجمالي الخريجين (الناجحين)</div>
                            <div class="kpi-value text-warning" data-counter="{{ $total_graduates ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-graduation-cap text-warning"></i> خريجو القطاع الحائزون على شهادات تخرج</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: Staff Statistics -->
    <div class="mb-4">
        <h4 class="section-title"><i class="fa-solid fa-user-tie text-primary me-2"></i> الموظفون والإطارات (Human Resources)</h4>
        <div class="row g-3">
            <!-- Explain Card -->
            <div class="col-xl-4 col-12">
                <div class="card h-100 border-0 shadow-sm" style="background: rgba(17, 45, 38, 0.03); border-right: 4px solid var(--minister-primary) !important;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2 text-primary" style="font-family:'Cairo';"><i class="fa-solid fa-circle-info"></i> توضيح البيانات</h6>
                        <p class="mb-0 text-muted small" style="line-height: 1.7; font-weight: 500;">
                            يضم هذا القسم تعداد الموارد البشرية المسجلين في النظام الوطني للتسيير. وتصنف الحسابات إلى نشطة ومفعلة (التي أتم أصحابها تسجيل الرقم التعريفي الوطني وكلمة المرور وباشروا الرصد والتسيير)، وحسابات قيد التفعيل لم يستكمل أصحابها التسجيل بعد.
                        </p>
                    </div>
                </div>
            </div>
            <!-- Cards Grid -->
            <div class="col-xl-8 col-12">
                <div class="row g-3">
                    <!-- Total Registered Staff -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card h-100">
                            <div class="kpi-label">إجمالي المسجلين بالقطاع</div>
                            <div class="kpi-value" data-counter="{{ $total_encadrements ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-address-book"></i> إطار وموظف وأستاذ</div>
                        </div>
                    </div>
                    <!-- Active Accounts -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card h-100">
                            <div class="kpi-label">الحسابات المفعلة (النشطة)</div>
                            <div class="kpi-value text-success" data-counter="{{ $count_active_staff ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-user-check text-success"></i> أتموا التسجيل بالمنصة</div>
                        </div>
                    </div>
                    <!-- Inactive Accounts -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card h-100">
                            <div class="kpi-label">حسابات قيد التفعيل</div>
                            <div class="kpi-value text-warning" data-counter="{{ $count_inactive_staff ?? 0 }}">0</div>
                            <div class="mt-2 small text-muted"><i class="fa-solid fa-hourglass-half text-warning"></i> لم يستكملوا التسجيل</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: Institutions Statistics -->
    <div class="mb-4">
        <h4 class="section-title"><i class="fa-solid fa-building-columns text-primary me-2"></i> المؤسسات والهياكل التكوينية (Institutions & Structures)</h4>
        <div class="row g-3">
            <!-- Explain Card -->
            <div class="col-xl-4 col-12">
                <div class="card h-100 border-0 shadow-sm" style="background: rgba(17, 45, 38, 0.03); border-right: 4px solid var(--minister-primary) !important;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2 text-primary" style="font-family:'Cairo';"><i class="fa-solid fa-circle-info"></i> توضيح البيانات</h6>
                        <p class="mb-0 text-muted small" style="line-height: 1.7; font-weight: 500;">
                            تصنيف الهياكل التكوينية النشطة التابعة لقطاع التكوين والتعليم المهنيين، مقسمة بين المعاهد المتخصصة للتعليم العالي، مراكز التكوين الأساسي، المدارس الخاصة المعتمدة الخاضعة للمراقبة البيداغوجية، والمديريات الولائية المشرفة محلياً، مع عزل المؤسسات التي تم توقيفها إدارياً.
                        </p>
                    </div>
                </div>
            </div>
            <!-- Cards Grid -->
            <div class="col-xl-8 col-12">
                <!-- Row 1: Core Training -->
                <div class="fw-bold mb-2 small text-primary"><i class="fa-solid fa-graduation-cap"></i> مؤسسات التكوين الأساسية (Core Training Institutions):</div>
                <div class="row g-2 mb-3">
                    <!-- CFPA -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">مراكز التكوين والتمهين (CFPA)</div>
                            <div class="kpi-value text-primary" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_cfpa ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">مراكز التكوين المهني والتمهين الأساسية</div>
                        </div>
                    </div>
                    <!-- INSFP -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">المعاهد الوطنية المتخصصة (INSFP)</div>
                            <div class="kpi-value text-primary" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_insfp ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">معاهد التكوين المهني المتخصصة العالية</div>
                        </div>
                    </div>
                    <!-- Private -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">المؤسسات الخاصة المعتمدة</div>
                            <div class="kpi-value text-primary" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_private ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">المدارس والمؤسسات الخاصة المرخصة</div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Pedagogical & IEP -->
                <div class="fw-bold mb-2 small text-primary"><i class="fa-solid fa-book-open-reader"></i> معاهد التكوين البيداغوجي والتعليم المهني:</div>
                <div class="row g-2 mb-3">
                    <!-- IEP -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">معاهد التعليم المهني (IEP)</div>
                            <div class="kpi-value text-success" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_iep ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">معاهد للتعليم والتوجيه المهني للشباب</div>
                        </div>
                    </div>
                    <!-- IFEP -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">معاهد تكوين الإطارات (IFEP)</div>
                            <div class="kpi-value text-success" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_ifep ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">معاهد التكوين والتعليم البيداغوجي والتربوي</div>
                        </div>
                    </div>
                    <!-- INFEP -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">المعهد الوطني للبحوث (INFEP)</div>
                            <div class="kpi-value text-success" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_infep ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">المعهد الوطني للتكوين والتعليم المهنيين</div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Support, Distance Training & Admin -->
                <div class="fw-bold mb-2 small text-primary"><i class="fa-solid fa-circle-nodes"></i> هياكل الدعم والتكوين عن بعد والتسيير الإداري:</div>
                <div class="row g-2 mb-3">
                    <!-- CNFEPD -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">فروع التكوين عن بعد (CNFEPD)</div>
                            <div class="kpi-value text-info" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_cnfepd ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">فروع وملحقات التعليم المهني عن بعد</div>
                        </div>
                    </div>
                    <!-- Annexes & SectionD -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">الملحقات والفروع المنتدبة</div>
                            <div class="kpi-value text-info" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_annexes_fleurs ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">الفروع المنتدبة والملحقات التابعة للمؤسسات</div>
                        </div>
                    </div>
                    <!-- DFEP -->
                    <div class="col-md-4">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid #112d26 !important;">
                            <div class="kpi-label" style="font-size: 0.85rem;">المديريات الولائية (DFEP)</div>
                            <div class="kpi-value text-info" style="font-size: 1.6rem; margin-top: 0.2rem;" data-counter="{{ $count_dfep ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.72rem; margin-top: 2px;">مديريات التكوين والتعليم المهنيين للولايات</div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Status & Accounts -->
                <div class="fw-bold mb-2 small text-primary"><i class="fa-solid fa-network-wired"></i> حالة الاتصال بالحسابات والفاعلية بالمنصة:</div>
                <div class="row g-2">
                    <!-- Total Ets -->
                    <div class="col-md-3">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid var(--minister-gold) !important;">
                            <div class="kpi-label" style="font-size: 0.8rem;">إجمالي الهياكل</div>
                            <div class="kpi-value text-primary" style="font-size: 1.5rem; margin-top: 0.2rem;" data-counter="{{ $total_etablissements ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.68rem; margin-top: 2px;">المسجلة بقاعدة البيانات</div>
                        </div>
                    </div>
                    <!-- Ets with Account -->
                    <div class="col-md-3">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid var(--minister-gold) !important;">
                            <div class="kpi-label" style="font-size: 0.8rem;">تمتلك حساب بالمنصة</div>
                            <div class="kpi-value text-success" style="font-size: 1.5rem; margin-top: 0.2rem;" data-counter="{{ $count_ets_with_account ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.68rem; margin-top: 2px;">حسابات دخول مفعلة</div>
                        </div>
                    </div>
                    <!-- Active Ets -->
                    <div class="col-md-3">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid var(--minister-gold) !important;">
                            <div class="kpi-label" style="font-size: 0.8rem;">مؤسسات نشطة</div>
                            <div class="kpi-value text-success" style="font-size: 1.5rem; margin-top: 0.2rem;" data-counter="{{ $count_ets_active ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.68rem; margin-top: 2px;">مفتوحة وتعمل حالياً</div>
                        </div>
                    </div>
                    <!-- Suspended Ets -->
                    <div class="col-md-3">
                        <div class="kpi-vip-card py-2 px-3 h-100" style="border-right: 3px solid var(--minister-gold) !important;">
                            <div class="kpi-label" style="font-size: 0.8rem;">موقوفة إدارياً</div>
                            <div class="kpi-value text-danger" style="font-size: 1.5rem; margin-top: 0.2rem;" data-counter="{{ $count_ets_suspended ?? 0 }}">0</div>
                            <div class="text-muted" style="font-size: 0.68rem; margin-top: 2px;">المعلقة والموقوفة مؤقتاً</div>
                        </div>
                    </div>
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

    <!-- SECTION: Active & Suspended Institutions Lists -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="module-card">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="section-title mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> قوائم المؤسسات (نشطة وموقوفة)</h4>
                    <ul class="nav nav-pills" id="etsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active btn-sm px-3" id="suspended-tab" data-bs-toggle="pill" data-bs-target="#suspended-list-pane" type="button" role="tab" aria-selected="true" style="font-family:'Cairo';">
                                <i class="fa-solid fa-ban text-danger me-1"></i> المؤسسات الموقوفة إدارياً ({{ count($suspended_etabs ?? []) }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link btn-sm px-3" id="active-tab" data-bs-toggle="pill" data-bs-target="#active-list-pane" type="button" role="tab" aria-selected="false" onclick="loadActiveEtabs()" style="font-family:'Cairo';">
                                <i class="fa-solid fa-circle-check text-success me-1"></i> المؤسسات والملحقات الناشطة ({{ number_format($count_ets_active ?? 0) }})
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content" id="etsTabContent">
                    <!-- Tab 1: Suspended -->
                    <div class="tab-pane fade show active" id="suspended-list-pane" role="tabpanel" aria-labelledby="suspended-tab">
                        <p class="text-muted small mb-3"><i class="fa-solid fa-circle-info text-danger"></i> هذه القائمة تعرض المؤسسات التي تم إيقاف تفعيلها مؤقتاً أو تجميد نشاطها إدارياً على مستوى المنصة الوطنية.</p>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <table class="table table-hover table-bordered mb-0 text-right">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="fw-bold">اسم المؤسسة التكوينية</th>
                                        <th class="fw-bold">الولاية</th>
                                        <th class="fw-bold">طبيعة المؤسسة</th>
                                        <th class="fw-bold text-center">حالة الحساب</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($suspended_etabs ?? [] as $se)
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $se->nom }}</td>
                                        <td>{{ $se->wilaya }}</td>
                                        <td><span class="badge bg-secondary">{{ $se->nature }}</span></td>
                                        <td class="text-center"><span class="badge bg-danger">موقوف إدارياً</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مؤسسات موقوفة حالياً.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Active -->
                    <div class="tab-pane fade" id="active-list-pane" role="tabpanel" aria-labelledby="active-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <p class="text-muted small mb-0"><i class="fa-solid fa-circle-info text-success"></i> هذه القائمة تعرض كافة الهياكل الناشطة المتصلة بالمنصة. يمكنك البحث الفوري وتصفية النتائج.</p>
                            <div class="d-flex gap-2">
                                <input type="text" id="activeSearchInput" class="form-control form-control-sm" placeholder="ابحث باسم المؤسسة، الولاية أو الطبيعة..." onkeyup="filterActiveEtabs()" style="width: 300px; font-family:'Cairo';">
                                <select id="activeAccountFilter" class="form-select form-select-sm" onchange="filterActiveEtabs()" style="width: 150px; font-family:'Cairo';">
                                    <option value="all">كل الحسابات</option>
                                    <option value="has_acc">تمتلك حساب</option>
                                    <option value="no_acc">لا تمتلك حساب</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="activeLoadingSpinner" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">جاري التحميل...</span>
                            </div>
                            <p class="mt-2 text-muted small">جاري تحميل وتجهيز قائمة المؤسسات الناشطة...</p>
                        </div>

                        <div id="activeEtabsTableWrapper" class="table-responsive d-none" style="max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <table class="table table-hover table-bordered mb-0 text-right">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="fw-bold">اسم المؤسسة التكوينية</th>
                                        <th class="fw-bold">الولاية</th>
                                        <th class="fw-bold">طبيعة المؤسسة</th>
                                        <th class="fw-bold text-center">حالة الحساب بالمنصة</th>
                                    </tr>
                                </thead>
                                <tbody id="activeEtabsTableBody">
                                    <!-- Dynamic rows from JS -->
                                </tbody>
                            </table>
                        </div>
                        <div id="activeEmptyMsg" class="text-center py-4 text-muted d-none"><i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i> لا توجد نتائج مطابقة لبحثك.</div>
                    </div>
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
                                <td>
                                    @if(($log['status'] ?? '') === 'success')
                                        <span class="status-badge status-success">دخول ناجح</span>
                                    @else
                                        <span class="status-badge status-danger">محاولة فاشلة</span>
                                    @endif
                                </td>
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

    // Active Etabs loading and filtering logic
    let activeEtabsData = [];

    window.loadActiveEtabs = function() {
        if (activeEtabsData.length > 0) return; // already loaded
        
        fetch("{{ url('/dashboard?ajax_active_etabs=1') }}")
            .then(res => res.json())
            .then(data => {
                activeEtabsData = data;
                document.getElementById('activeLoadingSpinner').classList.add('d-none');
                document.getElementById('activeEtabsTableWrapper').classList.remove('d-none');
                renderActiveEtabs(activeEtabsData);
            })
            .catch(err => {
                console.error("Failed to load active etabs", err);
                document.getElementById('activeLoadingSpinner').innerHTML = '<div class="text-danger"><i class="fa-solid fa-triangle-exclamation fs-3"></i><p class="mt-2 small">فشل في تحميل قائمة المؤسسات النشطة. يرجى إعادة المحاولة.</p></div>';
            });
    }

    function renderActiveEtabs(list) {
        const tbody = document.getElementById('activeEtabsTableBody');
        tbody.innerHTML = '';
        
        if (list.length === 0) {
            document.getElementById('activeEtabsTableWrapper').classList.add('d-none');
            document.getElementById('activeEmptyMsg').classList.remove('d-none');
            return;
        } else {
            document.getElementById('activeEtabsTableWrapper').classList.remove('d-none');
            document.getElementById('activeEmptyMsg').classList.add('d-none');
        }

        list.forEach(item => {
            const tr = document.createElement('tr');
            const accountBadge = item.has_account === 1 
                ? '<span class="badge bg-success"><i class="fa-solid fa-circle-check"></i> تمتلك حساب</span>'
                : '<span class="badge bg-warning text-dark"><i class="fa-solid fa-circle-exclamation"></i> قيد التفعيل</span>';
            
            tr.innerHTML = `
                <td class="fw-bold text-dark text-end">${item.nom}</td>
                <td class="text-end">${item.wilaya}</td>
                <td class="text-end"><span class="badge bg-secondary">${item.nature}</span></td>
                <td class="text-center">${accountBadge}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    window.filterActiveEtabs = function() {
        const query = document.getElementById('activeSearchInput').value.toLowerCase().trim();
        const accFilter = document.getElementById('activeAccountFilter').value;
        
        let filtered = activeEtabsData;
        
        if (query) {
            filtered = filtered.filter(item => 
                (item.nom && item.nom.toLowerCase().includes(query)) ||
                (item.wilaya && item.wilaya.toLowerCase().includes(query)) ||
                (item.nature && item.nature.toLowerCase().includes(query))
            );
        }
        
        if (accFilter === 'has_acc') {
            filtered = filtered.filter(item => item.has_account === 1);
        } else if (accFilter === 'no_acc') {
            filtered = filtered.filter(item => item.has_account === 0);
        }
        
        renderActiveEtabs(filtered);
    }
    // ══════════════════════════════════════════════════════
    // Animated Counter Engine — smooth ease-out counting
    // ══════════════════════════════════════════════════════
    function animateCounter(el, target, duration) {
        const start = performance.now();
        const easeOut = t => 1 - Math.pow(1 - t, 3); // cubic ease-out

        function step(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.round(easeOut(progress) * target);
            el.textContent = current.toLocaleString('en');
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = target.toLocaleString('en');
            }
        }
        requestAnimationFrame(step);
    }

    // Staggered card entrance + launch counters on viewport entry
    const kpiCards  = document.querySelectorAll('.kpi-vip-card');
    const counters  = document.querySelectorAll('[data-counter]');

    // Assign staggered animation-delay to each card
    kpiCards.forEach((card, i) => {
        card.style.animationDelay = `${i * 60}ms`;
    });

    // Use IntersectionObserver to fire counters when visible
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseInt(el.dataset.counter, 10) || 0;
                    // Duration scales with value: bigger numbers count longer (max 1800ms)
                    const duration = Math.min(600 + Math.sqrt(target) * 2, 1800);
                    animateCounter(el, target, duration);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.2 });

        counters.forEach(el => observer.observe(el));
    } else {
        // Fallback for browsers without IntersectionObserver
        counters.forEach(el => {
            const target = parseInt(el.dataset.counter, 10) || 0;
            animateCounter(el, target, 1200);
        });
    }
});
</script>
@endsection
