<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'منصة تسيير - وزارة التكوين والتعليم المهنيين'); ?></title>

    <!-- CSRF Shield & AJAX Configuration -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <script>
        (function() {
            const token = '<?php echo e(csrf_token()); ?>';
            
            // Function to inject token input to forms
            function injectCsrfInputs() {
                document.querySelectorAll('form').forEach(form => {
                    const method = (form.getAttribute('method') || '').toUpperCase();
                    if (method === 'POST') {
                        if (!form.querySelector('input[name="_token"]') && !form.querySelector('input[name="csrf_token"]')) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = '_token';
                            input.value = token;
                            form.appendChild(input);
                        }
                    }
                });
            }

            // Run immediately and setup listeners
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    injectCsrfInputs();
                    setupAjax();
                });
            } else {
                injectCsrfInputs();
                setupAjax();
            }

            // MutationObserver to handle dynamically added forms (e.g. via AJAX, Modals, etc.)
            if (window.MutationObserver) {
                const observer = new MutationObserver(injectCsrfInputs);
                observer.observe(document.documentElement, {
                    childList: true,
                    subtree: true
                });
            }

            // Double check on submit event
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form && form.tagName === 'FORM') {
                    const method = (form.getAttribute('method') || '').toUpperCase();
                    if (method === 'POST') {
                        if (!form.querySelector('input[name="_token"]') && !form.querySelector('input[name="csrf_token"]')) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = '_token';
                            input.value = token;
                            form.appendChild(input);
                        }
                    }
                }
            });

            // Setup AJAX default headers (Axios / jQuery)
            function setupAjax() {
                if (typeof axios !== 'undefined') {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                }
                if (typeof jQuery !== 'undefined') {
                    jQuery.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': token
                        }
                    });
                }
            }

            // Intercept native fetch for same-origin state-changing requests
            const { fetch: originalFetch } = window;
            window.fetch = async (...args) => {
                let [resource, config] = args;
                let method = 'GET';
                let isSameOrigin = true;

                if (resource instanceof Request) {
                    method = resource.method || 'GET';
                    const urlString = resource.url || '';
                    isSameOrigin = !urlString.startsWith('http://') && !urlString.startsWith('https://') || 
                                   urlString.startsWith(window.location.origin);
                } else {
                    if (config) {
                        method = config.method || 'GET';
                    }
                    const urlString = typeof resource === 'string' ? resource : '';
                    isSameOrigin = !urlString.startsWith('http://') && !urlString.startsWith('https://') || 
                                   urlString.startsWith(window.location.origin);
                }

                method = method.toUpperCase();
                if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method) && isSameOrigin) {
                    if (resource instanceof Request) {
                        try {
                            resource.headers.set('X-CSRF-TOKEN', token);
                        } catch (e) {
                            if (!config) config = {};
                            if (!config.headers) config.headers = {};
                            config.headers['X-CSRF-TOKEN'] = token;
                        }
                    } else {
                        if (!config) config = {};
                        if (!config.headers) config.headers = {};
                        if (config.headers instanceof Headers) {
                            if (!config.headers.has('X-CSRF-TOKEN')) {
                                config.headers.append('X-CSRF-TOKEN', token);
                            }
                        } else if (Array.isArray(config.headers)) {
                            const hasCsrf = config.headers.some(([key]) => key.toLowerCase() === 'x-csrf-token');
                            if (!hasCsrf) {
                                config.headers.push(['X-CSRF-TOKEN', token]);
                            }
                        } else {
                            if (!config.headers['X-CSRF-TOKEN'] && !config.headers['x-csrf-token']) {
                                config.headers['X-CSRF-TOKEN'] = token;
                            }
                        }
                    }
                }
                return originalFetch(resource, config);
            };
        })();
    </script>
    
    <!-- Modern Premium Typography: Plus Jakarta Sans, Inter, Geist, & Cairo (Arabic) -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    
    <!-- FontAwesome 6 for Sovereign Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Animate.css for smooth state transitions -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- App Design System (Sovereign Tokens) -->
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/app.tailwind.css?v=1.0')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/app.css?v=5.1')); ?>">
    
    <!-- Sovereign Design System — loads LAST to override all legacy styles -->
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/design-system.css?v=2.1')); ?>">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo e(asset('manifest.json')); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="منصة تسيير">
    <link rel="apple-touch-icon" href="<?php echo e(asset('assets/icons/icon-192x192.png')); ?>">
    
    <style>
        :root {
            /* Premium Light Color Palette Configuration */
            --bg-portal: #EEF2F6;
            --bg-surface: #FFFFFF;
            --bg-surface-elevated: #FFFFFF;
            --primary-accent: #2563EB;
            --secondary-accent: #0EA5E9;
            --text-primary: #0F172A;
            --text-secondary: #64748B;
            --border-portal: rgba(15, 23, 42, 0.08);
            --glass-surface: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(15, 23, 42, 0.06);
            --transition-premium: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --shadow-premium: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
        }

        html, body {
            overflow-x: hidden;
            width: 100%;
        }

        body {
            font-family: 'Cairo', 'Inter', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-portal);
            color: var(--text-primary);
            min-height: 100vh;
            scroll-behavior: smooth;
            position: relative;
        }

        /* Abstract Subtle Geometric Grid Pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(15, 23, 42, 0.015) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 1;
        }

        /* Floating Pill Glassmorphic Header Dock (Light) */
        .gov-header {
            background: rgba(15, 27, 61, 0.45) !important;
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 20px;
            margin: 1.5rem auto 0 auto;
            padding: 0.6rem 2rem;
            position: fixed;
            top: 0;
            left: 2rem;
            right: 2rem;
            max-width: 1200px;
            z-index: 1050;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .gov-header.scrolled {
            background: rgba(255, 255, 255, 0.8) !important;
            border: 1px solid rgba(15, 23, 42, 0.08) !important;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.6) !important;
        }
        
        [data-theme="dark"] .gov-header {
            background: rgba(15, 23, 42, 0.55) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        }

        .navbar-brand img {
            height: 48px;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.05));
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        .gov-header.scrolled .navbar-brand img {
            filter: brightness(0) invert(22%) sepia(93%) saturate(2581%) hue-rotate(218deg) brightness(96%) contrast(93%);
        }
        [data-theme="dark"] .gov-header.scrolled .navbar-brand img {
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.05));
        }

        .gov-title-text {
            color: #ffffff;
            text-align: right;
            line-height: 1.45;
            transition: color 0.4s ease;
        }
        .gov-header.scrolled .gov-title-text {
            color: var(--text-primary);
        }
        
        .gov-title-text .country {
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.65);
            display: block;
            font-weight: 700;
            transition: color 0.4s ease;
            margin-bottom: 3px;
        }
        .gov-header.scrolled .gov-title-text .country {
            color: var(--text-secondary);
        }

        .gov-title-text .ministry {
            font-size: 0.85rem;
            font-weight: 800;
            color: #ffffff;
            display: block;
            transition: color 0.4s ease;
        }
        .gov-header.scrolled .gov-title-text .ministry {
            color: var(--text-primary);
        }

        /* Navigation Links with Glass Hover */
        .gov-nav .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 0.6rem 1.25rem !important;
            border-radius: 14px;
            margin: 0 0.2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 0.45rem;
            position: relative;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .gov-header.scrolled .gov-nav .nav-link {
            color: #475569 !important;
        }
        
        .gov-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%) scaleX(0);
            width: 30%;
            height: 3px;
            background: linear-gradient(90deg, #60a5fa 0%, #34d399 100%);
            border-radius: 99px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
        }
        .gov-header.scrolled .gov-nav .nav-link::after {
            background: linear-gradient(90deg, #0284c7 0%, #38bdf8 100%);
        }

        .gov-nav .nav-link:hover {
            color: #60a5fa !important;
            background: transparent !important;
        }
        .gov-header.scrolled .gov-nav .nav-link:hover {
            color: #0284c7 !important;
        }
        
        .gov-nav .nav-link:hover::after {
            transform: translateX(-50%) scaleX(1);
            width: 50%;
        }
        
        .gov-nav .nav-link.active {
            color: #60a5fa !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }
        .gov-header.scrolled .gov-nav .nav-link.active {
            color: #0284c7 !important;
            background: rgba(2, 132, 199, 0.07) !important;
            border-color: rgba(2, 132, 199, 0.08) !important;
        }
        
        .gov-nav .nav-link.active::after {
            transform: translateX(-50%) scaleX(1);
            width: 50%;
        }

        /* Responsive Mobile Toggler Color */
        .navbar-toggler i {
            color: #ffffff !important;
            transition: color 0.4s ease;
        }
        .gov-header.scrolled .navbar-toggler i {
            color: var(--text-primary) !important;
        }

        /* Sovereign Administration Button */
        .btn-nav-login {
            background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            font-weight: 800;
            font-size: 0.85rem;
            padding: 0.65rem 1.5rem !important;
            border-radius: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.2) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-nav-login:hover {
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.35) !important;
            transform: translateY(-2px);
            filter: brightness(1.08);
        }
        .btn-nav-login i {
            transition: transform 0.3s ease;
        }
        .btn-nav-login:hover i {
            transform: scale(1.18) rotate(-8deg);
        }

        .public-content {
            margin-top: 130px;
            position: relative;
            z-index: 10;
            overflow-x: hidden;
        }

        /* Official Premium Footer (New Gradient Design) */
        .gov-footer {
            background: linear-gradient(135deg, #0f1b3d 0%, #172a5b 50%, #1d4ed8 100%) !important;
            border-top: none !important;
            padding: 0 0 3rem 0;
            transition: var(--transition-premium);
            position: relative;
            z-index: 10;
            box-shadow: 0 -10px 40px rgba(15, 23, 42, 0.05) !important;
        }

        [data-theme="dark"] .gov-footer {
            background: linear-gradient(135deg, #090e1f 0%, #0f1b3d 100%) !important;
            border-top: none !important;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.3) !important;
        }

        .gov-footer .container {
            padding-top: 3.5rem;
        }

        /* Wavy SVG divider transition to dark blue footer */
        .footer-waves {
            position: relative;
            width: 100%;
            height: 60px;
            margin-bottom: -1px;
            overflow: hidden;
            background: transparent !important;
        }
        
        /* Rebranding the macOS Sonoma blobs to match Royal Blue & Cyan brand colors */
        .apple-blob-1 {
            background: radial-gradient(circle, #2563eb 0%, #60a5fa 100%) !important;
        }
        .apple-blob-2 {
            background: radial-gradient(circle, #0ea5e9 0%, #38bdf8 100%) !important;
        }
        .apple-blob-3 {
            background: radial-gradient(circle, #1e3a8a 0%, #3b82f6 100%) !important;
        }
        .footer-waves .waves {
            width: 100%;
            height: 100%;
            margin-bottom: -1px;
        }
        .parallax-footer > use {
            animation: move-forever 25s cubic-bezier(.55,.5,.45,.5) infinite;
        }
        .parallax-footer > use:nth-child(1) {
            animation-delay: -2s;
            animation-duration: 7s;
        }
        .parallax-footer > use:nth-child(2) {
            animation-delay: -3s;
            animation-duration: 10s;
        }
        .parallax-footer > use:nth-child(3) {
            animation-delay: -4s;
            animation-duration: 13s;
        }
        .parallax-footer > use:nth-child(4) {
            animation-delay: -5s;
            animation-duration: 20s;
        }
        .parallax-footer .wave-layer-1 {
            fill: rgba(29, 78, 216, 0.15);
        }
        .parallax-footer .wave-layer-2 {
            fill: rgba(14, 165, 233, 0.1);
        }
        .parallax-footer .wave-layer-3 {
            fill: rgba(255, 255, 255, 0.05);
        }
        .parallax-footer .wave-layer-4 {
            fill: #0f1b3d;
        }
        [data-theme="dark"] .parallax-footer .wave-layer-4 {
            fill: #090e1f;
        }

        @keyframes move-forever {
            0% {
                transform: translate3d(-90px,0,0);
            }
            100% {
                transform: translate3d(85px,0,0);
            }
        }

        .footer-brand-logo {
            height: 48px;
        }

        .gov-footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding-top: 2rem;
            margin-top: 2rem;
        }
        .gov-footer-bottom a {
            color: rgba(255, 255, 255, 0.6) !important;
            text-decoration: none;
            margin: 0 0.8rem;
            font-size: 0.82rem;
            font-weight: 600;
            transition: var(--transition-premium);
        }
        .gov-footer-bottom a:hover {
            color: #60a5fa !important;
        }

        .footer-sec-title {
            font-size: 0.9rem;
            font-weight: 800;
            color: #ffffff !important;
            margin-bottom: 1.25rem;
            position: relative;
            padding-right: 0.75rem;
        }
        .footer-sec-title::before {
            content: '';
            position: absolute;
            right: 0;
            top: 4px;
            bottom: 4px;
            width: 3px;
            background: #60a5fa !important;
            border-radius: 2px;
        }

        /* Styling overrides to make text readable on dark blue footer background */
        .gov-footer .gov-title-text {
            color: #ffffff !important;
        }
        .gov-footer .gov-title-text .country {
            color: rgba(255, 255, 255, 0.65) !important;
        }
        .gov-footer .gov-title-text .ministry {
            color: #ffffff !important;
        }
        .gov-footer .text-secondary {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .gov-footer ul.list-unstyled li {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .gov-footer ul.list-unstyled li i.text-primary {
            color: #60a5fa !important;
        }
        .gov-footer ul.list-unstyled a.text-secondary {
            color: rgba(255, 255, 255, 0.7) !important;
            transition: var(--transition-premium);
        }
        .gov-footer ul.list-unstyled a.text-secondary:hover {
            color: #60a5fa !important;
            padding-right: 4px;
        }

        @media (max-width: 1200px) {
            .gov-nav .nav-link {
                padding: 0.6rem 0.75rem !important;
                font-size: 0.82rem;
            }
            .gov-title-text .country {
                display: none !important;
            }
            .gov-title-text .ministry {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 991px) {
            .gov-header {
                left: 1rem !important;
                right: 1rem !important;
                width: auto !important;
                margin: 0.75rem 0 0 0 !important;
                padding: 0.5rem 1rem;
                border-radius: 18px;
            }
            .public-content {
                margin-top: 110px;
            }
        }
        
        /* ─── Sovereign Content Shield CSS ─── */
        @media print {
            body {
                display: none !important;
            }
        }
        .sovereign-content-shield {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.98);
            z-index: 999999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-family: 'Cairo', sans-serif;
            text-align: center;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            transition: opacity 0.3s ease;
        }
        .sovereign-content-shield.active {
            display: flex !important;
        }
        .sovereign-watermark-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            user-select: none;
            z-index: 99999;
            overflow: hidden;
        }
        .sovereign-watermark {
            position: absolute;
            font-size: 14px;
            font-weight: 800;
            color: rgba(15, 23, 42, 0.045);
            white-space: nowrap;
            font-family: 'Cairo', 'Outfit', sans-serif;
            transform: rotate(-25deg);
            animation: watermark-drift 40s linear infinite alternate;
        }
        [data-theme="dark"] .sovereign-watermark {
            color: rgba(255, 255, 255, 0.045);
        }
        @keyframes watermark-drift {
            0% { transform: translate(0, 0) rotate(-25deg); }
            50% { transform: translate(100px, 80px) rotate(-20deg); }
            100% { transform: translate(-80px, 150px) rotate(-30deg); }
        }
        .public-content.shield-active {
            filter: blur(20px) !important;
            transition: filter 0.2s ease;
        }
    </style>
    <?php echo $__env->yieldContent('styles'); ?>
</head>
<body>
    <!-- Apple macOS Sonoma Wallpaper Background -->
    <div class="apple-bg-wall">
        <div class="apple-blob apple-blob-1"></div>
        <div class="apple-blob apple-blob-2"></div>
        <div class="apple-blob apple-blob-3"></div>
        <div class="apple-grid-overlay"></div>
    </div>

    <!-- Sovereign Floating Header Dock (Light) -->
    <header class="gov-header">
        <div class="container-fluid p-0">
            <nav class="navbar navbar-expand-lg p-0">
                
                <!-- Logo & Naming -->
                <a class="navbar-brand d-flex align-items-center m-0" href="<?php echo e(url('/')); ?>">
                    <img src="<?php echo e(asset('assets/images/logo.png')); ?>" alt="شعار الوزارة">
                    <div class="gov-title-text d-none d-md-block me-3">
                        <span class="country">الجمهورية الجزائرية الديمقراطية الشعبية</span>
                        <span class="ministry">وزارة التكوين والتعليم المهنيين</span>
                    </div>
                </a>

                <!-- Mobile Menu Toggler -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#govNavbar" aria-controls="govNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars-staggered" style="font-size:1.3rem;"></i>
                </button>

                <!-- Menu Links -->
                <div class="collapse navbar-collapse" id="govNavbar">
                    <ul class="navbar-nav mx-auto gov-nav mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->is('/') ? 'active' : ''); ?>" href="<?php echo e(url('/')); ?>"><i class="fa-solid fa-house"></i> الرئيسية</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->is('portal/features') ? 'active' : ''); ?>" href="<?php echo e(url('portal/features')); ?>"><i class="fa-solid fa-star"></i> ميزات المنصة</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->is('portal/about') ? 'active' : ''); ?>" href="<?php echo e(url('portal/about')); ?>"><i class="fa-solid fa-info-circle"></i> عن المنصة</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->is('portal/how-it-works') ? 'active' : ''); ?>" href="<?php echo e(url('portal/how-it-works')); ?>"><i class="fa-solid fa-gears"></i> كيفية العمل</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->is('portal/privacy') ? 'active' : ''); ?>" href="<?php echo e(url('portal/privacy')); ?>"><i class="fa-solid fa-shield-halved"></i> سياسة الخصوصية</a>
                        </li>
                    </ul>

                    <!-- Controls -->
                    <div class="d-flex align-items-center gap-3">
                        <a href="<?php echo e(url('login')); ?>" class="btn btn-nav-login d-flex align-items-center gap-2">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>فضاء الموظف</span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Content Slot -->
    <main class="public-content">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <!-- Sovereign Footer -->
    <footer class="gov-footer">
        <!-- Footer Wave Divider -->
        <div class="footer-waves">
            <svg class="waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
                <defs>
                    <path id="footer-wave" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18v44h-352z" />
                </defs>
                <g class="parallax-footer">
                    <use xlink:href="#footer-wave" x="48" y="0" class="wave-layer-1" />
                    <use xlink:href="#footer-wave" x="48" y="3" class="wave-layer-2" />
                    <use xlink:href="#footer-wave" x="48" y="5" class="wave-layer-3" />
                    <use xlink:href="#footer-wave" x="48" y="7" class="wave-layer-4" />
                </g>
            </svg>
        </div>

        <div class="container">
            <div class="row g-4">
                
                <!-- Institutional Column -->
                <div class="col-lg-5 col-md-6">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="<?php echo e(asset('assets/images/logo.png')); ?>" alt="وزارة التكوين المهني" class="footer-brand-logo">
                        <div class="gov-title-text text-start">
                            <span class="country">الجمهورية الجزائرية الديمقراطية الشعبية</span>
                            <span class="ministry" style="font-size: 0.95rem;">وزارة التكوين والتعليم المهنيين</span>
                        </div>
                    </div>
                    <p class="text-secondary small mt-3" style="line-height: 1.8;">
                        البوابة الرقمية المعتمدة للتسيير الشامل (ERP) وحوكمة قطاع التكوين والتعليم المهنيين على المستوى الوطني.
                    </p>
                </div>
                
                <!-- Navigation Column -->
                <div class="col-lg-3 col-md-6 ms-auto">
                    <h6 class="footer-sec-title">الخدمات السريعة</h6>
                    <ul class="list-unstyled text-secondary small" style="line-height: 2.2;">
                        <li><a href="https://takwin.dz" target="_blank" rel="noopener" class="text-decoration-none text-secondary hover-accent"><i class="fa-solid fa-chevron-left me-1" style="font-size: 0.7rem;"></i> التسجيل عبر منصة تكوين</a></li>
                        <li><a href="<?php echo e(url('portal/features')); ?>" class="text-decoration-none text-secondary hover-accent"><i class="fa-solid fa-chevron-left me-1" style="font-size: 0.7rem;"></i> ميزات وخصائص المنصة</a></li>
                        <li><a href="<?php echo e(url('portal/about')); ?>" class="text-decoration-none text-secondary hover-accent"><i class="fa-solid fa-chevron-left me-1" style="font-size: 0.7rem;"></i> عن منصة تسيير الموحدة</a></li>
                    </ul>
                </div>

                <!-- Contact Column -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="footer-sec-title">اتصل بنا</h6>
                    <ul class="list-unstyled text-secondary small" style="line-height: 2;">
                        <li><i class="fa-solid fa-map-marker-alt text-primary me-2"></i> وزارة التكوين والتعليم المهنيين، الجزائر العاصمة</li>
                        <li><i class="fa-solid fa-envelope text-primary me-2"></i> contact@mfep.gov.dz</li>
                        <li><i class="fa-solid fa-globe text-primary me-2"></i> www.mfep.gov.dz</li>
                    </ul>
                </div>

            </div>

            <!-- Footer Bottom -->
            <div class="gov-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center text-center">
                <div class="mb-3 mb-md-0">
                    <a href="<?php echo e(url('portal/about')); ?>">عن منصة تسيير</a>
                    <a href="<?php echo e(url('portal/features')); ?>">ميزات المنصة</a>
                    <a href="<?php echo e(url('portal/how-it-works')); ?>">كيفية عمل المنصة</a>
                    <a href="<?php echo e(url('portal/privacy')); ?>">سياسة خصوصية البيانات</a>
                    <a href="https://takwin.dz" target="_blank" rel="noopener">منصة تكوين للتسجيل</a>
                </div>
                <p class="mb-0 text-secondary" style="font-size:0.78rem;">© 2026 وزارة التكوين والتعليم المهنيين - الجمهورية الجزائرية الديمقراطية الشعبية. جميع الحقوق محفوظة</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 for Premium Modals -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.querySelector('.gov-header');
            if (!header) return;
            
            const hasHero = document.querySelector('.hero-dcode') !== null;
            if (!hasHero) {
                header.classList.add('scrolled');
                return;
            }
            
            const handleScroll = () => {
                if (window.scrollY > 40) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            };
            window.addEventListener('scroll', handleScroll);
            handleScroll(); // run immediately on page load
        });

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register("<?php echo e(url('')); ?>/sw.js")
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    }, function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>
    <script src="<?php echo e(asset('assets/js/pwa-prompt.js?v=1.0')); ?>"></script>

