@php
/**
 * Layout main.blade.php — SGFEP
 *
 * استعلامات قاعدة البيانات ممنوعة هنا.
 * جميع القوائم المرجعية تأتي من ReferenceCache (كاش 24h).
 * $showGlobalFilter يُحدَّد صراحةً من كل View — الافتراضي false.
 */
use App\Services\ReferenceCache;

$showGlobalFilter = $showGlobalFilter ?? false;

// بيانات المستخدم من session() فقط — لا $_SESSION
$user      = session('user') ?? [];
$roleCode  = strtolower($user['role_code'] ?? 'user');
$isApprenticeshipDept = (int)($user['IDMode_formation'] ?? 0) === 10;
$username = strtolower($user['username'] ?? '');
$isDfmUser = ($roleCode === 'central' && $username === 'dfm');
$isDrhUser = ($roleCode === 'central' && in_array($username, ['drh', 'drhinst', 'drhcentre', 'drhpb', 'drht']));
$directionCode = strtoupper($user['direction_code'] ?? $user['username'] ?? '');
$isDosfpUser = ($roleCode === 'central' && ($directionCode === 'DOSFP' || $username === 'dosfp'));
$isDepUser = ($roleCode === 'central' && ($directionCode === 'DEP' || $username === 'dep'));
$isDeohUser = ($roleCode === 'central' && ($directionCode === 'DEOH' || $username === 'deoh'));
$isDecUser = ($roleCode === 'central' && ($directionCode === 'DEC' || $username === 'dec'));
$isDfcriUser = ($roleCode === 'central' && ($directionCode === 'DFCRI' || $username === 'dfcri'));

$dept = 'general';
if ($isApprenticeshipDept || in_array($username, ['sdtpa', 'sdtpas'])) {
    $dept = 'apprentissage';
} elseif (in_array($username, ['biao', 'biaos'])) {
    $dept = 'orientation';
} elseif (in_array($username, ['dplm', 'dplms'])) {
    $dept = 'diplomes';
} elseif (in_array($username, ['sdtpp', 'sdtpps', 'sdtpc', 'sdtpcs'])) {
    $dept = 'pedagogie';
} elseif (in_array($username, ['admfine', 'admfines', 'samf', 'samfs', 'sdafm', 'sdsafms', 'sdarh', 'sdarhs', 'samrh', 'ssip'])) {
    $dept = 'administration';
}
// if ($roleCode === 'employee') { $roleCode = 'formateur'; }

$dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
$etabId = (int)($user['etablissement_id'] ?? 0);

// قوائم الفلترة — من ReferenceCache فقط (< 1ms من RAM)
if ($showGlobalFilter) {
    $filter_wilayas = match(true) {
        $roleCode === 'dfep' && $dfepId > 0 => ReferenceCache::wilayaForDfep($dfepId),
        default                              => ReferenceCache::wilayas(),
    };
    $filter_etablissements = match(true) {
        $roleCode === 'dfep' && $dfepId > 0                           => ReferenceCache::etablissementsForDfep($dfepId),
        in_array($roleCode, ['etablissement','directeur']) && $etabId > 0 => ReferenceCache::etablissementById($etabId),
        default                                                        => ReferenceCache::etablissements(),
    };
    $filter_modes = ReferenceCache::modesFormation();
} else {
    $filter_wilayas        = [];
    $filter_etablissements = [];
    $filter_modes          = [];
}

// مساعد: التحقق من المسار النشط
$path = parse_url(request()->getRequestUri(), PHP_URL_PATH);
$isActive = function($route, $exact = false) use ($path) {
    $cleanedPath = $path;
    if (str_starts_with($cleanedPath, '/sig')) {
        $cleanedPath = substr($cleanedPath, 4);
    }
    $cleanedPath = '/' . ltrim($cleanedPath, '/');
    $route = '/' . ltrim($route, '/');
    if ($exact) {
        return (rtrim($cleanedPath, '/') === rtrim($route, '/')) ? 'active' : '';
    }
    return (str_contains($cleanedPath, $route)) ? 'active' : '';
};

