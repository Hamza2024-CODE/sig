@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 */
?>
<div class="animate__animated animate__fadeIn">
@if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 mb-4 text-right animate__animated animate__fadeIn" style="background-color: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.15) !important; color: #15803d; font-weight: 700;">
            <i class="fa-solid fa-circle-check me-1.5"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-list-check text-primary me-2"></i> تسيير لجان التقييم ومتابعة المكونين / Inspections
            </h3>
            <p class="text-muted mb-0 small">تنظيم الزيارات الميدانية للمفتشين البيداغوجيين، المحاضر المشتركة، ورقابة الجودة</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('evaluation.inspecteurs') }}" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm" style="border-color: #643edb; color: #643edb;"><i class="fa-solid fa-user-shield me-2"></i> دليل المفتشين والزيارات</a>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addInspectionModal" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;"><i class="fa-solid fa-file-invoice me-2"></i> إدراج تقرير تفتيش</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">لجان التقييم البيداغوجية النشطة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['commissions']) ?> لجنة</h2>
                    <span class="small"><i class="fa-solid fa-people-group"></i> موزعة قطاعياً وجغرافياً بالولاية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">المفتشون والخبراء المعتمدون</h6>
                    <h2 class="display-5 fw-bold my-2 text-primary"><?= number_format($stats['inspecteurs']) ?> مفتشاً</h2>
                    <span class="small text-muted"><i class="fa-solid fa-user-shield text-success"></i> لمرافقة وتحسين جودة التأطير البيداغوجي</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">محاضر التقييم الجاهزة والنهائية</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= number_format($stats['pv_prets']) ?> محضراً</h2>
                    <span class="small text-muted"><i class="fa-solid fa-file-shield text-success"></i> جاهزة للمصادقة الوزارية النهائية</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-ninja text-primary me-2"></i> سجل زيارات التفتيش والتقويم الأخير</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('inspectsTable', 'inspections_formateurs.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('inspectsTable', 'inspections_formateurs.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="inspectsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">القسم واللجنة البيداغوجية</th>
                            <th>التخصص والمؤسسة التكوينية</th>
                            <th class="text-center">الدورة والمحضر المتابع</th>
                            <th class="text-center">التقييم البيداغوجي النهائي</th>
                            <th class="pe-4 text-end">التوصية الفنية للمحضر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">قسم مطور الويب والوسائط المتعددة - د5</div>
                                    <div class="text-muted small">رئيس اللجنة: أ. بن عيسى خديجة | مؤطر: أ. طويل عبد القادر</div>
                                </td>
                                <td>مطور الويب والوسائط المتعددة (معهد سعيدة)</td>
                                <td class="text-center fw-bold text-primary">دورة فيفري 2026</td>
                                <td class="text-center fw-bold text-success fs-5">18.5 / 20</td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-success rounded-pill px-3 py-2">معدل النجاح: 100% (25/25 متربص)</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <?php
                                if (isset($item['section_nom'])) {
                                    $title = $item['section_nom'];
                                    $subtitle = 'أعضاء اللجنة: ' . $item['jury_members'];
                                    $etab_spec = $item['spec_ar'] . ' (' . $item['etab_nom'] . ')';
                                    $inspector = $item['session_nom'];
                                    $note = $item['average_note'];
                                    $success_pct = $item['total_students'] > 0 ? round(($item['admitted_students'] / $item['total_students']) * 100) : 0;
                                    $appreciation = "نسبة النجاح: " . $success_pct . "% (" . $item['admitted_students'] . "/" . $item['total_students'] . " متربص)";
                                } else {
                                    $title = $item['formateur_nom'];
                                    $subtitle = 'تفتيش بيداغوجي للمكون';
                                    $etab_spec = $item['spec_ar'];
                                    $inspector = 'المفتش: ' . $item['inspecteur_id'];
                                    $note = $item['note_pedagogique'];
                                    $appreciation = $item['appreciation'];
                                }
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($title) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($subtitle) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($etab_spec) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= htmlspecialchars($inspector) ?></td>
                                    <td class="text-center fw-bold text-success fs-5"><?= htmlspecialchars($note) ?> / 20</td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-success rounded-pill px-3 py-2"><?= htmlspecialchars($appreciation) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add Inspection Modal -->
    <div class="modal fade animate__animated animate__fadeIn" id="addInspectionModal" tabindex="-1" aria-labelledby="addInspectionModalLabel" aria-hidden="true" style="direction: rtl; text-align: right;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="addInspectionModalLabel"><i class="fa-solid fa-file-signature text-primary me-2"></i> إدراج تقرير تفتيش جديد</h5>
                    <button type="button" class="btn-close ms-0 me-auto bg-white/80" data-bs-dismiss="modal" aria-label="Close" style="box-shadow: none;"></button>
                </div>
                <form action="{{ route('evaluation.gestion.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 text-right">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">المكون المستهدف (Nom du Formateur)</label>
                            <div class="apple-input-wrapper">
                                <input type="text" name="formateur_nom" required class="apple-input" placeholder="مثال: أ. بن عيسى خديجة">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">التخصص والمعهد (Spécialité & Etablissement)</label>
                            <div class="apple-input-wrapper">
                                <input type="text" name="spec_ar" required class="apple-input" placeholder="مثال: مطور الويب والوسائط المتعددة (معهد سعيدة)">
                                <i class="fa-solid fa-school"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">المفتش المتابع (Nom de l'Inspecteur)</label>
                            <div class="apple-input-wrapper">
                                <input type="text" name="inspecteur_id" required class="apple-input" placeholder="مثال: د. مهداوي">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">التقييم البيداغوجي النهائي (Note /20)</label>
                            <div class="apple-input-wrapper">
                                <input type="number" step="0.25" min="0" max="20" name="note_pedagogique" required class="apple-input" placeholder="مثال: 18.5">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">التوصية الفنية للمحضر (Appréciation)</label>
                            <select name="appreciation" required class="form-select rounded-4 py-2.5 px-3 bg-white" style="border: 1.5px solid rgba(15, 23, 42, 0.08); font-size: 0.88rem; font-weight: 600;">
                                <option value="موافقة تامة وترقية استثنائية">موافقة تامة وترقية استثنائية</option>
                                <option value="موافقة وتوصية بالترقية">موافقة وتوصية بالترقية</option>
                                <option value="مقبول وموصى بالاستمرار">مقبول وموصى بالاستمرار</option>
                                <option value="مرفوض وتوصية بإعادة التقييم">مرفوض وتوصية بإعادة التقييم</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-premium-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="apple-btn-primary rounded-pill px-4 fw-bold border-0" style="height: auto; padding: 0.6rem 1.5rem;">حفظ التقرير</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
