@extends('layouts.print')
@section('title', $title ?? 'طباعة - SGFEP')
@section('content')
<div class="container my-5 p-5 bg-white border shadow-sm" style="max-width: 950px; font-family: 'Cairo', sans-serif; position: relative;">
    
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
            <span>الرقم: <strong>SGFEP/DFEP/OFFR/<?= date('Y') ?>/<?= rand(100, 999) ?></strong></span>
        </div>
        <div class="col-6 text-start">
            <span>التاريخ: <strong><?= date('Y-m-d') ?></strong></span>
        </div>
    </div>

    <!-- Title Block -->
    <div class="text-center mb-4 py-3 border border-dark border-2 bg-light">
        <h3 class="fw-bold m-0" style="color: #1e293b; font-size: 1.4rem;">جدول عروض التكوين المفتوحة ودورات الالتحاق النشطة</h3>
        <span class="text-muted fw-bold d-block mt-1">Tableau des Offres de Formation & Sessions d'Admission</span>
    </div>

    <!-- Offers Table -->
    <div class="table-responsive mb-5">
        <table class="table table-bordered align-middle text-center" style="border: 1px solid #1e293b;">
            <thead class="bg-light text-dark fw-bold" style="border-bottom: 2px solid #1e293b;">
                <tr>
                    <th class="py-2" style="width: 100px;">رمز العرض</th>
                    <th class="py-2 text-right">التخصص البيداغوجي المستهدف</th>
                    <th class="py-2">الشهادة</th>
                    <th class="py-2">نمط التكوين</th>
                    <th class="py-2">المستوى المطلوب</th>
                    <th class="py-2" style="width: 100px;">الطاقة الاستيعابية</th>
                    <th class="py-2" style="width: 180px;">فترة الدورة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($offres)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">لا توجد عروض تكوين نشطة مسجلة حالياً.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($offres as $o): ?>
                        <tr>
                            <td class="fw-bold" style="font-family: 'Outfit';"><?= htmlspecialchars($o['code']) ?></td>
                            <td class="text-right">
                                <strong><?= htmlspecialchars($o['spec_ar']) ?></strong>
                                <div class="text-muted small" style="font-family: 'Outfit'; font-size: 0.75rem;"><?= htmlspecialchars($o['spec_fr']) ?></div>
                            </td>
                            <td><span class="badge bg-dark text-white px-2 py-1"><?= htmlspecialchars($o['diplome_vise']) ?></span></td>
                            <td class="fw-bold">
                                <?= $o['mode_formation'] === 'apprentissage' ? 'تمهين' : 'حضوري' ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($o['niveau_requis']) ?></td>
                            <td class="fw-bold"><?= $o['capacite'] ?> مقعد</td>
                            <td class="small text-muted" dir="ltr"><?= $o['date_debut'] ?> / <?= $o['date_fin'] ?></td>
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
            <span class="fw-bold d-block mb-5">رئيس مصلحة الامتحانات والتوجيه</span>
            <div style="border: 1px dashed #ccc; width: 180px; height: 80px; display: inline-block;" class="d-flex align-items-center justify-content-center text-muted small">
                ختم وتوقيع المديرية
            </div>
        </div>
    </div>

    <!-- Print control row (hidden during printing) -->
    <div class="mt-5 pt-4 border-top text-center no-print">
        <button onclick="window.print()" class="btn btn-dark px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-print me-1"></i> بدء طباعة العروض
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
