@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array  $offre
 * @var array  $config
 * @var int    $semestre
 * @var array  $matieres
 * @var array  $rows
 * @var int    $nbAdmis
 * @var int    $nbAjournes
 * @var int    $nbExclus
 * @var int    $total
 * @var float  $txReuss
 */

$diplomeLabels = [
    'CAP' => 'شهادة الكفاءة المهنية',
    'BEP' => 'شهادة التعليم المهني',
    'BP'  => 'شهادة المهارة المهنية',
    'BTS' => 'شهادة تقني سامي',
    'TS'  => 'شهادة تقني',
    'BFCA'=> 'شهادة مكوّن متخصص',
];

$semestreLabel = [1=>'الأول',2=>'الثاني',3=>'الثالث',4=>'الرابع',5=>'الخامس'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>محضر المداولات الرسمي – السداسي <?= $semestre ?> – <?= htmlspecialchars($offre['spec_ar']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Cairo',sans-serif; background:#f8fafc; color:#0f172a; direction:rtl; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.print-wrapper { max-width:1100px; margin:2rem auto; background:#fff; border-radius:16px; padding:2.5rem; box-shadow:0 10px 40px rgba(0,0,0,0.05); }
.no-print { margin-bottom:1.5rem; display:flex; gap:0.8rem; justify-content:space-between; align-items:center; }
.btn { padding:0.6rem 1.4rem; border-radius:30px; font-weight:700; font-size:0.9rem; cursor:pointer; border:none; transition:all 0.2s; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; }
.btn-primary { background:#0284c7; color:#fff; }
.btn-primary:hover { background:#0369a1; }
.btn-secondary { background:#f1f5f9; color:#334155; }
.btn-secondary:hover { background:#e2e8f0; }
.btn-print { background:#ffffff !important; color:#0284c7 !important; border:1px solid #0284c7 !important; }
.btn-print:hover { background:#f0f9ff !important; }
/* Sovereign Header */
.doc-sovereign-header { text-align:center; padding-bottom:1.2rem; border-bottom:3px double #0f172a; }
.flag-stripe { height:6px; background:linear-gradient(to right, #0284c7, #0f172a); border-radius:6px 6px 0 0; margin-bottom:1.5rem; }
.gov-title { font-size:1.05rem; font-weight:800; color:#0f172a; line-height:1.5; }
.gov-subtitle { font-size:0.85rem; font-weight:500; color:#475569; font-family:'Outfit'; }
/* Document Title */
.doc-title-block { background:linear-gradient(135deg,#0284c7,#0f172a); color:#fff; border-radius:12px; padding:1.2rem 2rem; margin:1.5rem 0; text-align:center; position:relative; }
.doc-title-block h2 { font-size:1.35rem; font-weight:800; margin-bottom:0.2rem; }
.doc-title-block p { font-size:0.85rem; opacity:0.8; font-family:'Outfit'; }
/* Info Grid */
.info-grid { display:grid; grid-template-columns:1.5fr 1fr; gap:0.6rem 1.5rem; margin-bottom:1.5rem; font-size:0.88rem; background:#f8fafc; padding:1rem; border-radius:12px; border:1px solid #e2e8f0; }
.info-row { display:flex; gap:0.4rem; align-items:baseline; }
.info-label { font-weight:700; color:#475569; min-width:130px; }
.info-value { font-weight:600; color:#0f172a; }
/* Deliberation Table */
.delib-table { width:100%; border-collapse:collapse; font-size:0.8rem; margin-bottom:1.5rem; }
.delib-table th { background:#1e293b; color:#fff; padding:0.6rem 0.4rem; font-weight:700; text-align:center; border:1px solid #334155; }
.delib-table td { border:1px solid #cbd5e1; padding:0.5rem; vertical-align:middle; text-align:center; }
.delib-table tr:nth-child(even) { background:#f8fafc; }
.delib-table .st-name { text-align:right; font-weight:600; padding-right:10px; }
.delib-table .num { font-family:'Outfit'; font-weight:600; }
.delib-table .grade-badge { padding:2px 6px; border-radius:10px; font-weight:700; font-size:0.75rem; }
.badge-admis { background-color:#e0f2fe; color:#0369a1; }
.badge-ajourne { background-color:#f1f5f9; color:#475569; }
.badge-exclu { background-color:#e2e8f0; color:#1e293b; }
.grade-eliminat { background:#f1f5f9; color:#0f172a; font-weight:700; border:1px solid #cbd5e1; }
.grade-pass { color:#0284c7; font-weight:700; }
.grade-fail { color:#475569; }
/* Decision Custom Colors */
.decision-admis { color:#0284c7 !important; }
.decision-ajourne { color:#475569 !important; }
.decision-exclu { color:#94a3b8 !important; }
.decision-abandon { color:#cbd5e1 !important; }
/* Stats Cards */
.stats-container { display:grid; grid-template-columns:repeat(5,1fr); gap:0.8rem; margin-bottom:1.8rem; }
.stat-card { background:#fff; border:1px solid #cbd5e1; border-radius:10px; padding:0.8rem; text-align:center; box-shadow:0 2px 4px rgba(0,0,0,0.02); }
.stat-card .val { font-size:1.4rem; font-weight:800; font-family:'Outfit'; color:#0284c7; }
.stat-card .lbl { font-size:0.72rem; color:#475569; font-weight:600; margin-top:0.1rem; }
/* Jury section */
.jury-section { border:1px solid #cbd5e1; border-radius:12px; padding:1.2rem; background:#f8fafc; margin-bottom:1.5rem; }
.jury-title { font-weight:700; font-size:0.88rem; color:#0f172a; margin-bottom:1rem; border-bottom:1px solid #cbd5e1; padding-bottom:0.4rem; }
.jury-cols { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.5rem; text-align:center; font-size:0.82rem; }
.jury-space { height:55px; border-bottom:1px dashed #94a3b8; width:80%; margin:0.5rem auto 0.2rem; }
/* Footer */
.doc-footer { text-align:center; font-size:0.7rem; color:#94a3b8; font-family:'Outfit'; margin-top:1.5rem; }
/* Print */
@media print {
    body { background:#fff !important; }
    .no-print { display:none !important; }
    .print-wrapper { box-shadow:none !important; margin:0 !important; border-radius:0 !important; width:100% !important; max-width:100% !important; padding:0 !important; }
    .stat-card { border:1px solid #000 !important; }
    .delib-table th { background:#1e293b !important; color:#fff !important; -webkit-print-color-adjust: exact; }
    .btn-print {
        background: #ffffff !important;
        color: #000000 !important;
        border: 1px solid #000000 !important;
    }
    select.print-select {
        border: none !important;
        background: transparent !important;
        color: #000 !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        pointer-events: none !important;
        padding: 0 !important;
        text-align: center !important;
        font-weight: bold !important;
    }
}
</style>
</head>
<body>
<form action="{{ route('grades.deliberation.confirm') }}" method="POST">
    @csrf
    <input type="hidden" name="offre_id" value="<?= \App\Helpers\SecureIdHelper::encrypt($offre['id']) ?>">
    <input type="hidden" name="semestre" value="<?= $semestre ?>">
    <div class="print-wrapper">

    <!-- Print Navigation -->
    <div class="no-print">
        <a href="/dashboard/grades" class="btn btn-secondary">
            ← العودة للوحة البيداغوجية
        </a>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <a href="/dashboard/grades/input?offre_id=<?= $offre['id'] ?>&semestre=<?= $semestre ?>" class="btn btn-secondary">تعديل النقاط</a>
            <button onclick="exportTableToExcel('delibTable', 'proces_verbal_deliberation.xls')" class="btn btn-secondary" style="border: 1px solid #cbd5e1;">
                Excel
            </button>
            <button onclick="exportTableToCSV('delibTable', 'proces_verbal_deliberation.csv')" class="btn btn-secondary" style="border: 1px solid #cbd5e1;">
                CSV
            </button>
            <button onclick="window.print()" class="btn btn-print">
                🖨️ طباعة محضر المداولات
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check-double"></i> مصادقة وترقية المتربصين
            </button>
        </div>
    </div>

    <!-- Flag Stripe -->
    <div class="flag-stripe"></div>

    <!-- Sovereign Header -->
    <div class="doc-sovereign-header">
        <p class="gov-title">الجمهورية الجزائرية الديمقراطية الشعبية</p>
        <p class="gov-subtitle">République Algérienne Démocratique et Populaire</p>
        <p class="gov-title" style="margin-top:0.4rem;">وزارة التكوين والتعليم المهنيين</p>
        <p class="gov-subtitle">Ministère de la Formation et de l'Enseignement Professionnels</p>
        <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-top:1rem;">
            <span><strong>المؤسسة:</strong> <?= htmlspecialchars($offre['etab_ar']) ?></span>
            <span><strong>Établissement:</strong> <?= htmlspecialchars($offre['etab_fr']) ?></span>
        </div>
    </div>

    <!-- Title Block -->
    <div class="doc-title-block">
        <h2>محضر المداولات الرسمي لنتائج القسم – السداسي <?= $semestreLabel[$semestre] ?? $semestre ?></h2>
        <p>Procès-Verbal Officiel de Délibération du Semestre <?= $semestre ?> | <?= $diplomeLabels[$offre['diplome_vise']] ?? $offre['diplome_vise'] ?></p>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-row">
            <span class="info-label">الشعبة والتخصص:</span>
            <span class="info-value text-primary" style="color:#0284c7;"><?= htmlspecialchars($offre['spec_ar']) ?> (<?= htmlspecialchars($offre['spec_code']) ?>)</span>
        </div>
        <div class="info-row">
            <span class="info-label">نمط التكوين:</span>
            <span class="info-value"><?= $offre['mode_formation'] === 'apprentissage' ? 'تمهين (Apprentissage)' : 'حضوري (Résidentiel)' ?></span>
        </div>
        <div class="info-row">
            <span class="info-label" style="font-family:'Outfit'">Spécialité (Fr):</span>
            <span class="info-value" style="font-family:'Outfit'"><?= htmlspecialchars($offre['spec_fr']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">دورة التكوين:</span>
            <span class="info-value"><?= date('Y', strtotime($offre['date_debut'])) ?> / <?= date('Y', strtotime($offre['date_fin'])) ?></span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="val" style="color:#0f172a;"><?= $total ?></div>
            <div class="lbl">إجمالي المتربصين<br>Inscrits</div>
        </div>
        <div class="stat-card" style="border-color:#bae6fd;">
            <div class="val" style="color:#0284c7;"><?= $nbAdmis ?></div>
            <div class="lbl">المقبولون (الناجحون)<br>Admis</div>
        </div>
        <div class="stat-card" style="border-color:#e2e8f0;">
            <div class="val" style="color:#475569;"><?= $nbAjournes ?></div>
            <div class="lbl">المؤجلون (الاستدراك)<br>Ajournés</div>
        </div>
        <div class="stat-card" style="border-color:#e2e8f0;">
            <div class="val" style="color:#94a3b8;"><?= $nbExclus ?></div>
            <div class="lbl">المقصون (إقصائية)<br>Exclus</div>
        </div>
        <div class="stat-card" style="background:#0f172a;border-color:#0f172a;">
            <div class="val" style="color:#fff;"><?= $txReuss ?>%</div>
            <div class="lbl" style="color:rgba(255,255,255,0.85)">نسبة النجاح العامة<br>Taux de Réussite</div>
        </div>
    </div>

    <!-- Deliberation Grid Table -->
    <table class="delib-table" id="delibTable">
        <thead>
            <tr>
                <th style="width:4%;">رتبة</th>
                <th style="width:12%;">رقم التسجيل</th>
                <th style="width:22%;text-align:right;">الاسم واللقب / Nom & Prénom</th>
                <th style="width:4%;">جنس</th>
                <?php foreach ($matieres as $m): ?>
                    <th style="font-size:0.72rem;" title="<?= htmlspecialchars($m['libelle_ar']) ?>">
                        <?= htmlspecialchars($m['code'] ?? 'M') ?><br>
                        <small style="opacity:0.85;">معامل: <?= $m['coefficient'] ?></small>
                    </th>
                <?php endforeach; ?>
                <th style="width:7%;background:#0f172a;border-color:#0f172a;">م. السداسي</th>
                <th style="width:10%;">القرار النهائي</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= 6 + count($matieres) ?>" style="padding:3rem;" class="text-muted">
                        لا يوجد متربصين مسجلين في هذا الفوج أو لم يتم رصد أي نقاط بعد.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="num"><?= $row['rang'] ?></td>
                        <td class="num"><code><?= htmlspecialchars($row['matricule']) ?></code></td>
                        <td class="st-name"><?= htmlspecialchars($row['nom_ar']) ?></td>
                        <td><?= $row['sexe'] === 'ذكر' ? 'ذ' : 'أ' ?></td>
                        <?php foreach ($matieres as $m): ?>
                            <?php 
                            $note = $row['marks'][$m['id']] ?? null; 
                            $isElim = ($note !== null && $note < 5.00 && $note > 0);
                            ?>
                            <td class="num <?= $isElim ? 'grade-eliminat' : ($note >= 10 ? 'grade-pass' : 'grade-fail') ?>">
                                <?= $note !== null ? number_format($note, 2) : '—' ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="num fw-bold" style="font-size:0.88rem;background:#f8fafc;"><?= number_format($row['average'], 2) ?></td>
                        <td>
                            <select name="decisions[<?= $row['id'] ?>]" class="form-select form-select-sm rounded-pill fw-bold text-center print-select" style="font-size:0.75rem; padding: 2px 10px; min-width: 120px; border-color: #cbd5e1;">
                                <option value="مقبول" <?= $row['decision'] === 'مقبول' ? 'selected' : '' ?> class="decision-admis fw-bold">مقبول / Admis</option>
                                <option value="مؤجل" <?= $row['decision'] === 'مؤجل' ? 'selected' : '' ?> class="decision-ajourne fw-bold">مؤجل / Ajourné</option>
                                <option value="مقصى" <?= $row['decision'] === 'مقصى' ? 'selected' : '' ?> class="decision-exclu fw-bold">مقصى / Exclu</option>
                                <option value="تخلى" class="decision-abandon fw-bold">تخلى / Abandon</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Deliberation Board Jury Section -->
    <div class="jury-section">
        <h6 class="jury-title"><i class="fa-solid fa-stamp"></i> أعضاء لجنة المداولات البيداغوجية الرسمية للمجلس:</h6>
        <div class="jury-cols">
            <div>
                <strong>الأساتذة المكوّنون (هيئة التدريس)</strong>
                <p style="color:#64748b;font-size:0.75rem;">Membres Enseignants / Formateurs</p>
                <div class="jury-space"></div>
                <small class="text-muted">(توقيع وتأشير)</small>
            </div>
            <div>
                <strong>المدير الفرعي للدراسات والتربصات</strong>
                <p style="color:#64748b;font-size:0.75rem;">Directeur des Études / Adjoint</p>
                <div class="jury-space"></div>
                <small class="text-muted">(توقيع وتأشير)</small>
            </div>
            <div>
                <strong>رئيس المجلس (مدير المؤسسة)</strong>
                <p style="color:#64748b;font-size:0.75rem;">Président du Jury / Directeur</p>
                <div class="jury-space"></div>
                <small class="text-muted">(توقيع وختم بيداغوجي)</small>
            </div>
        </div>
    </div>

    <!-- Footer metadata -->
    <div class="doc-footer">
        نظام تسيير وبوابة المداولات الإلكترونية الموحدة – SGFEP v2026 | رمز المداولة: PV-<?= strtoupper(substr(md5($offre['id'].$semestre), 0, 8)) ?> | تاريخ الانعقاد: <?= date('d/m/Y') ?>
    </div>
</form>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>

@endsection
