@extends('layouts.public')
@section('title', $title ?? 'المنصة الوطنية الموحدة لتسيير التكوين المهني ERP')

{{-- ══════════════════════════════════════════════════════════════
     CONTENT
══════════════════════════════════════════════════════════════ --}}
@section('content')

{{-- ────────────────────────────────────────────────────────────
     HERO SECTION — Dark Blue + Verification Tool
──────────────────────────────────────────────────────────── --}}
<section class="hero-dcode">

    {{-- Abstract floating shapes (like DCode) --}}
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-ring-1"></div>
        <div class="shape shape-ring-2"></div>
        <div class="shape shape-dot-grid"></div>
    </div>

    <div class="container position-relative" style="z-index:3;">
        <div class="row align-items-center g-5">

            {{-- LEFT: Copy + Buttons --}}
            <div class="col-lg-7">



                <h1 class="hero-title mb-3">
                    المنصة الوطنية الموحدة<br>
                    <span class="hero-title-gradient">لتسيير التكوين المهني</span>
                </h1>

                <p class="hero-desc mb-4">
                    البوابة الرقمية المعتمدة للتسيير الشامل لمسارات التكوين والتعليم المهنيين، تربط جميع المؤسسات عبر 58 ولاية بمركز البيانات الوطني المركزي.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="#servicesSection" class="btn-hero-primary">
                        <i class="fa-solid fa-layer-group me-2"></i> استكشاف الخدمات
                    </a>
                    <a href="{{ url('portal/about') }}" class="btn-hero-secondary">
                        <i class="fa-solid fa-circle-info me-2"></i> عن المنصة
                    </a>
                </div>
            </div>

            {{-- RIGHT: Premium ERP Mockup Graphic --}}
            <div class="col-lg-5 d-none d-lg-flex justify-content-center align-items-center position-relative">
                <div class="hero-image-glow"></div>
                <img src="{{ asset('assets/images/hero_erp_generated.png') }}" alt="منصة تسيير التكوين المهني ERP" class="img-fluid hero-laptop-img animate__animated animate__zoomIn">
            </div>
        </div>
    </div>

    {{-- Smooth animated waves transition to features section --}}
    <div class="hero-waves">
        <svg class="waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
            viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
            <defs>
                <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18v44h-352z" />
            </defs>
            <g class="parallax">
                <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,255,255,0.7)" />
                <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,255,255,0.5)" />
                <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,255,255,0.3)" />
                <use xlink:href="#gentle-wave" x="48" y="7" fill="#ffffff" />
            </g>
        </svg>
    </div>
