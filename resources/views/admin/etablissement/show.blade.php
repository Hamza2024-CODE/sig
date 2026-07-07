@extends('layouts.main')

@section('title', $title)

@section('styles')
<style>
/* ── Establishment Profile Premium Styles ── */
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

/* Sidebar / Info Card */
.info-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-sm);
    padding: var(--sp-6);
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: all var(--d-normal) var(--ease);
}
.info-card:hover { box-shadow: var(--shadow-md); }

.emblem-container {
    background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%);
    color: var(--primary-500);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto var(--sp-4) auto;
    border: 3px solid var(--border);
    box-shadow: var(--shadow-sm);
}

.ets-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .35rem 1rem;
    background: var(--primary-glow);
    border: 1px solid var(--primary-border);
    border-radius: var(--r-full);
    color: var(--primary-400);
    font-size: var(--text-xs);
    font-weight: 800;
    margin: 0 auto var(--sp-4) auto;
}

.info-section-title {
    font-size: var(--text-md);
    font-weight: 800;
    color: var(--tx-1);
    margin-bottom: var(--sp-4);
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding-bottom: var(--sp-2);
    border-bottom: 1px solid var(--border);
}

.meta-table {
    width: 100%;
}
.meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 0;
    border-bottom: 1px dashed var(--border);
}
.meta-row:last-child {
    border-bottom: none;
}
.meta-label {
    font-size: var(--text-xs);
    font-weight: 700;
    color: var(--tx-3);
}
.meta-value {
    font-size: var(--text-sm);
    font-weight: 700;
    color: var(--tx-1);
    text-align: left;
}

/* Form Card */
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
.input-wrapper .form-control,
.input-wrapper .form-textarea {
    padding-right: 2.75rem !important;
}
.input-wrapper .form-control:focus ~ .input-icon,
.input-wrapper .form-textarea:focus ~ .input-icon {
    color: var(--primary-400);
}

.form-textarea {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: var(--tx-1);
    background-color: var(--bg-card);
    background-clip: padding-box;
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}
.form-textarea:focus {
    border-color: var(--primary-400);
    outline: 0;
    box-shadow: 0 0 0 0.25rem var(--primary-glow);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: var(--sp-3);
    margin-top: var(--sp-6);
    padding-top: var(--sp-5);
    border-top: 1px solid var(--border);
}

