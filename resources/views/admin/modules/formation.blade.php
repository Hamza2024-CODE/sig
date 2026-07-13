@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $list
 * @var array $specialites
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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
                <div><?= session('flash_error');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-chalkboard-user text-primary me-2"></i> تسيير التكوين، البرامج البيداغوجية والتجهيزات / Formation
            </h3>
            <p class="text-muted mb-0 small">متابعة ورقمنة البرامج الدراسية، البطاقات الفنية للتخصصات، والوسائل التقنية للمحترفات</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                <i class="fa-solid fa-screwdriver-wrench me-2"></i> جرد وإضافة تجهيز تقني
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">البرامج الدراسية المبرمجة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= $stats['programmes'] ?> برنامج</h2>
                    <span class="small"><i class="fa-solid fa-book"></i> مطابقة تماماً للمدونة الوطنية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">برامج قيد التحديث والتحيين</h6>
                    <h2 class="display-5 fw-bold my-2 text-danger"><?= $stats['en_cours_maj'] ?> تخصص</h2>
                    <span class="small text-muted"><i class="fa-solid fa-arrows-rotate text-danger"></i> بالشراكة مع اللجان التقنية الاستشارية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">التجهيزات والمعدات التقنية الجردية</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= $stats['equipements'] ?> مجموعة</h2>
                    <span class="small text-muted"><i class="fa-solid fa-gears text-success"></i> موزعة على كافة المحترفات والمخابر</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-toolbox text-primary me-2"></i> وضعية ورشات التكوين التطبيقي والعتاد البيداغوجي الموجه</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('equipTable', 'equipements_pedagogiques.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('equipTable', 'equipements_pedagogiques.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="equipTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المؤسسة التكوينية</th>
                            <th>المحترف / التخصص المعني</th>
                            <th>التجهيز الرئيسي المعتمد</th>
                            <th class="text-center">الكمية المتوفرة</th>
                            <th class="text-center">الحالة التشغيلية</th>
                            <th class="text-center">تاريخ المعاينة الفنية</th>
                            <th class="pe-4 text-end no-print no-export">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">لا توجد تجهيزات مقيدة بسجل الجرد حالياً.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark" style="font-size: 0.85rem;">
                                        <i class="fa-solid fa-hotel text-primary me-2 small"></i>
                                        <?= htmlspecialchars($item['etab_nom'] ?? 'مؤسسة تكوينية') ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-secondary"><?= htmlspecialchars($item['spec_ar']) ?></div>
                                        <div class="text-muted small">محترف تطبيقي رسمي</div>
                                    </td>
                                    <td><span class="fw-semibold text-dark"><?= htmlspecialchars($item['designation']) ?></span></td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($item['quantite']) ?> طقم متكامل</td>
                                    <td class="text-center">
                                        <?php if ($item['etat'] === 'ممتاز' || $item['etat'] === 'جيد'): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($item['etat']) ?></span>
                                        <?php elseif ($item['etat'] === 'مقبول'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($item['etat']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa-solid fa-circle-xmark me-1"></i> <?= htmlspecialchars($item['etat']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-muted"><?= htmlspecialchars($item['date_inventaire'] ?? '2026-05-17') ?></td>
                                    <td class="pe-4 text-end no-print no-export">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 edit-eq-btn" 
                                                    data-id="<?= $item['id'] ?>"
                                                    data-designation="<?= htmlspecialchars($item['designation']) ?>"
                                                    data-specialite-id="<?= $item['specialite_id'] ?>"
                                                    data-quantite="<?= $item['quantite'] ?>"
                                                    data-etat="<?= htmlspecialchars($item['etat']) ?>"
                                                    data-date-acquisition="<?= $item['date_inventaire'] ?>"
                                                    data-etablissement-id="<?= $item['IDetablissement'] ?>">
                                                <i class="fa-solid fa-edit me-1"></i> تعديل
                                            </button>
                                            <form action="/dashboard/formation/delete/<?= $item['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا التجهيز من الجرد؟')">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fa-solid fa-trash me-1"></i> حذف</button>
</form>
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

<!-- Add Equipment Modal -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="addEquipmentModalLabel"><i class="fa-solid fa-screwdriver-wrench text-primary me-2"></i> قيد وتجهيز ورشة تكوينية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/formation/store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="nom_equipement" class="form-label small fw-bold text-muted">اسم التجهيز / الوصف الفني</label>
                        <input type="text" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="nom_equipement" name="nom_equipement" placeholder="مثال: لوحة التحكم الرقمي CNC" required>
                    </div>
                    <div class="mb-3">
                        <label for="etablissement_id" class="form-label small fw-bold text-muted">المؤسسة التكوينية التابع لها العتاد</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="etablissement_id" name="etablissement_id" required>
                            <?php foreach ($etablissements as $et): ?>
                                <option value="<?= $et['id'] ?>" <?= (session('user')['etablissement_id'] == $et['id']) ? 'selected' : '' ?>><?= htmlspecialchars($et['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="specialite_id" class="form-label small fw-bold text-muted">التخصص الموجه له ورشة التكوين</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="specialite_id" name="specialite_id" required>
                            <?php foreach ($specialites as $sp): ?>
                                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['libelle_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="quantite" class="form-label small fw-bold text-muted">الكمية المتوفرة</label>
                            <input type="number" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="quantite" name="quantite" min="1" value="1" required>
                        </div>
                        <div class="col-6">
                            <label for="etat" class="form-label small fw-bold text-muted">الحالة التشغيلية</label>
                            <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="etat" name="etat">
                                <option value="ممتاز" selected>ممتاز (Excellent)</option>
                                <option value="جيد">جيد (Bon)</option>
                                <option value="مقبول">مقبول (Acceptable)</option>
                                <option value="معطل">معطل (En panne)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="date_acquisition" class="form-label small fw-bold text-muted">تاريخ الجرد أو الحيازة</label>
                        <input type="date" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="date_acquisition" name="date_acquisition" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ الجرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Equipment Modal -->
<div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-labelledby="editEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="editEquipmentModalLabel"><i class="fa-solid fa-edit text-primary me-2"></i> تعديل بيانات التجهيز التقني</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/formation/update" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_nom_equipement" class="form-label small fw-bold text-muted">اسم التجهيز / الوصف الفني</label>
                        <input type="text" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="edit_nom_equipement" name="nom_equipement" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_etablissement_id" class="form-label small fw-bold text-muted">المؤسسة التكوينية التابع لها العتاد</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="edit_etablissement_id" name="etablissement_id" required>
                            <?php foreach ($etablissements as $et): ?>
                                <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_specialite_id" class="form-label small fw-bold text-muted">التخصص الموجه له ورشة التكوين</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="edit_specialite_id" name="specialite_id" required>
                            <?php foreach ($specialites as $sp): ?>
                                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['libelle_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="edit_quantite" class="form-label small fw-bold text-muted">الكمية المتوفرة</label>
                            <input type="number" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="edit_quantite" name="quantite" min="1" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_etat" class="form-label small fw-bold text-muted">الحالة التشغيلية</label>
                            <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="edit_etat" name="etat">
                                <option value="ممتاز">ممتاز (Excellent)</option>
                                <option value="جيد">جيد (Bon)</option>
                                <option value="مقبول">مقبول (Acceptable)</option>
                                <option value="معطل">معطل (En panne)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_date_acquisition" class="form-label small fw-bold text-muted">تاريخ الجرد أو الحيازة</label>
                        <input type="date" class="form-control rounded-pill border-light-subtle shadow-sm px-3" id="edit_date_acquisition" name="date_acquisition" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-eq-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const designation = this.getAttribute('data-designation');
            const specId = this.getAttribute('data-specialite-id');
            const etabId = this.getAttribute('data-etablissement-id');
            const qty = this.getAttribute('data-quantite');
            const etat = this.getAttribute('data-etat');
            const date = this.getAttribute('data-date-acquisition');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nom_equipement').value = designation;
            document.getElementById('edit_specialite_id').value = specId;
            document.getElementById('edit_etablissement_id').value = etabId;
            document.getElementById('edit_quantite').value = qty;
            document.getElementById('edit_etat').value = etat;
            document.getElementById('edit_date_acquisition').value = date;

            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editEquipmentModal'));
            editModal.show();
        });
    });
});
</script>



@endsection