</section>

           {{-- ────────────────────────────────────────────────────────────
     FEATURES SECTION — White, like DCode "Complete Online Security"
──────────────────────────────────────────────────────────── --}}
<section class="features-section">
    <div class="container">
        <div class="row align-items-center g-5">

            {{-- Left: copy --}}
            <div class="col-lg-5">
                <div class="sec-eyebrow">منصة تسيير الرقمية ERP</div>
                <h2 class="sec-heading">
                    تسيير كامل للتكوين المهني<br>
                    بـ <span class="sec-heading-accent">نظام ذكي متكامل</span>
                </h2>
                <p class="sec-desc">
                    نظام معلوماتي مركزي موحد يعتمد على تقنيات السحابة لتسجيل المتربصين، توزيع الفروع، تسيير الغيابات والتقييمات، إدارة الأساتذة والموظفين، وحوكمة القطاع بمرونة وكفاءة.
                </p>
                <a href="{{ url('portal/features') }}" class="btn-sec-cta">
                    اكتشف كل الميزات <i class="fa-solid fa-arrow-left ms-2"></i>
                </a>
            </div>

            {{-- Right: circular feature icons (like DCode's D-logo circle) --}}
            <div class="col-lg-7">
                <div class="feat-orbit-wrap">

                    {{-- Center logo --}}
                    <div class="feat-orbit-center">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="شعار" style="width:56px; height:56px; object-fit:contain;">
                    </div>

                    {{-- Orbit rings --}}
                    <div class="feat-orbit feat-orbit-inner">
                        <div class="feat-orb-item foi-1">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="feat-orb-item foi-2">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="feat-orb-item foi-3">
                            <i class="fa-solid fa-gears"></i>
                        </div>
                    </div>
                    <div class="feat-orbit feat-orbit-outer">
                        <div class="feat-orbit-item-lg fol-1">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span>المتربصين</span>
                        </div>
                        <div class="feat-orbit-item-lg fol-2">
                            <i class="fa-solid fa-book-open"></i>
                            <span>التسيير البيداغوجي</span>
                        </div>
                        <div class="feat-orbit-item-lg fol-3">
                            <i class="fa-solid fa-building-user"></i>
                            <span>الموارد البشرية</span>
                        </div>
                        <div class="feat-orbit-item-lg fol-4">
                            <i class="fa-solid fa-network-wired"></i>
                            <span>المؤسسات والهياكل</span>
                        </div>
                    </div>

                    {{-- Feature cards floating beside the orbit --}}
                    <div class="feat-floating-card ffc-1">
                        <i class="fa-solid fa-bolt text-warning me-2"></i>
                        <span>معالجة لحظية</span>
                    </div>
                    <div class="feat-floating-card ffc-2">
                        <i class="fa-solid fa-shield-halved text-success me-2"></i>
                        <span>حوكمة مركزية</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feature cards row --}}
        <div class="feat-cards-row">
            <div class="feat-card">
                <div class="feat-card-icon fc-blue"><i class="fa-solid fa-users-gear"></i></div>
                <h5 class="feat-card-title">شؤون المتربصين والتمهين</h5>
                <p class="feat-card-desc">تتبع شامل لمسار المتربص والتمهين منذ التسجيل مرورا بالغيابات إلى التقييم النهائي والشهادة.</p>
            </div>
            <div class="feat-card">
                <div class="feat-card-icon fc-cyan"><i class="fa-solid fa-chart-line"></i></div>
                <h5 class="feat-card-title">التقييم المستمر والنتائج</h5>
                <p class="feat-card-desc">إدخال لحظي للعلامات من الأساتذة، احتساب المعدلات آلياً وتوليد كشوف النقاط بدقة متناهية.</p>
            </div>
            <div class="feat-card">
                <div class="feat-card-icon fc-green"><i class="fa-solid fa-building-user"></i></div>
                <h5 class="feat-card-title">الموارد البشرية والأساتذة</h5>
                <p class="feat-card-desc">إدارة وتوزيع الحصص والأنشطة البيداغوجية، تسيير ملفات الأساتذة والموظفين مركزياً.</p>
            </div>
            <div class="feat-card">
                <div class="feat-card-icon fc-purple"><i class="fa-solid fa-server"></i></div>
                <h5 class="feat-card-title">إحصائيات متكاملة وحوكمة</h5>
                <p class="feat-card-desc">لوحة تحكم للمديريات والوزارة لتحليل المؤشرات البيداغوجية ودعم اتخاذ القرار الاستراتيجي.</p>
            </div>
        </div>
    </div>
</section>

{{-- ────────────────────────────────────────────────────────────
     HOW IT WORKS SECTION — White + Steps
──────────────────────────────────────────────────────────── --}}
<section class="hiw-section">
    <div class="container text-center">
        <div class="sec-eyebrow">تكامل الأدوار</div>
        <h2 class="sec-heading mb-2">منظومة التسيير الموحدة</h2>
        <p class="sec-desc mx-auto mb-5" style="max-width:560px;">
            تربط المنصة مختلف المستويات الإدارية والبيداغوجية لتوفير بيئة عمل متكاملة وحوكمة كاملة للقطاع.
        </p>

        <div class="hiw-steps">
            <div class="hiw-step">
                <div class="hiw-step-num">01</div>
                <div class="hiw-step-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <h5 class="hiw-step-title">الجانب البيداغوجي والمتربصين</h5>
                <p class="hiw-step-desc">تسيير المجموعات الدراسية، الحضور والغياب، وإدخال النقاط وحساب المعدلات للمتربصين إلكترونياً.</p>
            </div>
            <div class="hiw-connector"><i class="fa-solid fa-chevron-left"></i></div>
            <div class="hiw-step">
                <div class="hiw-step-num">02</div>
                <div class="hiw-step-icon hiw-icon-blue"><i class="fa-solid fa-users-gear"></i></div>
                <h5 class="hiw-step-title">تسيير الموارد والموظفين</h5>
                <p class="hiw-step-desc">متابعة ملفات الموظفين والأساتذة وتوزيع المهام الإدارية والبيداغوجية وضبط جداول التوقيت.</p>
            </div>
            <div class="hiw-connector"><i class="fa-solid fa-chevron-left"></i></div>
            <div class="hiw-step">
                <div class="hiw-step-num">03</div>
                <div class="hiw-step-icon hiw-icon-green"><i class="fa-solid fa-chart-pie"></i></div>
                <h5 class="hiw-step-title">المتابعة والتقارير للمتخذ القرار</h5>
                <p class="hiw-step-desc">توليد إحصائيات فورية حول نسب النجاح والاستيعاب، الغيابات والتسرب لدعم التخطيط المستقبلي.</p>
            </div>
        </div>

        <a href="{{ url('login') }}" class="btn-hiw-cta">
            <i class="fa-solid fa-right-to-bracket me-2"></i>
            الدخول إلى فضاء العمل
        </a>
    </div>
</section>

{{-- ────────────────────────────────────────────────────────────
     SERVICES STRIP — Minimal 4 cards
──────────────────────────────────────────────────────────── --}}
<section class="services-strip">
    <div class="container">
        <div class="sec-eyebrow text-center mb-2">الخدمات المكملة</div>
        <h2 class="sec-heading text-center mb-5">كل ما تحتاجه في مكان واحد</h2>

        <div class="sstrip-grid">
            <a href="https://takwin.dz" target="_blank" rel="noopener" class="sstrip-card">
                <div class="sstrip-icon ss-blue"><i class="fa-solid fa-plug"></i></div>
                <div class="sstrip-content">
                    <div class="sstrip-title">التسجيل عبر منصة تكوين</div>
                    <div class="sstrip-desc">تسجيل إلكتروني مركزي عبر takwin.dz</div>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square sstrip-arrow"></i>
            </a>
            <a href="{{ url('portal/nomenclature') }}" class="sstrip-card">
                <div class="sstrip-icon ss-green"><i class="fa-solid fa-book-open"></i></div>
                <div class="sstrip-content">
                    <div class="sstrip-title">مدونة البرامج التكوينية</div>
                    <div class="sstrip-desc">دليل الشعب والتخصصات الوطنية</div>
                </div>
                <i class="fa-solid fa-chevron-left sstrip-arrow"></i>
            </a>
            <a href="{{ url('portal/directions') }}" class="sstrip-card">
                <div class="sstrip-icon ss-amber"><i class="fa-solid fa-map-location-dot"></i></div>
                <div class="sstrip-content">
                    <div class="sstrip-title">دليل المديريات والمؤسسات</div>
                    <div class="sstrip-desc">ابحث عن المعاهد والمراكز حسب الولاية</div>
                </div>
                <i class="fa-solid fa-chevron-left sstrip-arrow"></i>
            </a>
            <a href="{{ url('login') }}" class="sstrip-card">
                <div class="sstrip-icon ss-purple"><i class="fa-solid fa-user-shield"></i></div>
                <div class="sstrip-content">
                    <div class="sstrip-title">فضاء الموظف والإداري</div>
                    <div class="sstrip-desc">دخول آمن للمنصة الإدارية الداخلية</div>
                </div>
                <i class="fa-solid fa-chevron-left sstrip-arrow"></i>
            </a>
        </div>
    </div>
</section>

{{-- ────────────────────────────────────────────────────────────
     NATIONAL STATS SECTION — Modern Bento Grid
──────────────────────────────────────────────────────────── --}}
<section class="national-stats-section">
    <div class="container">
        <div class="sec-eyebrow text-center mb-2">الإحصائيات الوطنية الحية</div>
        <h2 class="sec-heading text-center mb-2">لوحة المؤشرات الوطنية الفورية</h2>
        <p class="sec-desc text-center mx-auto mb-5" style="max-width: 600px;">
            بيانات حية ومباشرة تعكس مدى التحول الرقمي وحجم النشاط البيداغوجي الموحد عبر كافة ولايات ومؤسسات الوطن.
        </p>

        <div class="home-bento-grid">
            
            {{-- 1. Large Card: Trainees --}}
            <div class="home-bento-card home-bento-large bento-trainees">
                <div class="bento-card-glow"></div>
                <div class="bento-card-content">
                    <div class="bento-top">
                        <div class="bento-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="bento-badge"><i class="fa-solid fa-arrow-trend-up me-1"></i>+12% سنوي</div>
                    </div>
                    <div class="bento-middle">
                        <div class="bento-num" data-target="{{ $stat_inscrits }}">0</div>
                        <div class="bento-label">متربص ومتمهن مدمج بالنظام</div>
                    </div>
                    <div class="bento-bottom">
                        <div class="bento-desc">تغطية شاملة لكافة فئات التكوين البيداغوجي والتمهين المسجلة إلكترونياً.</div>
                    </div>
                </div>
            </div>

            {{-- 2. Medium Card: Data Sync Rate --}}
            <div class="home-bento-card home-bento-medium bento-sync">
                <div class="bento-card-glow"></div>
                <div class="bento-card-content">
                    <div class="bento-top">
                        <div class="bento-icon icon-purple"><i class="fa-solid fa-database"></i></div>
                        <div class="bento-pulse-wrapper">
                            <span class="bento-pulse-dot"></span>
                            <span class="bento-pulse-text">نشط الآن</span>
                        </div>
                    </div>
                    <div class="bento-middle">
                        <div class="bento-num-pct">99.97%</div>
                        <div class="bento-label">نسبة معالجة البيانات الفورية</div>
                    </div>
                    <div class="bento-bottom">
                        <div class="bento-progress-track">
                            <div class="bento-progress-fill" style="width: 0%" data-width="99.97%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Small Card: Institutions --}}
            <div class="home-bento-card home-bento-small bento-etabs">
                <div class="bento-card-glow"></div>
                <div class="bento-card-content">
                    <div class="bento-top">
                        <div class="bento-icon icon-green"><i class="fa-solid fa-building"></i></div>
                    </div>
                    <div class="bento-middle">
                        <div class="bento-num-sm" data-target="{{ $stat_etablissements }}">0</div>
                        <div class="bento-label">مؤسسة تكوينية نشطة</div>
                    </div>
                    <div class="bento-bottom">
                        <span class="bento-subtext">معاهد ومراكز متخصصة</span>
                    </div>
                </div>
            </div>

            {{-- 4. Small Card: Wilayas --}}
            <div class="home-bento-card home-bento-small bento-wilayas">
                <div class="bento-card-glow"></div>
                <div class="bento-card-content">
                    <div class="bento-top">
                        <div class="bento-icon icon-amber"><i class="fa-solid fa-map-location-dot"></i></div>
                    </div>
                    <div class="bento-middle">
                        <div class="bento-num-sm" data-target="{{ $stat_wilayas }}">0</div>
                        <div class="bento-label">ولاية مغطاة بالكامل</div>
                    </div>
                    <div class="bento-bottom">
                        <span class="bento-subtext">ربط شبكي متكامل للوطن</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     STYLES
══════════════════════════════════════════════════════════════ --}}
<style>
/* ─── RESET MARGIN from public layout ─── */
.public-content { margin-top: 0 !important; padding-top: 0; }

