@extends('layouts.main')
@section('title', $title ?? 'تخصيص وتصميم الشهادات الرسمية')
@section('content')
<?php
/**
 * @var array $settings
 * @var string $title
 */
?>
<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <!-- Header Page Title & Premium Cyber Sweep Line -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h1 class="h3 fw-bold mb-1" style="font-family: 'Cairo'; color: var(--text-main);">
                <i class="fa-solid fa-palette text-primary me-2"></i>
                تخصيص وتصميم الشهادات الرسمية والطباعة
            </h1>
            <p class="text-muted mb-0 small">التحكم في المظهر البصري لشهادات الدولة المطبوعة وتعديل العلامات المائية والألوان</p>
        </div>
        <div class="no-print">
            <a href="{{ url('dashboard/diplomes') }}" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-award me-1"></i> إدارة الشهادات
            </a>
        </div>
    </div>
    <div class="cyber-line-pulse mb-4"></div>

    <!-- Alert and Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>
            <?= session('flash_success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <?= session('flash_error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Section 1: Vocational Training Modes Table -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; min-height: 480px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success text-white p-2 rounded" style="--bs-bg-opacity: .12;">
                            <i class="fa-solid fa-graduation-cap text-success"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0" style="font-family: 'Cairo';">دليل أنماط التكوين والشهادات</h5>
                            <small class="text-muted">جدول يوضح أنماط التكوين المهني المختلفة والشهادات الملحقة بها</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0 border-0" style="font-family: 'Cairo';">
                            <thead class="table-light">
                                <tr class="fw-bold text-muted small">
                                    <th class="text-start">نمط التكوين</th>
                                    <th>الشهادة المتحصل عليها</th>
                                    <th>نوع الشهادة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-dark text-start" style="font-size: 0.88rem;">التكوين الإقامي</td>
                                    <td>
                                        <span class="badge bg-primary px-2.5 py-1.5 text-wrap" style="border-radius:6px; font-size:0.75rem; line-height: 1.4;">شهادة التحكم المهني (CMP) / شهادة التقني (ST) / شهادة التقني السامي (STS)</span>
                                    </td>
                                    <td><span class="text-muted small">شهادة مهنية (تتدرج حسب المستوى)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-dark text-start" style="font-size: 0.88rem;">التكوين عن طريق التمهين</td>
                                    <td>
                                        <span class="badge bg-success px-2.5 py-1.5 text-wrap" style="border-radius:6px; font-size:0.75rem; line-height: 1.4;">شهادة التحكم المهني (CMP) / شهادة التقني (ST) / شهادة التقني السامي (STS)</span>
                                    </td>
                                    <td><span class="text-muted small">شهادة مهنية (مدمجة)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-dark text-start" style="font-size: 0.88rem;">التكوين التأهيلي</td>
                                    <td>
                                        <span class="badge bg-warning text-dark px-2.5 py-1.5 text-wrap" style="border-radius:6px; font-size:0.75rem; line-height: 1.4;">شهادة التأهيل المهني (CQ)</span>
                                    </td>
                                    <td><span class="text-muted small">شهادة تأهيلية قصيرة المدى</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-dark text-start" style="font-size: 0.88rem;">التكوين عن بعد</td>
                                    <td>
                                        <span class="badge bg-info text-white px-2.5 py-1.5 text-wrap" style="border-radius:6px; font-size:0.75rem; line-height: 1.4;">شهادة (حسب التخصص والمستوى)</span>
                                    </td>
                                    <td><span class="text-muted small">شهادة مهنية (معادلة للإقامي)</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-dark text-start" style="font-size: 0.88rem;">الدروس المسائية</td>
                                    <td>
                                        <span class="badge text-white px-2.5 py-1.5 text-wrap" style="border-radius:6px; font-size:0.75rem; background-color:#6366f1; line-height: 1.4;">شهادة (حسب التخصص والمستوى)</span>
                                    </td>
                                    <td><span class="text-muted small">شهادة مهنية</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-dark text-start" style="font-size: 0.88rem;">التكوين المتواصل</td>
                                    <td>
                                        <span class="badge bg-secondary px-2.5 py-1.5 text-wrap" style="border-radius:6px; font-size:0.75rem; line-height: 1.4;">شهادة إتمام التكوين / شهادة تخصص</span>
                                    </td>
                                    <td><span class="text-muted small">شهادة توثيق كفاءة (إضافية)</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- توضيحات هامة حول هذه الشهادات -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-dark mb-3" style="font-family: 'Cairo';">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            توضيحات هامة حول هذه الشهادات:
                        </h6>
                        <ul class="list-unstyled p-0 m-0 text-muted small" style="line-height: 1.8; font-family: 'Cairo'; text-align: justify; padding-right: 0;">
                            <li class="mb-2">
                                <span class="badge bg-danger px-2 py-1 me-1 text-wrap" style="font-size: 0.7rem;">شهادة التقني السامي (STS)</span>:
                                تعتبر أعلى شهادة في التكوين المهني (المستوى 5)، وتتطلب مستوى تعليمي (ثالثة ثانوي) وتكوينًا لمدة 30 شهرًا.
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-primary px-2 py-1 me-1 text-wrap" style="font-size: 0.7rem;">شهادة التقني (ST)</span>:
                                (المستوى 4)، تتطلب مستوى تعليمي (أولى ثانوي) وتكوينًا لمدة 24 شهرًا.
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-success px-2 py-1 me-1 text-wrap" style="font-size: 0.7rem;">شهادة التحكم المهني (CMP)</span>:
                                (المستوى 3)، تتطلب مستوى تعليمي (رابعة متوسط) وتكوينًا لمدة 18 شهرًا.
                            </li>
                            <li class="mb-0">
                                <span class="badge bg-warning text-dark px-2 py-1 me-1 text-wrap" style="font-size: 0.7rem;">شهادة التأهيل المهني (CQ)</span>:
                                تُمنح عادة للتكوينات السريعة أو التكوينات الموجهة لفئات معينة، وتهدف إلى إعطاء مهارة محددة لسوق الشغل (المدة تتراوح من 3 إلى 12 شهرًا).
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Certificate Background & Customization Settings -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; min-height: 480px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .12;">
                            <i class="fa-solid fa-palette text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0" style="font-family: 'Cairo';">تخصيص وضبط الشهادات الرسمية</h5>
                            <small class="text-muted">التحكم في المظهر البصري لشهادات الدولة المطبوعة</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ url('dashboard/settings/diplome/update') }}" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">

                        <div class="row">
                            <!-- Border Color Picker -->
                            <div class="col-md-6 mb-3">
                                <label for="diploma_border_color" class="form-label fw-bold text-dark mb-1 small">لون إطار الشهادة</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" id="diploma_border_color" name="diploma_border_color" value="<?= htmlspecialchars($settings['diploma_border_color']) ?>" title="اختر لون إطار الشهادة" style="border-radius: 8px; width: 50px; height: 38px; padding: 4px;">
                                    <input type="text" class="form-control" id="diploma_border_color_text" value="<?= htmlspecialchars($settings['diploma_border_color']) ?>" style="font-family: 'Outfit'; font-size: 0.88rem; height: 38px; border-radius: 8px;" oninput="document.getElementById('diploma_border_color').value = this.value; updatePreview();">
                                </div>
                            </div>

                            <!-- Primary Title Color Picker -->
                            <div class="col-md-6 mb-3">
                                <label for="diploma_primary_color" class="form-label fw-bold text-dark mb-1 small">لون نصوص العناوين</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" id="diploma_primary_color" name="diploma_primary_color" value="<?= htmlspecialchars($settings['diploma_primary_color']) ?>" title="اختر لون خط العنوان" style="border-radius: 8px; width: 50px; height: 38px; padding: 4px;">
                                    <input type="text" class="form-control" id="diploma_primary_color_text" value="<?= htmlspecialchars($settings['diploma_primary_color']) ?>" style="font-family: 'Outfit'; font-size: 0.88rem; height: 38px; border-radius: 8px;" oninput="document.getElementById('diploma_primary_color').value = this.value; updatePreview();">
                                </div>
                            </div>
                        </div>

                        <!-- Watermark Image URL -->
                        <div class="mb-3">
                            <label for="diploma_watermark_url" class="form-label fw-bold text-dark mb-1 small">رابط شعار العلامة المائية بالخلفية (الوسط)</label>
                            <input type="text" class="form-control" id="diploma_watermark_url" name="diploma_watermark_url" value="<?= htmlspecialchars($settings['diploma_watermark_url']) ?>" placeholder="Ex: https://upload.wikimedia.org/wikipedia/commons/e/e0/Emblem_of_Algeria.svg" oninput="updatePreview()" style="font-family: 'Outfit'; font-size: 0.88rem; direction: ltr; text-align: left; border-radius: 8px;">
                            <div class="form-text text-muted" style="font-size:0.75rem;">شعار الدولة أو المؤسسة شبه شفاف في منتصف الشهادة.</div>
                        </div>

                        <!-- Background Image URL -->
                        <div class="mb-3">
                            <label for="diploma_bg_url" class="form-label fw-bold text-dark mb-1 small">رابط صورة الخلفية الأساسية (اختياري)</label>
                            <input type="text" class="form-control" id="diploma_bg_url" name="diploma_bg_url" value="<?= htmlspecialchars($settings['diploma_bg_url']) ?>" placeholder="مثال: رابط صورة ورق بردي أو زركشة" oninput="updatePreview()" style="font-family: 'Outfit'; font-size: 0.88rem; direction: ltr; text-align: left; border-radius: 8px;">
                            <div class="form-text text-muted" style="font-size:0.75rem;">صورة الخلفية الكاملة للشهادة. اتركها فارغة لاستخدام النقش الهندسي الافتراضي.</div>
                        </div>

                        <!-- Custom Save Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success fw-bold" style="border-radius: 10px; font-family: 'Cairo';">
                                <i class="fa-solid fa-circle-check me-1"></i> حفظ وتطبيق تغييرات تصميم الشهادة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Preview Mockup Container -->
    <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-eye text-primary"></i>
                <h5 class="fw-bold m-0" style="font-family: 'Cairo';">معاينة حية ومباشرة للشهادة (Live Preview Mockup)</h5>
            </div>
        </div>
        <div class="card-body p-4 d-flex justify-content-center bg-light rounded-bottom-4" style="overflow-x: auto;">
            <!-- Mini Mockup Frame -->
            <div id="diploma_preview_card" class="position-relative border p-3 bg-white" style="width: 650px; height: 460px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: all 0.3s ease; box-sizing: border-box; overflow:hidden;">
                <!-- Border preview -->
                <div id="preview_border" class="position-absolute" style="top: 10px; bottom: 10px; left: 10px; right: 10px; border: 1px solid #1e3a8a; padding: 2px;">
                    <div id="preview_border_inner" class="w-100 h-100" style="border: 2px solid #1e3a8a; box-sizing: border-box;"></div>
                </div>

                <!-- Watermark preview -->
                <div id="preview_watermark" class="position-absolute" style="top: 50%; left: 50%; width: 200px; height: 200px; transform: translate(-50%, -50%); background-image: url('https://upload.wikimedia.org/wikipedia/commons/e/e0/Emblem_of_Algeria.svg'); background-repeat: no-repeat; background-position: center; background-size: contain; opacity: 0.055; pointer-events: none; z-index: 1;"></div>

                <!-- Text content preview -->
                <div class="position-relative z-3 d-flex flex-column justify-content-between h-100 p-3" style="font-family: 'Cairo'; z-index: 10; height: 100%;">
                    <div class="d-flex justify-content-between align-items-start small" style="font-size:0.68rem; color:#334155;">
                        <div style="direction: ltr; font-family:'Outfit'; font-weight:700;">N° 018822/25/V/FP</div>
                        <div class="text-center fw-bold">
                            الجمهورية الجزائرية الديمقراطية الشعبية<br>
                            وزارة التكوين والتعليم المهنيين
                        </div>
                    </div>

                    <div class="text-center my-auto">
                        <h2 id="preview_title" class="fw-bold mb-2" style="font-size: 1.8rem; color: #1e3a8a; font-family:'Cairo';">شهادة تقني سامي</h2>
                        <p class="mb-1 text-muted" style="font-size: 0.72rem; line-height: 1.6;">
                            تمنح هذه الشهادة للسيد(ة): <strong class="text-dark">بن علي محمد</strong><br>
                            المولود(ة) بتاريخ: <strong class="text-dark">14/04/2000</strong> بـ: <strong class="text-dark">سعيدة</strong>
                        </p>
                        <p class="mb-0 text-muted" style="font-size: 0.72rem;">
                            التخصص: <strong class="text-dark">إعلام آلي / خيار: مطور تطبيقات متعددة المنصات</strong>
                        </p>
                    </div>

                    <div class="d-flex justify-content-between align-items-end small" style="font-size:0.65rem; width: 100%; margin-top: 10px;">
                        <div class="text-center" style="width: 45%;">
                            <div style="font-weight: 800; color: #1e3a8a; margin-bottom: 1px;">المسؤول البيداغوجي</div>
                            <div style="height: 18px; background-image: url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 100 30&quot;><path d=&quot;M10 15 Q25 5 40 20 T70 10 T90 20&quot; stroke=&quot;rgba(30,58,138,0.7)&quot; stroke-width=&quot;1.5&quot; fill=&quot;none&quot;/></svg>'); background-size: contain; background-repeat: no-repeat; background-position: center; margin: 2px 0;"></div>
                            <div style="font-weight: 700; color: #1e293b;">زقنون عمر</div>
                        </div>
                        <div class="text-center" style="width: 45%; position: relative;">
                            <div style="font-weight: 800; color: #1e3a8a; margin-bottom: 1px;">مديرة المعهد</div>
                            <div style="height: 18px; background-image: url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 100 30&quot;><path d=&quot;M10 15 Q25 5 40 20 T70 10 T90 20&quot; stroke=&quot;rgba(30,58,138,0.7)&quot; stroke-width=&quot;1.5&quot; fill=&quot;none&quot;/></svg>'); background-size: contain; background-repeat: no-repeat; background-position: center; margin: 2px 0;"></div>
                            <div style="font-weight: 700; color: #1e293b;">ولد سعيد فتيحة</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update live preview based on user selections
    function updatePreview() {
        var borderColor = document.getElementById('diploma_border_color').value;
        var primaryColor = document.getElementById('diploma_primary_color').value;
        var watermarkUrl = document.getElementById('diploma_watermark_url').value;
        var bgUrl = document.getElementById('diploma_bg_url').value;

        // Synchronize text inputs with color pickers
        document.getElementById('diploma_border_color_text').value = borderColor.toUpperCase();
        document.getElementById('diploma_primary_color_text').value = primaryColor.toUpperCase();

        // Apply border colors
        document.getElementById('preview_border').style.borderColor = borderColor;
        document.getElementById('preview_border_inner').style.borderColor = borderColor;

        // Apply primary color to title
        document.getElementById('preview_title').style.color = primaryColor;

        // Apply watermark image
        if (watermarkUrl) {
            document.getElementById('preview_watermark').style.backgroundImage = "url('" + watermarkUrl + "')";
            document.getElementById('preview_watermark').style.display = 'block';
        } else {
            document.getElementById('preview_watermark').style.display = 'none';
        }

        // Apply background image or fallback to default
        var card = document.getElementById('diploma_preview_card');
        if (bgUrl) {
            card.style.backgroundImage = "url('" + bgUrl + "')";
            card.style.backgroundSize = "cover";
            card.style.backgroundPosition = "center";
        } else {
            // Restore default honeycomb pattern
            card.style.backgroundImage = `
                radial-gradient(circle, rgba(72, 43, 143, 0.018) 10%, transparent 10.01%),
                linear-gradient(30deg, rgba(72, 43, 143, 0.008) 25%, transparent 25%),
                linear-gradient(150deg, rgba(72, 43, 143, 0.008) 25%, transparent 25%),
                linear-gradient(270deg, rgba(72, 43, 143, 0.008) 50%, transparent 50%)
            `;
            card.style.backgroundSize = "20px 35px";
        }
    }

    // Bind event listeners to update text input when color picker changes
    document.getElementById('diploma_border_color').addEventListener('input', function() {
        document.getElementById('diploma_border_color_text').value = this.value.toUpperCase();
        updatePreview();
    });
    document.getElementById('diploma_primary_color').addEventListener('input', function() {
        document.getElementById('diploma_primary_color_text').value = this.value.toUpperCase();
        updatePreview();
    });

    // Run preview once on load
    window.addEventListener('DOMContentLoaded', function() {
        updatePreview();
    });
</script>
@endsection
