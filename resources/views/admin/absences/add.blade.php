@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-1">تسجيل حضور الفوج البيداغوجي</h3>
                <p class="text-muted mb-0">قم بتحديد الطلبة الغائبين لليوم لتحديث ملفات رصد الحضور الإجمالية</p>
            </div>
            <div>
                <a href="/dashboard/absences" class="btn btn-outline-secondary px-4" style="border-radius: 8px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> العودة للخلف
                </a>
            </div>
        </div>
    </div>

    <form action="/dashboard/absences/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-body p-4 bg-light" style="border-radius: 12px;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark"><i class="fa-solid fa-calendar-day text-primary me-1"></i> تاريخ الحصة / Date</label>
                        <input type="date" name="date_absence" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark"><i class="fa-solid fa-hourglass-half text-primary me-1"></i> مدة الحصة (ساعات) *</label>
                        <select name="duree_heures" class="form-select" required>
                            <option value="2.00">ساعتان (2h)</option>
                            <option value="4.00">نصف يوم (4h)</option>
                            <option value="8.00">يوم كامل (8h)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block mb-2">ملاحظة: افتراضياً، يتم اعتبار كافة الطلاب حاضرين، يرجى فقط وضع علامة ✅ على الطلاب **الغائبين** فعلياً.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-right">
                        <thead class="table-light">
                            <tr class="fw-bold text-muted small">
                                <th class="py-3 px-4 text-center" style="width: 80px;">غائب؟</th>
                                <th>رقم التسجيل / Matricule</th>
                                <th>اسم المتربص الكامل</th>
                                <th>الفرع التكويني</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash d-block mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                        لا يوجد أي متربصين نشطين مسجلين في الفوج حالياً.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td class="text-center py-3 px-4">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="absent_students[]" value="<?= $s['stagiaire_id'] ?>" style="width: 22px; height: 22px; cursor: pointer;">
                                            </div>
                                        </td>
                                        <td><code class="text-dark fw-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($s['numero_matricule']) ?></code></td>
                                        <td>
                                            <strong class="text-primary fs-6"><?= htmlspecialchars($s['nom_ar'] . ' ' . $s['prenom_ar']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark px-3 py-1.5 fw-semibold"><?= htmlspecialchars($s['specialite_ar']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light p-3 text-start">
                <button type="submit" class="btn text-white px-5 py-2.5 fw-bold" style="background-color: var(--color-gov-purple); border-radius: 8px;">
                    <i class="fa-solid fa-circle-check me-1"></i> حفظ وتأكيد قائمة الحضور
                </button>
            </div>
        </div>
    </form>
</div>

@endsection
