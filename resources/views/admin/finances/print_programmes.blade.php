<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طباعة — البرامج الميزانياتية</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Cairo',sans-serif; font-size:11px; color:#111; direction:rtl; padding:20px; }
.print-header { text-align:center; margin-bottom:20px; border-bottom:2px solid #1a4fa8; padding-bottom:15px; }
.print-header h1 { font-size:16px; color:#0f2752; font-weight:900; }
.print-header p  { font-size:10px; color:#555; margin-top:4px; }
table { width:100%; border-collapse:collapse; margin-bottom:25px; }
thead th { background:#1a4fa8; color:#fff; padding:6px 10px; font-size:10px; text-align:right; }
tbody tr:nth-child(even) { background:#f4f7ff; }
tbody td { padding:5px 10px; border-bottom:1px solid #e0e6f0; font-size:10px; }
.prog-section h2 { font-size:12px; color:#0f2752; margin:15px 0 6px; padding-bottom:4px; border-bottom:1.5px solid #1a4fa8; }
@media print { @page { margin:1cm; } button { display:none !important; } }
</style>
</head>
<body>
<div class="print-header">
    <h1>البرامج والبرامج الفرعية — الميزانية</h1>
    <p>تاريخ الطباعة: {{ date('Y/m/d H:i') }} &nbsp;|&nbsp; منصة SGFEP</p>
</div>

@foreach($programmes as $prog)
@php $prog = (array)$prog; @endphp
<div class="prog-section">
    <h2>{{ $prog['nom'] }} — {{ $prog['nom_fr'] ?? '' }} ({{ $prog['code'] ?? '' }})</h2>
    @php
        $subs = array_filter((array)$sousprog, fn($s) => (int)(is_array($s)?$s['programme_id']:$s->programme_id) === (int)$prog['id']);
    @endphp
    @if(count($subs) > 0)
    <table>
        <thead>
            <tr>
                <th>الاسم بالعربية</th>
                <th>Nom Français</th>
                <th>الرمز</th>
                <th>الرمز الكامل</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subs as $sp)
            @php $sp = (array)$sp; @endphp
            <tr>
                <td>{{ $sp['nom'] ?? '—' }}</td>
                <td>{{ $sp['nom_fr'] ?? '—' }}</td>
                <td><code>{{ $sp['code'] ?? '—' }}</code></td>
                <td>{{ $sp['code_complet'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color:#888;font-size:10px;padding:6px 0;">لا توجد برامج فرعية</p>
    @endif
</div>
@endforeach

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
