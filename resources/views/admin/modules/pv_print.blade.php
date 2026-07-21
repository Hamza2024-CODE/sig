@extends('layouts.print')
@section('title', 'محضر لجنة مداولات التقييم السداسي - ' . ($type === 'apres' ? 'بعد الاستدراك' : 'قبل الاستدراك'))
@section('content')
<?php
/**
 * @var array  $offre
 * @var int    $semestre
 * @var string $type ('avant' | 'apres')
 * @var string $sectionCode
 * @var string $semDateD
 * @var string $semDateF
 * @var string $pvNumber
 * @var string $pvDate
 * @var array  $matieres
 * @var array  $rows
 * @var array  $stats
 * @var array  $juryMembers
 */

$typeText = ($type === 'apres') ? 'بعد الاستدراك' : 'قبل الاستدراك';
$titleSuffix = ($type === 'apres') ? '-بعد الاستدراك-' : '-قبل الاستدراك-';
?>

<style>
@page {
    size: A4 portrait;
    margin: 8mm 10mm;
}
@media print {
    .no-print { display: none !important; }
    .page-break { page-break-before: always !important; }
    @page { size: auto; }
}

.pv-wrapper {
    width: 100%;
    max-width: 210mm;
    margin: 0 auto;
    background: #fff;
    padding: 10mm 12mm;
    font-family: 'Cairo', sans-serif;
    color: #000;
    direction: rtl;
}

/* Page 1 Portrait Layout */
.pv-header-top {
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.6;
    margin-bottom: 5px;
}
.pv-subhead {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    font-size: 11.5px;
    font-weight: 700;
    margin-bottom: 12px;
    line-height: 1.6;
}
.pv-main-title {
    text-align: center;
    margin: 10px 0 14px;
}
.pv-main-title h1 {
    font-size: 21px;
    font-weight: 800;
    text-decoration: underline;
    margin-bottom: 2px;
}
.pv-main-title .sub-tag {
    font-size: 14px;
    font-weight: 700;
}

.pv-meta-grid {
    font-size: 12px;
    font-weight: 700;
    line-height: 2;
    margin-bottom: 12px;
}
.pv-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Statistics Table */
.pv-table-stats {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 11.5px;
}
.pv-table-stats td, .pv-table-stats th {
    border: 1px solid #000;
    padding: 4px 6px;
    text-align: center;
}
.pv-table-stats td.lbl {
    font-weight: 700;
    text-align: right;
    width: 26%;
    background: #fafafa;
}
.pv-table-stats td.val {
    font-weight: 800;
    width: 7%;
}

/* Jury Table & Inputs */
.pv-table-jury {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 20px;
}
.pv-table-jury th, .pv-table-jury td {
    border: 1px solid #000;
    padding: 3px 4px;
    text-align: center;
}
.pv-table-jury th {
    background: #e5e7eb;
    font-weight: 800;
}
.pv-table-jury tr {
    height: 28px;
}

.jury-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 3px 6px;
    font-family: 'Cairo', sans-serif;
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
    background: #fff;
    box-sizing: border-box;
}
.jury-input.jury-func {
    text-align: right;
}
.jury-input.jury-role, .jury-input.jury-name {
    text-align: center;
}

@media print {
    .jury-input {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        color: #000 !important;
        appearance: none !important;
        -webkit-appearance: none !important;
    }
}

.sign-president {
    text-align: left;
    padding-left: 40px;
    font-size: 13px;
    font-weight: 800;
    text-decoration: underline;
    margin-top: 15px;
}

/* Action Bar */
.action-bar {
    max-width: 210mm;
    margin: 15px auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #1e293b;
    color: #fff;
    padding: 10px 18px;
    border-radius: 10px;
}
.action-bar .btn-action {
    background: #0284c7;
    color: #fff;
    border: none;
    padding: 6px 16px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.action-bar .btn-action:hover {
    background: #0369a1;
}

/* Page 2 Landscape Layout */
.pv-landscape-wrapper {
    width: 100%;
    max-width: 285mm;
    margin: 0 auto;
    background: #fff;
    padding: 8mm 10mm;
    font-family: 'Cairo', sans-serif;
    color: #000;
    direction: rtl;
}
.pv-landscape-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin-bottom: 10px;
}
.pv-landscape-header .title {
    font-size: 18px;
    font-weight: 800;
}
.pv-landscape-header .meta-box {
    font-size: 12px;
    font-weight: 700;
    display: flex;
    gap: 15px;
}
.pv-landscape-header .meta-box span {
    border: 1px solid #000;
    padding: 2px 10px;
    border-radius: 4px;
}

