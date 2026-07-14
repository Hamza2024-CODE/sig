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
                            <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($sec['nom_ar']) ?></div>
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
                                        data-date-fin="<?= !empty($off->date_fin) ? substr($off->date_fin, 0, 10) : '' ?>">
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
                            <input type="number" name="duree" class="form-control" placeholder="24" required style="font-size:0.9rem;" step="0.5">
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

{{-- ══ DELETE FORM (HIDDEN) ══ --}}
<form id="deleteForm" action="" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<script>
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
}
</script>
@endsection
