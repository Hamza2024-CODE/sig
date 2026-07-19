@extends('layouts.print')
@section('title', $title ?? 'طباعة - SGFEP')
@section('content')
<?php
/**
 * @var array  $document   — document metadata (type, code, date)
 * @var array  $details    — person details from DB
 * @var bool   $is_employe — true for encadrement, false for apprenant
 */
$docType   = $document['document_type'] ?? 'certificat_scolaire';
$codeVer   = $document['code_verification'] ?? $document['code'] ?? 'N/A';
$docDate   = $document['date'] ?? date('Y-m-d');
$isEmploye = $is_employe ?? false;

// Normalise person name (works for both apprenant and encadrement)
$nomAr    = trim(($details['nom_ar'] ?? '') . ' ' . ($details['prenom_ar'] ?? ''));
$nomFr    = trim(($details['nom_fr'] ?? '') . ' ' . ($details['prenom_fr'] ?? ''));
$dateNais = $details['date_naissance'] ?? '---';
$lieu     = $details['lieu_naissance'] ?? 'الجزائر';
$matricule= $details['numero_matricule'] ?? '---';
$etabNom  = $details['etab_nom'] ?? 'المعهد الوطني المتخصص في التكوين المهني';
$etabFr   = $details['etab_fr']  ?? 'INSFP';

// Spec / Grade
$specAr   = $details['spec_ar'] ?? '---';
$specFr   = $details['spec_fr'] ?? '';

