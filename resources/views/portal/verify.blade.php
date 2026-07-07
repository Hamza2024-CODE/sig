@extends('layouts.public')

@section('title', 'بوابة التحقق الرقمي - منصة تسيير')

@section('styles')
<style>
    .verify-container {
        max-width: 850px;
        margin: 4rem auto;
        padding: 0 1.5rem;
        position: relative;
        z-index: 10;
        font-family: 'Cairo', sans-serif;
    }
    .verify-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        padding: 3rem;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .verify-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0f172a;
        text-align: center;
        margin-bottom: 0.5rem;
    }
    .verify-subtitle {
        font-size: 0.95rem;
        color: #64748b;
        text-align: center;
        margin-bottom: 2.5rem;
    }
    .verify-input-group {
        position: relative;
        background: #f8fafc;
        border: 2px solid rgba(15, 23, 42, 0.05);
        border-radius: 18px;
        padding: 6px;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .verify-input-group:focus-within {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1), inset 0 2px 4px rgba(0,0,0,0.01);
    }
    .verify-input {
        border: none;
        background: transparent;
        padding: 12px 20px;
        font-size: 1.2rem;
        font-weight: 700;
        color: #0f172a;
        width: 100%;
        font-family: 'Outfit', 'Cairo', sans-serif;
        text-align: center;
        letter-spacing: 1px;
    }
    .verify-input:focus {
        outline: none;
    }
    .verify-input::placeholder {
        color: #cbd5e1;
        font-weight: 500;
        letter-spacing: 0;
    }
    .verify-btn {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        border: none;
        color: white;
        font-weight: 800;
        font-size: 1.05rem;
        padding: 14px 32px;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
    }
    .verify-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
        filter: brightness(1.05);
    }
    
    /* Result Styling */
    .result-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.85rem;
        margin-bottom: 1.5rem;
    }
    .result-badge.success {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    .result-badge.danger {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    .result-card-success {
        border: 2px solid #10b981;
        background: rgba(16, 185, 129, 0.02);
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.05);
    }
    .result-card-danger {
        border: 2px solid #ef4444;
        background: rgba(239, 68, 68, 0.02);
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.05);
    }
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .info-table tr {
        border-bottom: 1px solid rgba(15, 23, 42, 0.05);
    }
    .info-table tr:last-child {
        border-bottom: none;
    }
    .info-table td {
        padding: 12px 8px;
        font-size: 0.95rem;
    }
    .info-table td.label {
        color: #64748b;
        font-weight: 700;
        width: 35%;
    }
    .info-table td.value {
        color: #0f172a;
        font-weight: 800;
    }
</style>
@endsection

@section('content')
<!-- Ambient Background Hero Elements to control header blur -->
<div class="hero-dcode d-none"></div>

