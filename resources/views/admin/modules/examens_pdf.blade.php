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
        .exams-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        .exams-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            border: 1px solid #1e3a8a;
            padding: 8px;
            text-align: right;
            font-weight: bold;
        }
        .exams-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: right;
        }
        .center {
            text-align: center;
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
        <div class="title">جدولة ورزنامة الامتحانات التقييمية الرسمية</div>
    </div>

    <table class="exams-table">
        <thead>
            <tr>
                <th style="width: 25%;">المادة / الاختبار</th>
                <th style="width: 30%;">التخصص / الفرع</th>
                <th style="width: 20%; text-align: center;">تاريخ وتوقيت الامتحان</th>
                <th style="width: 15%; text-align: center;">القاعة / المدرج</th>
                <th style="width: 10%; text-align: center;">المراقبون</th>
            </tr>
        </thead>
        <tbody>
            @if (empty($list))
                <tr>
                    <td>خوارزميات وهياكل البيانات المعقدة</td>
                    <td>مطور الويب والوسائط المتعددة</td>
                    <td class="center">2026-05-24 09:00</td>
                    <td class="center">المدرج الكبير أ</td>
                    <td class="center">أ. حدادي + 3 مراقبين</td>
                </tr>
            @else
                @foreach ($list as $item)
                    <tr>
                        <td>
                            <div style="font-weight: bold;">{{ $item['matiere_nom'] }}</div>
                            <div style="font-size: 8.5pt; color: #64748b;">تقييم رسمي</div>
                        </td>
                        <td>{{ $item['spec_ar'] }}</td>
                        <td class="center" style="font-family: 'dejavusans';">{{ $item['date_examen'] }}</td>
                        <td class="center">{{ $item['salle'] }}</td>
                        <td class="center">أ. {{ $item['examinateur'] ?? 'حدادي' }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>
</html>