// Document titles
$titles = [
    'certificat_scolaire'     => ['ar' => 'شـهادة مدرسـية',         'fr' => 'Certificat Scolaire'],
    'attestation_inscription' => ['ar' => 'شـهادة تسـجيل',          'fr' => 'Attestation d\'Inscription'],
    'attestation_stage'       => ['ar' => 'شـهادة تـربـص',          'fr' => 'Attestation de Stage'],
    'attestation_travail'     => ['ar' => 'شـهادة عـمـل',           'fr' => 'Attestation de Travail'],
    'bulletin_notes'          => ['ar' => 'كشـف النـقاط السـداسي',  'fr' => 'Bulletin de Notes'],
    'decision_isqat'          => ['ar' => 'شـهادة تـكويـن - مـفـصـولـيـن', 'fr' => 'Certificat de Formation - Exclus'],
    'basma_mouahada'          => ['ar' => 'بـطاقـة البـصمـة المـوحـدة', 'fr' => 'Fiche d\'Empreinte Unifiée'],
    'fiche_paie'              => ['ar' => 'كشـف الـراتب الشـهري',   'fr' => 'Bulletin de Paie Mensuel'],
];
$titleAr = $titles[$docType]['ar'] ?? 'وثيقة إدارية';
$titleFr = $titles[$docType]['fr'] ?? 'Document Administratif';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($codeVer) ?> — <?= htmlspecialchars($titleAr) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f1f5f9;
            margin: 0; padding: 20px;
            color: #1e293b;
        }
        @page {
            size: A4 portrait;
            margin: 0;
        }
        .print-container {
            background: white;
            width: 210mm; 
            min-height: 297mm;
            margin: 0 auto;
            padding: 12mm 15mm;
            box-shadow: 0 4px 20px rgba(0,0,0,.12);
            border-radius: 8px;
            position: relative;
        }
        /* ─── Header ─── */
        .rep-header { text-align: center; font-weight: 800; font-size: 13px; line-height: 1.8; }
        .rep-divider { border: none; border-top: 2px solid #482b8f; margin: 8px 0 16px; }
        /* ─── Establishment band ─── */
        .etab-band {
            display: flex; justify-content: space-between; align-items: flex-start;
            font-size: 12px; font-weight: 600;
            border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 28px;
        }
        /* ─── Title ─── */
        .doc-title-wrap { text-align: center; margin-bottom: 36px; }
        .doc-title-ar   { font-size: 28px; font-weight: 800; color: #482b8f; letter-spacing: .5px; }
        .doc-title-fr   { font-size: 14px; font-weight: 600; color: #64748b; font-family:'Outfit',sans-serif; }
        .title-underline { width: 80px; height: 3px; background: #482b8f; margin: 8px auto 0; border-radius: 2px; }
        /* ─── Body ─── */
        .doc-body { font-size: 15.5px; line-height: 2.4; text-align: justify; margin-bottom: 40px; }
        .doc-body .v { font-weight: 700; border-bottom: 1px dotted #000; padding: 0 4px; }
        /* ─── Table ─── */
        .notes-table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 13.5px; }
        .notes-table th, .notes-table td { border: 1px solid #cbd5e1; padding: 9px 12px; text-align: center; }
        .notes-table thead th { background: #f0ecff; color: #482b8f; font-weight: 700; }
        .notes-table tfoot td { background: #f8fafc; font-weight: 800; }
        /* ─── Footer ─── */
        .doc-footer {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-top: 60px; padding-top: 20px; border-top: 1px dashed #cbd5e1;
        }
        .qr-badge {
            border: 2px dashed #10b981; padding: 12px; border-radius: 12px;
            background: #f0fdf4; font-size: 11px; line-height: 1.5; max-width: 350px;
            display: flex; align-items: center; gap: 14px;
        }
        .qr-badge img {
            width: 80px; height: 80px; border-radius: 4px; border: 1px solid #bbf7d0;
            background: white; display: block;
        }
        .qr-badge .code-ver {
            font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 12px;
            background: white; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 4px;
            display: inline-block; margin-top: 6px; letter-spacing: 0.5px; color: #0f172a;
        }
        .sig-block { text-align: center; font-size: 13px; font-weight: 700; }
        .sig-line  { width: 160px; border-bottom: 1px solid #94a3b8; margin: 50px auto 6px; }
        /* ─── Print button ─── */
        .print-btn-wrap {
            position: fixed; bottom: 28px; right: 28px; z-index: 9999;
            display: flex; gap: 10px;
        }
        .btn-p {
            border: none; padding: 12px 22px; font-size: 15px; font-weight: 700;
            border-radius: 50px; cursor: pointer; display: flex; align-items: center; gap: 8px;
            box-shadow: 0 8px 20px rgba(0,0,0,.18); transition: all .2s;
        }
        .btn-p:hover { transform: translateY(-2px); }
        .btn-print { background: #482b8f; color: white; }
        .btn-back  { background: white; color: #482b8f; border: 2px solid #482b8f; }
        @media print {
            body { background: white; padding: 0; }
            .print-container { box-shadow: none; width: 100%; padding: 0; margin: 0; }
            .print-btn-wrap, .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Floating buttons -->
<div class="print-btn-wrap no-print">
    <button onclick="history.back()" class="btn-p btn-back"><i class="fa-solid fa-arrow-right"></i> رجوع</button>
    
    <?php if (in_array($docType, ['certificat_scolaire', 'attestation_inscription', 'attestation_stage'])): ?>
    <div style="display:flex; align-items:center; gap:8px; background:white; padding:4px 12px; border-radius:50px; border:2px solid #482b8f; box-shadow: 0 4px 15px rgba(0,0,0,0.15); direction: rtl;">
        <label style="font-weight:700; color:#482b8f; font-size:13px; margin:0; cursor:pointer;" for="docTypeSelector">نوع الوثيقة:</label>
        <select id="docTypeSelector" onchange="changeDocType(this.value)" style="border:none; outline:none; font-weight:700; color:#1e293b; background:transparent; font-size:13px; cursor:pointer; font-family:'Cairo',sans-serif;">
            <option value="attestation_stage" <?= $docType === 'attestation_stage' ? 'selected' : '' ?>>شهادة تربص (Attestation de Stage)</option>
            <option value="certificat_scolaire" <?= $docType === 'certificat_scolaire' ? 'selected' : '' ?>>شهادة مدرسية (Certificat Scolaire)</option>
            <option value="attestation_inscription" <?= $docType === 'attestation_inscription' ? 'selected' : '' ?>>شهادة تسجيل (Attestation d'Inscription)</option>
        </select>
    </div>
    <?php endif; ?>
    
    <button onclick="window.print()" class="btn-p btn-print"><i class="fa-solid fa-print"></i> طباعة الوثيقة</button>
</div>

<div class="print-container">
    <?php if ($docType === 'decision_isqat'): ?>
        <div style="border: 2px solid #000; border-radius: 0; padding: 20px; min-height: 250mm;">
    <?php endif; ?>

    <?php if ($docType !== 'bulletin_notes'): ?>
        <!-- ── Republic Header ── -->
        <div class="rep-header">
            الجمهورية الجزائرية الديمقراطية الشعبية<br>
            وزارة التكوين والتعليم المهنيين
        </div>
        <?php if ($docType !== 'decision_isqat'): ?>
            <hr class="rep-divider">
        <?php endif; ?>

        <!-- ── Establishment band ── -->
        <?php if ($docType === 'decision_isqat'): ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; font-size: 13px; font-weight: 700; margin-top: 15px; margin-bottom: 40px; direction: rtl; color: #000;">
                <div style="text-align: right; line-height: 1.8;">
                    مديرية التكوين والتعليم المهنيين لولاية <?= htmlspecialchars($details['wilaya_nom'] ?? 'سطيف') ?><br>
                    <?= htmlspecialchars($etabNom) ?><br>
                    الرقم: ........................
                </div>
                <div style="text-align: left; line-height: 1.8;">
                    رقم التسجيل : <?= htmlspecialchars($matricule) ?>
                </div>
            </div>
        <?php else: ?>
            <div class="etab-band">
                <div>
                    <div style="font-size:13px; font-weight:700;">مديرية التكوين والتعليم المهنيين لولاية <?= htmlspecialchars($details['wilaya_nom'] ?? 'سعيدة') ?></div>
                    <div><?= htmlspecialchars($etabNom) ?></div>
                </div>
                <div style="text-align:left; font-family:'Outfit',sans-serif; color:#64748b;">
                    <div>Direction de la Formation Professionnelle de <?= htmlspecialchars($details['wilaya_nom_fr'] ?? 'Saida') ?></div>
                    <div><?= htmlspecialchars($etabFr) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── Document Title ── -->
        <div class="doc-title-wrap" style="<?= $docType === 'decision_isqat' ? 'margin-top: 30px; margin-bottom: 40px;' : '' ?>">
            <div class="doc-title-ar" style="<?= $docType === 'decision_isqat' ? 'font-size: 42px; font-weight: 800; color: #000; letter-spacing: 2px;' : '' ?>">
                <?= ($docType === 'decision_isqat') ? 'شـهادة تـكويـن' : $titleAr ?>
            </div>
            <?php if ($docType !== 'decision_isqat'): ?>
                <div class="doc-title-fr"><?= $titleFr ?></div>
                <div class="title-underline"></div>
            <?php endif; ?>
        </div>

        <!-- ── Document Body ── -->
        <div class="doc-body">
    <?php endif; ?>

        <?php if ($docType === 'attestation_stage'): ?>
            إن مدير (ة) المؤسسة يشهد أن المتكون (ة) :
            <br>
            &nbsp;&nbsp;&nbsp;اللقب و الاسم : <span class="v"><?= htmlspecialchars($nomAr) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;تاريخ ومكان الميلاد: <span class="v"><?= htmlspecialchars($dateNais) ?></span>
            بـ: <span class="v"><?= htmlspecialchars($lieu ?: 'الجزائر') ?></span>
            <br>
            <?php if (!empty($details['adresse_ar'])): ?>
            &nbsp;&nbsp;&nbsp;العنوان : <span class="v"><?= htmlspecialchars($details['adresse_ar']) ?></span>
            <br>
            <?php endif; ?>
            &nbsp;&nbsp;&nbsp;مسجل (ة) تحت رقم: <span class="v" style="font-family:'Outfit';"><?= htmlspecialchars($matricule) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;يتابع تكوينا في <span class="v"><?= htmlspecialchars($specAr) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;نمط التكوين : <span class="v"><?= htmlspecialchars($details['mode_formation_ar'] ?? 'عن طريق التمهين') ?></span>
            <?php if (!empty($details['session_nom'])): ?>
            — الدروس المسائية للحصول على : <span class="v"><?= htmlspecialchars($details['session_nom']) ?></span>
            <?php endif; ?>
            <br>
            &nbsp;&nbsp;&nbsp;مدة التكوين : من
            <span class="v"><?= htmlspecialchars($details['date_debut_formatted'] ?? $details['date_debut'] ?? '---') ?></span>
            إلى <span class="v"><?= htmlspecialchars($details['date_fin_formatted'] ?? $details['date_fin'] ?? '---') ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;السنة التكوينية: <span class="v"><?= htmlspecialchars($details['session_nom'] ?? '2026/2025') ?></span>
            <?php if (!empty($details['semestre_num'])): ?>
            <br>
            &nbsp;&nbsp;&nbsp;السداسي رقم : <span class="v"><?= (int)$details['semestre_num'] ?></span>
            <?php endif; ?>
            <br><br>
            سُلمت هذه الوثيقة لاستعمالها فيما يسمح به القانون.

        <?php elseif ($docType === 'certificat_scolaire'): ?>
            يشهد مدير <span class="v"><?= htmlspecialchars($etabNom) ?></span> أن المتربص(ة):
            <br>
            &nbsp;&nbsp;&nbsp;السيد(ة) / الآنسة: <span class="v"><?= htmlspecialchars($nomAr) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;المولود(ة) بتاريخ: <span class="v"><?= htmlspecialchars($dateNais) ?></span>
            بـ: <span class="v"><?= htmlspecialchars($lieu) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;المسجل(ة) تحت رقم التسجيل: <span class="v" style="font-family:'Outfit';"><?= htmlspecialchars($matricule) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;يتابع تكوينه بالمؤسسة في تخصص: <span class="v"><?= htmlspecialchars($specAr) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;خلال الموسم الدراسي الحالي: <span class="v">2026 / 2025</span>
            <?php if (!empty($details['session_nom'])): ?>
            — الدورة: <span class="v"><?= htmlspecialchars($details['session_nom']) ?></span>
            <?php endif; ?>
            <br><br>
            سُلمت هذه الشهادة بناءً على طلب المعني بالأمر لاستعمالها في حدود ما يسمح به القانون.

        <?php elseif ($docType === 'attestation_inscription'): ?>
            يشهد مدير المؤسسة التكوينية المذكورة أعلاه أن المترشح(ة):
            <br>
            &nbsp;&nbsp;&nbsp;السيد(ة) / الآنسة: <span class="v"><?= htmlspecialchars($nomAr) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;المولود(ة) بتاريخ: <span class="v"><?= htmlspecialchars($dateNais) ?></span>
            بـ: <span class="v"><?= htmlspecialchars($lieu) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;رقم التسجيل: <span class="v" style="font-family:'Outfit';"><?= htmlspecialchars($matricule) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;قد تم تسجيله(ا) رسمياً في التخصص: <span class="v"><?= htmlspecialchars($specAr) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;بـ: <span class="v"><?= htmlspecialchars($etabNom) ?></span>
            خلال دورة: <span class="v"><?= htmlspecialchars($details['session_nom'] ?? 'فيفري 2026') ?></span>
            <br><br>
            سُلمت هذه الشهادة لتأكيد عملية التسجيل الإداري النهائي.

        <?php elseif ($docType === 'attestation_travail'): ?>
            يشهد مدير الموارد البشرية لقطاع التكوين والتعليم المهنيين أن:
            <br>
            &nbsp;&nbsp;&nbsp;السيد(ة): <span class="v"><?= htmlspecialchars($nomAr) ?></span>
            <?php if ($nomFr): ?>
            &nbsp; / <span style="font-family:'Outfit';"><?= htmlspecialchars($nomFr) ?></span>
            <?php endif; ?>
            <br>
            &nbsp;&nbsp;&nbsp;المولود(ة) بتاريخ: <span class="v"><?= htmlspecialchars($dateNais) ?></span>
            بـ: <span class="v"><?= htmlspecialchars($lieu) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;رقم التعريف الوطني (NIN): <span class="v" style="font-family:'Outfit';"><?= htmlspecialchars($matricule) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;يشغل منصب: <span class="v"><?= htmlspecialchars($specAr) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;بـ: <span class="v"><?= htmlspecialchars($etabNom) ?></span>
            <?php if (!empty($details['date_recrutement'])): ?>
            <br>
            &nbsp;&nbsp;&nbsp;تاريخ التوظيف: <span class="v"><?= htmlspecialchars($details['date_recrutement']) ?></span>
            <?php endif; ?>
            <br><br>
            يمارس مهامه بصفة منتظمة ومستمرة. سُلمت هذه الشهادة بناءً على طلبه لتقديمها لمن يهمه الأمر.

        <?php elseif ($docType === 'bulletin_notes'): ?>
            <?php foreach ($semestersData as $index => $sem): ?>
                <?php if ($index > 0): ?>
                    <div style="page-break-before: always;"></div>
                <?php endif; ?>
                
                <div style="font-family: 'Cairo', sans-serif; color: #000; background: #fff; padding: 10px; direction: rtl; min-height: 275mm; position: relative;">
                    
                    <!-- Republic Header -->
                    <div style="text-align: center; font-size: 13.5px; font-weight: 700; line-height: 1.6; margin-bottom: 5px;">
                        الجمهورية الجزائرية الديمقراطية الشعبية<br>
                        وزارة التكوين والتعليم المهنيين
                    </div>
                    
                    <!-- Header Etab/Wilaya Info -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; font-size: 12.5px; font-weight: 700; margin-bottom: 25px;">
                        <div style="text-align: right; line-height: 1.8;">
                            ولاية <?= htmlspecialchars($details['wilaya_nom'] ?? 'سطيف') ?><br>
                            مديرية التكوين والتعليم المهنيين<br>
                            <?= htmlspecialchars($etabNom) ?><br>
                            رقم: ........................
                        </div>
                        <div style="text-align: left; line-height: 1.8; font-family: 'Outfit'; visibility: hidden;">
                            <!-- Spacer -->
                        </div>
                    </div>
                    
                    <!-- Center Title -->
                    <div style="text-align: center; margin-bottom: 25px;">
                        <span style="font-size: 26px; font-weight: 800; border-bottom: 2px solid #000; padding-bottom: 4px;">كشـف النـقاط</span>
                    </div>
                    
                    <!-- Trainee Details Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px 40px; font-size: 14px; font-weight: 700; margin-bottom: 25px; line-height: 2;">
                        <div>الاسم واللقب : <span style="border-bottom: 1px dotted #000; padding: 0 8px;"><?= htmlspecialchars($nomAr) ?></span></div>
                        <div>رقم التسجيل : <span style="font-family: 'Outfit'; border-bottom: 1px dotted #000; padding: 0 8px;"><?= htmlspecialchars($matricule) ?></span></div>
                        <div style="grid-column: span 2;">الاختصاص : <span style="border-bottom: 1px dotted #000; padding: 0 8px;"><?= htmlspecialchars($specAr) ?></span></div>
                        <div>المستوى : <span style="font-family: 'Outfit'; border-bottom: 1px dotted #000; padding: 0 8px;"><?= htmlspecialchars($details['spec_niveau'] ?? '3') ?></span></div>
                        <div>الشهادة : <span style="border-bottom: 1px dotted #000; padding: 0 8px;"><?= htmlspecialchars($details['dplm_nom'] ?? 'شهادة التحكم المهني') ?></span></div>
                        
                        <?php
                        $formatToSlashYMD = function($dateStr) {
                            if (empty($dateStr)) return '---';
                            $dateStr = str_replace('-', '/', $dateStr);
                            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
                                return "{$m[3]}/{$m[2]}/{$m[1]}";
                            }
                            return $dateStr;
                        };
                        ?>
                        
                        <div>فترة التكوين من : <span style="font-family: 'Outfit';"><?= !empty($details['date_debut']) ? date('Y/m/d', strtotime($details['date_debut'])) : '---' ?></span> إلى : <span style="font-family: 'Outfit';"><?= !empty($details['date_fin']) ? date('Y/m/d', strtotime($details['date_fin'])) : '---' ?></span></div>
                        <div>السداسي : <span style="font-family: 'Outfit';"><?= $sem['num_sem'] ?></span> من : <span style="font-family: 'Outfit';"><?= !empty($sem['date_d']) ? date('Y/m/d', strtotime($sem['date_d'])) : '---' ?></span> إلى : <span style="font-family: 'Outfit';"><?= !empty($sem['date_f']) ? date('Y/m/d', strtotime($sem['date_f'])) : '---' ?></span></div>
                    </div>
                    
                    <!-- Grades Table -->
                    <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; border: 1.5px solid #000; text-align: center; margin-bottom: 25px; color: #000;">
                        <thead>
                            <tr style="border-bottom: 1.5px solid #000; font-weight: 700; height: 35px; background: #f5f5f5;">
                                <th style="border: 1px solid #000; width: 45px;">الرقم</th>
                                <th style="border: 1px solid #000; text-align: right; padding-right: 8px;">المادة</th>
                                <th style="border: 1px solid #000; width: 60px;">مراقبة 1</th>
                                <th style="border: 1px solid #000; width: 60px;">مراقبة 2</th>
                                <th style="border: 1px solid #000; width: 80px;">الامتحان الشامل</th>
                                <th style="border: 1px solid #000; width: 90px;">معدل المادة قبل</th>
                                <th style="border: 1px solid #000; width: 70px;">الاستدراك</th>
                                <th style="border: 1px solid #000; width: 90px;">المعدل النهائي</th>
                                <th style="border: 1px solid #000; width: 55px;">المعامل</th>
                                <th style="border: 1px solid #000; width: 60px;">الاقصائية</th>
                                <th style="border: 1px solid #000; width: 110px;">ملاحظة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $coefSum = 0;
                            $pointsBeforeSum = 0;
                            $pointsAfterSum = 0;
                            $hasResit = false;
                            
                            if (empty($sem['marks'])): ?>
                                <tr style="height: 40px;">
                                    <td colspan="11" style="border: 1px solid #000; font-weight: 700; text-align: center; color: #555; font-size: 13.5px; background: #fafafa;">
                                        لا توجد علامات تفصيلية مسجلة للمواد في هذا السداسي
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($sem['marks'] as $mIdx => $m): 
                                    $coef = (float)$m['coefficient'];
                                    $coefSum += $coef;
                                    
                                    $avgBefore = $m['average_before'] !== null ? (float)$m['average_before'] : (float)$m['average'];
                                    $pointsBeforeSum += $avgBefore * $coef;
                                    
                                    $avgAfter = $m['average'] !== null ? (float)$m['average'] : $avgBefore;
                                    $pointsAfterSum += $avgAfter * $coef;
                                    
                                    if ($m['resit'] !== null) {
                                        $hasResit = true;
                                    }
                                    
                                    // Determine appreciation/remark
                                    if ($m['average'] === null) {
                                        $appText = '---';
                                    } elseif ($m['average'] >= 18) {
                                        $appText = 'ممتاز جداً';
                                    } elseif ($m['average'] >= 16) {
                                        $appText = 'ممتاز';
                                    } elseif ($m['average'] >= 14) {
                                        $appText = 'جيد جداً';
                                    } elseif ($m['average'] >= 12) {
                                        $appText = 'جيد';
                                    } elseif ($m['average'] >= 10) {
                                        $appText = 'قريب من الحسن';
                                    } else {
                                        $appText = 'دون الوسط';
                                    }
                                    
                                    $elimText = ($avgAfter < 5.0 && $m['average'] !== null) ? 'قصية' : '0,00';
                                ?>
                                    <tr style="height: 28px;">
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $mIdx + 1 ?></td>
                                        <td style="border: 1px solid #000; text-align: right; padding-right: 8px; font-weight: 700;"><?= htmlspecialchars($m['module_nom']) ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $m['cc1'] !== null ? number_format($m['cc1'], 2) : '---' ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $m['cc2'] !== null ? number_format($m['cc2'], 2) : '---' ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $m['exam'] !== null ? number_format($m['exam'], 2) : '---' ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $m['average_before'] !== null ? number_format($m['average_before'], 2) : number_format($avgBefore, 2) ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $m['resit'] !== null ? number_format($m['resit'], 2) : '---' ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit'; font-weight: 700;"><?= number_format($avgAfter, 2) ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $coef ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit'; color: <?= $elimText === 'إقصائية' || $elimText === 'قصية' ? 'red' : '#000' ?>;"><?= $elimText ?></td>
                                        <td style="border: 1px solid #000; font-weight: 700;"><?= htmlspecialchars($appText) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php
                                $avgBeforeResult = $coefSum > 0 ? round($pointsBeforeSum / $coefSum, 2) : 0;
                                $avgAfterResult = $coefSum > 0 ? round($pointsAfterSum / $coefSum, 2) : 0;
                                ?>
                                
                                <!-- Subtotal row 1: مع قبل -->
                                <tr style="height: 30px; font-weight: 700; background: #fafafa;">
                                    <td colspan="5" style="border: 1px solid #000; text-align: left; padding-left: 10px;">مع قبل</td>
                                    <td style="border: 1px solid #000; font-family: 'Outfit';"><?= number_format($pointsBeforeSum, 2) ?></td>
                                    <td style="border: 1px solid #000;">---</td>
                                    <td style="border: 1px solid #000; font-family: 'Outfit';"><?= number_format($avgBeforeResult, 2) ?></td>
                                    <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $coefSum ?></td>
                                    <td colspan="2" style="border: 1px solid #000;">---</td>
                                </tr>
                                
                                <!-- Subtotal row 2: مع بعد (only shown if there is a resit or different values) -->
                                <?php if ($hasResit || $pointsAfterSum !== $pointsBeforeSum): ?>
                                    <tr style="height: 30px; font-weight: 700; background: #fafafa;">
                                        <td colspan="5" style="border: 1px solid #000; text-align: left; padding-left: 10px;">مع بعد</td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= number_format($pointsAfterSum, 2) ?></td>
                                        <td style="border: 1px solid #000;">---</td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= number_format($avgAfterResult, 2) ?></td>
                                        <td style="border: 1px solid #000; font-family: 'Outfit';"><?= $coefSum ?></td>
                                        <td colspan="2" style="border: 1px solid #000;">---</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Bottom Committee Decision & Summary Panel -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 13px; font-weight: 700; margin-bottom: 30px; line-height: 1.8; color: #000;">
                        <div style="border: 1px solid #000; padding: 12px; border-radius: 8px;">
                            <div>معدل السداسي : <span style="font-family: 'Outfit'; border: 1px solid #000; padding: 2px 15px; border-radius: 4px; margin-right: 8px;"><?= number_format($avgAfterResult, 2) ?></span></div>
                            <div style="margin-top: 8px;">قرار اللجنة : <span style="border: 1px solid #000; padding: 2px 15px; border-radius: 4px; margin-right: 8px;"><?= htmlspecialchars($sem['decision_nom']) ?></span></div>
                        </div>
                        
                        <div style="border: 1px solid #000; padding: 12px; border-radius: 8px;">
                            <div>ملاحظة اللجنة : <span style="border: 1px solid #000; padding: 2px 15px; border-radius: 4px; margin-right: 8px;"><?= !empty($sem['observation']) ? htmlspecialchars($sem['observation']) : 'لاشيء' ?></span></div>
                            <div style="margin-top: 8px;">عدد الغيابات : <span style="font-family: 'Outfit'; border: 1px solid #000; padding: 2px 10px; border-radius: 4px; margin-right: 8px;"><?= $sem['total_abs'] ?></span> &nbsp;&nbsp;&nbsp; منها مبررة : <span style="font-family: 'Outfit'; border: 1px solid #000; padding: 2px 10px; border-radius: 4px; margin-right: 8px;"><?= $sem['justified_abs'] ?></span></div>
                        </div>
                    </div>
                    
                    <!-- Footer Release & Signatures -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-end; font-size: 13.5px; font-weight: 700; color: #000;">
                        <!-- QR Code & Barcode on Left side of footer -->
                        <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                            <!-- QR Code -->
                            <div style="border: 1px solid #000; padding: 5px; border-radius: 6px; background: #fff;">
                                @php
                                     $verificationUrl = url('/verify?code=' . urlencode($codeVer));
                                     $qrCodeUrl = url('/api/qrcode?data=' . urlencode($verificationUrl));
                                @endphp
                                <img src="{{ $qrCodeUrl }}" alt="رمز التحقق الرقمي" style="width: 55px; height: 55px; display: block;" />
                            </div>
                            <!-- Barcode look-alike -->
                            <div style="display: flex; gap: 1px; align-items: stretch; height: 28px; width: 120px; border: 1px solid #000; padding: 2px; background: #fff;">
                                <?php for ($i=0; $i<28; $i++): ?>
                                    <div style="width: <?= rand(1, 3) ?>px; background: #000;"></div>
                                    <div style="width: <?= rand(1, 2) ?>px; background: transparent;"></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- City and Signature on Right side -->
                        <div style="text-align: right; line-height: 2;">
                            <?php
                            $city = '';
                            if (!empty($details['etab_nom'])) {
                                if (preg_match('/العلمة/u', $details['etab_nom'])) {
                                    $city = 'العلمة';
                                } elseif (preg_match('/يوب/u', $details['etab_nom'])) {
                                    $city = 'يوب';
                                } else {
                                    $words = explode(' ', trim($details['etab_nom']));
                                    $city = end($words);
                                }
                            }
                            $wilaya = $details['wilaya_nom'] ?? '';
                            $issuePlace = trim($city . '_' . $wilaya, '_');
                            ?>
                            حرر بـ : <strong><?= htmlspecialchars($issuePlace) ?></strong> في : <strong><?= date('Y/m/d', strtotime($docDate)) ?></strong>
                            <br>
                            <div style="text-align: center; margin-top: 10px; font-weight: 700; width: 180px;">
                                مدير (ة) المؤسسة
                            </div>
                        </div>
                    </div>
                    
                </div>
            <?php endforeach; ?>
        <?php elseif ($docType === 'decision_isqat'): ?>
            <?php
            $formatToSlashYMD = function($dateStr) {
                if (empty($dateStr)) return '---';
                $dateStr = str_replace('-', '/', $dateStr);
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
                    return "{$m[3]}/{$m[2]}/{$m[1]}";
                }
                return $dateStr;
            };
            ?>
            <div style="font-size: 17.5px; line-height: 2.8; margin-top: 40px; font-family: 'Cairo', sans-serif; color: #000; text-align: right; padding: 0 10px; direction: rtl;">
                إن مدير : <span style="font-weight: 700; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= htmlspecialchars($etabNom) ?></span>
                <br>
                يشهد أن السيد(ة) : <span style="font-weight: 700; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= htmlspecialchars($nomAr) ?></span>
                <br>
                المولود(ة) بتاريخ : <span style="font-weight: 700; font-family: 'Outfit'; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= $formatToSlashYMD($dateNais) ?></span>
                &nbsp;&nbsp; بـ: <span style="font-weight: 700; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= htmlspecialchars($lieu) ?></span>
                <br>
                تابع (ت) تكوينا في اختصاص : <span style="font-weight: 700; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= htmlspecialchars($specAr) ?></span>
                <br>
                مدة التكوين : <span style="font-weight: 700; font-family: 'Outfit'; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= htmlspecialchars($details['spec_duree'] ?? '36') ?></span> شهرا
                &nbsp;&nbsp; مستوى التأهيل : <span style="font-weight: 700; font-family: 'Outfit'; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= htmlspecialchars($details['spec_niveau'] ?? '4') ?></span>
                <br>
                بداية التكوين : <span style="font-weight: 700; font-family: 'Outfit'; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= !empty($details['date_debut']) ? date('Y/m/d', strtotime($details['date_debut'])) : '---' ?></span>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; تاريخ الفصل : <span style="font-weight: 700; font-family: 'Outfit'; color: #000; border-bottom: 1px dotted #000; padding-bottom: 2px;"><?= !empty($details['date_exclusion']) ? htmlspecialchars($details['date_exclusion']) : '---' ?></span>
            </div>

        <?php elseif ($docType === 'basma_mouahada'): ?>
            بناءً على تفعيل نظام الرقمنة والتحقق البيومتري الموحد لقطاع التكوين والتعليم المهنيين، تشهد الإدارة ببيانات البصمة الرقمية الآتية:
            <br><br>
            <div style="background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); border: 1px solid #99f6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between;">
                <!-- Fingerprint Watermark overlay -->
                <div style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); opacity: 0.12; font-size: 130px; color: #0d9488; pointer-events: none;">
                    <i class="fa-solid fa-fingerprint"></i>
                </div>
                
                <div style="flex: 0 0 70%; position: relative; z-index: 1;">
                    <table style="width: 100%; border: none; font-size: 14.5px; line-height: 2.2; text-align: right; direction: rtl;">
                        <tr>
                            <td style="font-weight: 700; width: 140px; color: #0f766e;"><i class="fa-solid fa-user me-1"></i> الاسم واللقب:</td>
                            <td class="v" style="font-weight:700;"><?= htmlspecialchars($nomAr) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; color: #0f766e;"><i class="fa-solid fa-id-card me-1"></i> المعرف الرقمي:</td>
                            <td class="v" style="font-family:'Outfit'; font-weight:700;"><?= htmlspecialchars($matricule) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; color: #0f766e;"><i class="fa-solid fa-key me-1"></i> رمز البصمة:</td>
                            <td class="v" style="font-family:'Outfit'; font-weight: 700; color: #0d9488;">BIO-FPR-<?= str_pad($details['id'] ?? 0, 6, '0', STR_PAD_LEFT) ?>-DZ</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; color: #0f766e;"><i class="fa-solid fa-laptop-code me-1"></i> حالة التحقق:</td>
                            <td>
                                <span class="badge bg-success text-white px-2 py-1 rounded" style="font-size: 11px; background-color: #10b981 !important;"><i class="fa-solid fa-circle-check"></i> موحدة ومؤكدة نشطة</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="flex: 0 0 25%; text-align: center; border-right: 2px dashed rgba(13, 148, 136, 0.25) !important; padding-right: 20px; z-index: 1;">
                    <div style="background: white; border: 2px dashed #0d9488; padding: 14px; border-radius: 10px; display: inline-block;">
                        <i class="fa-solid fa-fingerprint fs-1" style="color: #0d9488; font-size: 3rem;"></i>
                        <div style="font-size: 9px; font-weight: bold; color: #0f766e; margin-top: 6px; font-family: 'Outfit';">BIOMETRIC OK</div>
                    </div>
                </div>
            </div>
            تعتبر هذه البطاقة وثيقة رسمية لإثبات البصمة الموحدة عبر منصات قطاع التكوين المهني.
        <?php elseif ($docType === 'fiche_paie'): ?>
            كشف راتب تفصيلي خاص بالموظف(ة):
            <br>
            &nbsp;&nbsp;&nbsp;الاسم واللقب: <span class="v"><?= htmlspecialchars($nomAr) ?></span>
            &nbsp;| رقم التعريف الوطني (NIN): <span class="v" style="font-family:'Outfit';"><?= htmlspecialchars($matricule) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;الرتبة / الوظيفة: <span class="v"><?= htmlspecialchars($specAr) ?></span>
            &nbsp;| المؤسسة: <span class="v"><?= htmlspecialchars($etabNom) ?></span>
            <br>
            <table class="notes-table" style="margin-top:20px;">
                <thead>
                    <tr>
                        <th style="text-align:right;">العنصر (Rubrique)</th>
                        <th>القاعدة (Base)</th>
                        <th>المعدل (Taux)</th>
                        <th>المستحقات (Gain)</th>
                        <th>الخصومات (Retenue)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:right;font-weight:600;">الراتب الأساسي (Traitement de Base)</td>
                        <td>45,000.00</td>
                        <td>100%</td>
                        <td>45,000.00</td>
                        <td>---</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;font-weight:600;">تعويض الخبرة المهنية (IEPR)</td>
                        <td>45,000.00</td>
                        <td>10%</td>
                        <td>4,500.00</td>
                        <td>---</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;font-weight:600;">تعويض التأهيل (Indemnité de Qualification)</td>
                        <td>45,000.00</td>
                        <td>25%</td>
                        <td>11,250.00</td>
                        <td>---</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;font-weight:600;">تعويض دعم نشاط التكوين (Indemnité de Support)</td>
                        <td>---</td>
                        <td>---</td>
                        <td>6,500.00</td>
                        <td>---</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;font-weight:600;">اقتطاع الضمان الاجتماعي (Sécurité Sociale 9%)</td>
                        <td>67,250.00</td>
                        <td>9%</td>
                        <td>---</td>
                        <td>6,052.50</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;font-weight:600;">الضريبة على الدخل الإجمالي (IRG)</td>
                        <td>61,197.50</td>
                        <td>Barème</td>
                        <td>---</td>
                        <td>4,200.00</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:left;color:#482b8f;font-weight:800;">المجموع (Totaux)</td>
                        <td style="font-weight:800;color:#15803d;font-family:'Outfit';">67,250.00 د.ج</td>
                        <td style="font-weight:800;color:#ef4444;font-family:'Outfit';">10,252.50 د.ج</td>
                    </tr>
                    <tr style="background:#f0ecff;">
                        <td colspan="3" style="text-align:left;color:#482b8f;font-weight:800;font-size:16px;">صافي الدفع (Net à Payer)</td>
                        <td colspan="2" style="font-size:18px;color:#482b8f;font-family:'Outfit';font-weight:800;text-align:center;">
                            56,997.50 د.ج (DZ)
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>

    <!-- ── Footer ── -->
    <?php if ($docType === 'decision_isqat'): ?>
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 50px; padding-top: 20px; font-family:'Cairo'; font-size: 14px; color: #000; direction: rtl;">
            <div class="qr-badge" style="border: 1px dashed #cbd5e1; background: #f8fafc; padding: 6px 12px; border-radius: 8px; display: flex; align-items: center; gap: 8px; box-shadow: none;">
                @php
                     $verificationUrl = url('/verify?code=' . urlencode($codeVer));
                     $qrCodeUrl = url('/api/qrcode?data=' . urlencode($verificationUrl));
                @endphp
                <img src="{{ $qrCodeUrl }}" alt="رمز التحقق الرقمي" style="width: 50px; height: 50px; border-radius: 4px; border: 1px solid #cbd5e1;" />
                <div style="text-align: right;">
                    <div style="font-weight:700; font-size:9px; color:#475569;">كود التحقق: <?= htmlspecialchars($codeVer) ?></div>
                </div>
            </div>
            
            <div style="text-align: right; line-height: 2.2;">
                <?php
                $city = '';
                if (!empty($details['etab_nom'])) {
                    if (preg_match('/العلمة/u', $details['etab_nom'])) {
                        $city = 'العلمة';
                    } elseif (preg_match('/يوب/u', $details['etab_nom'])) {
                        $city = 'يوب';
                    } else {
                        $words = explode(' ', trim($details['etab_nom']));
                        $city = end($words);
                    }
                }
                $wilaya = $details['wilaya_nom'] ?? '';
                $issuePlace = trim($city . '_' . $wilaya, '_');
                ?>
                حرر بـ : <strong><?= htmlspecialchars($issuePlace) ?></strong> في : <strong><?= date('Y/m/d', strtotime($docDate)) ?></strong>
                <br>
                <div style="text-align: center; margin-top: 10px; font-weight: 700; width: 200px;">
                    مدير (ة) المؤسسة
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="doc-footer">
            <!-- Verification badge with dynamic QR Code -->
            <div class="qr-badge">
                @php
                     $verificationUrl = url('/verify?code=' . urlencode($codeVer));
                     $qrCodeUrl = url('/api/qrcode?data=' . urlencode($verificationUrl));
                @endphp
                <div>
                    <img src="{{ $qrCodeUrl }}" alt="رمز التحقق الرقمي" />
                </div>
                <div style="text-align: right; flex: 1;">
                    <div style="font-weight:800; color:#15803d; margin-bottom:4px; font-size:12px;">بوابة التحقق الرقمي</div>
                    <div style="font-size:10px; color:#475569;">امسح الرمز أو أدخل الكود في البوابة للتحقق من صحة وموثوقية الوثيقة.</div>
                    <div>
                        <span class="code-ver"><?= htmlspecialchars($codeVer) ?></span>
                    </div>
                </div>
            </div>

            <!-- Signature -->
            <div class="sig-block">
                <div>حرر بتاريخ: <strong><?= date('d/m/Y', strtotime($docDate)) ?></strong></div>
                <div class="sig-line"></div>
                <div>توقيع وختم المدير</div>
                <div style="font-size:11px; color:#94a3b8; margin-top:4px; font-family:'Outfit';">Signature &amp; Cachet du Directeur</div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($docType === 'decision_isqat'): ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // changeDocType — reload same URL with new doc= param
    function changeDocType(newType) {
        var url = new URL(window.location.href);
        url.searchParams.set('doc', newType);
        url.searchParams.set('direct', '1');
        window.location.href = url.toString();
    }

    // Auto-open print dialog after fonts render (skip if just switching types)
    window.addEventListener('DOMContentLoaded', () => {
        if (!window.location.search.includes('no_autoprint')) {
            setTimeout(() => { window.print(); }, 600);
        }
    });
</script>
</body>
</html>

@endsection
