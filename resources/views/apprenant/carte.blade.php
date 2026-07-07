@extends('layouts.main')
@section('title', 'بطاقة المتكون الرقمية')

@section('styles')
<style>
    .carte-wrapper {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 2.5rem;
        padding: 2rem 1rem;
    }
    
    /* ── بطاقة التعريف الرقمية الموحدة ── */
    .digital-card {
        width: 480px !important;
        height: 290px !important;
        border-radius: 14px !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12) !important;
        transition: all 0.3s ease !important;
        font-family: 'Cairo', sans-serif !important;
        box-sizing: border-box !important;
        padding: 12px 18px !important;
        display: block !important;
        direction: rtl !important;
        text-align: right !important;
        background-color: #ffffff !important;
        border: 1px solid #cbd5e0 !important;
    }
    
    .diagonal-flag-ar-left {
        position: absolute;
        top: 0;
        left: 0;
        width: 120px;
        height: 12px;
        background: linear-gradient(90deg, #d62246 33%, #ffffff 33%, #ffffff 66%, #008751 66%);
        transform: rotate(-45deg) translate(-35px, -10px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        z-index: 10;
    }

    .employee-photo-frame {
        width: 96px !important;
        height: 120px !important;
        border-radius: 8px !important;
        border: 2px solid #ffffff !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
        object-fit: cover !important;
        overflow: hidden !important;
    }

    /* Front Card Layout: Trainee */
    .digital-card.card-layout-trainee {
        background-image: url('{{ asset("assets/images/card_trainee_bg.png") }}') !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        border: none !important;
    }

    .digital-card.card-layout-trainee #qrLayoutCol {
        top: 26px !important;
        right: 38px !important;
        width: 60px !important;
        height: 60px !important;
        z-index: 5 !important;
    }

    .digital-card.card-layout-trainee #photoLayoutCol {
        top: 100px !important;
        left: 20px !important;
        width: 96px !important;
        height: 120px !important;
        z-index: 5 !important;
        overflow: hidden !important;
    }

    .digital-card.card-layout-trainee #detailsLayoutCol {
        top: 105px !important;
        right: 15px !important;
        width: 340px !important;
        text-align: right !important;
        direction: rtl !important;
        z-index: 5 !important;
    }

    .digital-card.card-layout-trainee #barcodeLayoutCol {
        bottom: 8px !important;
        left: 20px !important;
        width: 140px !important;
        height: 45px !important;
        z-index: 5 !important;
    }

    /* Back Card Layout: Trainee Back */
    .digital-card.card-layout-trainee-back {
        background-color: #f6fff6 !important;
        border: 1.5px solid #c2ffd2 !important;
        background-image: linear-gradient(135deg, #f6fff6 0%, #e6ffe6 100%) !important;
    }

    @media print {
        .btn-print-bar, .sovereign-sidebar, .command-bar, .sb-overlay, .top-navigation-bar { display: none !important; }
        .workspace { padding: 0 !important; margin: 0 !important; }
        .carte-wrapper { padding: 0 !important; gap: 4rem !important; }
        .digital-card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- ── رأس الصفحة ── --}}
    <div class="bento-grid mb-4">
        <div class="bento-12 glass-panel p-4" style="border-right: 6px solid var(--electric) !important;">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 1.5rem; font-family: 'Cairo';">
                <i class="fa-solid fa-id-card text-success me-2"></i> بطاقة المتكون الرقمية
            </h3>
            <p class="text-muted small mb-0 fw-bold">وثيقة إلكترونية رسمية تُثبت الانتساب لقطاع التكوين والتعليم المهنيين</p>
        </div>
    </div>

    {{-- ── لوحة العرض والطباعة ── --}}
    <div class="glass-panel p-4">
        
        {{-- شريط التحكم ── --}}
        <div class="d-flex gap-3 mb-4 justify-content-start align-items-center btn-print-bar">
            <button class="btn btn-success rounded-3 px-4 fw-bold" onclick="window.print()">
                <i class="fa-solid fa-print me-1.5"></i> طباعة البطاقة
            </button>
            <a href="{{ route('apprenant.dashboard') }}" class="btn btn-outline-secondary rounded-3 px-4 fw-bold">
                <i class="fa-solid fa-arrow-right me-1.5"></i> رجوع للوحة التحكم
            </a>
        </div>

        <div class="carte-wrapper">
            {{-- ── الوجه الأمامي ── --}}
            <div class="text-center">
                <p class="text-muted small fw-bold mb-2">الوجه الأمامي (Recto)</p>
                <div id="digitalIDCardFront" class="digital-card card-layout-trainee p-3 mx-auto text-start">
                    {{-- Algerian Flag corner strip --}}
                    <div class="diagonal-flag-ar-left" id="cardFlagStrip"></div>
                    
                    {{-- Top Card Header --}}
                    <div class="card-header-overlay d-flex flex-column align-items-center text-center w-100" style="z-index: 2; position: absolute; top: 12px; right: 0; left: 0; padding: 0 18px; pointer-events: none;">
                        <div class="fw-bold text-dark" style="font-size:0.55rem; font-family:'Cairo'; line-height: 1.2; letter-spacing: 0.2px; opacity: 0.95;">الجمهورية الجزائرية الديمقراطية الشعبية</div>
                        <div class="fw-bold text-success" id="cardMinistryTitle" style="font-size:0.6rem; font-family:'Cairo'; line-height: 1.2; opacity: 0.95;">وزارة التكوين والتعليم المهنيين</div>
                    </div>

                    {{-- Photo Box --}}
                    <div id="photoLayoutCol" style="position: absolute; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 8px;">
                        @php
                            $avatarUrl = !empty($apprenant->photo) ? asset(ltrim($apprenant->photo, '/')) : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode(($apprenant->Nom ?? 'T') . ' ' . ($apprenant->Prenom ?? ''));
                        @endphp
                        <img id="cardPhoto" src="{{ $avatarUrl }}" alt="Photo" class="employee-photo-frame">
                    </div>
                    
                    {{-- Details --}}
                    <div id="detailsLayoutCol" style="position: absolute; font-family:'Cairo'; font-size:0.68rem; font-weight:bold; line-height: 1.4; color: #1c3d1e;">
                        <div style="display: flex; flex-direction: column; gap: 4px; color: #1c3d1e;">
                            <div>الرقم التعريفي : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.75rem;">{{ $apprenant->IDapprenant }}</span></div>
                            <div>اللقب : <span style="color: #000; font-weight: 700;">{{ $apprenant->Nom }}</span> الاسم : <span style="color: #000; font-weight: 700;">{{ $apprenant->Prenom }}</span> الجنس : <span style="color: #000; font-weight: 700;">{{ ($apprenant->Civ ?? 1) == 1 ? 'ذكر' : 'أنثى' }}</span></div>
                            <div>تاريخ الميلاد : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.72rem;">{{ $apprenant->DateNais }}</span> مكان الميلاد : <span style="color: #000; font-weight: 700;">{{ $apprenant->LieuNais }}</span></div>
                            <div>النمط : <span style="color: #000; font-weight: 700;">{{ $apprenant->mode_nom }}</span> المستوى : <span style="color: #000; font-weight: 700;">{{ $apprenant->niveau_nom }}</span></div>
                            @php
                                $yearD = !empty($apprenant->date_debut) ? date('Y', strtotime($apprenant->date_debut)) : '2023';
                                $yearF = !empty($apprenant->date_fin) ? date('Y', strtotime($apprenant->date_fin)) : '2026';
                            @endphp
                            <div>فترة التكوين : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.72rem;">{{ $yearD }} - {{ $yearF }}</span></div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 325px;">الاختصاص : <span style="color: #000; font-weight: 700; font-size: 0.62rem;">{{ $apprenant->specialite_nom }}</span></div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 325px;">المؤسسة : <span style="color: #000; font-weight: 700; font-size: 0.62rem;">{{ $apprenant->etab_nom }}</span></div>
                        </div>
                    </div>

                    {{-- QR Code Box --}}
                    <div id="qrLayoutCol" style="position: absolute; display: flex; align-items: center; justify-content: center;">
                        <div class="d-inline-block bg-white rounded-3" id="qrContainer" style="padding: 1px; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode('ID: ' . $apprenant->IDapprenant . ' | NIN: ' . ($apprenant->Nin ?? '')) }}" alt="QR Code" style="width: 58px; height: 58px; border-radius: 4px;">
                        </div>
                    </div>

                    {{-- Barcode Box --}}
                    <div id="barcodeLayoutCol" style="position: absolute; display: flex; align-items: center; justify-content: center;">
                        <svg id="cardBarcode" style="width: 120px; height: 32px; background: #fff; border-radius: 4px; padding: 2px;"></svg>
                    </div>
                </div>
            </div>

            {{-- ── الوجه الخلفي ── --}}
            <div class="text-center">
                <p class="text-muted small fw-bold mb-2">الوجه الخلفي (Verso)</p>
                <div id="digitalIDCardBack" class="digital-card card-layout-trainee-back p-3 mx-auto text-start" style="position: relative;">
                    {{-- Back Content Overlay --}}
                    <div class="d-flex flex-column justify-content-center align-items-center text-center h-100 px-3" style="z-index: 2; margin-top: 15px; direction: rtl; font-family:'Cairo'; font-size: 0.72rem; line-height: 1.65; color: #1a3a52;">
                        <div class="d-flex align-items-start gap-2 mb-3 text-right" style="text-align: right; width: 100%;">
                            <span class="bg-success rounded-circle mt-1.5" style="width: 8px; height: 8px; flex-shrink: 0; display: inline-block;"></span>
                            <p class="mb-0 fw-semibold" style="font-size: 0.7rem; font-weight: 700;">
                                بطاقة المتكون وثيقة إدارية رسمية وهي شخصية حصريا، تسلم للمتكون وتبقى ملكية المؤسسة أو الإدارة العمومية.
                            </p>
                        </div>
                        <div class="d-flex align-items-start gap-2 text-right" style="text-align: right; width: 100%;">
                            <span class="bg-success rounded-circle mt-1.5" style="width: 8px; height: 8px; flex-shrink: 0; display: inline-block;"></span>
                            <p class="mb-0 fw-semibold" style="font-size: 0.7rem; font-weight: 700;">
                                في حالة ضياع أو سرقة بطاقة المتكون يجب على صاحبها أن يقدم فورا تصريحا بالضياع أو السرقة لدى المؤسسة التكوينية.
                            </p>
                        </div>
                    </div>

                    {{-- Bottom Green Bar --}}
                    <div id="cardBackBottomBar" class="w-100 bg-success text-white py-1 px-2 rounded-2 text-center d-flex justify-content-center align-items-center gap-1" style="z-index: 2; font-family:'Cairo'; font-size: 0.55rem; font-weight: bold; position: absolute; bottom: 12px; left: 0; right: 0; width: calc(100% - 36px); margin: 0 auto;">
                        <span>للمزيد من المعلومات حول هذه البطاقة، قم بمسح رمز الاستجابة السريعة باستخدام هاتفك الذكي</span>
                        <span style="font-family:'Outfit';">(WWW.MFEP.GOV.DZ)</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        drawBarcode('cardBarcode', '{{ $apprenant->IDapprenant }}');
    });

    function drawBarcode(svgId, text) {
        const svg = document.getElementById(svgId);
        if (!svg) return;
        svg.innerHTML = '';
        
        const code = '*' + String(text).toUpperCase() + '*';
        const CODE39_MAP = {
            '0': '101001101101', '1': '110100101011', '2': '101100101011', '3': '110110010101',
            '4': '101001101011', '5': '110100110101', '6': '101100110101', '7': '101001011011',
            '8': '110100101101', '9': '101100101101', 'A': '110101001011', 'B': '101101001011',
            'C': '110110100101', 'D': '101011001011', 'E': '110101100101', 'F': '101101100101',
            'G': '101010011011', 'H': '110101001101', 'I': '101101001101', 'J': '101011001101',
            'K': '110101010011', 'L': '101101010011', 'M': '110110101001', 'N': '101011010011',
            'O': '110101101001', 'P': '101101101001', 'Q': '101010110011', 'R': '110101011001',
            'S': '101101011001', 'T': '101011011001', 'U': '110010101011', 'V': '100110101011',
            'W': '110011010101', 'X': '100101101011', 'Y': '110010110101', 'Z': '100111010101',
            '-': '100101011011', '.': '110010101101', ' ': '100110101101', '*': '100101101101'
        };

        let binString = '';
        for (let char of code) {
            const pattern = CODE39_MAP[char] || CODE39_MAP[' '];
            binString += pattern + '0';
        }

        const width = 120;
        const height = 32;
        const barWidth = width / binString.length;

        for (let i = 0; i < binString.length; i++) {
            if (binString[i] === '1') {
                const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                rect.setAttribute("x", i * barWidth);
                rect.setAttribute("y", 0);
                rect.setAttribute("width", barWidth);
                rect.setAttribute("height", height);
                rect.setAttribute("fill", "#000000");
                svg.appendChild(rect);
            }
        }
    }
</script>
@endsection
