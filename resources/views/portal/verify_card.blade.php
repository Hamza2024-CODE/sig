<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من صحة البطاقة الرقمية</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@400;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --panel-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success-glow: 0 0 20px rgba(16, 185, 129, 0.2);
            --danger-glow: 0 0 20px rgba(239, 68, 68, 0.2);
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-primary);
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verify-container {
            max-width: 500px;
            width: 100%;
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .verify-header {
            padding: 30px 20px 20px;
            text-align: center;
            border-bottom: 1px solid var(--panel-border);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .status-badge.verified {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: var(--success-glow);
        }

        .status-badge.failed {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: var(--danger-glow);
        }

        .avatar-frame {
            width: 110px;
            height: 135px;
            border-radius: 12px;
            border: 3px solid rgba(255, 255, 255, 0.15);
            object-fit: cover;
            box-shadow: 0 8px 16px rgba(0,0,0,0.25);
            margin-bottom: 15px;
            background: #1e293b;
        }

        .info-grid {
            padding: 24px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .info-val {
            color: var(--text-primary);
            font-size: 0.92rem;
            font-weight: 700;
            text-align: left;
        }

        .btn-portal {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            font-weight: 700;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-portal:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .ministry-logo {
            font-size: 1.8rem;
            color: #10b981;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

    <div class="verify-container">
        
        @if($success)
            <div class="verify-header">
                <div class="ministry-logo">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="small text-uppercase tracking-wider text-success fw-bold mb-2">وزارة التكوين والتعليم المهنيين</div>
                
                <div class="status-badge verified">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>بطاقة معتمدة ورسمية</span>
                </div>

                <div>
                    @php
                        $photoUrl = $data['photo'] ?? null;
                        if ($photoUrl) {
                            $photoUrl = url(ltrim($photoUrl, '/'));
                        } else {
                            $seed = ($type === 'employee' ? ($data['Nom'] ?? 'User') : ($data['nom_ar'] ?? 'Trainee'));
                            $photoUrl = "https://api.dicebear.com/7.x/initials/svg?seed=" . urlencode($seed);
                        }
                    @endphp
                    <img src="{{ $photoUrl }}" alt="Photo" class="avatar-frame" onerror="this.src='https://api.dicebear.com/7.x/initials/svg?seed=User'">
                </div>

                <h4 class="fw-bold mb-1 text-white">
                    @if($type === 'employee')
                        {{ ($data['Nom'] ?? '') . ' ' . ($data['Prenom'] ?? '') }}
                    @else
                        {{ ($data['nom_ar'] ?? '') . ' ' . ($data['prenom_ar'] ?? '') }}
                    @endif
                </h4>
                
                <div class="text-secondary small">
                    @if($type === 'employee')
                        {{ strtoupper(($data['NomFr'] ?? '') . ' ' . ($data['PrenomFr'] ?? '')) }}
                    @else
                        {{ strtoupper(($data['nom_fr'] ?? '') . ' ' . ($data['prenom_fr'] ?? '')) }}
                    @endif
                </div>
            </div>

            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">صفة حامل البطاقة</span>
                    <span class="info-val badge bg-primary px-2.5 py-1.5 rounded-pill">
                        {{ $type === 'employee' ? 'موظف / أستاذ بالقطاع' : 'متربص / متكون بالقطاع' }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">الرقم التعريفي</span>
                    <span class="info-val font-monospace">{{ $type === 'employee' ? $data['IDEncadrement'] : $data['id'] }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">المؤسسة التكوينية</span>
                    <span class="info-val">{{ $data['etab_nom'] ?? 'غير محدد' }}</span>
                </div>

                @if($type === 'employee')
                    <div class="info-row">
                        <span class="info-label">الرتبة / السلك</span>
                        <span class="info-val">{{ $data['grade_nom'] ?? 'غير محدد' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">الوظيفة الحالية</span>
                        <span class="info-val">{{ $data['fonction_nom'] ?? $data['TachesPrincipale'] ?? 'غير محدد' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ التعيين</span>
                        <span class="info-val">{{ !empty($data['Daterecr']) ? date('Y/m/d', strtotime($data['Daterecr'])) : 'غير محدد' }}</span>
                    </div>
                @else
                    <div class="info-row">
                        <span class="info-label">التخصص</span>
                        <span class="info-val">{{ $data['spec_ar'] ?? 'غير محدد' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">نمط التكوين</span>
                        <span class="info-val">{{ $data['mode_nom'] ?? 'غير محدد' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">فترة التكوين</span>
                        <span class="info-val">
                            من: {{ !empty($data['date_deb']) ? date('Y/m/d', strtotime($data['date_deb'])) : '—' }} 
                            إلى: {{ !empty($data['date_fin']) ? date('Y/m/d', strtotime($data['date_fin'])) : '—' }}
                        </span>
                    </div>
                @endif

                <div class="info-row">
                    <span class="info-label">تاريخ الميلاد</span>
                    <span class="info-val">{{ !empty($data['DateNais']) ? str_replace('-', '/', $data['DateNais']) : 'غير محدد' }}</span>
                </div>

                <div class="mt-4">
                    <a href="{{ url('/') }}" class="btn-portal">
                        <i class="fa-solid fa-house me-2"></i>الرجوع إلى البوابة الرسمية
                    </a>
                </div>
            </div>
        @else
            <div class="verify-header py-5">
                <div class="text-danger mb-3" style="font-size: 3.5rem;">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                
                <div class="status-badge failed">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>فشل التحقق</span>
                </div>

                <h5 class="fw-bold text-white mb-2">بطاقة غير صالحة أو ملغية</h5>
                <p class="text-secondary small px-4 mb-4">{{ $message }}</p>

                <div class="px-4">
                    <a href="{{ url('/') }}" class="btn-portal">
                        <i class="fa-solid fa-house me-2"></i>الرجوع إلى البوابة الرسمية
                    </a>
                </div>
            </div>
        @endif

    </div>

</body>
</html>
