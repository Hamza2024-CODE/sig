@extends('layouts.main')
@section('title', 'إدارة المصادقة الثنائية (MFA) للنظام — SGFEP')
@section('content')
<div class="animate__animated animate__fadeIn py-4">

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family:'Cairo';">
                <i class="fa-solid fa-user-shield text-primary me-2"></i>
                إدارة المصادقة الثنائية (MFA) للمنصة
            </h3>
            <p class="text-muted small mb-0">لوحة تحكم المسؤول لإعداد سياسات التحقق وتعيين الاستثناءات الفردية للحسابات.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="{{ request()->is('sig/*') ? '/sig/admin/security' : '/admin/security' }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-shield-halved me-1"></i> مركز الأمان
            </a>
            <a href="{{ request()->is('sig/*') ? '/sig/dashboard' : '/dashboard' }}" class="btn btn-outline-dark rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> لوحة التحكم
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3" style="background:#f0fdf4; color:#16a34a;">
            <i class="fa-solid fa-circle-check fs-4"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3" style="background:#fef2f2; color:#dc2626;">
            <i class="fa-solid fa-circle-exclamation fs-4"></i>
            <div class="fw-bold">{{ session('error') }}</div>
        </div>
    @endif

    <div class="row g-4">
        <!-- ── Left Column: Global Policy Control ───────────────────── -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header border-0 text-white p-4" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
                    <h5 class="fw-bold mb-1" style="font-family:'Cairo';">السياسة الأمنية العامة</h5>
                    <p class="mb-0 text-white-50 small">اختر كيفية فرض الـ MFA على مستوى المنصة</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ request()->is('sig/*') ? '/sig/admin/security/mfa/global' : '/admin/security/mfa/global' }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-3">سياسة الفرض الحالية:</label>
                            
                            <!-- Option 1: Everyone -->
                            <div class="form-check p-3 rounded-4 border mb-3 cursor-pointer hover-shadow transition-all {{ $globalMode === 'everyone' ? 'border-primary bg-primary bg-opacity-10' : 'border-light' }}" onclick="selectPolicy('everyone')">
                                <input class="form-check-input ms-2 float-end" type="radio" name="global_mode" id="policy_everyone" value="everyone" {{ $globalMode === 'everyone' ? 'checked' : '' }}>
                                <label class="form-check-label w-100 cursor-pointer" for="policy_everyone">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-users text-primary"></i>
                                        فرض على الجميع
                                    </div>
                                    <div class="small text-muted mt-1">يُجبر كل مستخدم، معهد، أو مؤطر على تفعيل الـ MFA قبل استخدام حسابه.</div>
                                </label>
                            </div>

                            <!-- Option 2: Sensitive Only -->
                            <div class="form-check p-3 rounded-4 border mb-3 cursor-pointer hover-shadow transition-all {{ $globalMode === 'sensitive' ? 'border-indigo bg-indigo bg-opacity-10' : 'border-light' }}" onclick="selectPolicy('sensitive')">
                                <input class="form-check-input ms-2 float-end" type="radio" name="global_mode" id="policy_sensitive" value="sensitive" {{ $globalMode === 'sensitive' ? 'checked' : '' }}>
                                <label class="form-check-label w-100 cursor-pointer" for="policy_sensitive">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-user-gear text-indigo" style="color: #6366f1;"></i>
                                        الأدوار الحساسة فقط
                                    </div>
                                    <div class="small text-muted mt-1">يُفرض إجبارياً على المدراء والمسؤولين والمؤسسات، ويكون اختيارياً للبقية.</div>
                                </label>
                            </div>

                            <!-- Option 3: Optional -->
                            <div class="form-check p-3 rounded-4 border mb-3 cursor-pointer hover-shadow transition-all {{ $globalMode === 'optional' ? 'border-teal bg-teal bg-opacity-10' : 'border-light' }}" onclick="selectPolicy('optional')">
                                <input class="form-check-input ms-2 float-end" type="radio" name="global_mode" id="policy_optional" value="optional" {{ $globalMode === 'optional' ? 'checked' : '' }}>
                                <label class="form-check-label w-100 cursor-pointer" for="policy_optional">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-hand-holding-hand text-teal" style="color: #14b8a6;"></i>
                                        اختياري للجميع
                                    </div>
                                    <div class="small text-muted mt-1">يقرر المستخدم بنفسه من إعدادات حسابه تفعيل المصادقة أو تعطيلها.</div>
                                </label>
                            </div>

                            <!-- Option 4: Disabled -->
                            <div class="form-check p-3 rounded-4 border mb-3 cursor-pointer hover-shadow transition-all {{ $globalMode === 'disabled' ? 'border-danger bg-danger bg-opacity-10' : 'border-light' }}" onclick="selectPolicy('disabled')">
                                <input class="form-check-input ms-2 float-end" type="radio" name="global_mode" id="policy_disabled" value="disabled" {{ $globalMode === 'disabled' ? 'checked' : '' }}>
                                <label class="form-check-label w-100 cursor-pointer" for="policy_disabled">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-ban text-danger"></i>
                                        تعطيل الخدمة بالكامل
                                    </div>
                                    <div class="small text-muted mt-1">إغلاق وتخطي نظام المصادقة الثنائية لكامل المنصة إلا ما تم فرضه يدوياً.</div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold" style="background: linear-gradient(135deg, #0f172a, #2563eb); border:none;">
                            <i class="fa-solid fa-floppy-disk me-1"></i> حفظ السياسة الأمنية
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Information Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-light p-4">
                <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo';">
                    <i class="fa-solid fa-circle-info text-primary me-1"></i>
                    تنبيهات إدارية
                </h6>
                <p class="small text-muted mb-2">تطبيق السياسة الأمنية العامة لا يلغي الاستثناءات الفردية المحددة بالأسفل.</p>
                <p class="small text-muted mb-0">في حال فقدان أحد المستخدمين لجهازه الخاص بالتحقق، قم بالبحث عن حسابه في الجدول وانقر على زر <strong>إعادة التعيين الطارئة</strong> لمسح الـ Secret وتمكينه من الدخول مجدداً.</p>
            </div>
        </div>

        <!-- ── Right Column: User Administration ──────────────────── -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <!-- Tabs for User Tables -->
                    <ul class="nav nav-pills nav-fill bg-light p-1 rounded-4 mb-4" id="user-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link py-3 fw-bold rounded-3 {{ $type === 'utilisateur' ? 'active bg-white text-primary shadow-sm' : 'text-muted' }}" 
                               href="{{ request()->fullUrlWithQuery(['type' => 'utilisateur', 'page' => 1]) }}">
                                <i class="fa-solid fa-user-shield me-1"></i> المسؤولون والموظفون
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-3 fw-bold rounded-3 {{ $type === 'etablissement' ? 'active bg-white text-primary shadow-sm' : 'text-muted' }}" 
                               href="{{ request()->fullUrlWithQuery(['type' => 'etablissement', 'page' => 1]) }}">
                                <i class="fa-solid fa-school me-1"></i> مؤسسات التكوين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-3 fw-bold rounded-3 {{ $type === 'encadrement' ? 'active bg-white text-primary shadow-sm' : 'text-muted' }}" 
                               href="{{ request()->fullUrlWithQuery(['type' => 'encadrement', 'page' => 1]) }}">
                                <i class="fa-solid fa-graduation-cap me-1"></i> المؤطرون والأساتذة
                            </a>
                        </li>
                    </ul>

                    <!-- Search Form -->
                    <form method="GET" action="" class="mb-4">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-4">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 py-3 rounded-end-0" placeholder="ابحث بالاسم أو معرف الدخول..." value="{{ $search }}" style="border-radius: 0 12px 12px 0;">
                            <button type="submit" class="btn btn-dark px-4 rounded-end-4 fw-bold">بحث</button>
                            @if(!empty($search))
                                <a href="{{ request()->fullUrlWithQuery(['search' => '']) }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-center px-3" style="border-radius: 0;">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </form>

                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-muted small">
                                    <th class="py-3 px-3">المستخدم</th>
                                    <th class="py-3">معرف الدخول</th>
                                    <th class="py-3 text-center">حالة الـ MFA</th>
                                    <th class="py-3 text-center">السياسة النشطة</th>
                                    <th class="py-3 text-center" style="width: 250px;">إجراءات التحكم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $u)
                                    <tr>
                                        <td class="py-3 px-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center fw-bold text-primary" style="width: 38px; height: 38px; font-size: 0.9rem;">
                                                    {{ mb_substr($type === 'etablissement' ? ($u->Nom_Etablissement ?? 'م') : ($u->Nom ?? $u->nom ?? 'م'), 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark small">{{ $type === 'etablissement' ? $u->Nom_Etablissement : ($u->Nom ?? ($u->nom . ' ' . $u->prenom)) }}</div>
                                                    <span class="badge bg-light text-muted small" style="font-size: 0.65rem;">
                                                        @if($type === 'utilisateur')
                                                            {{ $u->role_code ?? 'موظف' }}
                                                        @elseif($type === 'etablissement')
                                                            مؤسسة
                                                        @else
                                                            مؤطر / أستاذ
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small fw-bold" style="font-family: 'Outfit';">
                                            {{ $type === 'etablissement' ? $u->nomUser : ($type === 'encadrement' ? $u->nin : $u->NomUser) }}
                                        </td>
                                        <td class="text-center">
                                            @if($u->mfa_enabled)
                                                <span class="badge rounded-pill px-3 py-1 bg-success bg-opacity-10 text-success small">
                                                    <i class="fa-solid fa-shield-check me-1"></i> مفعّل
                                                </span>
                                            @else
                                                <span class="badge rounded-pill px-3 py-1 bg-secondary bg-opacity-10 text-muted small">
                                                    غير نشط
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($u->mfa_policy === 'forced_global')
                                                <span class="badge rounded-pill px-2 py-1 bg-purple bg-opacity-10 text-purple small" style="color:#7c3aed; background:#f5f3ff;">
                                                    فرض عام (النظام)
                                                </span>
                                            @elseif($u->mfa_policy === 'forced_sensitive')
                                                <span class="badge rounded-pill px-2 py-1 bg-indigo bg-opacity-10 text-indigo small" style="color:#4f46e5; background:#eef2ff;">
                                                    فرض دور (حساس)
                                                </span>
                                            @elseif($u->mfa_policy === 'forced_admin')
                                                <span class="badge rounded-pill px-2 py-1 bg-primary bg-opacity-10 text-primary small" style="color:#2563eb; background:#eff6ff;">
                                                    فرض إداري (المدير)
                                                </span>
                                            @elseif($u->mfa_policy === 'exempted')
                                                <span class="badge rounded-pill px-2 py-1 bg-success bg-opacity-10 text-success small" style="color:#16a34a; background:#f0fdf4;">
                                                    معفى بأمر المدير
                                                </span>
                                            @elseif($u->mfa_policy === 'disabled')
                                                <span class="badge rounded-pill px-2 py-1 bg-danger bg-opacity-10 text-danger small" style="color:#dc2626; background:#fef2f2;">
                                                    سياسة معطلة
                                                </span>
                                            @else
                                                <span class="badge rounded-pill px-2 py-1 bg-info bg-opacity-10 text-info small" style="color:#0891b2; background:#ecfeff;">
                                                    اختياري للمستخدم
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <!-- Action Form: Force / Exempt / Default -->
                                                <form method="POST" action="{{ request()->is('sig/*') ? '/sig/admin/security/mfa/user/toggle' : '/admin/security/mfa/user/toggle' }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="user_key" value="{{ $u->user_key }}">
                                                    
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <!-- Force Button -->
                                                        <button type="submit" name="action" value="force" class="btn btn-outline-primary {{ $u->mfa_policy === 'forced_admin' ? 'active' : '' }}" title="فرض المصادقة الثنائية بالاسم">
                                                            <i class="fa-solid fa-lock"></i> فرض
                                                        </button>
                                                        <!-- Exempt Button -->
                                                        <button type="submit" name="action" value="exempt" class="btn btn-outline-success {{ $u->mfa_policy === 'exempted' ? 'active' : '' }}" title="إعفاء المستخدم من الـ MFA">
                                                            <i class="fa-solid fa-shield-slash"></i> إعفاء
                                                        </button>
                                                        <!-- Default Button -->
                                                        <button type="submit" name="action" value="default" class="btn btn-outline-secondary {{ (!in_array($u->user_key, $forcedUsers) && !in_array($u->user_key, $exemptedUsers)) ? 'active' : '' }}" title="إعادة للوضع التلقائي للنظام">
                                                            تلقائي
                                                        </button>
                                                    </div>
                                                </form>

                                                <!-- Emergency Reset Button (Red) -->
                                                @if($u->mfa_enabled || $u->google2fa_secret)
                                                    <form method="POST" action="{{ request()->is('sig/*') ? '/sig/admin/security/mfa/user/reset' : '/admin/security/mfa/user/reset' }}" class="d-inline" onsubmit="return confirm('تحذير: هل أنت متأكد من رغبتك في إعادة تعيين المصادقة الثنائية لهذا المستخدم؟ سيؤدي ذلك لإيقاف حماية حسابه وحذف رموزه السرية وأجهزته الموثوقة فوراً مما يتيح له الدخول دون تحقق.')">
                                                        @csrf
                                                        <input type="hidden" name="user_key" value="{{ $u->user_key }}">
                                                        <button type="submit" class="btn btn-sm btn-danger rounded-3" title="إعادة تعيين أمني طارئ (فقدان الجهاز)">
                                                            <i class="fa-solid fa-power-off"></i> تعيين
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <div class="mb-2"><i class="fa-solid fa-users-slash fs-2 opacity-50"></i></div>
                                            <div>لا يوجد مستخدمون مطابقون لمعيار البحث الحالي.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($users->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectPolicy(mode) {
    // Check the corresponding radio button
    document.getElementById('policy_' + mode).checked = true;
    
    // Remove active styles from all option blocks
    document.querySelectorAll('.form-check').forEach(el => {
        el.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10', 
                            'border-indigo', 'bg-indigo', 'bg-opacity-10',
                            'border-teal', 'bg-teal', 'bg-opacity-10',
                            'border-danger', 'bg-danger', 'bg-opacity-10');
        el.classList.add('border-light');
    });

    // Add active style to the selected block
    let activeBlock = document.getElementById('policy_' + mode).closest('.form-check');
    activeBlock.classList.remove('border-light');
    
    if (mode === 'everyone') {
        activeBlock.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
    } else if (mode === 'sensitive') {
        activeBlock.classList.add('border-indigo', 'bg-indigo', 'bg-opacity-10');
    } else if (mode === 'optional') {
        activeBlock.classList.add('border-teal', 'bg-teal', 'bg-opacity-10');
    } else if (mode === 'disabled') {
        activeBlock.classList.add('border-danger', 'bg-danger', 'bg-opacity-10');
    }
}
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}
.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.transition-all {
    transition: all 0.3s ease;
}
</style>
@endsection
