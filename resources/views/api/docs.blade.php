<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>توثيق واجهة البرمجة التطبيقية (API Docs) — SGFEP</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    
    <!-- Swagger UI CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css">
    
    <style>
        :root {
            --primary-color: #006233;
            --primary-dark: #004b27;
            --secondary-dark: #0d2137;
            --accent-color: #f59e0b;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Cairo', 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* Sovereign Header Styling */
        .api-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a5f 70%, var(--secondary-dark) 100%);
            color: #fff;
            padding: 1.5rem 2rem;
            border-bottom: 4px solid var(--accent-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .api-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .api-header p {
            font-size: 0.85rem;
            opacity: 0.85;
            margin-bottom: 0;
        }

        /* Swagger UI Overrides for Sovereign Theme styling */
        .swagger-ui {
            font-family: 'Cairo', 'Outfit', sans-serif !important;
            direction: ltr; /* Swagger UI internally expects LTR direction */
        }
        
        .swagger-ui .info {
            margin: 2rem 0 !important;
            direction: rtl; /* Force info panel Arabic text right */
            text-align: right;
        }

        .swagger-ui .info .title {
            font-family: 'Cairo', sans-serif !important;
            color: var(--secondary-dark) !important;
            font-weight: 800;
        }

        .swagger-ui .info p, .swagger-ui .info li, .swagger-ui .info td {
            font-family: 'Cairo', sans-serif !important;
            font-size: 0.9rem !important;
            color: #475569 !important;
        }

        .swagger-ui .scheme-container {
            background-color: #fff !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid var(--border-color) !important;
            padding: 1.5rem !important;
            margin: 1.5rem 0 !important;
            direction: ltr;
        }

        .swagger-ui .opblock {
            border-radius: 10px !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            margin-bottom: 1rem !important;
        }

        .swagger-ui .opblock .opblock-summary {
            padding: 0.75rem 1rem !important;
        }

        .swagger-ui .opblock-summary-method {
            border-radius: 6px !important;
            font-weight: 700 !important;
            font-family: 'Outfit', sans-serif !important;
        }

        .swagger-ui .btn.authorize {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #fff !important;
            border-radius: 30px !important;
            font-family: 'Cairo', sans-serif !important;
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            padding: 0.5rem 1.5rem !important;
            transition: all 0.2s !important;
        }

        .swagger-ui .btn.authorize:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
        }

        .swagger-ui .btn.authorize svg {
            fill: #fff !important;
            margin-right: 6px;
        }

        /* Container styling */
        .docs-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .card-custom {
            background-color: #fff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            padding: 1.5rem;
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
            font-family: 'Cairo', sans-serif;
            transform: rotate(-25deg);
            animation: watermark-drift 40s linear infinite alternate;
        }
        @keyframes watermark-drift {
            0% { transform: translate(0, 0) rotate(-25deg); }
            50% { transform: translate(100px, 80px) rotate(-20deg); }
            100% { transform: translate(-80px, 150px) rotate(-30deg); }
        }
        .docs-container.shield-active {
            filter: blur(20px) !important;
            transition: filter 0.2s ease;
        }
    </style>
</head>
<body>

    <!-- Premium Sovereign Header -->
    <header class="api-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fa-solid fa-satellite-dish text-warning me-2"></i>
                بوابة تكامل الأنظمة وربط الـ API — SGFEP
            </h1>
            <p>التوثيق المرجعي التفاعلي الرسمي لواجهات البرمجة الحكومية والشركاء الاقتصاديين</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="/dashboard/api-center" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" style="font-family:'Cairo';">
                <i class="fa-solid fa-key me-1"></i> إدارة المفاتيح
            </a>
            <a href="/dashboard" class="btn btn-outline-light rounded-pill px-4 fw-bold" style="font-family:'Cairo';">
                <i class="fa-solid fa-arrow-right me-1"></i> لوحة التحكم
            </a>
        </div>
    </header>

    <!-- Interactive Documentation Workspace -->
    <main class="docs-container">
        <div class="card-custom">
            <div id="swagger-ui"></div>
        </div>
    </main>

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
        $watermarkText = 'تسيير - وثائق الـ API - ' . date('Y-m-d H:i');
    @endphp
    <div class="sovereign-watermark-container">
        <div class="sovereign-watermark" style="top: 15%; left: 10%;">{{ $watermarkText }}</div>
        <div class="sovereign-watermark" style="top: 40%; left: 60%; animation-delay: -10s;">{{ $watermarkText }}</div>
        <div class="sovereign-watermark" style="top: 75%; left: 20%; animation-delay: -20s;">{{ $watermarkText }}</div>
        <div class="sovereign-watermark" style="top: 85%; left: 75%; animation-delay: -30s;">{{ $watermarkText }}</div>
    </div>
@endif

    <!-- Swagger UI Dependencies -->
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Determine current URL path to fetch openapi.json relatively
            const pathSegments = window.location.pathname.split('/');
            // If running under /sig/ prefix, adjust JSON location
            const isSubfolder = pathSegments.includes('sig');
            const openApiUrl = isSubfolder ? '/sig/api/v1/openapi.json' : '/api/v1/openapi.json';

            const ui = SwaggerUIBundle({
                url: openApiUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ],
                plugins: [
                    SwaggerUIBundle.presets.apis.plugins ?? (() => ({})),
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout",
                defaultModelsExpandDepth: -1, // Hides the schemas section from cluttering the bottom
                operationsSorter: "alpha"
            });
            window.ui = ui;
        };
    </script>

    <script>
        (function() {
            const shield = document.getElementById('sovereignContentShield');
            const contentArea = document.querySelector('.docs-container');
            let isPrinting = false;
            
            function showShield() {
                if (isPrinting) return;
                if (shield) shield.classList.add('active');
                if (contentArea) contentArea.classList.add('shield-active');
            }
            
            // Allow active user inspection of documentation without instant lock upon initial loading
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

            // 1. Focus & Page Visibility Monitors
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

            // 2. Gesture blocker
            document.addEventListener('touchstart', function(e) {
                if (e.touches.length >= 3) {
                    e.preventDefault();
                    showShield();
                    setTimeout(hideShield, 2000);
                }
            }, { passive: false });

            // 3. Prevent DevTools & Inspection Key Combinations
            window.addEventListener('keydown', function(e) {
                if (e.keyCode === 123) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                if (e.ctrlKey && e.shiftKey && (e.key.toLowerCase() === 'i' || e.key.toLowerCase() === 'j' || e.key.toLowerCase() === 'c')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                if (e.ctrlKey && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
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
    </script>@endif
</body>
</html>
