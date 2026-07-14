@extends('layouts.main')
@section('title', $title ?? 'الأقسام والشعب التكوينية')
@section('content')
<?php
/**
 * @var array  $sections
 * @var int    $total_count
 * @var int    $page
 * @var int    $total_pages
 * @var int    $per_page
 * @var string $search
 * @var int    $filter_session
 * @var int    $filter_etab
 * @var array  $wilayas
 * @var array  $etablissements
 * @var array  $offers
 * @var array  $sessions
 * @var array  $trainers
 * @var string $role_code
 */
$from = $total_count > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total_count);
?>

<div class="animate__animated animate__fadeIn" style="font-family:'Cairo', sans-serif;">

    {{-- ══ HEADER ══ --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 border-bottom pb-3">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--text-main);">
                <i class="fa-solid fa-users-rectangle text-primary me-2"></i>
                الأقسام والشعب التكوينية / Sections de Formation
            </h4>
            <p class="text-muted small mb-0">
                عرض <?= number_format($from) ?>–<?= number_format($to) ?> من إجمالي
                <strong class="text-primary"><?= number_format($total_count) ?></strong> قسم مفتوح
            </p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-primary rounded-pill px-4 fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                <i class="fa-solid fa-folder-plus me-1"></i> فتح قسم جديد
            </button>
            @if(\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') === '1')
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 fw-bold btn-sm">
                <i class="fa-solid fa-print me-1"></i> طباعة
            </button>
            <button onclick="exportTableToExcel('sectionsTable','sections.xls')"
                    class="btn btn-outline-success rounded-pill px-3 fw-bold btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Excel
            </button>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if (session()->has('flash_success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius:12px;">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('flash_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('flash_error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius:12px;">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('flash_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ══ FILTER BAR ══ --}}
    <div class="card border-0 shadow-sm p-3 mb-4 no-print" style="border-radius:16px;background:var(--card-bg);border:1px solid var(--card-border)!important;">
        <form method="GET" action="{{ url('dashboard/sections') }}" class="row g-2 align-items-end">

            {{-- بحث نصي --}}
            <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">بحث باسم القسم أو التخصص</label>
                <div class="input-group">
                    <span class="input-group-text border-0" style="background:var(--input-bg,#f8f9fa);">
                        <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           class="form-control border-0 rounded-end" placeholder="اسم القسم، تخصص..."
                           style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                </div>
            </div>

            {{-- فلتر المؤسسة --}}
            <?php if (count($etablissements) > 1): ?>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية</label>
                <select name="filter_etab" class="form-select border-0 rounded"
                        style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                    <option value="0">كل المؤسسات</option>
                    <?php foreach ($etablissements as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $filter_etab == $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nom_ar'] ?? $e['nom'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            {{-- فلتر الدورة التكوينية --}}
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">الدورة التكوينية</label>
                <select name="filter_session" class="form-select border-0 rounded"
                        style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                    <option value="0">كل الدورات</option>
                    <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s->id ?>" <?= $filter_session == $s->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s->nom_ar) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="font-size:0.88rem;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ url('dashboard/sections') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold"
                   style="font-size:0.88rem;">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- ══ TABLE ══ --}}
    <div class="card border-0 shadow-sm" style="border-radius:18px;background:var(--card-bg);border:1px solid var(--card-border)!important;">
        <div class="table-responsive">
            <table class="table align-middle mb-0 small" id="sectionsTable" style="text-align:right;">
                <thead style="background:rgba(0,0,0,0.03);">
                    <tr class="text-muted fw-bold" style="font-size:0.78rem;">
                        <th class="py-3 ps-4">#</th>
                        <th>اسم القسم بالعربية/الفرنسية</th>
                        <th>التخصص والشعبة</th>
                        <th>المؤسسة والدورة</th>
                        <th class="text-center">الأستاذ المنسق/المؤطر</th>
                        <th class="text-center">المدة (أشهر)</th>
                        <th class="text-center">عدد المجموعات</th>
                        <th class="text-center no-print">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sections)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 d-block mb-3 opacity-25"></i>
                            لا توجد نتائج مطابقة للبحث.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($sections as $idx => $sec):
                        $rowNum = ($page - 1) * $per_page + $idx + 1;
                        $trainerName = !empty($sec['enc_nom']) ? trim($sec['enc_prenom'] . ' ' . $sec['enc_nom']) : 'لم يحدد بعد';
                    ?>
                    <tr style="border-bottom:1px solid var(--card-border,#f1f5f9);transition:background 0.15s;"
                        onmouseover="this.style.background='rgba(0,0,0,0.02)'"
                        onmouseout="this.style.background=''">
                        <td class="py-2 ps-4 text-muted" style="font-family:'Inter';font-size:0.75rem;"><?= $rowNum ?></td>
                        <td>
                            <a href="javascript:void(0)" class="fw-bold text-primary show-trainees-btn" style="font-size:0.85rem;text-decoration:none;" data-id="<?= $sec['id'] ?>">
                                <i class="fa-solid fa-list-check me-1"></i> <?= htmlspecialchars($sec['nom_ar']) ?>
                            </a>
                            <div class="text-muted" style="font-size:0.72rem;font-family:'Outfit';"><?= htmlspecialchars($sec['nom_fr']) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark" style="font-size:0.8rem;"><?= htmlspecialchars($sec['spec_ar'] ?? '—') ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark" style="font-size:0.8rem;"><?= htmlspecialchars($sec['etab_ar'] ?? '—') ?></div>
                            <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($sec['session_nom'] ?? '—') ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1.5" style="font-size:0.75rem;">
                                <i class="fa-solid fa-user-tie text-primary me-1"></i> <?= htmlspecialchars($trainerName) ?>
                            </span>
                        </td>
                        <td class="text-center fw-bold" style="font-family:'Outfit';"><?= $sec['duree'] ?? '—' ?> شهر</td>
                        <td class="text-center" style="font-family:'Outfit';"><?= $sec['groupe'] ?? '1' ?> أفوَاج</td>
                        <td class="text-center no-print">
                            <div class="d-flex justify-content-center gap-1">
                                <button onclick="triggerPrintFromRow(<?= $sec['id'] ?>)" class="btn btn-sm btn-outline-success px-2" style="border-radius:6px;" title="طباعة محضر فتح الفرع">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                                <button onclick="openEditModal(<?= $sec['id'] ?>)" class="btn btn-sm btn-outline-primary px-2" style="border-radius:6px;" title="تعديل">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $sec['id'] ?>, '<?= htmlspecialchars($sec['nom_ar']) ?>')" class="btn btn-sm btn-outline-danger px-2" style="border-radius:6px;" title="حذف">
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

        {{-- ══ PAGINATION ══ --}}
        <?php if ($total_pages > 1): ?>
        <div class="p-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-top no-print"
             style="border-color:var(--card-border)!important;">
            <small class="text-muted">
                عرض <strong><?= $from ?></strong>–<strong><?= $to ?></strong>
                من <strong><?= number_format($total_count) ?></strong> قسم
                (صفحة <?= $page ?> / <?= $total_pages ?>)
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0 gap-1">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_session=<?= $filter_session ?>&filter_etab=<?= $filter_etab ?>">
                            <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($total_pages, $page + 2);
                    if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=1&search=<?= urlencode($search) ?>&filter_session=<?= $filter_session ?>&filter_etab=<?= $filter_etab ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&filter_session=<?= $filter_session ?>&filter_etab=<?= $filter_etab ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&filter_session=<?= $filter_session ?>&filter_etab=<?= $filter_etab ?>">
                            <?= $total_pages ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_session=<?= $filter_session ?>&filter_etab=<?= $filter_etab ?>">
                            <i class="fa-solid fa-chevron-left" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

</div>

{{-- ══ ADD MODAL ══ --}}
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="addSectionModalLabel"><i class="fa-solid fa-folder-plus me-2"></i> فتح قسم بيداغوجي دراسي جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/sections/store') }}" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">اربط القسم بعرض تكوين نشط ومعتمد *</label>
                            <input type="text" id="offerSearchInput" class="form-control mb-2 rounded-pill px-3 shadow-sm" placeholder="🔍 ابحث برمز العرض (رمز التكوين) أو اسم التخصص..." onkeyup="filterOffersList(this.value)" style="font-size:0.85rem;">
                            <select name="offre_id" class="form-select" required style="font-size:0.9rem;" onchange="onOfferSelectChange(this)">
                                <option value="" disabled selected>-- حدد عرض تكوين --</option>
                                <?php foreach ($offers as $off): ?>
                                <option value="<?= $off->id ?>"
                                        data-spec-ar="<?= htmlspecialchars($off->spec_ar) ?>"
                                        data-spec-fr="<?= htmlspecialchars($off->spec_fr ?? '') ?>"
                                        data-date-debut="<?= !empty($off->date_debut) ? substr($off->date_debut, 0, 10) : '' ?>"
                                        data-date-fin="<?= !empty($off->date_fin) ? substr($off->date_fin, 0, 10) : '' ?>"
                                        data-duree="<?= (int)($off->duree ?? 24) ?>">
                                    OFF-<?= $off->id ?> | <?= htmlspecialchars($off->spec_ar) ?> | <?= htmlspecialchars($off->etab_ar) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="text-muted small d-block mt-1">بمجرد اختيار العرض، سيقوم النظام تلقائياً بربط القسم بالتخصص، الدورة، والمؤسسة المقابلة.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اسم القسم بالعربية *</label>
                            <input type="text" name="nom_ar" id="add_nom_ar" class="form-control" placeholder="مثال: قسم صيانة السيارات 1" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اسم القسم بالفرنسية *</label>
                            <input type="text" name="nom_fr" id="add_nom_fr" class="form-control" placeholder="Mecanique Auto Section 1" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">تاريخ انطلاق الدراسة للقسم *</label>
                            <input type="date" name="date_debut" id="add_date_debut" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">تاريخ انتهاء الدراسة والتخرج المتوقع *</label>
                            <input type="date" name="date_fin" id="add_date_fin" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">مدة التكوين بالقسم (أشهر) *</label>
                            <input type="number" name="duree" id="add_duree" class="form-control" placeholder="24" required style="font-size:0.9rem;" step="0.5">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">عدد المجموعات / الأفواج الإجمالية *</label>
                            <input type="number" name="groupe" id="add_groupe" class="form-control" min="1" max="50" value="1" required style="font-size:0.9rem;">
                            <span class="text-muted small d-block mt-1">القيمة المتاحة من 1 إلى 50 فوج</span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الأستاذ المشرف / المنسق</label>
                            <select name="encadrement_id" class="form-select" style="font-size:0.9rem;">
                                <option value="">-- لم يحدد بعد --</option>
                                <?php foreach ($trainers as $t): ?>
                                <option value="<?= $t->id ?>"><?= htmlspecialchars($t->prenom . ' ' . $t->nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3" style="border-bottom-left-radius:18px; border-bottom-right-radius:18px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold btn-sm rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold btn-sm rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> فتح وحفظ القسم</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-dark text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="editSectionModalLabel"><i class="fa-solid fa-pen-to-square me-2"></i> تعديل بيانات القسم البيداغوجي</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/sections/update') }}" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4" id="editModalBody">
                    {{-- Spinner --}}
                    <div class="text-center py-5" id="editSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p class="text-muted mt-2">جاري استرجاع بيانات القسم...</p>
                    </div>

                    {{-- Form Fields (Hidden by default) --}}
                    <div class="row g-3 d-none" id="editFormFields">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اسم القسم بالعربية *</label>
                            <input type="text" name="nom_ar" id="edit_nom_ar" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اسم القسم بالفرنسية *</label>
                            <input type="text" name="nom_fr" id="edit_nom_fr" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">تاريخ انطلاق الدراسة للقسم *</label>
                            <input type="date" name="date_debut" id="edit_date_debut" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">تاريخ التخرج المتوقع *</label>
                            <input type="date" name="date_fin" id="edit_date_fin" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">مدة التكوين بالقسم (أشهر) *</label>
                            <input type="number" name="duree" id="edit_duree" class="form-control" required style="font-size:0.9rem;" step="0.5">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">عدد المجموعات *</label>
                            <input type="number" name="groupe" id="edit_groupe" class="form-control" min="1" max="50" required style="font-size:0.9rem;">
                            <span class="text-muted small d-block mt-1">القيمة المتاحة من 1 إلى 50 فوج</span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الأستاذ المشرف / المنسق</label>
                            <select name="encadrement_id" id="edit_encadrement_id" class="form-select" style="font-size:0.9rem;">
                                <option value="0">-- لم يحدد بعد --</option>
                                <?php foreach ($trainers as $t): ?>
                                <option value="<?= $t->id ?>"><?= htmlspecialchars($t->prenom . ' ' . $t->nom) ?></option>
                                <?php endforeach; ?>
                            </select>
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

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printable_branch_report, #printable_branch_report * {
        visibility: visible !important;
    }
    #printable_branch_report {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        direction: rtl !important;
        text-align: right !important;
        font-family: 'Cairo', sans-serif !important;
        font-size: 13px !important;
        line-height: 1.6 !important;
        color: #000 !important;
        background: #fff !important;
    }
    .print-page {
        page-break-after: always;
        padding: 40px;
        box-sizing: border-box;
    }
    .print-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .print-header h5 {
        margin: 5px 0;
        font-weight: bold;
    }
    .print-title {
        text-align: center;
        margin: 40px 0;
    }
    .print-title h2 {
        font-weight: bold;
        text-decoration: underline;
        font-size: 26px;
    }
    .print-info-table {
        width: 100%;
        margin-bottom: 30px;
        border-collapse: collapse;
    }
    .print-info-table td {
        padding: 10px 15px;
        font-size: 15px;
        vertical-align: top;
    }
    .print-signatures {
        width: 100%;
        margin-top: 50px;
    }
    .print-signatures td {
        text-align: center;
        width: 50%;
        font-weight: bold;
        font-size: 15px;
    }
    .print-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .print-table th, .print-table td {
        border: 1px solid #000 !important;
        padding: 6px 8px !important;
        font-size: 11px !important;
        text-align: center !important;
    }
    .print-table th {
        background-color: #f2f2f2 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<!-- Printable Hidden Report -->
<div id="printable_branch_report" class="d-none">
    <!-- Page 1: Administrative opening report -->
    <div class="print-page">
        <div class="print-header">
            <h5>الجمهورية الجزائرية الديمقراطية الشعبية</h5>
            <h5>وزارة التكوين والتعليم المهنيين</h5>
            <div style="display: flex; justify-content: space-between; margin-top: 20px; text-align: right; width: 100%;">
                <div style="font-weight: bold;">
                    <div>ولاية: <span id="print_out_wilaya">...</span></div>
                    <div>مديرية التكوين والتعليم المهنيين لولاية: <span id="print_out_dfep">...</span></div>
                    <div>المؤسسة التكوينية: <span id="print_out_etab">...</span></div>
                </div>
            </div>
        </div>

        <div class="print-title">
            <h2>محضر فتح الفرع</h2>
            <h4 style="margin-top: 15px; font-weight: bold;">الرقم: <span id="print_out_num">1</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; التاريخ: <span id="print_out_date">...</span></h4>
        </div>

        <div style="margin-top: 40px;">
            <table class="print-info-table">
                <tr>
                    <td style="width: 50%;"><strong>الاختصاص:</strong> <span id="print_out_spec">...</span></td>
                    <td style="width: 50%;"><strong>رمز الاختصاص:</strong> <span id="print_out_spec_code">...</span></td>
                </tr>
                <tr>
                    <td><strong>الفوج:</strong> <span id="print_out_foug">...</span></td>
                    <td><strong>مستوى التأهيل:</strong> <span id="print_out_level">...</span></td>
                </tr>
                <tr>
                    <td><strong>نمط تنظيم التكوين:</strong> <span id="print_out_org_mode">...</span></td>
                    <td><strong>نمط التسيير:</strong> <span id="print_out_mgmt_mode">...</span></td>
                </tr>
                <tr style="height: 20px;"><td></td><td></td></tr>
                <tr>
                    <td><strong>بداية التكوين:</strong> <span id="print_out_date_debut">...</span></td>
                    <td><strong>نهاية التكوين:</strong> <span id="print_out_date_fin">...</span></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <strong>عدد المتكونين:</strong> <span id="print_out_count_total">...</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <strong>منهم إناث:</strong> <span id="print_out_count_females">...</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <strong>منهم أجانب:</strong> <span id="print_out_count_foreigners">...</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <strong>منهم احتياجات خاصة:</strong> <span id="print_out_count_special">...</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><strong>المسؤول عن الفرع:</strong> <span id="print_out_responsable">...</span></td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 80px;">
            <table class="print-signatures">
                <tr>
                    <td>المسؤول البيداغوجي</td>
                    <td>مدير المؤسسة</td>
                </tr>
                <tr style="height: 120px;"><td></td><td></td></tr>
                <tr>
                    <td colspan="2" style="text-align: center; font-weight: bold; font-size: 16px;">تأشيرة المديرية</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Page 2: Trainees list -->
    <div class="print-page">
        <div style="text-align: center; margin-bottom: 20px;">
            <h4 style="font-weight: bold; text-decoration: underline;">قائمة المتربصين والمتمهنين المقيدين بالقسم</h4>
            <div style="font-size: 14px; margin-top: 10px; font-weight: bold;">
                الاختصاص: <span class="print_out_spec_linked">...</span> &nbsp;&nbsp; | &nbsp;&nbsp; الفوج: <span class="print_out_foug_linked">...</span>
            </div>
        </div>

        <table class="print-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>رقم التسجيل</th>
                    <th>الرقم التعريفي الوطني</th>
                    <th>اللقب</th>
                    <th>الاسم</th>
                    <th>تاريخ الميلاد</th>
                    <th>مكان الميلاد</th>
                    <th>المستوى الدراسي</th>
                    <th>العنوان</th>
                    <th>اسم الأب</th>
                    <th>لقب الأم</th>
                    <th>اسم الأم</th>
                    <th class="print-app-col">رقم العقد</th>
                    <th class="print-app-col">تاريخ العقد</th>
                    <th class="print-app-col">المستخدم</th>
                </tr>
            </thead>
            <tbody id="print_trainees_rows">
                <!-- Populated dynamically via JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Trainees List Modal -->
<div class="modal fade" id="viewTraineesModal" tabindex="-1" aria-labelledby="viewTraineesModalLabel" aria-true="true" style="font-family:'Cairo', sans-serif;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4 bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;">
                <h5 class="modal-title fw-bold" id="viewTraineesModalLabel"><i class="fa-solid fa-users me-2"></i> قائمة المتربصين المقيدين بالقسم بيداغوجياً</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Meta Info Alert -->
                <div class="alert alert-info border-0 shadow-sm mb-4 rounded-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="section_meta_info" style="background-color: #f0f9ff; border: 1px solid #bae6fd !important;">
                    <div class="d-flex flex-wrap gap-3">
                        <div><strong>الاختصاص:</strong> <span id="meta_spec_name" class="text-primary fw-bold">...</span></div>
                        <div><strong>الرمز:</strong> <span id="meta_spec_code" class="text-secondary fw-bold">...</span></div>
                        <div><strong>الفوج:</strong> <span id="meta_section_name" class="text-dark fw-bold">...</span></div>
                        <div><strong>تاريخ التكوين:</strong> <span id="meta_dates" class="text-muted fw-bold">...</span></div>
                        <div><strong>المسؤول عن الفرع:</strong> <span id="meta_trainer" class="text-success fw-bold">...</span></div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btn_trigger_print_options">
                            <i class="fa-solid fa-print me-1"></i> طباعة محضر فتح الفرع
                        </button>
                    </div>
                </div>

                <!-- Spinner -->
                <div id="traineesSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="text-muted mt-2">جاري تحميل قائمة المتربصين والمتمهنين...</p>
                </div>

                <!-- Trainees Table -->
                <div id="traineesTableContainer" class="table-responsive d-none">
                    <table class="table table-hover align-middle mb-0 small" id="modalTraineesTable">
                        <thead class="bg-light text-muted small fw-bold">
                            <tr>
                                <th>#</th>
                                <th>رقم التسجيل</th>
                                <th>الرقم التعريفي الوطني</th>
                                <th>اللقب والاسم</th>
                                <th>تاريخ ومكان الميلاد</th>
                                <th>المستوى الدراسي</th>
                                <th>العنوان</th>
                                <th>بيانات الوالدين</th>
                                <th class="apprenticeship-col d-none">رقم العقد</th>
                                <th class="apprenticeship-col d-none">تاريخ العقد</th>
                                <th class="apprenticeship-col d-none">المستخدم</th>
                            </tr>
                        </thead>
                        <tbody id="trainees_list_body">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light p-3 border-top-0" style="border-bottom-left-radius:18px; border-bottom-right-radius:18px;">
                <button type="button" class="btn btn-outline-secondary px-4 fw-bold btn-sm rounded-pill" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Print Options Modal -->
<div class="modal fade" id="printOptionsModal" tabindex="-1" aria-labelledby="printOptionsModalLabel" aria-hidden="true" style="z-index: 1060; font-family:'Cairo', sans-serif;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="printOptionsModalLabel"><i class="fa-solid fa-print text-success me-2"></i> خيارات طباعة محضر فتح الفرع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="print_num" class="form-label small fw-bold text-muted">رقم المحضر</label>
                    <input type="text" id="print_num" class="form-control rounded-pill border-light-subtle shadow-sm px-3" value="1">
                </div>
                <div class="mb-3">
                    <label for="print_date" class="form-label small fw-bold text-muted">تاريخ المحضر</label>
                    <input type="date" id="print_date" class="form-control rounded-pill border-light-subtle shadow-sm px-3" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label for="print_date_debut" class="form-label small fw-bold text-muted">تاريخ بداية التكوين</label>
                    <input type="date" id="print_date_debut" class="form-control rounded-pill border-light-subtle shadow-sm px-3">
                </div>
                <div class="mb-3">
                    <label for="print_date_fin" class="form-label small fw-bold text-muted">تاريخ نهاية التكوين</label>
                    <input type="date" id="print_date_fin" class="form-control rounded-pill border-light-subtle shadow-sm px-3">
                </div>
                <div class="mb-3">
                    <label for="print_org_mode" class="form-label small fw-bold text-muted">نمط تنظيم التكوين</label>
                    <input type="text" id="print_org_mode" class="form-control rounded-pill border-light-subtle shadow-sm px-3">
                </div>
                <div class="mb-3">
                    <label for="print_mgmt_mode" class="form-label small fw-bold text-muted">نمط التسيير</label>
                    <input type="text" id="print_mgmt_mode" class="form-control rounded-pill border-light-subtle shadow-sm px-3" value="معهد">
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" id="btn_confirm_print">
                    <i class="fa-solid fa-print me-1"></i> معاينة والطباعة
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ DELETE FORM (HIDDEN) ══ --}}
<form id="deleteForm" action="" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<script>
let currentSectionData = null;

