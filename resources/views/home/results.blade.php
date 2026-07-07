@extends('layouts.public')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array $student
 * @var array $grades
 * @var string $matricule
 * @var bool $is_search_only
 */

$is_search_only = $is_search_only ?? false;

// Mention translator
function getMentionLabels($mention) {
    switch ($mention) {
        case 'tres_bien':
            return ['ar' => 'حسن جداً', 'fr' => 'Très Bien'];
        case 'bien':
            return ['ar' => 'حسن', 'fr' => 'Bien'];
        case 'assez_bien':
            return ['ar' => 'قريب من الحسن', 'fr' => 'Assez Bien'];
        case 'passable':
            return ['ar' => 'مقبول', 'fr' => 'Passable'];
        default:
            return ['ar' => 'مقبول', 'fr' => 'Passable'];
    }
}
?>
@if($is_search_only)
    <!-- Premium Search View (Matching Home Aesthetics) -->
    <div class="luxury-viewport d-flex justify-content-center align-items-center" style="position: relative; overflow: hidden; background: transparent; min-height: 80vh; padding: 4rem 1rem; font-family: 'Cairo', sans-serif;">
        
        <div class="card apple-glass-panel border-0 p-5 animate__animated animate__fadeInUp" style="position: relative; z-index: 2; width: 100%; max-width: 580px;">
            <div class="text-center mb-4">
                <div class="d-inline-flex p-3 rounded-circle mb-3" style="background-color: rgba(37, 99, 235, 0.08); color: #2563eb;">
                    <i class="fa-solid fa-graduation-cap" style="font-size: 2.8rem;"></i>
                </div>
                <h3 class="fw-bold mb-1" style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);">بوابة الاستعلام البيداغوجي الموحدة</h3>
                <p class="text-muted small mb-0" style="font-weight: 600;">Consultation des Bulletins & Relevés de Notes</p>
            </div>

            <div class="alert bg-white/50 border p-3.5 mb-4 text-right" style="border-radius: 16px; border-color: rgba(37, 99, 235, 0.15) !important;">
                <h6 class="fw-bold text-primary mb-1.5" style="color: #2563eb !important;"><i class="fa-solid fa-circle-info me-1.5"></i> إرشادات الاستعلام:</h6>
                <p class="small text-muted mb-0 leading-relaxed" style="font-weight: 600;">
                    يرجى إدخال رقم التسجيل المطبوع على بطاقة المتربص للاستعلام اللحظي الآمن والمباشر عن كشف النقاط الرسمي ومحاضر المداولات الموقعة رقمياً.
                </p>
            </div>

            @if(isset($error_message) && !empty($error_message))
                <div class="alert alert-danger text-right border-0 shadow-sm p-3 mb-4 animate__animated animate__shakeX" style="border-radius: 14px; background-color: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.15) !important; color: #dc2626; font-weight: 700;">
                    <i class="fa-solid fa-circle-xmark me-1.5"></i> {{ $error_message }}
                </div>
            @endif

            <form action="{{ url('resultats') }}" method="GET" class="mb-3">
                <div class="form-group mb-4 text-right">
                    <label class="form-label fw-bold mb-2" style="font-size: 0.78rem; font-weight: 800; color: var(--text-primary); letter-spacing: 0.3px;">أدخل رقم التسجيل الإلكتروني أو رقم التعريف الوطني:</label>
                    <div class="apple-input-wrapper">
                        <input type="text" name="matricule" required class="apple-input" placeholder="مثال: STG-001" style="text-align: center; font-size: 1.05rem;">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="apple-btn-primary py-3 fw-bold border-0" style="height: auto;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>استعلام عن النتائج / Consulter</span>
                    </button>
                    <a href="/resultats?matricule=STG-001" class="btn btn-premium-secondary py-3 fw-bold" style="border-radius: 12px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>عرض كشف نقاط تجريبي سريع (Demo)</span>
                    </a>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="/" class="text-decoration-none text-muted small" style="font-weight: 700;"><i class="fa-solid fa-house me-1.5"></i> العودة للبوابة الرئيسية</a>
            </div>
        </div>
    </div>
@else
    @php
        $mentionLabels = getMentionLabels($student['mention'] ?? 'passable');
        $modeLabels = \App\Services\ModeService::getLabels($student['mode_formation'] ?? 'residentiel');
    @endphp

