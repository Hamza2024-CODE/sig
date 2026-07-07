@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array  $t          معلومات المتربص
 * @var array  $marks      قائمة المواد والنقاط
 * @var int    $semestre
 * @var float  $avg
 * @var string $mention
 * @var string $decision
 * @var array  $config
 * @var float  $totalCoef
 * @var float  $totalPts
 */

$mentionLabels = [
    'excellent'  => ['ar' => 'ممتاز',          'fr' => 'Excellent',   'color' => 'success'],
    'tres_bien'  => ['ar' => 'جيد جداً',        'fr' => 'Très Bien',   'color' => 'primary'],
    'bien'       => ['ar' => 'جيد',             'fr' => 'Bien',        'color' => 'info'],
    'assez_bien' => ['ar' => 'حسن',             'fr' => 'Assez Bien',  'color' => 'secondary'],
    'passable'   => ['ar' => 'مقبول',           'fr' => 'Passable',    'color' => 'warning'],
    'ajourne'    => ['ar' => 'مؤجل',            'fr' => 'Ajourné',     'color' => 'danger'],
];
$ml = $mentionLabels[$mention] ?? $mentionLabels['passable'];

$diplomeLabels = [
    'CAP' => 'شهادة الكفاءة المهنية',
    'BEP' => 'شهادة التعليم المهني',
    'BP'  => 'شهادة المهارة المهنية',
    'BTS' => 'شهادة تقني سامي',
    'TS'  => 'شهادة تقني',
    'BFCA'=> 'شهادة مكوّن متخصص',
];

$typeLabels = [
    'theorique'    => ['ar' => 'مادة نظرية',    'fr' => 'Théorique'],
    'tp'           => ['ar' => 'أعمال تطبيقية', 'fr' => 'Travaux Pratiques'],
    'stage_pratique'=> ['ar' => 'تربص ميداني',  'fr' => 'Stage Pratique'],
    'memoire'      => ['ar' => 'مذكرة التخرج',  'fr' => 'Mémoire de fin de formation'],
    'oral'         => ['ar' => 'مرافعة شفهية',  'fr' => 'Soutenance Orale'],
    'projet_fin'   => ['ar' => 'مشروع نهاية',   'fr' => 'Projet de Fin de Formation'],
];

