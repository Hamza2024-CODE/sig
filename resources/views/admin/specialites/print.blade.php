@extends('layouts.print')
@section('title', $title ?? 'طباعة - SGFEP')
@section('content')
<div class="container my-5 p-5 bg-white border shadow-sm" style="max-width: 900px; font-family: 'Cairo', sans-serif; position: relative;">
    
    <!-- Flag strip -->
    <div style="height: 5px; background: linear-gradient(to right, #006233 33.3%, #ffffff 33.3%, #ffffff 66.6%, #D21034 66.6%); width:100%; position: absolute; top:0; left:0;"></div>

    <!-- Government Header -->
    <div class="text-center mb-4 mt-3">
        <h5 class="fw-bold mb-1" style="font-size: 1rem;">الجمهورية الجزائرية الديمقراطية الشعبية</h5>
        <h6 class="fw-bold mb-1" style="font-size: 0.9rem;">وزارة التكوين والتعليم المهنيين</h6>
        <h6 class="fw-bold mb-3" style="font-size: 0.9rem;">المديرية الولائية للتكوين والتعليم المهنيين</h6>
        <div style="width: 100px; height: 1px; background-color: #ccc; margin: 0 auto;"></div>
    </div>

    <!-- Date and Reference -->
    <div class="row mb-4 text-end small text-muted">
        <div class="col-6">
            <span>الرقم: <strong>SGFEP/DFEP/SPEC/<?= date('Y') ?>/<?= rand(100, 999) ?></strong></span>
        </div>
        <div class="col-6 text-start">
            <span>التاريخ: <strong><?= date('Y-m-d') ?></strong></span>
        </div>
    </div>

    <!-- Title Block -->
    <div class="text-center mb-4 py-3 border border-dark border-2 bg-light">
        <h3 class="fw-bold m-0" style="color: #1e293b; font-size: 1.4rem;">الدليل الولائي للتخصصات البيداغوجية المعتمدة</h3>
        <span class="text-muted fw-bold d-block mt-1">Répertoire des Spécialités Pédagogiques Habilitées</span>
    </div>

    <!-- Specialties Table -->
    <div class="table-responsive mb-5">
        <table class="table table-bordered align-middle text-right" style="border: 1px solid #1e293b;">
            <thead class="bg-light text-dark fw-bold" style="border-bottom: 2px solid #1e293b;">
                <tr>
                    <th class="py-2 text-center" style="width: 120px;">رمز التخصص</th>
                    <th class="py-2">اسم التخصص</th>
                    <th class="py-2">الشعبة المهنية</th>
                    <th class="py-2 text-center" style="width: 140px;">المدة (سداسي)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($specialites)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">لا توجد تخصصات مسجلة حالياً.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($specialites as $s): ?>
                        <tr>
                            <td class="text-center fw-bold" style="font-family: 'Outfit';"><?= htmlspecialchars($s['code']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($s['libelle_ar']) ?></strong>
                                <div class="text-muted small" style="font-family: 'Outfit'; font-size: 0.75rem;"><?= htmlspecialchars($s['libelle_fr']) ?></div>
                            </td>
                            <td>
                                <span class="small"><?= htmlspecialchars($s['filiere_ar']) ?></span>
                                <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($s['filiere_fr']) ?></div>
                            </td>
                            <td class="text-center fw-bold"><?= $s['duree_semestres'] ?> سداسيات</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer Signature Block -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <span class="d-block text-muted small">مستخرج من النظام الموحد للتكوين المهني</span>
            <span class="d-block small text-muted">منصة تسيير (TASSYIR) - وزارة التكوين المهني</span>
        </div>
        <div class="col-6 text-start">
            <span class="fw-bold d-block mb-5">مصلحة تنظيم الفروع والتكوين</span>
            <div style="border: 1px dashed #ccc; width: 180px; height: 80px; display: inline-block;" class="d-flex align-items-center justify-content-center text-muted small">
                ختم وتوقيع المديرية
            </div>
        </div>
    </div>

    <!-- Print control row (hidden during printing) -->
    <div class="mt-5 pt-4 border-top text-center no-print">
        <button onclick="window.print()" class="btn btn-dark px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-print me-1"></i> بدء طباعة الدليل
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 py-2 ms-2" style="border-radius: 8px;">إغلاق النافذة</button>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background-color: white !important;
        color: black !important;
    }
    .container {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
}
</style>

<script>
window.onload = function() {
    setTimeout(function() {
        window.print();
    }, 800);
}
</script>

@endsection
