@extends('layouts.print')
@section('title', $title ?? 'طباعة الشهادة')
@section('content')
<?php
/** @var array $d */
$d = $d ?? [];
$settings = \App\Helpers\TakwinHelper::getSettings();

// Check if this is a BEP certificate (Vocational Education)
$isBEP = (str_contains(strtolower($d['type_diplome_fr'] ?? ''), 'brevet d\'enseignement professionnel') 
       || str_contains(strtolower($d['type_diplome_fr'] ?? ''), 'enseignement professionnel')
       || str_contains($d['type_diplome_ar'] ?? '', 'التعليم المهني')
       || (isset($d['niveau_qualification']) && str_contains($d['niveau_qualification'], 'التعليم المهني'))
);
?>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    @page { size: A4 landscape; margin: 0; }
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        background: #dde4ec;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 40px 0;
        font-family: 'Cairo', sans-serif;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ═══ PREVIEW SCALING ══════════════════════════════════════════════ */
    .diploma-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 1200px;
        padding: 20px;
        overflow: visible;
    }

    .diploma-scale-container {
        width: 297mm;
        height: 210mm;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
    }

    /* ═══ CANVAS ═══════════════════════════════════════════════════════ */
    .diploma-card {
        width: 297mm;
        height: 210mm;
        background: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 36px rgba(0,0,0,0.15);
        transform-origin: center center;
        flex-shrink: 0;
    }

    /* ═══ WATERMARK (CRESCENT & STAR) ══════════════════════════════════ */
    .diploma-watermark {
        position: absolute;
        top: 55%;
        left: 50%;
        width: 80mm;
        height: 80mm;
        transform: translate(-50%, -50%);
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        opacity: 0.07;
        z-index: 2;
        pointer-events: none;
    }

    .diploma-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        pointer-events: none;
    }

    /* ═══ CSS SOLID BORDERS (ALWAYS PRINT SHARP) ═══════════════════════ */
    .diploma-border-outer {
        position: absolute;
        inset: 6mm;
        border: 0.8mm solid #0f2d59;
        pointer-events: none;
        z-index: 3;
    }

    .diploma-border-inner {
        position: absolute;
        inset: 7.5mm;
        border: 1.6mm solid #0f2d59;
        pointer-events: none;
        z-index: 3;
    }

    /* ═══ HEADER — absolute columns, fully RTL-proof ═══════════════════ */
    .diploma-header {
        position: absolute;
        top: 10mm;
        left: 15mm;
        right: 15mm;
        height: 25mm;
        z-index: 10;
    }

    /* LEFT: serial number */
    .hdr-serial {
        position: absolute;
        left: 0; top: 2mm;
        width: 75mm;
        font-family: 'Cairo', 'Arial', sans-serif;
        font-size: 9.5pt;
        font-weight: 700;
        color: #000;
        direction: ltr;
        text-align: left;
        line-height: 1.4;
    }

    /* CENTER: republic + ministry */
    .hdr-center {
        position: absolute;
        left: 75mm;
        right: 85mm;
        top: 0;
        text-align: center;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        color: #000;
        line-height: 1.4;
    }
    .hdr-center .r1 { font-size: 11.5pt; font-weight: 700; }
    .hdr-center .r2 { font-size: 10.5pt; font-weight: 700; }

    /* RIGHT: directorate + institution */
    .hdr-right {
        position: absolute;
        right: 0; top: 2mm;
        width: 85mm;
        text-align: right;
        direction: rtl;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        color: #000;
        line-height: 1.4;
    }
    .hdr-right .r1 { font-size: 10.5pt; font-weight: 700; }
    .hdr-right .r2 { font-size: 9.5pt; font-weight: 700; }
    .diploma-border-outer {
        position: absolute;
        inset: 6mm;
        border: 0.8mm solid #1b44b3;
        pointer-events: none;
        z-index: 3;
    }

    .diploma-border-inner {
        position: absolute;
        inset: 7.5mm;
        border: 1.6mm solid #1b44b3;
        pointer-events: none;
        z-index: 3;
    }

    /* ═══ HEADER — absolute columns, fully RTL-proof ═══════════════════ */
    .diploma-header {
        position: absolute;
        top: 10mm;
        left: 15mm;
        right: 15mm;
        height: 25mm;
        z-index: 10;
    }

    /* LEFT: serial number */
    .hdr-serial {
        position: absolute;
        left: 0; top: 2mm;
        width: 75mm;
        font-family: 'Cairo', 'Arial', sans-serif;
        font-size: 9.5pt;
        font-weight: 700;
        color: #000;
        direction: ltr;
        text-align: left;
        line-height: 1.4;
    }
    .serial-number {
        border-bottom: 1.5px dotted #000;
        padding: 0 4px;
        display: inline-block;
        min-width: 38mm;
        text-align: center;
    }
    .serial-label {
        margin-left: 6px;
    }

    /* CENTER: republic + ministry */
    .hdr-center {
        position: absolute;
        left: 75mm;
        right: 85mm;
        top: 0;
        text-align: center;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        color: #000;
        line-height: 1.4;
    }
    .hdr-center .r1 { font-size: 11.5pt; font-weight: 700; }
    .hdr-center .r2 { font-size: 10.5pt; font-weight: 700; }

    /* RIGHT: directorate + institution */
    .hdr-right {
        position: absolute;
        right: 0; top: 2mm;
        width: 85mm;
        text-align: right;
        direction: rtl;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        color: #000;
        line-height: 1.4;
    }
    .hdr-right .r1 { font-size: 10.5pt; font-weight: 700; }
    .hdr-right .r2 { font-size: 9.5pt; font-weight: 700; }

    /* ═══ TITLE ══════════════════════════════════════════════════════════ */
    .main-title {
        position: absolute;
        top: 36mm;
        left: 0; right: 0;
        text-align: center;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        font-size: 52pt;
        font-weight: bold;
        color: #1b44b3;
        line-height: 1.0;
        z-index: 10;
    }

    /* ═══ ARABIC PREAMBLE ══════════════════════════════════════════════ */
    .arabic-preamble {
        position: absolute;
        top: 61mm;
        right: 15mm;
        width: 184mm;
        z-index: 10;
        direction: rtl;
        text-align: right;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        color: #000;
    }

    .preamble-head {
        font-size: 11pt;
        font-weight: bold;
        margin-bottom: 2mm;
    }

    .preamble-body {
        font-size: 10.5pt;
        line-height: 1.8;
    }

    /* ═══ BIOGRAPHICAL DETAILS (Millimeter Precision Alignment) ════════ */
    .bio-line-ar-1, .bio-line-ar-2, .bio-line-ar-3 {
        position: absolute;
        right: 15mm;
        width: 184mm;
        z-index: 10;
        direction: rtl;
        text-align: right;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.2;
    }
    .bio-line-ar-1 strong, .bio-line-ar-2 strong, .bio-line-ar-3 strong {
        font-weight: 700;
    }

    .bio-line-fr-1, .bio-line-fr-2, .bio-line-fr-3, .bio-line-fr-4, .bio-line-fr-5 {
        position: absolute;
        left: 15mm;
        width: 84mm;
        z-index: 10;
        direction: ltr;
        text-align: left;
        font-family: 'Outfit', 'Arial', sans-serif;
        font-size: 8.5pt;
        color: #000;
        line-height: 1.2;
    }
    .bio-line-fr-1 strong, .bio-line-fr-2 strong, .bio-line-fr-3 strong, .bio-line-fr-4 strong, .bio-line-fr-5 strong {
        font-weight: 700;
    }

    /* Exact Line-by-Line Vertical Offsets */
    .bio-line-ar-1, .bio-line-fr-1 { top: <?= $isBEP ? '84mm' : '96mm' ?>; }
    .bio-line-ar-2, .bio-line-fr-2 { top: <?= $isBEP ? '94mm' : '106mm' ?>; }
    .bio-line-ar-3, .bio-line-fr-3 { top: <?= $isBEP ? '104mm' : '116mm' ?>; }
    .bio-line-fr-4 { top: <?= $isBEP ? '114mm' : '126mm' ?>; }
    .bio-line-fr-5 { top: <?= $isBEP ? '124mm' : '136mm' ?>; }

    /* ═══ QR CODE CONTAINER ════════════════════════════════════════════ */
    .qr-absolute-container {
        position: absolute;
        top: 152mm;
        left: 15mm;
        width: 22mm;
        z-index: 10;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .qr-img {
        width: 22mm;
        height: 22mm;
        display: block;
        margin-bottom: 2mm;
    }
    
    .matricule-txt {
        font-family: 'Outfit', 'Arial', sans-serif;
        font-size: 7.5pt;
        font-weight: 700;
        color: #000;
        width: 35mm;
        text-align: center;
        white-space: nowrap;
    }

    /* ═══ SIGNATURES ═══════════════════════════════════════════════════ */
    .sig-right {
        position: absolute;
        top: 152mm;
        right: 15mm;
        width: 75mm;
        text-align: center;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        font-size: 11pt;
        font-weight: bold;
        color: #000;
        direction: rtl;
        z-index: 10;
    }

    .sig-left {
        position: absolute;
        top: 152mm;
        left: 107.5mm; /* Centers the signature horizontally in the center-left space */
        width: 75mm;
        text-align: center;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        font-size: 11pt;
        font-weight: bold;
        color: #000;
        direction: rtl;
        z-index: 10;
    }

    .sig-space {
        height: 20mm;
    }

    /* BOTTOM NOTICE */
    .bottom-notice {
        position: absolute;
        bottom: 9mm;
        right: 15mm;
        font-family: 'Amiri', 'Traditional Arabic', serif;
        font-size: 9.5pt;
        font-weight: bold;
        color: #000;
        z-index: 10;
        direction: rtl;
    }

    /* ═══ PRINT TOOLBAR (screen only) ═══════════════════════════════════ */
    .print-toolbar {
        position: fixed;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        background: rgba(255,255,255,0.96);
        backdrop-filter: blur(12px);
        padding: 9px 28px;
        border-radius: 50px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.15);
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .btn-print {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        color: #fff; border: none;
        padding: 9px 22px; border-radius: 50px;
        font-family: 'Cairo', sans-serif; font-weight: 700; font-size: 0.88rem;
        cursor: pointer;
    }
    .btn-close {
        background: #64748b; color: #fff; border: none;
        padding: 9px 22px; border-radius: 50px;
        font-family: 'Cairo', sans-serif; font-weight: 700; font-size: 0.88rem;
        cursor: pointer;
    }

    @media print {
        html, body, main {
            width: 297mm !important; height: 210mm !important;
            overflow: hidden !important; margin: 0 !important; padding: 0 !important;
            background: #fff !important;
        }
        .print-toolbar { display: none !important; }
        body { background: #fff !important; padding: 0 !important; }
        .diploma-wrapper { padding: 0 !important; margin: 0 !important; width: 297mm !important; height: 210mm !important; }
        .diploma-scale-container { width: 297mm !important; height: 210mm !important; }
        .diploma-card { box-shadow: none !important; margin: 0 !important; transform: none !important; }
    }
</style>

<!-- Toolbar -->
<div class="print-toolbar">
    <button class="btn-print" onclick="window.print()">🖨️ طباعة الشهادة الرسمية</button>
    <button class="btn-close" onclick="window.close()">✕ إغلاق</button>
</div>

<div class="diploma-wrapper">
    <div class="diploma-scale-container">
        <div class="diploma-card">

            <!-- CSS Borders (always printed) -->
            <div class="diploma-border-outer"></div>
            <div class="diploma-border-inner"></div>

            <!-- Background Wave & Honeycomb SVG -->
            <img class="diploma-bg"
                 src="<?= !empty($settings['diploma_bg_url']) ? htmlspecialchars($settings['diploma_bg_url']) : asset('assets/images/diploma_bg.svg') ?>"
                 alt="">

            <!-- Emblem Watermark (Crescent & Star) -->
            <div class="diploma-watermark" style="background-image: url('<?= htmlspecialchars($settings['diploma_watermark_url'] ?? 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Emblem_of_Algeria.svg') ?>')"></div>

            <!-- ── HEADER ──────────────────────────────────────────── -->
            <div class="diploma-header">
                <!-- LEFT: Serial -->
                <div class="hdr-serial">
                    <span class="serial-number"><?= htmlspecialchars($d['num_serie'] ?? '') ?></span>
                    <span class="serial-label">الرقم التسلسلي</span>
                </div>

                <!-- CENTER: Republic -->
                <div class="hdr-center">
                    <div class="r1">الجمهورية الجزائرية الديمقراطية الشعبية</div>
                    <div class="r2">وزارة التكوين و التعليم المهنيين</div>
                </div>

                <!-- RIGHT: Directorate + Institution -->
                <div class="hdr-right">
                    <div class="r1">مديرية التكوين و التعليم المهنيين لولاية <?= htmlspecialchars($d['wilaya_ar'] ?? '') ?></div>
                    <div class="r2"><?= htmlspecialchars($d['etab_ar'] ?? '') ?></div>
                </div>
            </div>

            <!-- ── TITLE ───────────────────────────────────────────── -->
            @if (!$isBEP)
                <div class="main-title"><?= htmlspecialchars($d['type_diplome_ar'] ?? 'شهادة تقني سام') ?></div>
            @else
                <div class="main-title-bep-spacer" style="height: 48px;"></div>
            @endif

            <!-- ── ARABIC PREAMBLE ──────────────────────── -->
            <div class="arabic-preamble" style="<?= $isBEP ? 'top: 50mm;' : '' ?>">
                <div class="preamble-head">إن وزير التكوين و التعليم المهنيين</div>
                <div class="preamble-body">
                    @if ($isBEP)
                        بمقتضى المرسوم التنفيذي رقم 17-212 المؤرخ في 26 شوال 1438 الموافق 20 يوليو 2017، الذي يحدد كيفيات إحداث الشهادات المتوجة لأطوار التعليم المهني<br>
                        بمقتضى القرار الوزاري رقم 102 المؤرخ 8 جمادى الآخرة عام 1442 الموافق 31 جانفي سنة 2021، الذي يحدد شروط وكيفيات تنظيم و تسليم الشهادات المتوجة لأطوار التعليم المهني و كذا نماذجها<br>
                        بناءا على محضر لجنة المداولات رقم : <?= htmlspecialchars($d['num_deliberation'] ?? '1') ?> المؤرخ في : <?= htmlspecialchars($d['date_deliberation_ar'] ?? '') ?>
                    @else
                        بمقتضى المرسوم التنفيذي رقم 16-282 المؤرخ في 2 صفر عام 1438 الموافق لـ 2 نوفمبر 2016 والذي يحدد نظام التكوين المهني الأولي والشهادات المتوجة له<br>
                        بمقتضى القرار المؤرخ في 23 ربيع الأول عام 1439 الموافق لـ 12 ديسمبر 2017 الذي يحدد شروط وكيفيات تسليم الشهادات المتوجة للتكوين المهني الأولي<br>
                        بناءا على محضر لجنة مداولات نهاية التكوين رقم : <?= htmlspecialchars($d['num_deliberation'] ?? '31') ?> المؤرخ في : <?= htmlspecialchars($d['date_deliberation_ar'] ?? '') ?>
                    @endif
                </div>
            </div>

            <!-- ── ARABIC BIOGRAPHICAL DETAILS ──────────────────────── -->
            <div class="bio-line-ar-1">
                تمنح هذه الشهادة للسيد(ة) : <strong><?= htmlspecialchars(($d['nom_ar'] ?? '') . ' ' . ($d['prenom_ar'] ?? '')) ?></strong> المولود(ة) بتاريخ : <strong><?= htmlspecialchars($d['date_naissance_ar'] ?? '') ?></strong> بـ <strong><?= htmlspecialchars($d['lieu_naissance'] ?? '') ?></strong>
            </div>

            <div class="bio-line-ar-2">
                التخصص : <strong><?= htmlspecialchars($d['spec_ar'] ?? '') ?></strong> &nbsp;&nbsp;&nbsp; مستوى التأهيل : <strong><?= htmlspecialchars($d['niveau_qualification'] ?? 'الخامس') ?></strong> &nbsp;&nbsp;&nbsp; بتقدير : <strong><?= htmlspecialchars($d['mention_ar'] ?? '') ?></strong>
            </div>

            <div class="bio-line-ar-3">
                <?php
                    $city = '';
                    if (!empty($d['etab_ar'])) {
                        if (str_contains($d['etab_ar'], 'العلمة')) {
                            $city = 'العلمة_سطيف';
                        } else {
                            $city = $d['wilaya_ar'] ?? '';
                        }
                    } else {
                        $city = $d['wilaya_ar'] ?? '';
                    }
                ?>
                حرر بـ : <strong><?= htmlspecialchars($city) ?></strong> في : <strong><?= htmlspecialchars($d['date_emission_ar'] ?? '') ?></strong>
            </div>

            <!-- ── FRENCH BIOGRAPHICAL DETAILS ───────────────── -->
            <div class="bio-line-fr-1">
                Nom : <strong><?= htmlspecialchars(strtoupper($d['nom_fr'] ?? '')) ?></strong>
            </div>
            <div class="bio-line-fr-2">
                Prénom : <strong><?= htmlspecialchars(ucfirst($d['prenom_fr'] ?? '')) ?></strong>
            </div>
            <div class="bio-line-fr-3">
                Date et Lieu de naissance : <strong><?= htmlspecialchars($d['date_naissance_fr'] ?? '') ?> &nbsp; <?= htmlspecialchars(strtoupper($d['lieu_naissance_fr'] ?? '')) ?></strong>
            </div>
            <div class="bio-line-fr-4">
                Diplôme : <strong><?= htmlspecialchars($d['type_diplome_fr'] ?? 'Brevet de technicien supérieur') ?></strong>
            </div>
            <div class="bio-line-fr-5">
                Spécialité : <strong><?= htmlspecialchars($d['spec_fr'] ?? '') ?></strong>
            </div>

            <!-- ── QR CODE (left column bottom) ───────────────── -->
            <div class="qr-absolute-container">
                <img class="qr-img"
                     src="https://api.qrserver.com/v1/create-qr-code/?size=88x88&data=<?= urlencode(url('/resultats?matricule=' . ($d['numero_matricule'] ?? ''))) ?>"
                     alt="QR">
                <div class="matricule-txt"><?= htmlspecialchars($d['numero_matricule'] ?? '') ?></div>
            </div>

            <!-- ── SIGNATURES (bottom row) ────────────────────────── -->
            <div class="sig-right">
                <div class="sig-title">المسؤول (ة) البيداغوجي (ة)</div>
                <div class="sig-space"></div>
            </div>

            <div class="sig-left">
                <div class="sig-title">مدير (ة) المؤسسة</div>
                <div class="sig-space"></div>
            </div>

            <!-- ── BOTTOM NOTICE ───────────────────────────────────── -->
            <div class="bottom-notice">لا تسلم إلا نسخة واحدة من هذه الشهادة</div>

        </div>
    </div>
</div>

<script>
    function adjustScale() {
        const card = document.querySelector('.diploma-card');
        const container = document.querySelector('.diploma-wrapper');
        if (!card || !container) return;
        
        const targetWidth = 1122; // 297mm at 96dpi
        const targetHeight = 794; // 210mm at 96dpi
        
        const viewportWidth = window.innerWidth - 40;
        const viewportHeight = window.innerHeight - 100; // Leave space for margins
        
        let scale = Math.min(viewportWidth / targetWidth, viewportHeight / targetHeight);
        if (scale > 1) scale = 1;
        
        card.style.transform = `scale(${scale})`;
    }
    window.addEventListener('resize', adjustScale);
    window.addEventListener('load', adjustScale);
    
    window.onload = function () {
        adjustScale();
        setTimeout(function () { 
            if (window.location.search.indexOf('noprint') === -1) {
                window.print(); 
            }
        }, 1200);
    };
</script>
@endsection
