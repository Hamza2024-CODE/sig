@extends('layouts.main')
@section('title', 'مركز بطاقات التكوين المهني الرقمية')

@section('styles')
<style id="card-styles">
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
    
    body.hide-backgrounds .digital-card {
        background-image: none !important;
        background-color: transparent !important;
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
    }
    
    .diagonal-flag-ar {
        display: none !important;
        position: absolute;
        top: 0;
        right: 0;
        width: 120px;
        height: 12px;
        background: linear-gradient(90deg, #d62246 33%, #ffffff 33%, #ffffff 66%, #008751 66%);
        transform: rotate(45deg) translate(35px, -10px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        z-index: 10;
    }

    .diagonal-flag-ar-left {
        display: none !important;
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
        width: 100% !important;
        height: 100% !important;
        border-radius: 8px !important;
        border: 2px solid #ffffff !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
        object-fit: cover !important;
        overflow: hidden !important;
    }

    /* Front Card Layout: Employee */
    .digital-card.card-layout-employee {
        background-image: url('{{ asset("assets/images/card_employee_bg.png") }}') !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        border: none !important;
    }
    
    .digital-card.card-layout-employee #qrLayoutCol {
        top: 26px !important;
        left: 38px !important;
        width: 60px !important;
        height: 60px !important;
        z-index: 5 !important;
    }

    .digital-card.card-layout-employee #photoLayoutCol {
        bottom: 45px !important;
        left: 20px !important;
        width: 96px !important;
        height: 120px !important;
        z-index: 5 !important;
    }

    .digital-card.card-layout-employee #detailsLayoutCol {
        top: 80px !important;
        right: 20px !important;
        width: 320px !important;
        text-align: right !important;
        direction: rtl !important;
        z-index: 5 !important;
    }

    .digital-card.card-layout-employee #barcodeLayoutCol {
        bottom: 8px !important;
        left: 130px !important;
        width: 140px !important;
        height: 45px !important;
        z-index: 5 !important;
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
        top: 110px !important;
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

    /* Back Card Layout: Employee Back */
    .digital-card.card-layout-employee-back {
        background-color: #f0f7ff !important;
        border: 1.5px solid #bce0fd !important;
        background-image: linear-gradient(135deg, #f0f7ff 0%, #e1f0ff 100%) !important;
    }

    /* Back Card Layout: Trainee Back */
    .digital-card.card-layout-trainee-back {
        background-color: #f6fff6 !important;
        border: 1.5px solid #c2ffd2 !important;
        background-image: linear-gradient(135deg, #f6fff6 0%, #e6ffe6 100%) !important;
    }
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- Header Block --}}
    <div class="glass-panel p-4 mb-4" style="border-right: 6px solid var(--electric) !important;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">
                    <i class="fa-solid fa-id-card-clip text-primary me-2"></i>
                    مركز بطاقات التكوين المهني الرقمية
                </h4>
                <p class="text-muted mb-0 small fw-bold">
                    البوابة لإصدار وإدارة وتخصيص البطاقات الرقمية المهنية والطلابية.
                </p>
            </div>
            
            {{-- Tab Navigation (Type Toggler) --}}
            <div class="btn-group rounded-pill p-1 bg-light border">
                <a href="?type=employee" class="btn btn-sm rounded-pill px-4 fw-bold {{ $type === 'employee' ? 'btn-primary' : 'btn-light text-dark' }}">
                    <i class="fa-solid fa-briefcase me-1"></i> بطاقات الموظفين
                </a>
                <a href="?type=trainee" class="btn btn-sm rounded-pill px-4 fw-bold {{ $type === 'trainee' ? 'btn-primary' : 'btn-light text-dark' }}">
                    <i class="fa-solid fa-graduation-cap me-1"></i> بطاقات المتربصين
                </a>
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="row g-4">
        {{-- Right Column: Filter + List --}}
        <div class="col-lg-7">
            <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';">
                        <i class="fa-solid fa-magnifying-glass me-2 text-secondary"></i>
                        البحث والتصفية
                    </h5>
                    
                    {{-- Search Form --}}
                    @if($type === 'employee')
                    <form method="GET" action="" class="row g-2 mb-4">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div class="col-md-4">
                            <input type="text" name="filter_search" class="form-control rounded-3" placeholder="ابحث بالاسم، اللقب، NIN، التخصص..." value="{{ $selected_filters['search'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <select name="filter_wilaya" id="filter-wilaya" onchange="onWilayaChange()" class="form-select rounded-3">
                                <option value="">كل الولايات</option>
                                @foreach($filter_wilayas as $w)
                                    <option value="{{ $w['id'] }}" {{ ($selected_filters['wilaya'] ?? '') == $w['id'] ? 'selected' : '' }}>
                                        {{ $w['nom_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="filter_etab" id="filter-etablissement" class="form-select rounded-3" data-init="true">
                                <option value="">كل المؤسسات</option>
                                @foreach($filter_etablissements as $e)
                                    <option value="{{ $e['id'] }}" data-wilaya="{{ $e['wilaya_id'] }}" {{ ($selected_filters['etab'] ?? '') == $e['id'] ? 'selected' : '' }}>
                                        {{ $e['nom_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="filter_grade" class="form-select rounded-3">
                                <option value="">كل الرتب</option>
                                @foreach($filter_grades as $g)
                                    <option value="{{ $g['id'] }}" {{ ($selected_filters['grade'] ?? '') == $g['id'] ? 'selected' : '' }}>
                                        {{ $g['nom_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="filter_fonction" class="form-select rounded-3">
                                <option value="">كل الوظائف</option>
                                @foreach($filter_fonctions as $f)
                                    <option value="{{ $f['id'] }}" {{ ($selected_filters['fonction'] ?? '') == $f['id'] ? 'selected' : '' }}>
                                        {{ $f['nom_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 text-end mt-2">
                            <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4">
                                <i class="fa-solid fa-filter me-1"></i> تصفية النتائج
                            </button>
                        </div>
                    </form>
                    @else
                    <form method="GET" action="" class="row g-2 mb-4">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div class="col-md-4">
                            <input type="text" name="filter_search" class="form-control rounded-3" placeholder="ابحث بالاسم، اللقب، NIN..." value="{{ $selected_filters['search'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <select name="filter_wilaya" id="filter-wilaya" onchange="onWilayaChange()" class="form-select rounded-3">
                                <option value="">كل الولايات</option>
                                @foreach($filter_wilayas as $w)
                                    <option value="{{ $w['id'] }}" {{ ($selected_filters['wilaya'] ?? '') == $w['id'] ? 'selected' : '' }}>
                                        {{ $w['nom_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="filter_etab" id="filter-etablissement" class="form-select rounded-3" data-init="true">
                                <option value="">كل المؤسسات</option>
                                @foreach($filter_etablissements as $e)
                                    <option value="{{ $e['id'] }}" data-wilaya="{{ $e['wilaya_id'] }}" {{ ($selected_filters['etab'] ?? '') == $e['id'] ? 'selected' : '' }}>
                                        {{ $e['nom_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="filter_branche" class="form-select rounded-3">
                                <option value="">كل الفروع</option>
                                @foreach($filter_branches as $b)
                                    <option value="{{ $b['id'] }}" {{ ($selected_filters['branche'] ?? '') == $b['id'] ? 'selected' : '' }}>
                                        {{ $b['libelle_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="filter_mode" class="form-select rounded-3">
                                <option value="">كل الأنماط</option>
                                @foreach($filter_modes as $m)
                                    <option value="{{ $m['code'] }}" {{ ($selected_filters['mode'] ?? '') == $m['code'] ? 'selected' : '' }}>
                                        {{ $m['libelle_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 text-end mt-2">
                            <button type="submit" class="btn btn-primary rounded-3 fw-bold px-4">
                                <i class="fa-solid fa-filter me-1"></i> تصفية النتائج
                            </button>
                        </div>
                    </form>
                    @endif

                    {{-- Table List --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="table-layout: fixed; width: 100%;">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th style="width: 32%;">الاسم واللقب</th>
                                    <th style="width: 30%;">{{ $type === 'employee' ? 'المادة/الوظيفة' : 'التخصص التكويني' }}</th>
                                    <th style="width: 23%;">المؤسسة</th>
                                    <th style="width: 15%;" class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(empty($records))
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">
                                            لا توجد بيانات مطابقة لخيارات التصفية.
                                        </td>
                                    </tr>
                                @else
                                    @foreach($records as $r)
                                        <tr style="cursor: pointer;" onclick="loadIDCard({{ $r['id'] }}, '{{ $type }}')">
                                            <td>
                                                <div style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" title="{{ $r['nom'] }} {{ $r['prenom'] }}">
                                                    <strong class="text-dark">{{ $r['nom'] }} {{ $r['prenom'] }}</strong>
                                                    <div class="text-muted" style="font-size: 0.68rem; font-family: 'Outfit';">NIN: {{ $r['nin'] ?? 'N/A' }}</div>
                                                </div>
                                            </td>
                                            <td class="small fw-semibold text-primary">
                                                <div style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" title="{{ $r['spec_ar'] ?? 'غير محدد' }}">
                                                    {{ $r['spec_ar'] ?? 'غير محدد' }}
                                                </div>
                                            </td>
                                            <td class="small text-muted">
                                                <div style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" title="{{ $r['etab_nom'] ?? 'المديرية الولائية' }}">
                                                    {{ $r['etab_nom'] ?? 'المديرية الولائية' }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-xs btn-outline-primary rounded-pill px-2.5 fw-bold" onclick="if(typeof event !== 'undefined') event.stopPropagation(); loadIDCard({{ $r['id'] }}, '{{ $type }}')">
                                                    <i class="fa-solid fa-eye me-1"></i> عرض البطاقة
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination --}}
                @if($totalPages > 1)
                    <div class="d-flex justify-content-center mt-4">
                        <nav>
                            <ul class="pagination pagination-sm gap-1">
                                <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                                    <a class="page-link rounded-3" href="?type={{ $type }}&page={{ $page - 1 }}&filter_search={{ $selected_filters['search'] }}&filter_wilaya={{ $selected_filters['wilaya'] }}&filter_etab={{ $selected_filters['etab'] }}&filter_mode={{ $selected_filters['mode'] ?? '' }}&filter_branche={{ $selected_filters['branche'] ?? '' }}&filter_grade={{ $selected_filters['grade'] ?? '' }}&filter_fonction={{ $selected_filters['fonction'] ?? '' }}">السابق</a>
                                </li>
                                @for($i = 1; $i <= $totalPages; $i++)
                                    <li class="page-item {{ $page == $i ? 'active' : '' }}">
                                        <a class="page-link rounded-3" href="?type={{ $type }}&page={{ $i }}&filter_search={{ $selected_filters['search'] }}&filter_wilaya={{ $selected_filters['wilaya'] }}&filter_etab={{ $selected_filters['etab'] }}&filter_mode={{ $selected_filters['mode'] ?? '' }}&filter_branche={{ $selected_filters['branche'] ?? '' }}&filter_grade={{ $selected_filters['grade'] ?? '' }}&filter_fonction={{ $selected_filters['fonction'] ?? '' }}">{{ $i }}</a>
                                    </li>
                                @endfor
                                <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                                    <a class="page-link rounded-3" href="?type={{ $type }}&page={{ $page + 1 }}&filter_search={{ $selected_filters['search'] }}&filter_wilaya={{ $selected_filters['wilaya'] }}&filter_etab={{ $selected_filters['etab'] }}&filter_mode={{ $selected_filters['mode'] ?? '' }}&filter_branche={{ $selected_filters['branche'] ?? '' }}&filter_grade={{ $selected_filters['grade'] ?? '' }}&filter_fonction={{ $selected_filters['fonction'] ?? '' }}">التالي</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif
            </div>
        </div>

        {{-- Left Column: Card Preview --}}
        <div class="col-lg-5">
            <div class="glass-panel p-4 h-100 d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 780px;">
                <div id="noCardSelectedBlock" class="py-5 text-muted">
                    <i class="fa-solid fa-address-card fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                    <h6 class="fw-bold mb-1">لم يتم اختيار أي مستخدم</h6>
                    <p class="small mb-0">انقر على زر "عرض البطاقة" في الجدول الجانبي لعرض وتخصيص بطاقة التعريف الرقمية.</p>
                </div>

                <div id="cardPreviewContainer" style="display: none; width: 100%;">
                    <div class="d-flex flex-column align-items-center gap-4">
                        
                        {{-- CARD FRONT --}}
                        <div class="d-flex flex-column align-items-center w-100">
                            <span class="badge bg-primary mb-2 px-3 py-1 rounded-pill fw-bold" style="font-family:'Cairo'; font-size: 0.72rem;">واجهة البطاقة (Front)</span>
                            <div id="digitalIDCardFront" class="digital-card card-layout-employee p-3 mx-auto text-start">
                                {{-- Algerian Flag corner strip --}}
                                <div class="diagonal-flag-ar" id="cardFlagStrip"></div>
                                
                                {{-- Top Card Header --}}
                                <div class="card-header-overlay d-flex flex-column align-items-center text-center w-100" style="z-index: 2; position: absolute; top: 12px; right: 0; left: 0; padding: 0 18px; pointer-events: none;">
                                    <div class="fw-bold text-dark" style="font-size:0.55rem; font-family:'Cairo'; line-height: 1.2; letter-spacing: 0.2px; opacity: 0.95;">الجمهورية الجزائرية الديمقراطية الشعبية</div>
                                    <div class="fw-bold text-success" id="cardMinistryTitle" style="font-size:0.6rem; font-family:'Cairo'; line-height: 1.2; opacity: 0.95;">وزارة التكوين والتعليم المهنيين</div>
                                </div>

                                {{-- Photo Box --}}
                                <div id="photoLayoutCol" style="position: absolute; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: 8px;">
                                     <img id="cardPhoto" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm0 14c-2.03 0-4.43-.82-6.14-2.88C7.55 15.8 9.68 15 12 15s4.45.8 6.14 2.12C16.43 19.18 14.03 20 12 20z'/></svg>" alt="Photo" class="employee-photo-frame" onerror="if(this.src && !this.src.includes('api.dicebear.com') && !this.src.includes('/public/uploads/')){ this.src = this.src.replace('/uploads/', '/public/uploads/'); }">
                                </div>
                                
                                {{-- Details --}}
                                <div id="detailsLayoutCol" style="position: absolute; font-family:'Cairo'; font-size:0.68rem; font-weight:bold; line-height: 1.4; color: #1a3d54;">
                                    {{-- Populated dynamically via JS --}}
                                </div>

                                {{-- QR Code Box --}}
                                <div id="qrLayoutCol" style="position: absolute; display: flex; align-items: center; justify-content: center;">
                                    <div class="d-inline-block bg-white rounded-3" id="qrContainer" style="padding: 1px; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                        {{-- QR Code SVG --}}
                                    </div>
                                </div>

                                {{-- Barcode Box --}}
                                <div id="barcodeLayoutCol" style="position: absolute; display: flex; align-items: center; justify-content: center;">
                                    <svg id="cardBarcode" style="width: 120px; height: 32px; background: #fff; border-radius: 4px; padding: 2px;"></svg>
                                </div>
                            </div>
                        </div>

                        {{-- CARD BACK --}}
                        <div class="d-flex flex-column align-items-center w-100">
                            <span class="badge bg-success mb-2 px-3 py-1 rounded-pill fw-bold" style="font-family:'Cairo'; font-size: 0.72rem;">ظهر البطاقة (Back)</span>
                            <div id="digitalIDCardBack" class="digital-card card-layout-employee-back p-3 mx-auto text-start">
                                {{-- Back Content Overlay --}}
                                <div class="d-flex flex-column justify-content-center align-items-center text-center h-100 px-3" style="z-index: 2; margin-top: 15px; direction: rtl; font-family:'Cairo'; font-size: 0.72rem; line-height: 1.65; color: #1a3a52;">
                                    <div class="d-flex align-items-start gap-2 mb-3 text-right" style="text-align: right; width: 100%;">
                                        <span id="cardBackBullet1" class="bg-success rounded-circle mt-1.5" style="width: 8px; height: 8px; flex-shrink: 0; display: inline-block;"></span>
                                        <p class="mb-0 fw-semibold" id="backTextLine1" style="font-size: 0.7rem; font-weight: 700;">
                                            البطاقة المهنية وثيقة إدارية رسمية وهي شخصية حصريا، تسلم للموظف وتبقى ملكية المؤسسة أو الإدارة العمومية.
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-start gap-2 text-right" style="text-align: right; width: 100%;">
                                        <span id="cardBackBullet2" class="bg-success rounded-circle mt-1.5" style="width: 8px; height: 8px; flex-shrink: 0; display: inline-block;"></span>
                                        <p class="mb-0 fw-semibold" id="backTextLine2" style="font-size: 0.7rem; font-weight: 700;">
                                            في حالة ضياع أو سرقة البطاقة المهنية يجب على صاحبها أن يقدم فورا تصريحا بالضياع أو السرقة لدى مصالح الأمن المختصة.
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

                    {{-- Action Controls --}}
                    @if(\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') === '1')
                    <div class="d-flex flex-column gap-3 w-100 mt-4 text-right" style="direction: rtl;">
                        <h6 class="fw-bold mb-1 text-secondary" style="font-size:0.8rem; font-family:'Cairo';"><i class="fa-solid fa-print me-1"></i> خيارات الطباعة المباشرة</h6>
                        <div class="d-flex gap-2">
                            <button onclick="printCard(true)" class="btn btn-outline-primary rounded-pill flex-grow-1 fw-bold" style="font-size:0.75rem; font-family:'Cairo';">
                                <i class="fa-solid fa-image me-1"></i> طباعة بالخلفية
                            </button>
                            <button onclick="printCard(false)" class="btn btn-outline-secondary rounded-pill flex-grow-1 fw-bold" style="font-size:0.75rem; font-family:'Cairo';">
                                <i class="fa-solid fa-eye-slash me-1"></i> بدون خلفية (للطباعة على بطاقات PVC جاهزة)
                            </button>
                        </div>

                        <h6 class="fw-bold mb-1 mt-2 text-secondary" style="font-size:0.8rem; font-family:'Cairo';"><i class="fa-solid fa-file-pdf me-1"></i> تحميل ملف PDF</h6>
                        <div class="d-flex gap-2">
                            <button onclick="downloadPDF(true)" class="btn btn-primary rounded-pill flex-grow-1 fw-bold" style="font-size:0.75rem; font-family:'Cairo';">
                                <i class="fa-solid fa-file-image me-1"></i> بالخلفية (PDF)
                            </button>
                            <button onclick="downloadPDF(false)" class="btn btn-secondary rounded-pill flex-grow-1 fw-bold" style="font-size:0.75rem; font-family:'Cairo';">
                                <i class="fa-solid fa-circle-minus me-1"></i> بدون خلفية (PDF)
                            </button>
                        </div>

                        <h6 class="fw-bold mb-1 mt-2 text-secondary" style="font-size:0.8rem; font-family:'Cairo';"><i class="fa-solid fa-images me-1"></i> تحميل كصور PNG</h6>
                        <div class="d-flex gap-2 mb-1">
                            <button onclick="downloadCard('front', true)" class="btn btn-outline-dark rounded-pill flex-grow-1 fw-bold" style="font-size:0.75rem; font-family:'Cairo'; padding: 6px 8px;">
                                الوجه بالخلفية
                            </button>
                            <button onclick="downloadCard('front', false)" class="btn btn-light rounded-pill flex-grow-1 fw-bold border" style="font-size:0.75rem; font-family:'Cairo'; padding: 6px 8px;">
                                الوجه بدون خلفية
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <button onclick="downloadCard('back', true)" class="btn btn-outline-dark rounded-pill flex-grow-1 fw-bold" style="font-size:0.75rem; font-family:'Cairo'; padding: 6px 8px;">
                                الظهر بالخلفية
                            </button>
                            <button onclick="downloadCard('back', false)" class="btn btn-light rounded-pill flex-grow-1 fw-bold border" style="font-size:0.75rem; font-family:'Cairo'; padding: 6px 8px;">
                                الظهر بدون خلفية
                            </button>
                        </div>
                    </div>
                    @else
                    {{-- Print actions disabled by admin feature flag --}}
                    <div class="w-100 mt-4 p-3 rounded-3 text-center" style="background: rgba(220,53,69,.06); border: 1.5px dashed rgba(220,53,69,.3);">
                        <i class="fa-solid fa-ban text-danger mb-2 d-block fs-4"></i>
                        <p class="text-danger fw-bold small mb-1" style="font-family:'Cairo';">خيارات الطباعة والتحميل معطّلة من طرف المسؤول</p>
                        <p class="text-muted" style="font-size:0.72rem; font-family:'Cairo';">يمكن إعادة تفعيلها من: الإعدادات ← إدارة الميزات والاستعلامات</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    function onWilayaChange() {
        const wilayaSelect = document.getElementById('filter-wilaya');
        if (!wilayaSelect) return;
        const wilayaVal = wilayaSelect.value;
        const etabSelect = document.getElementById('filter-etablissement');
        if (!etabSelect) return;
        
        if (etabSelect.getAttribute('data-init') !== 'true') {
            etabSelect.value = "";
        } else {
            etabSelect.removeAttribute('data-init');
        }
        
        Array.from(etabSelect.options).forEach(opt => {
            if (opt.value === "") {
                opt.style.display = "";
                return;
            }
            const optWilaya = opt.getAttribute('data-wilaya');
            if (!wilayaVal || optWilaya === wilayaVal) {
                opt.style.display = "";
            } else {
                opt.style.display = "none";
            }
        });
    }

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

        const svgWidth = 120;
        const svgHeight = 28;
        const barCount = binString.length;
        const barWidth = svgWidth / barCount;

        svg.setAttribute('viewBox', `0 0 ${svgWidth} ${svgHeight}`);
        
        const bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bg.setAttribute('width', svgWidth);
        bg.setAttribute('height', svgHeight);
        bg.setAttribute('fill', '#ffffff');
        svg.appendChild(bg);

        for (let i = 0; i < barCount; i++) {
            if (binString[i] === '1') {
                const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                rect.setAttribute('x', i * barWidth);
                rect.setAttribute('y', 0);
                rect.setAttribute('width', barWidth);
                rect.setAttribute('height', svgHeight - 6);
                rect.setAttribute('fill', '#000000');
                svg.appendChild(rect);
            }
        }

        const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        label.setAttribute('x', svgWidth / 2);
        label.setAttribute('y', svgHeight - 1);
        label.setAttribute('text-anchor', 'middle');
        label.setAttribute('font-family', 'Outfit, sans-serif');
        label.setAttribute('font-size', '6px');
        label.setAttribute('font-weight', 'bold');
        label.setAttribute('fill', '#333333');
        label.textContent = text;
        svg.appendChild(label);
    }

    function loadIDCard(id, type) {
        document.getElementById('noCardSelectedBlock').style.display = 'none';
        document.getElementById('cardPreviewContainer').style.display = 'block';

        const endpoint = type === 'employee' 
            ? `{{ url('dashboard/espace-employe/get') }}/${id}` 
            : `{{ url('dashboard/digital-cards/trainee') }}/${id}`;

        fetch(endpoint)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'خطأ في جلب البيانات');
                    return;
                }

                const cardFront = document.getElementById('digitalIDCardFront');
                const cardBack = document.getElementById('digitalIDCardBack');

                if (type === 'employee') {
                    cardFront.className = 'digital-card card-layout-employee';
                    cardBack.className = 'digital-card card-layout-employee-back';

                    // Update back theme colors to blue
                    const b1 = document.getElementById('cardBackBullet1');
                    const b2 = document.getElementById('cardBackBullet2');
                    const bar = document.getElementById('cardBackBottomBar');
                    if (b1) { b1.className = 'bg-primary rounded-circle mt-1.5'; }
                    if (b2) { b2.className = 'bg-primary rounded-circle mt-1.5'; }
                    if (bar) { bar.className = 'w-100 bg-primary text-white py-1 px-2 rounded-2 text-center d-flex justify-content-center align-items-center gap-1'; }

                    const emp = data.employee;
                    document.getElementById('cardFlagStrip').className = 'diagonal-flag-ar';
                    document.getElementById('cardMinistryTitle').innerText = 'وزارة التكوين والتعليم المهنيين';
                    
                    let birthDate = emp.DateNais ? emp.DateNais.replace(/-/g, '/') : '';
                    let deliveryDate = emp.Daterecr ? emp.Daterecr.replace(/-/g, '/') : '';
                    let lieuNais = emp.LieuNais || '';
                    let grade = emp.grade_nom || emp.Specialite || '';
                    let fonction = emp.fonction_nom || emp.TachesPrincipale || '';
                    let etab = emp.etab_nom || '';
                    
                    document.getElementById('detailsLayoutCol').innerHTML = `
                        <div style="display: flex; flex-direction: column; gap: 4px; color: #1a3d54;">
                            <div>الرقم التعريفي : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.75rem;">${emp.IDEncadrement}</span></div>
                            <div>اللقب : <span style="color: #000; font-weight: 700;">${emp.Nom}</span> الاسم : <span style="color: #000; font-weight: 700;">${emp.Prenom}</span></div>
                            <div>تاريخ الميلاد : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.72rem;">${birthDate}</span> مكان الميلاد : <span style="color: #000; font-weight: 700;">${lieuNais}</span></div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">الرتبة : <span style="color: #000; font-weight: 700; font-size: 0.62rem;">${grade}</span></div>
                            <div>الوظيفة : <span style="color: #000; font-weight: 700;">${fonction}</span></div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">المؤسسة : <span style="color: #000; font-weight: 700; font-size: 0.62rem;">${etab}</span></div>
                            <div class="d-flex justify-content-between align-items-center" style="width: 100%; margin-top: 2px;">
                                <div>تاريخ التسليم : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.72rem;">${deliveryDate}</span></div>
                                <div style="color: #d32f2f; font-weight: 700; font-size: 0.62rem; margin-left: 10px;">صالحة عشر (10) سنوات</div>
                            </div>
                        </div>
                    `;

                    document.getElementById('backTextLine1').innerText = "البطاقة المهنية وثيقة إدارية رسمية وهي شخصية حصريا، تسلم للموظف وتبقى ملكية المؤسسة أو الإدارة العمومية.";
                    document.getElementById('backTextLine2').innerText = "في حالة ضياع أو سرقة البطاقة المهنية يجب على صاحبها أن يقدم فورا تصريحا بالضياع أو السرقة لدى مصالح الأمن المختصة.";
                    
                    let avatar = emp.photo;
                    const baseUrl = "{{ url('/') }}";
                    if (avatar && avatar.trim() !== '' && avatar.toLowerCase() !== 'empty' && avatar.toLowerCase() !== 'default') {
                        avatar = avatar.trim();
                        if (!avatar.startsWith('http') && !avatar.startsWith('data:')) {
                            let cleaned = avatar.startsWith('/') ? avatar : '/' + avatar;
                            avatar = baseUrl + cleaned;
                        }
                    } else {
                        avatar = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm0 14c-2.03 0-4.43-.82-6.14-2.88C7.55 15.8 9.68 15 12 15s4.45.8 6.14 2.12C16.43 19.18 14.03 20 12 20z'/></svg>";
                    }
                    document.getElementById('cardPhoto').src = avatar;

                    // Generate dynamic QR code image inside qrContainer
                    let verifyUrl = "{{ url('verify/card/employee') }}/" + (emp.secure_id || '');
                    document.getElementById('qrContainer').innerHTML = `<img src="/api/qrcode?data=${encodeURIComponent(verifyUrl)}" alt="QR Code" style="width: 58px; height: 58px; border-radius: 4px;">`;
                    
                    // Draw dynamic barcode
                    drawBarcode('cardBarcode', emp.IDEncadrement);

                } else {
                    cardFront.className = 'digital-card card-layout-trainee';
                    cardBack.className = 'digital-card card-layout-trainee-back';

                    // Update back theme colors to green
                    const b1 = document.getElementById('cardBackBullet1');
                    const b2 = document.getElementById('cardBackBullet2');
                    const bar = document.getElementById('cardBackBottomBar');
                    if (b1) { b1.className = 'bg-success rounded-circle mt-1.5'; }
                    if (b2) { b2.className = 'bg-success rounded-circle mt-1.5'; }
                    if (bar) { bar.className = 'w-100 bg-success text-white py-1 px-2 rounded-2 text-center d-flex justify-content-center align-items-center gap-1'; }

                    const trainee = data.trainee;
                    document.getElementById('cardFlagStrip').className = 'diagonal-flag-ar-left';
                    document.getElementById('cardMinistryTitle').innerText = 'وزارة التكوين والتعليم المهنيين';
                    
                    let birthDate = trainee.DateNais ? trainee.DateNais.replace(/-/g, '/') : '';
                    let dateDeb = trainee.date_deb ? trainee.date_deb.replace(/-/g, '/') : '';
                    let dateFin = trainee.date_fin ? trainee.date_fin.replace(/-/g, '/') : '';
                    let genderText = (trainee.Civ == 1 || trainee.sexe == 1) ? 'ذكر' : 'أنثى';
                    let lieuNais = trainee.LieuNais || '';
                    let mode = trainee.mode_nom || '';
                    let regime = trainee.regime_nom || '';
                    let spec = trainee.spec_ar || '';
                    let etab = trainee.etab_nom || '';

                    document.getElementById('detailsLayoutCol').innerHTML = `
                        <div style="display: flex; flex-direction: column; gap: 4px; color: #1c3d1e;">
                            <div>الرقم التعريفي : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.75rem;">${trainee.id}</span></div>
                            <div>اللقب : <span style="color: #000; font-weight: 700;">${trainee.nom_ar || ''}</span> الاسم : <span style="color: #000; font-weight: 700;">${trainee.prenom_ar || ''}</span> الجنس : <span style="color: #000; font-weight: 700;">${genderText}</span></div>
                            <div>تاريخ الميلاد : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.72rem;">${birthDate}</span> مكان الميلاد : <span style="color: #000; font-weight: 700;">${lieuNais}</span></div>
                            <div>النمط : <span style="color: #000; font-weight: 700;">${mode}</span> النظام : <span style="color: #000; font-weight: 700;">${regime}</span></div>
                            <div>فترة التكوين من : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.72rem;">${dateDeb || 'غير محدد'}</span> إلى : <span style="color: #000; font-weight: 700; font-family:'Outfit'; font-size:0.72rem;">${dateFin || 'غير محدد'}</span></div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">الاختصاص : <span style="color: #000; font-weight: 700; font-size: 0.62rem;">${spec}</span></div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">المؤسسة : <span style="color: #000; font-weight: 700; font-size: 0.62rem;">${etab}</span></div>
                        </div>
                    `;

                    document.getElementById('backTextLine1').innerText = "بطاقة المتكون وثيقة إدارية رسمية وهي شخصية حصريا، تسلم للمتكون وتبقى ملكية المؤسسة أو الإدارة العمومية.";
                    document.getElementById('backTextLine2').innerText = "في حالة ضياع أو سرقة بطاقة المتكون يجب على صاحبها أن يقدم فورا تصريحا بالضياع أو السرقة لدى المؤسسة التكوينية.";
                    
                    let avatar = trainee.photo;
                    const baseUrl = "{{ url('/') }}";
                    if (avatar && avatar.trim() !== '' && avatar.toLowerCase() !== 'empty' && avatar.toLowerCase() !== 'default') {
                        avatar = avatar.trim();
                        if (!avatar.startsWith('http') && !avatar.startsWith('data:')) {
                            let cleaned = avatar.startsWith('/') ? avatar : '/' + avatar;
                            avatar = baseUrl + cleaned;
                        }
                    } else {
                        avatar = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm0 14c-2.03 0-4.43-.82-6.14-2.88C7.55 15.8 9.68 15 12 15s4.45.8 6.14 2.12C16.43 19.18 14.03 20 12 20z'/></svg>";
                    }
                    document.getElementById('cardPhoto').src = avatar;

                    // Generate dynamic QR code image inside qrContainer
                    let verifyUrl = "{{ url('verify/card/trainee') }}/" + (trainee.secure_id || '');
                    document.getElementById('qrContainer').innerHTML = `<img src="/api/qrcode?data=${encodeURIComponent(verifyUrl)}" alt="QR Code" style="width: 58px; height: 58px; border-radius: 4px;">`;

                    // Draw dynamic barcode
                    drawBarcode('cardBarcode', trainee.id);
                }
            })
            .catch(err => {
                console.error('Fetch Error:', err);
                alert('حدث خطأ أثناء جلب البيانات: ' + err.message);
            });
    }

    function setBackgroundState(withBg) {
        if (!withBg) {
            document.body.classList.add('hide-backgrounds');
        } else {
            document.body.classList.remove('hide-backgrounds');
        }
    }

    function printCard(withBg = true) {
        setBackgroundState(withBg);

        setTimeout(() => {
            const front = document.getElementById('digitalIDCardFront');
            const back = document.getElementById('digitalIDCardBack');
            if (!front || !back) {
                setBackgroundState(true);
                return;
            }

            const frontHtml = front.outerHTML;
            const backHtml = back.outerHTML;

            const printWindow = window.open('', '_blank', 'width=850,height=700');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html dir="rtl" lang="ar">
                <head>
                    <meta charset="UTF-8">
                    <title>طباعة البطاقة</title>
                    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@400;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                    <style>
                        /* Local Card Styles */
                        ${document.getElementById('card-styles').innerHTML}

                        body {
                            margin: 0;
                            padding: 20px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            gap: 30px;
                            background: #ffffff;
                        }
                        .card-print-wrapper {
                            page-break-after: always;
                            page-break-inside: avoid;
                        }
                        .card-print-wrapper:last-child {
                            page-break-after: avoid;
                        }
                        .no-print {
                            display: none !important;
                        }
                        
                        /* Mirror active class state from parent */
                        ${!withBg ? `
                        .digital-card {
                            background-image: none !important;
                            background-color: transparent !important;
                            background: transparent !important;
                            box-shadow: none !important;
                            border: none !important;
                        }
                        ` : ''}
                    </style>
                </head>
                <body>
                    <div class="card-print-wrapper">${frontHtml}</div>
                    <div class="card-print-wrapper">${backHtml}</div>
                    <script>
                        // Execute print once document is parsed and rendered
                        setTimeout(function() {
                            window.print();
                            window.close();
                        }, 500);
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();

            // Restore background state
            setBackgroundState(true);
        }, 50);
    }

    function downloadCard(side, withBg = true) {
        const card = side === 'front' 
            ? document.getElementById('digitalIDCardFront')
            : document.getElementById('digitalIDCardBack');
        if (!card) return;
        
        setBackgroundState(withBg);
        
        setTimeout(() => {
            htmlToImage.toPng(card, {
                pixelRatio: 2.5,
                style: {
                    transform: 'scale(1)',
                    transformOrigin: 'top left'
                }
            })
            .then(dataUrl => {
                const link = document.createElement('a');
                const sideName = side === 'front' ? 'front' : 'back';
                const bgName = withBg ? 'with_bg' : 'no_bg';
                link.download = `card_${sideName}_${bgName}.png`;
                link.href = dataUrl;
                link.click();
                
                // Restore background state
                setBackgroundState(true);
            })
            .catch(err => {
                alert('حدث خطأ أثناء تحميل البطاقة كصورة. قد يكون ذلك بسبب كوكيز المتصفح أو قيود حماية الصور.');
                console.error(err);
                setBackgroundState(true);
            });
        }, 50);
    }

    function downloadPDF(withBg = true) {
        const frontCard = document.getElementById('digitalIDCardFront');
        const backCard = document.getElementById('digitalIDCardBack');
        if (!frontCard || !backCard) return;

        // Show loading state
        const targetBtn = event ? event.currentTarget : null;
        let originalText = '';
        if (targetBtn) {
            originalText = targetBtn.innerHTML;
            targetBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> جاري التحميل...';
            targetBtn.disabled = true;
        }

        setBackgroundState(withBg);

        setTimeout(() => {
            htmlToImage.toPng(frontCard, { pixelRatio: 2.5 })
                .then(frontDataUrl => {
                    htmlToImage.toPng(backCard, { pixelRatio: 2.5 })
                        .then(backDataUrl => {
                            const { jsPDF } = window.jspdf;
                            
                            // Standard CR80 Card Size is 85.6mm x 53.98mm.
                            const pdf = new jsPDF({
                                orientation: 'landscape',
                                unit: 'mm',
                                format: [85.6, 53.98]
                            });
                            
                            // Add front
                            pdf.addImage(frontDataUrl, 'PNG', 0, 0, 85.6, 53.98);
                            
                            // Add new page for back
                            pdf.addPage([85.6, 53.98], 'landscape');
                            pdf.addImage(backDataUrl, 'PNG', 0, 0, 85.6, 53.98);
                            
                            // Save
                            const bgName = withBg ? 'with_bg' : 'no_bg';
                            pdf.save(`digital_card_${bgName}.pdf`);
                            
                            // Restore state
                            setBackgroundState(true);
                            if (targetBtn) {
                                targetBtn.innerHTML = originalText;
                                targetBtn.disabled = false;
                            }
                        })
                        .catch(err => {
                            alert('حدث خطأ أثناء تحميل ظهر البطاقة');
                            console.error(err);
                            setBackgroundState(true);
                            if (targetBtn) {
                                targetBtn.innerHTML = originalText;
                                targetBtn.disabled = false;
                            }
                        });
                })
                .catch(err => {
                    alert('حدث خطأ أثناء تحميل وجه البطاقة');
                    console.error(err);
                    setBackgroundState(true);
                    if (targetBtn) {
                        targetBtn.innerHTML = originalText;
                        targetBtn.disabled = false;
                    }
                });
        }, 100);
    }

    document.addEventListener("DOMContentLoaded", function() {
        onWilayaChange();
    });
</script>
@endsection
