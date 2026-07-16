@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<div class="container-fluid py-4" style="font-family:'Cairo', sans-serif;">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">دراسة ملفات المترشحين / Candidats</h3>
                <p class="text-muted mb-0">مراجعة والتحقق من ملفات التسجيل الأولي للمتربصين واتخاذ القرارات الإدارية</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                    <i class="fa-solid fa-user-plus me-1"></i> تسجيل مترشح جديد
                </button>
                <a href="/dashboard" class="btn btn-outline-secondary px-4" style="border-radius: 8px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> العودة للوحة القيادة
                </a>
            </div>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session()->has('flash_success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #e6f7ed; color: #006233;">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('flash_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('flash_error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #fdf2f2; color: #9b1c1c;">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('flash_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <style>
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
    </style>

    <!-- Advanced Filtering Bar -->
    <div class="card border-0 shadow-sm mb-4 no-print" style="border-radius: 16px; background:var(--card-bg); border:1px solid var(--card-border)!important;">
        <div class="card-body p-3">
            <form method="GET" action="{{ url('dashboard/candidates') }}" class="row g-3 align-items-end">
                <input type="hidden" name="status" value="{{ $statusFilter }}">

                <!-- Search -->
                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary small">بحث بالاسم أو رقم التعريف الوطني (NIN)</label>
                    <div class="search-input-group">
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="رقم التعريف الوطني، الاسم..." style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>

                <!-- Wilaya -->
                @if(in_array($role, ['admin', 'central', 'high_admin', 'dfep']))
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-secondary small">الولاية</label>
                        <select name="wilaya_id" class="form-select filter-select" onchange="this.form.submit()" style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                            <option value="">كل الولايات</option>
                            @foreach($wilayas as $w)
                                <option value="{{ $w->id }}" {{ ($filters['wilaya_id'] ?? '') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Center (Establishment) -->
                @if(in_array($role, ['admin', 'central', 'high_admin', 'dfep']))
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small">المؤسسة / المركز</label>
                        <select name="etablissement_id" class="form-select filter-select" onchange="this.form.submit()" style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                            <option value="">كل المؤسسات</option>
                            @foreach($etablissements as $et)
                                <option value="{{ $et->id }}" {{ ($filters['etablissement_id'] ?? '') == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Training Mode -->
                <div class="col-md-2">
                    <label class="form-label fw-bold text-secondary small">نمط التكوين</label>
                    <select name="mode_id" class="form-select filter-select" style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                        <option value="">كل الأنماط</option>
                        @foreach($modes as $m)
                            <option value="{{ $m->id }}" {{ ($filters['mode_id'] ?? '') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Offre -->
                <div class="col-md-9">
                    <label class="form-label fw-bold text-secondary small">العرض التكويني (التخصص)</label>
                    <select name="offre_id" class="form-select filter-select" style="background:var(--input-bg,#f8f9fa); font-size:0.88rem;">
                        <option value="">كل العروض التكوينية</option>
                        @foreach($offers as $of)
                            <option value="{{ $of->id }}" {{ ($filters['offre_id'] ?? '') == $of->id ? 'selected' : '' }}>{{ $of->spec_ar }} — {{ $of->etab_ar }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit buttons -->
                <div class="col-md-3 d-flex gap-2">
                    <a href="{{ url('dashboard/candidates') }}" class="btn btn-outline-secondary w-50 fw-bold" style="border-radius:10px; font-family:'Cairo'; font-size:0.88rem; padding: 0.6rem 0;">
                        <i class="fa-solid fa-rotate-left me-1"></i> إعادة تعيين
                    </a>
                    <button type="submit" class="btn btn-filter-submit w-50" style="border-radius:10px; font-family:'Cairo'; font-size:0.88rem; padding: 0.6rem 0;">
                        <i class="fa-solid fa-filter me-1"></i> تصفية
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Buttons (Quick filter) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="fw-bold me-2 text-muted"><i class="fa-solid fa-filter me-1"></i> تصفية سريعة حسب القرار:</span>
                <a href="/dashboard/candidates?status=all&search={{ $filters['search'] ?? '' }}&wilaya_id={{ $filters['wilaya_id'] ?? '' }}&etablissement_id={{ $filters['etablissement_id'] ?? '' }}&mode_id={{ $filters['mode_id'] ?? '' }}&offre_id={{ $filters['offre_id'] ?? '' }}" class="btn btn-sm px-3 <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-light' ?>" style="border-radius: 20px;">الكل / Tous</a>
                <a href="/dashboard/candidates?status=pending&search={{ $filters['search'] ?? '' }}&wilaya_id={{ $filters['wilaya_id'] ?? '' }}&etablissement_id={{ $filters['etablissement_id'] ?? '' }}&mode_id={{ $filters['mode_id'] ?? '' }}&offre_id={{ $filters['offre_id'] ?? '' }}" class="btn btn-sm px-3 <?= $statusFilter === 'pending' ? 'btn-warning text-white' : 'btn-light' ?>" style="border-radius: 20px;">في الانتظار / En attente</a>
                <a href="/dashboard/candidates?status=approved&search={{ $filters['search'] ?? '' }}&wilaya_id={{ $filters['wilaya_id'] ?? '' }}&etablissement_id={{ $filters['etablissement_id'] ?? '' }}&mode_id={{ $filters['mode_id'] ?? '' }}&offre_id={{ $filters['offre_id'] ?? '' }}" class="btn btn-sm px-3 <?= $statusFilter === 'approved' ? 'btn-success' : 'btn-light' ?>" style="border-radius: 20px;">مقبول / Accepté</a>
                <a href="/dashboard/candidates?status=rejected&search={{ $filters['search'] ?? '' }}&wilaya_id={{ $filters['wilaya_id'] ?? '' }}&etablissement_id={{ $filters['etablissement_id'] ?? '' }}&mode_id={{ $filters['mode_id'] ?? '' }}&offre_id={{ $filters['offre_id'] ?? '' }}" class="btn btn-sm px-3 <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-light' ?>" style="border-radius: 20px;">غير مقبول / Refusé</a>
            </div>
        </div>
    </div>

    <!-- Candidate List Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-user-graduate text-muted me-2"></i> قائمة طلبات المترشحين الجدد</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('candTable', 'candidats.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('candTable', 'candidats.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-right" id="candTable">
                    <thead class="table-light">
                        <tr class="fw-bold text-muted" style="font-size: 0.9rem;">
                            <th class="py-3 px-4">#</th>
                            <th>رقم التسجيل / NIN</th>
                            <th>الاسم الكامل / Nom Complet</th>
                            <th>معلومات الاتصال والميلاد</th>
                            <th>المؤسسة المستقبلة</th>
                            <th>الفرع التكويني / Spécialité</th>
                            <th>صيغة التكوين</th>
                            <th>الحالة والقرار</th>
                            <th class="text-center no-print no-export">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidates)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox d-block mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                    لا توجد طلبات مسجلة تطابق التصفية الحالية
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $index => $c): ?>
                                <tr>
                                    <td class="py-3 px-4 fw-bold text-muted"><?= $index + 1 ?></td>
                                    <td>
                                        <span class="d-block fw-bold text-dark"><?= htmlspecialchars($c['numero_inscription']) ?></span>
                                        <small class="text-muted" style="font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($c['nin']) ?></small>
                                    </td>
                                    <td>
                                        <span class="d-block fw-bold text-primary"><?= htmlspecialchars($c['nom_ar'] . ' ' . $c['prenom_ar']) ?></span>
                                        <small class="text-muted"><?= htmlspecialchars($c['prenom_fr'] . ' ' . $c['nom_fr']) ?></small>
                                    </td>
                                    <td>
                                        <!-- معلومات الاتصال والميلاد -->
                                        <?php if (!empty($c['telephone'])): ?>
                                            <span class="d-block small text-dark"><i class="fa-solid fa-phone text-muted me-1"></i> <?= htmlspecialchars($c['telephone']) ?></span>
                                        <?php endif; ?>
                                        <small class="text-muted d-block" style="font-size: 0.8rem;">
                                            <i class="fa-solid fa-cake-candles me-1"></i>
                                            <?= !empty($c['date_naissance']) ? date('d/m/Y', strtotime($c['date_naissance'])) : '-' ?>
                                            <?= !empty($c['lieu_naissance']) ? ' بـ ' . htmlspecialchars($c['lieu_naissance']) : '' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <!-- المؤسسة المستقبلة -->
                                        <span class="d-block fw-semibold text-secondary" style="font-size: 0.85rem;"><i class="fa-solid fa-building-user text-muted me-1"></i> <?= htmlspecialchars($c['etab_nom'] ?? '-') ?></span>
                                        <?php if (!empty($c['session_nom'])): ?>
                                            <small class="text-muted d-block" style="font-size: 0.78rem;"><i class="fa-solid fa-calendar-days text-muted me-1"></i> <?= htmlspecialchars($c['session_nom']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark px-3 py-2 fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($c['specialite_ar']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-semibold" style="font-size: 0.9rem;">
                                            <?= $c['mode_formation'] === 'apprentissage' ? 'تمهين / Apprentissage' : 'حضوري / Présentiel' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (empty($c['decision']) || $c['decision'] === 'قيد الانتظار'): ?>
                                            <span class="badge bg-warning-subtle text-warning px-3 py-2" style="font-size: 0.85rem;"><i class="fa-solid fa-spinner fa-spin me-1"></i> في الانتظار</span>
                                        <?php elseif ($c['decision'] === 'مقبول'): ?>
                                            <span class="badge bg-success-subtle text-success px-3 py-2" style="font-size: 0.85rem;"><i class="fa-solid fa-circle-check me-1"></i> مقبول / Accepté</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-3 py-2" style="font-size: 0.85rem;" title="<?= htmlspecialchars($c['motif_refus'] ?? '') ?>"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض / Refusé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center no-print no-export">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-primary px-2" style="border-radius: 6px;" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#actionModal" 
                                                    data-id="<?= $c['pre_inscr_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($c['nom_ar'] . ' ' . $c['prenom_ar']) ?>" 
                                                    data-nin="<?= htmlspecialchars($c['nin']) ?>"
                                                    data-specialite="<?= htmlspecialchars($c['specialite_ar']) ?>"
                                                    data-statut="<?= htmlspecialchars($c['decision'] ?? '') ?>"
                                                    data-motif="<?= htmlspecialchars($c['motif_refus'] ?? '') ?>"
                                                    title="دراسة الملف واتخاذ القرار">
                                                <i class="fa-solid fa-signature"></i>
                                            </button>
                                            <button onclick="openEditModal(<?= $c['pre_inscr_id'] ?>)" class="btn btn-sm btn-outline-primary px-2" style="border-radius: 6px;" title="تعديل البيانات">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?= $c['pre_inscr_id'] ?>, '<?= htmlspecialchars($c['nom_ar'] . ' ' . $c['prenom_ar']) ?>')" class="btn btn-sm btn-outline-danger px-2" style="border-radius: 6px;" title="حذف المترشح">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Studying and Validating the Candidate -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border:none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="actionModalLabel"><i class="fa-solid fa-file-signature me-2"></i> دراسة ملف التسجيل واتخاذ القرار</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/candidates/action" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="pre_inscr_id" id="modal_pre_inscr_id">
                <div class="modal-body p-4">
                    <!-- Trainee Basic Card -->
                    <div class="card bg-light border-0 mb-4" style="border-radius: 10px;">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <span class="text-muted d-block small">اسم المترشح:</span>
                                    <strong class="text-dark d-block fs-5" id="modal_name"></strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <span class="text-muted d-block small">رقم التعريف الوطني NIN:</span>
                                    <strong class="text-dark d-block" id="modal_nin"></strong>
                                </div>
                                <div class="col-md-12">
                                    <span class="text-muted d-block small">الفرع الدراسي المطلوب:</span>
                                    <strong class="text-primary d-block" id="modal_specialite"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Review -->
                    <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-folder-open text-warning me-1"></i> الملفات والوثائق المرفوعة (معاينة مباشرة):</h6>
                    <div id="candidate_docs_container" class="row g-2 mb-4">
                        <div class="col-12 text-center py-3 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            جاري سحب الملفات المرفقة...
                        </div>
                    </div>

                    <!-- Decision Inputs -->
                    <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-clipboard-check text-success me-1"></i> اتخاذ القرار الإداري:</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">القرار النهائي / Décision Final *</label>
                            <select name="decision" id="modal_decision" class="form-select" required onchange="toggleRejectionField(this.value)">
                                <option value="" disabled selected>-- اختر القرار --</option>
                                <option value="مقبول">مقبول / Accepté</option>
                                <option value="غير مقبول">غير مقبول / Refusé</option>
                            </select>
                        </div>
                    </div>

                    <!-- Rejection Reason -->
                    <div class="row d-none" id="rejection_field">
                        <div class="col-12">
                            <label class="form-label fw-bold">سبب الرفض / Motif de Refus *</label>
                            <textarea name="motif_refus" id="modal_motif" class="form-control" placeholder="أدخل سبب الرفض بالتفصيل لمشاركته مع المترشح..." rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3 bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">إلغاء</button>
                    <button type="submit" class="btn btn-primary text-white px-4" style="border-radius: 8px;"><i class="fa-solid fa-check-double me-1"></i> حفظ وتحديث القرار</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ ADD CANDIDATE MODAL ══ --}}
<div class="modal fade" id="addCandidateModal" tabindex="-1" aria-labelledby="addCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="addCandidateModalLabel"><i class="fa-solid fa-user-plus me-2"></i> تسجيل ملف مترشح جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/candidates/store') }}" method="POST">
                <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">عرض التكوين المستهدف *</label>
                            <select name="offre_id" class="form-select" required style="font-size:0.9rem;">
                                <option value="" disabled selected>-- حدد عرض التكوين المطلوب --</option>
                                <?php foreach ($offers as $off): ?>
                                <option value="<?= $off->id ?>"><?= htmlspecialchars($off->spec_ar) ?> | <?= htmlspecialchars($off->etab_ar) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اللقب (بالعربية) *</label>
                            <input type="text" name="nom" class="form-control" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الاسم (بالعربية) *</label>
                            <input type="text" name="prenom" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اللقب (بالفرنسية) *</label>
                            <input type="text" name="nom_fr" class="form-control" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الاسم (بالفرنسية) *</label>
                            <input type="text" name="prenom_fr" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">تاريخ الميلاد *</label>
                            <input type="text" name="date_nais" class="form-control" placeholder="YYYY-MM-DD أو DD/MM/YYYY" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">مكان الميلاد بالعربية</label>
                            <input type="text" name="lieu_nais" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">مكان الميلاد بالفرنسية</label>
                            <input type="text" name="lieu_nais_fr" class="form-control" style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">رقم التعريف الوطني (NIN)</label>
                            <input type="text" name="nin" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">رقم التسجيل الأولي *</label>
                            <input type="text" name="num_ins" class="form-control" placeholder="INS-2026-0001" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الجنس *</label>
                            <select name="civ" class="form-select" required style="font-size:0.9rem;">
                                <option value="1">ذكر</option>
                                <option value="2">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">رقم الهاتف</label>
                            <input type="text" name="tel" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" style="font-size:0.9rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3" style="border-bottom-left-radius:18px; border-bottom-right-radius:18px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold btn-sm rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold btn-sm rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> تسجيل المترشح</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ EDIT CANDIDATE MODAL ══ --}}
<div class="modal fade" id="editCandidateModal" tabindex="-1" aria-labelledby="editCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-dark text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="editCandidateModalLabel"><i class="fa-solid fa-user-pen me-2"></i> تعديل بيانات ملف المترشح</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/candidates/update') }}" method="POST">
                <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4" id="editModalBody">
                    {{-- Spinner --}}
                    <div class="text-center py-5" id="editSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p class="text-muted mt-2">جاري استرجاع بيانات المترشح...</p>
                    </div>

                    {{-- Form Fields --}}
                    <div class="row g-3 d-none" id="editFormFields">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اللقب (بالعربية) *</label>
                            <input type="text" name="nom" id="edit_nom" class="form-control" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الاسم (بالعربية) *</label>
                            <input type="text" name="prenom" id="edit_prenom" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اللقب (بالفرنسية) *</label>
                            <input type="text" name="nom_fr" id="edit_nom_fr" class="form-control" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الاسم (بالفرنسية) *</label>
                            <input type="text" name="prenom_fr" id="edit_prenom_fr" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">تاريخ الميلاد *</label>
                            <input type="text" name="date_nais" id="edit_date_nais" class="form-control" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">مكان الميلاد بالعربية</label>
                            <input type="text" name="lieu_nais" id="edit_lieu_nais" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">مكان الميلاد بالفرنسية</label>
                            <input type="text" name="lieu_nais_fr" id="edit_lieu_nais_fr" class="form-control" style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">رقم التعريف الوطني (NIN)</label>
                            <input type="text" name="nin" id="edit_nin" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">رقم التسجيل الأولي *</label>
                            <input type="text" name="num_ins" id="edit_num_ins" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الجنس *</label>
                            <select name="civ" id="edit_civ" class="form-select" required style="font-size:0.9rem;">
                                <option value="1">ذكر</option>
                                <option value="2">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">رقم الهاتف</label>
                            <input type="text" name="tel" id="edit_tel" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">البريد الإلكتروني</label>
                            <input type="email" name="email" id="edit_email" class="form-control" style="font-size:0.9rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3" style="border-bottom-left-radius:18px; border-bottom-right-radius:18px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold btn-sm rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-dark px-4 fw-bold btn-sm rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ DELETE FORM (HIDDEN) ══ --}}
<form id="deleteForm" action="" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">
</form>

<script>
// Logic to populate the Action validation modal
const actionModal = document.getElementById('actionModal');
if (actionModal) {
    actionModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        const nin = btn.getAttribute('data-nin');
        const specialite = btn.getAttribute('data-specialite');
        const statut = btn.getAttribute('data-statut');
        const motif = btn.getAttribute('data-motif');

        // Populate fields
        document.getElementById('modal_pre_inscr_id').value = id;
        document.getElementById('modal_name').textContent = name;
        document.getElementById('modal_nin').textContent = nin;
        document.getElementById('modal_specialite').textContent = specialite;
        document.getElementById('modal_decision').value = (statut === 'مقبول' ? 'مقبول' : (statut === 'غير مقبول' ? 'غير مقبول' : ''));
        document.getElementById('modal_motif').value = motif;

        toggleRejectionField(document.getElementById('modal_decision').value);

        // Fetch documents
        const container = document.getElementById('candidate_docs_container');
        container.innerHTML = `
            <div class="col-12 text-center py-3 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                جاري سحب الملفات المرفقة...
            </div>
        `;

        fetch('{{ url("dashboard/candidates/show") }}/' + id)
            .then(res => res.json())
            .then(response => {
                if (!response.success || !response.data) {
                    container.innerHTML = `<div class="col-12"><div class="alert alert-warning py-2 mb-0 small"><i class="fa-solid fa-info-circle me-1"></i> لا تتوفر مستندات رقمية مرفوعة لهذا المترشح.</div></div>`;
                    return;
                }

                const data = response.data;
                
                // Helper to resolve paths and build preview block
                function getMediaHtml(path, label) {
                    if (!path) return '';
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
                        return `<div class="col-md-6 mb-2">
                            <div class="p-2 border rounded bg-white text-center h-100">
                                <strong class="d-block small text-muted mb-1">${label}</strong>
                                <img src="${resolved}" class="img-thumbnail img-fluid rounded" style="max-height: 120px; cursor:pointer;" onclick="window.open('${resolved}')">
                            </div>
                        </div>`;
                    }
                    
                    if (isPdf) {
                        return `<div class="col-md-12 mb-2">
                            <div class="p-2 border rounded bg-white h-100">
                                <strong class="d-block small text-muted mb-1">${label}</strong>
                                <iframe src="${resolved}" style="width:100%; height:200px;" border="0"></iframe>
                            </div>
                        </div>`;
                    }
                    
                    return `<div class="col-md-6 mb-2">
                        <div class="p-2 border rounded bg-white text-center h-100">
                            <strong class="d-block small text-muted mb-1">${label}</strong>
                            <a href="${resolved}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2 mt-2 rounded">
                                <i class="fa-solid fa-download me-1"></i> تحميل/معاينة
                            </a>
                        </div>
                    </div>`;
                }

                let html = '';
                html += getMediaHtml(data.photo_path || data.photo, 'الصورة الشخصية / Photo');
                html += getMediaHtml(data.certscol_path, 'الشهادة المدرسية / Scolarité');
                html += getMediaHtml(data.actnaispdf_path, 'عقد الميلاد / Acte de Naissance');
                html += getMediaHtml(data.diplomecert_path, 'شهادة المؤهل / Diplôme');
                html += getMediaHtml(data.contratpdf_path, 'عقد التمهين / Contrat');
                
                if (html === '') {
                    container.innerHTML = `<div class="col-12"><div class="alert alert-warning py-2 mb-0 small"><i class="fa-solid fa-info-circle me-1"></i> لم يتم رفع أي مستندات رقمية لهذا المترشح.</div></div>`;
                } else {
                    container.innerHTML = html;
                }
            })
            .catch(err => {
                container.innerHTML = `<div class="col-12"><div class="alert alert-danger py-2 mb-0 small">حدث خطأ في تحميل ملفات المترشح: ${err.message}</div></div>`;
            });
    });
}

function toggleRejectionField(val) {
    const field = document.getElementById('rejection_field');
    const textarea = document.getElementById('modal_motif');
    if (val === 'غير مقبول') {
        field.classList.remove('d-none');
        textarea.setAttribute('required', 'required');
    } else {
        field.classList.add('d-none');
        textarea.removeAttribute('required');
    }
}

function openEditModal(id) {
    var myModal = new bootstrap.Modal(document.getElementById('editCandidateModal'));
    myModal.show();

    document.getElementById('editSpinner').classList.remove('d-none');
    document.getElementById('editFormFields').classList.add('d-none');

    fetch('{{ url("dashboard/candidates/show") }}/' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                document.getElementById('edit_id').value = res.data.IDCandidat;
                document.getElementById('edit_nom').value = res.data.Nom;
                document.getElementById('edit_nom_fr').value = res.data.NomFr;
                document.getElementById('edit_prenom').value = res.data.Prenom;
                document.getElementById('edit_prenom_fr').value = res.data.PrenomFr;
                document.getElementById('edit_date_nais').value = res.data.DateNais;
                document.getElementById('edit_lieu_nais').value = res.data.LieuNais;
                document.getElementById('edit_lieu_nais_fr').value = res.data.LieuNaisFr;
                document.getElementById('edit_num_ins').value = res.data.NumIns;
                document.getElementById('edit_nin').value = res.data.Nin;
                document.getElementById('edit_civ').value = res.data.Civ;
                document.getElementById('edit_tel').value = res.data.Tel;
                document.getElementById('edit_email').value = res.data.email;

                document.getElementById('editSpinner').classList.add('d-none');
                document.getElementById('editFormFields').classList.remove('d-none');
            } else {
                alert('خطأ في جلب البيانات: ' + res.message);
            }
        })
        .catch(err => {
            alert('حدث خطأ غير متوقع أثناء الاتصال بالخادم.');
        });
}

function confirmDelete(id, name) {
    if (confirm("هل أنت متأكد من حذف ملف المترشح " + name + " نهائياً من سجلات النظام؟")) {
        var form = document.getElementById('deleteForm');
        form.action = '{{ url("dashboard/candidates/delete") }}/' + id;
        form.submit();
    }
}
</script>
@endsection
