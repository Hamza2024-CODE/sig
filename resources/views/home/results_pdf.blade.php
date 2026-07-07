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
        .republic-header {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        .divider {
            border-top: 2px solid #2563eb;
            margin: 10px 0;
            opacity: 0.5;
        }
        .etab-info {
            width: 100%;
            margin-bottom: 20px;
            font-size: 9.5pt;
        }
        .etab-info td {
            vertical-align: top;
        }
        .title-box {
            text-align: center;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .title-ar {
            font-size: 15pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 3px;
        }
        .title-fr {
            font-size: 11pt;
            font-style: italic;
            color: #475569;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .student-table th {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: right;
            font-weight: bold;
            width: 20%;
        }
        .student-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: right;
            width: 30%;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .grades-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            border: 1px solid #1e3a8a;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
        }
        .grades-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 6px;
            text-align: right;
        }
        .grades-table .center {
            text-align: center;
        }
        .grades-table .bold {
            font-weight: bold;
        }
        .decision-container {
            width: 100%;
            margin-bottom: 30px;
        }
        .decision-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            height: 60px;
        }
        .decision-title {
            font-size: 8.5pt;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: bold;
        }
        .decision-val {
            font-size: 11.5pt;
            font-weight: bold;
            color: #1e3a8a;
        }
        .footer-section {
            width: 100%;
            margin-top: 20px;
        }
        .footer-section td {
            vertical-align: top;
        }
        .signature-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 5px;
        }
        .signature-sub {
            font-size: 8.5pt;
            color: #64748b;
            font-style: italic;
        }
        .qr-container {
            text-align: center;
        }
        .qr-text {
            font-size: 8pt;
            font-weight: bold;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <!-- Republic Header -->
    <div class="republic-header">
        الجمهورية الجزائرية الديمقراطية الشعبية<br>
        <span style="font-size: 9.5pt; font-weight: normal; font-style: italic;">République Algérienne Démocratique et Populaire</span><br>
        وزارة التكوين والتعليم المهنيين<br>
        <span style="font-size: 9.5pt; font-weight: normal; font-style: italic;">Ministère de la Formation et de l'Enseignement Professionnels</span>
    </div>

    <div class="divider"></div>

    <!-- Etablissement Info -->
    <table class="etab-info">
        <tr>
            <td style="text-align: right; width: 50%;">
                <strong>المؤسسة:</strong> {{ $student['etab_ar'] }}<br>
                <strong>رقم التسجيل:</strong> <span style="font-family: 'dejavusans'; font-weight: bold; color: #2563eb;">{{ $student['numero_matricule'] }}</span>
            </td>
            <td style="text-align: left; width: 50%; direction: ltr;">
                <strong>Établissement:</strong> {{ $student['etab_fr'] }}<br>
                <strong>N° Matricule:</strong> <span style="font-family: 'dejavusans'; font-weight: bold; color: #2563eb;">{{ $student['numero_matricule'] }}</span>
            </td>
        </tr>
    </table>

    <!-- Title -->
    <div class="title-box">
        <div class="title-ar">كشف النقاط الرسمي للمتخرج</div>
        <div class="title-fr">Relevé de Notes Officiel du Diplômé</div>
    </div>

    <!-- Student Info -->
    <table class="student-table">
        <tr>
            <th>الاسم واللقب:</th>
            <td>{{ $student['nom_ar'] }} {{ $student['prenom_ar'] }}</td>
            <th style="direction: ltr; text-align: left;">Nom & Prénom:</th>
            <td style="font-family: 'dejavusans'; font-weight: bold;">{{ $student['prenom_fr'] }} {{ $student['nom_fr'] }}</td>
        </tr>
        <tr>
            <th>تاريخ الميلاد:</th>
            <td><span style="font-family: 'dejavusans';">{{ date('d/m/Y', strtotime($student['date_naissance'])) }}</span></td>
            <th style="direction: ltr; text-align: left;">Date de Naissance:</th>
            <td><span style="font-family: 'dejavusans';">{{ date('d/m/Y', strtotime($student['date_naissance'])) }}</span></td>
        </tr>
        <tr>
            <th>رقم التعريف الوطني:</th>
            <td><span style="font-family: 'dejavusans'; font-weight: bold;">{{ $student['nin'] }}</span></td>
            <th style="direction: ltr; text-align: left;">NIN:</th>
            <td><span style="font-family: 'dejavusans'; font-weight: bold;">{{ $student['nin'] }}</span></td>
        </tr>
        <tr>
            <th>التخصص الدراسي:</th>
            <td style="color: #2563eb; font-weight: bold;">{{ $student['spec_ar'] }}</td>
            <th style="direction: ltr; text-align: left;">Spécialité:</th>
            <td style="color: #2563eb; font-weight: bold; font-family: 'dejavusans';">{{ $student['spec_fr'] }}</td>
        </tr>
        <tr>
            <th>نمط التكوين:</th>
            @php
                $modeLabels = \App\Services\ModeService::getLabels($student['mode_formation'] ?? 'residentiel');
            @endphp
            <td>{{ $modeLabels['ar'] }}</td>
            <th style="direction: ltr; text-align: left;">Mode de Formation:</th>
            <td>{{ $modeLabels['fr'] }}</td>
        </tr>
    </table>

    <!-- Grades Table -->
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 15%;">رمز الوحدة / Code</th>
                <th style="width: 50%;">الوحدات التعليمية والبيداغوجية / Modules</th>
                <th style="width: 10%;">المعامل / Coef</th>
                <th style="width: 12%;">النقاط / Note</th>
                <th style="width: 13%;">النقاط الموزونة / Pondérée</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_coef = 0;
                $total_points = 0;
            @endphp
            @foreach ($grades as $grade)
                @php
                    $weighted = $grade['note'] * $grade['coef'];
                    $total_coef += $grade['coef'];
                    $total_points += $weighted;
                @endphp
                <tr>
                    <td class="center" style="font-family: 'dejavusans'; font-weight: bold;">{{ $grade['code'] }}</td>
                    <td>
                        <div style="font-weight: bold; margin-bottom: 2px;">{{ $grade['libelle_ar'] }}</div>
                        <div style="font-size: 8.5pt; color: #475569; font-style: italic;">{{ $grade['libelle_fr'] }}</div>
                    </td>
                    <td class="center" style="font-family: 'dejavusans';">{{ $grade['coef'] }}</td>
                    <td class="center bold" style="font-family: 'dejavusans';">{{ number_format($grade['note'], 2) }}</td>
                    <td class="center" style="font-family: 'dejavusans';">{{ number_format($weighted, 2) }}</td>
                </tr>
            @endforeach
            <tr class="bold" style="background-color: #f1f5f9;">
                <td colspan="2" style="text-align: left; padding: 10px;">المجموع / Total :</td>
                <td class="center" style="font-family: 'dejavusans';">{{ $total_coef }}</td>
                <td></td>
                <td class="center" style="font-family: 'dejavusans';">{{ number_format($total_points, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Decision Box -->
    <table class="decision-container">
        <tr>
            <td style="width: 33.3%; padding: 0 4px;">
                <div class="decision-box">
                    <div class="decision-title">النتيجة النهائية / Résultat Final</div>
                    <div class="decision-val" style="color: #16a34a;">مقبول (ناجح) / Admis</div>
                </div>
            </td>
            <td style="width: 33.3%; padding: 0 4px;">
                <div class="decision-box">
                    <div class="decision-title">المعدل العام / Moyenne Générale</div>
                    <div class="decision-val" style="font-family: 'dejavusans'; font-size: 13.5pt;">
                        {{ number_format($student['moyenne_generale'] ?? ($total_points / $total_coef), 2) }} <span style="font-size: 9.5pt; color: #64748b;">/ 20.00</span>
                    </div>
                </div>
            </td>
            <td style="width: 33.3%; padding: 0 4px;">
                @php
                    switch ($student['mention'] ?? 'passable') {
                        case 'tres_bien': $mentionLabels = ['ar' => 'حسن جداً', 'fr' => 'Très Bien']; break;
                        case 'bien': $mentionLabels = ['ar' => 'حسن', 'fr' => 'Bien']; break;
                        case 'assez_bien': $mentionLabels = ['ar' => 'قريب من الحسن', 'fr' => 'Assez Bien']; break;
                        default: $mentionLabels = ['ar' => 'مقبول', 'fr' => 'Passable'];
                    }
                @endphp
                <div class="decision-box">
                    <div class="decision-title">التقدير العام / Mention</div>
                    <div class="decision-val">{{ $mentionLabels['ar'] }} / {{ $mentionLabels['fr'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Signatures & QR -->
    <table class="footer-section">
        <tr>
            <td style="width: 50%; text-align: right; padding-right: 10px;">
                <div class="signature-title">توقيع وختم مدير المؤسسة</div>
                <div class="signature-sub">Signature et Griffe du Directeur</div>
                <div style="height: 60px;"></div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="qr-container">
                    @php
                        $verifyUrl = url("/resultats?matricule=" . $student['numero_matricule']);
                    @endphp
                    <!-- QR Code via Image API -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" style="width: 100px; height: 100px; display: block; margin: 0 auto; border: 1px solid #cbd5e1; padding: 4px; background-color: #ffffff;">
                    <div class="qr-text">رمز التحقق السريع</div>
                    <div class="signature-sub" style="font-size: 7.5pt;">Code QR de Vérification</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
