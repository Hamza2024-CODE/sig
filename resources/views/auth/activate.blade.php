@extends('layouts.public')
@section('title', $title ?? 'درع التفعيل — SGFEP')
@section('content')
<style>
    /* ============================================================
       SOVEREIGN ACTIVATION SHIELD - GLASSMORPHISM UI DESIGN
       ============================================================ */
    .activation-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 4rem 1.5rem;
        min-height: calc(100vh - 220px);
        position: relative;
        z-index: 10;
        background: radial-gradient(circle at center, rgba(15, 23, 42, 0.03) 0%, rgba(15, 23, 42, 0.08) 100%);
    }

    [data-theme="dark"] .activation-wrapper {
        background: radial-gradient(circle at center, rgba(10, 16, 28, 0.1) 0%, rgba(7, 10, 18, 0.4) 100%);
    }

    .glass-shield-card {
        width: 100%;
        max-width: 580px;
        background: rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.45);
        border-radius: 24px;
        padding: 3rem 2.5rem;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08), 
                    inset 0 1px 0 rgba(255, 255, 255, 0.5);
        position: relative;
        overflow: hidden;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    [data-theme="dark"] .glass-shield-card {
        background: rgba(15, 23, 42, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25), 
                    inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    /* Ambient Decorative Glows */
    .shield-glow-orb {
        position: absolute;
        width: 250px;
        height: 250px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
        top: -100px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1;
        pointer-events: none;
        animation: pulseGlow 4s infinite alternate;
    }

    [data-theme="dark"] .shield-glow-orb {
        background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
    }

    @keyframes pulseGlow {
        0% { transform: translateX(-50%) scale(1); opacity: 0.8; }
        100% { transform: translateX(-50%) scale(1.15); opacity: 1; }
    }

    .shield-icon-container {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(29, 78, 216, 0.2) 100%);
        border: 1px solid rgba(37, 99, 235, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        position: relative;
        z-index: 5;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
    }

    [data-theme="dark"] .shield-icon-container {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(5, 150, 105, 0.18) 100%);
        border-color: rgba(16, 185, 129, 0.25);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15);
    }

    .shield-icon-container i {
        font-size: 2.5rem;
        color: #1d4ed8;
        animation: iconPulse 2.5s infinite ease-in-out;
    }

    [data-theme="dark"] .shield-icon-container i {
        color: #10b981;
    }

    @keyframes iconPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.08); }
    }

    .shield-header {
        position: relative;
        z-index: 5;
        margin-bottom: 2rem;
    }

    .shield-title {
        font-family: 'Cairo', sans-serif;
        font-weight: 900;
        font-size: 1.6rem;
        color: var(--tx-1);
        margin-bottom: 0.5rem;
    }

    .shield-subtitle {
        font-family: 'Cairo', sans-serif;
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--tx-2);
        opacity: 0.8;
    }

    .user-info-badge {
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        padding: 1rem 1.25rem;
        margin-bottom: 2.2rem;
        text-align: right;
        position: relative;
        z-index: 5;
        font-family: 'Cairo', sans-serif;
    }

    [data-theme="dark"] .user-info-badge {
        background: rgba(10, 16, 28, 0.4);
        border-color: rgba(255, 255, 255, 0.05);
    }

    .user-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px dashed rgba(0, 0, 0, 0.06);
    }

    [data-theme="dark"] .user-info-row {
        border-bottom-color: rgba(255, 255, 255, 0.06);
    }

    .user-info-row:last-child {
        border-bottom: none;
    }

    .user-info-label {
        font-weight: 700;
        font-size: 0.82rem;
        color: var(--tx-2);
    }

    .user-info-value {
        font-weight: 800;
        font-size: 0.85rem;
        color: var(--tx-1);
    }

    /* Input Styling */
    .key-input-container {
        position: relative;
        z-index: 5;
        margin-bottom: 2rem;
    }

    .license-input {
        background: rgba(255, 255, 255, 0.7);
        border: 1.5px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        padding: 1.1rem;
        font-family: 'Outfit', monospace;
        font-size: 1.15rem;
        font-weight: 700;
        text-align: center;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--tx-1);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.25s ease;
    }

    [data-theme="dark"] .license-input {
        background: rgba(7, 10, 18, 0.6);
        border-color: rgba(255, 255, 255, 0.1);
    }

    .license-input:focus {
        background: #ffffff;
        border-color: #1d4ed8;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        outline: none;
    }

    [data-theme="dark"] .license-input:focus {
        background: rgba(7, 10, 18, 0.9);
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
    }

    .activate-btn {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        border: none;
        border-radius: 14px;
        padding: 1rem;
        width: 100%;
        color: #ffffff;
        font-family: 'Cairo', sans-serif;
        font-weight: 800;
        font-size: 1rem;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.2);
        transition: all 0.2s ease;
        position: relative;
        z-index: 5;
    }

    [data-theme="dark"] .activate-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
    }

    .activate-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(29, 78, 216, 0.3);
    }

    [data-theme="dark"] .activate-btn:hover {
        box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
    }

    .activate-btn:active {
        transform: translateY(0);
    }

    .developer-signature-footer {
        margin-top: 3rem;
        font-family: 'Cairo', sans-serif;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--tx-3);
        opacity: 0.8;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding-top: 1.25rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
    }

    [data-theme="dark"] .developer-signature-footer {
        border-top-color: rgba(255, 255, 255, 0.05);
    }

    .developer-signature-footer i {
        color: #dc3545;
    }

    /* Warning Toast style alert */
    .alert-glass {
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.2);
        color: #dc3545;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-family: 'Cairo', sans-serif;
        font-size: 0.83rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