/* Landscape Table */
.pv-landscape-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
    margin-bottom: 15px;
}
.pv-landscape-table th, .pv-landscape-table td {
    border: 1px solid #000;
    padding: 4px 3px;
    text-align: center;
}
.pv-landscape-table th {
    background: #f3f4f6;
    font-weight: 800;
}
.pv-landscape-table th.mdl-header {
    font-size: 9.5px;
    line-height: 1.2;
}
.pv-landscape-table .num {
    font-family: 'Inter', sans-serif;
    font-weight: 700;
}
.pv-landscape-table tfoot td {
    font-weight: 700;
    height: 38px;
    vertical-align: top;
    font-size: 9.5px;
}

.landscape-signatures {
    display: flex;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 800;
    margin-top: 25px;
    padding: 0 20px;
}
</style>

<!-- Action Bar (Hidden when printing) -->
<div class="action-bar no-print">
    <div>
        <a href="/dashboard/grades/deliberation?offre_id=<?= \App\Helpers\SecureIdHelper::encrypt($offre['id']) ?>&semestre=<?= $semestre ?>" class="btn-action" style="background:#475569;">
            ← العودة للمداولات
        </a>
    </div>
    <div style="display:flex; gap:10px;">
        <?php if ($type === 'avant'): ?>
            <a href="/dashboard/grades/pv-print?offre_id=<?= \App\Helpers\SecureIdHelper::encrypt($offre['id']) ?>&semestre=<?= $semestre ?>&type=apres" class="btn-action" style="background:#d97706;">
                🔄 عرض محضر بعد الاستدراك
            </a>
        <?php else: ?>
            <a href="/dashboard/grades/pv-print?offre_id=<?= \App\Helpers\SecureIdHelper::encrypt($offre['id']) ?>&semestre=<?= $semestre ?>&type=avant" class="btn-action" style="background:#d97706;">
                🔄 عرض محضر قبل الاستدراك
            </a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn-action">
            🖨️ طباعة المحضر الرسمي (الصفحتين)
        </button>
    </div>
</div>

