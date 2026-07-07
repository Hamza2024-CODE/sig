@extends('layouts.main')

@section('title', $title)

@section('styles')
<style>
/* ── Profile Page Premium Styles ── */
.profile-banner {
    background: linear-gradient(145deg, var(--navy-900) 0%, var(--navy-700) 50%, var(--primary-500) 100%);
    border-radius: var(--r-2xl);
    padding: var(--sp-6) var(--sp-8);
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: var(--sp-6);
    box-shadow: var(--shadow-xl);
}
.profile-banner::before {
    content: '';
    position: absolute;
    top: -30%; right: -5%;
    width: 50%; height: 180%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.07) 0%, transparent 65%);
    pointer-events: none;
}
.profile-banner::after {
    content: '';
    position: absolute;
    bottom: -20px; left: -20px;
    width: 140px; height: 140px;
    border-radius: 50%;
    border: 30px solid rgba(255,255,255,0.04);
    pointer-events: none;
}
.profile-banner-title {
    font-size: var(--text-xl);
    font-weight: 900;
    margin-bottom: 4px;
    position: relative;
    z-index: 1;
}
.profile-banner-sub {
    font-size: var(--text-sm);
    opacity: .7;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

/* Avatar section */
.avatar-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-sm);
    padding: var(--sp-6);
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    transition: all var(--d-normal) var(--ease);
}
.avatar-card:hover { box-shadow: var(--shadow-md); }

.avatar-frame {
    position: relative;
    width: 130px;
    height: 130px;
    margin-bottom: var(--sp-4);
}
.avatar-frame img,
.avatar-fallback {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--border);
    box-shadow: var(--shadow-md);
    transition: box-shadow var(--d-normal);
}
.avatar-frame:hover img,
.avatar-frame:hover .avatar-fallback {
    box-shadow: 0 0 0 4px var(--primary-glow), var(--shadow-lg);
}
.avatar-fallback {
    background: linear-gradient(145deg, var(--navy-800) 0%, var(--primary-400) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.2rem;
    font-weight: 900;
    color: #fff;
}
.avatar-edit-btn {
    position: absolute;
    bottom: 4px;
    left: 4px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--primary-400);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 3px solid var(--bg-card);
    font-size: .8rem;
    box-shadow: var(--shadow-sm);
    transition: all var(--d-normal) var(--ease-spring);
}
.avatar-edit-btn:hover {
    background: var(--primary-500);
    transform: scale(1.12);
}
.role-pill {
    display: inline-flex;
    align-items: center;
    padding: .35rem 1rem;
    background: var(--primary-glow);
    border: 1px solid var(--primary-border);
    border-radius: var(--r-full);
    color: var(--primary-400);
    font-size: var(--text-xs);
    font-weight: 800;
    margin-bottom: var(--sp-2);
}
.avatar-meta {
    width: 100%;
    border-top: 1px solid var(--border);
    padding-top: var(--sp-4);
    margin-top: var(--sp-4);
    text-align: end;
}
.meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--sp-2);
}
.meta-label { font-size: var(--text-xs); font-weight: 700; color: var(--tx-3); text-transform: uppercase; letter-spacing: .4px; }
.meta-value { font-size: var(--text-sm); font-weight: 700; color: var(--tx-1); font-family: var(--font-mono); }

/* Form card */
.form-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-sm);
    padding: var(--sp-6);
    transition: all var(--d-normal) var(--ease);
}
.form-card:hover { box-shadow: var(--shadow-md); }
.form-section-title {
    font-size: var(--text-md);
    font-weight: 800;
    color: var(--tx-1);
    margin-bottom: var(--sp-5);
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding-bottom: var(--sp-3);
    border-bottom: 1px solid var(--border);
}
.form-section-title i {
    width: 32px;
    height: 32px;
    border-radius: var(--r-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .88rem;
    background: var(--primary-glow);
    color: var(--primary-400);
}
.password-section-header {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    font-size: var(--text-md);
    font-weight: 800;
    color: var(--tx-1);
    margin-top: var(--sp-6);
    margin-bottom: var(--sp-2);
    padding-top: var(--sp-5);
    border-top: 1px solid var(--border);
}
.password-section-header i { color: var(--warning); }
.password-hint {
    font-size: var(--text-sm);
    color: var(--tx-3);
    font-weight: 600;
    margin-bottom: var(--sp-4);
}
.input-wrapper {
    position: relative;
}
.input-wrapper .input-icon {
    position: absolute;
    top: 50%; right: 14px;
    transform: translateY(-50%);
    color: var(--tx-3);
    font-size: .9rem;
    pointer-events: none;
    transition: color var(--d-fast);
    z-index: 2;
}
.input-wrapper .form-control {
    padding-right: 2.75rem !important;
}
.input-wrapper .form-control:focus ~ .input-icon { color: var(--primary-400); }
.form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--sp-3);
    margin-top: var(--sp-6);
    padding-top: var(--sp-5);
    border-top: 1px solid var(--border);
}
</style>
@endsection

