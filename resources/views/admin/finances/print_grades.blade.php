<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طباعة — المناصب المالية</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Cairo',sans-serif; font-size:11px; color:#111; direction:rtl; padding:20px; }
.print-header { text-align:center; margin-bottom:20px; border-bottom:2px solid #1a4fa8; padding-bottom:15px; }
.print-header h1 { font-size:16px; color:#0f2752; font-weight:900; margin-bottom:5px; }
.print-header p  { font-size:10px; color:#555; }
.stats-row { display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap; }
.stat-box { border:1px solid #dde3ef; border-radius:8px; padding:8px 12px; flex:1; min-width:100px; text-align:center; }
.stat-box .val { font-size:18px; font-weight:900; color:#1a4fa8; }
.stat-box .lbl { font-size:9px; color:#666; margin-top:2px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
thead th { background:#1a4fa8; color:#fff; padding:6px 8px; font-size:10px; font-weight:700; text-align:right; }
tbody tr:nth-child(even) { background:#f4f7ff; }
tbody tr:nth-child(odd)  { background:#fff; }
tbody td { padding:5px 8px; border-bottom:1px solid #dde3ef; font-size:10px; }
tfoot td { background:#e8f0fe; font-weight:900; padding:6px 8px; font-size:11px; }
.badge { display:inline-block; padding:1px 6px; border-radius:4px; font-size:9px; font-weight:700; }
.badge-green  { background:#d4edda; color:#155724; }
.badge-orange { background:#fff3cd; color:#856404; }
.badge-red    { background:#f8d7da; color:#721c24; }
.print-footer { margin-top:20px; text-align:center; font-size:9px; color:#999; border-top:1px solid #dde; padding-top:10px; }
@media print {
    @page { margin:1cm; size:A4 landscape; }
    button { display:none !important; }
}
</style>
</head>
<body>
<div class="print-header">
    <h1>تقرير المناصب المالية — كتلة الأجور</h1>
    <p>
        {{ $user['nom_complet'] ?? 'المستخدم' }} &nbsp;|&nbsp;
        المؤسسة رقم: {{ $etabId }} &nbsp;|&nbsp;
        تاريخ الطباعة: {{ date('Y/m/d H:i') }}
    </p>
</div>

<div class="stats-row">
    <div class="stat-box">
        <div class="val">{{ number_format($stats['total_postes'] ?? 0) }}</div>
        <div class="lbl">إجمالي المناصب</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format($stats['postes_occupes'] ?? 0) }}</div>
        <div class="lbl">مشغولة</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format($stats['postes_vacants'] ?? 0) }}</div>
        <div class="lbl">شاغرة</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format($stats['surplus'] ?? 0) }}</div>
        <div class="lbl">فائض</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format($stats['besoins'] ?? 0) }}</div>
        <div class="lbl">احتياجات</div>
    </div>
    <div class="stat-box">
        <div class="val">{{ number_format(($stats['depense_annuelle'] ?? 0)/1000, 1) }}k دج</div>
        <div class="lbl">النفقة السنوية</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>الرتبة</th>
            <th>Catégorie</th>
            <th>الفئة</th>
            <th>السنة</th>
            <th>إجمالي</th>
            <th>مشغول</th>
            <th>شاغر</th>
            <th>فائض</th>
            <th>احتياج</th>
            <th>الإندكس</th>
            <th>المعالجة السنوية (دج)</th>
            <th>العلاوات (دج)</th>
            <th>النفقة السنوية (دج)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($grades['data'] as $i => $g)
        @php $g = (array)$g; @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $g['grade_nom'] ?? '—' }}</strong></td>
            <td>{{ $g['grade_nom_fr'] ?? '' }}</td>
            <td>{{ $g['categorie'] ?? '—' }}</td>
            <td>{{ $g['annee'] ?? '—' }}</td>
            <td><strong>{{ number_format($g['nbr_total'] ?? 0) }}</strong></td>
            <td><span class="badge badge-green">{{ number_format($g['nbr_occupe'] ?? 0) }}</span></td>
            <td><span class="badge badge-orange">{{ number_format($g['nbr_vacant'] ?? 0) }}</span></td>
            <td><span class="badge badge-red">{{ number_format($g['nbr_surplus'] ?? 0) }}</span></td>
            <td>{{ number_format($g['nbr_besoin'] ?? 0) }}</td>
            <td>{{ $g['Indice'] ?? '—' }}</td>
            <td>{{ number_format($g['Traitementannuel'] ?? 0, 2) }}</td>
            <td>{{ number_format($g['Primeetindemnites'] ?? 0, 2) }}</td>
            <td><strong>{{ number_format($g['Depenceannuel'] ?? 0, 2) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="14" style="text-align:center;padding:20px;color:#888;">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">المجموع</td>
            <td>{{ number_format($stats['total_postes'] ?? 0) }}</td>
            <td>{{ number_format($stats['postes_occupes'] ?? 0) }}</td>
            <td>{{ number_format($stats['postes_vacants'] ?? 0) }}</td>
            <td>{{ number_format($stats['surplus'] ?? 0) }}</td>
            <td>{{ number_format($stats['besoins'] ?? 0) }}</td>
            <td colspan="3"></td>
            <td>{{ number_format($stats['depense_annuelle'] ?? 0, 2) }}</td>
        </tr>
    </tfoot>
</table>

<div class="print-footer">
    منصة SGFEP — نظام تسيير مؤسسات التكوين المهني &nbsp;|&nbsp; تم الإنشاء بتاريخ {{ date('Y-m-d H:i:s') }}
</div>

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