$semestreLabel = [1=>'الأول',2=>'الثاني',3=>'الثالث',4=>'الرابع',5=>'الخامس'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>كشف النقاط الرسمي – <?= htmlspecialchars($t['nom_ar'].' '.$t['prenom_ar']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Cairo',sans-serif; background:#f8fafc; color:#0f172a; direction:rtl; }
.print-wrapper { max-width:860px; margin:2rem auto; background:#fff; border-radius:16px; padding:2.5rem 2.5rem 3rem; box-shadow:0 10px 40px rgba(0,0,0,0.06); }
.no-print { margin-bottom:1.5rem; display:flex; gap:0.8rem; justify-content:space-between; align-items:center; }
.btn { padding:0.6rem 1.4rem; border-radius:30px; font-weight:700; font-size:0.9rem; cursor:pointer; border:none; transition:all 0.2s; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; }
.btn-primary { background:#0284c7; color:#fff; }
.btn-secondary { background:#f1f5f9; color:#334155; }
/* Header */
.doc-sovereign-header { text-align:center; padding-bottom:1.2rem; border-bottom:3px double #0284c7; }
.flag-stripe { height:6px; background:linear-gradient(to right,#006233 33.3%,#fff 33.3%,#fff 66.6%,#D21034 66.6%); border-radius:6px 6px 0 0; margin-bottom:1.5rem; }
.gov-title { font-size:1.05rem; font-weight:800; color:#0f172a; line-height:1.5; }
.gov-subtitle { font-size:0.85rem; font-weight:500; color:#475569; font-family:'Outfit'; }
/* Document Title */
.doc-title-block { background:linear-gradient(135deg,#0284c7,#0ea5e9); color:#fff; border-radius:12px; padding:1.2rem 2rem; margin:1.5rem 0; text-align:center; }
.doc-title-block h2 { font-size:1.4rem; font-weight:800; margin-bottom:0.2rem; }
.doc-title-block p { font-size:0.85rem; opacity:0.85; font-family:'Outfit'; }
/* Info Grid */
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.6rem 1.5rem; margin-bottom:1.5rem; }
.info-row { display:flex; gap:0.4rem; align-items:baseline; font-size:0.88rem; }
.info-label { font-weight:700; color:#475569; min-width:120px; }
.info-value { font-weight:600; color:#0f172a; }
/* Table */
.grades-table { width:100%; border-collapse:collapse; font-size:0.85rem; margin-bottom:1.5rem; }
.grades-table th { background:#0284c7; color:#fff; padding:0.7rem 0.8rem; font-weight:700; text-align:center; }
.grades-table th:first-child { text-align:right; }
.grades-table td { border:1px solid #e2e8f0; padding:0.55rem 0.8rem; vertical-align:middle; }
.grades-table td:first-child { font-weight:600; }
.grades-table td.num { text-align:center; font-family:'Outfit'; font-weight:600; }
.grades-table tr:nth-child(even) { background:#f8fafc; }
.grade-low { color:#dc2626; font-weight:700; }
.grade-high { color:#059669; font-weight:700; }
.grade-eliminat { background:#fef2f2 !important; }
/* Summary */
.summary-box { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1.5rem; }
.sum-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; text-align:center; }
.sum-card .val { font-size:1.7rem; font-weight:900; font-family:'Outfit'; }
.sum-card .lbl { font-size:0.78rem; color:#64748b; font-weight:600; }
.decision-admis { color:#059669; }
.decision-ajourne { color:#dc2626; }
.decision-exclu { color:#7c3aed; }
/* Signatures */
.sig-section { display:flex; justify-content:space-between; align-items:center; border-top:2px solid #e2e8f0; padding-top:1.5rem; margin-top:2rem; }
.sig-box { text-align:center; }
.sig-box .sig-label { font-weight:700; font-size:0.85rem; margin-bottom:0.3rem; }
.sig-box .sig-fr { font-size:0.75rem; color:#64748b; font-family:'Outfit'; }
.sig-space { height:60px; border-bottom:1px dashed #94a3b8; width:200px; margin:0 auto 0.5rem; }
.qr-mock { width:90px; height:90px; background:repeating-conic-gradient(from 45deg,#0f172a 0 25%,#fff 0 50%) 0 0/9px 9px; border:2px solid #cbd5e1; }
/* Print */
@media print {
    body { background:#fff !important; }
    .no-print { display:none !important; }
    .print-wrapper { box-shadow:none !important; margin:0 !important; border-radius:0 !important; }
}
</style>
</head>
<body>
<div class="print-wrapper">

    <!-- Controls (no print) -->
    <div class="no-print">
        <a href="/dashboard/grades" class="btn btn-secondary">
            ← العودة للوحة التنقيط
        </a>
        <div style="display:flex;gap:0.5rem;">
            <a href="/dashboard/grades/input?offre_id=<?= $t['offre_id'] ?>&semestre=<?= $semestre ?>&matiere_id="
               class="btn btn-secondary">إدخال النقاط</a>
            <button onclick="exportTableToExcel('transcriptTable', 'releve_notes_<?= $t['numero_matricule'] ?>.xls')" class="btn btn-primary" style="background:#10b981; border:none;">
                Excel
            </button>
            <button onclick="exportTableToCSV('transcriptTable', 'releve_notes_<?= $t['numero_matricule'] ?>.csv')" class="btn btn-primary" style="background:#0ea5e9; border:none;">
                CSV
            </button>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ طباعة كشف النقاط
            </button>
        </div>
    </div>

    <!-- Flag -->
    <div class="flag-stripe"></div>

    <!-- Header -->
    <div class="doc-sovereign-header">
        <p class="gov-title">الجمهورية الجزائرية الديمقراطية الشعبية</p>
        <p class="gov-subtitle">République Algérienne Démocratique et Populaire</p>
        <p class="gov-title" style="margin-top:0.4rem;">وزارة التكوين والتعليم المهنيين</p>
        <p class="gov-subtitle">Ministère de la Formation et de l'Enseignement Professionnels</p>
        <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-top:1rem;">
            <span><strong>المؤسسة:</strong> <?= htmlspecialchars($t['etab_ar']) ?></span>
            <span><strong>Établissement:</strong> <?= htmlspecialchars($t['etab_fr']) ?></span>
        </div>
    </div>

    <!-- Document Title -->
    <div class="doc-title-block">
        <h2>كشف النقاط الرسمي – السداسي <?= $semestreLabel[$semestre] ?? $semestre ?></h2>
        <p>Relevé de Notes Officiel – Semestre <?= $semestre ?> | <?= $diplomeLabels[$t['diplome_vise']] ?? $t['diplome_vise'] ?></p>
    </div>

    <!-- Student Info -->
    <div class="info-grid">
        <div class="info-row"><span class="info-label">الاسم واللقب:</span><span class="info-value"><?= htmlspecialchars($t['nom_ar'].' '.$t['prenom_ar']) ?></span></div>
        <div class="info-row"><span class="info-label" style="font-family:'Outfit'">Nom & Prénom:</span><span class="info-value" style="font-family:'Outfit'"><?= htmlspecialchars($t['prenom_fr'].' '.$t['nom_fr']) ?></span></div>
        <div class="info-row"><span class="info-label">تاريخ الميلاد:</span><span class="info-value" style="font-family:'Outfit'"><?= date('d/m/Y', strtotime($t['date_naissance'])) ?></span></div>
        <div class="info-row"><span class="info-label">NIN / رقم تعريف وطني:</span><span class="info-value" style="font-family:'Outfit'"><?= htmlspecialchars($t['nin']) ?></span></div>
        <div class="info-row"><span class="info-label">رقم التسجيل:</span><span class="info-value" style="font-family:'Outfit';color:#0284c7"><?= htmlspecialchars($t['numero_matricule']) ?></span></div>
        <div class="info-row"><span class="info-label">الشهادة المستهدفة:</span><span class="info-value"><?= $diplomeLabels[$t['diplome_vise']] ?? $t['diplome_vise'] ?> | <?= $t['mode_formation'] ?></span></div>
        <div class="info-row" style="grid-column:1/-1"><span class="info-label">التخصص:</span><span class="info-value" style="color:#0284c7"><?= htmlspecialchars($t['spec_ar']) ?> / <span style="font-family:'Outfit'"><?= htmlspecialchars($t['spec_fr']) ?></span></span></div>
    </div>

    <!-- Grades Table -->
    <table class="grades-table" id="transcriptTable">
        <thead>
            <tr>
                <th style="width:5%">رمز</th>
                <th style="width:28%;text-align:right">المادة / Matière</th>
                <th style="width:6%">المعامل</th>
                <th style="width:7%">الفرض1</th>
                <th style="width:7%">الفرض2</th>
                <th style="width:7%">اختبار</th>
                <th style="width:7%">استدراكي</th>
                <th style="width:8%">معدل المادة</th>
                <th style="width:9%">النقطة المرجحة</th>
                <th style="width:16%">ملاحظة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($marks as $m): ?>
            <tr class="<?= $m['est_eliminatoire'] ? 'grade-eliminat' : '' ?>">
                <td class="num"><?= htmlspecialchars($m['code']) ?></td>
                <td>
                    <strong><?= htmlspecialchars($m['libelle_ar']) ?></strong><br>
                    <small style="font-family:'Outfit';color:#64748b"><?= htmlspecialchars($m['libelle_fr']) ?></small>
                    <?php if ($m['type_matiere'] !== 'theorique'): ?>
                        <span style="font-size:0.72rem;background:#f1f5f9;padding:1px 5px;border-radius:8px;margin-right:4px">
                            <?= $typeLabels[$m['type_matiere']]['ar'] ?? $m['type_matiere'] ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($m['est_eliminatoire']): ?>
                        <span style="font-size:0.72rem;background:#fef2f2;color:#dc2626;padding:1px 5px;border-radius:8px;font-weight:700;margin-right:4px">⚠ إقصائية</span>
                    <?php endif; ?>
                </td>
                <td class="num"><?= $m['coefficient'] ?></td>
                <?php if ($m['type_matiere'] === 'memoire'): ?>
                    <td class="num" colspan="2"><?= $m['note_memoire'] !== null ? number_format($m['note_memoire'],2) : '—' ?></td>
                    <td class="num" colspan="2"><?= $m['note_soutenance'] !== null ? number_format($m['note_soutenance'],2) : '—' ?></td>
                <?php elseif ($m['type_matiere'] === 'stage_pratique'): ?>
                    <td class="num" colspan="4"><?= $m['note_stage'] !== null ? number_format($m['note_stage'],2) : '—' ?></td>
                <?php else: ?>
                    <td class="num"><?= $m['note_cc1']      !== null ? number_format($m['note_cc1'],2)      : '—' ?></td>
                    <td class="num"><?= $m['note_cc2']      !== null ? number_format($m['note_cc2'],2)      : '—' ?></td>
                    <td class="num"><?= $m['note_examen']   !== null ? number_format($m['note_examen'],2)   : '—' ?></td>
                    <td class="num <?= $m['note_rattrapage'] !== null ? 'grade-low' : '' ?>">
                        <?= $m['note_rattrapage'] !== null ? number_format($m['note_rattrapage'],2) : '—' ?>
                    </td>
                <?php endif; ?>
                <td class="num <?= $m['note_finale'] >= 10 ? 'grade-high' : 'grade-low' ?>" style="font-size:1rem">
                    <?= number_format($m['note_finale'],2) ?>
                </td>
                <td class="num"><?= number_format($m['note_ponderee'],2) ?></td>
                <td style="font-size:0.78rem;color:#475569"><?= htmlspecialchars($m['observation'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            <!-- Total -->
            <tr style="background:#f1f5f9;font-weight:700;">
                <td colspan="2" style="text-align:center">المجموع / Total</td>
                <td class="num"><?= number_format($totalCoef,2) ?></td>
                <td colspan="6" class="num" style="font-size:0.8rem;color:#64748b">
                    Σ(coef) = <?= number_format($totalCoef,2) ?> | Σ(note×coef) = <?= number_format($totalPts,2) ?>
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-box">
        <div class="sum-card">
            <div class="val" style="color:#0284c7"><?= number_format($avg,2) ?>/20</div>
            <div class="lbl">المعدل العام السداسي<br>Moyenne Générale</div>
        </div>
        <div class="sum-card">
            <div class="val badge-<?= $ml['color'] ?>" style="color:<?=
                $ml['color']==='success'?'#059669':($ml['color']==='danger'?'#dc2626':'#0284c7') ?>">
                <?= $ml['ar'] ?>
            </div>
            <div class="lbl">التقدير / Mention<br><?= $ml['fr'] ?></div>
        </div>
        <div class="sum-card">
            <div class="val <?= str_contains($decision,'مقبول') ? 'decision-admis' : 'decision-ajourne' ?>">
                <?= str_contains($decision,'مقبول') ? '✓ ناجح' : '✗ مؤجل' ?>
            </div>
            <div class="lbl">النتيجة / Résultat<br><?= str_contains($decision,'Admis') ? 'Admis' : 'Ajourné' ?></div>
        </div>
    </div>

    <!-- Nota bene -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:0.8rem 1.2rem;font-size:0.78rem;color:#475569;margin-bottom:1.2rem;">
        <strong>ملاحظة بيداغوجية:</strong>
        صيغة الحساب المعتمدة: <strong>معدل المادة = (ف1+ف2)/2 × 40% + أكبر(اختبار، استدراكي) × 60%</strong>.
        المذكرة = مذكرة×60% + مناقشة×40%.
        النقطة الإقصائية: أقل من 5/20.
        معدل النجاح: 10/20 فأكثر.
    </div>

    <!-- Signatures & QR -->
    <div class="sig-section">
        <div class="sig-box">
            <p class="sig-label">إمضاء المكوّن / المكوّنة</p>
            <p class="sig-fr">Signature du Formateur/Formatrice</p>
            <div class="sig-space"></div>
        </div>
        <div class="sig-box">
            <div class="qr-mock"></div>
            <p style="font-size:0.72rem;margin-top:0.4rem;color:#64748b;font-family:'Outfit'">
                QR-<?= strtoupper(substr(md5($t['numero_matricule'].$semestre),0,8)) ?>
            </p>
        </div>
        <div class="sig-box">
            <p class="sig-label">توقيع وختم مدير المؤسسة</p>
            <p class="sig-fr">Signature et Griffe du Directeur</p>
            <div class="sig-space"></div>
        </div>
    </div>

    <p style="text-align:center;font-size:0.72rem;color:#94a3b8;margin-top:1rem;font-family:'Outfit'">
        تم توليده رقمياً | Généré électroniquement – SGFEP v2026 | تاريخ الطباعة: <?= date('d/m/Y H:i') ?>
    </p>
</div>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>

@endsection