@section('content')
@php
$avatarUrl = !empty($user['avatar']) ? $user['avatar'] : null;
if ($avatarUrl) {
    $avatarUrl = ltrim(str_replace('/sig/uploads/avatars/', 'uploads/avatars/', $avatarUrl), '/');
    $avatarUrl = asset($avatarUrl);
}
$initials = mb_strtoupper(mb_substr($user['nom_complet'], 0, 1));
@endphp

<!-- Page Banner -->
<div class="profile-banner">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="profile-banner-title">
                <i class="fa-solid fa-user-gear me-2"></i>إعدادات الحساب والملف الشخصي
            </h1>
            <p class="profile-banner-sub">تعديل البيانات الإدارية، كلمة المرور، والصورة الرمزية</p>
        </div>
        <a href="{{ url('dashboard') }}" class="btn btn-light px-4 fw-bold shadow-sm" style="border-radius: var(--r-md);">
            <i class="fa-solid fa-house me-1"></i> لوحة التحكم
        </a>
    </div>
</div>

<div style="max-width: 960px; margin: 0 auto;">

    @if (session('success'))
        <div class="alert alert-success mb-4 fw-bold d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check fa-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-4 fw-bold d-flex align-items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ request()->is('sig/*') ? url('sig/dashboard/profile/update') : url('dashboard/profile/update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">

            <!-- Avatar Column -->
            <div class="col-md-4">
                <div class="avatar-card">
                    <div class="avatar-frame">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" id="avatarPreview" alt="صورة الملف">
                        @else
                            <div class="avatar-fallback" id="avatarFallback">{{ $initials }}</div>
                            <img id="avatarPreview" class="d-none" src="" alt="صورة الملف">
                        @endif
                        <label for="avatarInput" class="avatar-edit-btn" title="تغيير الصورة">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                        <input type="file" name="avatar" id="avatarInput" class="d-none"
                               accept="image/jpeg,image/png,image/gif"
                               onchange="previewProfileImage(this)">
                    </div>

                    <div class="role-pill">
                        <i class="fa-solid fa-shield-halved me-1"></i>
                        {{ $user['role_ar'] }}
                    </div>

                    <p class="text-muted small mb-0 mt-1" style="font-size: var(--text-xs);">
                        JPG, PNG, GIF — الحجم الأقصى: <strong>2 MB</strong>
                    </p>

                    <div class="avatar-meta">
                        <div class="meta-row">
                            <span class="meta-label">رقم الحساب</span>
                            <span class="meta-value">#{{ $user['id'] }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">تاريخ الإنشاء</span>
                            <span class="meta-value">{{ date('d/m/Y', strtotime($user['created_at'])) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Column -->
            <div class="col-md-8">
                <div class="form-card">

                    <h2 class="form-section-title">
                        <i class="fa-solid fa-address-card"></i>
                        معلومات الحساب الأساسية
                    </h2>

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label class="form-label">الاسم واللقب / Nom complet</label>
                            <div class="input-wrapper">
                                <input type="text" name="nom_complet"
                                       value="{{ $user['nom_complet'] }}"
                                       class="form-control" required>
                                <i class="fa-solid fa-user input-icon"></i>
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="col-md-6">
                            <label class="form-label">اسم المستخدم / Nom d'utilisateur</label>
                            <div class="input-wrapper">
                                <input type="text" name="username"
                                       value="{{ $user['username'] }}"
                                       class="form-control" required style="font-family: var(--font-mono);">
                                <i class="fa-solid fa-at input-icon"></i>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-12">
                            <label class="form-label">البريد الإلكتروني / Adresse Email</label>
                            <div class="input-wrapper">
                                <input type="email" name="email"
                                       value="{{ $user['email'] }}"
                                       class="form-control" required style="font-family: var(--font-mono);">
                                <i class="fa-solid fa-envelope input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="password-section-header">
                        <i class="fa-solid fa-key"></i>
                        تغيير كلمة المرور <small class="text-muted fw-normal" style="font-size: var(--text-xs);">(اختياري)</small>
                    </div>
                    <p class="password-hint">اترك الحقول فارغة إذا لم تكن ترغب في تغيير كلمة المرور حالياً.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">كلمة المرور الجديدة</label>
                            <div class="input-wrapper">
                                <input type="password" name="new_password"
                                       placeholder="—" class="form-control"
                                       style="font-family: var(--font-mono);">
                                <i class="fa-solid fa-lock input-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تأكيد كلمة المرور</label>
                            <div class="input-wrapper">
                                <input type="password" name="confirm_password"
                                       placeholder="—" class="form-control"
                                       style="font-family: var(--font-mono);">
                                <i class="fa-solid fa-shield-halved input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <a href="{{ url('dashboard') }}" class="btn btn-light px-4 fw-bold">
                            <i class="fa-solid fa-xmark me-1"></i> إلغاء
                        </a>
                        <button type="submit" class="btn btn-primary px-5 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> حفظ التحديثات
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            const fallback = document.getElementById('avatarFallback');
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            }
            if (fallback) fallback.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
