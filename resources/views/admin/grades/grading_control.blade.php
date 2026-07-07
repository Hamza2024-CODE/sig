@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $config
 * @var array $establishments
 * @var array $trainingModes
 * @var int $selectedModeId
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Breadcrumbs -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">
                <i class="fa-solid fa-gears text-primary me-2"></i> إعدادات ومحددات نظام التقييم والمعدلات
            </h4>
            <p class="text-muted small mb-0">لوحة تحكم إدارية مركزية لضبط المعايير البيداغوجية، فترات الحجز، والأوزان النسبية لحساب المعدلات.</p>
        </div>
        <a href="/dashboard/grades" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
            <i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة البيداغوجية
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= session('flash_success') ?>
        </div>
        <?php  ?>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= session('flash_error') ?>
        </div>
        <?php  ?>
    <?php endif; ?>

    <form action="/dashboard/grades/control/save" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">

        <div class="row g-4">
            <!-- Right Column: Time Windows & Security Exceptions -->
            <div class="col-lg-8">
                <!-- 0. MODE SELECTOR CARD -->
                <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:20px; border-top: 4px solid var(--info-color, #0dcaf0) !important;">
                    <h5 class="fw-bold text-dark mb-4" style="font-family:'Cairo';">
                        <i class="fa-solid fa-layer-group text-info me-2"></i> النمط التكويني المستهدف
                    </h5>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted">اختر النمط لضبط إعداداته الخاصة المستقلة</label>
                        <select name="mode_id" class="form-select form-select-lg border-0 bg-light fw-bold" onchange="window.location.href='?mode_id=' + this.value;">
                            <?php foreach ($trainingModes as $id => $name): ?>
                                <option value="<?= $id ?>" <?= ($selectedModeId === $id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?> (<?= $id ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 1. TIME WINDOW CARD -->
                <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:20px; border-top: 4px solid var(--primary-color) !important;">
                    <h5 class="fw-bold text-dark mb-4" style="font-family:'Cairo';">
                        <i class="fa-regular fa-clock text-primary me-2"></i> نافذة وجدول الحجز المسموح للأساتذة
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">تاريخ بداية إدخال النقاط / Date Début</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-regular fa-calendar text-muted"></i></span>
                                <input type="date" name="grading_start_date" class="form-control border-0 bg-light py-2.5 fw-semibold" value="<?= htmlspecialchars($config['workflow']['grading_start_date'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">تاريخ نهاية إدخال النقاط / Date Limite</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-regular fa-calendar text-muted"></i></span>
                                <input type="date" name="grading_end_date" class="form-control border-0 bg-light py-2.5 fw-semibold" value="<?= htmlspecialchars($config['workflow']['grading_end_date'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded-3">
                        <small class="text-muted d-block" style="line-height:1.6;">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            <strong>ملاحظة هامة:</strong> خارج هذه الفترة الزمنية، سيتم قفل إمكانية إدخال أو تعديل أي نقاط من طرف الأساتذة تلقائياً في بوابة المكون، ما لم يُمنح للمؤسسة استثناء صريح أدناه.
                        </small>
                    </div>
                </div>

                <!-- 2. EXCEPTIONS & OVERRIDES CARD -->
                <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:20px;">
                    <h5 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">
                        <i class="fa-solid fa-circle-nodes text-warning me-2"></i> استثناءات الاستدراك للمؤسسات المرخص لها
                    </h5>
                    <p class="text-muted small mb-4">حدد المؤسسات التي يُسمح لأساتذتها بتجاوز قفل التاريخ ورصد أو تعديل علامات الاستدراك والامتحانات المتأخرة.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">اختر المؤسسات المستثناة / Établissements Autorisés</label>
                        <div class="row g-2" style="max-height: 240px; overflow-y: auto; border: 1.5px solid var(--border); padding:1rem; border-radius:12px;">
                            <?php foreach ($establishments as $etab): ?>
                                <?php
                                $isChecked = in_array((int)$etab['id'], $config['workflow']['remedial_allowed_establishments'] ?? []);
                                ?>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="remedial_allowed_establishments[]" value="<?= $etab['id'] ?>" id="etab_<?= $etab['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold text-dark" for="etab_<?= $etab['id'] ?>">
                                            <?= htmlspecialchars($etab['nom']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Left Column: Weights, Constants & Policies -->
            <div class="col-lg-4">
                <!-- 1. GENERAL POLICIES & TOGGLES -->
                <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:20px;">
                    <h5 class="fw-bold text-dark mb-4" style="font-family:'Cairo';">
                        <i class="fa-solid fa-shield-halved text-success me-2"></i> حالة وسياسة الاعتماد
                    </h5>
                    <div class="form-check form-switch mb-3 p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                        <div class="me-2">
                            <label class="form-check-label fw-bold text-dark d-block small mb-1" for="final_validation_active">تفعيل الاعتماد النهائي</label>
                            <span class="text-muted d-block" style="font-size:0.7rem;">قفل رصد النقاط على كافة المؤسسات.</span>
                        </div>
                        <input class="form-check-input" type="checkbox" name="final_validation_active" value="1" id="final_validation_active" <?= ($config['workflow']['final_validation_active'] ?? false) ? 'checked' : '' ?>>
                    </div>
                </div>

                <!-- 2. WEIGHTS CONFIGURATION -->
                <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:20px;">
                    <h5 class="fw-bold text-dark mb-4" style="font-family:'Cairo';">
                        <i class="fa-solid fa-scale-balanced text-primary me-2"></i> الأوزان البيداغوجية للمواد
                        <span class="badge bg-primary ms-2"><?= htmlspecialchars($trainingModes[$selectedModeId]) ?></span>
                    </h5>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">وزن التقويم المستمر / Continuous (CC1)</label>
                        <input type="number" step="0.05" min="0" max="1" name="continuous_assessment_weight" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['continuous_assessment_weight'] ?? $config['module_grade']['continuous_assessment_weight'] ?? 0.4) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">وزن الفرض والمسابقات / Quiz (CC2)</label>
                        <input type="number" step="0.05" min="0" max="1" name="quiz_weight" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['quiz_weight'] ?? $config['module_grade']['quiz_weight'] ?? 0.4) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">وزن الامتحان السداسي / Exam (Cs)</label>
                        <input type="number" step="0.05" min="0" max="1" name="exam_weight" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['exam_weight'] ?? $config['module_grade']['exam_weight'] ?? 0.6) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">المقسوم بكسر الحساب / Divisor</label>
                        <input type="number" step="0.1" min="0.5" max="3" name="divisor" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['divisor'] ?? $config['module_grade']['divisor'] ?? 1.0) ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-muted">عتبة الإقصاء من المادة / Elimination Grade</label>
                        <input type="number" step="0.5" min="0" max="10" name="elimination_threshold" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['elimination_threshold'] ?? $config['semester']['elimination_threshold'] ?? 5.0) ?>">
                    </div>
                </div>

                <!-- 2.5 DISTANCE LEARNING WEIGHTS -->
                <div class="card border-0 shadow-sm p-4 bg-white mb-4 <?= !in_array($selectedModeId, [18, 21]) ? 'opacity-50' : '' ?>" style="border-radius:20px;">
                    <h5 class="fw-bold text-dark mb-4" style="font-family:'Cairo';">
                        <i class="fa-solid fa-laptop-file text-info me-2"></i> أوزان التكوين عن بعد (Distance)
                    </h5>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">وزن نقطة التفاعل والأرضية (يقابل CC1)</label>
                        <input type="number" step="0.05" min="0" max="1" name="dl_platform_activity" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['dl_platform_activity'] ?? $config['distance_learning']['weights']['platform_activity'] ?? 0.3) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">وزن الفروض (يقابل CC2)</label>
                        <input type="number" step="0.05" min="0" max="1" name="dl_assignments" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['dl_assignments'] ?? $config['distance_learning']['weights']['assignments'] ?? 0.3) ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-muted">وزن الامتحان التجمعي (يقابل Cs)</label>
                        <input type="number" step="0.05" min="0" max="1" name="dl_written_exam" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['dl_written_exam'] ?? $config['distance_learning']['weights']['written_exam'] ?? 0.4) ?>">
                    </div>
                </div>

                <!-- 3. SEMESTER & RETRIAL THRESHOLDS -->
                <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:20px;">
                    <h5 class="fw-bold text-dark mb-4" style="font-family:'Cairo';">
                        <i class="fa-solid fa-graduation-cap text-purple me-2"></i> عتبات القبول والتمهين
                    </h5>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">معدل النجاح الأدنى للمادة والاستدراك</label>
                        <input type="number" step="0.25" min="8" max="12" name="passing_threshold" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['passing_threshold'] ?? $config['remedial']['passing_threshold'] ?? 10.0) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">معدل القبول السداسي الأدنى</label>
                        <input type="number" step="0.25" min="8" max="12" name="passing_gpa_threshold" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['passing_gpa_threshold'] ?? $config['semester']['passing_gpa_threshold'] ?? 10.0) ?>">
                    </div>
                    <div class="mb-0 <?= $selectedModeId !== 10 ? 'opacity-50' : '' ?>">
                        <label class="form-label small fw-semibold text-muted">معامل التربص التطبيقي للتمهين (مخصص للتمهين)</label>
                        <input type="number" step="0.5" min="1" max="10" name="company_coefficient" class="form-control border-0 bg-light py-2 fw-semibold" value="<?= (float)($config['modes'][$selectedModeId]['company_coefficient'] ?? $config['semester']['apprenticeship']['company_coefficient'] ?? 4.0) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button Panel -->
        <div class="card border-0 shadow-sm p-3 bg-white d-flex flex-row justify-content-between align-items-center gap-3" style="border-radius:16px;">
            <span class="small text-muted fw-bold"><i class="fa-solid fa-shield-halved text-success me-1"></i> حماية إدخالات إعدادات النظام مفعلة.</span>
            <div class="d-flex gap-2">
                <a href="/dashboard/grades" class="btn btn-light rounded-pill px-4 fw-bold btn-sm">إلغاء التغييرات</a>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold btn-sm">حفظ الإعدادات والمعايير</button>
            </div>
        </div>
    </form>
</div>

@endsection