/* Self-contained Toast Notification */
.ets-toast {
    position: fixed;
    bottom: 24px;
    left: 24px;
    padding: 1rem 1.5rem;
    border-radius: var(--r-md);
    background: var(--bg-card);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-xl);
    z-index: 9999;
    display: none;
    align-items: center;
    gap: 0.75rem;
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    transform: translateY(20px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
}
.ets-toast.show {
    display: flex;
    transform: translateY(0);
    opacity: 1;
}
.ets-toast.success {
    border-right: 5px solid var(--success, #2ec4b6);
    color: var(--success, #2ec4b6);
}
.ets-toast.error {
    border-right: 5px solid var(--danger, #e71d36);
    color: var(--danger, #e71d36);
}
</style>
@endsection

@section('content')
<!-- Page Banner -->
<div class="profile-banner">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="profile-banner-title">
                <i class="fa-solid fa-hotel me-2"></i>ملف المؤسسة التكوينية
            </h1>
            <p class="profile-banner-sub">عرض وتعديل البيانات الرسمية، الرمز الوطني، والعنوان التفصيلي للمقر</p>
        </div>
        <a href="{{ url('dashboard') }}" class="btn btn-light px-4 fw-bold shadow-sm" style="border-radius: var(--r-md);">
            <i class="fa-solid fa-house me-1"></i> لوحة التحكم
        </a>
    </div>
</div>

@if($isAdminOrCentral)
<!-- Etablissement Selection Selector (Admin & Central Roles) -->
<div class="card mb-4 shadow-sm" style="border-radius: var(--r-xl); border: 1px solid var(--border); background: var(--bg-card); font-family: 'Cairo', sans-serif;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3" style="color: var(--tx-1);">
            <i class="fa-solid fa-magnifying-glass text-primary me-2"></i>البحث واختيار ملف المؤسسة التكوينية
        </h5>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-bold">الولاية / مديرية التكوين المهني</label>
                <select id="selectWilaya" class="form-select" style="font-family: 'Cairo';">
                    <option value="">— اختر الولاية —</option>
                    @foreach($wilayas as $w)
                        <option value="{{ $w->IDWilayaa }}">{{ $w->Num }} - {{ $w->Nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">المؤسسة التكوينية</label>
                <select id="selectEtablissement" class="form-select" style="font-family: 'Cairo';">
                    <option value="">— اختر المؤسسة —</option>
                    @foreach($etablissements as $e)
                        <option value="{{ $e->IDetablissement }}" data-dfep="{{ $e->IDDFEP }}" @if($e->IDetablissement == $selectedEtabId) selected @endif>{{ $e->Nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" onclick="loadEtabProfile()" class="btn btn-primary w-100 fw-bold" style="border-radius: var(--r-md); height: 38px;">
                    <i class="fa-solid fa-check me-1"></i> عرض الملف
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="container-fluid px-0">
    <div class="row g-4">
        <!-- Sidebar Institutional Info Column -->
        <div class="col-lg-4">
            <div class="info-card">
                <div class="emblem-container">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="text-center mb-3">
                    <h4 class="fw-bold mb-1" style="font-family: 'Cairo'; color: var(--tx-1);">{{ $etab['Nom'] }}</h4>
                    <p class="text-muted small mb-3" style="font-family: 'Outfit';">{{ $etab['NomFr'] ?? 'Non renseigné' }}</p>
                    <div class="ets-badge">
                        <i class="fa-solid fa-shield-halved me-1"></i>
                        {{ strtoupper($roleCode) }} — رقم المؤسسة: #{{ $etab['IDetablissement'] }}
                    </div>
                </div>

                <h5 class="info-section-title">
                    <i class="fa-solid fa-circle-info text-primary"></i>
                    بيانات مرجعية رسمية
                </h5>
                <div class="meta-table mb-4">
                    @if(!empty($etab['CodeEtsDecret']))
                    <div class="meta-row">
                        <span class="meta-label">رمز مرسوم التأسيس</span>
                        <span class="meta-value" style="font-family: var(--font-mono);">{{ $etab['CodeEtsDecret'] }}</span>
                    </div>
                    @endif
                    @if(!empty($etab['CodeEtsMihnati']))
                    <div class="meta-row">
                        <span class="meta-label">رمز مهنتي (Mihnati)</span>
                        <span class="meta-value" style="font-family: var(--font-mono);">{{ $etab['CodeEtsMihnati'] }}</span>
                    </div>
                    @endif
                    @if(!empty($etab['DateDecret']))
                    <div class="meta-row">
                        <span class="meta-label">تاريخ المرسوم</span>
                        <span class="meta-value">{{ date('d/m/Y', strtotime($etab['DateDecret'])) }}</span>
                    </div>
                    @endif
                    @if(!empty($etab['Tel']))
                    <div class="meta-row">
                        <span class="meta-label">الهاتف الرسمي</span>
                        <span class="meta-value" style="font-family: var(--font-mono); direction: ltr;">{{ $etab['Tel'] }}</span>
                    </div>
                    @endif
                    @if(!empty($etab['Email']))
                    <div class="meta-row">
                        <span class="meta-label">البريد الرسمي</span>
                        <span class="meta-value" style="font-family: var(--font-mono);">{{ $etab['Email'] }}</span>
                    </div>
                    @endif
                </div>

                <h5 class="info-section-title">
                    <i class="fa-solid fa-chart-simple text-success"></i>
                    طاقة الاستيعاب الإجمالية
                </h5>
                <div class="meta-table">
                    <div class="meta-row">
                        <span class="meta-label">التكوين الحضوري (المجموع)</span>
                        <span class="meta-value"><span class="badge bg-success px-2">{{ $etab['CapaciteT'] ?? 0 }} مقعد</span></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">الإقامة الداخلية (النظام الداخلي)</span>
                        <span class="meta-value"><span class="badge bg-info px-2">{{ $etab['CapaciteInternaT'] ?? 0 }} سرير</span></span>
                    </div>
                </div>

                @if($etab['IDetablissement'] == 374)
                <div class="card border-0 shadow-sm rounded-4 p-4 mt-4 text-white" style="background: linear-gradient(135deg, #311084 0%, #1e1b4b 100%); font-family: 'Cairo', sans-serif;">
                    <h6 class="fw-bold text-warning mb-2"><i class="fa-solid fa-key me-2"></i>بيانات مصلحة التمهين</h6>
                    <p class="small mb-3 text-white-50">حساب الموظف المسؤول عن مصلحة التمهين (ميباركي العالية) مفعل وجاهز للاستخدام.</p>
                    <button type="button" class="btn btn-sm btn-warning w-100 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#mebarkiModal">
                        <i class="fa-solid fa-eye me-1"></i> عرض الرمز السري وبيانات الدخول
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Form Column -->
        <div class="col-lg-8">
            <div class="form-card">
                <h2 class="form-section-title">
                    <i class="fa-solid fa-file-signature"></i>
                    تحديث بيانات ملف المؤسسة
                </h2>

                <form id="etabProfileForm" onsubmit="submitEtabProfile(event)">
                    @csrf
                    
                    @if($isAdminOrCentral)
                        <input type="hidden" name="etab_id" value="{{ $selectedEtabId }}">
                    @endif

                    <div class="row g-4">
                        <!-- Full Name Arabic -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold">الاسم الكامل للمؤسسة (بالعربية) *</label>
                            <div class="input-wrapper">
                                <input type="text" name="nom" value="{{ $etab['Nom'] }}" class="form-control" required>
                                <i class="fa-solid fa-building input-icon"></i>
                            </div>
                        </div>

                        <!-- Full Name French -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Nom Complet de l'Établissement (Français)</label>
                            <div class="input-wrapper">
                                <input type="text" name="nom_fr" value="{{ $etab['NomFr'] ?? '' }}" class="form-control" style="font-family: 'Outfit', sans-serif; direction: ltr; text-align: right;">
                                <i class="fa-solid fa-building input-icon"></i>
                            </div>
                        </div>

                        <!-- Establishment Code -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">رمز المؤسسة</label>
                            <div class="input-wrapper">
                                <input type="text" name="code" value="{{ $etab['Code'] ?? '' }}" class="form-control" style="font-family: var(--font-mono);">
                                <i class="fa-solid fa-qrcode input-icon"></i>
                            </div>
                        </div>

                        <!-- National Headquarters Address -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">العنوان الوطني للمقر</label>
                            <div class="input-wrapper">
                                <input type="text" name="adres" value="{{ $etab['Adres'] ?? '' }}" class="form-control">
                                <i class="fa-solid fa-map-location-dot input-icon"></i>
                            </div>
                        </div>

                        <!-- Description / Details -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold">تفاصيل / وصف المؤسسة</label>
                            <div class="input-wrapper">
                                <textarea name="obs" rows="5" class="form-textarea">{{ $etab['Obs'] ?? '' }}</textarea>
                                <i class="fa-solid fa-align-right input-icon" style="top: 24px;"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-actions">
                        <a href="{{ url('dashboard') }}" class="btn btn-light px-4 fw-bold shadow-sm" style="border-radius: var(--r-md);">
                            <i class="fa-solid fa-xmark me-1"></i> إلغاء
                        </a>
                        <button type="submit" id="submitBtn" class="btn btn-primary px-5 fw-bold shadow-sm" style="border-radius: var(--r-md);">
                            <i class="fa-solid fa-floppy-disk me-1"></i> حفظ التحديثات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="etsToast" class="ets-toast">
    <i id="toastIcon" class="fa-solid"></i>
    <span id="toastMessage"></span>
</div>
@endsection

@section('scripts')
<script>
@if($isAdminOrCentral)
(function() {
    const wilayaSelect = document.getElementById('selectWilaya');
    const etabSelect  = document.getElementById('selectEtablissement');
    if (!wilayaSelect || !etabSelect) return;

    // Cache all options
    const allOptions = Array.from(etabSelect.options).map(o => ({
        value: o.value, 
        text: o.text, 
        dfep: o.getAttribute('data-dfep')
    }));

    // Pre-select the Wilaya corresponding to the active establishment
    const selectedOpt = etabSelect.options[etabSelect.selectedIndex];
    if (selectedOpt) {
        const dfepId = selectedOpt.getAttribute('data-dfep');
        if (dfepId) {
            wilayaSelect.value = dfepId;
        }
    }

    function filterEtablissements(dfepId) {
        const currentVal = etabSelect.value;
        etabSelect.innerHTML = '<option value="">— اختر المؤسسة —</option>';
        
        const filtered = dfepId ? allOptions.filter(o => o.dfep == dfepId) : allOptions;
        filtered.forEach(o => {
            if (!o.value) return; // skip placeholder
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-dfep', o.dfep);
            if (o.value == currentVal) opt.selected = true;
            etabSelect.appendChild(opt);
        });
    }

    wilayaSelect.addEventListener('change', function() {
        filterEtablissements(this.value);
    });
})();

function loadEtabProfile() {
    const etabId = document.getElementById('selectEtablissement').value;
    if (!etabId) {
        alert('يرجى اختيار مؤسسة تكوينية أولاً.');
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.set('etab_id', etabId);
    window.location.href = url.toString();
}
@endif

async function submitEtabProfile(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Disable submit button and show spinner
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> جاري الحفظ...';
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const url = '{{ request()->is('sig/*') ? url('sig/dashboard/etablissement/update') : url('dashboard/etablissement/update') }}';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            // Dynamically update UI values
            const nameEl = document.querySelector('.info-card h4.fw-bold');
            const subEl = document.querySelector('.info-card p.text-muted');
            if (nameEl) nameEl.textContent = data.nom;
            if (subEl) subEl.textContent = data.nom_fr || 'Non renseigné';
            
            // Show mebarki credentials popup if this is sidi gacem center
            const etabIdInput = form.querySelector('input[name="etab_id"]');
            const currentEtabId = etabIdInput ? etabIdInput.value : '{{ $etab["IDetablissement"] }}';
            if (currentEtabId == 374) {
                setTimeout(() => {
                    const myModal = new bootstrap.Modal(document.getElementById('mebarkiModal'));
                    myModal.show();
                }, 500);
            }
        } else {
            showToast(result.error || 'حدث خطأ أثناء حفظ البيانات.', 'error');
        }
    } catch (err) {
        showToast('فشل الاتصال بالخادم. يرجى التحقق من الشبكة.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

function showToast(msg, type) {
    const toast = document.getElementById('etsToast');
    const icon = document.getElementById('toastIcon');
    const message = document.getElementById('toastMessage');
    
    // Set classes and text
    toast.className = 'ets-toast ' + type;
    message.textContent = msg;
    
    if (type === 'success') {
        icon.className = 'fa-solid fa-circle-check fa-lg text-success';
    } else {
        icon.className = 'fa-solid fa-circle-exclamation fa-lg text-danger';
    }
    
    // Show toast with slide-in animation
    toast.style.display = 'flex';
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Hide toast after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }, 4000);
}
</script>

@if($etab['IDetablissement'] == 374)
<!-- Mebarki Credentials Modal -->
<div class="modal fade" id="mebarkiModal" tabindex="-1" aria-hidden="true" style="font-family: 'Cairo', sans-serif;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-white" style="background: linear-gradient(135deg, #1e1b4b 0%, #311084 100%);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-key me-2"></i>معلومات الولوج لمصلحة التمهين</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="fa-solid fa-user-shield text-warning mb-3 animate__animated animate__bounceIn" style="font-size: 3.5rem;"></i>
                <h4 class="fw-bold mb-1">مصلحة التمهين - سيدي قاسم</h4>
                <p class="text-white-50 small mb-4">بيانات الدخول المخصصة للموظف المسؤول عن تسيير مصلحة التمهين:</p>
                
                <div class="p-3 rounded-4 mb-3 text-start" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-light border-opacity-10">
                        <span class="text-white-50 small">اسم الموظف:</span>
                        <span class="fw-bold text-light">ميباركي العالية</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-light border-opacity-10">
                        <span class="text-white-50 small">اسم المستخدم:</span>
                        <span class="fw-bold text-warning" style="font-family: monospace; font-size: 1.05rem;">mebarki.elalia</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-white-50 small">الرمز السري (كلمة المرور):</span>
                        <span class="fw-bold text-warning" style="font-family: monospace; font-size: 1.05rem;">Mebarki@374</span>
                    </div>
                </div>
                <p class="text-white-50 small mb-0"><i class="fa-solid fa-circle-info text-info me-1"></i>يمكن للموظف استخدام هذه البيانات لتسجيل الدخول مباشرة إلى لوحة التحكم الخاصة به.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-warning w-100 fw-bold rounded-pill py-2.5 shadow-sm" data-bs-dismiss="modal">موافق، فهمت</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
