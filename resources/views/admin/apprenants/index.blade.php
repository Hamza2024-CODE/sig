@extends('layouts.main')
@section('title', $title ?? 'سجل المتربصين والمتمهنين')
@section('content')
<?php
/**
 * @var array  $students
 * @var int    $total_count
 * @var int    $page
 * @var int    $total_pages
 * @var int    $per_page
 * @var string $search
 * @var int    $filter_section
 * @var string $filter_status
 * @var int    $filter_etab
 * @var array  $wilayas
 * @var array  $etablissements
 * @var array  $sections
 * @var array  $availableCandidates
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
                <i class="fa-solid fa-user-graduate text-primary me-2"></i>
                سجل المتربصين والمتمهنين / Registre des Stagiaires
            </h4>
            <p class="text-muted small mb-0">
                عرض <?= number_format($from) ?>–<?= number_format($to) ?> من إجمالي
                <strong class="text-primary"><?= number_format($total_count) ?></strong> متربص ومتمهن نشط
            </p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-primary rounded-pill px-4 fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fa-solid fa-user-plus me-1"></i> تسجيل متربص جديد
            </button>
            @if(\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') === '1')
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 fw-bold btn-sm">
                <i class="fa-solid fa-print me-1"></i> طباعة
            </button>
            <button onclick="exportTableToExcel('studentsTable','stagiaires.xls')"
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
        <form method="GET" action="{{ url('dashboard/apprenants') }}" class="row g-2 align-items-end">

            {{-- بحث نصي --}}
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">بحث باسم المتربص أو رقم التسجيل</label>
                <div class="input-group">
                    <span class="input-group-text border-0" style="background:var(--input-bg,#f8f9fa);">
                        <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           class="form-control border-0 rounded-end" placeholder="اسم، رقم التسجيل، CCP..."
                           style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                </div>
            </div>

            {{-- فلتر المؤسسة (للمسؤولين فقط) --}}
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

            {{-- فلتر القسم --}}
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">القسم الدراسي</label>
                <select name="filter_section" class="form-select border-0 rounded"
                        style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                    <option value="0">كل الأقسام</option>
                    <?php foreach ($sections as $s): ?>
                    <option value="<?= $s->id ?>" <?= $filter_section == $s->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s->nom_ar) ?> (<?= htmlspecialchars($s->spec_ar) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            {{-- فلتر الحالة --}}
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">الحالة الدراسية</label>
                <select name="filter_status" class="form-select border-0 rounded"
                        style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>الكل</option>
                    <option value="actif" <?= $filter_status === 'actif' ? 'selected' : '' ?>>نشط</option>
                    <option value="abondon" <?= $filter_status === 'abondon' ? 'selected' : '' ?>>تخلى</option>
                    <option value="exclu" <?= $filter_status === 'exclu' ? 'selected' : '' ?>>مقصى</option>
                    <option value="diplome" <?= $filter_status === 'diplome' ? 'selected' : '' ?>>متخرج</option>
                </select>
            </div>

            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="font-size:0.88rem;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ url('dashboard/apprenants') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold"
                   style="font-size:0.88rem;">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- ══ TABLE ══ --}}
    <div class="card border-0 shadow-sm" style="border-radius:18px;background:var(--card-bg);border:1px solid var(--card-border)!important;">
        <div class="table-responsive">
            <table class="table align-middle mb-0 small" id="studentsTable" style="text-align:right;">
                <thead style="background:rgba(0,0,0,0.03);">
                    <tr class="text-muted fw-bold" style="font-size:0.78rem;">
                        <th class="py-3 ps-4">#</th>
                        <th>اسم المتربص / المتكون</th>
                        <th>رقم التسجيل / الدخول</th>
                        <th>القسم / التخصص</th>
                        <th>المؤسسة التكوينية</th>
                        <th class="text-center">الدفعة / الفرع</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-center no-print">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 d-block mb-3 opacity-25"></i>
                            لا توجد نتائج مطابقة للبحث.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($students as $idx => $s):
                        $isFemale  = ((int)($s['civ'] ?? 1) === 2);
                        $nomComplet = trim(($s['nom_ar'] ?? '') . ' ' . ($s['prenom_ar'] ?? ''));
                        $nomFr = trim(($s['prenom_fr'] ?? '') . ' ' . ($s['nom_fr'] ?? ''));
                        $initial   = mb_strtoupper(mb_substr($s['nom_ar'] ?? 'م', 0, 1));
                        $rowNum    = ($page - 1) * $per_page + $idx + 1;
                        
                        $statusClass = match($s['statut']) {
                            'actif' => 'bg-success-subtle text-success',
                            'abondon' => 'bg-warning-subtle text-warning',
                            'exclu' => 'bg-danger-subtle text-danger',
                            'diplome' => 'bg-primary-subtle text-primary',
                            default => 'bg-light text-dark'
                        };
                    ?>
                    <tr style="border-bottom:1px solid var(--card-border,#f1f5f9);transition:background 0.15s;"
                        onmouseover="this.style.background='rgba(0,0,0,0.02)'"
                        onmouseout="this.style.background=''">
                        <td class="py-2 ps-4 text-muted" style="font-family:'Inter';font-size:0.75rem;"><?= $rowNum ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                                     style="width:36px;height:36px;font-size:0.85rem;
                                            background:<?= $isFemale ? 'rgba(236,72,153,0.12)' : 'rgba(59,130,246,0.12)' ?>;
                                            color:<?= $isFemale ? '#ec4899' : '#3b82f6' ?>;">
                                     <?= $initial ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($nomComplet) ?></div>
                                    <div class="text-muted" style="font-size:0.72rem;font-family:'Outfit';"><?= htmlspecialchars($nomFr) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="fw-semibold text-dark" style="font-family:'Outfit';">
                            <div><?= htmlspecialchars($s['numero_matricule'] ?? '—') ?></div>
                            <div class="text-muted small" style="font-size:0.72rem; font-family:'Cairo';">
                                <i class="fa-solid fa-calendar-days text-muted me-1"></i>سنة الدخول: <?= !empty($s['date_inscription']) ? date('Y', strtotime($s['date_inscription'])) : '—' ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark" style="font-size:0.8rem;"><?= htmlspecialchars($s['section_nom'] ?? '—') ?></div>
                            <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($s['spec_ar'] ?? '—') ?></div>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($s['etab_ar'] ?? '—') ?></td>
                        <td class="text-center" style="font-size: 0.85rem;">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($s['session_nom'] ?? '—') ?></div>
                            <div class="text-muted small">الفرع: <?= htmlspecialchars($s['section_nom'] ?? 'غير محدد') ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill px-2.5 py-1.5 <?= $statusClass ?>" style="font-size:0.7rem; font-family:'Cairo';">
                                <?= $s['statut'] === 'actif' ? 'نشط' : ($s['statut'] === 'abondon' ? 'تخلى' : ($s['statut'] === 'exclu' ? 'مقصى' : 'متخرج')) ?>
                            </span>
                        </td>
                        <td class="text-center no-print">
                            <div class="d-flex justify-content-center gap-1">
                                <button onclick="viewStudentDetails(<?= $s['id'] ?>)" class="btn btn-sm btn-outline-info px-2" style="border-radius:6px;" title="عرض التفاصيل">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button onclick="openEditModal(<?= $s['id'] ?>)" class="btn btn-sm btn-outline-primary px-2" style="border-radius:6px;" title="تعديل">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $s['id'] ?>, '<?= htmlspecialchars($nomComplet) ?>')" class="btn btn-sm btn-outline-danger px-2" style="border-radius:6px;" title="حذف">
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
                من <strong><?= number_format($total_count) ?></strong> طالب
                (صفحة <?= $page ?> / <?= $total_pages ?>)
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0 gap-1">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_section=<?= $filter_section ?>&filter_status=<?= $filter_status ?>&filter_etab=<?= $filter_etab ?>">
                            <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($total_pages, $page + 2);
                    if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=1&search=<?= urlencode($search) ?>&filter_section=<?= $filter_section ?>&filter_status=<?= $filter_status ?>&filter_etab=<?= $filter_etab ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&filter_section=<?= $filter_section ?>&filter_status=<?= $filter_status ?>&filter_etab=<?= $filter_etab ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&filter_section=<?= $filter_section ?>&filter_status=<?= $filter_status ?>&filter_etab=<?= $filter_etab ?>">
                            <?= $total_pages ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_section=<?= $filter_section ?>&filter_status=<?= $filter_status ?>&filter_etab=<?= $filter_etab ?>">
                            <i class="fa-solid fa-chevron-left" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

</div>

{{-- ══ STUDENT DETAILS MODAL ══ --}}
<div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px; font-family:'Cairo';">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="studentDetailsModalLabel">
                    <i class="fa-solid fa-user-graduate me-2"></i> تفاصيل الطالب / المتكون الكاملة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailsModalContent">
                <!-- Injected via AJAX -->
            </div>
            <div class="modal-footer bg-light p-3" style="border-bottom-left-radius:18px; border-bottom-right-radius:18px;">
                <button type="button" class="btn btn-secondary px-4 fw-bold btn-sm rounded-pill" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ ADD MODAL ══ --}}
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="addStudentModalLabel"><i class="fa-solid fa-user-plus me-2"></i> تسجيل طالب / متكون جديد في النظام</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/apprenants/store') }}" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">اختر المترشح المقبول للتسجيل *</label>
                            <select name="candidat_id" id="add_candidat_id" class="form-select select2" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important;" onchange="onAddCandidateChange(this)">
                                <option value="" disabled selected>-- حدد مترشحاً مقبولاً (تمت مراجعته) --</option>
                                <?php foreach ($availableCandidates as $cand): ?>
                                <option value="<?= $cand->id ?>"
                                        data-nin="<?= htmlspecialchars($cand->nin ?? '') ?>"
                                        data-numins="<?= htmlspecialchars($cand->num_ins ?? '') ?>">
                                    <?= htmlspecialchars($cand->nom_ar . ' ' . $cand->prenom_ar) ?> (<?= htmlspecialchars($cand->nom_fr . ' ' . $cand->prenom_fr) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="text-muted small d-block mt-1">تظهر فقط ملفات المتربصين المقبولين الذين لم يتم تسجيلهم كأعضاء بعد في النظام.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">القسم الدراسي الفعلي *</label>
                            <select name="section_id" class="form-select" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important;">
                                <option value="" disabled selected>-- اختر القسم --</option>
                                <?php foreach ($sections as $s): ?>
                                <option value="<?= $s->id ?>"><?= htmlspecialchars($s->nom_ar) ?> (<?= htmlspecialchars($s->spec_ar) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">رقم بطاقة التعريف الوطنية / رقم التسجيل *</label>
                            <input type="text" name="nccp" id="add_nccp" class="form-control" placeholder="M2026/0001" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">المجموعة الدراسية *</label>
                            <input type="number" name="groupe" id="add_groupe_trainee" class="form-control" min="1" max="50" value="1" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important;">
                            <span class="text-muted small d-block mt-1">رقم الفوج من 1 إلى 50</span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">قرار التثبيت الفني *</label>
                            <select name="valide" class="form-select" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important;">
                                <option value="1">مؤكد ومثبت</option>
                                <option value="0">غير مثبت بعد</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الحالة الإدارية للطالب *</label>
                            <select name="statut" class="form-select" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important;">
                                <option value="actif">نشط / Actif</option>
                                <option value="abondon">تخلى / Abondon</option>
                                <option value="exclu">مقصى / Exclu</option>
                                <option value="diplome">متخرج / Diplômé</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3" style="border-bottom-left-radius:18px; border-bottom-right-radius:18px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold btn-sm rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold btn-sm rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> حفظ وتثبيت التسجيل</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="editStudentModalLabel"><i class="fa-solid fa-pen-to-square me-2"></i> تعديل معلومات المتربص</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/apprenants/update') }}" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4" id="editModalBody">
                    {{-- Spinner --}}
                    <div class="text-center py-5" id="editSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p class="text-muted mt-2">جاري استرجاع بيانات المتربص...</p>
                    </div>

                    {{-- Form Fields (Hidden by default, shown when loaded) --}}
                    <div class="row g-3 d-none" id="editFormFields">
                        <div class="col-12 mb-2">
                            <div class="card border-0 overflow-hidden" style="border-radius:12px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe !important;">
                                <div class="card-body p-3 text-dark d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px; height:48px; font-size:1.4rem; background:rgba(59,130,246,0.15); color: #2563eb;">
                                        <i class="fa-solid fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <span class="d-block small mb-1" style="font-size:0.8rem; color: #1e40af; font-weight: 500;">اسم المتربص المقيد بالنظام</span>
                                        <strong class="fs-5" id="edit_student_name" style="font-family:'Cairo'; color: #1e3a8a !important;"></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-1">القسم الدراسي *</label>
                            <select name="section_id" id="edit_section_id" class="form-select text-dark" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important; height: 42px; border-radius: 8px; border: 1px solid #ced4da !important;">
                                <?php foreach ($sections as $s): ?>
                                <option value="<?= $s->id ?>"><?= htmlspecialchars($s->nom_ar) ?> (<?= htmlspecialchars($s->spec_ar) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-1">رقم بطاقة التعريف الوطنية / رقم التسجيل *</label>
                            <input type="text" name="nccp" id="edit_nccp" class="form-control text-dark" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important; height: 42px; border-radius: 8px; border: 1px solid #ced4da !important;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary mb-1">المجموعة الدراسية (الفوج) *</label>
                            <input type="text" name="groupe" id="edit_groupe" class="form-control text-dark" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important; height: 42px; border-radius: 8px; border: 1px solid #ced4da !important;">
                            <span class="text-muted small d-block mt-1" style="font-size: 0.72rem;">رقم الفوج المعتمد من 1 إلى 50</span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary mb-1">قرار التثبيت البيداغوجي *</label>
                            <select name="valide" id="edit_valide" class="form-select text-dark" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important; height: 42px; border-radius: 8px; border: 1px solid #ced4da !important;">
                                <option value="1">مؤكد ومثبت</option>
                                <option value="0">غير مثبت</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary mb-1">الحالة الإدارية الحالية *</label>
                            <select name="statut" id="edit_statut" class="form-select text-dark" required style="font-size:0.9rem; color: #212529 !important; background-color: #ffffff !important; height: 42px; border-radius: 8px; border: 1px solid #ced4da !important;">
                                <option value="actif">نشط / Actif</option>
                                <option value="abondon">تخلى / Abondon</option>
                                <option value="exclu">مقصى / Exclu</option>
                                <option value="diplome">متخرج / Diplômé</option>
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
document.addEventListener('DOMContentLoaded', function() {
    // Move all modals to document.body to prevent backdrop layering bugs
    document.querySelectorAll('.modal').forEach(function(modal) {
        if (modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
    });
});

const BASE = '{{ asset("") }}';
const CSRF = '{{ csrf_token() }}';
const MEDIA_ACTIONS_ENABLED = {{ \App\Helpers\SovereignLicensingHelper::getSetting('patrimoine_media_actions_enabled', '1') === '1' ? 'true' : 'false' }};

function openEditModal(id) {
    // Show modal
    var myModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    myModal.show();

    // Reset view
    document.getElementById('editSpinner').classList.remove('d-none');
    document.getElementById('editFormFields').classList.add('d-none');

    // Fetch data via AJAX
    fetch('{{ url("dashboard/apprenants/show") }}/' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                document.getElementById('edit_id').value = res.data.IDapprenant;
                document.getElementById('edit_student_name').textContent = res.data.nom_ar + ' ' + res.data.prenom_ar;
                
                // Dynamically ensure the student's section option exists in the select dropdown
                const selectEl = document.getElementById('edit_section_id');
                let optionExists = false;
                for (let i = 0; i < selectEl.options.length; i++) {
                    if (selectEl.options[i].value == res.data.IDSection) {
                        optionExists = true;
                        break;
                    }
                }
                if (!optionExists && res.data.IDSection) {
                    const opt = document.createElement('option');
                    opt.value = res.data.IDSection;
                    opt.text = (res.data.section_nom || ('قسم معرف بـ: ' + res.data.IDSection)) + (res.data.spec_ar ? ' (' + res.data.spec_ar + ')' : '');
                    selectEl.add(opt);
                }
                selectEl.value = res.data.IDSection;
                
                document.getElementById('edit_nccp').value = res.data.Nccp || res.data.nin || '';
                document.getElementById('edit_groupe').value = res.data.Groupe ?? 1;
                document.getElementById('edit_valide').value = res.data.Valide;
                document.getElementById('edit_statut').value = res.data.statut;

                // Toggle visibility
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

function onAddCandidateChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    const nin = selectedOption.getAttribute('data-nin');
    const numins = selectedOption.getAttribute('data-numins');
    
    document.getElementById('add_nccp').value = numins || nin || '';
}

function viewStudentDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
    const content = document.getElementById('detailsModalContent');
    
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">جاري سحب بيانات المتربص...</p>
        </div>
    `;
    modal.show();

    fetch('{{ url("dashboard/apprenants/show") }}/' + id)
        .then(res => res.json())
        .then(response => {
            if (!response.success) {
                content.innerHTML = `<div class="alert alert-danger text-center"><i class="fa-solid fa-exclamation-triangle me-2"></i> ${response.message}</div>`;
                return;
            }

            const data = response.data;
            
            // Dynamic modal preview helper
            if (!window.previewDocumentModal) {
                window.previewDocumentModal = function(url, title) {
                    let modalEl = document.getElementById('documentPreviewModal');
                    if (!modalEl) {
                        modalEl = document.createElement('div');
                        modalEl.id = 'documentPreviewModal';
                        modalEl.className = 'modal fade';
                        modalEl.setAttribute('tabindex', '-1');
                        modalEl.setAttribute('aria-hidden', 'true');
                        modalEl.style.zIndex = '10000';
                        modalEl.innerHTML = `
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                                    <div class="modal-header bg-light border-0 py-3" style="border-top-left-radius:15px; border-top-right-radius:15px;">
                                        <h5 class="modal-title fw-bold text-dark m-0" id="documentPreviewModalTitle" style="font-family:'Cairo'; font-size:1.05rem;"></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-0" id="documentPreviewModalBody" style="height:600px; background:#f8fafc;">
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modalEl);
                    }
                    
                    document.getElementById('documentPreviewModalTitle').innerText = title;
                    const body = document.getElementById('documentPreviewModalBody');
                    const lower = url.toLowerCase();
                    const isImage = lower.endsWith('.png') || lower.endsWith('.jpg') || lower.endsWith('.jpeg') || lower.endsWith('.gif') || lower.endsWith('.svg') || lower.endsWith('.webp');
                    
                    if (isImage) {
                        body.innerHTML = `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:20px; box-sizing:border-box;">
                            <img src="${url}" style="max-width:100%; max-height:100%; object-fit:contain; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                        </div>`;
                    } else {
                        body.innerHTML = `<iframe src="${url}" style="width:100%; height:100%; border:none;" allow="autoplay"></iframe>`;
                    }
                    
                    const m = new bootstrap.Modal(modalEl);
                    m.show();
                };
            }

            function getMediaHtml(path, label) {
                if (!path || path.trim() === '' || path.trim().toLowerCase() === 'empty') {
                    return `<div><span class="profile-field-label text-muted d-block mb-1 fw-bold">${label}:</span><span class="text-muted small">لا توجد وثيقة مرفوعة</span></div>`;
                }
                const lower = path.toLowerCase();
                
                let base = (window.laravel_url || '').replace(/\/$/, '');
                let resolved = path;
                if (lower.startsWith('/uploads/')) {
                    resolved = `${base}${path}`;
                } else if (!lower.startsWith('http://') && !lower.startsWith('https://') && !lower.startsWith('/') && !lower.startsWith('data:')) {
                    resolved = `${base}/${path}`;
                }
                
                return `<div>
                    <span class="profile-field-label text-muted d-block mb-1 fw-bold">${label}:</span>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-bold text-start" onclick="previewDocumentModal('${resolved}', '${label}')" style="font-size:0.75rem;">
                        <i class="fa-solid fa-eye me-1"></i> معاينة المستند المرفق
                    </button>
                </div>`;
            }

            // Photo resolver
            let base = (window.laravel_url || '').replace(/\/$/, '');
            let photoUrl = base + '/assets/images/default-avatar.png'; // default fallback
            if (data.photo_path) {
                let p = data.photo_path;
                photoUrl = p.startsWith('/uploads/') ? `${base}${p}` : (p.startsWith('/') ? p : `${base}/${p}`);
            } else if (data.pre_photo_path) {
                let p = data.pre_photo_path;
                photoUrl = p.startsWith('/uploads/') ? `${base}${p}` : (p.startsWith('/') ? p : `${base}/${p}`);
            }

            const genderText = parseInt(data.civ) === 2 ? 'أنثى' : 'ذكر';

            let photoActionHtml = '';
            if (MEDIA_ACTIONS_ENABLED) {
                let hasPhoto = data.photo_path || data.pre_photo_path;
                photoActionHtml = `
                    <div class="mt-2 d-flex justify-content-center gap-2">
                        <button class="btn btn-xs btn-outline-primary py-0.5 px-2" onclick="triggerCandidatPhotoUpload()" style="font-size:0.68rem; font-family:'Cairo';">
                            <i class="fa-solid fa-camera"></i> ${hasPhoto ? 'تغيير' : 'إضافة'}
                        </button>
                        ${hasPhoto ? `
                        <button class="btn btn-xs btn-outline-danger py-0.5 px-2" onclick="deleteCandidatPhoto(${data.IDapprenant})" style="font-size:0.68rem; font-family:'Cairo';">
                            <i class="fa-solid fa-trash-can"></i> حذف
                        </button>
                        ` : ''}
                    </div>
                    <input type="file" id="detailCandidatPhotoInput" accept="image/*" style="display:none;" onchange="handleCandidatPhotoUpload(${data.IDapprenant})">
                `;
            }

            content.innerHTML = `
                <div class="row g-4 text-right" dir="rtl">
                    <!-- Photo and Primary Details -->
                    <div class="col-md-4 text-center border-start">
                        <div class="mb-3">
                            <img src="${photoUrl}" class="rounded-circle img-thumbnail shadow-sm" style="width:140px; height:140px; object-fit:cover;" onerror="if(this.src && !this.src.includes('/public/uploads/') && this.src.includes('/uploads/')){ this.src = this.src.replace('/uploads/', '/public/uploads/'); } else { this.src='/sig/assets/images/default-avatar.png'; }">
                            ${photoActionHtml}
                        </div>
                        <h5 class="fw-bold text-primary mb-1">${data.nom_ar || ''} ${data.prenom_ar || ''}</h5>
                        <p class="text-muted small text-uppercase mb-2 font-monospace">${data.prenom_fr || ''} ${data.nom_fr || ''}</p>
                        <span class="badge rounded-pill bg-light text-dark px-3 py-1.5 fw-bold mb-3" style="font-size:0.8rem;">
                            رقم التسجيل: ${data.Nccp || '—'}
                        </span>
                        
                        <div class="p-3 bg-light rounded text-start small text-right">
                            <div class="mb-2"><strong>الحالة الإدارية:</strong> <span class="badge bg-info text-white ms-1">${data.statut || '—'}</span></div>
                            <div class="mb-2"><strong>المجموعة الدراسية:</strong> المجموعة ${data.Groupe || '1'}</div>
                            <div><strong>التثبيت:</strong> ${data.Valide == 1 ? '<span class="text-success fw-bold">✓ مؤكد</span>' : '<span class="text-warning fw-bold">قيد المراجعة</span>'}</div>
                        </div>
                    </div>
                    
                    <!-- Civil & Academic Fields -->
                    <div class="col-md-8">
                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-dark"><i class="fa-solid fa-address-card me-1 text-primary"></i> المعلومات الشخصية والدراسية</h6>
                        <div class="row g-3 small">
                            <div class="col-6">
                                <span class="profile-field-label text-muted d-block">رقم التعريف الوطني NIN</span>
                                <strong class="text-dark font-monospace">${data.nin || '—'}</strong>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label text-muted d-block">الجنس</span>
                                <strong class="text-dark">${genderText}</strong>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label text-muted d-block">تاريخ الميلاد</span>
                                <strong class="text-dark">${data.date_nais || '—'}</strong>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label text-muted d-block">مكان الميلاد</span>
                                <strong class="text-dark">${data.lieu_nais || '—'}</strong>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label text-muted d-block">رقم الهاتف</span>
                                <strong class="text-dark font-monospace">${data.tel || '—'}</strong>
                            </div>
                            <div class="col-6">
                                <span class="profile-field-label text-muted d-block">البريد الإلكتروني</span>
                                <strong class="text-dark font-monospace">${data.email || '—'}</strong>
                            </div>
                        </div>

                        @php
                        // No changes to server-side code, we are updating the JS block.
                        @endphp
                        
                        <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3 text-dark"><i class="fa-solid fa-folder-open me-1 text-warning"></i> وثائق الملف المرفوعة (معاينة مباشرة)</h6>
                        <div class="row g-3">
                            <div class="col-md-6 border-end">
                                ${getMediaHtml((data.relevedenotes_url && data.relevedenotes_url.trim().toLowerCase() !== 'empty') ? data.relevedenotes_url : (data.certscol_path && data.certscol_path.trim().toLowerCase() !== 'empty' ? data.certscol_path : null), 'الشهادة المدرسية / كشف النقاط')}
                            </div>
                            <div class="col-md-6">
                                ${getMediaHtml((data.actn_url && data.actn_url.trim().toLowerCase() !== 'empty') ? data.actn_url : (data.actnaispdf_path && data.actnaispdf_path.trim().toLowerCase() !== 'empty' ? data.actnaispdf_path : null), 'عقد الميلاد / Acte de Naissance')}
                            </div>
                            <div class="col-md-6 border-end mt-3">
                                ${getMediaHtml((data.exdiplome_url && data.exdiplome_url.trim().toLowerCase() !== 'empty') ? data.exdiplome_url : (data.diplomecert_path && data.diplomecert_path.trim().toLowerCase() !== 'empty' ? data.diplomecert_path : null), 'شهادة المؤهل / Diplôme')}
                            </div>
                            <div class="col-md-6 mt-3">
                                ${getMediaHtml((data.enneexperience_url && data.enneexperience_url.trim().toLowerCase() !== 'empty') ? data.enneexperience_url : (data.contratpdf_path && data.contratpdf_path.trim().toLowerCase() !== 'empty' ? data.contratpdf_path : null), 'شهادة العمل والخبرة / عقد التمهين')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            content.innerHTML = `<div class="alert alert-danger text-center"><i class="fa-solid fa-exclamation-triangle me-2"></i> خطأ أثناء تحميل البيانات: ${err.message}</div>`;
        });
}

function confirmDelete(id, name) {
    if (confirm("هل أنت متأكد من حذف المتربص " + name + " نهائياً من سجلات النظام؟")) {
        var form = document.getElementById('deleteForm');
        form.action = '{{ url("dashboard/apprenants/delete") }}/' + id;
        form.submit();
    }
}

function triggerCandidatPhotoUpload() {
    document.getElementById('detailCandidatPhotoInput').click();
}

function handleCandidatPhotoUpload(id) {
    const input = document.getElementById('detailCandidatPhotoInput');
    if (!input.files || input.files.length === 0) return;
    
    const file = input.files[0];
    const formData = new FormData();
    formData.append('type', 'candidat');
    formData.append('id', id);
    formData.append('action', 'upload');
    formData.append('photo', file);
    formData.append('_token', CSRF);
    
    Swal.fire({
        title: 'جاري رفع وحفظ الصورة...',
        html: 'يرجى الانتظار...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`${BASE.replace(/\/$/, '')}/dashboard/patrimoine/media/update`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم بنجاح',
                text: data.message,
                confirmButtonText: 'حسناً'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: data.error || 'فشل تحديث الصورة',
                confirmButtonText: 'حسناً'
            });
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'خطأ في الاتصال',
            text: 'تعذر الاتصال بالخادم لرفع الصورة.',
            confirmButtonText: 'حسناً'
        });
    });
}

function deleteCandidatPhoto(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "هل تريد حذف الصورة المرفقة للمتربص؟",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذفها',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('type', 'candidat');
            formData.append('id', id);
            formData.append('action', 'delete');
            formData.append('_token', CSRF);
            
            Swal.fire({
                title: 'جاري الحذف...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`${BASE.replace(/\/$/, '')}/dashboard/patrimoine/media/update`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: data.message,
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: data.error || 'فشل حذف الصورة',
                        confirmButtonText: 'حسناً'
                    });
                }
            })
            .catch(err => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في الاتصال',
                    text: 'تعذر الاتصال بالخادم لإتمام العملية.',
                    confirmButtonText: 'حسناً'
                });
            });
        }
    });
}
</script>
@endsection
