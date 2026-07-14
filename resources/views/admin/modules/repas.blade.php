@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $menus
 * @var array $reservations
 * @var array $stagiaires
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-utensils text-primary me-2"></i> نظام حجز ومتابعة الوجبات / Meal Reservation & Catering
            </h3>
            <p class="text-muted mb-0 small">تسيير المطعم الداخلي، حجز وجبات الطلبة الداخليين والمتمهنين، ومتابعة جودة التغذية</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;" data-bs-toggle="modal" data-bs-target="#reserveMealModal"><i class="fa-solid fa-plus-circle me-2"></i> حجز وجبة جديدة</button>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= session('flash_success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php  ?>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i> <?= session('flash_error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php  ?>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">الوجبات المبرمجة الكلية</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['menus']) ?> وجبات</h2>
                    <span class="small"><i class="fa-solid fa-utensils"></i> تغطي الوجبات المحددة لهذا الأسبوع</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">الحجوزات النشطة اليوم</h6>
                    <h2 class="display-5 fw-bold my-2 text-primary"><?= number_format($stats['reservations']) ?> حجز</h2>
                    <span class="small text-muted"><i class="fa-solid fa-clock text-primary"></i> مؤكدة وفي انتظار الاستهلاك</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">الوجبات الموزعة والمستهلكة</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= number_format($stats['served']) ?> وجبة</h2>
                    <span class="small text-muted"><i class="fa-solid fa-circle-check text-success"></i> بنسبة كفاءة توزيع 98.2%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Menus and Menu Manager -->
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-bowl-food text-primary me-2"></i> قائمة الوجبات النشطة (Menu)</h5>
                </div>
                <div class="card-body px-4 py-3">
                    <?php if (empty($menus)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-circle-exclamation fs-1 mb-3"></i>
                            <p class="mb-0">لا يوجد وجبات مبرمجة حالياً.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3 mt-2">
                            <?php foreach ($menus as $menu): ?>
                                <div class="p-3 rounded-4 bg-light border-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary rounded-pill px-3 py-1 mb-2">
                                            <?= $menu['type_repas'] === 'dejeuner' ? 'الغداء' : ($menu['type_repas'] === 'diner' ? 'العشاء' : 'الفطور') ?>
                                        </span>
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($menu['plat_principal']) ?></h6>
                                        <small class="text-muted"><i class="fa-solid fa-apple-whole text-success me-1"></i> التحلية: <?= htmlspecialchars($menu['dessert'] ?? 'لا يوجد') ?></small>
                                    </div>
                                    <div class="text-end">
                                        <small class="d-block text-muted mb-1"><?= htmlspecialchars($menu['date_menu']) ?></small>
                                        <span class="badge bg-success rounded-pill px-3 py-1"><?= htmlspecialchars(ucfirst($menu['statut'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <!-- Reservations logs -->
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i> سجل حجوزات وجبات الطلاب</h5>
                    <div class="d-flex gap-2 no-print">
                        <button onclick="exportTableToExcel('mealsTable', 'reservations_repas.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-file-excel me-1"></i> Excel
                        </button>
                        <button onclick="exportTableToCSV('mealsTable', 'reservations_repas.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-file-csv me-1"></i> CSV
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="mealsTable">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-4">المتربص</th>
                                    <th>الوجبة / الطبق</th>
                                    <th class="text-center">تاريخ الاستهلاك</th>
                                    <th class="text-center">رمز الاستجابة (QR)</th>
                                    <th class="pe-4 text-end">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-ticket fs-1 mb-3"></i>
                                            <p class="mb-0">لا توجد حجوزات مسجلة حالياً.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservations as $res): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($res['nom_ar'] . ' ' . $res['prenom_ar']) ?></div>
                                                <div class="text-muted small">رقم: <?= htmlspecialchars($res['numero_matricule']) ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($res['type_repas'] === 'dejeuner' ? 'الغداء' : 'العشاء') ?></div>
                                                <div class="text-muted small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($res['plat_principal']) ?></div>
                                            </td>
                                            <td class="text-center fw-bold text-muted"><?= htmlspecialchars($res['date_consommation']) ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border border-dark rounded-pill px-3 py-2 fw-bold" style="font-family: 'Outfit', sans-serif;">
                                                    <i class="fa-solid fa-qrcode me-1 text-primary"></i> <?= htmlspecialchars($res['code_qr']) ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <?php if ($res['statut'] === 'reserve'): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-clock me-1"></i> محجوزة</span>
                                                <?php elseif ($res['statut'] === 'consomme'): ?>
                                                    <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i> مستهلكة</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fa-solid fa-ban me-1"></i> ملغاة</span>
                                                <?php endif; ?>
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
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="reserveMealModal" tabindex="-1" aria-labelledby="reserveMealModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form action="/dashboard/repas/reserver" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                    <div class="modal-header border-0 pt-4 px-4 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="reserveMealModalLabel"><i class="fa-solid fa-ticket text-primary me-2"></i> حجز وجبة جديدة للمتربص</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 py-3">
                        <div class="mb-3">
                            <label for="stagiaire_id" class="form-label fw-bold text-muted small">اختر المتربص</label>
                            <select class="form-select rounded-3 border-light bg-light" id="stagiaire_id" name="stagiaire_id" required>
                                <option value="" disabled selected>-- اختر متربص من القائمة --</option>
                                <?php foreach ($stagiaires as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['nom_ar'] . ' ' . $st['prenom_ar'] . ' (رقم: ' . $st['numero_matricule'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="menu_id" class="form-label fw-bold text-muted small">اختر الوجبة / اليوم</label>
                            <select class="form-select rounded-3 border-light bg-light" id="menu_id" name="menu_id" required>
                                <option value="" disabled selected>-- اختر وجبة من المنيو --</option>
                                <?php foreach ($menus as $menu): ?>
                                    <option value="<?= $menu['id'] ?>">
                                        <?= htmlspecialchars($menu['date_menu'] . ' - ' . ($menu['type_repas'] === 'dejeuner' ? 'الغداء' : 'العشاء') . ' (' . $menu['plat_principal'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">تأكيد الحجز</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
