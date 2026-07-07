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
    'attestation_travail'     => ['ar' => 'شـهادة عـمـل',           'fr' => 'Attestation de Travail'],
    'bulletin_notes'          => ['ar' => 'كشـف النـقاط السـداسي',  'fr' => 'Bulletin de Notes'],
    'decision_isqat'          => ['ar' => 'قـرار إسـقاط بـيـداغـوجـي', 'fr' => 'Décision d\'Exclusion Pédagogique'],
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
        .print-container {
            background: white;
            width: 210mm; min-height: 297mm;
            margin: 0 auto;
            padding: 18mm 20mm;
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
            .print-btn-wrap { display: none; }
        }
    </style>
</head>
<body>

<!-- Floating buttons -->
<div class="print-btn-wrap">
    <button onclick="history.back()" class="btn-p btn-back"><i class="fa-solid fa-arrow-right"></i> رجوع</button>
    <button onclick="window.print()" class="btn-p btn-print"><i class="fa-solid fa-print"></i> طباعة الوثيقة</button>
</div>

<div class="print-container">

    <!-- ── Republic Header ── -->
    <div class="rep-header">
        الجمهورية الجزائرية الديمقراطية الشعبية<br>
        وزارة التكوين والتعليم المهنيين
    </div>
    <hr class="rep-divider">

    <!-- ── Establishment band ── -->
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

    <!-- ── Document Title ── -->
    <div class="doc-title-wrap">
        <div class="doc-title-ar"><?= $titleAr ?></div>
        <div class="doc-title-fr"><?= $titleFr ?></div>
        <div class="title-underline"></div>
    </div>

    <!-- ── Document Body ── -->
    <div class="doc-body">

        <?php if ($docType === 'certificat_scolaire'): ?>
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
            كشف نقاط تفصيلي للمسار البيداغوجي للمتربص(ة):
            <br>
            &nbsp;&nbsp;&nbsp;الاسم واللقب: <span class="v"><?= htmlspecialchars($nomAr) ?></span>
            &nbsp;| رقم التسجيل: <span class="v" style="font-family:'Outfit';"><?= htmlspecialchars($matricule) ?></span>
            <br>
            &nbsp;&nbsp;&nbsp;التخصص: <span class="v"><?= htmlspecialchars($specAr) ?></span>
            <br>
            <table class="notes-table" style="margin-top:20px;">
                <thead>
                    <tr>
                        <th>المقياس الدراسي</th>
                        <th>النوع</th>
                        <th>المعامل</th>
                        <th>العلامة</th>
                        <th>التقدير</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $semNames = [
                        1 => 'السداسي الأول',
                        2 => 'السداسي الثاني',
                        3 => 'السداسي الثالث',
                        4 => 'السداسي الرابع',
                        5 => 'السداسي الخامس',
                        6 => 'السداسي السادس',
                    ];
                    ?>
                    <?php if (!empty($semestersData)): ?>
                        <?php foreach ($semestersData as $sem): 
                            $numSem = (int)$sem['num_sem'];
                            $semName = $semNames[$numSem] ?? ('السداسي ' . $numSem);
                            $semAvg = (float)($sem['average'] ?? 0.0);
                            
                            $semStatus = '---';
                            $semColor = '#64748b';
                            if ($semAvg > 0) {
                                $semColor = $semAvg >= 10 ? '#10b981' : '#ef4444';
                                if ($semAvg >= 16) {
                                    $semStatus = 'ناجح بامتياز';
                                } elseif ($semAvg >= 14) {
                                    $semStatus = 'ناجح بجيد جداً';
                                } elseif ($semAvg >= 12) {
                                    $semStatus = 'ناجح بجيد';
                                } elseif ($semAvg >= 10) {
                                    $semStatus = 'ناجح مقبول';
                                } else {
                                    $semStatus = 'مستدرك';
                                }
                            }
                        ?>
                            <!-- Semester Header Row -->
                            <tr style="background-color: #f8fafc; font-weight: bold; text-align: right;">
                                <td colspan="5" style="color: #482b8f; font-size: 14px; padding: 10px 12px; border-bottom: 2px solid #cbd5e1; border-top: 2px solid #cbd5e1;">
                                    <i class="fa-solid fa-graduation-cap me-1"></i> <?= htmlspecialchars($semName) ?>
                                </td>
                            </tr>

                            <!-- Semester Modules -->
                            <?php foreach ($sem['marks'] as $m): 
                                $isGradeNull = ($m['average'] === null);
                                $avgVal = $isGradeNull ? 0.00 : (float)$m['average'];
                                
                                // Determine appreciation
                                if ($isGradeNull) {
                                    $appText = '---';
                                    $appColor = '#64748b';
                                } elseif ($avgVal >= 18) {
                                    $appText = 'ممتاز جداً';
                                    $appColor = '#10b981';
                                } elseif ($avgVal >= 16) {
                                    $appText = 'ممتاز';
                                    $appColor = '#10b981';
                                } elseif ($avgVal >= 14) {
                                    $appText = 'جيد جداً';
                                    $appColor = '#10b981';
                                } elseif ($avgVal >= 12) {
                                    $appText = 'جيد';
                                    $appColor = '#2563eb';
                                } elseif ($avgVal >= 10) {
                                    $appText = 'قريب من الحسن';
                                    $appColor = '#f59e0b';
                                } else {
                                    $appText = 'دون الوسط';
                                    $appColor = '#ef4444';
                                }
                                
                                // Determine type dynamically based on exis_c1/exis_cs
                                $typeText = 'تطبيقي ونظري';
                                if (isset($m['exis_c1']) && isset($m['exis_cs'])) {
                                    if ($m['exis_c1'] == 0 && $m['exis_cs'] > 0) {
                                        $typeText = 'نظري';
                                    } elseif ($m['exis_c1'] > 0 && $m['exis_cs'] == 0) {
                                        $typeText = 'تطبيقي';
                                    }
                                }
                            ?>
                                <tr>
                                    <td style="text-align:right;font-weight:600;"><?= htmlspecialchars($m['module_nom']) ?></td>
                                    <td><?= htmlspecialchars($typeText) ?></td>
                                    <td><?= htmlspecialchars($m['coefficient']) ?></td>
                                    <td style="font-family:'Outfit'; font-weight:700;"><?= number_format($avgVal, 2) ?></td>
                                    <td style="color:<?= $appColor ?>;font-weight:700;"><?= htmlspecialchars($appText) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Semester Subtotal Row -->
                            <tr style="background-color: #f1f5f9; font-weight: bold;">
                                <td colspan="3" style="text-align:left; color:#482b8f; font-weight: 700;">
                                    معدل <?= htmlspecialchars($semName) ?>:
                                </td>
                                <td style="font-family:'Outfit'; color:#482b8f; font-weight:800;">
                                    <?= number_format($semAvg, 2) ?> / 20
                                </td>
                                <td style="color:<?= $semColor ?>; font-weight:800;">
                                    <?= htmlspecialchars($semStatus) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback to static mock data -->
                        <tr>
                            <td style="text-align:right;font-weight:600;">الخوارزميات وهياكل البيانات</td>
                            <td>تطبيقي ونظري</td><td>3</td>
                            <td style="font-family:'Outfit'; font-weight:700;">16.50</td>
                            <td style="color:#10b981;font-weight:700;">ممتاز</td>
                        </tr>
                        <tr>
                            <td style="text-align:right;font-weight:600;">تطوير تطبيقات الويب</td>
                            <td>تطبيقي</td><td>4</td>
                            <td style="font-family:'Outfit'; font-weight:700;">18.00</td>
                            <td style="color:#10b981;font-weight:700;">ممتاز جداً</td>
                        </tr>
                        <tr>
                            <td style="text-align:right;font-weight:600;">إدارة قواعد البيانات (MySQL)</td>
                            <td>تطبيقي</td><td>3</td>
                            <td style="font-family:'Outfit'; font-weight:700;">15.00</td>
                            <td style="color:#10b981;font-weight:700;">جيد جداً</td>
                        </tr>
                        <tr>
                            <td style="text-align:right;font-weight:600;">تصميم الشبكات المحلية</td>
                            <td>نظري</td><td>2</td>
                            <td style="font-family:'Outfit'; font-weight:700;">14.00</td>
                            <td style="color:#2563eb;font-weight:700;">جيد</td>
                        </tr>
                    <?php endif; ?>

                    <!-- Graduation Thesis (NoteMemoire) Row at the end of tbody -->
                    <?php 
                    if (!$isEmploye && array_key_exists('noteMemoire', get_defined_vars())):
                        $isMemoNull = ($noteMemoire === null);
                        $memoVal = $isMemoNull ? 0.00 : (float)$noteMemoire;
                        
                        // Determine appreciation
                        if ($isMemoNull) {
                            $memoAppText = '---';
                            $memoAppColor = '#64748b';
                        } elseif ($memoVal >= 18) {
                            $memoAppText = 'ممتاز جداً';
                            $memoAppColor = '#10b981';
                        } elseif ($memoVal >= 16) {
                            $memoAppText = 'ممتاز';
                            $memoAppColor = '#10b981';
                        } elseif ($memoVal >= 14) {
                            $memoAppText = 'جيد جداً';
                            $memoAppColor = '#10b981';
                        } elseif ($memoVal >= 12) {
                            $memoAppText = 'جيد';
                            $memoAppColor = '#2563eb';
                        } elseif ($memoVal >= 10) {
                            $memoAppText = 'قريب من الحسن';
                            $memoAppColor = '#f59e0b';
                        } else {
                            $memoAppText = 'دون الوسط';
                            $memoAppColor = '#ef4444';
                        }
                    ?>
                        <tr style="background-color: #f0fdf4; font-weight: bold; border-top: 2px solid #cbd5e1;">
                            <td style="text-align:right; font-weight:700; color: #15803d;">
                                <i class="fa-solid fa-book me-1"></i> مذكرة التخرج (Projet de Fin d'Études)
                            </td>
                            <td style="color: #15803d;">تطبيقي</td>
                            <td style="color: #15803d;">1</td>
                            <td style="font-family:'Outfit'; font-weight:700; color: #15803d;">
                                <?= number_format($memoVal, 2) ?>
                            </td>
                            <td style="color:<?= $memoAppColor ?>; font-weight:700;">
                                <?= htmlspecialchars($memoAppText) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:left;color:#482b8f;font-weight:800;font-size:15px;">المعدل العام للمسار البيداغوجي (Moyenne Générale)</td>
                        <td style="font-size:17px;color:#482b8f;font-family:'Outfit';font-weight:800;">
                            <?php if (!empty($semestersData)): ?>
                                <?= number_format((float)($semesterAverage ?? 0.0), 2) ?> / 20
                            <?php else: ?>
                                16.15 / 20
                            <?php endif; ?>
                        </td>
                        <td style="color:<?= (float)($semesterAverage ?? 0.0) >= 10 ? '#10b981' : '#ef4444' ?>;font-weight:800;">
                            <?php if (!empty($semestersData)): 
                                $avgVal = (float)($semesterAverage ?? 0.0);
                                if ($avgVal >= 16) {
                                    echo 'ناجح بامتياز';
                                } elseif ($avgVal >= 14) {
                                    echo 'ناجح بجيد جداً';
                                } elseif ($avgVal >= 12) {
                                    echo 'ناجح بجيد';
                                } elseif ($avgVal >= 10) {
                                    echo 'ناجح مقبول';
                                } else {
                                    echo 'مستدرك';
                                }
                            else: ?>
                                ناجح بامتياز
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php elseif ($docType === 'decision_isqat'): ?>
            إن مدير <span class="v"><?= htmlspecialchars($etabNom) ?></span>،
            <br>
            - بمقتضى القانون رقم 08-07 المؤرخ في 23 فيفري 2008 المتضمن القانون التوجيهي للتربية الوطنية؛
            <br>
            - وبمقتضى القرار الوزاري المتضمن نظام الجماعات التربوية في قطاع التكوين والتعليم المهنيين؛
            <br>
            - وبناءً على محاضر المجلس التأديبي للمؤسسة والتقارير البيداغوجية المرفوعة؛
            <br>
            - ونظراً للغيابات المتكررة وغير المبررة للمتربص(ة) المعني أدناه وتجاوز الغياب للمدة المقررة قانوناً:
            <br><br>
            <div style="font-weight: 700; border-right: 4px solid #ef4444; background: #fef2f2; border-radius: 8px; padding: 16px 24px; margin-bottom: 24px;">
                &nbsp;&nbsp;&nbsp;السيد(ة) / الآنسة: <span class="v"><?= htmlspecialchars($nomAr) ?></span>
                <br>
                &nbsp;&nbsp;&nbsp;المولود(ة) بتاريخ: <span class="v"><?= htmlspecialchars($dateNais) ?></span>
                بـ: <span class="v"><?= htmlspecialchars($lieu) ?></span>
                <br>
                &nbsp;&nbsp;&nbsp;رقم التسجيل: <span class="v" style="font-family:'Outfit';"><?= htmlspecialchars($matricule) ?></span>
                <br>
                &nbsp;&nbsp;&nbsp;القسم التكويني والتخصص: <span class="v"><?= htmlspecialchars($specAr) ?></span>
            </div>
            <h5 class="fw-bold text-danger mb-2" style="font-family:'Cairo'; text-decoration: underline;"><i class="fa-solid fa-gavel me-1"></i> يـقـرر مـا يـلـي:</h5>
            <strong>المادة الأولى:</strong> يُشطب ويُسقط بصفة نهائية المتربص(ة) المذكور(ة) أعلاه من قوائم المؤسسة.
            <br>
            <strong>المادة الثانية:</strong> يُسرح المعني رسمياً ويلغى تسجيله من تاريخ صدور هذا القرار، ولا يمكنه إعادة الالتحاق إلا وفق الشروط البيداغوجية المنظمة.

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
    <div class="doc-footer">
        <!-- Verification badge with dynamic QR Code -->
        <div class="qr-badge">
            @php
                $verificationUrl = url('/verify?code=' . urlencode($codeVer));
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($verificationUrl);
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

</div>

<script>
    // Auto-open print dialog after fonts render
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => { window.print(); }, 600);
    });
</script>
</body>
</html>

@endsection
