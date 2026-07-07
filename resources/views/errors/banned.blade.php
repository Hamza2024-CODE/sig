<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم حظر الوصول المؤقت — SGFEP Shield</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #0b0f19;
            --surface: #151d30;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --red: #ef4444;
            --red-glow: rgba(239, 68, 68, 0.15);
            --border: rgba(255, 255, 255, 0.08);
        }
        body {
            background-color: var(--bg);
            color: var(--text-primary);
            font-family: 'Cairo', 'Segoe UI', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .banned-card {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 80px var(--red-glow);
            animation: fadeIn 0.6s ease-out;
        }
        .shield-icon {
            width: 80px;
            height: 80px;
            background-color: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--red);
            font-size: 2.2rem;
            box-shadow: 0 0 20px var(--red-glow);
        }
        h1 {
            font-size: 1.6rem;
            font-weight: 900;
            margin: 0 0 12px;
            color: var(--text-primary);
        }
        p.subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0 0 30px;
        }
        .details-box {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            text-align: right;
            margin-bottom: 30px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 700;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        .detail-value {
            font-family: 'Outfit', 'Cairo', monospace;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.85rem;
        }
        .contact-text {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin: 0;
        }
        .btn-retry {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #1f2937, #111827);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 12px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-retry:hover {
            background: linear-gradient(135deg, #374151, #1f2937);
            transform: translateY(-2px);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="banned-card">
        <div class="shield-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1>تم حظر الوصول مؤقتاً</h1>
        <p class="subtitle">لقد رصد نظام الحماية سلوكاً مشبوهاً أو محاولات دخول فاشلة متكررة من عنوان الاتصال الخاص بك، وتم تقييد ولوجك لحماية البيانات العامة.</p>
        
        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">عنوان الـ IP الخاص بك</span>
                <span class="detail-value">{{ $ip }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">سبب الحظر</span>
                <span class="detail-value text-danger">{{ $reason }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">تاريخ فك الحظر</span>
                <span class="detail-value">
                    @if($banned_until)
                        {{ date('Y-m-d H:i:s', strtotime($banned_until)) }}
                    @else
                        دائم (تواصل مع الدعم)
                    @endif
                </span>
            </div>
        </div>

        <p class="contact-text">إذا كنت تعتقد أن هذا الحظر قد تم بالخطأ، يرجى مراسلة مديرية تكنولوجيا المعلومات بالوزارة.</p>
        
        <button onclick="window.location.reload()" class="btn-retry">
            <i class="fa-solid fa-rotate-right"></i>
            <span>إعادة المحاولة / Actualiser</span>
        </button>
    </div>
</body>
</html>
