@extends('layouts.main')
@section('title', 'إعدادات المصادقة الثنائية (MFA) — SGFEP')
@section('content')
<div class="container animate__animated animate__fadeIn py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Alerts for feedback -->
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3" style="background:#f0fdf4; color:#16a34a;">
                    <i class="fa-solid fa-circle-check fs-4"></i>
                    <div class="fw-bold">{{ session('success') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
                    <ul class="mb-0 list-unstyled">
                        @foreach ($errors->all() as $error)
                            <li class="fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- ── Card 1: Active Security Hub Header ─────────────────── -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-body p-4 text-center position-relative" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff;">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 50% 30%, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0) 70%);"></div>
                    
                    <div class="rounded-circle bg-success bg-opacity-20 d-inline-flex align-items-center justify-content-center mb-3 animate__animated animate__pulse animate__infinite" style="width: 80px; height: 80px; border: 2px solid rgba(16, 185, 129, 0.4);">
                        <i class="fa-solid fa-shield-check text-success fs-1"></i>
                    </div>
                    
                    <h3 class="fw-bold mb-1" style="font-family:'Cairo';">حسابك مؤمن بنشاط</h3>
                    <p class="text-white-50 small mb-3">المصادقة الثنائية (MFA) قيد العمل لحماية بياناتك من الوصول غير المصرح به.</p>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <span class="badge bg-white bg-opacity-10 rounded-pill px-3 py-2 text-white small">
                            <i class="fa-regular fa-calendar-check me-1 text-success"></i>
                            تاريخ التفعيل: {{ $user->mfa_enabled_at ? $user->mfa_enabled_at->format('Y-m-d H:i') : 'نشط' }}
                        </span>
                        
                        @if($isMandatory)
                            <span class="badge rounded-pill px-3 py-2 text-warning small" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3);">
                                <i class="fa-solid fa-lock me-1"></i>
                                فرض إلزامي من النظام
                            </span>
                        @else
                            <span class="badge rounded-pill px-3 py-2 text-teal small" style="background: rgba(20, 184, 166, 0.15); border: 1px solid rgba(20, 184, 166, 0.3);">
                                <i class="fa-solid fa-user-check me-1"></i>
                                تفعيل اختياري (شخصي)
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if($isMandatory)
                <!-- Mandatory Warning Box -->
                <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3" style="background:#fffbeb; color:#b45309;">
                    <i class="fa-solid fa-circle-exclamation fs-4"></i>
                    <div>
                        <div class="fw-bold">سياسة الأمان والامتثال للمنصة</div>
                        <div class="small">بناءً على الصلاحيات الممنوحة لحسابك أو توجيهات مدير النظام، يُعتبر تفعيل المصادقة الثنائية أمراً إلزامياً ولا يمكن تعطيله لتجنب ثغرات الحساب.</div>
                    </div>
                </div>
            @endif

            <!-- ── Card 2: Trusted Devices Management ────────────────── -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-2" style="font-family:'Cairo';">
                        <i class="fa-solid fa-laptop-medical text-primary me-2"></i>
                        الأجهزة الموثوقة المسجلة
                    </h5>
                    <p class="text-muted small mb-4">هذه هي المتصفحات والأجهزة التي اخترت "تذكر هذا الجهاز" عليها. لن يُطلب منك إدخال الرمز السداسي (OTP) عليها لمدة 30 يوماً من تاريخ التفعيل.</p>

                    @forelse($trustedDevices as $device)
                        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 rounded-4 border border-light mb-3 hover-shadow transition-all">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; color: #4b5563;">
                                    @if(preg_match('/Windows/i', $device->user_agent))
                                        <i class="fa-brands fa-windows fs-4 text-primary"></i>
                                    @elseif(preg_match('/iPhone|iPad|Macintosh/i', $device->user_agent))
                                        <i class="fa-brands fa-apple fs-4 text-dark"></i>
                                    @elseif(preg_match('/Android/i', $device->user_agent))
                                        <i class="fa-brands fa-android fs-4 text-success"></i>
                                    @else
                                        <i class="fa-solid fa-laptop fs-4"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">{{ $device->device_name }}</div>
                                    <div class="text-muted small" style="font-family:'Outfit'; font-size: 0.8rem;">
                                        IP: {{ $device->ip_address }} | آخر نشاط: {{ $device->last_activity->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ request()->is('sig/*') ? '/sig/security/mfa/device/revoke/'.$device->id : '/security/mfa/device/revoke/'.$device->id }}" class="mt-2 mt-sm-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                    <i class="fa-solid fa-trash-can me-1"></i> إلغاء التوثيق
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-center py-4 bg-light rounded-4 text-muted">
                            <div class="mb-2"><i class="fa-solid fa-laptop-slash fs-3 opacity-50"></i></div>
                            <div class="small">لا توجد أجهزة موثوقة مسجلة حالياً. سيُطلب منك الرمز في كل محاولة دخول.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- ── Card 3: Backup Recovery Codes ────────────────────── -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-2" style="font-family:'Cairo';">
                        <i class="fa-solid fa-key text-primary me-2"></i>
                        رموز الاسترداد الاحتياطية (Backup Codes)
                    </h5>
                    <p class="text-muted small mb-4">إذا فقدت هاتفك أو تعذر عليك تلقي رموز OTP، يمكنك استخدام أحد رموز الاسترداد للدخول. يُرجى الاحتفاظ بها في مكان آمن وتذكر أنه يمكن استخدام كل رمز لمرة واحدة فقط.</p>
                    
                    <div class="bg-light p-3 rounded-4 mb-3 border border-light">
                        <div class="fw-bold text-dark small mb-1">توليد رموز جديدة:</div>
                        <div class="small text-muted">توليد رموز جديدة سيؤدي تلقائياً إلى إلغاء وصلاحية كافة الرموز الاحتياطية السابقة. سنطلب منك إدخال كلمة مرور حسابك لتأكيد العملية.</div>
                    </div>

                    <form method="POST" action="{{ request()->is('sig/*') ? '/sig/security/mfa/recovery-codes/regenerate' : '/security/mfa/recovery-codes/regenerate' }}" class="row g-3 align-items-center">
                        @csrf
                        <div class="col-md-7">
                            <input type="password" name="password" required class="form-control rounded-3" placeholder="أدخل كلمة مرور حسابك الحالية للتأكيد..." style="height: 45px;">
                        </div>
                        <div class="col-md-5 d-grid">
                            <button type="submit" class="btn btn-dark rounded-3 fw-bold" style="height: 45px;">
                                <i class="fa-solid fa-rotate me-1"></i> توليد رموز استرداد جديدة
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Card 4: Deactivate MFA (Only if optional) ─────────── -->
            @if(!$isMandatory)
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-danger bg-danger bg-opacity-5">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-danger mb-2" style="font-family:'Cairo';">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            تعطيل المصادقة الثنائية (MFA)
                        </h5>
                        <p class="text-muted small mb-4">تعطيل المصادقة الثنائية يقلل من أمان حسابك ويجعله عرضة للمخاطر. يرجى التفكير جيداً قبل المضي قدماً.</p>
                        
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small text-muted">يتطلب تعطيل الحماية إدخال كلمة المرور لتأكيد الهوية.</div>
                            <button type="button" class="btn btn-danger rounded-3 fw-bold px-4" onclick="toggleDisableSection()">
                                <i class="fa-solid fa-shield-slash me-1"></i> طلب إيقاف الحماية
                            </button>
                        </div>

                        <!-- Slide down password input for deactivation -->
                        <div id="disableSection" class="mt-4 p-4 rounded-4 border bg-white shadow-sm d-none animate__animated animate__fadeIn">
                            <h6 class="fw-bold text-dark mb-3">تأكيد تعطيل الحماية الثنائية</h6>
                            <form method="POST" action="{{ request()->is('sig/*') ? '/sig/security/mfa/disable' : '/security/mfa/disable' }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label text-muted small">يرجى كتابة كلمة مرور حسابك لتأكيد إيقاف الـ MFA تماماً وحذف مفاتيحك السرية:</label>
                                    <input type="password" name="password" required class="form-control rounded-3" placeholder="كلمة المرور الحالية..." style="height: 45px;">
                                </div>
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-outline-secondary rounded-3" onclick="toggleDisableSection()">تراجع</button>
                                    <button type="submit" class="btn btn-danger rounded-3 fw-bold px-4">
                                        تأكيد الإيقاف التام
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<script>
function toggleDisableSection() {
    let section = document.getElementById('disableSection');
    if (section) {
        section.classList.toggle('d-none');
    }
}
</script>

<style>
.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.transition-all {
    transition: all 0.3s ease;
}
.cursor-pointer {
    cursor: pointer;
}
</style>
@endsection