<?php if(\App\Helpers\SovereignLicensingHelper::getSetting('content_protection_shield_active', '1') === '1'): ?>
<!-- Sovereign Content Shield Overlay -->
<div id="sovereignContentShield" class="sovereign-content-shield">
    <div class="text-center p-4">
        <i class="fa-solid fa-shield-halved text-warning mb-4 animate__animated animate__pulse animate__infinite" style="font-size: 4.5rem; display: block; margin: 0 auto 20px;"></i>
        <h3 class="fw-bold mb-2" style="font-family:'Cairo';">درع حماية المحتوى نشط</h3>
        <p class="text-white-50 mb-0" style="font-size:0.95rem;">غير مسموح بالتقاط صور للشاشة أو تصوير الفيديو لمحتوى المنصة الرسمية.</p>
        <span class="badge bg-danger rounded-pill px-3 py-1.5 mt-4 small" style="font-family:'Outfit';">SECURITY BLOCK ACTIVE</span>
    </div>
</div>

<!-- Dynamic Watermarks Container -->
<?php
    $publicUser = session('user') ?? [];
    $watermarkText = 'تسيير - ' . (!empty($publicUser['nom_complet']) ? $publicUser['nom_complet'] : 'بوابة عامة') . ' - ' . date('Y-m-d H:i');
