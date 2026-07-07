@extends('layouts.public')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<style>
.reset-page-wrap {
    min-height: calc(100vh - 220px);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem 1.5rem;
}
.reset-card {
    width: 100%;
    max-width: 500px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r-2xl);
    box-shadow: var(--shadow-xl);
    padding: 3rem 2.5rem;
    position: relative;
    overflow: hidden;
    transition: all var(--d-normal) var(--ease);
}
.reset-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-400), var(--primary-300), var(--info));
    border-radius: var(--r-2xl) var(--r-2xl) 0 0;
}
.reset-card::after {
    content: '';
    position: absolute;
    bottom: -40px;
    left: -40px;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: var(--primary-glow);
    pointer-events: none;
}
.reset-logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(145deg, var(--navy-800) 0%, var(--primary-400) 100%);
    border-radius: var(--r-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    box-shadow: var(--shadow-primary);
}
.reset-logo img {
    width: 40px;
    height: 40px;
    object-fit: contain;
    filter: brightness(0) invert(1);
}
.reset-title {
    font-size: var(--text-xl);
    font-weight: 900;
    color: var(--tx-1);
    text-align: center;
    margin-bottom: 4px;
    letter-spacing: -.3px;
}
.reset-subtitle {
    font-size: var(--text-sm);
    color: var(--tx-3);
    font-weight: 600;
    text-align: center;
    margin-bottom: 2rem;
}
.reset-input-group {
    position: relative;
    margin-bottom: 1.25rem;
}
.reset-input-group label {
    display: block;
    font-size: var(--text-xs);
    font-weight: 700;
    color: var(--tx-2);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 6px;
}
.reset-input-group .input-icon {
    position: absolute;
    left: 14px;
    bottom: 12px;
    color: var(--tx-3);
    font-size: .9rem;
    pointer-events: none;
    transition: color var(--d-fast);
}
[dir="rtl"] .reset-input-group .input-icon { left: auto; right: 14px; }
.reset-input-group input {
    width: 100%;
    background: var(--bg-app) !important;
    border: 1.5px solid var(--border) !important;
    border-radius: var(--r-md) !important;
    padding: .75rem 1rem .75rem 2.75rem;
    color: var(--tx-1) !important;
    font-family: var(--font-primary);
    font-size: var(--text-base);
    font-weight: 600;
    transition: all var(--d-normal) var(--ease);
    outline: none;
}
[dir="rtl"] .reset-input-group input { padding: .75rem 2.75rem .75rem 1rem; }
.reset-input-group input:focus {
    background: var(--bg-card) !important;
    border-color: var(--primary-400) !important;
    box-shadow: var(--shadow-focus);
}
.reset-input-group input:focus + .input-icon,
.reset-input-group input:focus ~ .input-icon { color: var(--primary-400); }
.btn-reset {
    width: 100%;
    padding: .85rem;
    background: linear-gradient(145deg, var(--primary-400) 0%, var(--primary-500) 100%);
    color: #fff;
    border: none;
    border-radius: var(--r-md);
    font-size: var(--text-base);
    font-weight: 800;
    font-family: var(--font-primary);
    cursor: pointer;
    box-shadow: var(--shadow-primary);
    transition: all var(--d-normal) var(--ease-spring);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 1.5rem;
}
.btn-reset:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(37, 99, 235, 0.45);
    filter: brightness(1.08);
}
.btn-back-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: var(--tx-3);
    font-size: var(--text-sm);
    font-weight: 700;
    text-decoration: none;
    margin-top: 1.25rem;
    transition: color var(--d-fast);
}
.btn-back-link:hover { color: var(--primary-400); }
.user-info-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: .75rem 1.25rem;
    background: var(--primary-glow);
    border: 1px solid var(--primary-border);
    border-radius: var(--r-lg);
    margin-bottom: 1.5rem;
}
.user-info-pill i { color: var(--primary-400); }
.user-info-pill span {
    font-size: var(--text-sm);
    font-weight: 700;
    color: var(--tx-1);
}
.error-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: .85rem 1.25rem;
    background: var(--danger-glow);
    border: 1px solid var(--danger-border);
    border-radius: var(--r-lg);
    margin-bottom: 1.5rem;
    color: var(--danger);
    font-size: var(--text-sm);
    font-weight: 700;
}
</style>

<div class="reset-page-wrap">
    <div class="reset-card">

        <!-- Logo -->
        <div class="reset-logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="SGFEP">
        </div>

        <h1 class="reset-title">إعادة تعيين كلمة المرور</h1>
        <p class="reset-subtitle">Réinitialisation du mot de passe sécurisé</p>

        <?php if (!empty($error)): ?>
            <div class="error-box">
                <i class="fa-solid fa-circle-exclamation fa-lg"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <a href="/login" class="btn-back-link">
                <i class="fa-solid fa-arrow-right-long"></i>
                العودة لتسجيل الدخول
            </a>
        <?php else: ?>

            <?php if (!empty($resetUser['nom_complet'] ?? $resetUser['username'] ?? '')): ?>
            <div class="user-info-pill">
                <i class="fa-solid fa-user-shield"></i>
                <span><?= htmlspecialchars($resetUser['nom_complet'] ?? $resetUser['username'] ?? '') ?></span>
            </div>
            <?php endif; ?>

            <form action="/reset-password" method="POST">
                @csrf
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div class="reset-input-group">
                    <label for="reset_password_new">كلمة المرور الجديدة</label>
                    <input type="password" id="reset_password_new" name="password"
                           placeholder="أدخل كلمة مرور قوية..."
                           required minlength="6">
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>

                <div class="reset-input-group">
                    <label for="reset_password_confirm">تأكيد كلمة المرور</label>
                    <input type="password" id="reset_password_confirm" name="password_confirm"
                           placeholder="أعد إدخال كلمة المرور..."
                           required minlength="6">
                    <i class="fa-solid fa-shield-halved input-icon"></i>
                </div>

                <button type="submit" class="btn-reset">
                    <i class="fa-solid fa-key"></i>
                    تحديث كلمة المرور
                </button>
            </form>

            <a href="/login" class="btn-back-link">
                <i class="fa-solid fa-arrow-right-long"></i>
                العودة لصفحة الدخول
            </a>
        <?php endif; ?>

    </div>
</div>

@endsection