<div class="result-page-container container my-5" style="font-family: 'Cairo', sans-serif;">
    
    <!-- Controls (No Print) -->
    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <a href="/" class="btn btn-premium-secondary px-4 py-2.5 fw-bold" style="border-radius: 12px; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-arrow-right"></i>
            <span>العودة للرئيسية / Retour</span>
        </a>
        <a href="{{ url('resultats') }}?matricule={{ urlencode($student['numero_matricule']) }}&pdf=1" target="_blank" class="apple-btn-primary px-4 py-2.5 fw-bold text-decoration-none animate__animated animate__pulse animate__infinite" style="height: auto; display: inline-flex; align-items: center; gap: 0.5rem; justify-content: center;">
            <i class="fa-solid fa-file-pdf"></i>
            <span>تنزيل كشف النقاط الرسمي (PDF) / Télécharger</span>
        </a>
    </div>

    <?php if (isset($student['is_demo'])): ?>
        <div class="alert alert-warning no-print text-center border-0 shadow-sm p-3 mb-4" style="border-radius: 14px; background-color: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.15) !important; color: #d97706; font-weight:700;">
            <i class="fa-solid fa-circle-exclamation me-1.5"></i> نسخة تجريبية محاكية للعرض البيداغوجي والوزاري / Version de Démo pour Présentation
        </div>
    <?php endif; ?>

    <!-- Gorgeous Official Sheet with Apple Glassmorphic Styling -->
    <div class="official-sheet-luxury apple-glass-panel p-4 p-md-5 position-relative" style="border-radius: 28px; border: 1px solid rgba(255, 255, 255, 0.45) !important;">
        
        <!-- Sovereign Color Ribbon on top of sheet -->
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%); border-top-left-radius: 28px; border-top-right-radius: 28px;"></div>

        <!-- Official Sovereign Header -->
        <div class="text-center mb-4">
            <h5 class="fw-bold mb-1" style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary);">الجمهورية الجزائرية الديمقراطية الشعبية</h5>
            <h6 class="text-muted mb-3" style="font-family:'Outfit'; font-size: 0.85rem; font-weight:600; letter-spacing:0.5px;">République Algérienne Démocratique et Populaire</h6>
            <h5 class="fw-bold mb-1" style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary);">وزارة التكوين والتعليم المهنيين</h5>
            <h6 class="text-muted" style="font-family:'Outfit'; font-size: 0.85rem; font-weight:600; letter-spacing:0.5px;">Ministère de la Formation et de l'Enseignement Professionnels</h6>
            
            <div class="mx-auto my-3" style="width: 80px; height: 2px; background-color: #2563eb; opacity: 0.3;"></div>
            
            <div class="row w-100 justify-content-between text-right small px-2">
                <div class="col-sm-6 text-right" style="color: var(--text-primary);">
                    <strong>المؤسسة:</strong> <?= htmlspecialchars($student['etab_ar']) ?><br>
                    <strong>رقم التسجيل:</strong> <code class="fw-bold" style="color: #2563eb; font-size:0.9rem; font-family:'Outfit';"><?= htmlspecialchars($student['numero_matricule']) ?></code>
                </div>
                <div class="col-sm-6 text-left" style="text-align: left !important; color: var(--text-primary);">
                    <strong>Établissement:</strong> <?= htmlspecialchars($student['etab_fr']) ?><br>
                    <strong>N° Matricule:</strong> <code class="fw-bold" style="color: #2563eb; font-size:0.9rem; font-family:'Outfit';"><?= htmlspecialchars($student['numero_matricule']) ?></code>
                </div>
            </div>
        </div>

        <div class="text-center my-4 py-3" style="background-color: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 16px;">
            <h4 class="fw-extrabold mb-1" style="font-size: 1.45rem; color: var(--text-primary);">كشف النقاط الرسمي للمتخرج</h4>
            <h5 class="text-muted small mb-0" style="font-family:'Outfit'; font-weight:600;">Relevé de Notes Officiel du Diplômé</h5>
        </div>

        <!-- Student Identity Grid (Modern Premium Layout) -->
        <div class="row g-3 mb-4 text-right">
            <!-- Name & Surname -->
            <div class="col-md-6">
                <div class="p-3.5 border rounded-4 h-100 bg-white/50" style="border-color: rgba(37, 99, 235, 0.08) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2 text-primary">
                        <i class="fa-solid fa-circle-user fs-5"></i>
                        <span class="fw-bold small">الاسم واللقب / Nom & Prénom</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-extrabold mb-0 text-dark" style="font-size: 1.05rem;"><?= htmlspecialchars($student['nom_ar']) ?> <?= htmlspecialchars($student['prenom_ar']) ?></h5>
                        <span class="text-muted small fw-bold" style="font-family:'Outfit';"><?= htmlspecialchars($student['prenom_fr']) ?> <?= htmlspecialchars($student['nom_fr']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Date of Birth -->
            <div class="col-md-6">
                <div class="p-3.5 border rounded-4 h-100 bg-white/50" style="border-color: rgba(37, 99, 235, 0.08) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2 text-info">
                        <i class="fa-solid fa-calendar-day fs-5"></i>
                        <span class="fw-bold small">تاريخ الميلاد / Date de Naissance</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.05rem;"><?= date('d/m/Y', strtotime($student['date_naissance'])) ?></h5>
                        <span class="text-muted small fw-bold" style="font-family:'Outfit';"><?= date('d/m/Y', strtotime($student['date_naissance'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- National Identification Number (NIN) -->
            <div class="col-md-4">
                <div class="p-3.5 border rounded-4 h-100 bg-white/50" style="border-color: rgba(37, 99, 235, 0.08) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2 text-secondary">
                        <i class="fa-solid fa-id-card-clip fs-5"></i>
                        <span class="fw-bold small">رقم التعريف الوطني / NIN</span>
                    </div>
                    <code class="fw-bold text-dark d-block" style="font-family:'Outfit'; font-size: 0.95rem; letter-spacing: 0.5px;"><?= htmlspecialchars($student['nin']) ?></code>
                </div>
            </div>

            <!-- Training Mode -->
            <div class="col-md-4">
                <div class="p-3.5 border rounded-4 h-100 bg-white/50" style="border-color: rgba(37, 99, 235, 0.08) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                        <i class="fa-solid fa-briefcase fs-5"></i>
                        <span class="fw-bold small">نمط التكوين / Mode de Formation</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><?= $modeLabels['ar'] ?></h6>
                        <span class="text-muted small fw-bold" style="font-family:'Outfit';"><?= $modeLabels['fr'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Registration Number -->
            <div class="col-md-4">
                <div class="p-3.5 border rounded-4 h-100 bg-white/50" style="border-color: rgba(37, 99, 235, 0.08) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2 text-success">
                        <i class="fa-solid fa-barcode fs-5"></i>
                        <span class="fw-bold small">رقم التسجيل / N° Matricule</span>
                    </div>
                    <code class="fw-bold text-primary d-block" style="font-family:'Outfit'; font-size: 1rem; color: #2563eb !important;"><?= htmlspecialchars($student['numero_matricule']) ?></code>
                </div>
            </div>

            <!-- Specialty -->
            <div class="col-md-12">
                <div class="p-3.5 border rounded-4 bg-white/50" style="border-color: rgba(37, 99, 235, 0.08) !important;">
                    <div class="d-flex align-items-center gap-2 mb-2 text-primary">
                        <i class="fa-solid fa-graduation-cap fs-5"></i>
                        <span class="fw-bold small">التخصص الدراسي / Spécialité</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="fw-extrabold mb-0 text-primary" style="font-size: 1.15rem; color: #2563eb !important;"><?= htmlspecialchars($student['spec_ar']) ?></h5>
                        <span class="text-muted small fw-bold" style="font-family:'Outfit'; font-size: 0.95rem; font-style: italic;"><?= htmlspecialchars($student['spec_fr']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle text-right" style="border-color: rgba(15, 23, 42, 0.1); color: var(--text-primary);">
                <thead>
                    <tr class="text-white text-center" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;">
                        <th style="width: 15%;">رمز الوحدة / Code</th>
                        <th style="width: 50%;">الوحدات التعليمية والبيداغوجية / Modules</th>
                        <th style="width: 10%;">المعامل / Coef</th>
                        <th style="width: 12%;">النقاط / Note</th>
                        <th style="width: 13%;">النقاط الموزونة / Pondérée</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_coef = 0;
                    $total_points = 0;
                    foreach ($grades as $grade): 
                        $weighted = $grade['note'] * $grade['coef'];
                        $total_coef += $grade['coef'];
                        $total_points += $weighted;
                    ?>
                        <tr>
                            <td class="text-center"><code style="font-family:'Outfit'; font-weight:700; color: var(--text-secondary);"><?= $grade['code'] ?></code></td>
                            <td>
                                <div class="d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($grade['libelle_ar']) ?></strong>
                                    <span class="text-muted small" style="font-family:'Outfit'; font-weight: 500; font-style: italic;"><?= htmlspecialchars($grade['libelle_fr']) ?></span>
                                </div>
                            </td>
                            <td class="text-center" style="font-family:'Outfit'; font-weight:600;"><?= $grade['coef'] ?></td>
                            <td class="text-center" style="font-family:'Outfit'; font-weight:700;"><strong><?= number_format($grade['note'], 2) ?></strong></td>
                            <td class="text-center" style="font-family:'Outfit'; font-weight:600;"><?= number_format($weighted, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background: rgba(37, 99, 235, 0.02);">
                        <td colspan="2" class="text-start">المجموع / Total :</td>
                        <td class="text-center" style="font-family:'Outfit';"><?= $total_coef ?></td>
                        <td></td>
                        <td class="text-center" style="font-family:'Outfit';"><?= number_format($total_points, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Decision Box -->
        <div class="row g-3 align-items-stretch mb-5">
            <div class="col-md-4 text-center">
                <div class="p-3 border rounded-4 h-100 d-flex flex-column justify-content-center" style="background: rgba(37, 99, 235, 0.04); border-color: rgba(37, 99, 235, 0.12) !important;">
                    <small class="text-muted d-block mb-1" style="font-weight: 700;">النتيجة النهائية / Résultat Final</small>
                    <span class="badge bg-success text-white py-2 px-3 fw-bold" style="font-size: 1.05rem;"><i class="fa-solid fa-circle-check me-1.5"></i>مقبول (ناجح) / Admis</span>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-3 border rounded-4 h-100 d-flex flex-column justify-content-center" style="background: rgba(37, 99, 235, 0.04); border-color: rgba(37, 99, 235, 0.12) !important;">
                    <small class="text-muted d-block mb-1" style="font-weight: 700;">المعدل العام / Moyenne Générale</small>
                    <span class="fw-extrabold text-primary" style="font-size: 1.7rem; font-family:'Outfit'; color: #2563eb !important;">
                        <?= number_format($student['moyenne_generale'] ?? ($total_points / $total_coef), 2) ?> <span style="font-size:1.1rem; font-weight:600; color: #64748b;">/ 20.00</span>
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-3 border rounded-4 h-100 d-flex flex-column justify-content-center" style="background: rgba(37, 99, 235, 0.04); border-color: rgba(37, 99, 235, 0.12) !important;">
                    <small class="text-muted d-block mb-1" style="font-weight: 700;">التقدير العام / Mention</small>
                    <span class="fw-bold" style="font-size: 1.15rem; color: var(--text-primary);"><?= $mentionLabels['ar'] ?> / <?= $mentionLabels['fr'] ?></span>
                </div>
            </div>
        </div>

        <!-- Footnote, Seal, Signatures & QR Verification -->
        <div class="row align-items-center pt-3 border-top" style="border-color: rgba(15, 23, 42, 0.08) !important;">
            <div class="col-sm-6 text-right">
                <p class="fw-bold mb-1" style="color: var(--text-primary);">توقيع وختم مدير المؤسسة</p>
                <p class="text-muted small mb-0" style="font-family:'Outfit'; font-weight: 600;">Signature et Griffe du Directeur</p>
                <div style="height: 70px;"></div> <!-- Physical signature gap -->
            </div>
            <div class="col-sm-6 d-flex flex-column align-items-center justify-content-center text-center">
                <!-- Verified QR Code -->
                <div class="p-2 bg-white border rounded-3 mb-2 shadow-sm" style="border-color: rgba(37, 99, 235, 0.15) !important;">
                    @php
                        $verifyUrl = url("/resultats?matricule=" . $student['numero_matricule']);
                    @endphp
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code" style="width: 100px; height: 100px; display: block; border-radius: 4px;">
                </div>
                <small class="fw-bold d-block" style="color: var(--text-primary);">رمز التحقق السريع</small>
                <small class="text-muted" style="font-family:'Outfit'; font-size:0.75rem; font-weight: 600;">Code QR de Vérification</small>
            </div>
        </div>

    </div>
</div>

<style>
/* Printing specific overrides */
@media print {
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
    .no-print {
        display: none !important;
    }
    .result-page-container {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .official-sheet-luxury {
        background: #ffffff !important;
        color: #000000 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }
    .official-sheet-luxury th {
        background: #f8fafc !important;
        color: #000000 !important;
    }
    .official-sheet-luxury thead tr {
        background: #0f172a !important;
        color: #ffffff !important;
    }
}
</style>
@endif
@endsection
