<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
            <?php if ($background && !empty($bgPath)): ?>
            background-image: url('<?= $bgPath ?>');
            background-image-resize: 6;
            <?php endif; ?>
        }
        body {
            font-family: 'xbriyaz', 'aealarab', 'dejavusans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            direction: rtl;
        }
        .diploma-page {
            width: 297mm;
            box-sizing: border-box;
            page-break-after: always;
        }
        .diploma-page:last-of-type {
            page-break-after: avoid;
        }
        .border-outer {
            border: 3px solid #1e3a5f;
            margin: 8mm;
            padding: 5mm;
            box-sizing: border-box;
        }
        .border-inner {
            border: 1px solid #1e3a5f;
            padding: 5mm;
            box-sizing: border-box;
        }
        .no-border-padding {
            padding: 18mm;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

<?php
// Resolve local background path safely
$bgPath = '';
if ($background) {
    if (!empty($settings['diploma_bg_url'])) {
        $bgPath = $settings['diploma_bg_url'];
    } else {
        $localSvg = public_path('assets/images/diploma_bg.svg');
        $localPng = public_path('assets/images/diploma_bg.png');
        if (file_exists($localSvg)) {
            $bgPath = $localSvg;
        } elseif (file_exists($localPng)) {
            $bgPath = $localPng;
        }
    }
}
?>

@foreach ($diplomas as $d)
<?php
$isBEP = (str_contains(strtolower($d['type_diplome_fr'] ?? ''), 'brevet d\'enseignement professionnel') 
       || str_contains(strtolower($d['type_diplome_fr'] ?? ''), 'enseignement professionnel')
       || str_contains($d['type_diplome_ar'] ?? '', 'التعليم المهني')
       || (isset($d['niveau_qualification']) && str_contains($d['niveau_qualification'], 'التعليم المهني'))
);
?>
<div class="diploma-page">
    
    <?php if ($background): ?>
    <div class="border-outer">
        <div class="border-inner">
    <?php else: ?>
    <div class="no-border-padding">
    <?php endif; ?>

        <!-- 1. HEADER TABLE -->
        <table style="width: 100%; border-collapse: collapse; direction: rtl;">
            <tr>
                <!-- Left: Serial -->
                <td style="width: 30%; text-align: left; vertical-align: top; direction: ltr;">
                    @if ($isBEP)
                        <span style="font-family: 'dejavusans', sans-serif; font-size: 11pt; font-weight: bold; color: #1e3a5f; display: inline-block; border-bottom: 1px dotted #000; min-width: 30mm; text-align: center;"><?= htmlspecialchars($d['num_serie'] ?? '') ?></span>
                        <span style="font-size: 7.5pt; color: #64748b; display: inline-block; margin-left: 4px;">الرقم التسلسلي</span>
                    @else
                        <span style="font-family: 'dejavusans', sans-serif; font-size: 11pt; font-weight: bold; color: #1e3a5f; display: block;"><?= htmlspecialchars($d['num_serie'] ?? '') ?></span>
                        <span style="font-size: 7.5pt; color: #64748b; display: block; margin-top: 2px;">الرقم التسلسلي</span>
                    @endif
                </td>
                <!-- Center: Country / Ministry -->
                <td style="width: 40%; text-align: center; vertical-align: top;">
                    <div style="font-size: 11.5pt; font-weight: bold; color: #1e3a5f; margin-bottom: 2px;">الجمهورية الجزائرية الديمقراطية الشعبية</div>
                    <div style="font-size: 10.5pt; font-weight: bold; color: #1e3a5f;">وزارة التكوين و التعليم المهنيين</div>
                </td>
                <!-- Right: Wilaya / Etab -->
                <td style="width: 30%; text-align: right; vertical-align: top;">
                    <div style="font-size: 10.5pt; color: #334155; margin-bottom: 2px;">مديرية التكوين و التعليم المهنيين لولاية <?= htmlspecialchars($d['wilaya_ar'] ?? '') ?></div>
                    <div style="font-size: 10.5pt; font-weight: bold; color: #1e3a5f;"><?= htmlspecialchars($d['etab_ar'] ?? '') ?></div>
                </td>
            </tr>
        </table>

        <!-- 2. MAIN TITLE -->
        @if (!$isBEP)
            <div style="text-align: center; margin-top: 6mm; font-size: 24pt; font-weight: bold; color: #1e3a5f;">
                <?= htmlspecialchars($d['type_diplome_ar'] ?? 'شهادة تقني سام') ?>
            </div>
        @else
            <div style="height: 10mm;"></div>
        @endif

        <!-- 3. PREAMBLE -->
        <div style="margin: <?= $isBEP ? '2mm' : '4mm' ?> 10mm 0 10mm; text-align: center; direction: rtl;">
            <div style="font-size: 11pt; font-weight: bold; color: #1e3a5f; margin-bottom: 2px;"><?= htmlspecialchars($settings['diploma_decree_title'] ?? 'إن وزير التكوين و التعليم المهنيين') ?></div>
            <div style="font-size: 9pt; color: #334155; line-height: 1.4; text-align: justify;">
                @if ($isBEP)
                    بمقتضى المرسوم التنفيذي رقم 17-212 المؤرخ في 26 شوال 1438 الموافق 20 يوليو 2017، الذي يحدد كيفيات إحداث الشهادات المتوجة لأطوار التعليم المهني<br>
                    بمقتضى القرار الوزاري رقم 102 المؤرخ 8 جمادى الآخرة عام 1442 الموافق 31 جانفي سنة 2021، الذي يحدد شروط وكيفيات تنظيم و تسليم الشهادات المتوجة لأطوار التعليم المهني و كذا نماذجها<br>
                    بناءا على محضر لجنة المداولات رقم : <?= htmlspecialchars($d['num_deliberation'] ?? '1') ?> المؤرخ في : <?= htmlspecialchars($d['date_deliberation_ar'] ?? '') ?>
                @else
                    <?= htmlspecialchars($settings['diploma_decree_1'] ?? 'بمقتضى المرسوم التنفيذي رقم 16-282 المؤرخ في 2 صفر عام 1438 الموافق لـ 2 نوفمبر 2016 والذي يحدد نظام التكوين المهني الأولي والشهادات المتوجة له') ?><br>
                    <?= htmlspecialchars($settings['diploma_decree_2'] ?? 'بمقتضى القرار المؤرخ في 23 ربيع الأول عام 1439 الموافق لـ 12 ديسمبر 2017 الذي يحدد شروط وكيفيات تسليم الشهادات المتوجة للتكوين المهني الأولي') ?><br>
                    بناءا على محضر لجنة مداولات نهاية التكوين رقم : <?= htmlspecialchars($d['num_deliberation'] ?? '31') ?> المؤرخ في : <?= htmlspecialchars($d['date_deliberation_ar'] ?? '') ?>
                @endif
            </div>
        </div>

        <!-- 4. BIOGRAPHICAL DETAILS TABLE (Arabic Right, French Left) -->
        <table style="width: 100%; margin-top: <?= $isBEP ? '4mm' : '6mm' ?>; border-collapse: collapse; direction: rtl;">
            <tr>
                <!-- ARABIC DETAILS (Right Column) -->
                <td style="width: 60%; vertical-align: top; text-align: right; font-size: 11.5pt; color: #1e293b; line-height: 1.7; padding-right: 5mm;">
                    تمنح هذه الشهادة للسيد(ة) : <strong><?= htmlspecialchars(($d['nom_ar'] ?? '') . ' ' . ($d['prenom_ar'] ?? '')) ?></strong><br>
                    المولود(ة) بتاريخ : <strong><?= htmlspecialchars($d['date_naissance_ar'] ?? '') ?></strong> بـ <strong><?= htmlspecialchars($d['lieu_naissance'] ?? '') ?></strong><br>
                    التخصص : <strong><?= htmlspecialchars($d['spec_ar'] ?? '') ?></strong><br>
                    مستوى التأهيل : <strong><?= htmlspecialchars($d['niveau_qualification'] ?? 'الخامس') ?></strong> &nbsp;&nbsp;&nbsp;&nbsp; بتقدير : <strong><?= htmlspecialchars($d['mention_ar'] ?? '') ?></strong><br>
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
                </td>
                <!-- FRENCH DETAILS (Left Column) -->
                <td style="width: 40%; vertical-align: top; text-align: left; font-size: 9.5pt; color: #334155; line-height: 1.5; direction: ltr; padding-left: 5mm;">
                    Nom : <strong><?= htmlspecialchars(strtoupper($d['nom_fr'] ?? '')) ?></strong><br>
                    Prénom : <strong><?= htmlspecialchars(ucfirst($d['prenom_fr'] ?? '')) ?></strong><br>
                    Date & Lieu de naissance : <strong><?= htmlspecialchars($d['date_naissance_fr'] ?? '') ?> &nbsp; <?= htmlspecialchars(strtoupper($d['lieu_naissance_fr'] ?? '')) ?></strong><br>
                    Spécialité : <strong><?= htmlspecialchars($d['spec_fr'] ?? '') ?></strong><br>
                    Diplôme : <strong><?= htmlspecialchars($d['type_diplome_fr'] ?? '') ?></strong>
                </td>
            </tr>
        </table>

        <!-- 5. SIGNATURES & QR TABLE -->
        <table style="width: 100%; margin-top: 6mm; border-collapse: collapse; direction: rtl;">
            <tr>
                <!-- Pedagogical Signature (Right) -->
                <td style="width: 35%; text-align: center; vertical-align: top;">
                    <div style="font-size: 9.5pt; font-weight: bold; color: #1e3a5f; margin-bottom: 8mm;">المسؤول (ة) البيداغوجي (ة)</div>
                    <div style="height: 10mm;"></div>
                </td>
                <!-- QR Code (Center) -->
                <td style="width: 30%; text-align: center; vertical-align: top;">
                    <?php if (!empty($d['qr_base64'])): ?>
                        <img src="<?= $d['qr_base64'] ?>" style="width: 20mm; height: 20mm; margin-bottom: 2px;" alt="QR">
                    <?php endif; ?>
                    <div style="font-family: 'dejavusans', sans-serif; font-size: 8pt; color: #64748b;"><?= htmlspecialchars($d['numero_matricule'] ?? '') ?></div>
                </td>
                <!-- Director Signature (Left) -->
                <td style="width: 35%; text-align: center; vertical-align: top;">
                    <div style="font-size: 9.5pt; font-weight: bold; color: #1e3a5f; margin-bottom: 8mm;">مدير (ة) المؤسسة</div>
                    <div style="height: 10mm;"></div>
                </td>
            </tr>
        </table>

        <!-- 6. BOTTOM NOTICE -->
        <div style="text-align: center; font-size: 8pt; color: #94a3b8; margin-top: 3mm;">
            لا تسلم إلا نسخة واحدة من هذه الشهادة
        </div>

    <?php if ($background): ?>
        </div>
    </div>
    <?php else: ?>
    </div>
    <?php endif; ?>

</div>
@endforeach

</body>
</html>
