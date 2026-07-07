<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SGFEP Print')</title>
    
    <!-- Google Fonts: Cairo & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    
    <!-- FontAwesome for Premium Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/app.css?v=5.1') }}">
    
    <style>
        body {
            font-family: 'Cairo', 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        
        @media print {
            body {
                background-color: white !important;
            }
            .no-print {
                display: none !important;
            }
            .sovereign-content-shield {
                display: none !important;
            }
            .sovereign-watermark-container {
                display: none !important;
            }
        }

        /* ─── Sovereign Content Shield CSS ─── */
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
        main.shield-active {
            filter: blur(20px) !important;
            transition: filter 0.2s ease;
        }
    </style>
</head>
<body>

    <!-- Main Content Injection -->
    <main>
        @yield('content')
    </main>

    <!-- Dynamic Watermarks Container -->
    @php
        $printUser = session('user') ?? [];
        $watermarkText = 'تسيير - ' . (!empty($printUser['nom_complet']) ? $printUser['nom_complet'] : 'بوابة عامة') . ' - ' . date('Y-m-d H:i');
    @endphp
    <div class="sovereign-watermark-container">
        <div class="sovereign-watermark" style="top: 15%; left: 10%;">{{ $watermarkText }}</div>
        <div class="sovereign-watermark" style="top: 40%; left: 60%; animation-delay: -10s;">{{ $watermarkText }}</div>
        <div class="sovereign-watermark" style="top: 75%; left: 20%; animation-delay: -20s;">{{ $watermarkText }}</div>
        <div class="sovereign-watermark" style="top: 85%; left: 75%; animation-delay: -30s;">{{ $watermarkText }}</div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