/* ══════════════════════════════════════════════════════════════
   1. HERO SECTION
══════════════════════════════════════════════════════════════ */
.hero-dcode {
    position: relative;
    background: linear-gradient(135deg, #0f1b3d 0%, #1a2d6e 40%, #1d4ed8 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 140px 0 160px;
    overflow: hidden;
}

/* Wavy SVG divider transition to white */
.hero-waves {
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 12vh;
    min-height: 80px;
    max-height: 150px;
    z-index: 4;
    overflow: hidden;
}

.waves {
    position: relative;
    width: 100%;
    height: 100%;
    margin-bottom: -7px; /* Fix Safari gap */
    min-height: 80px;
    max-height: 150px;
}

/* Animation */
.parallax > use {
    animation: move-forever 25s cubic-bezier(.55,.5,.45,.5) infinite;
}
.parallax > use:nth-child(1) {
    animation-delay: -2s;
    animation-duration: 7s;
}
.parallax > use:nth-child(2) {
    animation-delay: -3s;
    animation-duration: 10s;
}
.parallax > use:nth-child(3) {
    animation-delay: -4s;
    animation-duration: 13s;
}
.parallax > use:nth-child(4) {
    animation-delay: -5s;
    animation-duration: 20s;
}

@keyframes move-forever {
    0% {
        transform: translate3d(-90px,0,0);
    }
    100% {
        transform: translate3d(85px,0,0);
    }
}

/* Floating abstract shapes */
.hero-shapes { position: absolute; inset: 0; pointer-events: none; z-index: 1; overflow: hidden; }

.shape {
    position: absolute;
    border-radius: 50%;
    opacity: 0.07;
}
.shape-1 {
    width: 600px; height: 600px;
    background: radial-gradient(circle, #60a5fa 0%, transparent 70%);
    top: -200px; right: -150px;
    animation: floatShape 12s ease-in-out infinite;
}
.shape-2 {
    width: 400px; height: 400px;
    background: radial-gradient(circle, #818cf8 0%, transparent 70%);
    bottom: -100px; left: -100px;
    animation: floatShape 16s ease-in-out infinite reverse;
}
.shape-3 {
    width: 250px; height: 250px;
    background: radial-gradient(circle, #34d399 0%, transparent 70%);
    top: 40%; right: 20%;
    opacity: 0.05;
    animation: floatShape 9s ease-in-out infinite;
}
.shape-ring-1 {
    width: 500px; height: 500px;
    border: 1.5px solid rgba(255,255,255,0.08);
    border-radius: 50%;
    top: -200px; right: -100px;
    background: none;
    animation: spinSlow 40s linear infinite;
}
.shape-ring-2 {
    width: 300px; height: 300px;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 50%;
    bottom: -50px; left: 10%;
    background: none;
    animation: spinSlow 30s linear infinite reverse;
}
.shape-dot-grid {
    inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
    background-size: 36px 36px;
    border-radius: 0;
    opacity: 1;
}

@keyframes floatShape {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(20px, -30px) scale(1.05); }
}
@keyframes spinSlow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ── Hero status pill ── */
.hero-pill {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 100px;
    padding: 6px 16px;
    font-size: 0.78rem;
    font-weight: 700;
    color: rgba(255,255,255,0.85);
    backdrop-filter: blur(10px);
}
.hero-pill-dot {
    width: 7px; height: 7px;
    background: #34d399;
    border-radius: 50%;
    display: inline-block;
    animation: dotPulse 2s infinite;
    flex-shrink: 0;
}
@keyframes dotPulse {
    0%, 100% { opacity: 0.5; transform: scale(0.9); }
    50% { opacity: 1; transform: scale(1.3); box-shadow: 0 0 8px rgba(52,211,153,0.6); }
}

/* ── Hero headline ── */
.hero-title {
    font-size: clamp(2.2rem, 5vw, 4rem);
    font-weight: 900;
    color: #ffffff;
    line-height: 1.45;
    letter-spacing: normal;
}
.hero-title-gradient {
    background: linear-gradient(90deg, #60a5fa 0%, #34d399 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
.hero-desc {
    font-size: 1rem;
    color: rgba(255,255,255,0.72);
    line-height: 1.9;
    font-weight: 500;
    max-width: 580px;
}

/* ── Hero Buttons ── */
.btn-hero-primary {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    color: #0f1b3d !important;
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    padding: 12px 28px;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
.btn-hero-primary:hover {
    transform: translateY(-2px);
    background: #eef2f6;
    box-shadow: 0 15px 35px rgba(0,0,0,0.25);
    color: #1d4ed8 !important;
}

.btn-hero-secondary {
    display: inline-flex;
    align-items: center;
    background: rgba(255,255,255,0.08);
    color: #ffffff !important;
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    padding: 12px 28px;
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    backdrop-filter: blur(10px);
}
.btn-hero-secondary:hover {
    transform: translateY(-2px);
    background: rgba(255,255,255,0.18);
    border-color: rgba(255,255,255,0.5);
    color: #ffffff !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* ── Verification Hero Card ── */
.verify-hero-card {
    background: rgba(255,255,255,0.97);
    border-radius: 24px;
    padding: 2rem;
    box-shadow: 0 32px 80px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.15);
    position: relative;
    overflow: hidden;
}
.verify-hero-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #1d4ed8 0%, #0ea5e9 50%, #34d399 100%);
    border-radius: 24px 24px 0 0;
}

.vhc-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.vhc-icon-wrap {
    position: relative;
    width: 52px; height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #1d4ed8 0%, #0ea5e9 100%);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.vhc-pulse-ring {
    position: absolute;
    inset: -5px;
    border: 2px solid rgba(29,78,216,0.3);
    border-radius: 18px;
    animation: ringPulse 2s infinite;
}
@keyframes ringPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 1; }
}
.vhc-icon { color: #fff; font-size: 1.4rem; }
.vhc-title { font-size: 1rem; font-weight: 800; color: #0f172a; }
.vhc-subtitle { font-size: 0.78rem; color: #64748b; font-weight: 500; }
.vhc-secure-badge {
    font-size: 0.7rem; font-weight: 800;
    color: #059669;
    background: rgba(5,150,105,0.08);
    border: 1px solid rgba(5,150,105,0.15);
    border-radius: 8px;
    padding: 4px 10px;
    display: flex; align-items: center;
    white-space: nowrap;
}

/* Input row */
.vhc-input-wrap {
    display: flex; align-items: center;
    background: #f8faff;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 4px 4px 4px 4px;
    transition: all 0.25s ease;
    margin-bottom: 1rem;
}
.vhc-input-wrap:focus-within {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 4px rgba(29,78,216,0.12);
    background: #fff;
}
.vhc-search-icon {
    color: #94a3b8;
    font-size: 0.9rem;
    margin: 0 12px;
    flex-shrink: 0;
}
.vhc-input {
    flex: 1;
    border: none;
    background: transparent;
    font-family: 'Cairo', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
    outline: none;
    padding: 10px 4px;
}
.vhc-input::placeholder { color: #94a3b8; font-weight: 400; }
.vhc-btn {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #1d4ed8 0%, #0ea5e9 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 22px;
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.25s ease;
    white-space: nowrap;
    box-shadow: 0 4px 16px rgba(29,78,216,0.3);
}
.vhc-btn-shine {
    position: absolute;
    top: -50%; left: -60%;
    width: 200%; height: 200%;
    background: linear-gradient(115deg, transparent 40%, rgba(255,255,255,0.2) 50%, transparent 60%);
    transform: rotate(30deg);
    transition: transform 0.7s ease;
    pointer-events: none;
}
.vhc-btn:hover .vhc-btn-shine { transform: translate(60%, 40%) rotate(30deg); }
.vhc-btn:hover { filter: brightness(1.1); transform: translateY(-1px); }

/* Chips */
.vhc-chips {
    display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
    font-size: 0.78rem; color: #64748b;
}
.vhc-chip-label { font-weight: 700; display: flex; align-items: center; gap: 4px; }
.vhc-chip {
    background: rgba(29,78,216,0.06);
    color: #1d4ed8;
    border: 1px solid rgba(29,78,216,0.15);
    border-radius: 100px;
    padding: 4px 14px;
    font-size: 0.78rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
}
.vhc-chip:hover { background: #1d4ed8; color: #fff; border-color: transparent; }

/* Result Panel */
.vhc-result {
    position: relative;
    margin-top: 1rem;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1.5px solid rgba(5,150,105,0.15);
    border-radius: 16px;
    padding: 1.25rem;
    animation: slideInUp 0.4s ease;
    overflow: hidden;
}
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.vhc-laser {
    position: absolute;
    width: 100%; height: 2px;
    background: linear-gradient(90deg, transparent, #22c55e, transparent);
    top: 0; left: 0; z-index: 5;
    animation: laserSweep 2s ease;
}
@keyframes laserSweep {
    0% { top: 0; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}
.vhc-avatar {
    width: 46px; height: 46px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; font-weight: 900;
    flex-shrink: 0;
}
.vhc-result-name { font-weight: 800; font-size: 0.95rem; color: #0f172a; }
.vhc-result-mat { font-size: 0.8rem; color: #059669; font-weight: 700; }
.vhc-verified-badge {
    font-size: 0.72rem; font-weight: 800;
    background: #059669; color: #fff;
    border-radius: 100px;
    padding: 5px 12px;
    white-space: nowrap;
}
.vhc-result-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.6rem;
    border-top: 1px solid rgba(5,150,105,0.12);
    padding-top: 0.75rem;
    margin-top: 0.25rem;
}
.vhc-rg-label { display: block; font-size: 0.72rem; color: #64748b; font-weight: 700; margin-bottom: 2px; }
.vhc-rg-val { font-size: 0.82rem; font-weight: 700; color: #0f172a; }

/* ── Right stat cards ── */
.hero-stat-card {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 20px;
    padding: 1.25rem 1.5rem;
    backdrop-filter: blur(12px);
    color: #fff;
    position: relative;
    transition: all 0.3s ease;
    width: 100%;
}
.hero-stat-card:hover {
    background: rgba(255,255,255,0.15);
    transform: translateY(-3px);
}
.hsc-icon {
    width: 40px; height: 40px;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    margin-bottom: 0.75rem;
    color: #fff;
}
.hsc-icon-green { background: rgba(52,211,153,0.2); color: #34d399; }
.hsc-icon-amber { background: rgba(251,191,36,0.2); color: #fbbf24; }
.hsc-icon-purple { background: rgba(167,139,250,0.2); color: #a78bfa; }

.hsc-content { display: flex; flex-direction: column; }
.hsc-num { font-size: 2rem; font-weight: 900; color: #fff; line-height: 1; }
.hsc-num-sm { font-size: 1.75rem; font-weight: 900; color: #fff; margin: 0.25rem 0; }
.hsc-num-pct { font-size: 1.1rem; font-weight: 900; color: #fff; white-space: nowrap; }
.hsc-label { font-size: 0.78rem; color: rgba(255,255,255,0.65); font-weight: 600; margin-top: 4px; }
.hsc-badge-up {
    position: absolute; top: 1rem; left: 1rem;
    background: rgba(52,211,153,0.2);
    color: #34d399;
    font-size: 0.7rem; font-weight: 800;
    padding: 4px 10px; border-radius: 100px;
}
.hsc-progress-track {
    height: 6px; background: rgba(255,255,255,0.1);
    border-radius: 3px; overflow: hidden;
    width: 140px;
}
.hsc-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #34d399, #0ea5e9);
    border-radius: 3px;
    transition: width 2s cubic-bezier(0.16,1,0.3,1);
}

/* ══════════════════════════════════════════════════════════════
   2. FEATURES SECTION
══════════════════════════════════════════════════════════════ */
.features-section {
    background: #ffffff;
    padding: 100px 0 60px;
    overflow: hidden;
}

.sec-eyebrow {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #1d4ed8;
    margin-bottom: 0.6rem;
}
.sec-heading {
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 900;
    color: #0f172a;
    line-height: 1.45;
    margin-bottom: 1rem;
}
.sec-heading-accent {
    color: #1d4ed8;
}
.sec-desc {
    font-size: 0.92rem;
    line-height: 1.9;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 1.75rem;
}
.btn-sec-cta {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
    color: #fff;
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 0.9rem;
    padding: 12px 28px;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 8px 24px rgba(29,78,216,0.25);
}
.btn-sec-cta:hover { filter: brightness(1.1); transform: translateY(-2px); color: #fff; }

/* ── Orbit Visual ── */
.feat-orbit-wrap {
    position: relative;
    width: 380px; height: 380px;
    margin: 0 auto;
}
.feat-orbit-center {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    width: 90px; height: 90px;
    background: linear-gradient(135deg, #0f1b3d, #1d4ed8);
    border: 2px solid rgba(255, 255, 255, 0.15);
    border-radius: 24px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 35px rgba(29, 78, 216, 0.25);
    z-index: 3;
}
.feat-orbit {
    position: absolute;
    top: 50%; left: 50%;
    border-radius: 50%;
    border: 1.5px dashed rgba(29,78,216,0.15);
    transform: translate(-50%,-50%);
}
.feat-orbit-inner { width: 200px; height: 200px; animation: spinOrbit 15s linear infinite; }
.feat-orbit-outer { width: 340px; height: 340px; animation: spinOrbit 25s linear infinite reverse; }
@keyframes spinOrbit { from { transform: translate(-50%,-50%) rotate(0deg); } to { transform: translate(-50%,-50%) rotate(360deg); } }

@keyframes spinCounterInnerX {
    from { transform: translateX(-50%) rotate(360deg); }
    to { transform: translateX(-50%) rotate(0deg); }
}
@keyframes spinCounterInnerY {
    from { transform: translateY(-50%) rotate(360deg); }
    to { transform: translateY(-50%) rotate(0deg); }
}
@keyframes spinCounterOuterX {
    from { transform: translateX(-50%) rotate(0deg); }
    to { transform: translateX(-50%) rotate(360deg); }
}
@keyframes spinCounterOuterY {
    from { transform: translateY(-50%) rotate(0deg); }
    to { transform: translateY(-50%) rotate(360deg); }
}

.feat-orb-item {
    position: absolute;
    width: 38px; height: 38px;
    background: #1d4ed8;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 0.85rem;
    box-shadow: 0 4px 16px rgba(29,78,216,0.3);
}
.foi-1 { top: -19px; left: 50%; animation: spinCounterInnerX 15s linear infinite; }
.foi-2 { bottom: -19px; left: 50%; animation: spinCounterInnerX 15s linear infinite; }
.foi-3 { left: -19px; top: 50%; animation: spinCounterInnerY 15s linear infinite; }

.feat-orb-item-lg {
    position: absolute;
    background: #fff;
    border: 1.5px solid rgba(29,78,216,0.1);
    border-radius: 14px;
    padding: 8px 14px;
    display: flex; align-items: center; gap: 6px;
    font-size: 0.72rem; font-weight: 700; color: #1d4ed8;
    box-shadow: 0 4px 20px rgba(15,23,42,0.07);
    white-space: nowrap;
}
.fol-1 { top: -22px; left: 50%; animation: spinCounterOuterX 25s linear infinite; }
.fol-2 { right: -22px; top: 50%; animation: spinCounterOuterY 25s linear infinite; }
.fol-3 { bottom: -22px; left: 50%; animation: spinCounterOuterX 25s linear infinite; }
.fol-4 { left: -22px; top: 50%; animation: spinCounterOuterY 25s linear infinite; }

.feat-floating-card {
    position: absolute;
    background: #fff;
    border-radius: 12px;
    padding: 8px 16px;
    font-size: 0.78rem; font-weight: 700; color: #0f172a;
    box-shadow: 0 8px 30px rgba(15,23,42,0.1);
    border: 1px solid rgba(15,23,42,0.06);
    white-space: nowrap;
    display: flex; align-items: center;
}
.ffc-1 { top: 10px; right: -30px; }
.ffc-2 { bottom: 40px; right: -20px; }

/* ── Feature cards ── */
.feat-cards-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
    margin-top: 5rem;
}
.feat-card {
    background: #f8faff;
    border: 1px solid rgba(29,78,216,0.08);
    border-radius: 20px;
    padding: 2rem 1.5rem;
    transition: all 0.3s ease;
}
.feat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 50px rgba(29,78,216,0.1); border-color: rgba(29,78,216,0.2); }
.feat-card-icon {
    width: 50px; height: 50px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; margin-bottom: 1.1rem;
}
.fc-blue  { background: rgba(29,78,216,0.08); color: #1d4ed8; }
.fc-cyan  { background: rgba(14,165,233,0.08); color: #0ea5e9; }
.fc-green { background: rgba(5,150,105,0.08); color: #059669; }
.fc-purple{ background: rgba(124,58,237,0.08); color: #7c3aed; }
.feat-card-title { font-size: 0.92rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; }
.feat-card-desc { font-size: 0.8rem; color: #64748b; line-height: 1.7; margin: 0; }

/* ══════════════════════════════════════════════════════════════
   3. HOW IT WORKS
══════════════════════════════════════════════════════════════ */
.hiw-section {
    background: #f8faff;
    padding: 100px 0;
}
.hiw-steps {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 0;
    margin-bottom: 3rem;
    flex-wrap: wrap;
}
.hiw-step {
    flex: 1;
    min-width: 200px;
    max-width: 280px;
    text-align: center;
    padding: 0 1rem;
}
.hiw-step-num {
    font-size: 0.7rem;
    font-weight: 900;
    color: #1d4ed8;
    background: rgba(29,78,216,0.08);
    border-radius: 100px;
    padding: 4px 12px;
    display: inline-block;
    margin-bottom: 1rem;
    letter-spacing: 1px;
}
.hiw-step-icon {
    width: 72px; height: 72px;
    background: #fff;
    border: 2px solid rgba(29,78,216,0.12);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #1d4ed8;
    margin: 0 auto 1.1rem;
    box-shadow: 0 8px 30px rgba(29,78,216,0.1);
    transition: all 0.3s ease;
}
.hiw-step-icon:hover { transform: scale(1.08); box-shadow: 0 16px 40px rgba(29,78,216,0.2); }
.hiw-icon-blue { background: linear-gradient(135deg, #1d4ed8, #0ea5e9); color: #fff; border-color: transparent; }
.hiw-icon-green { background: linear-gradient(135deg, #059669, #34d399); color: #fff; border-color: transparent; }
.hiw-step-title { font-size: 0.92rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; }
.hiw-step-desc { font-size: 0.8rem; color: #64748b; line-height: 1.7; }
.hiw-connector {
    color: rgba(29,78,216,0.25);
    font-size: 1.2rem;
    padding-top: 3rem;
    margin: 0 0.5rem;
    align-self: flex-start;
}
.btn-hiw-cta {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
    color: #fff;
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    padding: 15px 40px;
    border-radius: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 12px 35px rgba(29,78,216,0.3);
    cursor: pointer;
    border: none;
}
.btn-hiw-cta:hover { filter: brightness(1.1); transform: translateY(-3px); color: #fff; box-shadow: 0 20px 50px rgba(29,78,216,0.4); }

/* ══════════════════════════════════════════════════════════════
   4. SERVICES STRIP
══════════════════════════════════════════════════════════════ */
.services-strip {
    background: #ffffff;
    padding: 80px 0 100px;
}
.sstrip-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem;
}
.sstrip-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #f8faff;
    border: 1px solid rgba(29,78,216,0.08);
    border-radius: 18px;
    padding: 1.25rem 1.5rem;
    text-decoration: none;
    transition: all 0.3s ease;
}
.sstrip-card:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(29,78,216,0.1); border-color: rgba(29,78,216,0.2); }
.sstrip-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.ss-blue   { background: rgba(29,78,216,0.1); color: #1d4ed8; }
.ss-green  { background: rgba(5,150,105,0.1); color: #059669; }
.ss-amber  { background: rgba(217,119,6,0.1); color: #d97706; }
.ss-purple { background: rgba(124,58,237,0.1); color: #7c3aed; }
.sstrip-content { flex: 1; }
.sstrip-title { font-size: 0.88rem; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
.sstrip-desc { font-size: 0.75rem; color: #64748b; font-weight: 500; }
.sstrip-arrow { color: #94a3b8; font-size: 0.8rem; flex-shrink: 0; transition: transform 0.2s ease; }
.sstrip-card:hover .sstrip-arrow { transform: translateX(-4px); color: #1d4ed8; }

/* ══════════════════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════════════════ */
@media (max-width: 991px) {
    .hero-dcode { padding: 130px 0 60px; min-height: auto; }
    .feat-orbit-wrap { width: 280px; height: 280px; }
    .feat-orbit-inner { width: 150px; height: 150px; }
    .feat-orbit-outer { width: 250px; height: 250px; }
    .hiw-connector { display: none; }
    .hiw-steps { gap: 1.5rem; }
}
@media (max-width: 767px) {
    .hero-title { font-size: 2rem; }
    .verify-hero-card { padding: 1.25rem; }
    .vhc-btn { padding: 10px 14px; }
    .sstrip-grid { grid-template-columns: 1fr; }
    .feat-cards-row { grid-template-columns: 1fr; }
}

/* ══════════════════════════════════════════════════════════════
   HERO LAPTOP MOCKUP GLOW
   ══════════════════════════════════════════════════════════════ */
.hero-image-glow {
    position: absolute;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(96, 165, 250, 0.22) 0%, transparent 70%);
    filter: blur(40px);
    z-index: 1;
    pointer-events: none;
}
.hero-laptop-img {
    max-width: 115%;
    height: auto;
    z-index: 2;
    mix-blend-mode: screen;
    filter: drop-shadow(0 20px 50px rgba(15, 23, 42, 0.45)) contrast(1.05) brightness(1.05);
    animation: floatLaptop 6s ease-in-out infinite;
}
@keyframes floatLaptop {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(0.4deg); }
}

/* ══════════════════════════════════════════════════════════════
   BENTO STATISTICS SECTION
   ══════════════════════════════════════════════════════════════ */
.national-stats-section {
    background: linear-gradient(180deg, #ffffff 0%, #EEF2F6 100%);
    padding: 100px 0;
    position: relative;
    overflow: hidden;
}
.national-stats-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(15,23,42,0.06), transparent);
}

.home-bento-grid {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 1.5rem !important;
    margin-top: 3rem !important;
    width: 100% !important;
}

.home-bento-card {
    position: relative;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 24px;
    padding: 2.25rem;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 260px;
    width: 100% !important;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
}

.home-bento-large {
    grid-column: span 2 !important;
}
.home-bento-medium {
    grid-column: span 1 !important;
}
.home-bento-small {
    grid-column: span 1 !important;
}

.bento-card-glow {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(400px circle at var(--mouse-x, 0) var(--mouse-y, 0), rgba(37, 99, 235, 0.04), transparent 60%);
    z-index: 1;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.4s ease;
}
.home-bento-card:hover .bento-card-glow {
    opacity: 1;
}

.bento-card-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    width: 100%;
}

.home-bento-card:hover {
    transform: translateY(-6px);
    border-color: rgba(37, 99, 235, 0.2);
    box-shadow: 0 25px 50px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.bento-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.bento-icon {
    width: 52px; height: 52px;
    background: rgba(37, 99, 235, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.1);
    color: #2563EB;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}
.bento-icon.icon-green {
    background: rgba(5, 150, 105, 0.06);
    border-color: rgba(5, 150, 105, 0.1);
    color: #059669;
}
.bento-icon.icon-amber {
    background: rgba(217, 119, 6, 0.06);
    border-color: rgba(217, 119, 6, 0.1);
    color: #d97706;
}
.bento-icon.icon-purple {
    background: rgba(124, 58, 237, 0.06);
    border-color: rgba(124, 58, 237, 0.1);
    color: #7c3aed;
}

.bento-badge {
    background: rgba(5, 150, 105, 0.08);
    border: 1px solid rgba(5, 150, 105, 0.15);
    color: #059669;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 100px;
}

.bento-middle {
    margin-bottom: 1rem;
}
.bento-num {
    font-size: 3.5rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
    letter-spacing: -1.5px;
}
.bento-num-sm {
    font-size: 2.5rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
}
.bento-num-pct {
    font-size: 3rem;
    font-weight: 900;
    color: #2563EB;
    line-height: 1;
}
.bento-label {
    font-size: 0.95rem;
    font-weight: 800;
    color: #334155;
    margin-top: 0.5rem;
}

.bento-bottom {
    margin-top: auto;
}
.bento-desc {
    font-size: 0.82rem;
    color: #64748b;
    line-height: 1.6;
}
.bento-subtext {
    font-size: 0.78rem;
    font-weight: 700;
    color: #94a3b8;
}

.bento-pulse-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(124, 58, 237, 0.06);
    border: 1px solid rgba(124, 58, 237, 0.12);
    padding: 5px 12px;
    border-radius: 100px;
}
.bento-pulse-dot {
    width: 6px; height: 6px;
    background: #7c3aed;
    border-radius: 50%;
    animation: bentoPulse 2s infinite;
}
.bento-pulse-text {
    font-size: 0.7rem;
    font-weight: 800;
    color: #7c3aed;
    text-transform: uppercase;
}
@keyframes bentoPulse {
    0%, 100% { transform: scale(0.9); opacity: 0.6; }
    50% { transform: scale(1.3); opacity: 1; box-shadow: 0 0 8px rgba(124, 58, 237, 0.8); }
}

.bento-progress-track {
    height: 8px;
    background: #f1f5f9;
    border-radius: 100px;
    overflow: hidden;
    width: 100%;
    margin-top: 1rem;
}
.bento-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2563EB, #0EA5E9);
    border-radius: 100px;
    transition: width 2s cubic-bezier(0.16, 1, 0.3, 1);
}

.bento-trainees:hover {
    border-color: rgba(37, 99, 235, 0.3);
}
.bento-sync:hover {
    border-color: rgba(124, 58, 237, 0.3);
}
.bento-etabs:hover {
    border-color: rgba(5, 150, 105, 0.3);
}
.bento-wilayas:hover {
    border-color: rgba(217, 119, 6, 0.3);
}

@media (max-width: 991px) {
    .home-bento-grid {
        grid-template-columns: 1fr 1fr !important;
    }
    .home-bento-large {
        grid-column: span 2 !important;
    }
}
@media (max-width: 767px) {
    .home-bento-grid {
        grid-template-columns: 1fr !important;
    }
    .home-bento-large, .home-bento-medium, .home-bento-small {
        grid-column: span 1 !important;
    }
    .bento-num {
        font-size: 2.75rem;
    }
}
</style>

{{-- ══════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════ --}}
<script>
/* ── Demo verification ── */
const demoData = {
    'STG-001': { name: 'عبد الرحمن بن علي', mat: 'STG-001', etab: 'المعهد الوطني المتخصص — السانية', spec: 'تطوير البرمجيات وإدارة الشبكات', moy: '16.85 / 20', mention: 'حسن جداً', avatar: 'ع' },
    'STG-002': { name: 'يوسفي سارة',        mat: 'STG-002', etab: 'مركز التكوين المهني — سعيدة',    spec: 'محاسبة وتسيير المؤسسات',        moy: '15.40 / 20', mention: 'حسن',      avatar: 'ي' },
    'STG-003': { name: 'بن عمر كريم',       mat: 'STG-003', etab: 'مركز التكوين المهني — وهران',    spec: 'الميكانيك الصناعية',             moy: '17.20 / 20', mention: 'ممتاز',    avatar: 'ك' },
};

function heroDemo(mat) {
    document.getElementById('heroMatricule').value = mat;
    const d = demoData[mat];
    if (!d) return;
    const panel = document.getElementById('heroResultPanel');
    panel.classList.remove('d-none');
    // Reset laser
    const laser = document.getElementById('heroLaserBeam');
    laser.style.animation = 'none';
    laser.offsetHeight; // reflow
    laser.style.animation = 'laserSweep 2s ease';
    // Populate
    document.getElementById('heroAvatar').innerText   = d.avatar;
    document.getElementById('heroName').innerText     = d.name;
    document.getElementById('heroMat').innerText      = d.mat;
    document.getElementById('heroEtab').innerText     = d.etab;
    document.getElementById('heroSpec').innerText     = d.spec;
    document.getElementById('heroMoy').innerText      = d.moy;
    document.getElementById('heroMention').innerText  = d.mention;
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/* ── Count-up animation for stats ── */
document.addEventListener('DOMContentLoaded', () => {
    // Animate stat numbers
    const counters = document.querySelectorAll('[data-target]');
    const animateCounter = (el) => {
        const target = parseInt(el.dataset.target);
        const duration = 2000;
        const start = performance.now();
        const update = (time) => {
            const elapsed = Math.min((time - start) / duration, 1);
            const ease = 1 - Math.pow(1 - elapsed, 3);
            el.innerText = Math.floor(ease * target).toLocaleString('ar-DZ');
            if (elapsed < 1) requestAnimationFrame(update);
            else el.innerText = target.toLocaleString('ar-DZ');
        };
        requestAnimationFrame(update);
    };

    // Animate progress bars
    const bars = document.querySelectorAll('[data-width]');
    const animateBar = (el) => {
        setTimeout(() => { el.style.width = el.dataset.width; }, 300);
    };

    // IntersectionObserver to trigger on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                if (e.target.dataset.target) animateCounter(e.target);
                if (e.target.dataset.width)  animateBar(e.target);
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });

    counters.forEach(el => observer.observe(el));
    bars.forEach(el => observer.observe(el));

    // Bento mouse-move glow effect
    const bCards = document.querySelectorAll('.home-bento-card');
    bCards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });
});
</script>

@endsection
