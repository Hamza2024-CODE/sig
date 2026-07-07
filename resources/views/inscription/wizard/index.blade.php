@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<div class="wizard-page-wrapper container animate__animated animate__fadeIn" style="margin-top: 5rem; margin-bottom: 8rem;">
    <div class="wizard-container-premium text-center p-5" style="background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(72, 43, 143, 0.08); border: 1px solid #e2e8f0; max-width: 800px; margin: 0 auto;">
        
        <!-- Sovereign Emblem Header -->
        <div class="mb-4">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Sovereign Seal" style="width: 100px; height: auto; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.05));">
        </div>
        
        <h2 class="fw-bold mb-2" style="font-family: 'Cairo'; color: var(--color-gov-purple-dark); font-size: 1.8rem;">
            استمارة التسجيل الأولي موحدة وطنيّاً
        </h2>
        <p class="text-muted mb-4 small" style="font-size: 0.95rem;">الجمهورية الجزائرية الديمقراطية الشعبية - وزارة التكوين والتعليم المهنيين</p>
        
        <div class="cyber-line-pulse mx-auto mb-4" style="max-width: 250px;"></div>
        
        <!-- Premium Notice Box -->
        <div class="alert alert-warning border-0 p-4 mb-4 text-start animate__animated animate__zoomIn" style="background-color: rgba(255, 193, 7, 0.07); color: #664d03; border-radius: 16px; border: 1px solid rgba(255, 193, 7, 0.15) !important;">
            <div class="d-flex align-items-start gap-3">
                <div class="bg-warning text-white p-2 rounded-circle" style="--bs-bg-opacity: .15;">
                    <i class="fa-solid fa-triangle-exclamation text-warning" style="font-size: 1.3rem;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-2" style="font-family: 'Cairo'; font-size: 1rem;">إعلان هام للمترشحين / Avis Important</h5>
                    <p class="m-0 small" style="line-height: 1.6; font-weight: 500;">
                        تزامناً مع الرقمنة الشاملة للقطاع وتوحيد القنوات الرقمية، تم <strong>إيقاف التسجيل الأولي المباشر</strong> عبر هذه المنصة المحلية. جميع طلبات التسجيل تُجرى الآن حصرياً وبصفة موحدة عبر البوابة الوطنية الرسمية للتسجيلات <strong>(تكوين)</strong>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Features of Centralized Registration -->
        <div class="row g-3 mb-5 text-start">
            <div class="col-md-6">
                <div class="p-3 rounded h-100" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px !important;">
                    <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2" style="font-family: 'Cairo'; font-size: 0.88rem;">
                        <i class="fa-solid fa-circle-check text-success"></i>
                        قناة وطنية موحدة
                    </h6>
                    <p class="m-0 text-muted small" style="font-size: 0.78rem; line-height: 1.5;">
                        تسجيل واحد يتيح لك الترشح لمختلف المعاهد والمراكز الوطنية عبر كامل التراب الوطني بكل مرونة.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded h-100" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px !important;">
                    <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2" style="font-family: 'Cairo'; font-size: 0.88rem;">
                        <i class="fa-solid fa-cloud-arrow-down text-primary"></i>
                        مزامنة تلقائية وآمنة
                    </h6>
                    <p class="m-0 text-muted small" style="font-size: 0.78rem; line-height: 1.5;">
                        بمجرد تأكيد تسجيلك في منصة (تكوين)، ستنتقل بياناتك ووثائقك تلقائياً لملفك هنا عبر تكامل الـ API الآمن.
                    </p>
                </div>
            </div>
        </div>

        <!-- Call to Action Button -->
        <div class="d-flex flex-column align-items-center gap-3">
            <a href="https://takwin.dz/" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg px-5 py-3 fw-bold d-inline-flex align-items-center gap-2 animate__animated animate__pulse animate__infinite" style="border-radius: 14px; font-family: 'Cairo'; font-size: 1.05rem; box-shadow: 0 8px 24px rgba(72, 43, 143, 0.25);">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                الانتقال للتسجيل في منصة takwin.dz
            </a>
            <a href="/login" class="text-decoration-none small text-muted fw-bold mt-2 hover-primary">
                <i class="fa-solid fa-arrow-left me-1"></i> العودة لصفحة تسجيل الدخول للمستخدمين
            </a>
        </div>
        
    </div>
</div>

<style>
.wizard-container-premium {
    background: white;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
}
.hover-primary:hover {
    color: var(--color-gov-purple) !important;
}
</style>

@endsection