?>
<div class="sovereign-watermark-container">
    <div class="sovereign-watermark" style="top: 15%; left: 10%;"><?php echo e($watermarkText); ?></div>
    <div class="sovereign-watermark" style="top: 40%; left: 60%; animation-delay: -10s;"><?php echo e($watermarkText); ?></div>
    <div class="sovereign-watermark" style="top: 75%; left: 20%; animation-delay: -20s;"><?php echo e($watermarkText); ?></div>
    <div class="sovereign-watermark" style="top: 85%; left: 75%; animation-delay: -30s;"><?php echo e($watermarkText); ?></div>
</div>

<script>
(function() {
    const shield = document.getElementById('sovereignContentShield');
    const contentArea = document.querySelector('.public-content');
    let isPrinting = false;
    
    function showShield() {
        if (isPrinting) return;
        if (shield) shield.classList.add('active');
        if (contentArea) contentArea.classList.add('shield-active');
    }
    
    function hideShield() {
        if (shield) shield.classList.remove('active');
        if (contentArea) contentArea.classList.remove('shield-active');
    }

    // Print event listeners to bypass shield during printing
    window.addEventListener('beforeprint', function() {
        isPrinting = true;
        hideShield();
    });
    window.addEventListener('afterprint', function() {
        setTimeout(function() {
            isPrinting = false;
        }, 1000);
    });

    // 1. Focus & Page Visibility Monitors (Android/iOS safe)
    window.addEventListener('blur', showShield);
    window.addEventListener('focus', function() {
        setTimeout(hideShield, 1500);
    });
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden || document.visibilityState === 'hidden') {
            showShield();
        } else {
            setTimeout(hideShield, 1500);
        }
    });

    // 2. Gesture blocker (3-Finger Swipe Screenshot blocker on mobile)
    document.addEventListener('touchstart', function(e) {
        if (e.touches.length >= 3) {
            e.preventDefault();
            showShield();
            setTimeout(hideShield, 2000);
        }
    }, { passive: false });

    // 3. Prevent DevTools & Inspection Key Combinations
    window.addEventListener('keydown', function(e) {
        // F12
        if (e.keyCode === 123) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // Ctrl+Shift+I, J, C
        if (e.ctrlKey && e.shiftKey && (e.key.toLowerCase() === 'i' || e.key.toLowerCase() === 'j' || e.key.toLowerCase() === 'c')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // Ctrl+S
        if (e.ctrlKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // Ctrl+U
        if (e.ctrlKey && e.key.toLowerCase() === 'u') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, true);

    // 4. Overwrite context menu
    window.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    // 5. Intercept PrintScreen Key & Overwrite Clipboard
    window.addEventListener('keyup', function(e) {
        if (e.key === 'PrintScreen') {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText("درع حماية المحتوى: غير مسموح بالتقاط صور للشاشة / Content Protected").catch(() => {});
            }
            showShield();
            setTimeout(hideShield, 1500);
        }
    });
})();
</script>
<?php endif; ?>
    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\sig\resources\views/layouts/public.blade.php ENDPATH**/ ?>