@extends('layouts.public')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
@php
    $hideOtherLogins = \App\Helpers\SovereignLicensingHelper::getSetting('hide_other_login_portals', '0') === '1';
@endphp
<style>
    /* ============================================================
       APPLE macOS/iOS THEMED LOGIN PANEL STYLES
       Architecture: Split-Screen Viewport | Frosted Glass Panel | iOS Slider Tabs
       ============================================================ */
    /* Fix Bootstrap modal fogginess / stacking context issue */
    body.modal-open .public-content {
        z-index: 1060 !important;
    }

    .login-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 3.5rem 1.5rem;
        min-height: calc(100vh - 220px);
        position: relative;
        z-index: 10;
    }

    .split-login-container {
        display: grid;
        grid-template-columns: 1fr 1.15fr;
        width: 100%;
        max-width: 1060px;
        min-height: 600px;
        overflow: hidden;
        position: relative;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                    box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .split-login-container::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(400px circle at var(--x, 50%) var(--y, 50%), rgba(37, 99, 235, 0.12), transparent 80%);
        pointer-events: none;
        z-index: 1;
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    [data-theme="dark"] .split-login-container::before {
        background: radial-gradient(400px circle at var(--x, 50%) var(--y, 50%), rgba(16, 185, 129, 0.18), transparent 80%);
    }
    .split-login-container:hover::before {
        opacity: 1;
    }

    /* Left Pane: Elegant Glass Form Container */
    .login-form-side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 4.5rem 3.5rem;
        position: relative;
        background: rgba(255, 255, 255, 0.15);
        transition: background-color 0.4s ease;
        z-index: 5;
    }
    
    [data-theme="dark"] .login-form-side {
        background: rgba(10, 16, 28, 0.25);
    }

    /* Right Pane: Majestic Sovereign Branding Graphic */
    .login-graphic-side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 4rem;
        background: linear-gradient(135deg, #0f1b3d 0%, #1a2d6e 40%, #1d4ed8 100%);
        color: #ffffff;
        position: relative;
        text-align: center;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        overflow: hidden;
    }
    
    [data-theme="dark"] .login-graphic-side {
        background: linear-gradient(135deg, #07122a 0%, #0f1b3d 60%, #1a2d6e 100%);
        border-right-color: rgba(255, 255, 255, 0.04);
    }

    .login-graphic-side::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(105deg, 
            rgba(255, 255, 255, 0) 30%, 
            rgba(255, 255, 255, 0.08) 50%, 
            rgba(255, 255, 255, 0) 70%
        );
        background-size: 200% 200%;
        background-position: var(--bg-x, 50%) var(--bg-y, 50%);
        pointer-events: none;
        z-index: 2;
        mix-blend-mode: color-dodge;
        transition: background-position 0.15s ease-out;
    }

    /* Ambient backgrounds matching new home design */
    .login-shapes {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 1;
    }
    .login-shape {
        position: absolute;
        border-radius: 50%;
        opacity: 0.12;
    }
    .login-shape-1 {
        width: 300px; height: 300px;
        background: radial-gradient(circle, #60a5fa 0%, transparent 70%);
        top: -50px; right: -50px;
        animation: floatShapeLogin 12s ease-in-out infinite;
    }
    .login-shape-2 {
        width: 250px; height: 250px;
        background: radial-gradient(circle, #34d399 0%, transparent 70%);
        bottom: -50px; left: -50px;
        animation: floatShapeLogin 16s ease-in-out infinite reverse;
    }
    .login-shape-ring {
        width: 350px; height: 350px;
        border: 1.5px solid rgba(255,255,255,0.06);
        border-radius: 50%;
        top: 20%; left: -80px;
        animation: spinSlowLogin 40s linear infinite;
    }
    .login-dot-grid {
        inset: 0;
        background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
        background-size: 24px 24px;
        border-radius: 0;
        opacity: 1;
    }
    @keyframes floatShapeLogin {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(15px, -20px) scale(1.05); }
    }
    @keyframes spinSlowLogin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Override input focus rings to match HSL theme colors */
    .apple-input:focus {
        border-color: #1d4ed8 !important;
        box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.12) !important;
        background: #ffffff !important;
    }
    .apple-input:focus + i {
        color: #1d4ed8 !important;
    }

    /* Override buttons to match new design system HSL gradients */
    .apple-btn-primary {
        background: linear-gradient(135deg, #1d4ed8, #0ea5e9) !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        box-shadow: 0 4px 16px rgba(29, 78, 216, 0.2) !important;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .apple-btn-primary:hover {
        transform: translateY(-1.5px) !important;
        box-shadow: 0 8px 24px rgba(29, 78, 216, 0.35) !important;
        filter: brightness(1.08) !important;
    }

    /* Segmented Tab Active overrides */
    .ios-segmented-control {
        background: rgba(15, 23, 42, 0.04) !important;
        border: 1px solid rgba(15, 23, 42, 0.05) !important;
    }
    .ios-tab-trigger.active {
        color: #1d4ed8 !important;
    }

    .form-group label {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--text-primary, #0f172a);
        margin-bottom: 0.5rem;
        letter-spacing: 0.3px;
        display: block;
    }
    
    [data-theme="dark"] .form-group label {
        color: #94a3b8;
    }

    /* Responsiveness */
    @media (max-width: 991px) {
        .split-login-container {
            grid-template-columns: 1fr;
            max-width: 480px;
            min-height: auto;
            border-radius: 20px;
        }
        .login-graphic-side {
            display: none !important;
        }
        .login-form-side {
            padding: 3.5rem 2rem;
        }
    }
</style>

<div class="login-wrapper">
    <div class="split-login-container apple-glass-panel">
        
        <!-- Left Pane: Elegant Form Container -->
        <div class="login-form-side justify-content-center">
            
            <div class="animate__animated animate__fadeInUp" style="width: 100%; max-width: 390px; margin: 0 auto;">
                
                <!-- Header -->
                <div class="text-right mb-4">
                    <h3 class="fw-bold text-dark mb-1" style="font-size: 1.45rem; font-weight: 900; font-family: 'Cairo', sans-serif; color: var(--text-primary) !important;">بوابة الدخول الموحدة</h3>
                    <p id="login-subtitle" class="text-muted small" style="font-weight: 600;">
                        {{ $hideOtherLogins ? 'بوابة دخول المؤسسات التكوينية' : 'يرجى اختيار فئة الحساب لإتمام المصادقة والولوج' }}
                    </p>
                </div>

                <!-- iOS-Style Segmented Tab Controls -->
                @if($hideOtherLogins)
                <div class="ios-segmented-control mb-4" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="ios-slider-pill"></div>
                    <button type="button" class="ios-tab-trigger active" id="btn-etablissement" onclick="switchLoginType('etablissement')">
                        مؤسسة تكوينية
                    </button>
                    <button type="button" class="ios-tab-trigger" id="btn-special" onclick="switchLoginType('special')">
                        حساب خاص
                    </button>
                </div>
                @else
                <div class="ios-segmented-control mb-4" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="ios-slider-pill"></div>
                    <button type="button" class="ios-tab-trigger active" id="btn-employee" onclick="switchLoginType('employee')">
                        موظف / أستاذ
                    </button>
                    <button type="button" class="ios-tab-trigger" id="btn-etablissement" onclick="switchLoginType('etablissement')">
                        مؤسسة تكوينية
                    </button>
                    <button type="button" class="ios-tab-trigger" id="btn-apprenant" onclick="switchLoginType('apprenant')">
                        متربص
                    </button>
                    <button type="button" class="ios-tab-trigger" id="btn-special" onclick="switchLoginType('special')">
                        حساب خاص
                    </button>
                </div>
                @endif

                <!-- Alerts -->
                <?php if (isset($error) || session()->has('flash_error') || session()->has('error')): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX" style="margin-bottom: 1.5rem; border-radius: 12px; font-weight: 700; text-align: center; border: none; background-color: rgba(229, 62, 62, 0.08); color: #ef4444; font-size: 0.82rem;">
                        <i class="fa-solid fa-circle-exclamation me-1.5"></i> <?= htmlspecialchars($error ?? session('flash_error') ?? session('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success) || session()->has('success')): ?>
                    <div class="alert alert-success" style="margin-bottom: 1.5rem; border-radius: 12px; font-weight: 700; text-align: center; border: none; background-color: rgba(14, 166, 110, 0.08); color: #10b981; font-size: 0.82rem;">
                        <i class="fa-solid fa-circle-check me-1.5"></i> <?= htmlspecialchars($success ?? session('success')) ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form action="/login" method="POST" id="login-form">
                    @csrf
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <input type="hidden" name="login_type" id="login_type" value="{{ $hideOtherLogins ? 'etablissement' : 'employee' }}">
                    <input type="hidden" name="employee_password" id="employee_password_hidden">

                    <!-- Primary Username / NIN Field -->
                    <div class="form-group mb-3 text-right">
                        <label id="username-label">رقم التعريف الوطني / NIN *</label>
                        <div class="apple-input-wrapper">
                            <input type="text" name="username" id="username-input" required class="apple-input" placeholder="أدخل رقم التعريف الوطني (NIN)">
                            <i id="username-icon" class="fa-solid fa-id-card"></i>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group mb-3 text-right">
                        <label id="password-label">كلمة المرور / Mot de passe *</label>
                        <div class="apple-input-wrapper">
                            <input type="password" name="password" required class="apple-input" placeholder="••••••••">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>

                    <!-- Secret User Code (Required for school level accounts) -->
                    <div class="form-group mb-4 text-right d-none" id="secret-code-group">
                        <label>الرمز السري للمستخدم / Code secret *</label>
                        <div class="apple-input-wrapper">
                            <input type="password" name="secret_code" id="secret_code" class="apple-input" placeholder="••••••••">
                            <i class="fa-solid fa-key"></i>
                        </div>
                    </div>

                    <?php if (isset($is_captcha_active) && $is_captcha_active): ?>
                    <!-- Mathematical CAPTCHA -->
                    <div class="form-group mb-3 text-right">
                        <label>التحقق البشري / CAPTCHA (<?= htmlspecialchars($captcha_question) ?>) *</label>
                        <div class="apple-input-wrapper">
                            <input type="number" name="captcha" required class="apple-input" placeholder="أدخل النتيجة هنا / Entrez le résultat">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <button type="submit" class="apple-btn-primary w-100 mt-2">
                        <span>تسجيل الدخول / Se connecter</span>
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </button>
                </form>

                <!-- Helper Text Block -->
                <div class="mt-4 pt-3 border-top text-center" style="border-color: rgba(15, 23, 42, 0.08) !important;">
                    <p class="mb-0 text-muted small" id="demo-credentials-text" style="font-weight: 600; line-height: 1.6;">
                        <span class="badge mb-2 px-2.5 py-1.5 bg-primary-100 text-primary-700 rounded-pill">دخول الموظفين / Accès Employés</span><br>
                        يرجى إدخال رقم التعريف الوطني المكون من 18 رقماً وكلمة المرور الخاصة بك.
                    </p>
                </div>
            </div>

        </div>

        <!-- Right Pane: Majestic Governmental Branding Graphic -->
        <div class="login-graphic-side">
            <!-- Ambient backgrounds matching new home design -->
            <div class="login-shapes">
                <div class="login-shape login-shape-1"></div>
                <div class="login-shape login-shape-2"></div>
                <div class="login-shape login-shape-ring"></div>
                <div class="login-shape login-dot-grid"></div>
            </div>

            <div class="animate__animated animate__fadeInDown" style="max-width: 440px; z-index: 10;">
                <img src="{{ asset('assets/images/logo.png') }}" alt="MFEP Seal" style="height: 100px; margin-bottom: 2rem; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));">
                
                <h1 class="fw-black mb-3" style="font-size: 2rem; font-weight: 900; line-height: 1.45; font-family: 'Cairo', sans-serif;">المنصة الرقمية الموحدة لقطاع التكوين</h1>
                
                <p class="text-white-50 mb-4" style="font-size: 0.92rem; line-height: 1.8; font-weight: 500;">
                    النظام الوطني الموحد (ERP) لتسيير مسارات التكوين والتعليم المهنيين، حوكمة الموارد البشرية، والتقييمات التربوية.
                </p>
                
                <div style="width: 50px; height: 3px; background-color: #f59e0b; margin: 2rem auto; border-radius: 2px;"></div>
                
                <span class="small text-white-50 fw-bold" style="letter-spacing: 0.5px; font-size: 0.72rem; text-transform: uppercase;">وزارة التكوين والتعليم المهنيين</span>
                <span class="d-block small text-white-50 mt-1" style="font-size: 0.65rem;">الجمهورية الجزائرية الديمقراطية الشعبية</span>
            </div>
        </div>

    </div>
</div>

<script>
    function switchLoginType(type) {
        document.querySelectorAll('.ios-tab-trigger').forEach(btn => btn.classList.remove('active'));
        
        const tabs     = {!! json_encode($hideOtherLogins ? ['etablissement', 'special'] : ['employee', 'etablissement', 'apprenant', 'special']) !!};
        let index      = tabs.indexOf(type);
        if (index === -1) {
            type = tabs[0];
            index = 0;
        }

        const btn = document.getElementById('btn-' + type);
        if (btn) btn.classList.add('active');

        document.getElementById('login_type').value = type;

        const slider   = document.querySelector('.ios-slider-pill');
        const tabWidth = 100 / tabs.length;
        if (slider) {
            slider.style.transform = `translateX(calc(${-index * 100}% - ${index * 2}px))`;
            slider.style.width     = `calc(${tabWidth}% - 3px)`;
        }

        const usernameInput = document.getElementById('username-input');
        usernameInput.value = '';

        const secretGroup = document.getElementById('secret-code-group');
        const secretInput = document.getElementById('secret_code');

        const subtitle = document.getElementById('login-subtitle');
        if (type === 'employee') {
            if (subtitle) subtitle.innerText = "بوابة دخول الموظفين والأساتذة";
            document.getElementById('username-label').innerText = "رقم التعريف الوطني / NIN *";
            document.getElementById('username-icon').className = "fa-solid fa-id-card";
            usernameInput.placeholder = "أدخل رقم التعريف الوطني (NIN)";
            secretGroup.classList.add('d-none');
            secretInput.removeAttribute('required');
            document.getElementById('demo-credentials-text').innerHTML = `
                <span class="badge mb-2 px-2.5 py-1.5 bg-primary-100 text-primary-700 rounded-pill">دخول الموظفين / Accès Employés</span><br>
                يرجى إدخال رقم التعريف الوطني المكون من 18 رقماً وكلمة المرور الخاصة بك.
            `;
        } else if (type === 'etablissement') {
            if (subtitle) subtitle.innerText = "بوابة دخول المؤسسات التكوينية";
            document.getElementById('username-label').innerText = "اسم مستخدم المؤسسة / Nom d'utilisateur *";
            document.getElementById('username-icon').className = "fa-solid fa-school";
            usernameInput.placeholder = "اسم مستخدم المؤسسة (Etablissement)";
            secretGroup.classList.remove('d-none');
            secretInput.removeAttribute('required');
            document.getElementById('demo-credentials-text').innerHTML = `
                <span class="badge mb-2 px-2.5 py-1.5 bg-primary-100 text-primary-700 rounded-pill">دخول المؤسسات / Accès Etablissements</span><br>
                يرجى إدخال اسم مستخدم المؤسسة، وكلمة المرور (أو الرمز السري للمصلحة مباشرة في حقل كلمة المرور).
            `;
        } else if (type === 'apprenant') {
            if (subtitle) subtitle.innerText = "بوابة دخول المتربصين";
            document.getElementById('username-label').innerText = "رقم التعريف الوطني للمتربص / NIN *";
            document.getElementById('username-icon').className = "fa-solid fa-user-graduate";
            usernameInput.placeholder = "أدخل رقم التعريف الوطني (18 رقم)";
            secretGroup.classList.add('d-none');
            secretInput.removeAttribute('required');
            document.getElementById('demo-credentials-text').innerHTML = `
                <span class="badge mb-2 px-2.5 py-1.5 rounded-pill" style="background:rgba(16,185,129,0.12);color:#10b981;">دخول المتربصين / Espace Stagiaire</span><br>
                اسم المستخدم = رقم التعريف الوطني (NIN) &nbsp;|&nbsp; كلمة المرور = NIN
            `;
        } else if (type === 'special') {
            if (subtitle) subtitle.innerText = "بوابة الدخول الخاصة";
            document.getElementById('username-label').innerText = "اسم المستخدم / Nom d'utilisateur *";
            document.getElementById('username-icon').className = "fa-solid fa-user-shield";
            usernameInput.placeholder = "اسم مستخدم الحساب الخاص (Admin)";
            secretGroup.classList.add('d-none');
            secretInput.removeAttribute('required');
            document.getElementById('demo-credentials-text').innerHTML = `
                <span class="badge mb-2 px-2.5 py-1.5 bg-primary-100 text-primary-700 rounded-pill">الحسابات الإدارية / Accès Admin</span><br>
                يرجى إدخال اسم المستخدم وكلمة المرور الخاصة بالحساب الإداري للولوج.
            `;
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Flat 2D glow tracking only — no 3D rotation
        const container = document.querySelector('.split-login-container');
        if (container) {
            container.addEventListener('mousemove', function(e) {
                const rect = container.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const percentX = (x / rect.width) * 100;
                const percentY = (y / rect.height) * 100;
                container.style.setProperty('--x', `${x}px`);
                container.style.setProperty('--y', `${y}px`);
                container.style.setProperty('--bg-x', `${percentX}%`);
                container.style.setProperty('--bg-y', `${percentY}%`);
            });
            container.addEventListener('mouseleave', function() {
                container.style.setProperty('--bg-x', '50%');
                container.style.setProperty('--bg-y', '50%');
            });
        }
        
        // Setup initial position of tabs
        const urlParams = new URLSearchParams(window.location.search);
        const loginType = urlParams.get('type') || '{{ $hideOtherLogins ? "etablissement" : "employee" }}';
        switchLoginType(loginType);

    });
</script>
@endsection
