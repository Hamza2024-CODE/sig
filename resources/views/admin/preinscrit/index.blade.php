@extends('layouts.main')

@section('title', 'التسجيلات الأولية عبر الإنترنت — SGFEP')

@section('styles')
<style>
.pre-hero {
    background: linear-gradient(135deg, #1f4068 0%, #162447 50%, #0f1a1c 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 45px rgba(22, 36, 71, 0.25);
}
.pre-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.05) 0%, transparent 70%);
    pointer-events: none;
}
.pre-hero-title {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.3rem;
}
.pre-hero-sub {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.75);
    font-family: 'Cairo', sans-serif;
}

.pre-card {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--border, #e8edf5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    margin-bottom: 1.5rem;
}

.search-input-group {
    position: relative;
}
.search-input-group input {
    padding-right: 2.75rem;
    border-radius: 10px;
    font-family: 'Cairo', sans-serif;
    font-size: 0.88rem;
}
.search-input-group i {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--tx-3, #8898b0);
}

.filter-select {
    font-family: 'Cairo', sans-serif;
    font-size: 0.88rem;
    border-radius: 10px;
}

.btn-filter-submit {
    background: linear-gradient(135deg, #1f4068, #162447);
    color: #fff;
    border: none;
    font-family: 'Cairo', sans-serif;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.6rem 1.5rem;
    transition: all 0.2s;
}
.btn-filter-submit:hover {
    box-shadow: 0 4px 15px rgba(22, 36, 71, 0.35);
    transform: translateY(-1px);
    color: #fff;
}

.badge-status {
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    font-size: 0.75rem;
    padding: 0.4rem 0.8rem;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-status-pending {
    background: rgba(255, 127, 17, 0.12);
    color: #e07b00;
}
.badge-status-approved {
    background: rgba(28, 176, 95, 0.12);
    color: #1cb05f;
}
.badge-status-rejected {
    background: rgba(220, 53, 69, 0.12);
    color: #dc3545;
}

.modal-cairo {
    font-family: 'Cairo', sans-serif;
}
.modal-header-gradient {
    background: linear-gradient(135deg, #1f4068 0%, #162447 100%);
    color: #fff;
}
.profile-section-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1f4068;
    border-bottom: 2px solid #e8edf5;
    padding-bottom: 0.4rem;
    margin-bottom: 1rem;
}
.profile-field-label {
    font-size: 0.78rem;
    color: var(--tx-3, #7f8c8d);
    font-weight: 600;
}
.profile-field-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--tx-1, #2c3e50);
}

[data-theme="dark"] .pre-card { background: #1e2d4a; border-color: rgba(255,255,255,0.07); }
</style>
@endsection

@section('content')
<div class="container-fluid py-4 modal-cairo">
    
    <!-- Hero Header -->
    <div class="pre-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="pre-hero-title">
                    <i class="fa-solid fa-laptop-file me-2 text-warning"></i>
                    طلبات التسجيلات الأولية عبر الإنترنت
                </h1>
                <p class="pre-hero-sub mb-0">
                    مراجعة وتأكيد طلبات التسجيل الأولي للمترشحين الجدد وتحويلهم إلى طلبات رسمية بعد التحقق.
                </p>
            </div>
            <div>
                <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> طباعة القائمة
                </button>
            </div>
        </div>
    </div>

    @if (session('flash_success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('flash_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('flash_error'))
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('flash_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters Section -->
    <div class="pre-card no-print">
        <form method="GET" action="" class="row g-3 align-items-end">
            <!-- Search -->
            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary small">بحث بالاسم أو رقم التعريف الوطني (NIN)</label>
                <div class="search-input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="رقم التعريف الوطني، الاسم...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>

            <!-- Status -->
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary small">حالة الطلب</label>
                <select name="status" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="0" {{ $status === '0' ? 'selected' : '' }}>قيد الانتظار</option>
                    <option value="1" {{ $status === '1' ? 'selected' : '' }}>تم القبول</option>
                    <option value="2" {{ $status === '2' ? 'selected' : '' }}>مرفوض</option>
                </select>
            </div>

            <!-- Wilaya -->
            @if(in_array($role, ['admin', 'central', 'high_admin', 'dfep']))
                <div class="col-md-2">
                    <label class="form-label fw-bold text-secondary small">الولاية</label>
                    <select name="wilaya_id" class="form-select filter-select" onchange="resetAndSubmit(this)">
                        <option value="">كل الولايات</option>
                        @foreach($wilayas as $w)
                            <option value="{{ $w->id }}" {{ $wilayaId == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Center (Establishment) -->
            @if(in_array($role, ['admin', 'central', 'high_admin', 'dfep']))
                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary small">المؤسسة / المركز</label>
                    <select name="etablissement_id" class="form-select filter-select" onchange="resetOfferAndSubmit(this)">
                        <option value="">كل المؤسسات</option>
                        @foreach($etablissements as $et)
                            <option value="{{ $et->id }}" {{ $etablissementId == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Training Mode -->
            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary small">نمط التكوين</label>
                <select name="mode_id" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">كل الأنماط</option>
                    @foreach($modes as $m)
                        <option value="{{ $m->id }}" {{ $modeId == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Offre -->
            <div class="col-md-9">
                <label class="form-label fw-bold text-secondary small">العرض التكويني (التخصص)</label>
                <select name="offre_id" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">كل العروض التكوينية</option>
                    @foreach($offers as $of)
                        <option value="{{ $of->id }}" {{ $offreId == $of->id ? 'selected' : '' }}>{{ $of->spec_ar }} — {{ $of->etab_ar }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Submit buttons -->
            <div class="col-md-3 d-flex gap-2">
                <a href="{{ url('dashboard/preinscrits') }}" class="btn btn-outline-secondary w-50 fw-bold" style="border-radius:10px; font-family:'Cairo'; font-size:0.88rem; padding: 0.6rem 0;">
                    <i class="fa-solid fa-rotate-left me-1"></i> إعادة تعيين
                </a>
                <button type="submit" class="btn btn-filter-submit w-50">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="pre-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size: 0.8rem; font-weight: 800;">رقم الهوية الوطني (NIN)</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">الاسم واللقب بالعربية</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">التخصص المطلوب والمؤسسة</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">الهاتف والبريد</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">المستوى الدراسي</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">تاريخ التسجيل الأولي</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">الحالة</th>
                        <th style="font-size: 0.8rem; font-weight: 800;" class="text-center no-print">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preinscriptions as $pre)
                        <tr>
                            <td class="fw-bold text-dark font-monospace">{{ $pre->Nin }}</td>
                            <td>
                                @if(str_contains($pre->Nom, '?') || str_contains($pre->Prenom, '?'))
                                    @if(!empty($pre->correct_nom_ar) && !empty($pre->correct_prenom_ar))
                                        <strong class="text-success" title="تم استرداد الاسم الصحيح من الهوية الوطنية"><i class="fa-solid fa-id-card-clip me-1 text-success"></i>{{ $pre->correct_nom_ar }} {{ $pre->correct_prenom_ar }}</strong>
                                    @else
                                        <strong class="text-danger" title="الاسم غير مقروء في قاعدة البيانات">{{ $pre->Nom }} {{ $pre->Prenom }}</strong>
                                    @endif
                                @else
                                    <strong>{{ $pre->Nom }} {{ $pre->Prenom }}</strong>
                                @endif
                                <br><small class="text-uppercase text-secondary font-monospace" style="font-size: 0.78rem;">{{ $pre->NomFr }} {{ $pre->PrenomFr }}</small>
                            </td>
                            <td>
                                <strong>{{ $pre->spec_nom ?? 'غير محدد' }}</strong>
                                @if(!empty($pre->mode_nom))
                                    <span class="badge bg-secondary text-white ms-1 small" style="font-size: 0.68rem; font-family:'Cairo'; padding: 2px 6px;">{{ $pre->mode_nom }}</span>
                                @endif
                                <br><small class="text-muted" style="font-size: 0.78rem;">{{ $pre->etab_nom }}</small>
                            </td>
                            <td>
                                <i class="fa-solid fa-phone text-muted me-1 small"></i> {{ $pre->tel1 }}
                                @if($pre->email)
                                    <br><small class="text-muted"><i class="fa-solid fa-envelope me-1 small"></i> {{ $pre->email }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $pre->niveau_nom ?? 'غير محدد' }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($pre->dateInscr)->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($pre->Validation == 0)
                                    <span class="badge-status badge-status-pending">
                                        <i class="fa-solid fa-spinner fa-spin"></i> قيد الانتظار
                                    </span>
                                @elseif($pre->Validation == 1)
                                    <span class="badge-status badge-status-approved">
                                        <i class="fa-solid fa-circle-check"></i> مقبول ومحول
                                    </span>
                                @else
                                    <span class="badge-status badge-status-rejected" title="{{ $pre->remarque }}">
                                        <i class="fa-solid fa-circle-xmark"></i> مرفوض
                                    </span>
                                @endif
                            </td>
                            <td class="text-center no-print">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewPreinscritDetails({{ $pre->IDPreinscrit }})">
                                    <i class="fa-solid fa-eye me-1"></i> المعالجة
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary"></i>
                                <p class="mb-0">لا توجد طلبات تسجيلات أولية مسجلة حالياً.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 no-print">
            <div class="text-muted small">
                عرض {{ $preinscriptions->firstItem() }} إلى {{ $preinscriptions->lastItem() }} من إجمالي {{ $preinscriptions->total() }} طلب تسجيل
            </div>
            <div>
                {{ $preinscriptions->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade modal-cairo" id="preinscritModal" tabindex="-1" aria-labelledby="preinscritModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-header-gradient p-3">
                <h5 class="modal-title fw-bold" id="preinscritModalLabel">
                    <i class="fa-solid fa-user-check me-2"></i> تفاصيل ومعالجة طلب التسجيل الأولي
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalContent">
                <!-- Injected via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const BASE_URL = '{{ url("") }}';

function resetAndSubmit(select) {
    const form = select.form;
    const etabSelect = form.querySelector('select[name="etablissement_id"]');
    const offreSelect = form.querySelector('select[name="offre_id"]');
    if (etabSelect) etabSelect.value = '';
    if (offreSelect) offreSelect.value = '';
    form.submit();
}

function resetOfferAndSubmit(select) {
    const form = select.form;
    const offreSelect = form.querySelector('select[name="offre_id"]');
    if (offreSelect) offreSelect.value = '';
    form.submit();
}

function viewPreinscritDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('preinscritModal'));
    const content = document.getElementById('modalContent');
    
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">جاري سحب بيانات الملف الشخصي...</p>
        </div>
    `;
    modal.show();

    fetch(`${BASE_URL}/dashboard/preinscrits/show/${id}`)
        .then(res => res.json())
        .then(response => {
            if (!response.success) {
                content.innerHTML = `<div class="alert alert-danger text-center"><i class="fa-solid fa-exclamation-triangle me-2"></i> ${response.message}</div>`;
                return;
            }

            const data = response.data;
            const statusBadge = data.Validation == 0 ? 
                '<span class="badge-status badge-status-pending"><i class="fa-solid fa-spinner fa-spin"></i> قيد الانتظار</span>' :
                (data.Validation == 1 ? 
                    '<span class="badge-status badge-status-approved"><i class="fa-solid fa-circle-check"></i> مقبول</span>' : 
                    '<span class="badge-status badge-status-rejected"><i class="fa-solid fa-circle-xmark"></i> مرفوض</span>');

            let formActionHtml = '';
            if (data.Validation == 0) {
                formActionHtml = `
                    <div class="mt-4 p-3 bg-light rounded border">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-gavel me-1 text-primary"></i> اتخاذ القرار النهائي بشأن الملف</h6>
                        <form method="POST" action="${BASE_URL}/dashboard/preinscrits/action" class="row g-3 align-items-center">
                            @csrf
                            <input type="hidden" name="id" value="${data.IDPreinscrit}">
                            <div class="col-md-8">
                                <input type="text" name="remarque" class="form-control form-control-sm" placeholder="ملاحظة أو سبب الرفض...">
                            </div>
                            <div class="col-md-4 text-end d-flex gap-2">
                                <button type="submit" name="decision" value="approve" class="btn btn-sm btn-success w-50">
                                    <i class="fa-solid fa-check me-1"></i> قبول
                                </button>
                                <button type="submit" name="decision" value="reject" class="btn btn-sm btn-danger w-50">
                                    <i class="fa-solid fa-xmark me-1"></i> رفض
                                </button>
                            </div>
                        </form>
                    </div>
                `;
            } else {
                formActionHtml = `
                    <div class="mt-4 p-3 bg-light rounded border">
                        <span class="fw-bold">القرار المتخذ:</span>
                        <div class="text-secondary small mt-1"><i class="fa-solid fa-note-sticky me-1"></i> ${data.remarque || 'لا توجد ملاحظة.'}</div>
                    </div>
                `;
            }

            // Fallback for corrupted Arabic name in modal
            let displayNameAr = `${data.Nom || ''} ${data.Prenom || ''}`;
            if ((data.Nom && data.Nom.includes('?')) || (data.Prenom && data.Prenom.includes('?'))) {
                if (data.correct_nom_ar && data.correct_prenom_ar) {
                    displayNameAr = `<span class="text-success"><i class="fa-solid fa-id-card-clip me-1"></i>${data.correct_nom_ar} ${data.correct_prenom_ar} <span class="badge bg-success text-white" style="font-size: 0.65rem; padding: 2px 5px; font-family:'Cairo';">مسترد من الهوية</span></span>`;
                } else {
                    displayNameAr = `<span class="text-danger">${data.Nom || ''} ${data.Prenom || ''} <span class="badge bg-danger text-white" style="font-size: 0.65rem; padding: 2px 5px; font-family:'Cairo';">تالف</span></span>`;
                }
            }

            // Helper to get image path or pdf preview
            function getMediaHtml(path, label) {
                if (!path) return `<div class="border rounded p-2 text-center bg-white h-100"><span class="profile-field-label text-muted d-block fw-bold">${label}:</span><span class="text-muted small">لا توجد وثيقة</span></div>`;
                const lower = path.toLowerCase();
                const isImage = lower.endsWith('.png') || lower.endsWith('.jpg') || lower.endsWith('.jpeg') || lower.endsWith('.gif') || lower.endsWith('.svg') || lower.endsWith('.webp');
                const isPdf = lower.endsWith('.pdf');
                
                let resolved = path;
                if (path.startsWith('/uploads/')) {
                    resolved = `/sig${path}`;
                } else if (!lower.startsWith('http://') && !lower.startsWith('https://') && !lower.startsWith('/') && !lower.startsWith('data:')) {
                    resolved = `/sig/${path}`;
                }
                
                if (isImage) {
                    return `<div class="border rounded p-2 text-center bg-white h-100">
                        <span class="profile-field-label d-block mb-1 fw-bold text-muted">${label}</span>
                        <img src="${resolved}" class="img-thumbnail img-fluid rounded" style="max-height:120px; cursor:pointer;" onclick="window.open('${resolved}')">
                    </div>`;
                }
                if (isPdf) {
                    return `<div class="border rounded p-2 bg-white h-100">
                        <span class="profile-field-label d-block mb-1 fw-bold text-muted">${label}</span>
                        <iframe src="${resolved}" style="width:100%; height:160px;" border="0"></iframe>
                    </div>`;
                }
                
                return `<div class="border rounded p-2 bg-white h-100 text-center">
                    <span class="profile-field-label d-block mb-1 fw-bold text-muted">${label}</span>
                    <a href="${resolved}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2 mt-2 rounded">
                        <i class="fa-solid fa-download me-1"></i> تحميل/معاينة
                    </a>
                </div>`;
            }

            const docsSectionHtml = `
                <div class="col-12 mt-3">
                    <h6 class="profile-section-title"><i class="fa-solid fa-folder-open me-1 text-warning"></i> وثائق ومستندات طلب التسجيل الأولي (معاينة مباشرة)</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            ${getMediaHtml(data.photo_path, 'الصورة الشخصية / Photo')}
                        </div>
                        <div class="col-md-4">
                            ${getMediaHtml(data.certscol_path, 'الشهادة المدرسية / Scolarité')}
                        </div>
                        <div class="col-md-4">
                            ${getMediaHtml(data.actnaispdf_path, 'عقد الميلاد / Acte de Naissance')}
                        </div>
                        <div class="col-md-6 mt-2">
                            ${getMediaHtml(data.diplomecert_path, 'شهادة المؤهل / Diplôme')}
                        </div>
                        <div class="col-md-6 mt-2">
                            ${getMediaHtml(data.contratpdf_path, 'عقد التمهين / Contrat')}
                        </div>
                    </div>
                </div>
            `;

            content.innerHTML = `
                <div class="row g-4 text-right" dir="rtl">
                    <!-- Column 1: Civil Data -->
                    <div class="col-md-7">
                        <h6 class="profile-section-title"><i class="fa-solid fa-address-card me-1"></i> المعلومات المدنية والاجتماعية</h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <span class="profile-field-label">الاسم الكامل بالعربية</span>
                                <div class="profile-field-value text-primary">${displayNameAr}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">الاسم بالفرنسية</span>
                                <div class="profile-field-value text-uppercase font-monospace">${data.NomFr || '—'} ${data.PrenomFr || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">رقم التعريف الوطني (NIN)</span>
                                <div class="profile-field-value font-monospace">${data.Nin || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">تاريخ الميلاد</span>
                                <div class="profile-field-value">${data.DateNais} ${data.Presume ? '(مفترض)' : ''}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">مكان الميلاد</span>
                                <div class="profile-field-value">${data.LieuNais || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">رقم عقد الميلاد</span>
                                <div class="profile-field-value font-monospace">${data.NumActeNais || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">اسم الأب</span>
                                <div class="profile-field-value">${data.PrenomPere || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">اسم ولقب الأم</span>
                                <div class="profile-field-value">${data.PrenomMere || '—'} ${data.NomMere || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">الجنسية</span>
                                <div class="profile-field-value">${data.nationalite_nom || 'جزائرية'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">المستفيد من ذوي الهمم؟</span>
                                <div class="profile-field-value">${data.endicape == 1 ? 'نعم ♿' : 'لا'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Column 2: Program and Registration -->
                    <div class="col-md-5 bg-light p-3 rounded border-start">
                        <h6 class="profile-section-title"><i class="fa-solid fa-graduation-cap me-1"></i> معلومات التسجيل الموضعي</h6>
                        <div class="mb-3">
                            <span class="profile-field-label">التخصص المطلوب</span>
                            <div class="profile-field-value text-dark">${data.spec_nom || 'غير محدد'}</div>
                        </div>
                        <div class="mb-3">
                            <span class="profile-field-label">نمط التكوين</span>
                            <div class="profile-field-value text-dark">${data.mode_nom || 'غير محدد'}</div>
                        </div>
                        <div class="mb-3">
                            <span class="profile-field-label">المؤسسة التعليمية</span>
                            <div class="profile-field-value text-secondary small">${data.etab_nom || 'غير محدد'}</div>
                        </div>
                        <div class="mb-3">
                            <span class="profile-field-label">المستوى الدراسي المصرح به</span>
                            <div class="profile-field-value text-dark">${data.niveau_nom || '—'}</div>
                        </div>
                        <div class="mb-3">
                            <span class="profile-field-label">تاريخ طلب التسجيل</span>
                            <div class="profile-field-value text-dark font-monospace">${data.dateInscr}</div>
                        </div>
                        <div class="mb-3">
                            <span class="profile-field-label">حالة الطلب الحالية</span>
                            <div class="mt-1">${statusBadge}</div>
                        </div>
                    </div>
                    
                    <!-- Documents section -->
                    ${docsSectionHtml}
                </div>
                ${formActionHtml}
            `;
        })
        .catch(err => {
            content.innerHTML = `<div class="alert alert-danger text-center"><i class="fa-solid fa-exclamation-triangle me-2"></i> خطأ أثناء استرداد الملف: ${err.message}</div>`;
        });
}
</script>
@endsection
