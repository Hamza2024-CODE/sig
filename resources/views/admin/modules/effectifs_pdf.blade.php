<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'dejavusans', 'cairo', sans-serif;
            font-size: 10pt;
            color: #1e293b;
            direction: rtl;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .title-box {
            text-align: center;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .summary-table th {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: right;
            font-weight: bold;
            width: 25%;
        }
        .summary-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: right;
            width: 25%;
        }
        .effectifs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        .effectifs-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            border: 1px solid #1e3a8a;
            padding: 8px;
            text-align: right;
            font-weight: bold;
        }
        .effectifs-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: right;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        الجمهورية الجزائرية الديمقراطية الشعبية<br>
        وزارة التكوين والتعليم المهنيين<br>
        <span style="font-size: 9pt; font-weight: normal; font-style: italic;">Ministère de la Formation et de l'Enseignement Professionnels</span>
    </div>

    <div class="title-box">
        <div class="title">الكشف الإحصائي لتعداد المتكونين النشطين</div>
    </div>

    <table class="summary-table">
        <tr>
            <th>تعداد سنة 2024:</th>
            <td class="bold" style="color: #475569;">{{ number_format($stats['yr_2024'] ?? 0) }}</td>
            <th>تعداد سنة 2025:</th>
            <td class="bold" style="color: #2563eb;">{{ number_format($stats['yr_2025'] ?? 0) }}</td>
        </tr>
        <tr>
            <th>تعداد سنة 2026:</th>
            <td class="bold" style="color: #16a34a;">{{ number_format($stats['yr_2026'] ?? 0) }}</td>
            <th>التعداد الإجمالي الكلي:</th>
            <td class="bold" style="color: #1e3a8a;">{{ number_format($stats['total'] ?? 0) }}</td>
        </tr>
    </table>

    <h4 style="margin-bottom: 10px; color: #1e3a8a;">توزيع التعداد حسب السنوات (2024 - 2026)</h4>
    <table class="effectifs-table">
        <thead>
            <tr>
                <th style="width: 44%;">المؤسسة التكوينية</th>
                <th style="width: 14%; text-align: center;">تعداد 2024</th>
                <th style="width: 14%; text-align: center;">تعداد 2025</th>
                <th style="width: 14%; text-align: center;">تعداد 2026</th>
                <th style="width: 14%; text-align: center;">الإجمالي العام</th>
            </tr>
        </thead>
        <tbody>
            @if (empty($list))
                <tr>
                    <td colspan="5" class="center">لا توجد بيانات تعداد حالياً.</td>
                </tr>
            @else
                @foreach ($list as $item)
                    <tr>
                        <td class="bold">{{ $item['etab_nom'] }}</td>
                        <td class="center text-secondary">{{ number_format($item['yr_2024']) }}</td>
                        <td class="center text-primary bold">{{ number_format($item['yr_2025']) }}</td>
                        <td class="center text-success bold">{{ number_format($item['yr_2026']) }}</td>
                        <td class="center bold" style="color: #1e3a8a;">{{ number_format($item['total']) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>
</html>
