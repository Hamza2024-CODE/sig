<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ في النظام – SGFEP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
        }
        .error-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 50px 60px;
            max-width: 680px;
            width: 90%;
            text-align: center;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .error-icon {
            font-size: 72px;
            margin-bottom: 24px;
            display: block;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 12px;
        }
        .subtitle {
            color: #94a3b8;
            font-size: 1rem;
            margin-bottom: 32px;
            line-height: 1.7;
        }
        @media (prefers-color-scheme: light) {
            .error-detail {
                display: none;
            }
        }
        .error-detail {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 32px;
            text-align: left;
            direction: ltr;
            font-family: monospace;
            font-size: 0.82rem;
            color: #fca5a5;
            word-break: break-all;
        }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 10px;
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59,130,246,0.5); }
        .btn-ghost {
            background: rgba(255,255,255,0.08);
            color: #94a3b8;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.12); color: #e2e8f0; }

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
        .error-card.shield-active {
            filter: blur(20px) !important;
            transition: filter 0.2s ease;
        }
    </style>
    <!-- FontAwesome for Shield Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="error-card">
        <span class="error-icon">⚠️</span>
        <h1>حدث خطأ في النظام</h1>
        <p class="subtitle">
            واجه النظام خطأً غير متوقع أثناء معالجة طلبك.<br>
            <span style="font-size:0.9em;color:#64748b;">Une erreur inattendue s'est produite lors du traitement de votre requête.</span>
        </p>

        @if(config('app.debug'))
        <div class="error-detail">
            <strong>{{ $message ?? 'Unknown error' }}</strong><br>
            @if(isset($file)) <span style="color:#fbbf24;">{{ $file }}</span> : ligne {{ $line ?? '?' }} @endif
        </div>
        @endif

        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-ghost">← رجوع</a>
            <a href="/dashboard" class="btn btn-primary">🏠 لوحة التحكم</a>
        </div>
    </div>

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
        $watermarkText = 'تسيير - خطأ في النظام - ' . date('Y-m-d H:i');
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
        const contentArea = document.querySelector('.error-card');
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
    </script>
@endif
</body>
</html>
