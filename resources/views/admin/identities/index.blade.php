@extends('layouts.main')

@section('title', 'سجل الهويات الوطني البيومتري — SGFEP')

@section('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   IDENTITY REGISTRY — Premium Sovereign UI Design
   ═══════════════════════════════════════════════════════════════ */
.identity-hero {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #3a7bd5 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 45px rgba(30, 60, 114, 0.2);
}
.identity-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events: none;
}
.identity-hero-title {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.3rem;
}
.identity-hero-sub {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.8);
    font-family: 'Cairo', sans-serif;
}

/* Glassmorphic Tables & Filters */
.identity-card {
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
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: #fff;
    border: none;
    font-family: 'Cairo', sans-serif;
    font-weight: 600;
    border-radius: 10px;
    padding: 0.6rem 1.5rem;
    transition: all 0.2s;
}
.btn-filter-submit:hover {
    box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
    transform: translateY(-1px);
    color: #fff;
}

/* Badges */
.badge-role {
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    font-size: 0.75rem;
    padding: 0.4rem 0.8rem;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-role-employee {
    background: rgba(46, 204, 113, 0.12);
    color: #2ecc71;
}
.badge-role-trainee {
    background: rgba(52, 152, 219, 0.12);
    color: #3498db;
}
.badge-role-unregistered {
    background: rgba(127, 140, 141, 0.12);
    color: #7f8c8d;
}

/* Details Modal & Printable layouts */
.modal-cairo {
    font-family: 'Cairo', sans-serif;
}
.modal-header-gradient {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: #fff;
}
.profile-section-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1e3c72;
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

/* Print CSS */
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endsection

@section('content')
<div class="container-fluid py-4 modal-cairo">
    
    <!-- Hero Header -->
    <div class="identity-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="identity-hero-title">
                    <i class="fa-solid fa-id-card-clip me-2 text-warning"></i>
                    سجل الهويات الوطني البيومتري الموحد
                </h1>
                <p class="identity-hero-sub mb-0">
                    بوابة التحقق ومطابقة الهويات الرقمية لقطاع التكوين والتعليم المهنيين بالتكامل مع سجل الحالة المدنية
                </p>
            </div>
            <div class="no-print">
                <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> طباعة القائمة الحالية
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="identity-card no-print">
        <form method="GET" action="{{ route('admin.identities.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold text-secondary small">بحث بالاسم أو رقم التعريف الوطني (NIN)</label>
                <div class="search-input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="أدخل رقم التعريف الوطني (18 رقماً) أو الاسم...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold text-secondary small">الصفة الإدارية بالقطاع</label>
                <select name="role" class="form-select filter-select">
                    <option value="">كل الصفات</option>
                    <option value="employee" {{ $filterRole === 'employee' ? 'selected' : '' }}>موظف / مؤطر</option>
                    <option value="trainee" {{ $filterRole === 'trainee' ? 'selected' : '' }}>متربص / متمهن</option>
                    <option value="unregistered" {{ $filterRole === 'unregistered' ? 'selected' : '' }}>غير مسجل بالقطاع</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold text-secondary small">الجنس</label>
                <select name="sexe" class="form-select filter-select">
                    <option value="">الكل</option>
                    <option value="M" {{ $sexe === 'M' ? 'selected' : '' }}>ذكر</option>
                    <option value="F" {{ $sexe === 'F' ? 'selected' : '' }}>أنثى</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-filter-submit w-100">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="identity-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size: 0.8rem; font-weight: 800;">رقم التعريف الوطني (NIN)</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">الاسم واللقب بالعربية</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">الاسم واللقب بالفرنسية</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">تاريخ الميلاد</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">الجنس</th>
                        <th style="font-size: 0.8rem; font-weight: 800;">الحالة بالقطاع</th>
                        <th style="font-size: 0.8rem; font-weight: 800;" class="text-center no-print">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="fw-bold text-dark font-monospace">{{ $item['identity']->nin }}</td>
                            <td>{{ $item['identity']->nom_ar }} {{ $item['identity']->prenom_ar }}</td>
                            <td class="text-uppercase text-secondary font-monospace" style="font-size: 0.85rem;">
                                {{ $item['identity']->nom }} {{ $item['identity']->prenom }}
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($item['identity']->date_naissance)->format('Y-m-d') }}
                                @if($item['identity']->presume)
                                    <span class="badge bg-warning text-dark small" style="font-size: 0.65rem;">مفترض</span>
                                @endif
                            </td>
                            <td>
                                @if($item['identity']->sexe === 'M')
                                    <span class="text-primary"><i class="fa-solid fa-mars me-1"></i> ذكر</span>
                                @else
                                    <span class="text-danger"><i class="fa-solid fa-venus me-1"></i> أنثى</span>
                                @endif
                            </td>
                            <td>
                                @if($item['status'] === 'employee')
                                    <span class="badge-role badge-role-employee">
                                        <i class="fa-solid fa-user-tie"></i> {{ $item['details']['role_label'] }}
                                    </span>
                                    <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-building me-1"></i> {{ $item['details']['etab'] }}
                                    </div>
                                @elseif($item['status'] === 'trainee')
                                    <span class="badge-role badge-role-trainee">
                                        <i class="fa-solid fa-user-graduate"></i> {{ $item['details']['role_label'] }}
                                    </span>
                                    <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-graduation-cap me-1"></i> {{ $item['details']['etab'] }}
                                    </div>
                                @else
                                    <span class="badge-role badge-role-unregistered">
                                        <i class="fa-solid fa-user-slash"></i> {{ $item['details']['role_label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center no-print">
                                <div class="btn-group gap-1">
                                    <button class="btn btn-sm btn-outline-primary border-0" onclick="viewIdentityDetail('{{ $item['identity']->nin }}')" title="عرض الملف الكامل">
                                        <i class="fa-solid fa-eye"></i> التفاصيل
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary border-0" onclick="printIdentityCard('{{ $item['identity']->nin }}')" title="طباعة شهادة المطابقة">
                                        <i class="fa-solid fa-print"></i> طباعة
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-triangle-exclamation fa-2x mb-2 text-warning"></i>
                                <p class="mb-0">لم يتم العثور على أي هويات مطابقة لمعايير البحث الحالية.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Links -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 no-print">
            <div class="text-muted small">
                عرض {{ $identitiesPaginated->firstItem() }} إلى {{ $identitiesPaginated->lastItem() }} من إجمالي {{ $identitiesPaginated->total() }} سجل
            </div>
            <div>
                {{ $identitiesPaginated->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Detailed Civil Status Modal -->
<div class="modal fade modal-cairo" id="identityDetailModal" tabindex="-1" aria-labelledby="identityDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-header-gradient p-3">
                <h5 class="modal-title fw-bold" id="identityDetailModalLabel">
                    <i class="fa-solid fa-id-card me-2"></i> بطاقة الهوية الرقمية المفصلة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalBodyContent">
                <!-- Dynamic Content injected via JS -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnPrintModalCard">
                    <i class="fa-solid fa-print me-1"></i> طباعة الشهادة
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function viewIdentityDetail(nin) {
    const modal = new bootstrap.Modal(document.getElementById('identityDetailModal'));
    const body = document.getElementById('modalBodyContent');
    const printBtn = document.getElementById('btnPrintModalCard');
    
    // Set loading
    body.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">جاري سحب الهوية ومطابقة البيانات المدنية...</p>
        </div>
    `;
    printBtn.onclick = null;
    modal.show();

    fetch(`${APP_URL}/dashboard/identities/${nin}`)
        .then(res => res.json())
        .then(response => {
            if (!response.success) {
                body.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> ${response.message}
                    </div>
                `;
                return;
            }

            const data = response.data;
            const extra = response.extra || {};
            const emp = response.employee;
            const train = response.trainee;

            // Generate HTML
            let html = `
                <div class="row g-4">
                    <!-- Right: Civil Information -->
                    <div class="col-md-7">
                        <h6 class="profile-section-title">
                            <i class="fa-solid fa-user me-1 text-primary"></i> بيانات الحالة المدنية
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <span class="profile-field-label">اللقب والاسم (بالعربية)</span>
                                <div class="profile-field-value text-primary">${data.nom_ar || '—'} ${data.prenom_ar || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">الاسم واللقب (بالفرنسية)</span>
                                <div class="profile-field-value text-uppercase font-monospace">${data.nom || '—'} ${data.prenom || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">رقم التعريف الوطني (NIN)</span>
                                <div class="profile-field-value font-monospace text-dark">${data.nin}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">رقم عقد الميلاد</span>
                                <div class="profile-field-value font-monospace">${data.numero_acte_naissance || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">تاريخ الميلاد</span>
                                <div class="profile-field-value">${data.date_naissance} ${data.presume ? '<span class="badge bg-warning text-dark small">مفترض</span>' : ''}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">بلد ومكان الميلاد</span>
                                <div class="profile-field-value">${extra.lieu_nais || '—'} (${data.pays_naissance === 'DZ' ? 'الجزائر' : data.pays_naissance})</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">اسم الأب</span>
                                <div class="profile-field-value">${extra.pren_pere || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">اسم ولقب الأم</span>
                                <div class="profile-field-value">${extra.pren_mere || '—'} ${extra.nom_mere || '—'}</div>
                            </div>
                        </div>
                        
                        <h6 class="profile-section-title mt-4">
                            <i class="fa-solid fa-ring me-1 text-primary"></i> الحالة الاجتماعية والعائلية
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <span class="profile-field-label">الوضعية العائلية</span>
                                <div class="profile-field-value">${extra.isMaried === '1' ? 'متزوج(ة)' : 'أعزب / عزباء'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">الزوج / الزوجات</span>
                                <div class="profile-field-value text-secondary small">${extra.epoux || extra.epouses || '—'}</div>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label">الحالة الحيوية</span>
                                <div class="profile-field-value">
                                    ${extra.isDied === '1' ? '<span class="badge bg-danger">متوفى(ة)</span>' : '<span class="badge bg-success">حي(ة)</span>'}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Left: Sector Enrollment Info -->
                    <div class="col-md-5 bg-light p-3 rounded-3 border-start">
                        <h6 class="profile-section-title">
                            <i class="fa-solid fa-graduation-cap me-1 text-primary"></i> الموضعة بالقطاع
                        </h6>
                        
                        <div class="text-center mb-3">
                            <i class="fa-solid fa-id-card-clip fa-3x text-secondary mb-2"></i>
                            <div class="fw-bold">الصفة الحالية</div>
                        </div>
            `;

            if (emp) {
                html += `
                    <div class="alert alert-success border-0 text-center">
                        <i class="fa-solid fa-user-tie fa-lg mb-1 d-block"></i>
                        <strong>موظف / مؤطر بالقطاع</strong>
                    </div>
                    <div class="mb-3">
                        <span class="profile-field-label">المؤسسة الحالية</span>
                        <div class="profile-field-value text-dark">${emp.etab_nom || '—'}</div>
                    </div>
                    <div class="mb-3">
                        <span class="profile-field-label">الرابط الإداري</span>
                        <div class="mt-1">
                            <a href="${APP_URL}/dashboard/rh-gestion?tab=personnel" class="btn btn-sm btn-primary w-100">
                                <i class="fa-solid fa-user me-1"></i> ملف الموظف
                            </a>
                        </div>
                    </div>
                `;
            } else if (train) {
                html += `
                    <div class="alert alert-info border-0 text-center">
                        <i class="fa-solid fa-user-graduate fa-lg mb-1 d-block"></i>
                        <strong>متربص / متمهن بالقطاع</strong>
                    </div>
                    <div class="mb-2">
                        <span class="profile-field-label">مؤسسة التكوين</span>
                        <div class="profile-field-value text-dark">${train.etab_nom || '—'}</div>
                    </div>
                    <div class="mb-2">
                        <span class="profile-field-label">التخصص</span>
                        <div class="profile-field-value text-secondary font-monospace small">${train.spec_nom || '—'}</div>
                    </div>
                    <div class="mt-3">
                        <a href="${APP_URL}/dashboard/encadrement" class="btn btn-sm btn-info w-100 text-white">
                            <i class="fa-solid fa-graduation-cap me-1"></i> متابعة المتربص
                        </a>
                    </div>
                `;
            } else {
                html += `
                    <div class="alert alert-secondary border-0 text-center">
                        <i class="fa-solid fa-user-slash fa-lg mb-1 d-block text-muted"></i>
                        <strong>غير مسجل حالياً بالقطاع</strong>
                    </div>
                    <p class="small text-muted text-center">لا توجد أي بيانات للمتربص أو للموظف في السجلات الحالية بهذا الرقم.</p>
                `;
            }

            html += `
                    </div>
                </div>
            `;

            body.innerHTML = html;

            // Set dynamic print action
            printBtn.onclick = function() {
                printIdentityCard(data.nin);
            };
        })
        .catch(err => {
            body.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> خطأ أثناء سحب البيانات: ${err.message}
                </div>
            `;
        });
}

function printIdentityCard(nin) {
    // Open a styled clean print window
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    
    fetch(`${APP_URL}/dashboard/identities/${nin}`)
        .then(res => res.json())
        .then(response => {
            if (!response.success) {
                alert('فشل سحب بيانات الهوية للطباعة');
                printWindow.close();
                return;
            }

            const data = response.data;
            const extra = response.extra || {};
            const emp = response.employee;
            const train = response.trainee;

            let statusLabel = 'غير مسجل بالقطاع';
            let subStatus = '—';
            if (emp) {
                statusLabel = 'موظف / مؤطر رسمي';
                subStatus = emp.etab_nom;
            } else if (train) {
                statusLabel = 'متربص / متمهن';
                subStatus = `${train.etab_nom} - ${train.spec_nom}`;
            }

            printWindow.document.write(`
                <html>
                <head>
                    <title>بطاقة مطابقة الهوية الوطنية البيومترية</title>
                    <style>
                        body {
                            font-family: 'Cairo', 'Courier New', sans-serif;
                            direction: rtl;
                            padding: 40px;
                            color: #333;
                            background-color: #fff;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 3px double #333;
                            padding-bottom: 20px;
                        }
                        .title {
                            font-size: 22px;
                            font-weight: bold;
                            margin: 15px 0;
                        }
                        .meta-info {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 25px;
                            font-size: 14px;
                        }
                        .section-title {
                            font-size: 16px;
                            font-weight: bold;
                            background-color: #f2f2f2;
                            padding: 8px 12px;
                            margin-top: 25px;
                            border-right: 4px solid #1e3c72;
                        }
                        .grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 15px;
                            margin-top: 15px;
                            font-size: 14px;
                        }
                        .field {
                            display: flex;
                            border-bottom: 1px dotted #ccc;
                            padding-bottom: 5px;
                        }
                        .label {
                            font-weight: bold;
                            color: #555;
                            min-width: 180px;
                        }
                        .value {
                            flex: 1;
                        }
                        .footer {
                            margin-top: 50px;
                            text-align: left;
                            font-size: 13px;
                        }
                        .stamp-space {
                            margin-top: 20px;
                            border: 1px dashed #aaa;
                            width: 200px;
                            height: 100px;
                            display: inline-block;
                            text-align: center;
                            line-height: 100px;
                            color: #777;
                        }
                        @media print {
                            button { display: none !important; }
                        }
                    </style>
                </head>
                <body>
                    <div style="text-align: center;">
                        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-bottom: 20px; background-color: #1e3c72; color: white; border: none; border-radius: 5px;">
                            طباعة المستند
                        </button>
                    </div>
                    <div class="header">
                        <div>الجمهورية الجزائرية الديمقراطية الشعبية</div>
                        <div style="font-weight: bold; margin-top: 5px;">وزارة التكوين والتعليم المهنيين</div>
                        <div class="title">بطاقة مطابقة ومصادقة الهوية الوطنية البيومترية</div>
                        <div style="font-size: 12px; color: #666;">مستخرجة الكترونياً من سجل الموضعة الوطني الموحد</div>
                    </div>

                    <div class="meta-info">
                        <div><strong>تاريخ الاستخراج:</strong> ${new Date().toLocaleDateString('ar-DZ')}</div>
                        <div><strong>رمز المطابقة الرقمي:</strong> VAL-${Math.floor(100000 + Math.random() * 900000)}</div>
                    </div>

                    <div class="section-title">بيانات الهوية والبيانات المدنية</div>
                    <div class="grid">
                        <div class="field"><div class="label">رقم التعريف الوطني (NIN):</div><div class="value" style="font-weight: bold;">${data.nin}</div></div>
                        <div class="field"><div class="label">اللقب والاسم (بالعربية):</div><div class="value">${data.nom_ar || ''} ${data.prenom_ar || ''}</div></div>
                        <div class="field"><div class="label">اللقب والاسم (بالفرنسية):</div><div class="value text-uppercase">${data.nom || ''} ${data.prenom || ''}</div></div>
                        <div class="field"><div class="label">تاريخ الميلاد:</div><div class="value">${data.date_naissance} ${data.presume ? '(مفترض)' : ''}</div></div>
                        <div class="field"><div class="label">مكان الميلاد:</div><div class="value">${extra.lieu_nais || '—'}</div></div>
                        <div class="field"><div class="label">رقم عقد الميلاد:</div><div class="value">${data.numero_acte_naissance || '—'}</div></div>
                        <div class="field"><div class="label">اسم الأب:</div><div class="value">${extra.pren_pere || '—'}</div></div>
                        <div class="field"><div class="label">اسم ولقب الأم:</div><div class="value">${extra.pren_mere || '—'} ${extra.nom_mere || '—'}</div></div>
                    </div>

                    <div class="section-title">بيانات الموضعة الحالية بالقطاع</div>
                    <div class="grid">
                        <div class="field"><div class="label">الصفة الحالية:</div><div class="value" style="font-weight: bold; color: #1e3c72;">${statusLabel}</div></div>
                        <div class="field"><div class="label">المؤسسة / التخصص:</div><div class="value">${subStatus}</div></div>
                        <div class="field"><div class="label">الحالة العائلية:</div><div class="value">${extra.isMaried === '1' ? 'متزوج(ة)' : 'أعزب/عزباء'}</div></div>
                        <div class="field"><div class="label">الحالة الحيوية للسجل:</div><div class="value">${extra.isDied === '1' ? 'متوفى' : 'حي(ة)'}</div></div>
                    </div>

                    <div class="footer">
                        <div style="float: right;">
                            <strong>توقيع وختم المصلحة المركزية المعنية</strong>
                            <div class="stamp-space">ختم المنصة الرقمية</div>
                        </div>
                        <div style="float: left; text-align: center; margin-top: 40px;">
                            <strong>ملاحظة هامة:</strong><br>
                            يتم إعداد هذا المستند استناداً لعمليات المزامنة والتكامل البيومتري لقاعدة البيانات الوطنية.<br>
                            أي شطب أو تغيير يلغي صلاحية هذه البطاقة.
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        });
}
</script>
@endsection
