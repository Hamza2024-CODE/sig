@extends('layouts.print')
@section('title', $title ?? 'طباعة جماعية للشهادات')
@section('content')
<?php
/** @var array $diplomas */
/** @var int   $count   */
$settings = \App\Helpers\TakwinHelper::getSettings();

// Load the 38 background images dynamically from public/diplom
$diplomImages = glob(str_replace('\\', '/', public_path('diplom/*.{jpeg,jpg,png}')), GLOB_BRACE);
$imagesList = array_map('basename', $diplomImages);
sort($imagesList);


if (!function_exists('cleanFrenchText')) {
    function cleanFrenchText($text) {
        if (empty($text)) return '';
        // Map common broken sequences
        $text = str_replace(
            ['Option-á:', 'Option-â:', 'Option-ã:', 'Option-ä:', 'Option-à:', 'Option-æ:', 'Option-¦:', 'Option-:R', 'Option-: r'],
            'Option :',
            $text
        );
        $text = str_replace(
            ['R-¦seaux', 'R-seaux', 'R-¦seaux', 'R-Â¦seaux', 'R-┬«seaux', 'R-┬«seaux', 'R-¬seaux'],
            'Réseaux',
            $text
        );
        $text = preg_replace('/Option[-–\s]*[^:]*:/i', 'Option :', $text);
        return $text;
    }
}
?>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    /* ═══ PAGE SETUP ══════════════════════════════════════════════════════ */
    @page { size: A4 landscape; margin: 0; }
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        background: #dde4ec;
        font-family: 'Cairo', sans-serif;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ═══ TOOLBAR (hidden on print) ══════════════════════════════════════ */
    .batch-toolbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 9999;
        background: linear-gradient(135deg, #1e1b4b 0%, #4a154b 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 24px;
        gap: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .batch-toolbar .toolbar-info {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        font-weight: 700;
        color: #f8fafc;
    }
    .batch-toolbar .toolbar-info .badge-count {
        background: rgba(255,255,255,0.15);
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.85rem;
        font-family: 'Outfit', sans-serif;
        color: #fff;
    }
    .batch-toolbar .toolbar-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .btn-batch-print {
        background: #1d4ed8;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-size: 0.85rem;
        font-weight: 700;
        font-family: 'Cairo', sans-serif;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(29, 78, 216, 0.3);
    }
    .btn-batch-print:hover {
        background: #1e40af;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(29, 78, 216, 0.4);
    }
    .btn-batch-close {
        background: #334155;
        color: #f8fafc;
        border: 1px solid #475569;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 0.85rem;
        font-weight: 700;
        font-family: 'Cairo', sans-serif;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-batch-close:hover {
        background: #475569;
        color: #fff;
    }
    .batch-toolbar label {
        color: #94a3b8 !important;
        font-size: 0.85rem;
        font-weight: 700;
        font-family: 'Cairo', sans-serif;
    }
    .batch-toolbar select {
        background: #1e293b;
        color: #f8fafc;
        border: 1px solid #334155;
        padding: 6px 16px 6px 36px;
        border-radius: 8px;
        font-family: 'Cairo', sans-serif;
        font-size: 0.85rem;
        outline: none;
        cursor: pointer;
        transition: all 0.2s;
        appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
        background-repeat: no-repeat;
        background-position: left 10px center;
        background-size: 14px;
    }
    .batch-toolbar select:hover {
        border-color: #475569;
        background-color: #334155;
    }

    /* PDF Dropdown menu styling */
    .dropdown-pdf {
        position: relative;
        display: inline-block;
    }
    .dropdown-pdf-menu {
        display: none;
        position: absolute;
        top: 130%;
        left: 0;
        background: #1e293b;
        border: 1px solid #334155;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        border-radius: 8px;
        width: 220px;
        z-index: 9999;
        padding: 6px 0;
    }
    .dropdown-pdf-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 600;
        text-align: right;
        transition: all 0.2s;
    }
    .dropdown-pdf-menu a:hover {
        background: #334155;
        color: #fff;
    }
    .dropdown-pdf-menu a:not(:last-child) {
        border-bottom: 1px solid #334155;
    }
    .btn-pdf-trigger {
        background: #065f46;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-family: 'Cairo', sans-serif;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(6, 95, 70, 0.3);
    }
    .btn-pdf-trigger:hover {
        background: #047857;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(6, 95, 70, 0.4);
    }

    /* ═══ MAIN CONTENT AREA ══════════════════════════════════════════════ */
    .batch-container {
        padding-top: 70px; /* toolbar height */
        padding-bottom: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 32px;
    }

    /* ═══ DIPLOMA WRAPPER (each card) ════════════════════════════════════ */
    .diploma-page {
        width: 297mm;
        height: 210mm;
        background: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 36px rgba(0,0,0,0.15);
        page-break-after: always;
        break-after: page;
    }
    .diploma-page:last-of-type {
        page-break-after: avoid;
        break-after: avoid;
    }

    /* ═══ WATERMARK ═══════════════════════════════════════════════════════ */
    .diploma-watermark {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 200px; height: 200px;
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        opacity: 0.06;
        pointer-events: none;
    }

    /* ═══ BACKGROUND IMAGE ════════════════════════════════════════════════ */
    .diploma-bg {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        opacity: 0.08;
        pointer-events: none;
    }
    .diploma-bg.jpeg-bg {
        opacity: 1 !important;
        object-fit: fill !important;
        background: #fff;
    }
    body.no-backgrounds .diploma-bg,
    body.no-backgrounds .diploma-watermark,
    body.no-backgrounds .diploma-border-outer,
    body.no-backgrounds .diploma-border-inner {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }

    /* ═══ BORDERS ═════════════════════════════════════════════════════════ */
    .diploma-border-outer {
        position: absolute;
        top: 8px; right: 8px; bottom: 8px; left: 8px;
        border: 3px solid #1e3a5f;
        border-radius: 4px;
        pointer-events: none;
    }
    .diploma-border-inner {
        position: absolute;
        top: 14px; right: 14px; bottom: 14px; left: 14px;
        border: 1px solid #1e3a5f;
        border-radius: 2px;
        opacity: 0.5;
        pointer-events: none;
    }

    /* ═══ HEADER ══════════════════════════════════════════════════════════ */
    .diploma-header {
        position: absolute;
        top: 22px; left: 22px; right: 22px;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: start;
        gap: 8px;
        direction: rtl;
    }
    .hdr-serial { text-align: right; }
    .serial-number { font-family: 'Outfit', sans-serif; font-size: 0.7rem; font-weight: 700; color: #1e3a5f; display: block; }
    .serial-label  { font-size: 0.55rem; color: #64748b; display: block; }
    .hdr-center { text-align: center; }
    .hdr-center .r1 { font-size: 0.72rem; font-weight: 700; color: #1e3a5f; margin-bottom: 2px; }
    .hdr-center .r2 { font-size: 0.68rem; font-weight: 600; color: #1e3a5f; }
    .hdr-right { text-align: left; }
    .hdr-right .r1 { font-size: 0.65rem; color: #334155; margin-bottom: 2px; }
    .hdr-right .r2 { font-size: 0.68rem; font-weight: 700; color: #1e3a5f; }

    /* ═══ MAIN TITLE ══════════════════════════════════════════════════════ */
    .main-title {
        position: absolute;
        top: 68px; left: 50%; transform: translateX(-50%);
        font-family: 'Amiri', serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e3a5f;
        white-space: nowrap;
        letter-spacing: 1px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.08);
    }

    /* ═══ PREAMBLE ════════════════════════════════════════════════════════ */
    .arabic-preamble {
        position: absolute;
        top: 100px; left: 22px; right: 22px;
        direction: rtl;
        text-align: justify;
    }
    .preamble-head { font-size: 0.72rem; font-weight: 700; color: #1e3a5f; margin-bottom: 4px; text-align: center; }
    .preamble-body { font-size: 0.6rem; color: #334155; line-height: 1.5; }

    /* ═══ BIOGRAPHICAL LINES (Arabic) ════════════════════════════════════ */
    .bio-line-ar-1,
    .bio-line-ar-2,
    .bio-line-ar-3 {
        position: absolute;
        right: 22px; left: 22px;
        direction: rtl;
        text-align: justify;
        font-size: 0.78rem;
        color: #1e293b;
        line-height: 1.6;
    }
    .bio-line-ar-1 { top: 148px; }
    .bio-line-ar-2 { top: 168px; }
    .bio-line-ar-3 { top: 188px; }

    /* ═══ FRENCH DETAILS (right column) ══════════════════════════════════ */
    .bio-line-fr-1, .bio-line-fr-2, .bio-line-fr-3,
    .bio-line-fr-4, .bio-line-fr-5 {
        position: absolute;
        left: 22px;
        font-size: 0.65rem;
        color: #334155;
        line-height: 1.5;
    }
    .bio-line-fr-1 { top: 148px; }
    .bio-line-fr-2 { top: 162px; }
    .bio-line-fr-3 { top: 176px; }
    .bio-line-fr-4 { top: 190px; }

    /* ═══ QR CODE ═════════════════════════════════════════════════════════ */
    .qr-absolute-container {
        position: absolute;
        bottom: 30px; left: 22px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }
    .qr-img { width: 54px; height: 54px; }
    .matricule-txt { font-family: 'Outfit', sans-serif; font-size: 0.5rem; color: #64748b; }

    /* ═══ BEP (Vocational Education) Specific Styling Overrides ════════ */
    .diploma-page.is-bep .main-title { visibility: hidden; height: 10px; }
    .diploma-page.is-bep .arabic-preamble { top: 68px; }

    /* ═══ SIGNATURES ══════════════════════════════════════════════════════ */
    .sig-right {
        position: absolute;
        bottom: 24px; right: 50px;
        text-align: center;
        direction: rtl;
    }
    .sig-left {
        position: absolute;
        bottom: 24px; left: 50px;
        text-align: center;
    }
    .sig-title { font-size: 0.62rem; font-weight: 700; color: #1e3a5f; margin-bottom: 2px; }
    .sig-space  { height: 28px; border-bottom: 1px solid #94a3b8; width: 100px; margin: 0 auto; }

    /* ═══ BOTTOM NOTICE ═══════════════════════════════════════════════════ */
    .bottom-notice {
        position: absolute;
        bottom: 18px; left: 50%; transform: translateX(-50%);
        font-size: 0.55rem;
        color: #94a3b8;
        white-space: nowrap;
    }

    /* ═══ PRINT OVERRIDES ═════════════════════════════════════════════════ */
    body.no-backgrounds .diploma-bg,
    body.no-backgrounds .diploma-watermark,
    body.no-backgrounds .diploma-border-outer,
    body.no-backgrounds .diploma-border-inner {
        display: none !important;
    }

    @media print {
        .batch-toolbar { display: none !important; }
        body { background: #fff !important; padding: 0 !important; }
        .batch-container { padding-top: 0 !important; gap: 0 !important; }
        .diploma-page {
            box-shadow: none !important;
            width: 297mm !important;
            height: 210mm !important;
        }
    }
</style>

<!-- ══ TOOLBAR ══════════════════════════════════════════════════════════ -->
<div class="batch-toolbar" style="background:#0f172a; border-bottom:1px solid rgba(255,255,255,0.15); font-family:'Cairo', sans-serif; display: flex; flex-wrap: wrap; height: auto; padding: 12px 24px; gap: 15px; align-items: center;">
    <div class="toolbar-info" style="display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-graduation-cap" style="color:#3b82f6; font-size:1.1rem;"></i>
        <span>طباعة جماعية للشهادات الرسمية</span>
        <span class="badge-count">{{ $count }} شهادة</span>
    </div>
    
    <!-- Background Template Dropdown Select -->
    <div style="display:inline-flex; align-items:center; gap:8px;">
        <label for="template_select">خلفية الشهادة (القالب):</label>
        <select id="template_select" onchange="changeBatchTemplates(this.value)">
            <option value="">خلفية افتراضية (SVG)</option>
            <?php foreach ($imagesList as $imgName): ?>
                <option value="<?= htmlspecialchars($imgName) ?>"><?= htmlspecialchars($imgName) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Background Toggle Select -->
    <div style="display:inline-flex; align-items:center; gap:8px;">
        <label for="bg-toggle">نوع الورق:</label>
        <select id="bg-toggle" onchange="toggleBackgrounds(this.value)">
            <option value="1">ورق عادي (طباعة الخلفية والإطار)</option>
            <option value="0">ورق رسمي مسبق الطباعة (نصوص فقط)</option>
        </select>
    </div>

    <!-- Fine-Tuning Offsets -->
    <div style="display:inline-flex; align-items:center; gap:8px; border-left:1px solid #334155; padding-left:12px;">
        <label>إزاحة النص (أعلى/أسفل):</label>
        <input type="number" id="offset_y" value="0" step="0.5" oninput="adjustBatchYOffset(this.value)" style="width: 55px; background: #1e293b; color: #fff; border: 1px solid #334155; border-radius: 6px; padding: 4px; text-align: center; outline:none;">
        <span style="font-size: 11px; color: #94a3b8;">مم</span>
    </div>

    <div style="display:inline-flex; align-items:center; gap:8px;">
        <label>يمين/يسار:</label>
        <input type="number" id="offset_x" value="0" step="0.5" oninput="adjustBatchXOffset(this.value)" style="width: 55px; background: #1e293b; color: #fff; border: 1px solid #334155; border-radius: 6px; padding: 4px; text-align: center; outline:none;">
        <span style="font-size: 11px; color: #94a3b8;">مم</span>
    </div>

    <div style="display:inline-flex; align-items:center; gap:8px; border-left:1px solid #334155; padding-left:12px;">
        <label>حجم الخط:</label>
        <input type="number" id="font_size_scale" value="100" min="50" max="150" step="1" oninput="adjustBatchFontSize(this.value)" style="width: 55px; background: #1e293b; color: #fff; border: 1px solid #334155; border-radius: 6px; padding: 4px; text-align: center; outline:none;">
        <span style="font-size: 11px; color: #94a3b8;">%</span>
    </div>

    <div style="display:inline-flex; align-items:center; gap:8px;">
        <input type="checkbox" id="toggle_borders" checked onchange="toggleBatchBorders(this.checked)" style="cursor:pointer;">
        <label for="toggle_borders" style="cursor:pointer;">الإطار الافتراضي</label>
    </div>

    <?php
    $diplomaIds = implode(',', array_map(function($d) { return $d['diplome_id']; }, $diplomas));
    ?>
    <div class="toolbar-actions" style="margin-right: auto; display: flex; gap: 8px;">
        <!-- PDF Download Dropdown -->
        <div class="dropdown-pdf">
            <button class="btn-pdf-trigger" onclick="togglePdfMenu(event)">
                <i class="fa-solid fa-file-pdf"></i> تحميل كـ PDF <i class="fa-solid fa-chevron-down ms-1" style="font-size:0.7rem;"></i>
            </button>
            <div id="pdf-menu" class="dropdown-pdf-menu">
                <a href="{{ route('diplomes.download.pdf.batch') }}?ids={{ $diplomaIds }}&background=1">
                    <i class="fa-solid fa-image"></i> تحميل بالخلفية والإطار
                </a>
                <a href="{{ route('diplomes.download.pdf.batch') }}?ids={{ $diplomaIds }}&background=0">
                    <i class="fa-solid fa-align-justify"></i> تحميل بدون خلفية (للطباعة)
                </a>
            </div>
        </div>

        <button class="btn-batch-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> طباعة الكل (Ctrl+P)
        </button>
        <button class="btn-batch-close" onclick="window.close()">
            <i class="fa-solid fa-xmark"></i> إغلاق
        </button>
    </div>
</div>

<!-- ══ DIPLOMAS ══════════════════════════════════════════════════════════ -->
<div class="batch-container">
@foreach ($diplomas as $d)
<?php
$isBEP = (str_contains(strtolower($d['type_diplome_fr'] ?? ''), 'brevet d\'enseignement professionnel') 
       || str_contains(strtolower($d['type_diplome_fr'] ?? ''), 'enseignement professionnel')
       || str_contains($d['type_diplome_ar'] ?? '', 'التعليم المهني')
       || (isset($d['niveau_qualification']) && str_contains($d['niveau_qualification'], 'التعليم المهني'))
);
?>
<div class="diploma-page <?= $isBEP ? 'is-bep' : '' ?>" data-qualif-id="<?= htmlspecialchars($d['qualif_id'] ?? 0) ?>">

    <!-- Background & Watermark -->
    <img class="diploma-bg"
         src="<?= !empty($settings['diploma_bg_url']) ? htmlspecialchars($settings['diploma_bg_url']) : asset('assets/images/diploma_bg.svg') ?>"
         alt="">
    <div class="diploma-watermark"
         style="background-image: url('<?= htmlspecialchars($settings['diploma_watermark_url'] ?? 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Emblem_of_Algeria.svg') ?>')">
    </div>

    <!-- Borders -->
    <div class="diploma-border-outer"></div>
    <div class="diploma-border-inner"></div>

    <!-- Wrap all printable elements in a translation/scale wrapper -->
    <div class="diploma-printable-content" style="position: absolute; inset: 0; z-index: 10; transition: transform 0.1s ease-out, font-size 0.1s ease-out; transform-origin: top center;">
        <!-- HEADER -->
        <div class="diploma-header">
            <div class="hdr-serial">
                @if ($isBEP)
                    <div style="direction: rtl; text-align: left;">
                        <span class="serial-number" style="border-bottom: 1px dotted #000; min-width: 30mm; display: inline-block; text-align: center; font-family: 'Outfit';"><?= htmlspecialchars($d['num_serie'] ?? '') ?></span>
                        <span class="serial-label">الرقم التسلسلي</span>
                    </div>
                @else
                    <span class="serial-number"><?= htmlspecialchars($d['num_serie'] ?? '') ?></span>
                    <span class="serial-label">الرقم التسلسلي</span>
        تمنح هذه الشهادة للسيد(ة) : <strong><?= htmlspecialchars(($d['nom_ar'] ?? '') . ' ' . ($d['prenom_ar'] ?? '')) ?></strong>
        المولود(ة) بتاريخ : <strong><?= htmlspecialchars($d['date_naissance_ar'] ?? '') ?></strong>
        بـ <strong><?= htmlspecialchars($d['lieu_naissance'] ?? '') ?></strong>
    </div>
    <div class="bio-line-ar-2">
        التخصص : <strong><?= htmlspecialchars($d['spec_ar'] ?? '') ?></strong>
        &nbsp;&nbsp;&nbsp; مستوى التأهيل : <strong><?= htmlspecialchars($d['niveau_qualification'] ?? 'الخامس') ?></strong>
        &nbsp;&nbsp;&nbsp; بتقدير : <strong><?= htmlspecialchars($d['mention_ar'] ?? '') ?></strong>
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
        حرر بـ : <strong><?= htmlspecialchars($city) ?></strong>
        في : <strong><?= htmlspecialchars($d['date_emission_ar'] ?? '') ?></strong>
    </div>

    <!-- FRENCH BIOGRAPHICAL DETAILS -->
    <div class="bio-line-fr-1">Nom : <strong><?= htmlspecialchars(strtoupper($d['nom_fr'] ?? '')) ?></strong></div>
    <div class="bio-line-fr-2">Prénom : <strong><?= htmlspecialchars(ucfirst($d['prenom_fr'] ?? '')) ?></strong></div>
    <div class="bio-line-fr-3">Date et Lieu de naissance : <strong><?= htmlspecialchars($d['date_naissance_fr'] ?? '') ?> <?= htmlspecialchars(strtoupper($d['lieu_naissance_fr'] ?? '')) ?></strong></div>
    <div class="bio-line-fr-4">Diplôme : <strong><?= htmlspecialchars($d['type_diplome_fr'] ?? '') ?></strong></div>
    <div class="bio-line-fr-5">Spécialité : <strong><?= htmlspecialchars(cleanFrenchText($d['spec_fr'] ?? '')) ?></strong></div>

    <!-- QR CODE -->
    <div class="qr-absolute-container">
        <img class="qr-img"
             src="<?= !empty($d['qr_base64']) ? $d['qr_base64'] : '/api/qrcode?data=' . urlencode(url('/verify-diploma?id=' . $d['diplome_id'])) ?>"
             alt="QR">
        <div class="matricule-txt"><?= htmlspecialchars($d['numero_matricule'] ?? '') ?></div>
    </div>

    <!-- SIGNATURES -->
    <div class="sig-right">
        <div class="sig-title">المسؤول (ة) البيداغوجي (ة)</div>
        <div class="sig-space"></div>
    </div>
    <div class="sig-left">
        <div class="sig-title">مدير (ة) المؤسسة</div>
        <div class="sig-space"></div>
    </div>

    <!-- BOTTOM NOTICE -->
    <div class="bottom-notice">لا تسلم إلا نسخة واحدة من هذه الشهادة</div>

</div>{{-- end .diploma-page --}}
@endforeach
</div>{{-- end .batch-container --}}

<script>
    const defaultBgUrl = <?= json_encode(!empty($settings['diploma_bg_url']) ? htmlspecialchars($settings['diploma_bg_url']) : asset('assets/images/diploma_bg.svg')) ?>;

    window.addEventListener('load', function () {
        // Apply saved localStorage settings to each page individually on load
        document.querySelectorAll('.diploma-page').forEach(function (page) {
            const qualifId = page.getAttribute('data-qualif-id');
            const bgImg = page.querySelector('.diploma-bg');
            const content = page.querySelector('.diploma-printable-content');
            
            if (qualifId) {
                // Load saved background
                const savedBg = localStorage.getItem('diploma_bg_qualif_' + qualifId);
                if (savedBg && bgImg) {
                    bgImg.src = '/diplom/' + savedBg;
                    bgImg.classList.add('jpeg-bg');
                    // Hide borders if using JPEG background template
                    const borderOuter = page.querySelector('.diploma-border-outer');
                    const borderInner = page.querySelector('.diploma-border-inner');
                    if (borderOuter) borderOuter.style.display = 'none';
                    if (borderInner) borderInner.style.display = 'none';
                    document.getElementById('toggle_borders').checked = false;
                }
                
                // Load saved offsets & font scale
                let transform = '';
                const savedOffsetX = localStorage.getItem('diploma_offset_x_' + qualifId);
                const savedOffsetY = localStorage.getItem('diploma_offset_y_' + qualifId);
                const savedFontScale = localStorage.getItem('diploma_font_scale_' + qualifId);
                
                if (savedOffsetX !== null || savedOffsetY !== null) {
                    const x = savedOffsetX !== null ? parseFloat(savedOffsetX) : 0;
                    const y = savedOffsetY !== null ? parseFloat(savedOffsetY) : 0;
                    transform = `translate(${x}mm, ${y}mm)`;
                    
                    // Set inputs to first matched value in toolbar just for user visual reference
                    document.getElementById('offset_x').value = x;
                    document.getElementById('offset_y').value = y;
                }
                
                if (content) {
                    if (transform) content.style.transform = transform;
                    if (savedFontScale !== null) {
                        content.style.fontSize = (parseInt(savedFontScale) / 100) + 'em';
                        document.getElementById('font_size_scale').value = savedFontScale;
                    }
                }
            }
        });

        if (window.location.search.indexOf('noprint') === -1) {
            setTimeout(function () { window.print(); }, 2000);
        }
    });

    function changeBatchTemplates(imgName) {
        document.querySelectorAll('.diploma-page').forEach(function (page) {
            const qualifId = page.getAttribute('data-qualif-id');
            const bgImg = page.querySelector('.diploma-bg');
            if (!bgImg) return;

            if (imgName) {
                bgImg.src = '/diplom/' + imgName;
                bgImg.classList.add('jpeg-bg');
                toggleBatchBorders(false);
                document.getElementById('toggle_borders').checked = false;
                if (qualifId) localStorage.setItem('diploma_bg_qualif_' + qualifId, imgName);
            } else {
                bgImg.src = defaultBgUrl;
                bgImg.classList.remove('jpeg-bg');
                toggleBatchBorders(true);
                document.getElementById('toggle_borders').checked = true;
                if (qualifId) localStorage.removeItem('diploma_bg_qualif_' + qualifId);
            }
        });
    }

    function toggleBackgrounds(val) {
        if (val === "0") {
            document.body.classList.add('no-backgrounds');
        } else {
            document.body.classList.remove('no-backgrounds');
        }
    }

    function toggleBatchBorders(visible) {
        document.querySelectorAll('.diploma-page').forEach(function (page) {
            const borderOuter = page.querySelector('.diploma-border-outer');
            const borderInner = page.querySelector('.diploma-border-inner');
            if (borderOuter) borderOuter.style.display = visible ? '' : 'none';
            if (borderInner) borderInner.style.display = visible ? '' : 'none';
        });
    }

    function adjustBatchYOffset(val) {
        const offset = parseFloat(val) || 0;
        document.querySelectorAll('.diploma-page').forEach(function (page) {
            const qualifId = page.getAttribute('data-qualif-id');
            const content = page.querySelector('.diploma-printable-content');
            if (qualifId) localStorage.setItem('diploma_offset_y_' + qualifId, offset);
            
            // Reapply transform
            const savedOffsetX = localStorage.getItem('diploma_offset_x_' + qualifId) || 0;
            if (content) content.style.transform = `translate(${savedOffsetX}mm, ${offset}mm)`;
        });
    }

    function adjustBatchXOffset(val) {
        const offset = parseFloat(val) || 0;
        document.querySelectorAll('.diploma-page').forEach(function (page) {
            const qualifId = page.getAttribute('data-qualif-id');
            const content = page.querySelector('.diploma-printable-content');
            if (qualifId) localStorage.setItem('diploma_offset_x_' + qualifId, offset);
            
            // Reapply transform
            const savedOffsetY = localStorage.getItem('diploma_offset_y_' + qualifId) || 0;
            if (content) content.style.transform = `translate(${offset}mm, ${savedOffsetY}mm)`;
        });
    }

    function adjustBatchFontSize(val) {
        const scale = parseInt(val) || 100;
        document.querySelectorAll('.diploma-page').forEach(function (page) {
            const qualifId = page.getAttribute('data-qualif-id');
            const content = page.querySelector('.diploma-printable-content');
            if (qualifId) localStorage.setItem('diploma_font_scale_' + qualifId, scale);
            if (content) content.style.fontSize = (scale / 100) + 'em';
        });
    }

    function togglePdfMenu(event) {
        event.stopPropagation();
        var menu = document.getElementById('pdf-menu');
        if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    window.addEventListener('click', function(e) {
        var menu = document.getElementById('pdf-menu');
        if (menu && menu.style.display === 'block') {
            menu.style.display = 'none';
        }
    });
</script>
@endsection
