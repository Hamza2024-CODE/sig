@extends('layouts.main')
@section('title', $title ?? 'كشف الموظفين والمكونين')
@section('content')
<?php
/**
 * @var array  $formateurs
 * @var int    $total_count
 * @var int    $page
 * @var int    $total_pages
 * @var int    $per_page
 * @var string $search
 * @var int    $filter_etab
 * @var array  $wilayas
 * @var array  $etablissements
 * @var string $role_code
 */
$from = $total_count > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total_count);
?>

<div class="animate__animated animate__fadeIn" style="font-family:'Cairo', sans-serif;">

    {{-- ══ HEADER ══ --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--text-main);">
                <i class="fa-solid fa-id-card-clip text-primary me-2"></i>
                كشف الموظفين والمكونين / Registre des Encadreurs
            </h4>
            <p class="text-muted small mb-0">
                عرض <?= number_format($from) ?>–<?= number_format($to) ?> من إجمالي
                <strong class="text-primary"><?= number_format($total_count) ?></strong> موظف
            </p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ url('dashboard/formateurs/age-distribution') }}" class="btn btn-outline-info rounded-pill px-3 fw-bold btn-sm shadow-sm d-inline-flex align-items-center gap-1" style="border-color:#0ea5e9; color:#0ea5e9;">
                <i class="fa-solid fa-chart-pie"></i> إحصائيات السن والشعب
            </a>
            <button class="btn btn-primary rounded-pill px-4 fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addTrainerModal">
                <i class="fa-solid fa-user-plus me-1"></i> إضافة موظف جديد
            </button>
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 fw-bold btn-sm">
                <i class="fa-solid fa-print me-1"></i> طباعة
            </button>
            <button onclick="exportTableToExcel('formateursTable','encadrement.xls')"
                    class="btn btn-outline-success rounded-pill px-3 fw-bold btn-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Excel
            </button>
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
        <form method="GET" action="{{ url('dashboard/encadrement') }}" class="row g-2 align-items-end">

            {{-- بحث نصي --}}
            <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">بحث باسم الموظف</label>
                <div class="input-group">
                    <span class="input-group-text border-0" style="background:var(--input-bg,#f8f9fa);">
                        <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           class="form-control border-0 rounded-end" placeholder="اسم، لقب..."
                           style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                </div>
            </div>

            {{-- فلتر الولاية --}}
            <?php if (in_array($role_code, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])): ?>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">الولاية</label>
                <select name="filter_wilaya" class="form-select border-0 rounded" onchange="this.form.submit()"
                        style="background:var(--input-bg,#f8f9fa);font-size:0.88rem;">
                    <option value="0">كل الولايات</option>
                    <?php foreach ($wilayas as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= ($filter_wilaya ?? 0) == $w['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($w['nom_ar'] ?? $w['nom'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            {{-- فلتر المؤسسة --}}
            <?php if (count($etablissements) > 1 || !empty($etablissements)): ?>
            <div class="col-12 col-md-4">
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

            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="font-size:0.88rem;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ url('dashboard/encadrement') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold"
                   style="font-size:0.88rem;">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- ══ TABLE ══ --}}
    <div class="card border-0 shadow-sm" style="border-radius:18px;background:var(--card-bg);border:1px solid var(--card-border)!important;">
        <div class="table-responsive">
            <table class="table align-middle mb-0 small" id="formateursTable" style="text-align:right;">
                <thead style="background:rgba(0,0,0,0.03);">
                    <tr class="text-muted fw-bold" style="font-size:0.78rem;">
                        <th class="py-3 ps-4">#</th>
                        <th>اسم الموظف</th>
                        <th class="text-center">الجنس</th>
                        <th class="text-center">الإيكلون</th>
                        <th>البريد الإلكتروني</th>
                        <th>المؤسسة / الولاية</th>
                        <th>تاريخ الإدماج</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-center no-print">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($formateurs)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 d-block mb-3 opacity-25"></i>
                            لا توجد نتائج مطابقة للبحث.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($formateurs as $idx => $f):
                        $isFemale  = ((int)($f['civ'] ?? 1) === 2);
                        $nomComplet = trim(($f['nom'] ?? '') . ' ' . ($f['prenom'] ?? ''));
                        $initial   = mb_strtoupper(mb_substr($f['nom'] ?? 'م', 0, 1));
                        $echlo     = (int)($f['echlo'] ?? 0);
                        $sit       = $f['situation'] ?? 'قيد الخدمة';
                        $sitOk     = str_contains($sit, 'قيد');
                        $rowNum    = ($page - 1) * $per_page + $idx + 1;
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
                                    <div class="text-muted" style="font-size:0.72rem;">موظف قطاع التكوين المهني</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill px-2" style="font-size:0.68rem;
                                  background:<?= $isFemale ? 'rgba(236,72,153,0.1)' : 'rgba(59,130,246,0.1)' ?>;
                                  color:<?= $isFemale ? '#ec4899' : '#3b82f6' ?>;">
                                <?= $isFemale ? 'أنثى' : 'ذكر' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border" style="font-family:'Inter';font-size:0.78rem;">
                                <?= $echlo ?: '—' ?>
                            </span>
                        </td>
                        <td class="text-muted" style="font-family:'Inter';font-size:0.79rem;direction:ltr;text-align:left;">
                            <?= htmlspecialchars($f['email'] ?? '—') ?>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark" style="font-size:0.8rem;">
                                <?= mb_substr(htmlspecialchars($f['etab_ar'] ?? '—'), 0, 35) ?>
                            </div>
                            <?php if (!empty($f['wilaya'])): ?>
                            <div class="text-muted" style="font-size:0.72rem;">
                                <i class="fa-solid fa-location-dot text-primary me-1"></i>
                                <?= htmlspecialchars($f['wilaya']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted" style="font-family:'Inter';font-size:0.79rem;">
                            <?= htmlspecialchars($f['date_install'] ?? '—') ?>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill px-2 py-1"
                                  style="font-size:0.65rem;
                                         background:<?= $sitOk ? 'rgba(16,185,129,0.12)' : 'rgba(148,163,184,0.12)' ?>;
                                         color:<?= $sitOk ? '#10b981' : '#64748b' ?>;">
                                <?= htmlspecialchars($sit ?: 'قيد الخدمة') ?>
                            </span>
                        </td>
                        <td class="text-center no-print">
                            <div class="d-flex justify-content-center gap-1">
                                <button onclick="openEditModal(<?= $f['id'] ?>)" class="btn btn-sm btn-outline-primary px-2" style="border-radius:6px;" title="تعديل">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $f['id'] ?>, '<?= htmlspecialchars($nomComplet) ?>')" class="btn btn-sm btn-outline-danger px-2" style="border-radius:6px;" title="حذف">
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
                من <strong><?= number_format($total_count) ?></strong> موظف
                (صفحة <?= $page ?> / <?= $total_pages ?>)
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0 gap-1">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_etab=<?= $filter_etab ?>">
                            <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($total_pages, $page + 2);
                    if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=1&search=<?= urlencode($search) ?>&filter_etab=<?= $filter_etab ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&filter_etab=<?= $filter_etab ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3"
                           href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&filter_etab=<?= $filter_etab ?>">
                            <?= $total_pages ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_etab=<?= $filter_etab ?>">
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
<div class="modal fade" id="addTrainerModal" tabindex="-1" aria-labelledby="addTrainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="addTrainerModalLabel"><i class="fa-solid fa-user-plus me-2"></i> إضافة موظف / مؤطر جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/formateurs/store') }}" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اللقب بالعربية *</label>
                            <input type="text" name="nom" class="form-control" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الاسم بالعربية *</label>
                            <input type="text" name="prenom" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الجنس *</label>
                            <select name="civ" class="form-select" required style="font-size:0.9rem;">
                                <option value="1">ذكر</option>
                                <option value="2">أنثى</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">رقم الهاتف</label>
                            <input type="text" name="tel" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">العنوان الشخصي</label>
                            <input type="text" name="adres" class="form-control" style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">تاريخ التعيين / التثبيت</label>
                            <input type="date" name="date_install" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الدرجة / الإيكلون</label>
                            <input type="number" name="echlo" class="form-control" value="0" style="font-size:0.9rem;" step="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">المؤسسة التكوينية المقيد بها *</label>
                            <select name="etablissement_id" class="form-select" required style="font-size:0.9rem;">
                                <option value="" disabled selected>-- حدد المؤسسة --</option>
                                <?php foreach ($etablissements as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom_ar'] ?? $e['nom'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">المهام والوظائف الأساسية</label>
                            <textarea name="taches" class="form-control" rows="3" style="font-size:0.9rem;" placeholder="أدخل المهام الوظيفية..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3" style="border-bottom-left-radius:18px; border-bottom-right-radius:18px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold btn-sm rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold btn-sm rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> حفظ الموظف</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editTrainerModal" tabindex="-1" aria-labelledby="editTrainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-dark text-white" style="border-top-left-radius:18px; border-top-right-radius:18px;">
                <h5 class="modal-title fw-bold" id="editTrainerModalLabel"><i class="fa-solid fa-pen-to-square me-2"></i> تعديل بيانات الموظف / المؤطر</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url('dashboard/formateurs/update') }}" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4" id="editModalBody">
                    {{-- Spinner --}}
                    <div class="text-center py-5" id="editSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p class="text-muted mt-2">جاري استرجاع البيانات...</p>
                    </div>

                    {{-- Form Fields --}}
                    <div class="row g-3 d-none" id="editFormFields">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اللقب بالعربية *</label>
                            <input type="text" name="nom" id="edit_nom" class="form-control" required style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الاسم بالعربية *</label>
                            <input type="text" name="prenom" id="edit_prenom" class="form-control" required style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">البريد الإلكتروني</label>
                            <input type="email" name="email" id="edit_email" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">الجنس *</label>
                            <select name="civ" id="edit_civ" class="form-select" required style="font-size:0.9rem;">
                                <option value="1">ذكر</option>
                                <option value="2">أنثى</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">رقم الهاتف</label>
                            <input type="text" name="tel" id="edit_tel" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">العنوان</label>
                            <input type="text" name="adres" id="edit_adres" class="form-control" style="font-size:0.9rem;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">تاريخ التعيين</label>
                            <input type="date" name="date_install" id="edit_date_install" class="form-control" style="font-size:0.9rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الدرجة</label>
                            <input type="number" name="echlo" id="edit_echlo" class="form-control" style="font-size:0.9rem;" step="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">المؤسسة *</label>
                            <select name="etablissement_id" id="edit_etablissement_id" class="form-select" required style="font-size:0.9rem;">
                                <?php foreach ($etablissements as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom_ar'] ?? $e['nom'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">المهام والوظائف الأساسية</label>
                            <textarea name="taches" id="edit_taches" class="form-control" rows="3" style="font-size:0.9rem;"></textarea>
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

{{-- ══ DELETE FORM ══ --}}
<form id="deleteForm" action="" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<script>
function openEditModal(id) {
    var myModal = new bootstrap.Modal(document.getElementById('editTrainerModal'));
    myModal.show();

    document.getElementById('editSpinner').classList.remove('d-none');
    document.getElementById('editFormFields').classList.add('d-none');

    fetch('{{ url("dashboard/formateurs/show") }}/' + id)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                document.getElementById('edit_id').value = res.data.IDEncadrement;
                document.getElementById('edit_nom').value = res.data.Nom;
                document.getElementById('edit_prenom').value = res.data.Prenom;
                document.getElementById('edit_email').value = res.data.Email;
                document.getElementById('edit_civ').value = res.data.Civ;
                document.getElementById('edit_tel').value = res.data.Tel;
                document.getElementById('edit_adres').value = res.data.Adres;
                document.getElementById('edit_date_install').value = res.data.DateInstall;
                document.getElementById('edit_echlo').value = res.data.Echlo;
                document.getElementById('edit_etablissement_id').value = res.data.IDetablissement;
                document.getElementById('edit_taches').value = res.data.TachesPrincipale;

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
    if (confirm("هل أنت متأكد من حذف الموظف " + name + " نهائياً؟")) {
        var form = document.getElementById('deleteForm');
        form.action = '{{ url("dashboard/formateurs/delete") }}/' + id;
        form.submit();
    }
}
</script>
@endsection