$hasPerm = fn($perm) => \App\Helpers\PermissionHelper::has($perm);
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ isset($userPrefs) ? $userPrefs->theme : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'منصة تسيير | التسيير الرقمي الموحد لقطاع التكوين والتعليم المهنيين')</title>
    <meta name="description" content="منصة تسيير الرقمية لوزارة التكوين والتعليم المهنيين - الجمهورية الجزائرية الديمقراطية الشعبية">

    <!-- CSRF Shield & AJAX Configuration -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function() {
            let u = '{{ url("/") }}';
            if (u.startsWith('http:') && window.location.protocol === 'https:') {
                u = u.replace('http:', 'https:');
            }
            window.laravel_url = u;
        })();
        (function() {
            const token = '{{ csrf_token() }}';
            
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

    <!-- Dark Mode Preload: Prevents flash -->
    <script>
        (function() {
            var saved = "{{ isset($userPrefs) ? $userPrefs->theme : '' }}";
            if (!saved) {
                saved = localStorage.getItem('sgfep_theme') || 'light';
            } else {
                localStorage.setItem('sgfep_theme', saved);
            }
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>

    <!-- Hardware Fingerprint & Specifications Capture Cookie -->
    <script>
        (function() {
            try {
                // 1. CPU cores
                const cpuCores = navigator.hardwareConcurrency || 'غير معروف';
                
                // 2. RAM (approximate, in GB)
                const ramSize = navigator.deviceMemory ? (navigator.deviceMemory + ' GB') : 'غير معروف';
                
                // 3. GPU (from WebGL)
                let gpu = 'غير معروف';
                try {
                    const canvas = document.createElement('canvas');
                    const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                    if (gl) {
                        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                        if (debugInfo) {
                            gpu = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || 'غير معروف';
                        }
                    }
                } catch (gpuErr) {}
                
                // Clean up GPU string if necessary
                if (gpu && gpu !== 'غير معروف') {
                    gpu = gpu.replace(/ANGLE \((.*)\)/, '$1').trim();
                }

                // 4. Screen Resolution
                const screenRes = window.screen.width + 'x' + window.screen.height;

                // 5. Operating System Platform
                let platform = navigator.platform || 'غير معروف';
                if (navigator.userAgentData && navigator.userAgentData.platform) {
                    platform = navigator.userAgentData.platform;
                }

                // Clean platform display names
                const pLower = platform.toLowerCase();
                if (pLower.includes('win')) platform = 'Windows PC';
                else if (pLower.includes('mac')) platform = 'macOS Device';
                else if (pLower.includes('linux')) platform = 'Linux PC';
                else if (pLower.includes('android')) platform = 'Android Phone';
                else if (pLower.includes('iphone') || pLower.includes('ipad')) platform = 'iOS Device';

                const specs = {
                    cpu_cores: cpuCores,
                    ram_size: ramSize,
                    gpu: gpu,
                    screen_res: screenRes,
                    platform: platform
                };

                // Store as a cookie (valid for 30 days)
                const cookieValue = encodeURIComponent(JSON.stringify(specs));
                document.cookie = "device_hardware_specs=" + cookieValue + "; path=/; max-age=" + (60 * 60 * 24 * 30) + "; SameSite=Lax";
            } catch (err) {}
        })();
    </script>

    <!-- Fonts: preconnect for speed, swap to avoid FOUT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/app.tailwind.css?v=1.0') }}">

    <!-- Legacy App CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/app.css?v=5.1') }}">

    <!-- Sovereign Design System -->
    <link rel="stylesheet" href="{{ asset('assets/css/design-system.css?v=2.1') }}">

    <!-- PWA + TWA Ready -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#003870">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="منصة تسيير">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('assets/icons/icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/icons/icon-192x192.png') }}">
    <link rel="apple-touch-startup-image" href="{{ asset('assets/icons/icon-512x512.png') }}">
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function(reg) {
                        console.log('[SGFEP] Service Worker registered. Scope:', reg.scope);
                    })
                    .catch(function(err) {
                        console.warn('[SGFEP] Service Worker registration failed:', err);
                    });
            });
        }
    </script>

    <style>
        .dock-drawer {
            position: fixed;
            top: 0;
            right: -260px;
            width: 260px;
            height: 100vh;
            background: var(--bg-glass);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-left: 1px solid var(--border-glass);
            box-shadow: -10px 0 45px rgba(2, 9, 26, 0.08);
            z-index: 495;
            transition: right var(--d-slow) var(--ease);
            padding: 1.75rem 1.25rem;
            overflow-y: auto;
        }
        
        .dock-drawer.open {
            right: var(--dock-w);
        }
        
        .dock-drawer-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--tx-1);
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Cairo', sans-serif;
        }
        
        .dock-drawer-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .dock-drawer-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--r-md);
            color: var(--tx-2);
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            transition: all var(--d-fast) var(--ease);
            border: 1px solid transparent;
        }
        
        .dock-drawer-link i {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(26, 107, 204, 0.05);
            color: var(--tx-3);
            font-size: 0.8rem;
            transition: all var(--d-fast);
        }
        
        .dock-drawer-link:hover {
            background: rgba(26, 107, 204, 0.08);
            color: var(--electric);
            transform: translateX(-4px);
            border-color: var(--electric-border);
        }
        .dock-drawer-link:hover i {
            background: var(--electric-glow);
            color: var(--electric);
        }
        
        .dock-drawer-link.active {
            background: linear-gradient(135deg, var(--electric) 0%, var(--electric-dark) 100%);
            color: #fff !important;
            box-shadow: 0 4px 12px var(--electric-glow);
            border-color: var(--electric-border);
        }
        .dock-drawer-link.active i {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .bg-light-hover:hover {
            background-color: rgba(26, 107, 204, 0.05) !important;
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
        .content-area.shield-active {
            filter: blur(20px) !important;
            transition: filter 0.2s ease;
        }
    </style>
    <script>
        const APP_URL = "{{ url('') }}";
    </script>
    @if(isset($userPrefs))
    <style id="sgfep-user-prefs-styles">
        :root {
            /* Accent color overrides */
            --sovereign-azure: {{ $userPrefs->accent_color ?? '#1a6bcc' }};
            --color-primary: {{ $userPrefs->accent_color ?? '#1a6bcc' }};
            --sovereign-azure-glow: color-mix(in srgb, var(--sovereign-azure) 12%, transparent);
            --electric-glow: color-mix(in srgb, var(--sovereign-azure) 15%, transparent);
            --electric: {{ $userPrefs->accent_color ?? '#1a6bcc' }};
        }

        /* Transparent Theme Overrides */
        [data-theme="transparent"] {
            --color-bg: transparent;
            --color-bg2: rgba(255, 255, 255, 0.12);
            --color-border: rgba(26, 107, 204, 0.15);
            --color-text: #0f2752;
            --color-text-2: #1e3a6b;
            --color-text-3: #566a8d;
            --color-glass: rgba(255, 255, 255, 0.4);
            --bg-glass: rgba(255, 255, 255, 0.4);
            --border-glass: rgba(26, 107, 204, 0.15);
            --sovereign-neutral-900: #0f2752;
            --sovereign-neutral-700: #1e3a6b;
            --sovereign-neutral-600: #3b5a95;
            --sovereign-neutral-500: #566a8d;
            --sovereign-neutral-200: rgba(26, 107, 204, 0.12);
            --sovereign-neutral-100: rgba(26, 107, 204, 0.08);
            --sovereign-neutral-50: rgba(26, 107, 204, 0.04);
            --border: rgba(26, 107, 204, 0.15);
            --tx-1: #0f2752;
            --tx-2: #1e3a6b;
            --tx-3: #566a8d;
            --bg-surface-elevated: rgba(255, 255, 255, 0.55);
        }
        [data-theme="transparent"] body {
            background: linear-gradient(135deg, rgba(26, 107, 204, 0.08) 0%, rgba(111, 66, 193, 0.08) 100%), #f4f6fb;
            background-attachment: fixed;
        }
        [data-theme="transparent"] .apple-bg-wall {
            background: linear-gradient(135deg, rgba(26, 107, 204, 0.15) 0%, rgba(111, 66, 193, 0.15) 100%), #f4f6fb !important;
        }
        [data-theme="transparent"] .apple-glass-panel, 
        [data-theme="transparent"] .glass-panel, 
        [data-theme="transparent"] .kpi-card {
            background: rgba(255, 255, 255, 0.55) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
        }
        [data-theme="transparent"] .sovereign-sidebar {
            background: rgba(255, 255, 255, 0.5) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border-left: 1px solid rgba(26, 107, 204, 0.15) !important;
        }
        [data-theme="transparent"] .sidebar-item {
            color: #1e3a6b !important;
        }
        [data-theme="transparent"] .sidebar-item:hover, 
        [data-theme="transparent"] .sidebar-item.active {
            background: rgba(26, 107, 204, 0.12) !important;
            color: var(--sovereign-azure) !important;
        }

        /* Color Theme Overrides */
        [data-theme="color"] {
            --color-bg: #1a6bcc;
            --color-bg2: rgba(255, 255, 255, 0.2);
            --color-border: rgba(255, 255, 255, 0.3);
            --color-text: #ffffff;
            --color-text-2: rgba(255, 255, 255, 0.9);
            --color-text-3: rgba(255, 255, 255, 0.7);
            --color-glass: rgba(255, 255, 255, 0.22);
            --bg-glass: rgba(255, 255, 255, 0.2);
            --border-glass: rgba(255, 255, 255, 0.25);
            --sovereign-neutral-900: #ffffff;
            --sovereign-neutral-700: rgba(255, 255, 255, 0.9);
            --sovereign-neutral-600: rgba(255, 255, 255, 0.85);
            --sovereign-neutral-500: rgba(255, 255, 255, 0.75);
            --sovereign-neutral-200: rgba(255, 255, 255, 0.25);
            --sovereign-neutral-100: rgba(255, 255, 255, 0.15);
            --sovereign-neutral-50: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.25);
            --tx-1: #ffffff;
            --tx-2: rgba(255, 255, 255, 0.9);
            --tx-3: rgba(255, 255, 255, 0.75);
            --bg-surface-elevated: rgba(255, 255, 255, 0.18);
        }
        [data-theme="color"] body {
            background: linear-gradient(135deg, #1a6bcc 0%, #6f42c1 100%);
            background-attachment: fixed;
        }
        [data-theme="color"] .apple-bg-wall {
            background: linear-gradient(135deg, #1a6bcc 0%, #6f42c1 100%) !important;
        }
        [data-theme="color"] .apple-glass-panel, 
        [data-theme="color"] .glass-panel, 
        [data-theme="color"] .kpi-card, 
        [data-theme="color"] .glass-card {
            background: rgba(255, 255, 255, 0.18) !important;
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            color: #fff !important;
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.1) !important;
        }
        [data-theme="color"] .sovereign-sidebar {
            background: rgba(255, 255, 255, 0.12) !important;
            border-left: 1px solid rgba(255, 255, 255, 0.18) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
        }
        [data-theme="color"] .sidebar-item {
            color: rgba(255, 255, 255, 0.85) !important;
        }
        [data-theme="color"] .sidebar-item:hover, 
        [data-theme="color"] .sidebar-item.active {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.18) !important;
            border-right-color: #fff !important;
        }
        [data-theme="color"] .logo-text {
            color: #fff !important;
        }
        [data-theme="color"] .command-bar {
            background: rgba(255, 255, 255, 0.15) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
        }
        [data-theme="color"] .cb-profile-name, [data-theme="color"] .cb-profile-role {
            color: #fff !important;
        }
        [data-theme="color"] .data-table th, [data-theme="color"] .premium-table th {
            background-color: rgba(255, 255, 255, 0.18) !important;
            color: #fff !important;
            border-bottom-color: rgba(255, 255, 255, 0.25) !important;
        }
        [data-theme="color"] .data-table td, [data-theme="color"] .premium-table td {
            color: #fff !important;
            border-bottom-color: rgba(255, 255, 255, 0.15) !important;
        }
        [data-theme="color"] .data-table tbody tr:hover td, [data-theme="color"] .premium-table tbody tr:hover td {
            background-color: rgba(255, 255, 255, 0.12) !important;
        }
        [data-theme="color"] .form-floating-premium input, 
        [data-theme="color"] .form-floating-premium select, 
        [data-theme="color"] .form-floating-premium textarea,
        [data-theme="color"] .apple-input,
        [data-theme="color"] .form-input {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            color: #fff !important;
        }
        [data-theme="color"] .form-floating-premium label {
            color: rgba(255, 255, 255, 0.8) !important;
        }
    </style>

    <!-- Font Size Overrides -->
    @if(($userPrefs->font_size ?? 'md') === 'sm')
    <style id="theme-font-sm">
        body, html, .app-shell, .workspace, .data-table, .premium-table {
            font-size: 0.84rem !important;
        }
        .sidebar-item {
            font-size: 0.82rem !important;
            padding: 0.6rem 1rem !important;
        }
    </style>
    @elseif(($userPrefs->font_size ?? 'md') === 'lg')
    <style id="theme-font-lg">
        body, html, .app-shell, .workspace, .data-table, .premium-table {
            font-size: 1.02rem !important;
        }
        .sidebar-item {
            font-size: 0.96rem !important;
            padding: 0.85rem 1.4rem !important;
        }
    </style>
    @endif

    <!-- Compact Mode Overrides -->
    @if($userPrefs->compact_mode)
    <style id="theme-compact-mode">
        .bento-grid, .bento-grid-premium {
            gap: 0.75rem !important;
            padding: 0.75rem !important;
        }
        .kpi-card {
            padding: 0.85rem !important;
            gap: 0.25rem !important;
        }
        .data-table td, .data-table th {
            padding: 0.45rem 0.75rem !important;
        }
        .premium-table td, .premium-table th {
            padding: 0.55rem 0.8rem !important;
        }
        .workspace {
            padding-top: 0.5rem !important;
        }
        .card, .glass-panel, .apple-glass-panel {
            border-radius: 12px !important;
            padding: 0.9rem !important;
        }
        .cb-avatar {
            width: 28px !important;
            height: 28px !important;
            font-size: 0.85rem !important;
        }
        .command-bar {
            min-height: 44px !important;
            padding: 0.35rem 0.75rem !important;
        }
    </style>
    @endif

    <!-- Animations Enabled / Disabled -->
    @if(!($userPrefs->animations_enabled ?? true))
    <style id="theme-animations-disabled">
        *, *::before, *::after {
            animation-duration: 0s !important;
            animation-delay: 0s !important;
            transition-duration: 0s !important;
            transition-delay: 0s !important;
        }
    </style>
    @endif
    @endif
    @yield('styles')
</head>
<body class="{{ (isset($userPrefs) && $userPrefs->compact_mode) ? 'compact-mode' : '' }}">

<!-- Layout Shell -->
<div class="app-shell">

    @php
    $depNames = [
        'dir_finance' => ['title' => 'مديرية المالية', 'icon' => 'fa-wallet'],
        'dir_rh' => ['title' => 'الموارد البشرية', 'icon' => 'fa-users-line'],
        'dir_plan' => ['title' => 'مديرية التنمية', 'icon' => 'fa-chart-pie'],
        'dir_coop' => ['title' => 'مديرية الدراسات', 'icon' => 'fa-handshake-angle'],
        'dir_it' => ['title' => 'المعلوماتية', 'icon' => 'fa-server'],
        'dir_exam' => ['title' => 'الامتحانات', 'icon' => 'fa-file-signature'],
        'dir_trak' => ['title' => 'التكوين المتواصل', 'icon' => 'fa-chalkboard-user'],
        'dir_edu' => ['title' => 'التعليم المهني', 'icon' => 'fa-book'],
        'dir_org' => ['title' => 'تنظيم التكوين', 'icon' => 'fa-calendar-alt']
    ];

    $depTabs = [
        'dir_finance' => [
            ['label'=>'تسيير الميزانية','icon'=>'fa-money-bill-wave','tab'=>'budget'],
            ['label'=>'الفواتير والمشتريات','icon'=>'fa-file-invoice','tab'=>'invoices'],
            ['label'=>'الصفقات والعقود','icon'=>'fa-file-signature','tab'=>'contracts'],
            ['label'=>'العتاد وجرد المخازن','icon'=>'fa-cubes','tab'=>'inventory']
        ],
        'dir_rh' => [
            ['label'=>'ملفات الموظفين','icon'=>'fa-address-card','tab'=>'staff'],
            ['label'=>'سجل الحضور','icon'=>'fa-calendar-check','tab'=>'attendance'],
            ['label'=>'الترقيات والمسابقات','icon'=>'fa-award','tab'=>'promotions'],
            ['label'=>'الأجور والرواتب','icon'=>'fa-calculator','tab'=>'salaries']
        ],
        'dir_plan' => [
            ['label'=>'تتبع المشاريع','icon'=>'fa-diagram-project','tab'=>'projects'],
            ['label'=>'مخطط التكوين السنوي','icon'=>'fa-calendar-days','tab'=>'annual_plan'],
            ['label'=>'مؤشرات الأداء','icon'=>'fa-chart-line','tab'=>'kpis'],
            ['label'=>'الإحصائيات والخرائط','icon'=>'fa-map-location-dot','tab'=>'map_stats']
        ],
        'dir_coop' => [
            ['label'=>'الاتفاقيات والشراكات','icon'=>'fa-file-contract','tab'=>'conventions'],
            ['label'=>'التعاون الدولي','icon'=>'fa-earth-africa','tab'=>'intl_coop'],
            ['label'=>'الدراسات الاستراتيجية','icon'=>'fa-book-open-reader','tab'=>'research']
        ],
        'dir_it' => [
            ['label'=>'مراقبة الخوادم','icon'=>'fa-network-wired','tab'=>'servers'],
            ['label'=>'واجهات البرمجة APIs','icon'=>'fa-key','tab'=>'apis'],
            ['label'=>'الأمن السيبراني','icon'=>'fa-shield-halved','tab'=>'cyber'],
            ['label'=>'النسخ الاحتياطي','icon'=>'fa-database','tab'=>'backups']
        ],
        'dir_exam' => [
            ['label'=>'تنظيم الامتحانات','icon'=>'fa-graduation-cap','tab'=>'exams_plan'],
            ['label'=>'الشهادات والمصادقة','icon'=>'fa-stamp','tab'=>'verify_cert'],
            ['label'=>'مراكز الامتحانات','icon'=>'fa-building-columns','tab'=>'centers']
        ],
        'dir_trak' => [
            ['label'=>'الدورات التأهيلية','icon'=>'fa-clock-rotate-left','tab'=>'courses'],
            ['label'=>'التكوين المستمر','icon'=>'fa-arrows-spin','tab'=>'continuous'],
            ['label'=>'البرامج القطاعية','icon'=>'fa-handshake','tab'=>'joint_programs']
        ],
        'dir_edu' => [
            ['label'=>'الشعب والتخصصات','icon'=>'fa-tags','tab'=>'specialties'],
            ['label'=>'المناهج والبرامج','icon'=>'fa-book-bookmark','tab'=>'programs'],
            ['label'=>'تعداد المتربصين','icon'=>'fa-users','tab'=>'trainees']
        ],
        'dir_org' => [
            ['label'=>'توزيع المتكونين','icon'=>'fa-users-gear','tab'=>'cohorts'],
            ['label'=>'الرزنامة والدخول','icon'=>'fa-calendar-check','tab'=>'schedule_entry'],
            ['label'=>'التقييم البيداغوجي','icon'=>'fa-star','tab'=>'bimonthly']
        ]
    ];
    @endphp

    <!-- =====================================================
         1. Collapsible Sovereign Sidebar
         ===================================================== -->
    <aside class="sovereign-sidebar" id="sovereignSidebar">
        <!-- Sidebar Logo -->
        <div class="sidebar-logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Sovereign Seal">
            <span class="logo-text">منصة تسيير</span>
            <button type="button" class="btn btn-sm text-muted ms-auto d-none d-lg-block" id="pinSidebarBtn" onclick="toggleSidebarPin()" title="تثبيت/طي القائمة"><i class="fa-solid fa-thumbtack"></i></button>
        </div>

        <!-- Sidebar Menu -->
        <nav class="sidebar-menu">
            @if ($roleCode === 'apprenant')
                <!-- ══ فضاء المتربص ══ -->
                <div class="sidebar-section-label"><span>فضاء المتربص</span></div>

                <a href="{{ request()->is('sig/*') ? url('sig/apprenant') : url('apprenant') }}" class="sidebar-item {{ ($isActive('/apprenant', true) || $isActive('/sig/apprenant', true)) ? 'active' : '' }}" title="الصفحة الرئيسية">
                    <i class="fa-solid fa-house text-success"></i>
                    <span>الصفحة الرئيسية</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/apprenant/carte') : url('apprenant/carte') }}" class="sidebar-item {{ ($isActive('/apprenant/carte', true) || $isActive('/sig/apprenant/carte', true)) ? 'active' : '' }}" title="بطاقة المتكون">
                    <i class="fa-solid fa-id-card text-primary"></i>
                    <span>بطاقة المتكون</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/apprenant#notes') : url('apprenant#notes') }}" class="sidebar-item" title="نتائجي">
                    <i class="fa-solid fa-chart-line text-warning"></i>
                    <span>نتائجي</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/apprenant#emploi') : url('apprenant#emploi') }}" class="sidebar-item" title="استعمال الزمن">
                    <i class="fa-solid fa-calendar-week text-info"></i>
                    <span>استعمال الزمن</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/apprenant#documents') : url('apprenant#documents') }}" class="sidebar-item" title="وثائقي">
                    <i class="fa-solid fa-folder-open text-danger"></i>
                    <span>وثائقي</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/apprenant#teachers') : url('apprenant#teachers') }}" class="sidebar-item" title="الأساتذة">
                    <i class="fa-solid fa-chalkboard-user" style="color: #8b5cf6;"></i>
                    <span>الأساتذة</span>
                </a>
            @else
                @if ($isApprenticeshipDept)
                    <!-- ══ فضاء مصلحة التمهين ══ -->
                    <div class="sidebar-section-label"><span>فضاء مصلحة التمهين</span></div>

                    <a href="{{ url('dashboard') }}" class="sidebar-item {{ ($isActive('/dashboard', true) && !isset($_GET['tab'])) ? 'active' : '' }}" title="لوحة التحكم">
                        <i class="fa-solid fa-chart-line text-success"></i>
                        <span>لوحة التحكم</span>
                    </a>

                    <!-- 1. الشؤون البيداغوجية والتعليم -->
                    <div class="sidebar-dropdown">
                        <button type="button" class="sidebar-item {{ ($isActive('/dashboard/offres') || $isActive('/dashboard/inscriptions') || $isActive('/dashboard/apprenants') || $isActive('/dashboard/sections') || $isActive('/dashboard/specialites') || $isActive('/dashboard/sessions') || $isActive('/dashboard/effectifs')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="التكوين والتعليم">
                            <i class="fa-solid fa-graduation-cap text-primary"></i>
                            <span>التكوين والتعليم</span>
                            <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                        </button>
                        <div class="sidebar-submenu {{ ($isActive('/dashboard/offres') || $isActive('/dashboard/inscriptions') || $isActive('/dashboard/apprenants') || $isActive('/dashboard/sections') || $isActive('/dashboard/specialites') || $isActive('/dashboard/sessions') || $isActive('/dashboard/effectifs')) ? 'open' : '' }}">
                            <a href="{{ url('dashboard/offres') }}" class="sidebar-subitem {{ $isActive('/dashboard/offres', true) }}" title="العروض"><i class="fa-solid fa-briefcase text-primary"></i> <span>العروض</span></a>
                            <a href="{{ url('dashboard/inscriptions') }}" class="sidebar-subitem {{ $isActive('/dashboard/inscriptions') }}" title="التسجيل والتوجيه"><i class="fa-solid fa-user-plus text-primary"></i> <span>التسجيل والتوجيه</span></a>
                            <a href="{{ url('dashboard/apprenants') }}" class="sidebar-subitem {{ $isActive('/dashboard/apprenants') }}" title="المتربصين"><i class="fa-solid fa-user-graduate text-primary"></i> <span>المتربصين</span></a>
                            <a href="{{ url('dashboard/sections') }}" class="sidebar-subitem {{ $isActive('/dashboard/sections') }}" title="الأقسام التكوينية"><i class="fa-solid fa-users-rectangle text-primary"></i> <span>الأقسام التكوينية</span></a>
                            <a href="{{ url('dashboard/specialites') }}" class="sidebar-subitem {{ $isActive('/dashboard/specialites') }}" title="تنظيم الفروع"><i class="fa-solid fa-sitemap text-primary"></i> <span>تنظيم الفروع</span></a>
                            <a href="{{ url('dashboard/sessions') }}" class="sidebar-subitem {{ $isActive('/dashboard/sessions') }}" title="تخطيط الدورات"><i class="fa-solid fa-calendar-alt text-primary"></i> <span>تخطيط الدورات</span></a>
                            <a href="{{ url('dashboard/effectifs') }}" class="sidebar-subitem {{ $isActive('/dashboard/effectifs') }}" title="تسيير التعداد"><i class="fa-solid fa-users text-primary"></i> <span>تسيير التعداد</span></a>
                        </div>
                    </div>

                    <!-- 2. تسيير التكوين والخدمات -->
                    <div class="sidebar-dropdown">
                        <button type="button" class="sidebar-item {{ ($isActive('/dashboard/distribution-globale') || $isActive('/dashboard/formation') || $isActive('/dashboard/formateurs') || $isActive('/dashboard/formateurs/age-distribution')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="تسيير التكوين والخدمات">
                            <i class="fa-solid fa-chalkboard-user text-info"></i>
                            <span>تسيير التكوين والخدمات</span>
                            <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                        </button>
                        <div class="sidebar-submenu {{ ($isActive('/dashboard/distribution-globale') || $isActive('/dashboard/formation') || $isActive('/dashboard/formateurs') || $isActive('/dashboard/formateurs/age-distribution')) ? 'open' : '' }}">
                            <a href="{{ url('dashboard/distribution-globale') }}" class="sidebar-subitem {{ $isActive('/dashboard/distribution-globale') }}" title="التوزيع العام"><i class="fa-solid fa-chart-pie text-info"></i> <span>التوزيع العام</span></a>
                            <a href="{{ url('dashboard/formation') }}" class="sidebar-subitem {{ $isActive('/dashboard/formation') }}" title="تسيير التكوين"><i class="fa-solid fa-chalkboard-user text-info"></i> <span>تسيير التكوين</span></a>
                            <a href="{{ url('dashboard/formateurs') }}" class="sidebar-subitem {{ $isActive('/dashboard/formateurs') }}" title="ملف الأساتذة"><i class="fa-solid fa-users-rectangle text-info"></i> <span>ملف الأساتذة</span></a>
                            <a href="{{ url('dashboard/formateurs/age-distribution') }}" class="sidebar-subitem {{ $isActive('/dashboard/formateurs/age-distribution') }}" title="إحصائيات الأساتذة والسن"><i class="fa-solid fa-chart-line text-info"></i> <span>إحصائيات الأساتذة والسن</span></a>
                        </div>
                    </div>

                    <!-- 3. التقييمات والشهادات -->
                    <div class="sidebar-dropdown">
                        <button type="button" class="sidebar-item {{ ($isActive('/resultats') || $isActive('/dashboard/gestion-evaluations') || $isActive('/dashboard/evaluation-finale') || $isActive('/dashboard/grades/reconduits') || $isActive('/dashboard/grades') || $isActive('/dashboard/reconduits') || $isActive('/dashboard/reconduits/transfers') || $isActive('/dashboard/schedule')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="التقييمات والشهادات">
                            <i class="fa-solid fa-list-check text-warning"></i>
                            <span>التقييمات والشهادات</span>
                            <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                        </button>
                        <div class="sidebar-submenu {{ ($isActive('/resultats') || $isActive('/dashboard/gestion-evaluations') || $isActive('/dashboard/evaluation-finale') || $isActive('/dashboard/grades/reconduits') || $isActive('/dashboard/grades') || $isActive('/dashboard/reconduits') || $isActive('/dashboard/reconduits/transfers') || $isActive('/dashboard/schedule')) ? 'open' : '' }}">
                            <a href="{{ url('dashboard/grades/reconduits') }}" class="sidebar-subitem {{ $isActive('/dashboard/grades/reconduits') }}" title="تسجيل نقاط المتربصين المستمرين"><i class="fa-solid fa-pen-to-square text-warning"></i> <span>تسجيل نقاط المتربصين المستمرين</span></a>
                            <a href="{{ url('dashboard/grades') }}" class="sidebar-subitem {{ $isActive('/dashboard/grades') }}" title="دفتر العلامات والمداولات"><i class="fa-solid fa-graduation-cap text-warning"></i> <span>دفتر العلامات والمداولات</span></a>
                            <a href="{{ url('resultats') }}" class="sidebar-subitem {{ $isActive('/resultats') }}" title="التقييم سداسي"><i class="fa-solid fa-star-half-stroke text-warning"></i> <span>التقييم سداسي</span></a>
                            <a href="{{ url('dashboard/reconduits') }}" class="sidebar-subitem {{ $isActive('/dashboard/reconduits') }}" title="المتربصين المستمرين"><i class="fa-solid fa-users-viewfinder text-warning"></i> <span>المتربصين المستمرين</span></a>
                            <a href="{{ url('dashboard/reconduits/transfers') }}" class="sidebar-subitem {{ $isActive('/dashboard/reconduits/transfers') }}" title="طلبات تحويل المتربصين"><i class="fa-solid fa-arrows-spin text-warning"></i> <span>طلبات تحويل المتربصين</span></a>
                            <a href="{{ url('dashboard/schedule') }}" class="sidebar-subitem {{ $isActive('/dashboard/schedule') }}" title="استعمال الزمن"><i class="fa-solid fa-calendar-days text-warning"></i> <span>استعمال الزمن</span></a>
                            <a href="{{ url('dashboard/gestion-evaluations') }}" class="sidebar-subitem {{ $isActive('/dashboard/gestion-evaluations') }}" title="تسيير التقييمات"><i class="fa-solid fa-list-check text-warning"></i> <span>تسيير التقييمات</span></a>
                            <a href="{{ url('dashboard/evaluation-finale') }}" class="sidebar-subitem {{ $isActive('/dashboard/evaluation-finale') }}" title="التقييم نهائي"><i class="fa-solid fa-flag-checkered text-warning"></i> <span>التقييم نهائي</span></a>
                        </div>
                    </div>

                    <!-- 4. التمهين والمؤسسات -->
                    <div class="sidebar-dropdown">
                        <button type="button" class="sidebar-item {{ ($isActive('/dashboard/partenaires') || $isActive('/dashboard/maitres-apprentissage')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="التمهين والمؤسسات">
                            <i class="fa-solid fa-industry text-danger"></i>
                            <span>التمهين والمؤسسات</span>
                            <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                        </button>
                        <div class="sidebar-submenu {{ ($isActive('/dashboard/partenaires') || $isActive('/dashboard/maitres-apprentissage')) ? 'open' : '' }}">
                            <a href="{{ url('dashboard/partenaires') }}" class="sidebar-subitem {{ $isActive('/dashboard/partenaires') }}" title="المؤسسات الاقتصادية"><i class="fa-solid fa-building text-danger"></i> <span>المؤسسات الاقتصادية</span></a>
                            <a href="{{ url('dashboard/maitres-apprentissage') }}" class="sidebar-subitem {{ $isActive('/dashboard/maitres-apprentissage') }}" title="معلمو التمهين"><i class="fa-solid fa-person-chalkboard text-danger"></i> <span>معلمو التمهين</span></a>
                        </div>
                    </div>
                @else
                    <!-- ══ Section 1: العامة والملف ══ -->
                    <div class="sidebar-section-label"><span>العامة والملف</span></div>

            <!-- Home Link -->
            <a href="{{ url('dashboard') }}" class="sidebar-item {{ ($isActive('/dashboard', true) && !isset($_GET['tab'])) ? 'active' : '' }}" title="لوحة التحكم">
                <i class="fa-solid fa-chart-line"></i>
                <span>لوحة التحكم</span>
            </a>

            <!-- Category: Digital Cards (بطاقات التكوين المهني) -->
            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'etablissement', 'directeur']) && (int)(session('user.IDMode_formation') ?? 0) !== 10 && $dept !== 'diplomes' && $dept !== 'administration' && !$isDfmUser && !$isDrhUser && !$isDepUser && !$isDeohUser && !$isDosfpUser && !$isDfcriUser)
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ $isActive('/dashboard/digital-cards') ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="بطاقات التكوين المهني">
                        <i class="fa-solid fa-id-card-clip text-primary"></i>
                        <span>بطاقات التكوين المهني</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ $isActive('/dashboard/digital-cards') ? 'open' : '' }}">
                        <a href="{{ request()->is('sig/*') ? url('sig/dashboard/digital-cards?type=employee') : url('dashboard/digital-cards?type=employee') }}" class="sidebar-subitem {{ ($isActive('/dashboard/digital-cards') && request('type', 'employee') === 'employee') ? 'active' : '' }}" title="بطاقات الموظفين">
                            <i class="fa-solid fa-user-tie"></i> <span>بطاقات الموظفين</span>
                        </a>
                        <a href="{{ request()->is('sig/*') ? url('sig/dashboard/digital-cards?type=trainee') : url('dashboard/digital-cards?type=trainee') }}" class="sidebar-subitem {{ ($isActive('/dashboard/digital-cards') && request('type') === 'trainee') ? 'active' : '' }}" title="بطاقات المتربصين">
                            <i class="fa-solid fa-user-graduate"></i> <span>بطاقات المتربصين</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- محرك سير العمل -->
            @if(!in_array($roleCode, ['employee', 'formateur', 'dfep', 'central', 'etablissement', 'directeur']) && $dept === 'general')
            <a href="{{ url('dashboard/workflow') }}" class="sidebar-item {{ $isActive('/dashboard/workflow') }}" title="محرك سير العمل — طلبات الإجازات والترقيات">
                <i class="fa-solid fa-diagram-project text-primary"></i>
                <span>محرك سير العمل</span>
            </a>
            @endif

            <!-- منشئ التقارير -->
            @if(!in_array($roleCode, ['employee', 'formateur', 'dfep', 'central', 'etablissement', 'directeur']) && $dept === 'general')
            <a href="{{ url('dashboard/reports') }}" class="sidebar-item {{ $isActive('/dashboard/reports') }}" title="منشئ التقارير الديناميكية">
                <i class="fa-solid fa-chart-bar text-info"></i>
                <span>التقارير</span>
            </a>
            @endif

            <!-- DSS (Decision Support System) Link -->
            @if (in_array($roleCode, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre', 'dir_finance', 'dir_edu']) && $dept !== 'diplomes' && !$isDosfpUser && !$isDfcriUser)
                <a href="{{ request()->is('sig/*') ? url('sig/dashboard/dss') : url('dashboard/dss') }}" class="sidebar-item {{ $isActive('/dashboard/dss') }}" title="دعم القرار الاستراتيجي">
                    <i class="fa-solid fa-brain text-info"></i>
                    <span>دعم القرار الاستراتيجي</span>
                </a>
            @endif

            <!-- Security Center Dashboard Link -->
            @if ($roleCode === 'admin')
                <a href="{{ request()->is('sig/*') ? url('sig/dashboard/admin-stats') : url('dashboard/admin-stats') }}" class="sidebar-item {{ $isActive('/dashboard/admin-stats') }}" title="لوحة الإحصائيات (الأدمن)">
                    <i class="fa-solid fa-chart-simple text-primary"></i>
                    <span>لوحة الإحصائيات (الأدمن)</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/dashboard/security') : url('dashboard/security') }}" class="sidebar-item {{ $isActive('/dashboard/security') }}" title="مركز الأمان والرقابة">
                    <i class="fa-solid fa-shield-halved text-danger"></i>
                    <span>مركز الأمان والرقابة</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/dashboard/security/mfa') : url('dashboard/security/mfa') }}" class="sidebar-item {{ $isActive('/dashboard/security/mfa') }}" title="إدارة سياسات الـ MFA">
                    <i class="fa-solid fa-user-shield text-danger"></i>
                    <span>إدارة سياسات الـ MFA</span>
                </a>
                <a href="{{ request()->is('sig/*') ? url('sig/dashboard/sync-files') : url('dashboard/sync-files') }}" class="sidebar-item {{ $isActive('/dashboard/sync-files') }}" title="مزامنة ملفات HFSQL">
                    <i class="fa-solid fa-database text-success"></i>
                    <span>مزامنة صور HFSQL</span>
                </a>
            @endif

            <!-- ══ Section 2: الشؤون البيداغوجية والتعليم ══ -->
            @if (in_array($dept, ['general', 'pedagogie', 'orientation', 'diplomes', 'apprentissage']) && !$isDfmUser && !$isDrhUser)
            <div class="sidebar-section-line"></div>
            <div class="sidebar-section-label"><span>الشؤون البيداغوجية والتعليم</span></div>

            <!-- Category: Training (التكوين والتعليم) -->
            @if (($hasPerm('offres') || $hasPerm('inscriptions') || in_array($roleCode, ['dfep', 'admin', 'central', 'etablissement', 'directeur', 'employee'])) && in_array($dept, ['general', 'pedagogie', 'orientation', 'apprentissage']))
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/offres') || $isActive('/dashboard/pedagogical-activity-report') || $isActive('/dashboard/inscriptions') || $isActive('/dashboard/preinscrits') || $isActive('/dashboard/specialites') || $isActive('/dashboard/sessions') || $isActive('/dashboard/effectifs') || $isActive('/dashboard/apprenants') || $isActive('/dashboard/sections') || $isActive('/dashboard/reconduits') || $isActive('/dashboard/reconduits/transfers') || $isActive('/dashboard/diplomes/liste-2021-present') || $isActive('/dashboard/schedule')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="التكوين والتعليم">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>التكوين والتعليم</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/offres') || $isActive('/dashboard/pedagogical-activity-report') || $isActive('/dashboard/inscriptions') || $isActive('/dashboard/preinscrits') || $isActive('/dashboard/specialites') || $isActive('/dashboard/sessions') || $isActive('/dashboard/effectifs') || $isActive('/dashboard/apprenants') || $isActive('/dashboard/sections') || $isActive('/dashboard/reconduits') || $isActive('/dashboard/reconduits/transfers') || $isActive('/dashboard/diplomes/liste-2021-present') || $isActive('/dashboard/schedule')) ? 'open' : '' }}">
                        @if(!$isDeohUser && !$isDecUser && !$isDfcriUser)
                            @if (in_array($dept, ['general', 'pedagogie', 'apprentissage']))
                                <a href="{{ url('dashboard/offres') }}" class="sidebar-subitem {{ $isActive('/dashboard/offres', true) }}" title="العروض"><i class="fa-solid fa-briefcase"></i> <span>العروض</span></a>
                                @if (in_array($roleCode, ['admin', 'dfep', 'central']) && $dept === 'general')
                                    <a href="{{ url('dashboard/offres/validation') }}" class="sidebar-subitem {{ $isActive('/dashboard/offres/validation') }}" title="المصادقة على العروض"><i class="fa-solid fa-stamp"></i> <span>المصادقة على العروض</span></a>
                                @endif
                                <a href="{{ url('dashboard/pedagogical-activity-report') }}" class="sidebar-subitem {{ $isActive('/dashboard/pedagogical-activity-report') }}" title="حصيلة النشاطات البيداغوجية"><i class="fa-solid fa-chart-pie text-success"></i> <span>حصيلة النشاطات البيداغوجية</span></a>
                            @endif
                        @endif
                        @if(!$isDosfpUser)
                            @if(!$isDecUser && !$isDfcriUser)
                                @if (in_array($dept, ['general', 'pedagogie', 'apprentissage']))
                                    @if(!$isDeohUser)
                                        <a href="{{ url('dashboard/sections') }}" class="sidebar-subitem {{ $isActive('/dashboard/sections') }}" title="الأقسام التكوينية (الفروع)"><i class="fa-solid fa-users-rectangle"></i> <span>الأقسام التكوينية (الفروع)</span></a>
                                    @endif
                                @endif
                                @if(!$isDeohUser && in_array($dept, ['general', 'pedagogie', 'orientation']))
                                    <a href="{{ url('dashboard/specialites') }}" class="sidebar-subitem {{ $isActive('/dashboard/specialites') }}" title="تنظيم الفروع"><i class="fa-solid fa-sitemap"></i> <span>تنظيم الفروع</span></a>
                                    <a href="{{ url('dashboard/preinscrits') }}" class="sidebar-subitem {{ $isActive('/dashboard/preinscrits') }}" title="التسجيلات الأولية عبر الإنترنت"><i class="fa-solid fa-laptop-file text-warning"></i> <span>التسجيلات الأولية عبر الإنترنت</span></a>
                                    <a href="{{ url('dashboard/candidates') }}" class="sidebar-subitem {{ $isActive('/dashboard/candidates') }}" title="قبول الملفات ورفضها (كونديدا)"><i class="fa-solid fa-user-check text-success"></i> <span>قبول الملفات ورفضها</span></a>
                                    <a href="{{ url('dashboard/inscriptions') }}" class="sidebar-subitem {{ $isActive('/dashboard/inscriptions') }}" title="التوجيه للأقسام البيداغوجية"><i class="fa-solid fa-user-plus"></i> <span>التوجيه للأقسام</span></a>
                                @endif
                                @if (in_array($dept, ['general', 'pedagogie', 'apprentissage']))
                                    <a href="{{ url('dashboard/apprenants') }}" class="sidebar-subitem {{ $isActive('/dashboard/apprenants') }}" title="المتربصين الجدد"><i class="fa-solid fa-user-graduate"></i> <span>المتربصين الجدد</span></a>
                                    <a href="{{ url('dashboard/reconduits') }}" class="sidebar-subitem {{ $isActive('/dashboard/reconduits') }}" title="المتربصين المستمرين"><i class="fa-solid fa-users-viewfinder"></i> <span>المتربصين المستمرين</span></a>
                                    <a href="{{ url('dashboard/reconduits/transfers') }}" class="sidebar-subitem {{ $isActive('/dashboard/reconduits/transfers') }}" title="طلبات تحويل المتربصين بين المؤسسات"><i class="fa-solid fa-arrows-spin text-warning"></i> <span>تحويل المتربصين</span></a>
                                @endif
                                @if (in_array($dept, ['general', 'diplomes', 'pedagogie']) && in_array($roleCode, ['admin', 'dfep', 'central', 'high_admin', 'secretaire_general', 'ministre']))
                                    <a href="{{ url('dashboard/diplomes/liste-2021-present') }}" class="sidebar-subitem {{ $isActive('/dashboard/diplomes/liste-2021-present') }}" title="جميع المتربصين (من 2021)"><i class="fa-solid fa-user-graduate text-warning"></i> <span>جميع المتربصين (من 2021)</span></a>
                                @endif
                                @if(!$isDeohUser)
                                    @if (in_array($dept, ['general', 'pedagogie']))
                                        <a href="{{ url('dashboard/effectifs') }}" class="sidebar-subitem {{ $isActive('/dashboard/effectifs') }}" title="تسيير التعداد"><i class="fa-solid fa-users"></i> <span>تسيير التعداد</span></a>
                                    @endif
                                    @if (in_array($dept, ['general', 'pedagogie', 'apprentissage']))
                                        <a href="{{ url('dashboard/schedule') }}" class="sidebar-subitem {{ $isActive('/dashboard/schedule') }}" title="استعمال الزمن"><i class="fa-solid fa-calendar-days text-primary"></i> <span>استعمال الزمن</span></a>
                                    @endif
                                    @if (in_array($roleCode, ['admin', 'high_admin']))
                                        <a href="{{ url('dashboard/sessions') }}" class="sidebar-subitem {{ $isActive('/dashboard/sessions') }}" title="تخطيط الدورات"><i class="fa-solid fa-calendar-alt"></i> <span>تخطيط الدورات</span></a>
                                    @endif
                                @endif
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            <!-- Category: National Specialty Directory (RNFC) -->
            @if(!in_array($roleCode, ['employee', 'formateur', 'dfep', 'central']) && in_array($dept, ['general', 'pedagogie']))
            <div class="sidebar-dropdown">
                <button type="button" class="sidebar-item {{ $isActive('/dashboard/rnfc') ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="مدونة الشعب والقطاعات (RNFC)">
                    <i class="fa-solid fa-sitemap text-teal"></i>
                    <span>مدونة الشعب والقطاعات (RNFC)</span>
                    <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                </button>
                <div class="sidebar-submenu {{ $isActive('/dashboard/rnfc') ? 'open' : '' }}">
                    <a href="{{ url('dashboard/rnfc') }}" class="sidebar-subitem {{ $isActive('/dashboard/rnfc') }}" title="مستكشف دليل الشعب"><i class="fa-solid fa-cubes text-teal"></i> <span>مستكشف دليل الشعب</span></a>
                    @if(in_array($roleCode, ['admin', 'central']))
                        <a href="{{ url('dashboard/sync') }}" class="sidebar-subitem {{ $isActive('/dashboard/sync') }}" title="مزامنة المدونة"><i class="fa-solid fa-rotate text-teal"></i> <span>مزامنة المدونة</span></a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Category: Apprenticeship -->
            @if ((in_array($roleCode, ['admin','dfep','etablissement','directeur']) || $isDecUser || $isDfcriUser) && in_array($dept, ['general', 'apprentissage']))
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/partenaires') || $isActive('/dashboard/maitres-apprentissage') || $isActive('/dashboard/integration')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="التمهين والمؤسسات">
                        <i class="fa-solid fa-industry"></i>
                        <span>التمهين والمؤسسات</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/partenaires') || $isActive('/dashboard/maitres-apprentissage') || $isActive('/dashboard/integration')) ? 'open' : '' }}">
                        <a href="{{ url('dashboard/partenaires') }}" class="sidebar-subitem {{ $isActive('/dashboard/partenaires') }}" title="المؤسسات الاقتصادية"><i class="fa-solid fa-building"></i> <span>المؤسسات الاقتصادية</span></a>
                        <a href="{{ url('dashboard/maitres-apprentissage') }}" class="sidebar-subitem {{ $isActive('/dashboard/maitres-apprentissage') }}" title="معلمو التمهين"><i class="fa-solid fa-person-chalkboard"></i> <span>معلمو التمهين</span></a>
                        @if (in_array($dept, ['general', 'pedagogie', 'apprentissage']) && (int)(session('user.IDMode_formation') ?? 0) !== 10)
                            <a href="{{ url('dashboard/integration') }}" class="sidebar-subitem {{ $isActive('/dashboard/integration') }}" title="الادماج"><i class="fa-solid fa-handshake"></i> <span>الادماج</span></a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Category: Evaluations & Exams -->
            @if (($hasPerm('grades') || in_array($roleCode, ['admin','dfep','etablissement','directeur'])) && in_array($dept, ['general', 'pedagogie', 'diplomes', 'apprentissage']) && !$isDosfpUser && !$isDepUser)
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/resultats') || $isActive('/dashboard/evaluation-stagiaires') || $isActive('/dashboard/examens') || $isActive('/dashboard/evaluation-finale') || $isActive('/dashboard/diplomes') || $isActive('/dashboard/diplomes/statistiques') || $isActive('/dashboard/grades/progress') || $isActive('/dashboard/grades') || $isActive('/dashboard/gestion-evaluations')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="التقييمات والشهادات">
                        <i class="fa-solid fa-list-check"></i>
                        <span>التقييمات والشهادات</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/resultats') || $isActive('/dashboard/evaluation-stagiaires') || $isActive('/dashboard/examens') || $isActive('/dashboard/evaluation-finale') || $isActive('/dashboard/diplomes') || $isActive('/dashboard/diplomes/statistiques') || $isActive('/dashboard/grades/progress') || $isActive('/dashboard/grades') || $isActive('/dashboard/gestion-evaluations')) ? 'open' : '' }}">
                        @if (in_array($dept, ['general', 'pedagogie', 'apprentissage']))
                            <a href="{{ url('dashboard/grades') }}" class="sidebar-subitem {{ $isActive('/dashboard/grades') }}" title="دفتر العلامات والمداولات"><i class="fa-solid fa-graduation-cap"></i> <span>دفتر العلامات والمداولات</span></a>
                            @if (in_array($roleCode, ['admin','dfep','etablissement','directeur']))
                                <a href="{{ url('dashboard/grades/progress') }}" class="sidebar-subitem {{ $isActive('/dashboard/grades/progress') }}" title="متابعة تقدم الرصد"><i class="fa-solid fa-chart-line"></i> <span>متابعة تقدم الرصد</span></a>
                            @endif
                            <a href="{{ url('dashboard/examens') }}" class="sidebar-subitem {{ $isActive('/dashboard/examens') }}" title="الامتحانات التقييمية"><i class="fa-solid fa-file-signature"></i> <span>الامتحانات التقييمية</span></a>
                            <a href="{{ url('dashboard/evaluation-finale') }}" class="sidebar-subitem {{ $isActive('/dashboard/evaluation-finale') }}" title="التقييم نهائي"><i class="fa-solid fa-flag-checkered"></i> <span>التقييم نهائي</span></a>
                            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'high_admin']))
                                <a href="{{ url('dashboard/evaluation-stagiaires') }}" class="sidebar-subitem {{ $isActive('/dashboard/evaluation-stagiaires') }}" title="التقييم - المتكونين"><i class="fa-solid fa-user-graduate"></i> <span>التقييم - المتكونين</span></a>
                            @endif
                            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']))
                                <a href="{{ url('dashboard/gestion-evaluations') }}" class="sidebar-subitem {{ $isActive('/dashboard/gestion-evaluations') }}" title="لجان التقييم ومتابعة المكونين"><i class="fa-solid fa-file-invoice text-primary"></i> <span>لجان التقييم ومتابعة المكونين</span></a>
                                <a href="{{ url('dashboard/gestion-evaluations/inspecteurs') }}" class="sidebar-subitem {{ $isActive('/dashboard/gestion-evaluations/inspecteurs') }}" title="سجل المفتشين والزيارات"><i class="fa-solid fa-user-shield text-info"></i> <span>سجل المفتشين والزيارات</span></a>
                                <a href="{{ url('dashboard/gestion-evaluations/jury') }}" class="sidebar-subitem {{ $isActive('/dashboard/gestion-evaluations/jury') }}" title="لجان مناقشة المذكرات"><i class="fa-solid fa-users text-success"></i> <span>لجان مناقشة المذكرات</span></a>
                            @endif
                        @endif
                        @if (in_array($dept, ['general', 'diplomes']))
                            <a href="{{ url('dashboard/diplomes') }}" class="sidebar-subitem {{ ($isActive('/dashboard/diplomes') && !$isActive('/dashboard/diplomes/liste-2021-present')) ? 'active' : '' }}" title="الشهادات"><i class="fa-solid fa-award"></i> <span>الشهادات</span></a>
                            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'high_admin', 'secretaire_general', 'ministre']))
                                <a href="{{ url('dashboard/diplomes/statistiques') }}" class="sidebar-subitem {{ $isActive('/dashboard/diplomes/statistiques') }}" title="إحصائيات الخريجين"><i class="fa-solid fa-chart-pie"></i> <span>إحصائيات الخريجين</span></a>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            <!-- Category: Discipline & Physical Services -->
            @if (($hasPerm('discipline') || $hasPerm('repas') || $hasPerm('documents') || $roleCode === 'dfep') && !$isDfmUser && !$isDrhUser && !$isDepUser && !$isDeohUser)
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/absences') || $isActive('/dashboard/discipline') || $isActive('/dashboard/repas') || $isActive('/dashboard/documents') || $isActive('/dashboard/distribution-globale') || $isActive('/dashboard/distribution-detaillee') || $isActive('/dashboard/formation')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="تسيير التكوين والخدمات">
                        <i class="fa-solid fa-clipboard-user"></i>
                        <span>تسيير التكوين والخدمات</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/absences') || $isActive('/dashboard/discipline') || $isActive('/dashboard/repas') || $isActive('/dashboard/documents') || $isActive('/dashboard/distribution-globale') || $isActive('/dashboard/distribution-detaillee') || $isActive('/dashboard/formation')) ? 'open' : '' }}">
                        @if (($hasPerm('discipline') || $roleCode === 'dfep') && !$isDosfpUser && !$isDfcriUser)
                            <a href="{{ url('dashboard/absences') }}" class="sidebar-subitem {{ $isActive('/dashboard/absences') }}" title="المتابعة، الانضباط"><i class="fa-solid fa-user-check"></i> <span>المتابعة، الانضباط</span></a>
                        @endif
                        @if ($hasPerm('inscriptions') || $roleCode === 'dfep')
                            <a href="{{ url('dashboard/distribution-globale') }}" class="sidebar-subitem {{ $isActive('/dashboard/distribution-globale') }}" title="التوزيع العام"><i class="fa-solid fa-chart-pie"></i> <span>التوزيع العام</span></a>
                            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'high_admin']))
                                <a href="{{ url('dashboard/distribution-detaillee') }}" class="sidebar-subitem {{ $isActive('/dashboard/distribution-detaillee') }}" title="التوزيع المفصل"><i class="fa-solid fa-chart-bar"></i> <span>التوزيع المفصل</span></a>
                            @endif
                        @endif
                        @if ($hasPerm('offres') || $roleCode === 'dfep')
                            <a href="{{ url('dashboard/formation') }}" class="sidebar-subitem {{ $isActive('/dashboard/formation') }}" title="تسيير التكوين"><i class="fa-solid fa-chalkboard-user"></i> <span>تسيير التكوين</span></a>
                        @endif
                        @if (($hasPerm('documents') || $roleCode === 'dfep') && !$isDosfpUser && !$isDfcriUser && $roleCode !== 'etablissement' && $roleCode !== 'directeur')
                            <a href="{{ url('dashboard/documents') }}" class="sidebar-subitem {{ $isActive('/dashboard/documents') }}" title="الشهادات والمطبوعات"><i class="fa-solid fa-print"></i> <span>الشهادات والمطبوعات</span></a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Category: Instant Official Documents & Biometrics -->
            @if (($hasPerm('documents') || $roleCode === 'dfep' || in_array($roleCode, ['admin', 'central', 'etablissement', 'directeur'])) && !$isDfmUser && !$isDrhUser && !$isDepUser && !$isDeohUser && !$isDosfpUser && !$isDfcriUser)
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/documents') && !str_contains(request()->fullUrl(), 'tab=')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="الوثائق الرسمية الفورية">
                        <i class="fa-solid fa-file-shield text-warning"></i>
                        <span>الوثائق الرسمية الفورية</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ $isActive('/dashboard/documents') ? 'open' : '' }}">
                        <a href="{{ url('dashboard/documents') }}" class="sidebar-subitem {{ ($isActive('/dashboard/documents', true) && !request('focus')) ? 'active' : '' }}" title="بوابة استخراج الوثائق">
                            <i class="fa-solid fa-print text-primary"></i> <span>بوابة استخراج الوثائق</span>
                        </a>
                        <a href="{{ url('dashboard/documents?focus=isqat') }}" class="sidebar-subitem {{ (request('focus') === 'isqat') ? 'active' : '' }}" title="شهادة تكوين - مفصولين">
                            <i class="fa-solid fa-user-slash text-danger"></i> <span>شهادة تكوين - مفصولين</span>
                        </a>
                        <a href="{{ url('dashboard/documents?focus=basma') }}" class="sidebar-subitem {{ (request('focus') === 'basma') ? 'active' : '' }}" title="البصمة الرقمية الموحدة">
                            <i class="fa-solid fa-fingerprint text-info"></i> <span>البصمة الرقمية الموحدة</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Category: Employee (قسم الموظف) -->
            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']) && !$isDfmUser && !$isDrhUser && !$isDosfpUser && !$isDepUser && !$isDeohUser && !$isDfcriUser)
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/espace-employe') || $isActive('/dashboard/formateurs/age-distribution') || $isActive('/dashboard/formateurs') || $isActive('/dashboard/gestion-evaluations')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="قسم الموظف">
                        <i class="fa-solid fa-users-gear text-success"></i>
                        <span>قسم الموظف</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/espace-employe') || $isActive('/dashboard/formateurs/age-distribution') || $isActive('/dashboard/formateurs') || $isActive('/dashboard/gestion-evaluations')) ? 'open' : '' }}">
                        <a href="{{ url('dashboard/espace-employe') }}" class="sidebar-subitem {{ $isActive('/dashboard/espace-employe') }}" title="فضاء الموظف">
                            <i class="fa-solid fa-briefcase"></i> <span>فضاء الموظف</span>
                        </a>
                        <a href="{{ url('dashboard/formateurs/age-distribution') }}" class="sidebar-subitem {{ $isActive('/dashboard/formateurs/age-distribution') }}" title="إحصائيات الأساتذة"><i class="fa-solid fa-chart-line"></i> <span>إحصائيات الأساتذة</span></a>
                        <a href="{{ url('dashboard/formateurs') }}" class="sidebar-subitem {{ $isActive('/dashboard/formateurs') }}" title="التقييم - المكونين (الأساتذة)"><i class="fa-solid fa-user-tie"></i> <span>التقييم - المكونين (الأساتذة)</span></a>
                        <a href="{{ url('dashboard/gestion-evaluations') }}" class="sidebar-subitem {{ $isActive('/dashboard/gestion-evaluations') }}" title="تسيير لجان التقييم"><i class="fa-solid fa-list-check"></i> <span>تسيير لجان التقييم</span></a>
                    </div>
                </div>
            @endif            @endif

            <!-- Category: Directorate Tabs -->
            @if (strpos($roleCode, 'dir_') === 0 && isset($depNames[$roleCode]))
                @php
                $dep = $depNames[$roleCode];
                $activeTabs = $depTabs[$roleCode] ?? [];
                @endphp
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item active" onclick="toggleSidebarDropdown(this)" title="{{ $dep['title'] }}">
                        <i class="fa-solid {{ $dep['icon'] }}"></i>
                        <span>{{ $dep['title'] }}</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu open">
                        @foreach ($activeTabs as $t)
                            @php
                            $ac = (isset($_GET['tab']) && $_GET['tab'] === $t['tab']) ? 'active' : '';
                            @endphp
                            <a href="/sig/dashboard?tab={{ $t['tab'] }}" class="sidebar-subitem {{ $ac }}" title="{{ $t['label'] }}"><i class="fa-solid {{ $t['icon'] }}"></i> <span>{{ $t['label'] }}</span></a>
                        @endforeach
                    </div>
                </div>
            @elseif (in_array($roleCode, ['admin', 'ministre', 'secretaire_general']))
                <!-- Category: Central Directorates (Admin) -->
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ isset($_GET['view_dir']) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="المديريات المركزية">
                        <i class="fa-solid fa-building-columns"></i>
                        <span>المديريات المركزية</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ isset($_GET['view_dir']) ? 'open' : '' }}">
                        @foreach ($depNames as $dirKey => $dirData)
                            <a href="/sig/dashboard?view_dir={{ $dirKey }}" class="sidebar-subitem {{ (isset($_GET['view_dir']) && $_GET['view_dir'] === $dirKey) ? 'active' : '' }}" title="{{ $dirData['title'] }}">
                                <i class="fa-solid {{ $dirData['icon'] }} text-secondary"></i> <span>{{ $dirData['title'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif


            <!-- ══ Section 4: النظام والإعدادات ══ -->
            @if ($dept === 'general')
            <div class="sidebar-section-line"></div>
            <div class="sidebar-section-label"><span>النظام والإعدادات</span></div>

            <!-- Category: Administration & Users -->
            @if ($roleCode === 'admin')
                <!-- Menu 1: Users & Permissions -->
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/users') || $isActive('/dashboard/permissions') || $isActive('/dashboard/roles') || $isActive('/dashboard/identities')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="المستخدمين والصلاحيات">
                        <i class="fa-solid fa-users-gear text-primary"></i>
                        <span>المستخدمين والصلاحيات</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/users') || $isActive('/dashboard/permissions') || $isActive('/dashboard/roles') || $isActive('/dashboard/identities')) ? 'open' : '' }}">
                        <a href="{{ url('dashboard/users') }}" class="sidebar-subitem {{ $isActive('/dashboard/users') }}" title="إدارة الحسابات"><i class="fa-solid fa-user-check text-primary"></i> <span>إدارة الحسابات</span></a>
                        <a href="{{ url('dashboard/roles') }}" class="sidebar-subitem {{ $isActive('/dashboard/roles') }}" title="صلاحيات المستخدمين"><i class="fa-solid fa-user-shield text-danger"></i> <span>صلاحيات المستخدمين</span></a>
                        <a href="{{ url('dashboard/permissions') }}" class="sidebar-subitem {{ $isActive('/dashboard/permissions') }}" title="تخصيص صلاحيات الحسابات"><i class="fa-solid fa-key text-warning"></i> <span>تخصيص الصلاحيات</span></a>
                        <a href="{{ url('dashboard/identities') }}" class="sidebar-subitem {{ $isActive('/dashboard/identities') }}" title="سجل الهويات الوطني"><i class="fa-solid fa-id-card text-success"></i> <span>سجل الهويات الوطني</span></a>
                    </div>
                </div>

                <!-- Menu 2: Database & System Tools -->
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/sync') || $isActive('/dashboard/database') || $isActive('/dashboard/import') || $isActive('/dashboard/hfsql-export') || $isActive('/dashboard/audit-logs')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="أدوات وقواعد البيانات">
                        <i class="fa-solid fa-database text-success"></i>
                        <span>أدوات وقواعد البيانات</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/sync') || $isActive('/dashboard/database') || $isActive('/dashboard/import') || $isActive('/dashboard/hfsql-export') || $isActive('/dashboard/audit-logs')) ? 'open' : '' }}">
                        <a href="{{ url('dashboard/sync') }}" class="sidebar-subitem {{ $isActive('/dashboard/sync') }}" title="مزامنة البيانات (HFSQL)"><i class="fa-solid fa-server text-success"></i> <span>مزامنة البيانات (HFSQL)</span></a>
                        <a href="{{ url('dashboard/hfsql-export') }}" class="sidebar-subitem {{ $isActive('/dashboard/hfsql-export') }}" title="مزامنة HFSQL &larr; MySQL"><i class="fa-solid fa-arrows-rotate text-info"></i> <span>مزامنة الصادرات</span></a>
                        <a href="{{ url('dashboard/database') }}" class="sidebar-subitem {{ $isActive('/dashboard/database') }}" title="إدارة قاعدة البيانات"><i class="fa-solid fa-screwdriver-wrench text-warning"></i> <span>إدارة قاعدة البيانات</span></a>
                        <a href="{{ url('dashboard/import') }}" class="sidebar-subitem {{ $isActive('/dashboard/import') }}" title="استيراد وتصدير البيانات"><i class="fa-solid fa-file-import text-primary"></i> <span>استيراد وتصدير البيانات</span></a>
                        <a href="{{ url('dashboard/audit-logs') }}" class="sidebar-subitem {{ $isActive('/dashboard/audit-logs') }}" title="سجل العمليات"><i class="fa-solid fa-list-check text-secondary"></i> <span>سجل العمليات</span></a>
                    </div>
                </div>

                <!-- Menu 3: Advanced Settings & APIs -->
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/settings') || $isActive('/dashboard/settings/takwin') || $isActive('/dashboard/settings/diplome') || $isActive('/dashboard/reports') || $isActive('/dashboard/builder') || $isActive('/dashboard/api-center') || $isActive('/dashboard/archive')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="إعدادات المنصة المتقدمة">
                        <i class="fa-solid fa-sliders text-warning"></i>
                        <span>إعدادات المنصة المتقدمة</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/settings') || $isActive('/dashboard/settings/takwin') || $isActive('/dashboard/settings/diplome') || $isActive('/dashboard/reports') || $isActive('/dashboard/builder') || $isActive('/dashboard/api-center') || $isActive('/dashboard/archive')) ? 'open' : '' }}">
                        <a href="{{ url('dashboard/settings') }}" class="sidebar-subitem {{ $isActive('/dashboard/settings', true) }}" title="إعدادات المنصة"><i class="fa-solid fa-gear text-primary"></i> <span>إعدادات النظام العامة</span></a>
                        <a href="{{ url('dashboard/settings/takwin') }}" class="sidebar-subitem {{ $isActive('/dashboard/settings/takwin') }}" title="إعدادات Takwin API"><i class="fa-solid fa-cloud-arrow-down text-info"></i> <span>إعدادات Takwin API</span></a>
                        <a href="{{ url('dashboard/settings/diplome') }}" class="sidebar-subitem {{ $isActive('/dashboard/settings/diplome') }}" title="تخصيص وتصميم الشهادات"><i class="fa-solid fa-palette text-success"></i> <span>تصميم الشهادات</span></a>
                        <a href="{{ url('dashboard/reports') }}" class="sidebar-subitem {{ $isActive('/dashboard/reports') }}" title="منشئ التقارير"><i class="fa-solid fa-chart-bar text-warning"></i> <span>منشئ التقارير</span></a>
                        <a href="{{ url('dashboard/builder') }}" class="sidebar-subitem {{ $isActive('/dashboard/builder') }}" title="منشئ لوحات التحكم"><i class="fa-solid fa-compass-drafting text-danger"></i> <span>منشئ لوحات التحكم</span></a>
                        <a href="{{ url('dashboard/api-center') }}" class="sidebar-subitem {{ $isActive('/dashboard/api-center') }}" title="مركز الاتصال الرقمي"><i class="fa-solid fa-satellite-dish text-info"></i> <span>مركز الاتصال الرقمي</span></a>
                        <a href="{{ url('dashboard/archive') }}" class="sidebar-subitem {{ $isActive('/dashboard/archive') }}" title="بوابة الأرشيف (HFSQL)"><i class="fa-solid fa-box-archive text-secondary"></i> <span>بوابة الأرشيف (HFSQL)</span></a>
                    </div>
                </div>
            @endif
            @endif
            @endif
            @endif

            @if ($roleCode !== 'apprenant')
<!-- ══ Section: الإدارة والخدمات العامة ══ -->
            <div class="sidebar-section-line"></div>
            <div class="sidebar-section-label"><span>الإدارة والخدمات العامة</span></div>

            <!-- ══ Category: التسيير المالي والإداري ══ -->
            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']) && !$isDosfpUser && !$isDepUser && !$isDeohUser)
                <div class="sidebar-dropdown">
                    <button type="button" class="sidebar-item {{ ($isActive('/dashboard/finances') || $isActive('/dashboard/patrimoine') || $isActive('/dashboard/rh-gestion') || $isActive('/dashboard/identities')) ? 'active' : '' }}" onclick="toggleSidebarDropdown(this)" title="التسيير المالي والإداري">
                        <i class="fa-solid fa-coins text-warning"></i>
                        <span>التسيير المالي والإداري</span>
                        <i class="fa-solid fa-chevron-down ms-auto dropdown-chevron" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="sidebar-submenu {{ ($isActive('/dashboard/finances') || $isActive('/dashboard/patrimoine') || $isActive('/dashboard/rh-gestion') || $isActive('/dashboard/identities')) ? 'open' : '' }}">
                        <a href="{{ url('dashboard/finances') }}" class="sidebar-subitem {{ ($isActive('/dashboard/finances') && !request('tab')) ? 'active' : '' }}" title="المناصب المالية والميزانية">
                            <i class="fa-solid fa-money-bill-wave text-success"></i> <span>المناصب المالية والميزانية</span>
                        </a>
                        @if($isDfmUser)
                            <a href="{{ url('dashboard/documents') }}" class="sidebar-subitem {{ $isActive('/dashboard/documents') }}" title="الوثائق الرسمية">
                                <i class="fa-solid fa-file-shield text-warning"></i> <span>الوثائق الرسمية</span>
                            </a>
                        @endif
                        @if(!$isDrhUser)
                            <a href="{{ url('dashboard/finances?tab=grants_dashboard') }}" class="sidebar-subitem {{ ($isActive('/dashboard/finances') && request('tab')==='grants_dashboard') ? 'active' : '' }}" title="لوحة إحصائيات المنح">
                                <i class="fa-solid fa-chart-pie text-warning"></i> <span>لوحة إحصائيات المنح</span>
                            </a>
                            <a href="{{ url('dashboard/finances?tab=employees_dashboard') }}" class="sidebar-subitem {{ ($isActive('/dashboard/finances') && request('tab')==='employees_dashboard') ? 'active' : '' }}" title="لوحة إحصائيات الموظفين">
                                <i class="fa-solid fa-users-gear text-warning"></i> <span>لوحة إحصائيات الموظفين</span>
                            </a>
                            <a href="{{ url('dashboard/finances?tab=programmes') }}" class="sidebar-subitem {{ ($isActive('/dashboard/finances') && request('tab')==='programmes') ? 'active' : '' }}" title="البرامج والبرامج الفرعية">
                                <i class="fa-solid fa-layer-group"></i> <span>البرامج والبرامج الفرعية</span>
                            </a>
                            <a href="{{ url('dashboard/finances?tab=fournisseurs') }}" class="sidebar-subitem {{ ($isActive('/dashboard/finances') && request('tab')==='fournisseurs') ? 'active' : '' }}" title="تسيير الموردين">
                                <i class="fa-solid fa-truck"></i> <span>تسيير الموردين</span>
                            </a>
                            <a href="{{ url('dashboard/patrimoine') }}" class="sidebar-subitem {{ $isActive('/dashboard/patrimoine') }}" title="تسيير الوسائل والممتلكات">
                                <i class="fa-solid fa-building text-info"></i> <span>تسيير الوسائل والممتلكات</span>
                            </a>
                            <a href="{{ url('dashboard/rh-gestion') }}" class="sidebar-subitem {{ $isActive('/dashboard/rh-gestion') }}" title="الموارد البشرية والإدارية">
                                <i class="fa-solid fa-users-gear text-primary"></i> <span>الموارد البشرية والإدارية</span>
                            </a>
                            <a href="{{ url('dashboard/identities') }}" class="sidebar-subitem {{ $isActive('/dashboard/identities') }}" title="سجل التحقق من الهوية الوطنية">
                                <i class="fa-solid fa-id-card text-danger"></i> <span>سجل التحقق من الهوية الوطنية</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- البريد الداخلي -->
            <a href="{{ url('dashboard/messages') }}" class="sidebar-item {{ $isActive('/dashboard/messages') }}" title="البريد الداخلي">
                <i class="fa-solid fa-envelope text-warning"></i>
                <span>البريد الداخلي</span>
                @php try { $unreadCount = \App\Models\EmployeeMessage::unreadCount(session('user.id', 0)); } catch(\Exception $e) { $unreadCount = 0; } @endphp
                @if($unreadCount > 0)
                <span style="background:#dc3545;color:#fff;border-radius:20px;padding:.1rem .45rem;font-size:.68rem;font-weight:800;margin-right:auto;font-family:'Outfit';">{{ $unreadCount }}</span>
                @endif
            </a>

            <!-- ملف المؤسسة -->
            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']) && $dept !== 'diplomes' && !$isDfmUser && !$isDrhUser && !$isDosfpUser && !$isDeohUser)
                <a href="{{ request()->is('sig/*') ? url('sig/dashboard/etablissement') : url('dashboard/etablissement') }}" class="sidebar-item {{ $isActive('/dashboard/etablissement') }}" title="ملف المؤسسة">
                    <i class="fa-solid fa-hotel text-warning"></i>
                    <span>ملف المؤسسة</span>
                </a>
            @endif

            <!-- إعدادات المظهر والتفضيلات -->
            @if (in_array($roleCode, ['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin']) && !$isDfmUser && !$isDrhUser)
                <a href="{{ request()->is('sig/*') ? url('sig/dashboard/preferences') : url('dashboard/preferences') }}" class="sidebar-item {{ $isActive('/dashboard/preferences') }}" title="إعدادات المظهر والتفضيلات">
                    <i class="fa-solid fa-palette text-info"></i>
                    <span>إعدادات المظهر والتفضيلات</span>
                </a>
            @endif

            <!-- المصادقة الثنائية (MFA) -->
            <a href="{{ request()->is('sig/*') ? url('sig/security/mfa') : url('security/mfa') }}" class="sidebar-item {{ $isActive('/security/mfa') }}" title="المصادقة الثنائية (MFA)">
                <i class="fa-solid fa-key text-warning"></i>
                <span>المصادقة الثنائية (MFA)</span>
            </a>
            @endif
        </nav>


        <!-- Sidebar Footer -->
        <div style="border-top: 1px solid rgba(255, 255, 255, 0.08); padding: 0.75rem 0;">
            <a href="{{ url('dashboard/profile') }}" class="sidebar-item {{ $isActive('/dashboard/profile') }}">
                <i class="fa-solid fa-user-gear"></i>
                <span>الملف الإداري</span>
            </a>
            <a href="{{ request()->is('sig/*') ? url('sig/logout') : url('logout') }}" class="sidebar-item text-danger">
                <i class="fa-solid fa-power-off"></i>
                <span>الخروج الآمن</span>
            </a>
        </div>
    </aside>

    <!-- Sidebar Overlay Backdrop for Mobile Drawer -->
    <div class="sb-overlay" id="sbOverlay" onclick="closeAllDrawers()"></div>

    <!-- =====================================================
         3. MAIN WORKSPACE (Floating Command Bar + Content)
         ===================================================== -->
    <div class="workspace">

        <!-- FLOATING COMMAND BAR -->
        <div class="command-bar-wrap">
            <header class="command-bar">
                <!-- Left: Quick search trigger & shortcut indicator -->
                <div class="cb-search" onclick="openCommandPalette()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="بحث سريع في المنصة..." id="globalSearchPlaceholder" readonly style="cursor: pointer;">
                    <span class="cb-shortcut">⌘ K</span>
                </div>

                <div class="cb-divider"></div>

                <!-- Right: Action Buttons & Avatar Chip -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Mobile Hamburger -->
                    <button class="cb-btn d-lg-none" onclick="openMobileMenu()" type="button">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <!-- Theme toggle -->
                    <button class="cb-btn" id="themToggleBtn" onclick="toggleTheme()" title="تبديل وضع العرض">
                        <i id="themeIcon" class="fa-regular fa-moon"></i>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div class="dropdown">
                        <button class="cb-btn" data-bs-toggle="dropdown" aria-expanded="false" id="notifBtn">
                            <i class="fa-regular fa-bell"></i>
                            <span id="notifDot" class="cb-notif-dot"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start border-0 p-0 mt-2" style="width:340px; max-height:420px; overflow-y:auto;" aria-labelledby="notifBtn">
                            <div class="p-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--border);">
                                <h6 class="mb-0 fw-bold" style="font-size:.85rem;">
                                    <i class="fa-solid fa-bell me-2 text-primary"></i>الإخطارات والرسائل
                                </h6>
                                <button class="btn btn-sm p-0 text-primary" onclick="markAllNotifsRead()" style="font-size:.72rem; font-weight:700;">قراءة الكل</button>
                            </div>
                            <div id="notifList" class="p-2">
                                <div class="text-center p-3 text-muted small">جاري التحميل...</div>
                            </div>
                        </div>
                    </div>

                    <div class="cb-divider"></div>

                    <!-- Profile Dropdown -->
                    @php
                    $navAvatar = !empty($user['avatar']) ? $user['avatar'] : null;
                    if ($navAvatar) {
                        $navAvatar = ltrim(str_replace('/sig/uploads/avatars/', 'uploads/avatars/', $navAvatar), '/');
                        $navAvatarUrl = asset($navAvatar);
                    } else {
                        $navAvatarUrl = null;
                    }
                    $navInitials = mb_strtoupper(mb_substr($user['nom_complet'] ?? 'م', 0, 1));
                    @endphp
                    <div class="dropdown">
                        <a href="#" class="cb-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            @if ($navAvatarUrl)
                                <img src="{{ $navAvatarUrl }}" class="cb-avatar" style="object-fit:cover;" width="32" height="32">
                            @else
                                <div class="cb-avatar">{{ $navInitials }}</div>
                            @endif
                            <div class="cb-profile-text">
                                <span class="cb-profile-name">{{ $user['nom_complet'] ?? 'المشرف' }}</span>
                                <span class="cb-profile-role">{{ $user['role_ar'] ?? '' }}</span>
                            </div>
                            <i class="fa-solid fa-chevron-down ms-1 text-muted" style="font-size:.65rem;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-start mt-2">
                            <li>
                                <a class="dropdown-item" href="{{ url('dashboard/profile') }}">
                                    <i class="fa-solid fa-user-gear me-2 text-primary"></i>الملف الإداري
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ request()->is('sig/*') ? url('sig/logout') : url('logout') }}">
                                    <i class="fa-solid fa-power-off me-2"></i>الخروج الآمن
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
            </header>
        </div>

        <!-- MAIN DYNAMIC CONTENT -->
        <main class="content-area">
            @if ($showGlobalFilter)
                <!-- Sovereign Glassmorphic Global Filter Bar -->
                <div class="global-filter-bar mb-4 animate__animated animate__fadeIn">
                    <form method="GET" action="" class="row g-3 align-items-end p-3 rounded-4 shadow-sm border" style="background: var(--bg-surface-elevated); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-color: var(--border-portal); border-radius: 20px;">
                        
                        <!-- Wilaya Filter -->
                        <div class="col-12 col-md-3">
                            <label for="global-filter-wilaya" class="form-label fw-bold text-secondary mb-1.5" style="font-size: 0.82rem; font-family: 'Cairo';"><i class="fa-solid fa-map-location-dot text-primary me-1"></i> الولاية</label>
                            <select name="filter_wilaya" id="global-filter-wilaya" class="form-select border-0 shadow-sm py-2.5 px-3 bg-light rounded-3 w-100" style="font-size: 0.85rem; font-family: 'Cairo'; font-weight: 600;" onchange="onGlobalWilayaChange()" {{ (in_array($roleCode, ['dfep', 'etablissement', 'directeur'])) ? 'disabled' : '' }}>
                                <option value="">كل الولايات / Toutes</option>
                                @foreach ($filter_wilayas as $w)
                                    <option value="{{ $w['id'] }}" {{ (isset($_GET['filter_wilaya']) && $_GET['filter_wilaya'] == $w['id']) || (in_array($roleCode, ['dfep', 'etablissement', 'directeur']) && $dfepId == $w['id']) ? 'selected' : '' }}>
                                        {{ $w['nom_ar'] }} ({{ $w['code'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Etablissement Filter -->
                        <div class="col-12 col-md-4">
                            <label for="global-filter-etablissement" class="form-label fw-bold text-secondary mb-1.5" style="font-size: 0.82rem; font-family: 'Cairo';"><i class="fa-solid fa-building-columns text-primary me-1"></i> المؤسسة التكوينية</label>
                            <select name="filter_etablissement" id="global-filter-etablissement" class="form-select border-0 shadow-sm py-2.5 px-3 bg-light rounded-3 w-100" style="font-size: 0.85rem; font-family: 'Cairo'; font-weight: 600;" {{ (in_array($role, ['etablissement', 'directeur'])) ? 'disabled' : '' }}>
                                <option value="">كل المؤسسات / Etablissements</option>
                                @foreach ($filter_etablissements as $et)
                                    <option value="{{ $et['id'] }}" data-wilaya="{{ $et['wilaya_id'] }}" {{ (isset($_GET['filter_etablissement']) && $_GET['filter_etablissement'] == $et['id']) || ($role === 'directeur' && $etabId == $et['id']) ? 'selected' : '' }}>
                                        {{ $et['nom_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Mode Filter -->
                        <div class="col-12 col-md-3">
                            <label for="global-filter-mode" class="form-label fw-bold text-secondary mb-1.5" style="font-size: 0.82rem; font-family: 'Cairo';"><i class="fa-solid fa-graduation-cap text-primary me-1"></i> نمط التكوين</label>
                            <select name="filter_mode" id="global-filter-mode" class="form-select border-0 shadow-sm py-2.5 px-3 bg-light rounded-3 w-100" style="font-size: 0.85rem; font-family: 'Cairo'; font-weight: 600;">
                                <option value="">كل الأنماط / Modes</option>
                                @foreach ($filter_modes as $m)
                                    <option value="{{ $m['id'] }}" {{ (isset($_GET['filter_mode']) && $_GET['filter_mode'] == $m['id']) ? 'selected' : '' }}>
                                        {{ $m['libelle_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5" style="font-family: 'Cairo'; font-size: 0.85rem; background: linear-gradient(135deg, var(--electric) 0%, var(--electric-dark) 100%); border: none;">
                                <i class="fa-solid fa-filter"></i> تصفية
                            </button>
                            <a href="?" class="btn btn-secondary py-2.5 px-3 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="font-family: 'Cairo'; font-size: 0.85rem; background-color: var(--bg-portal); border: 1px solid var(--border-portal); color: var(--text-secondary);">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <script>
                let allGlobalEtabOptions = [];

                function initGlobalEtabCache() {
                    const etabSelect = document.getElementById('global-filter-etablissement');
                    if (etabSelect && allGlobalEtabOptions.length === 0) {
                        allGlobalEtabOptions = Array.from(etabSelect.options).map(opt => ({
                            value: opt.value,
                            text: opt.textContent || opt.innerText,
                            wilaya: opt.getAttribute('data-wilaya')
                        }));
                    }
                }

                function onGlobalWilayaChange() {
                    const wilayaVal = document.getElementById('global-filter-wilaya').value;
                    const etabSelect = document.getElementById('global-filter-etablissement');
                    if (!etabSelect) return;
                    
                    initGlobalEtabCache();
                    
                    const fragment = document.createDocumentFragment();
                    allGlobalEtabOptions.forEach(opt => {
                        if (opt.value === "" || !wilayaVal || opt.wilaya === wilayaVal) {
                            const optionEl = document.createElement('option');
                            optionEl.value = opt.value;
                            optionEl.textContent = opt.text;
                            if (opt.wilaya) {
                                optionEl.setAttribute('data-wilaya', opt.wilaya);
                            }
                            fragment.appendChild(optionEl);
                        }
                    });
                    
                    etabSelect.innerHTML = "";
                    etabSelect.appendChild(fragment);
                    etabSelect.value = "";
                }

                document.addEventListener('DOMContentLoaded', () => {
                    const wilayaSelect = document.getElementById('global-filter-wilaya');
                    if (wilayaSelect) {
                        const wilayaVal = wilayaSelect.value;
                        const etabSelect = document.getElementById('global-filter-etablissement');
                        if (etabSelect && wilayaVal) {
                            initGlobalEtabCache();
                            const currentVal = etabSelect.value;
                            
                            const fragment = document.createDocumentFragment();
                            allGlobalEtabOptions.forEach(opt => {
                                if (opt.value === "" || opt.wilaya == wilayaVal) {
                                    const optionEl = document.createElement('option');
                                    optionEl.value = opt.value;
                                    optionEl.textContent = opt.text;
                                    if (opt.wilaya) {
                                        optionEl.setAttribute('data-wilaya', opt.wilaya);
                                    }
                                    if (opt.value === currentVal) {
                                        optionEl.selected = true;
                                    }
                                    fragment.appendChild(optionEl);
                                }
                            });
                            
                            etabSelect.innerHTML = "";
                            etabSelect.appendChild(fragment);
                        }
                    }
                });
                </script>
            @endif

            @yield('content')
        </main>

    </div><!-- /.workspace -->

</div><!-- /.app-shell -->

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav">
    <a href="{{ url('dashboard') }}" class="mob-nav-item {{ ($isActive('/dashboard', true) && !isset($_GET['tab'])) ? 'active' : '' }}">
        <i class="fa-solid fa-chart-line"></i>
        <span>الرئيسية</span>
    </a>
    <a href="javascript:void(0)" class="mob-nav-item" onclick="openMobileMenu()">
        <i class="fa-solid fa-bars"></i>
        <span>القائمة</span>
    </a>
    <a href="{{ url('dashboard/profile') }}" class="mob-nav-item {{ $isActive('/dashboard/profile') }}">
        <i class="fa-solid fa-user-gear"></i>
        <span>الملف</span>
    </a>
    <a href="{{ request()->is('sig/*') ? url('sig/logout') : url('logout') }}" class="mob-nav-item text-danger">
        <i class="fa-solid fa-power-off"></i>
        <span>خروج</span>
    </a>
</nav>

<!-- Floating Command Palette Modal -->
<div class="modal fade" id="commandPaletteModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-body p-4" style="direction: rtl;">
                <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded-4" style="border: 1px solid var(--border);">
                    <i class="fa-solid fa-magnifying-glass text-primary fs-5"></i>
                    <input type="text" class="form-control border-0 bg-transparent py-2 shadow-none fs-5" id="commandPaletteInput" placeholder="ابحث عن المتربصين، الخدمات، أو الأقسام..." style="outline: none;">
                    <span class="badge bg-secondary py-2 px-3 text-dark">ESC</span>
                </div>
                <div class="text-muted small px-2 mb-3 fw-bold"><i class="fa-solid fa-circle-info me-1"></i> اختصارات سريعة للوصول إلى أقسام الحوكمة</div>
                <div class="d-flex flex-column gap-2" id="commandPaletteResults">
                    <a href="{{ url('dashboard') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none bg-light-hover" style="border: 1px solid var(--border); transition: all 0.2s;">
                        <span class="text-dark fw-bold"><i class="fa-solid fa-chart-line text-primary me-2"></i> لوحة التحليلات الرئيسية</span>
                        <i class="fa-solid fa-chevron-left text-muted"></i>
                    </a>
                    <a href="{{ url('resultats') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none bg-light-hover" style="border: 1px solid var(--border); transition: all 0.2s;">
                        <span class="text-dark fw-bold"><i class="fa-solid fa-star-half-stroke text-primary me-2"></i> بوابة تقييم الأداء السداسي</span>
                        <i class="fa-solid fa-chevron-left text-muted"></i>
                    </a>
                    <a href="{{ url('dashboard/profile') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none bg-light-hover" style="border: 1px solid var(--border); transition: all 0.2s;">
                        <span class="text-dark fw-bold"><i class="fa-solid fa-user-gear text-primary me-2"></i> إعدادات الملف الشخصي والسرية</span>
                        <i class="fa-solid fa-chevron-left text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>

<script>
// ===== Theme Logic =====
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('sgfep_theme', theme);
    const icon = document.getElementById('themeIcon');
    if (icon) {
        icon.className = theme === 'dark' ? 'fa-solid fa-sun text-warning' : 'fa-regular fa-moon text-muted';
    }
}
function toggleTheme() {
    const cur = document.documentElement.getAttribute('data-theme') || 'light';
    applyTheme(cur === 'dark' ? 'light' : 'dark');
}

// ===== Collapsible Sidebar Dropdowns =====
function toggleSidebarDropdown(btn) {
    const parent = btn.parentElement;
    const submenu = parent.querySelector('.sidebar-submenu');
    const chevron = btn.querySelector('.dropdown-chevron');
    
    const isOpen = submenu.classList.contains('open');
    
    document.querySelectorAll('.sidebar-submenu').forEach(el => {
        el.classList.remove('open');
    });
    document.querySelectorAll('.dropdown-chevron').forEach(el => {
        el.classList.remove('fa-chevron-up');
        el.classList.add('fa-chevron-down');
    });
    
    if (!isOpen) {
        submenu.classList.add('open');
        if (chevron) {
            chevron.classList.remove('fa-chevron-down');
            chevron.classList.add('fa-chevron-up');
        }
    }
}

function closeAllDrawers() {
    if (document.querySelectorAll('.modal.show').length > 0) return;
    const sidebar = document.getElementById('sovereignSidebar');
    if (sidebar) sidebar.classList.remove('open-mobile');
    const overlay = document.getElementById('sbOverlay');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function openMobileMenu() {
    const sidebar = document.getElementById('sovereignSidebar');
    if (sidebar) sidebar.classList.add('open-mobile');
    const overlay = document.getElementById('sbOverlay');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// ===== Command Palette Modal Control =====
let commandPaletteModal = null;
document.addEventListener('DOMContentLoaded', function() {
    const cpEl = document.getElementById('commandPaletteModal');
    if (cpEl) {
        const existing = bootstrap.Modal.getInstance(cpEl);
        if (existing) existing.dispose();
        commandPaletteModal = new bootstrap.Modal(cpEl, { backdrop: true, keyboard: true });
        cpEl.addEventListener('hidden.bs.modal', function () {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }

    document.addEventListener('hidden.bs.modal', function () {
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length === 0) {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    });
    
    window.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            openCommandPalette();
        }
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(function(modalEl) {
                const inst = bootstrap.Modal.getInstance(modalEl);
                if (inst) inst.hide();
            });
        }
    });

    const paletteInput = document.getElementById('commandPaletteInput');
    if (paletteInput) {
        paletteInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            searchCommandPalette(query);
        });
    }
});

let searchDebounceTimeout = null;
const defaultPaletteHtml = `
    <a href="{{ url('dashboard') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none bg-light-hover" style="border: 1px solid var(--border); transition: all 0.2s;">
        <span class="text-dark fw-bold"><i class="fa-solid fa-chart-line text-primary me-2"></i> لوحة التحليلات الرئيسية</span>
        <i class="fa-solid fa-chevron-left text-muted"></i>
    </a>
    <a href="{{ url('resultats') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none bg-light-hover" style="border: 1px solid var(--border); transition: all 0.2s;">
        <span class="text-dark fw-bold"><i class="fa-solid fa-star-half-stroke text-primary me-2"></i> بوابة تقييم الأداء السداسي</span>
        <i class="fa-solid fa-chevron-left text-muted"></i>
    </a>
    <a href="{{ url('dashboard/profile') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none bg-light-hover" style="border: 1px solid var(--border); transition: all 0.2s;">
        <span class="text-dark fw-bold"><i class="fa-solid fa-user-gear text-primary me-2"></i> إعدادات الملف الشخصي والسرية</span>
        <i class="fa-solid fa-chevron-left text-muted"></i>
    </a>
`;

function searchCommandPalette(query) {
    const listContainer = document.getElementById('commandPaletteResults');
    if (!listContainer) return;

    if (!query) {
        listContainer.innerHTML = defaultPaletteHtml;
        return;
    }

    if (query.length < 2) {
        return;
    }

    listContainer.innerHTML = '<div class="text-center p-3 text-muted small"><i class="fa-solid fa-spinner fa-spin me-2"></i>جاري البحث...</div>';

    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        const basePrefix = window.location.pathname.startsWith('/sig') ? '/sig' : '';
        fetch(basePrefix + '/dashboard/api/search-all?q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                listContainer.innerHTML = '';
                if (!data || data.length === 0) {
                    listContainer.innerHTML = '<div class="text-center p-4 text-muted small"><i class="fa-solid fa-circle-question fs-4 mb-2 d-block"></i>لا توجد نتائج مطابقة لـ "' + query + '"</div>';
                    return;
                }

                data.forEach(cat => {
                    const catHeader = document.createElement('div');
                    catHeader.className = 'text-secondary fw-bold small mt-3 mb-1 px-2 text-end';
                    catHeader.style.fontFamily = 'Cairo';
                    catHeader.style.fontSize = '0.8rem';
                    catHeader.textContent = cat.category;
                    listContainer.appendChild(catHeader);

                    cat.items.forEach(item => {
                        const a = document.createElement('a');
                        a.href = item.url;
                        a.className = 'd-flex align-items-center justify-content-between p-2.5 px-3 rounded-3 text-decoration-none bg-light-hover';
                        a.style.border = '1px solid var(--border)';
                        a.style.transition = 'all 0.2s';
                        a.style.marginBottom = '6px';
                        
                        a.innerHTML = `
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3" style="width:32px; height:32px; flex-shrink:0;">
                                    <i class="fa-solid ${item.icon}"></i>
                                </div>
                                <div class="text-start">
                                    <div class="text-dark fw-bold" style="font-size:0.85rem;">${item.title}</div>
                                    <div class="text-muted" style="font-size:0.75rem;">${item.desc}</div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-left text-muted" style="font-size:0.75rem;"></i>
                        `;
                        listContainer.appendChild(a);
                    });
                });
            })
            .catch(err => {
                console.error(err);
                listContainer.innerHTML = '<div class="text-center p-3 text-danger small">حدث خطأ أثناء الاتصال بالخادم</div>';
            });
    }, 250);
}

function openCommandPalette() {
    if (commandPaletteModal) {
        commandPaletteModal.show();
        setTimeout(() => {
            const input = document.getElementById('commandPaletteInput');
            if (input) {
                input.value = '';
                input.focus();
                searchCommandPalette('');
            }
        }, 300);
    }
}

// ===== Notifications Fetcher =====
function fetchNotifications() {
    const basePrefix = window.location.pathname.startsWith('/sig') ? '/sig' : '';
    fetch(basePrefix + '/dashboard/notifications/fetch')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const dot = document.getElementById('notifDot');
            const list = document.getElementById('notifList');
            if (dot) dot.style.display = data.unread_count > 0 ? 'block' : 'none';
            if (!list) return;
            list.innerHTML = '';
            if (data.notifications.length === 0) {
                list.innerHTML = '<div class="text-center p-4 text-muted small"><i class="fa-solid fa-bell-slash fs-4 mb-2 d-block"></i>لا توجد إخطارات جديدة</div>';
            } else {
                data.notifications.forEach(n => {
                    const unread = n.is_read == 0;
                    const dotHtml = unread ? '<div style="width:7px;height:7px;border-radius:50%;background:#1A6BCC;flex-shrink:0;margin-top:5px;"></div>' : '';
                    const iconColor = n.type === 'success' ? '#0EA66E' : (n.type === 'warning' ? '#F0A500' : '#d9534f');
                    const iconClass = n.type === 'success' ? 'fa-circle-check' : (n.type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-exclamation');
                    list.innerHTML += `
                        <a href="${n.url ? (basePrefix + n.url) : '#'}" class="text-decoration-none" onclick="markNotifRead(${n.id})">
                            <div class="d-flex gap-2 p-2 rounded-3 mb-1" style="${unread ? 'background:rgba(26,107,204,.05);' : ''}">
                                <i class="fa-solid ${iconClass} mt-1" style="color:${iconColor}; font-size:.85rem;"></i>
                                <div class="flex-grow-1 text-end" style="direction: rtl;">
                                    <div class="fw-bold" style="font-size:.82rem; color:var(--tx-1);">${n.title}</div>
                                    <div style="font-size:.75rem; color:var(--tx-2);">${n.message}</div>
                                    <div style="font-size:.68rem; color:var(--tx-3);">${n.created_at}</div>
                                </div>
                                ${dotHtml}
                            </div>
                        </a>
                    `;
                });
            }
        }).catch(() => {});
}

function markNotifRead(id) {
    const basePrefix = window.location.pathname.startsWith('/sig') ? '/sig' : '';
    fetch(basePrefix + '/dashboard/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: 'id=' + encodeURIComponent(id)
    }).then(() => fetchNotifications());
}

function markAllNotifsRead() {
    const basePrefix = window.location.pathname.startsWith('/sig') ? '/sig' : '';
    fetch(basePrefix + '/dashboard/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: ''
    }).then(() => fetchNotifications());
}

// ===== Init =====
function toggleSidebarPin() {
    const shell = document.querySelector('.app-shell');
    if (shell) {
        const isPinned = shell.classList.toggle('sidebar-pinned');
        localStorage.setItem('sidebar_pinned', isPinned ? 'true' : 'false');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    applyTheme(localStorage.getItem('sgfep_theme') || 'light');
    fetchNotifications();
    setInterval(fetchNotifications, 120000);
    
    // Restore sidebar pin state
    const isPinned = localStorage.getItem('sidebar_pinned') === 'true';
    if (isPinned) {
        const shell = document.querySelector('.app-shell');
        if (shell) shell.classList.add('sidebar-pinned');
    }
});


// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register("{{ url('') }}/sw.js")
            .then(function(registration) {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}
</script>
<script src="{{ asset('assets/js/pwa-prompt.js?v=1.0') }}"></script>

@if (\App\Helpers\SovereignLicensingHelper::getSetting('content_protection_shield_active', '1') === '1')
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
@php
    $watermarkText = 'تسيير - ' . ($user['nom_complet'] ?? 'مستخدم') . ' - ' . date('Y-m-d H:i');
@endphp
<div class="sovereign-watermark-container">
    <div class="sovereign-watermark" style="top: 15%; left: 10%;">{{ $watermarkText }}</div>
    <div class="sovereign-watermark" style="top: 40%; left: 60%; animation-delay: -10s;">{{ $watermarkText }}</div>
    <div class="sovereign-watermark" style="top: 75%; left: 20%; animation-delay: -20s;">{{ $watermarkText }}</div>
    <div class="sovereign-watermark" style="top: 85%; left: 75%; animation-delay: -30s;">{{ $watermarkText }}</div>
</div>

<script>
(function() {
    const shield = document.getElementById('sovereignContentShield');
    const contentArea = document.querySelector('.content-area');
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
    window.addEventListener('blur', function() {
        if (document.activeElement && document.activeElement.tagName === 'IFRAME') {
            return;
        }
        showShield();
    });
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
    // 6. Global Modal Relocator to prevent backdrop stacking bugs on dynamic pages
    document.addEventListener('show.bs.modal', function(event) {
        const modal = event.target;
        if (modal && modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
    });
})();
</script>
@endif
<!-- Intro.js Tour Engine -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">
<style>
    .floating-help-btn {
        position: fixed;
        bottom: 30px;
        left: 30px;
        background-color: var(--color-gov-purple-dark, #4a154b);
        color: white !important;
        border: none;
        border-radius: 50%;
        width: 52px;
        height: 52px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(74, 21, 75, 0.35);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    .floating-help-btn:hover {
        transform: scale(1.1) rotate(15deg);
        background-color: #3b103c;
    }
    .introjs-tooltip {
        background-color: #ffffff;
        color: #1e3a5f;
        font-family: 'Cairo', sans-serif;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border: 1px solid rgba(0,0,0,0.05);
        padding: 15px;
    }
    .introjs-tooltiptitle {
        font-weight: bold;
        color: #1e3a5f;
        font-size: 1.1rem;
    }
    .introjs-button {
        border-radius: 20px !important;
        font-family: 'Cairo', sans-serif !important;
        font-size: 0.85rem !important;
        font-weight: bold !important;
        padding: 6px 15px !important;
        transition: all 0.2s ease !important;
    }
    .introjs-donebutton, .introjs-nextbutton {
        background-color: var(--color-gov-purple-dark, #4a154b) !important;
        color: #ffffff !important;
        border: 1px solid var(--color-gov-purple-dark, #4a154b) !important;
        text-shadow: none !important;
    }
    .introjs-donebutton:hover, .introjs-nextbutton:hover {
        background-color: #3b103c !important;
    }
    .introjs-prevbutton {
        background-color: #f1f5f9 !important;
        color: #64748b !important;
        border: 1px solid #e2e8f0 !important;
        text-shadow: none !important;
    }
    .introjs-prevbutton:hover {
        background-color: #e2e8f0 !important;
    }
    .introjs-bullets ul li a.active {
        background: var(--color-gov-purple-dark, #4a154b) !important;
    }
</style>

<!-- Universal Guide Floating Button -->
<button id="global-tour-btn" class="floating-help-btn" title="دليل الصفحة">
    <i class="fas fa-compass"></i>
</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>
<script src="{{ asset('assets/js/universal-tours.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let currentPath = window.location.pathname;
        if (currentPath.startsWith('/sig')) {
            currentPath = currentPath.substring(4);
        }
        currentPath = '/' + currentPath.replace(/^\/+|\/+$/g, '');

        if (typeof UniversalTours !== 'undefined' && UniversalTours[currentPath]) {
            const steps = UniversalTours[currentPath];
            const tourKey = 'tour_seen_' + currentPath.replace(/\//g, '_');

            const globalTourBtn = document.getElementById('global-tour-btn');
            if (globalTourBtn) {
                globalTourBtn.style.display = 'flex';
                globalTourBtn.addEventListener('click', function() {
                    runUniversalTour(steps);
                });
            }

            if (!localStorage.getItem(tourKey)) {
                setTimeout(function() {
                    runUniversalTour(steps, tourKey);
                }, 1800);
            }
        }
    });

    function runUniversalTour(steps, saveKey = null) {
        introJs().setOptions({
            steps: steps,
            nextLabel: 'التالي <i class="fas fa-chevron-left ms-1"></i>',
            prevLabel: '<i class="fas fa-chevron-right me-1"></i> السابق',
            doneLabel: 'إتمام الجولة <i class="fas fa-check-circle ms-1"></i>',
            exitOnOverlayClick: false,
            showProgress: true,
            language: 'ar'
        }).oncomplete(function() {
            if (saveKey) localStorage.setItem(saveKey, 'true');
        }).onexit(function() {
            if (saveKey) localStorage.setItem(saveKey, 'true');
        }).start();
    }
</script>

<script>
(function() {
    const replacements = [
        { search: /(?<!\p{L})المتربصين وال[ط]لاب(?!\p{L})/gu, replace: 'المتربصين' },
        { search: /(?<!\p{L})إحصائيات المتربصين وال[ط]لاب(?!\p{L})/gu, replace: 'إحصائيات المتربصين' },
        { search: /(?<!\p{L})تقرير المتربصين وال[ط]لاب(?!\p{L})/gu, replace: 'تقرير المتربصين' },
        { search: /(?<!\p{L})ال[ط]لاب(?!\p{L})/gu, replace: 'المتربصين' },
        { search: /(?<!\p{L})ال[ط]لبة(?!\p{L})/gu, replace: 'المتربصين' },
        { search: /(?<!\p{L})[ط]لاب(?!\p{L})/gu, replace: 'متربصين' },
        { search: /(?<!\p{L})[ط]البي التكوين(?!\p{L})/gu, replace: 'متربصي التكوين' },
        { search: /(?<!\p{L})[ط]البي(?!\p{L})/gu, replace: 'متربصي' },
        { search: /(?<!\p{L})[ط]البين(?!\p{L})/gu, replace: 'متربصين' },
        { search: /(?<!\p{L})[ط]البان(?!\p{L})/gu, replace: 'متربصان' },
        { search: /(?<!\p{L})[ط]البون(?!\p{L})/gu, replace: 'متربصون' },
        { search: /(?<!\p{L})[ط]الباً(?!\p{L})/gu, replace: 'متربصاً' },
        { search: /(?<!\p{L})ال[ط]البات(?!\p{L})/gu, replace: 'المتربصات' },
        { search: /(?<!\p{L})[ط]البة(?!\p{L})/gu, replace: 'متربصة' },
        { search: /(?<!\p{L})[ط]الب(?!\p{L})/gu, replace: 'متربص' }
    ];

    function replaceAttributes(node) {
        if (node.nodeType === Node.ELEMENT_NODE) {
            const placeholder = node.getAttribute('placeholder');
            if (placeholder) {
                let text = placeholder;
                let changed = false;
                replacements.forEach(r => {
                    r.search.lastIndex = 0;
                    if (r.search.test(text)) {
                        text = text.replace(r.search, r.replace);
                        changed = true;
                    }
                });
                if (changed) {
                    node.setAttribute('placeholder', text);
                }
            }

            const title = node.getAttribute('title');
            if (title) {
                let text = title;
                let changed = false;
                replacements.forEach(r => {
                    r.search.lastIndex = 0;
                    if (r.search.test(text)) {
                        text = text.replace(r.search, r.replace);
                        changed = true;
                    }
                });
                if (changed) {
                    node.setAttribute('title', text);
                }
            }
        }
    }

    function replaceTextInNode(node) {
        replaceAttributes(node);

        if (node.nodeType === Node.TEXT_NODE) {
            let text = node.nodeValue;
            let changed = false;
            
            const parent = node.parentNode;
            if (parent && (parent.tagName === 'SCRIPT' || parent.tagName === 'STYLE' || parent.tagName === 'TEXTAREA')) {
                return;
            }

            replacements.forEach(r => {
                r.search.lastIndex = 0;
                if (r.search.test(text)) {
                    text = text.replace(r.search, r.replace);
                    changed = true;
                }
            });

            if (changed) {
                node.nodeValue = text;
            }
        } else {
            for (let child of node.childNodes) {
                replaceTextInNode(child);
            }
        }
    }

    function replaceTitle() {
        let titleText = document.title;
        let titleChanged = false;
        replacements.forEach(r => {
            r.search.lastIndex = 0;
            if (r.search.test(titleText)) {
                titleText = titleText.replace(r.search, r.replace);
                titleChanged = true;
            }
        });
        if (titleChanged) {
            document.title = titleText;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        replaceTextInNode(document.body);
        replaceTitle();

        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    replaceTextInNode(node);
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });

    // Concurrent session real-time background check
    (function() {
        const checkUrl = window.location.pathname.includes('/sig') ? '{{ url("/sig/session/check-active") }}' : '{{ url("/session/check-active") }}';
        setInterval(() => {
            fetch(checkUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => {
                if (res.status === 401 || res.redirected) {
                    window.location.reload();
                    return;
                }
                return res.json();
            })
            .then(data => {
                if (data && data.active === false) {
                    window.location.reload();
                }
            })
            .catch(() => {});
        }, 10000); // Check every 10 seconds
    })();
})();
</script>

@yield('scripts')
</body>
</html>
