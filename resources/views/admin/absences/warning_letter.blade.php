@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<div class="container my-5 p-5 bg-white border shadow-sm" style="max-width: 800px; font-family: 'Cairo', sans-serif; position: relative;">
    
    <!-- Flag strip -->
    <div style="height: 5px; background: linear-gradient(to right, #006233 33.3%, #ffffff 33.3%, #ffffff 66.6%, #D21034 66.6%); width:100%; position: absolute; top:0; left:0;"></div>

    <!-- Government Header -->
    <div class="text-center mb-4 mt-3">
        <h5 class="fw-bold mb-1" style="font-size: 1rem;">الجمهورية الجزائرية الديمقراطية الشعبية</h5>
        <h6 class="fw-bold mb-1" style="font-size: 0.9rem;">وزارة التكوين والتعليم المهنيين</h6>
        <h6 class="fw-bold mb-3" style="font-size: 0.9rem;">المعهد الوطني المتخصص في التكوين المهني - السانية</h6>
        <div style="width: 100px; height: 1px; background-color: #ccc; margin: 0 auto;"></div>
    </div>

    <!-- Date and Reference -->
    <div class="row mb-5 text-end small text-muted">
        <div class="col-6">
            <span>الرقم: <strong>SGFEP/DFEP/<?= date('Y') ?>/<?= rand(100, 999) ?></strong></span>
        </div>
        <div class="col-6 text-start">
            <span>وهران في: <strong><?= date('Y-m-d') ?></strong></span>
        </div>
    </div>

    <!-- Warning Title Block -->
    <div class="text-center mb-5 py-3 border border-dark border-2 bg-light">
        <h3 class="fw-bold m-0" style="color: #c0392b; font-size: 1.6rem;"><?= htmlspecialchars($type) ?></h3>
        <span class="text-muted fw-bold d-block mt-1">Notification d'absence & Avertissement</span>
    </div>

    <!-- Letter Body -->
    <div class="fs-5 text-dark leading-loose text-end mb-5" style="line-height: 2;">
        <p>إلى السيد(ة): <strong class="text-primary"><?= htmlspecialchars($t['nom_ar'] . ' ' . $t['prenom_ar']) ?></strong></p>
        <p>المولود(ة) في: <strong><?= htmlspecialchars($t['date_naissance']) ?></strong></p>
        <p>رقم التسجيل / Matricule: <code><?= htmlspecialchars($t['numero_matricule']) ?></code></p>
        <p>الفرع الدراسي / Spécialité: <strong class="text-dark"><?= htmlspecialchars($t['specialite_ar']) ?></strong></p>
        
        <div class="my-4 p-3 border rounded bg-light" style="border-right: 5px solid #c0392b !important;">
            <p class="m-0">
                بناءً على سجلات حضور الغيابات البيداغوجية، لقد تبين للمصالح المعنية أنكم سجلتم غيابات متكررة غير مبررة بلغت في مجموعها المتراكم: 
                <strong class="text-danger fs-4"><?= (float)$t['total_absences'] ?> ساعة</strong>.
            </p>
        </div>

        <?php if ($level === 3): ?>
            <p class="text-danger fw-bold">
                نظراً لتجاوزكم الحد الأقصى المسموح به قانوناً (8 ساعات غياب غير مبررة)، نعلمكم أنه قد تم اتخاذ قرار **الإقصاء النهائي** والمباشر لكم من المعهد ابتداءً من تاريخ هذا الإشعار.
            </p>
        <?php else: ?>
            <p>
                بموجب هذا، نوجه إليكم هذا **<?= htmlspecialchars($type) ?>** كإجراء تنظيمي رسمي. ندعوكم للالتزام التام بجدول التوقيت والالتحاق فوراً بحصصكم البيداغوجية، ونعلمكم أنه في حال استمرار الغياب وتجاوز المدة القصوى المحددة بـ 8 ساعات، فسيتم تطبيق عقوبة **الإقصاء التام والنهائي** دون إشعار آخر.
            </p>
        <?php endif; ?>
    </div>

    <!-- Footer Signature Block -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <span class="d-block text-muted small">نسخة موجهة إلى:</span>
            <span class="d-block small text-muted">- ملف المتربص بالمعهد</span>
            <span class="d-block small text-muted">- الولي الشرعي</span>
        </div>
        <div class="col-6 text-start">
            <span class="fw-bold d-block mb-5">مدير المؤسسة والتكوين</span>
            <div style="border: 1px dashed #ccc; width: 180px; height: 80px; display: inline-block;" class="d-flex align-items-center justify-content-center text-muted small">
                ختم وتوقيع الإدارة
            </div>
        </div>
    </div>

    <!-- Print control row (hidden during printing) -->
    <div class="mt-5 pt-4 border-top text-center no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-print me-1"></i> بدء طباعة الوثيقة الآن
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 py-2 ms-2" style="border-radius: 8px;">إغلاق النافذة</button>
    </div>
</div>

<style>
/* CSS to hide buttons during browser print */
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background-color: white !important;
    }
    .container {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<script>
// Auto print launch
window.onload = function() {
    setTimeout(function() {
        window.print();
    }, 800);
}
</script>

@endsection