<div class="verify-container animate__animated animate__fadeIn">
    <div class="verify-card">
        
        <!-- Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 70px; height: 70px;">
                <i class="fa-solid fa-shield-halved fs-2" style="color: #2563eb;"></i>
            </div>
            <h2 class="verify-title">بوابة التحقق الرقمي</h2>
            <p class="verify-subtitle">أدخل رمز التحقق المكتوب على المستند لمطابقة البيانات والتأكد من موثوقية الوثيقة الرسمية الصادرة</p>
        </div>

        <!-- Verification Form -->
        <form action="{{ url('verify') }}" method="GET" class="mb-5">
            <div class="verify-input-group">
                <input type="text" 
                       name="code" 
                       value="{{ $code ?? '' }}" 
                       class="verify-input" 
                       placeholder="مثال: BULL-2026-00001" 
                       required 
                       autocomplete="off" 
                       autofocus>
                <button type="submit" class="verify-btn">
                    <i class="fa-solid fa-circle-check me-1"></i> تحقق الآن
                </button>
            </div>
        </form>

        <!-- Verification Result -->
        @if (!empty($code) || !empty($signature))
            @if ($document)
                <!-- Success Result -->
                <div class="card border-0 rounded-4 p-4 result-card-success animate__animated animate__zoomIn">
                    <div class="text-center mb-3">
                        @if ($is_cryptographic)
                            <span class="result-badge success" style="background:#d1fae5; color:#065f46; border:1.5px solid #34d399; box-shadow: 0 0 15px rgba(52, 211, 153, 0.3);">
                                <i class="fa-solid fa-file-signature"></i> وثيقة موقعة رقمياً ومحمية بالكامل (Cryptographically Verified)
                            </span>
                        @else
                            <span class="result-badge success">
                                <i class="fa-solid fa-circle-check"></i> وثيقة رسمية معتمدة ومطابقة في الأرشيف
                            </span>
                        @endif
                        
                        <div class="d-flex align-items-center justify-content-center text-success gap-2 mb-2">
                            <i class="fa-solid fa-shield-halved fs-2 text-success"></i>
                            <h4 class="fw-bold mb-0">الحالة: سارية وموثوقة</h4>
                        </div>
                        <p class="text-muted small mb-0">تم إمضاء هذه الوثيقة وتوقيعها رقمياً وسجلها متوفر في الأرشيف الرقمي للمنصة.</p>
                    </div>

                    <table class="info-table">
                        <tr>
                            <td class="label">نوع المستند الصادر</td>
                            <td class="value text-primary">
                                @php
                                    switch($document['document_type']) {
                                        case 'certificat_scolaire': echo 'شهادة مدرسية (Certificat Scolaire)'; break;
                                        case 'attestation_inscription': echo 'شهادة تسجيل (Attestation Inscription)'; break;
                                        case 'attestation_travail': echo 'شهادة عمل (Attestation de Travail)'; break;
                                        case 'bulletin_notes': echo 'كشف النقاط السداسي (Bulletin de Notes)'; break;
                                        case 'decision_isqat': echo 'قرار إسقاط بيداغوجي (Décision d\'Exclusion)'; break;
                                        case 'basma_mouahada': echo 'بطاقة البصمة الرقمية الموحدة'; break;
                                        case 'fiche_paie': echo 'كشف الراتب الشهري (Bulletin de Paie)'; break;
                                        default: echo htmlspecialchars($document['document_type']);
                                    }
                                @endphp
                            </td>
                        </tr>
                        <tr>
                            <td class="label">اسم المستفيد</td>
                            <td class="value">{{ htmlspecialchars($document['demandeur_nom']) }}</td>
                        </tr>
                        <tr>
                            <td class="label">بيانات التعريف</td>
                            <td class="value" style="font-family: 'Outfit', sans-serif;">{{ htmlspecialchars($document['identifier']) }}</td>
                        </tr>
                        <tr>
                            <td class="label">تاريخ الإصدار والتوقيع</td>
                            <td class="value" style="font-family: 'Outfit', sans-serif;">{{ htmlspecialchars(substr($document['request_date'], 0, 10)) }}</td>
                        </tr>
                        <tr>
                            <td class="label">رمز التحقق الرقمي</td>
                            <td class="value"><span class="badge bg-light text-dark border border-dark rounded-pill px-3 py-1 fw-bold" style="font-family: 'Outfit', sans-serif;">{{ htmlspecialchars($document['code_verification']) }}</span></td>
                        </tr>
                    </table>

                    <div class="text-center mt-4">
                        <a href="{{ url('/verify/print/' . $document['id']) }}" target="_blank" class="btn btn-success rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                            <i class="fa-solid fa-file-pdf me-1"></i> عرض وتحميل النسخة الرسمية الأصلية (PDF)
                        </a>
                    </div>
                </div>
            @else
                <!-- Error Result -->
                <div class="card border-0 rounded-4 p-4 result-card-danger text-center animate__animated animate__shakeX">
                    <span class="result-badge danger d-inline-flex align-self-center">
                        <i class="fa-solid fa-triangle-exclamation"></i> فشل التحقق الرقمي
                    </span>
                    <h5 class="fw-bold text-danger mb-2">الرمز غير مطابق أو منتهي الصلاحية</h5>
                    <p class="text-muted small mb-0 px-md-5">
                        {{ $error ?? 'رمز التحقق المدخل غير صحيح أو لم يتم العثور على أي وثيقة مطابقة في الأرشيف الرقمي للمؤسسة التكوينية.' }}
                    </p>
                    <div class="alert alert-light border border-danger-subtle rounded-3 small text-start mt-3 mb-0" style="direction: rtl;">
                        <h6 class="fw-bold text-dark mb-1 small"><i class="fa-solid fa-info-circle text-danger me-1"></i>إرشادات هامة:</h6>
                        <ul class="mb-0 ps-3">
                            <li>يرجى التأكد من كتابة الكود بنفس التنسيق المطبوع على الوثيقة (مثال: <strong style="font-family:'Outfit';">BULL-2026-00010</strong>).</li>
                            <li>تأكد من عدم وجود مسافات زائدة قبل أو بعد الرمز.</li>
                            <li>إذا تكرر هذا الخطأ، قد تكون الوثيقة ملغاة أو لم تصدر رسمياً من نظام التسيير.</li>
                        </ul>
                    </div>
                </div>
            @endif
        @endif

    </div>
</div>
@endsection
