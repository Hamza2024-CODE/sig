@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $maitres
 * @var array  $entreprises
 * @var array  $apprenants
 * @var array  $stats
 */
?>
<div class="animate__animated animate__fadeIn">

    <!-- Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-4 me-2"></i>
                <div><?= session('flash_success');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
                <div><?= session('flash_error');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color:#1e293b;font-family:'Cairo',sans-serif;">
                <i class="fa-solid fa-person-chalkboard text-primary me-2"></i>معلمو التمهين
            </h3>
            <p class="text-muted mb-0 small">
                سجل معلمي التمهين المكلفين بالإشراف الميداني على المتربصين لدى المؤسسات الاقتصادية الشريكة
            </p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="/dashboard/partenaires" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-building me-2"></i>المؤسسات الاقتصادية
            </a>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm"
                    style="background:linear-gradient(135deg,#482b8f 0%,#643edb 100%);border:none;"
                    data-bs-toggle="modal" data-bs-target="#addMaitreModal">
                <i class="fa-solid fa-plus me-2"></i>إضافة معلم تمهين
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100"
                 style="background:linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%);color:white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي معلمي التمهين</h6>
                    <h2 class="display-5 fw-bold my-2 text-white"><?= number_format($stats['total']) ?></h2>
                    <span class="small"><i class="fa-solid fa-person-chalkboard"></i> معلم مسجل في المنظومة</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">نشطون حالياً</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= number_format($stats['actifs']) ?></h2>
                    <span class="small text-muted">
                        <i class="fa-solid fa-circle-check text-success"></i>
                        معلم في إطار عقد ساري المفعول
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">مؤسسات مشاركة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['entreprises']) ?></h2>
                    <span class="small text-muted">
                        <i class="fa-solid fa-industry text-warning"></i>
                        مؤسسة اقتصادية تضم معلمين مؤهلين
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-ul text-primary me-2"></i>قائمة معلمي التمهين المسجلين
            </h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('maitresTable','maitres_apprentissage.xls')"
                        class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="window.print()"
                        class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <?php if (empty($maitres)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-person-chalkboard fa-3x mb-3 opacity-25"></i>
                    <p class="mb-1 fw-bold">لا يوجد معلمو تمهين مسجلون</p>
                    <p class="small">
                        يمكنك إضافة معلم تمهين جديد عبر الزر أعلاه،
                        أو تحقق من وجود جدول <code>maitre_apprentissage</code> في قاعدة البيانات.
                    </p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="maitresTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">معلم التمهين</th>
                            <th>المؤسسة الاقتصادية</th>
                            <th>المتربص المرافَق</th>
                            <th class="text-center">الوظيفة / الصفة</th>
                            <th class="text-center">تاريخ البداية</th>
                            <th class="text-center">تاريخ النهاية</th>
                            <th class="text-center no-export">الحالة</th>
                            <th class="pe-4 text-end no-print no-export">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maitres as $m): ?>
                        <?php
                            $isActive = empty($m['DateF']) || strtotime($m['DateF']) >= time();
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">
                                    <?= htmlspecialchars($m['Nom'] . ' ' . $m['Prenom']) ?>
                                </div>
                                <?php if (!empty($m['NomFr'])): ?>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($m['NomFr'] . ' ' . ($m['PrenomFr'] ?? '')) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($m['Tel'])): ?>
                                    <div class="text-muted small">
                                        <i class="fa-solid fa-phone fa-xs me-1"></i><?= htmlspecialchars($m['Tel']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">
                                    <?= htmlspecialchars($m['entreprise_ar'] ?? '—') ?>
                                </div>
                                <?php if (!empty($m['entreprise_fr'])): ?>
                                    <div class="text-muted small"><?= htmlspecialchars($m['entreprise_fr']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($m['apprenant_ar'])): ?>
                                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-user-graduate me-1 text-primary"></i>
                                        <?= htmlspecialchars($m['apprenant_ar']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="text-muted small"><?= htmlspecialchars($m['Fonction'] ?? '—') ?></span>
                            </td>
                            <td class="text-center text-muted small">
                                <?= htmlspecialchars($m['DateD'] ?? '—') ?>
                            </td>
                            <td class="text-center text-muted small">
                                <?= !empty($m['DateF']) ? htmlspecialchars($m['DateF']) : '<span class="text-success fw-bold">مفتوح</span>' ?>
                            </td>
                            <td class="text-center">
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2">
                                        <i class="fa-solid fa-check me-1"></i>نشط
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill px-3 py-2">
                                        <i class="fa-solid fa-circle-xmark me-1"></i>منتهي
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-end no-print no-export">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-1"
                                        onclick="openEditMaitre(<?= htmlspecialchars(json_encode($m)) ?>)">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>تعديل
                                </button>
                                <form action="/dashboard/maitres-apprentissage/delete/<?= (int)$m['IDMaitre'] ?>"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('هل تريد حذف هذا المعلم؟')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                        <i class="fa-solid fa-trash me-1"></i>حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Maitre Modal -->
<div class="modal fade" id="addMaitreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-person-chalkboard text-primary me-2"></i>إضافة معلم تمهين جديد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/dashboard/maitres-apprentissage/store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">الاسم (عربي) <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control rounded-pill" placeholder="اسم المعلم" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">اللقب (عربي)</label>
                            <input type="text" name="prenom" class="form-control rounded-pill" placeholder="لقب المعلم">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">الاسم (فرنسي)</label>
                            <input type="text" name="nom_fr" class="form-control rounded-pill" placeholder="Nom">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">اللقب (فرنسي)</label>
                            <input type="text" name="prenom_fr" class="form-control rounded-pill" placeholder="Prénom">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">المؤسسة الاقتصادية</label>
                            <select name="id_entreprise" class="form-select rounded-pill">
                                <option value="">-- غير محدد --</option>
                                <?php foreach ($entreprises as $e): ?>
                                    <option value="<?= (int)$e['IDEntreprise'] ?>">
                                        <?= htmlspecialchars($e['Nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">المتربص المرافَق</label>
                            <select name="id_apprenant" class="form-select rounded-pill">
                                <option value="">-- غير مرتبط بمتربص --</option>
                                <?php foreach ($apprenants as $a): ?>
                                    <option value="<?= (int)$a['IDapprenant'] ?>">
                                        <?= htmlspecialchars($a['nom_complet']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">الوظيفة / الصفة</label>
                            <input type="text" name="fonction" class="form-control rounded-pill"
                                   placeholder="مثال: مشرف تقني، مهندس، تقني سامي...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">الهاتف</label>
                            <input type="text" name="tel" class="form-control rounded-pill" placeholder="رقم الهاتف">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">تاريخ البداية</label>
                            <input type="date" name="date_debut" class="form-control rounded-pill">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">تاريخ النهاية (اختياري)</label>
                            <input type="date" name="date_fin" class="form-control rounded-pill">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"
                            style="background:linear-gradient(135deg,#482b8f 0%,#643edb 100%);border:none;">
                        <i class="fa-solid fa-floppy-disk me-2"></i>حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Maitre Modal -->
<div class="modal fade" id="editMaitreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-pen-to-square text-warning me-2"></i>تعديل بيانات معلم التمهين
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/dashboard/maitres-apprentissage/update" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" name="id" id="edit_m_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">الاسم</label>
                            <input type="text" name="nom" id="edit_m_nom" class="form-control rounded-pill" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">اللقب</label>
                            <input type="text" name="prenom" id="edit_m_prenom" class="form-control rounded-pill">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">الوظيفة / الصفة</label>
                            <input type="text" name="fonction" id="edit_m_fonction" class="form-control rounded-pill">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">الهاتف</label>
                            <input type="text" name="tel" id="edit_m_tel" class="form-control rounded-pill">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">تاريخ انتهاء العقد</label>
                            <input type="date" name="date_fin" id="edit_m_datefin" class="form-control rounded-pill">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-white">
                        <i class="fa-solid fa-floppy-disk me-2"></i>حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditMaitre(m) {
    document.getElementById('edit_m_id').value      = m.IDMaitre;
    document.getElementById('edit_m_nom').value     = m.Nom    || '';
    document.getElementById('edit_m_prenom').value  = m.Prenom || '';
    document.getElementById('edit_m_fonction').value = m.Fonction || '';
    document.getElementById('edit_m_tel').value     = m.Tel    || '';
    document.getElementById('edit_m_datefin').value = m.DateF  || '';
    new bootstrap.Modal(document.getElementById('editMaitreModal')).show();
}
</script>

@endsection
