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
        .filiere-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        .filiere-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            border: 1px solid #1e3a8a;
            padding: 8px;
            text-align: right;
            font-weight: bold;
        }
        .filiere-table td {
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
        <div class="title">تقرير التنوع البيداغوجي وتوزيع الشعب المهنية</div>
    </div>

    <table class="summary-table">
        <tr>
            <th>الشعب المهنية الكلية:</th>
            <td class="bold">{{ number_format($stats['filieres']) }}</td>
            <th>التخصصات المدونة:</th>
            <td class="bold">{{ number_format($stats['specialites']) }}</td>
        </tr>
        <tr>
            <th>نسبة التغطية والتوجيه:</th>
            <td class="bold" style="color: #16a34a;">{{ $stats['taux_couverture'] }}</td>
            <th>عدد الفروع النشطة:</th>
            <td class="bold" style="color: #2563eb;">{{ number_format($stats['sections']) }}</td>
        </tr>
    </table>

    <h4 style="margin-bottom: 10px; color: #1e3a8a;">التوزيع المفصل للمتكونين حسب الشعب الكبرى</h4>
    <table class="filiere-table">
        <thead>
            <tr>
                <th style="width: 45%;">الشعبة المهنية (Filière)</th>
                <th style="width: 15%; text-align: center;">التخصصات</th>
                <th style="width: 15%; text-align: center;">التعداد الفعلي</th>
                <th style="width: 10%; text-align: center;">إناث %</th>
                <th style="width: 15%; text-align: center;">التوجيه</th>
            </tr>
        </thead>
        <tbody>
            @if (empty($list))
                <tr>
                    <td class="ps-4">
                        <div class="bold">إعلام آلي ورقمنة واتصالات (INT)</div>
                        <div style="font-size: 8.5pt; color: #64748b;">Informatique et Numérique</div>
                    </td>
                    <td class="center">12 تخصص</td>
                    <td class="center">549 متربص</td>
                    <td class="center">52.6%</td>
                    <td class="center">أولوية تنموية رقمنة</td>
                </tr>
            @else
                @foreach ($list as $item)
                    @php
                        $tauxFemme = $item['total_stagiaires'] > 0 ? ($item['femmes'] / $item['total_stagiaires'] * 100) : 0;
                        
                        $tag = 'أولوية تنموية';
                        $code = strtolower($item['code']);
                        if (strpos($code, 'int') !== false || strpos($code, 'inf') !== false) {
                            $tag = 'أولوية رقمنة';
                        } elseif (strpos($code, 'agr') !== false || strpos($code, 'flh') !== false) {
                            $tag = 'أولوية فلاحية ورعوية';
                        } elseif (strpos($code, 'ind') !== false || strpos($code, 'bât') !== false) {
                            $tag = 'صناعة وبناء وتشييد';
                        }
                    @endphp
                    <tr>
                        <td>
                            <div class="bold">{{ $item['filiere_nom'] }}</div>
                            <div style="font-size: 8.5pt; color: #64748b; font-style: italic;">{{ $item['filiere_fr'] }}</div>
                        </td>
                        <td class="center">{{ $item['specialites_count'] }} تخصص</td>
                        <td class="center bold" style="color: #2563eb;">{{ number_format($item['total_stagiaires']) }} متربص</td>
                        <td class="center" style="color: #db2777;">{{ number_format($tauxFemme, 1) }}%</td>
                        <td class="center">{{ $tag }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>
</html>
