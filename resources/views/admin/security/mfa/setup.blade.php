@extends('layouts.main')
@section('title', 'إعداد المصادقة الثنائية (MFA) — SGFEP')
@section('content')
<div class="container animate__animated animate__fadeIn py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Alert message if coming with warning -->
            @if(session('warning'))
                <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3" style="background:#fffbeb; color:#b45309;">
                    <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                    <div>
                        <div class="fw-bold">تنبيه أمني إلزامي</div>
                        <div class="small">{{ session('warning') }}</div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header border-0 text-white p-4 text-center" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
                    <div class="rounded-circle bg-white bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-shield-halved text-warning fs-1"></i>
                    </div>
                    <h4 class="fw-bold mb-1" style="font-family:'Cairo';">تفعيل المصادقة الثنائية (MFA)</h4>
                    <p class="mb-0 text-white-50 small">حماية حسابك عبر رمز تحقق ديناميكي (TOTP)</p>
                </div>
                
                <div class="card-body p-4">
                    <!-- Steps description -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo';">الخطوة 1: مسح الرمز المربع (QR Code)</h6>
                        <p class="text-muted small mb-3">قم بفتح تطبيق المصادقة الخاص بك (مثل Google Authenticator أو Microsoft Authenticator) وامسح الرمز التالي:</p>
                        
                        <div class="text-center bg-light p-4 rounded-4 border border-light mb-3">
                            @if(!empty($qrCodeImage))
                                <img src="{{ $qrCodeImage }}" alt="MFA QR Code" class="img-fluid rounded border shadow-sm bg-white p-2" style="max-height: 200px;">
                            @else
                                <div class="alert alert-danger mb-0">خطأ في توليد رمز QR. يرجى إدخال الرمز السري يدوياً.</div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo';">الخطوة 2: الإدخال اليدوي (إذا تعذر المسح)</h6>
                        <p class="text-muted small mb-2">إذا كنت لا تستطيع مسح رمز QR، يمكنك إدخال المفتاح السري التالي في تطبيقك يدوياً:</p>
                        <div class="input-group mb-3">
                            <input type="text" id="secretKey" class="form-control text-center fw-bold bg-light" readonly value="{{ $secret }}" style="font-family:'Outfit'; font-size:1.1rem; letter-spacing:1px; border:1px dashed #cbd5e1;">
                            <button class="btn btn-outline-primary" type="button" onclick="copySecretKey()" id="copyBtn">
                                <i class="fa-regular fa-copy me-1"></i> نسخ
                            </button>
                        </div>
                    </div>

                    <hr class="my-4 border-light">

                    <form method="POST" action="{{ request()->is('sig/*') ? '/sig/security/mfa/setup/confirm' : '/security/mfa/setup/confirm' }}">
                        @csrf
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo';">الخطوة 3: تأكيد التفعيل</h6>
                            <label for="otp" class="form-label text-muted small">أدخل الرمز المكون من 6 أرقام الظاهر في تطبيق المصادقة الخاص بك لتأكيد الربط:</label>
                            <input type="text" name="otp" id="otp" required autocomplete="off" class="form-control form-control-lg text-center fw-bold" placeholder="000000" maxlength="6" style="font-family:'Outfit'; font-size:1.8rem; letter-spacing: 5px; border-radius: 12px; border:2px solid #cbd5e1;">
                            @error('otp')
                                <div class="text-danger small mt-2 fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-4 fw-bold" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                                <i class="fa-solid fa-lock-open me-2"></i> تأكيد وتفعيل الحماية
                            </button>
                            <a href="/logout" class="btn btn-link text-muted small mt-2">تسجيل الخروج والعودة لاحقاً</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copySecretKey() {
    var copyText = document.getElementById("secretKey");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    
    var btn = document.getElementById("copyBtn");
    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> تم النسخ';
    btn.classList.replace("btn-outline-primary", "btn-success");
    
    setTimeout(function() {
        btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> نسخ';
        btn.classList.replace("btn-success", "btn-outline-primary");
    }, 2500);
}
</script>
@endsection
