@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
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
                <i class="fa-solid fa-cloud-arrow-down text-primary me-2"></i>
                إعدادات تكامل البيانات والربط مع منصة Takwin.dz
            </h1>
            <p class="text-muted mb-0 small">تحديث وتسيير إعدادات جلب طلبات التسجيل الخارجي مباشرة عبر واجهات البرمجة التطبيقية (API)</p>
        </div>
        <div class="no-print">
            <a href="{{ url('dashboard/candidates') }}" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-user-graduate me-1"></i> إدارة المترشحين
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
        <?php  ?>
    <?php endif; ?>

    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <?= session('flash_error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php  ?>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Side: Connection settings & Configuration Form -->
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .12;">
                            <i class="fa-solid fa-sliders text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0" style="font-family: 'Cairo';">إعدادات الاتصال والتوثيق</h5>
                            <small class="text-muted">قم بتهيئة وتحديث مفاتيح التوثيق الخاصة بالوصول لـ Takwin API</small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="{{ url('dashboard/settings/takwin/update') }}" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">

                        <!-- API Endpoint URL -->
                        <div class="mb-4">
                            <label for="api_url" class="form-label fw-bold text-dark mb-2" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-link text-muted me-1"></i> رابط خادم API الأساسي (API Root Endpoint)
                            </label>
                            <input type="url" 
                                   class="form-control form-control-lg border-2" 
                                   id="api_url" 
                                   name="api_url" 
                                   value="<?= htmlspecialchars($settings['api_url']) ?>" 
                                   placeholder="https://takwin.dz/api" 
                                   required 
                                   style="font-family: 'Outfit', sans-serif; font-size: 0.95rem; direction: ltr; text-align: left; border-radius: 10px;">
                            <div class="form-text text-muted mt-2">
                                نقطة النهاية الموحدة لاستقبال الطلبات والمزامنة. يرجى إدخال الرابط الرسمي لمنصة تكوين.
                            </div>
                        </div>

                        <!-- API Bearer Token -->
                        <div class="mb-4">
                            <label for="api_token" class="form-label fw-bold text-dark mb-2" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-key text-muted me-1"></i> رمز التوثيق الآمن (API Bearer Token)
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control form-control-lg border-2 border-end-0 rounded-start-3" 
                                       id="api_token" 
                                       name="api_token" 
                                       value="<?= !empty($settings['api_token']) ? \App\Helpers\EncryptionHelper::maskedKey($settings['api_token']) : '' ?>" 
                                       placeholder="<?= !empty($settings['api_token']) ? '••••••••••••••••••••••••••••••••' : 'Bearer Token' ?>"
                                       style="font-family: 'Outfit', sans-serif; font-size: 0.95rem; direction: ltr; text-align: left;">
                                <button class="btn btn-outline-secondary border-2 border-start-0 px-3 rounded-end-3" type="button" id="toggleTokenBtn" onclick="toggleTokenVisibility()">
                                    <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted mt-2">
                                <i class="fa-solid fa-shield-halved text-success me-1"></i>
                                سيتم تشفير رمز التوثيق تلقائياً في قاعدة البيانات باستخدام خوارزمية <strong>AES-256-CBC</strong> والمفتاح الماستر لتأمين الاتصال بنسبة 100%.
                            </div>
                        </div>

                        <!-- Sync toggles and configurations -->
                        <div class="p-3 rounded mb-4" style="background-color: var(--bg-dashboard); border: 1px solid var(--border-color); border-radius: 12px !important;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white p-2 rounded" style="--bs-bg-opacity: .12;">
                                        <i class="fa-solid fa-arrows-spin text-primary animate-spin"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block" style="font-size: 0.88rem;">التفعيل والتزامن التلقائي</span>
                                        <small class="text-muted" style="font-size: 0.72rem;">تمكين النظام من جلب البيانات وجدولتها تلقائياً وبصفة دورية</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-0 m-0">
                                    <input class="form-check-input ms-0 me-2" 
                                           type="checkbox" 
                                           role="switch" 
                                           name="sync_enabled" 
                                           value="1" 
                                           id="sync_enabled" 
                                           <?= $settings['sync_enabled'] ? 'checked' : '' ?> 
                                           style="width: 48px; height: 24px; cursor: pointer;">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold" style="border-radius: 10px; font-family: 'Cairo';">
                                <i class="fa-solid fa-floppy-disk me-1"></i> حفظ إعدادات الاتصال والربط
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side: Live sync operations & Bento stats -->
        <div class="col-xl-5">
            <div class="d-flex flex-column gap-4 h-100">
                <!-- Sync Status Card (Bento Style) -->
                <div class="card border-0 shadow-sm" style="border-radius: 16px; background: linear-gradient(135deg, var(--primary-glow) 0%, transparent 100%);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3" style="font-family: 'Cairo'; color: var(--text-main);">
                            <i class="fa-solid fa-network-wired text-primary me-2"></i>
                            حالة مزامنة البيانات الحالية
                        </h5>

                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="p-3 rounded-circle" style="background-color: <?= $settings['last_sync_status'] === 'success' ? 'rgba(40, 167, 69, 0.15)' : ($settings['last_sync_status'] === 'failed' ? 'rgba(220, 53, 69, 0.15)' : 'rgba(108, 117, 125, 0.15)') ?>;">
                                <i class="fa-solid <?= $settings['last_sync_status'] === 'success' ? 'fa-circle-check text-success' : ($settings['last_sync_status'] === 'failed' ? 'fa-triangle-exclamation text-danger' : 'fa-clock text-muted') ?> fa-2x"></i>
                            </div>
                            <div>
                                <span class="text-muted small fw-bold d-block">الحالة الأخيرة للتزامن</span>
                                <h4 class="fw-bold m-0" style="font-family: 'Cairo'; color: <?= $settings['last_sync_status'] === 'success' ? '#28a745' : ($settings['last_sync_status'] === 'failed' ? '#dc3545' : 'var(--text-main)') ?>;">
                                    <?= $settings['last_sync_status'] === 'success' ? 'مزامنة ناجحة / Activé' : ($settings['last_sync_status'] === 'failed' ? 'فشل التزامن / Erreur' : 'غير متصل بعد / Inactif') ?>
                                </h4>
                            </div>
                        </div>

                        <div class="p-3 rounded mb-3" style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(26, 107, 204, 0.08);">
                            <span class="text-muted small fw-bold d-block mb-1">آخر تقرير للمزامنة:</span>
                            <p class="m-0 small text-dark fw-semibold">
                                <?= $settings['last_sync_message'] ?? 'لا توجد عمليات مزامنة سابقة مسجلة بالمنصة حتى الآن.' ?>
                            </p>
                        </div>

                        <div class="d-flex justify-content-between align-items-center small text-muted">
                            <span>تاريخ آخر اتصال ناجح:</span>
                            <span class="fw-bold text-dark" style="font-family: 'Outfit';">
                                <?= $settings['last_sync_at'] ? date('Y-m-d H:i:s', strtotime($settings['last_sync_at'])) : '—' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Instant Trigger Actions -->
                <div class="card border-0 shadow-sm flex-grow-1" style="border-radius: 16px;">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-play text-success"></i>
                            <h5 class="fw-bold m-0" style="font-family: 'Cairo';">أدوات التشغيل اليدوي والتحقق</h5>
                        </div>
                    </div>

                    <div class="card-body p-4 d-flex flex-column justify-content-between gap-3">
                        <p class="text-muted small m-0">
                            بإمكانك إطلاق عملية المزامنة الفورية لجلب طلبات التسجيل الجديدة من منصة takwin.dz أو اختبار الاتصال بالخادم الافتراضي.
                        </p>

                        <!-- Information Alert about Inscription Flow -->
                        <div class="alert alert-info border-0 p-3 mb-0 small" style="background-color: rgba(13, 202, 240, 0.08); color: #087990; border-radius: 10px;">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            <strong>ملاحظة هامة:</strong> تم إيقاف وتعطيل استمارة التسجيل الأولي العامة المباشرة بالمنصة بنجاح لمنع التكرار وتسهيل تدفق البيانات مركزياً من منصة <strong>takwin.dz</strong> كقناة معتمدة وحيدة.
                        </div>

                        <div class="d-flex flex-column gap-2 mt-auto">
                            <!-- Real Sync Form -->
                            <form action="{{ url('dashboard/settings/takwin/sync') }}" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                <button type="submit" class="btn btn-outline-primary btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2" style="border-radius: 10px; font-family: 'Cairo'; font-size: 0.92rem;">
                                    <i class="fa-solid fa-rotate"></i>
                                    بدء عملية المزامنة الفورية (الربط المباشر)
                                </button>
                            </form>

                            <!-- Simulated Sync Form for instant preview/testing -->
                            <form action="{{ url('dashboard/settings/takwin/sync') }}" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                                <input type="hidden" name="simulate" value="1">
                                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2" style="border-radius: 10px; font-family: 'Cairo'; font-size: 0.92rem; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);">
                                    <i class="fa-solid fa-vial"></i>
                                    تشغيل محاكاة المزامنة التجريبية (إدراج بيانات تجريبية)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<script>
    function toggleTokenVisibility() {
        var tokenInput = document.getElementById('api_token');
        var toggleIcon = document.getElementById('toggleIcon');
        
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            toggleIcon.className = 'fa-solid fa-eye-slash';
        } else {
            tokenInput.type = 'password';
            toggleIcon.className = 'fa-solid fa-eye';
        }
    }
</script>
@endsection