</style>

<div class="activation-wrapper">
    <div class="glass-shield-card animate__animated animate__zoomIn">
        <div class="shield-glow-orb"></div>

        <!-- Shield Icon -->
        <div class="shield-icon-container">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <!-- Header -->
        <div class="shield-header">
            <h1 class="shield-title">تفعيل النظام</h1>
            <p class="shield-subtitle">Platform Activation Shield — SGFEP</p>
        </div>

        <!-- Error Flash Messages -->
        @if(session('error'))
            <div class="alert-glass">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- User Information -->
        <div class="user-info-badge">
            <div class="user-info-row">
                <span class="user-info-label">الحساب المستهدف</span>
                <span class="user-info-value">{{ $user['nom_complet'] }} ({{ $user['username'] }})</span>
            </div>
            <div class="user-info-row">
                <span class="user-info-label">رتبة الصلاحية</span>
                <span class="user-info-value">{{ $user['role_ar'] }}</span>
            </div>
            <div class="user-info-row">
                <span class="user-info-label">حالة الحماية</span>
                <span class="user-info-value text-success">
                    <i class="fa-solid fa-shield-heart me-1"></i> درع منع تسريب البيانات نشط
                </span>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="">
            @csrf
            <div class="key-input-container">
                <label class="form-label small fw-bold mb-2 text-muted" style="font-family: 'Cairo';">أدخل رمز التفعيل الخاص بمؤسستك</label>
                <input type="text" 
                       name="activation_key" 
                       id="activationKeyInput" 
                       class="form-control license-input" 
                       placeholder="GFEP-XXXX-YYYY-ZZZZ-AAAA-BBBB" 
                       maxlength="29" 
                       required 
                       autocomplete="off" 
                       autofocus>
            </div>

            <button type="submit" class="activate-btn">
                <i class="fa-solid fa-key me-1"></i> تأكيد وتفعيل الترخيص
            </button>
        </form>


    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('activationKeyInput');
        
        // Auto-formatting helper for License Key: GFEP-XXXX-YYYY-ZZZZ-AAAA-BBBB
        input.addEventListener('input', function(e) {
            let val = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
            
            // Remove all hyphens to re-apply them correctly
            let rawVal = val.replace(/-/g, '');
            
            // Format chunks of 4 characters, starting with GFEP (which is 4)
            let formatted = '';
            
            if (rawVal.length > 0) {
                // First block: GFEP (or whatever the user starts typing)
                formatted += rawVal.substring(0, 4);
                
                if (rawVal.length > 4) {
                    formatted += '-' + rawVal.substring(4, 8);
                }
                if (rawVal.length > 8) {
                    formatted += '-' + rawVal.substring(8, 12);
                }
                if (rawVal.length > 12) {
                    formatted += '-' + rawVal.substring(12, 16);
                }
                if (rawVal.length > 16) {
                    formatted += '-' + rawVal.substring(16, 20);
                }
                if (rawVal.length > 20) {
                    formatted += '-' + rawVal.substring(20, 24);
                }
            }
            
            e.target.value = formatted.substring(0, 29);
        });
    });
</script>
@endsection