document.addEventListener('DOMContentLoaded', function() {
    const viewTraineesModalEl = document.getElementById('viewTraineesModal');
    const printOptionsModalEl = document.getElementById('printOptionsModal');
    
    // Add click listeners to section name links
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('.show-trainees-btn');
        if (target) {
            e.preventDefault();
            const id = target.getAttribute('data-id');
            openTraineesModal(id);
        }
    });

    function openTraineesModal(id) {
        const viewTraineesModal = new bootstrap.Modal(viewTraineesModalEl);
        viewTraineesModal.show();

        document.getElementById('traineesSpinner').classList.remove('d-none');
        document.getElementById('traineesTableContainer').classList.add('d-none');

        fetch('{{ url("dashboard/sections/trainees") }}/' + id)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    currentSectionData = res;
                    populateTraineesModal(res);
                } else {
                    alert('خطأ في جلب المتربصين: ' + res.message);
                }
            })
            .catch(err => {
                alert('حدث خطأ أثناء تحميل قائمة المتربصين.');
            });
    }

    function populateTraineesModal(data) {
        const sec = data.section;
        const trainees = data.trainees;

        // Meta info
        document.getElementById('meta_spec_name').textContent = sec.spec_ar || '—';
        document.getElementById('meta_spec_code').textContent = sec.spec_code || '—';
        document.getElementById('meta_section_name').textContent = sec.nom_ar || '—';
        document.getElementById('meta_dates').textContent = 'من ' + sec.date_debut + ' إلى ' + sec.date_fin;
        document.getElementById('meta_trainer').textContent = sec.responsable || 'لم يحدد بعد';

        // Trainees list
        const tbody = document.getElementById('trainees_list_body');
        tbody.innerHTML = '';

        const isApprenticeship = (parseInt(sec.IDMode_formation) === 10);
        
        // Show/hide apprenticeship columns
        const appCols = document.querySelectorAll('.apprenticeship-col');
        appCols.forEach(col => {
            if (isApprenticeship) {
                col.classList.remove('d-none');
            } else {
                col.classList.add('d-none');
            }
        });

        if (trainees.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${isApprenticeship ? 11 : 8}" class="text-center py-4 text-muted">لا يوجد متربصون مقيدون في هذا القسم حالياً.</td></tr>`;
        } else {
            trainees.forEach((tr, index) => {
                const trRow = document.createElement('tr');
                trRow.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${escapeHtml(tr.nccp)}</td>
                    <td><span style="font-family:'Outfit';">${escapeHtml(tr.nin || '—')}</span></td>
                    <td><strong>${escapeHtml(tr.nom_ar)} ${escapeHtml(tr.prenom_ar)}</strong></td>
                    <td>${escapeHtml(tr.date_naissance)} بـ ${escapeHtml(tr.lieu_naissance)}</td>
                    <td>${escapeHtml(tr.niveau_scolaire || '—')}</td>
                    <td>${escapeHtml(tr.adresse || '—')}</td>
                    <td>
                        <div class="small">الأب: ${escapeHtml(tr.prenom_pere || '—')}</div>
                        <div class="small">الأم: ${escapeHtml(tr.prenom_mere || '—')} ${escapeHtml(tr.nom_mere || '—')}</div>
                    </td>
                    <td class="apprenticeship-col ${isApprenticeship ? '' : 'd-none'}">${escapeHtml(tr.num_contrat || '—')}</td>
                    <td class="apprenticeship-col ${isApprenticeship ? '' : 'd-none'}">${escapeHtml(tr.date_contrat || '—')}</td>
                    <td class="apprenticeship-col ${isApprenticeship ? '' : 'd-none'}">${escapeHtml(tr.nom_employeur || '—')}</td>
                `;
                tbody.appendChild(trRow);
            });
        }

        document.getElementById('traineesSpinner').classList.add('d-none');
        document.getElementById('traineesTableContainer').classList.remove('d-none');
    }

    // Trigger Print Options Modal
    document.getElementById('btn_trigger_print_options').addEventListener('click', function() {
        if (!currentSectionData) return;
        const sec = currentSectionData.section;

        document.getElementById('print_date_debut').value = sec.date_debut || '';
        document.getElementById('print_date_fin').value = sec.date_fin || '';
        document.getElementById('print_org_mode').value = (parseInt(sec.IDMode_formation) === 10) ? 'تكوين عن طريق التمهين' : 'حضوري أولي';
        document.getElementById('print_mgmt_mode').value = (sec.etab_nom && (sec.etab_nom.indexOf('المعهد') !== -1 || sec.etab_nom.indexOf('معهد') !== -1)) ? 'معهد' : 'مركز';

        const printModal = new bootstrap.Modal(printOptionsModalEl);
        printModal.show();
    });

    // Confirm and Print
    document.getElementById('btn_confirm_print').addEventListener('click', function() {
        if (!currentSectionData) return;
        const sec = currentSectionData.section;
        const trainees = currentSectionData.trainees;

        // Fetch inputs from options modal
        const printNum = document.getElementById('print_num').value;
        const printDate = formatDateArabic(document.getElementById('print_date').value);
        const printDateDebut = formatDateArabic(document.getElementById('print_date_debut').value);
        const printDateFin = formatDateArabic(document.getElementById('print_date_fin').value);
        const printOrgMode = document.getElementById('print_org_mode').value;
        const printMgmtMode = document.getElementById('print_mgmt_mode').value;

        // Populate printout elements
        document.getElementById('print_out_wilaya').textContent = sec.wilaya_nom || '—';
        document.getElementById('print_out_dfep').textContent = sec.dfep_nom || '—';
        document.getElementById('print_out_etab').textContent = sec.etab_nom || '—';
        
        document.getElementById('print_out_num').textContent = printNum || '1';
        document.getElementById('print_out_date').textContent = printDate || '—';
        
        document.getElementById('print_out_spec').textContent = sec.spec_ar || '—';
        document.getElementById('print_out_spec_code').textContent = sec.spec_code || '—';
        document.getElementById('print_out_foug').textContent = sec.groupe || '1';
        document.getElementById('print_out_level').textContent = sec.niveau_qualif || '—';
        document.getElementById('print_out_org_mode').textContent = printOrgMode || '—';
        document.getElementById('print_out_mgmt_mode').textContent = printMgmtMode || '—';
        
        document.getElementById('print_out_date_debut').textContent = printDateDebut || '—';
        document.getElementById('print_out_date_fin').textContent = printDateFin || '—';

        // Count stats
        let total = trainees.length;
        let females = 0;
        let foreigners = 0;
        let specialNeeds = 0;

        trainees.forEach(tr => {
            if (tr.sexe == 2 || tr.sexe == 'أنثى' || tr.sexe == 'F') females++;
            if (tr.Nationalite != 0) foreigners++;
            if (tr.endicape == 1) specialNeeds++;
        });

        document.getElementById('print_out_count_total').textContent = total;
        document.getElementById('print_out_count_females').textContent = females;
        document.getElementById('print_out_count_foreigners').textContent = foreigners;
        document.getElementById('print_out_count_special').textContent = specialNeeds;
        
        document.getElementById('print_out_responsable').textContent = sec.responsable || 'لم يحدد بعد';

        // Document link titles
        document.querySelectorAll('.print_out_spec_linked').forEach(el => el.textContent = sec.spec_ar || '—');
        document.querySelectorAll('.print_out_foug_linked').forEach(el => el.textContent = sec.groupe || '1');

        // Populate printed rows
        const printBody = document.getElementById('print_trainees_rows');
        printBody.innerHTML = '';

        const isApprenticeship = (parseInt(sec.IDMode_formation) === 10);
        
        // Show/hide printed apprenticeship columns
        document.querySelectorAll('.print-app-col').forEach(col => {
            if (isApprenticeship) {
                col.style.display = 'table-cell';
            } else {
                col.style.display = 'none';
            }
        });

        trainees.forEach((tr, index) => {
            const trRow = document.createElement('tr');
            trRow.innerHTML = `
                <td>${index + 1}</td>
                <td>${escapeHtml(tr.nccp)}</td>
                <td>${escapeHtml(tr.nin || '—')}</td>
                <td>${escapeHtml(tr.nom_ar)}</td>
                <td>${escapeHtml(tr.prenom_ar)}</td>
                <td>${escapeHtml(tr.date_naissance)}</td>
                <td>${escapeHtml(tr.lieu_naissance)}</td>
                <td>${escapeHtml(tr.niveau_scolaire || '—')}</td>
                <td>${escapeHtml(tr.adresse || '—')}</td>
                <td>${escapeHtml(tr.prenom_pere || '—')}</td>
                <td>${escapeHtml(tr.nom_mere || '—')}</td>
                <td>${escapeHtml(tr.prenom_mere || '—')}</td>
                <td class="print-app-col" style="display: ${isApprenticeship ? 'table-cell' : 'none'}">${escapeHtml(tr.num_contrat || '—')}</td>
                <td class="print-app-col" style="display: ${isApprenticeship ? 'table-cell' : 'none'}">${escapeHtml(tr.date_contrat || '—')}</td>
                <td class="print-app-col" style="display: ${isApprenticeship ? 'table-cell' : 'none'}">${escapeHtml(tr.nom_employeur || '—')}</td>
            `;
            printBody.appendChild(trRow);
        });

        // Hide modals to clean print screen
        const printModalInstance = bootstrap.Modal.getInstance(printOptionsModalEl);
        if (printModalInstance) printModalInstance.hide();

        const traineesModalInstance = bootstrap.Modal.getInstance(viewTraineesModalEl);
        if (traineesModalInstance) traineesModalInstance.hide();

        // Print!
        setTimeout(() => {
            window.print();
        }, 300);
    });

    // Helper functions
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function formatDateArabic(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return parts[0] + '/' + parts[1] + '/' + parts[2];
    }
});

function openEditModal(id) {
    var myModal = new bootstrap.Modal(document.getElementById('editSectionModal'));
    myModal.show();

    document.getElementById('editSpinner').classList.remove('d-none');
    document.getElementById('editFormFields').classList.add('d-none');

    fetch('{{ url("dashboard/sections/show") }}/' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                document.getElementById('edit_id').value = res.data.IDSection;
                document.getElementById('edit_nom_ar').value = res.data.Nom;
                document.getElementById('edit_nom_fr').value = res.data.NomFr;
                document.getElementById('edit_date_debut').value = res.data.DateDF;
                document.getElementById('edit_date_fin').value = res.data.DateFF;
                document.getElementById('edit_duree').value = res.data.Duree;
                document.getElementById('edit_groupe').value = res.data.Groupe;
                document.getElementById('edit_encadrement_id').value = res.data.IDEncadrement;

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
    if (confirm("هل أنت متأكد من حذف القسم البيداغوجي " + name + " نهائياً؟")) {
        var form = document.getElementById('deleteForm');
        form.action = '{{ url("dashboard/sections/delete") }}/' + id;
        form.submit();
    }
}

function filterOffersList(query) {
    query = query.toLowerCase();
    const select = document.querySelector('select[name="offre_id"]');
    const options = select.options;
    for (let i = 0; i < options.length; i++) {
        const option = options[i];
        if (option.value === "") continue;
        const text = option.text.toLowerCase();
        if (text.includes(query)) {
            option.style.display = "";
        } else {
            option.style.display = "none";
        }
    }
}

function onOfferSelectChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    const specAr = selectedOption.getAttribute('data-spec-ar');
    const specFr = selectedOption.getAttribute('data-spec-fr');
    const dateDebut = selectedOption.getAttribute('data-date-debut');
    const dateFin = selectedOption.getAttribute('data-date-fin');
    const duree = selectedOption.getAttribute('data-duree');
    
    if (specAr) {
        document.getElementById('add_nom_ar').value = specAr;
    }
    if (specFr) {
        document.getElementById('add_nom_fr').value = specFr.toUpperCase();
    }
    if (dateDebut) {
        document.getElementById('add_date_debut').value = dateDebut;
    }
    if (dateFin) {
        document.getElementById('add_date_fin').value = dateFin;
    }
    if (duree) {
        document.getElementById('add_duree').value = duree;
    }
}

function triggerPrintFromRow(id) {
    fetch('{{ url("dashboard/sections/trainees") }}/' + id)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                currentSectionData = res;
                const sec = res.section;
                document.getElementById('print_date_debut').value = sec.date_debut || '';
                document.getElementById('print_date_fin').value = sec.date_fin || '';
                document.getElementById('print_org_mode').value = (parseInt(sec.IDMode_formation) === 10) ? 'تكوين عن طريق التمهين' : 'حضوري أولي';
                document.getElementById('print_mgmt_mode').value = (sec.etab_nom && (sec.etab_nom.indexOf('المعهد') !== -1 || sec.etab_nom.indexOf('معهد') !== -1)) ? 'معهد' : 'مركز';

                const printModal = new bootstrap.Modal(document.getElementById('printOptionsModal'));
                printModal.show();
            } else {
                alert('خطأ في جلب بيانات القسم: ' + res.message);
            }
        })
        .catch(err => {
            alert('حدث خطأ أثناء تحميل بيانات الطباعة.');
        });
}
</script>
@endsection
