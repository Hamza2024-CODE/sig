@extends('layouts.main')
@section('title', 'رموز الاسترداد الاحتياطية — SGFEP')
@section('content')
<div class="container animate__animated animate__fadeIn py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header border-0 text-white p-4 text-center" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-key text-warning fs-1"></i>
                    </div>
                    <h4 class="fw-bold mb-1" style="font-family:'Cairo';">رموز الاسترداد الاحتياطية</h4>
                    <p class="mb-0 text-white-50 small">احفظ هذه الرموز في مكان آمن لاستعادة الوصول لحسابك</p>
                </div>
                
                <div class="card-body p-4">
                    <div class="alert alert-danger border-0 rounded-4 d-flex align-items-start gap-3 mb-4" style="background-color: #fef2f2; color: #991b1b;">
                        <i class="fa-solid fa-triangle-exclamation fs-4 mt-1"></i>
                        <div>
                            <div class="fw-bold" style="font-family:'Cairo';">تحذير أمني هام جداً</div>
                            <div class="small">هذه الرموز تُعرض **لمرة واحدة فقط**. إذا فقدت هاتفك أو تعذر الوصول إلى تطبيق المصادقة، ستكون هذه الرموز هي وسيلتك الوحيدة لتسجيل الدخول إلى حسابك. قم بطباعتها أو كتابتها أو تحميلها فوراً.</div>
                        </div>
                    </div>

                    <!-- Recovery codes grid -->
                    <div class="bg-light p-4 rounded-4 border border-light mb-4">
                        <div class="row g-2 text-center" id="codesContainer">
                            @foreach($recoveryCodes as $code)
                                <div class="col-6 py-2">
                                    <span class="d-block bg-white border rounded-3 p-2 fw-bold text-dark code-item" style="font-family:'Outfit'; font-size:1.1rem; letter-spacing:1px;">{{ $code }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <button onclick="downloadCodes()" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-bold">
                                <i class="fa-solid fa-download me-1"></i> تحميل الرموز
                            </button>
                        </div>
                        <div class="col-6">
                            <button onclick="copyAllCodes()" class="btn btn-outline-secondary w-100 rounded-3 py-2 fw-bold" id="copyAllBtn">
                                <i class="fa-regular fa-copy me-1"></i> نسخ الكل
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <a href="{{ request()->is('sig/*') ? '/sig/dashboard' : '/dashboard' }}" class="btn btn-primary btn-lg rounded-4 fw-bold" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                            <i class="fa-solid fa-circle-check me-2"></i> لقد قمت بحفظها، الدخول للوحة التحكم
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyAllCodes() {
    var codeItems = document.querySelectorAll('.code-item');
    var codesText = "";
    codeItems.forEach(function(item) {
        codesText += item.textContent + "\n";
    });
    
    navigator.clipboard.writeText(codesText.trim());
    
    var btn = document.getElementById("copyAllBtn");
    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> تم النسخ';
    btn.className = "btn btn-success w-100 rounded-3 py-2 fw-bold";
    
    setTimeout(function() {
        btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> نسخ الكل';
        btn.className = "btn btn-outline-secondary w-100 rounded-3 py-2 fw-bold";
    }, 2500);
}

function downloadCodes() {
    var codeItems = document.querySelectorAll('.code-item');
    var codesText = "SGFEP BACKUP RECOVERY CODES\n";
    codesText += "Generated At: " + new Date().toLocaleString() + "\n";
    codesText += "-----------------------------\n";
    codeItems.forEach(function(item, idx) {
        codesText += (idx + 1) + ". " + item.textContent + "\n";
    });
    codesText += "-----------------------------\n";
    codesText += "WARNING: Keep these codes secret and secure.\n";
    
    var blob = new Blob([codesText], { type: 'text/plain;charset=utf-8' });
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "sgfep_security_recovery_codes.txt";
    link.click();
}
</script>
@endsection