<!-- ================= PAGE 1: PORTRAIT ================= -->
<div class="pv-wrapper">
    <!-- Top Header -->
    <div class="pv-header-top">
        الجمهورية الجزائرية الديمقراطية الشعبية<br>
        وزارة التكوين والتعليم المهنيين
    </div>

    <!-- Etab Header -->
    <div class="pv-subhead">
        <div>
            ولاية <?= htmlspecialchars($offre['wilaya_ar'] ?? 'الجزائر') ?><br>
            مديرية التكوين و التعليم المهنيين<br>
            <?= htmlspecialchars($offre['etab_ar']) ?>
        </div>
    </div>

    <!-- Main Title -->
    <div class="pv-main-title">
        <h1>محضر لجنة مداولات التقييم السداسي</h1>
        <div class="sub-tag"><?= $titleSuffix ?></div>
    </div>

    <!-- Meta Grid -->
    <div class="pv-meta-grid">
        <div class="pv-meta-row">
            <div>رقم : <strong><?= htmlspecialchars($pvNumber) ?></strong></div>
            <div>تاريخ : <strong><?= htmlspecialchars($pvDate) ?></strong></div>
        </div>
        <div class="pv-meta-row">
            <div>الاختصاص : <strong><?= htmlspecialchars($offre['spec_ar']) ?></strong></div>
            <div>النمط: <strong><?= htmlspecialchars($offre['mode_ar'] ?: 'حضوري') ?></strong></div>
        </div>
        <div class="pv-meta-row">
            <div>رمز الفرع : <strong><?= htmlspecialchars($sectionCode) ?></strong></div>
            <div>رمز الاختصاص: <strong><?= htmlspecialchars($offre['spec_code']) ?></strong></div>
            <div>مستوى التأهيل: <strong><?= htmlspecialchars($offre['niveau_qualification'] ?: '5') ?></strong></div>
        </div>
        <div class="pv-meta-row">
            <div>تاريخ بداية التكوين : <strong><?= htmlspecialchars(date('Y/m/d', strtotime($offre['date_debut']))) ?></strong></div>
            <div>تاريخ النهاية: <strong><?= htmlspecialchars(date('Y/m/d', strtotime($offre['date_fin']))) ?></strong></div>
        </div>
        <div class="pv-meta-row">
            <div>السداسي رقم: <strong><?= $semestre ?></strong></div>
            <div>من: <strong><?= $semDateD ?></strong> إلى: <strong><?= $semDateF ?></strong></div>
        </div>
    </div>

    <!-- Statistics Table -->
    <table class="pv-table-stats">
        <tbody>
            <tr>
                <td class="lbl">عدد المتكونين:</td>
                <td class="val"><?= $stats['total'] ?></td>
                <td class="lbl">منهم إناث:</td>
                <td class="val"><?= $stats['filles'] ?></td>
                <td class="lbl">منهم ذوي الاحتياجات:</td>
                <td class="val"><?= $stats['handicapes'] ?></td>
                <td class="lbl">منهم أجانب:</td>
                <td class="val"><?= $stats['etrangers'] ?></td>
            </tr>
            <tr>
                <td class="lbl">عدد الناجحين:</td>
                <td class="val"><?= $stats['admis']['total'] ?></td>
                <td class="lbl">منهم إناث:</td>
                <td class="val"><?= $stats['admis']['filles'] ?></td>
                <td class="lbl">منهم ذوي الاحتياجات:</td>
                <td class="val"><?= $stats['admis']['handicapes'] ?></td>
                <td class="lbl">منهم أجانب:</td>
                <td class="val"><?= $stats['admis']['etrangers'] ?></td>
            </tr>
            <tr>
                <td class="lbl">عدد المستدركين:</td>
                <td class="val"><?= $stats['ajournes']['total'] ?></td>
                <td class="lbl">منهم إناث:</td>
                <td class="val"><?= $stats['ajournes']['filles'] ?></td>
                <td class="lbl">منهم ذوي الاحتياجات:</td>
                <td class="val"><?= $stats['ajournes']['handicapes'] ?></td>
                <td class="lbl">منهم أجانب:</td>
                <td class="val"><?= $stats['ajournes']['etrangers'] ?></td>
            </tr>
            <tr>
                <td class="lbl">عدد المتخلين و المفصولين:</td>
                <td class="val"><?= $stats['exclus']['total'] ?></td>
                <td class="lbl">منهم إناث:</td>
                <td class="val"><?= $stats['exclus']['filles'] ?></td>
                <td class="lbl">منهم ذوي الاحتياجات:</td>
                <td class="val"><?= $stats['exclus']['handicapes'] ?></td>
                <td class="lbl">منهم أجانب:</td>
                <td class="val"><?= $stats['exclus']['etrangers'] ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Editable Jury Members Controls (Hidden when printing) -->
    <div style="margin: 14px 0 6px; display:flex; justify-content:space-between; align-items:center;" class="no-print">
        <span style="font-size:0.85rem; font-weight:800; color:#0f172a;">
            ✏️ أعضاء لجنة المداولات (حقول قابلة للتعديل والكتابة المباشرة قبل الطباعة):
        </span>
        <div style="display:flex; gap:6px;">
            <button type="button" onclick="addJuryRow()" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem; padding:2px 10px; font-weight:700;">
                ➕ إضافة عضو لجنة
            </button>
            <button type="button" onclick="clearJuryFields()" class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem; padding:2px 10px; font-weight:700;">
                🧹 تفريغ الأسماء لملئها
            </button>
        </div>
    </div>

    <!-- Jury Members Table -->
    <table class="pv-table-jury" id="juryTable">
        <thead>
            <tr>
                <th style="width: 5%;">الرقم</th>
                <th style="width: 32%;">اللقب و الاسم</th>
                <th style="width: 38%;">الرتبة و الوظيفة</th>
                <th style="width: 11%;">الصفة</th>
                <th style="width: 11%;">الملاحظة و الامضاء</th>
                <th style="width: 3%;" class="no-print"></th>
            </tr>
        </thead>
        <tbody id="juryTableBody">
            <?php foreach ($juryMembers as $idx => $m): ?>
                <tr>
                    <td class="row-num"><?= $idx + 1 ?></td>
                    <td>
                        <input type="text" class="jury-input jury-name" value="<?= htmlspecialchars($m['nom_complet']) ?>" placeholder="اللقب والاسم">
                    </td>
                    <td>
                        <input type="text" class="jury-input jury-func" value="<?= htmlspecialchars($m['fonction']) ?>" placeholder="الرتبة والوظيفة">
                    </td>
                    <td>
                        <input type="text" class="jury-input jury-role" value="<?= htmlspecialchars($m['role']) ?>" placeholder="الصفة">
                    </td>
                    <td></td>
                    <td class="no-print text-center">
                        <button type="button" onclick="deleteJuryRow(this)" style="border:none;background:none;color:#ef4444;font-weight:bold;cursor:pointer;" title="حذف العضو">×</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="sign-president">
        رئيس (ة) اللجنة
    </div>
</div>

<!-- ================= PAGE 2: LANDSCAPE ================= -->
<div class="page-break"></div>

<div class="pv-landscape-wrapper">
    <!-- Landscape Header -->
    <div class="pv-landscape-header">
        <div class="title">
            نتائج المداولات <?= $typeText ?>
        </div>
        <div class="meta-box">
            <span>السداسي <?= $semestre ?></span>
            <span>الفرع <?= htmlspecialchars($sectionCode) ?></span>
            <span>الاختصاص: <?= htmlspecialchars($offre['spec_ar']) ?></span>
        </div>
    </div>

    <!-- Landscape Table -->
    <table class="pv-landscape-table">
        <thead>
            <tr>
                <th style="width: 3%;">الرقم</th>
                <th style="width: 11%;">رقم التسجيل</th>
                <th style="width: 10%;">اللقب</th>
                <th style="width: 10%;">الاسم</th>
                <?php foreach ($matieres as $m): ?>
                    <th class="mdl-header">
                        <div><?= htmlspecialchars($m['libelle_ar']) ?></div>
                        <div style="font-size:8.5px; opacity:0.8; margin-top:2px;">مع: <?= $m['coefficient'] ?> | ن: 0 | ت: 0</div>
                    </th>
                <?php endforeach; ?>
                <th style="width: 6%;">المجموع</th>
                <th style="width: 6%;">المعدل</th>
                <th style="width: 7%;">القرار</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= $r['rang'] ?></td>
                    <td class="num"><code><?= htmlspecialchars($r['matricule']) ?></code></td>
                    <td><?= htmlspecialchars($r['nom']) ?></td>
                    <td><?= htmlspecialchars($r['prenom']) ?></td>
                    <?php foreach ($matieres as $m): ?>
                        <td class="num"><?= isset($r['marks'][$m['id']]) && $r['marks'][$m['id']] !== null ? number_format($r['marks'][$m['id']], 2) : '—' ?></td>
                    <?php endforeach; ?>
                    <td class="num fw-bold"><?= number_format($r['points'], 2) ?></td>
                    <td class="num fw-bold"><?= number_format($r['average'], 2) ?></td>
                    <td style="font-weight:700;"><?= htmlspecialchars($r['decision']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: 800; padding-right:8px;">إمضاء المكونين</td>
                <?php foreach ($matieres as $m): ?>
                    <td style="font-size: 8.5px; vertical-align: bottom; padding-bottom: 4px;">
                        <?= htmlspecialchars($m['teacher_name'] ?: '—') ?>
                    </td>
                <?php endforeach; ?>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Landscape Signatures Footer -->
    <div class="landscape-signatures">
        <div>إمضاء المكونين</div>
        <div>الأستاذ (ة) الرئيسي</div>
        <div>المسؤول البيداغوجي</div>
        <div>مدير (ة) المؤسسة</div>
    </div>
</div>

<script>
function addJuryRow() {
    const tbody = document.getElementById('juryTableBody');
    const rowCount = tbody.rows.length + 1;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="row-num">${rowCount}</td>
        <td><input type="text" class="jury-input jury-name" value="" placeholder="اللقب والاسم"></td>
        <td><input type="text" class="jury-input jury-func" value="أستاذ متخصص في التكوين و التعليم المهنيين" placeholder="الرتبة والوظيفة"></td>
        <td><input type="text" class="jury-input jury-role" value="عضو" placeholder="الصفة"></td>
        <td></td>
        <td class="no-print text-center">
            <button type="button" onclick="deleteJuryRow(this)" style="border:none;background:none;color:#ef4444;font-weight:bold;cursor:pointer;" title="حذف العضو">×</button>
        </td>
    `;
    tbody.appendChild(tr);
    reindexJuryRows();
}

function deleteJuryRow(btn) {
    const row = btn.closest('tr');
    if (row) {
        row.remove();
        reindexJuryRows();
    }
}

function reindexJuryRows() {
    const rows = document.querySelectorAll('#juryTableBody tr');
    rows.forEach((r, idx) => {
        const numTd = r.querySelector('.row-num');
        if (numTd) numTd.textContent = idx + 1;
    });
}

function clearJuryFields() {
    if (confirm('هل أنت تأكد من رغبتك في تفريغ أسماء وحقول أعضاء اللجنة لملئها يدوياً؟')) {
        document.querySelectorAll('.jury-input').forEach(input => {
            if (!input.classList.contains('jury-role')) {
                input.value = '';
            }
        });
    }
}
</script>
@endsection
