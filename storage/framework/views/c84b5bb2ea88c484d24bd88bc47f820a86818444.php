<?php $__env->startSection('title', 'تحقق الهوية الثنائي (MFA Challenge) — SGFEP'); ?>
<?php $__env->startSection('content'); ?>
<div class="container animate__animated animate__fadeIn py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header border-0 text-white p-4 text-center" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-key text-primary fs-1"></i>
                    </div>
                    <h4 class="fw-bold mb-1" style="font-family:'Cairo';">التحقق الإضافي للهوية</h4>
                    <p class="mb-0 text-white-50 small">حسابك محمي بالمصادقة الثنائية (MFA)</p>
                </div>
                
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <p class="text-muted small">الرجاء إدخال الرمز المكون من 6 أرقام من تطبيق المصادقة الخاص بك، أو إدخال أحد رموز الاسترداد الاحتياطية المكون من 11 حرفاً.</p>
                    </div>

                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger border-0 rounded-3 mb-4 py-2">
                            <ul class="mb-0 small px-3">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="verify-form" method="POST" action="<?php echo e(request()->is('sig/*') ? '/sig/security/mfa/verify' : '/security/mfa/verify'); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="device_fingerprint" id="device_fingerprint">

                        <div class="mb-4">
                            <label for="otp" class="form-label fw-bold text-dark small" style="font-family:'Cairo';">رمز التحقق أو رمز الاسترداد:</label>
                            <input type="text" name="otp" id="otp" required autocomplete="off" autofocus class="form-control form-control-lg text-center fw-bold" placeholder="000000" maxlength="11" style="font-family:'Outfit'; font-size:1.6rem; letter-spacing: 2px; border-radius: 12px; border:2px solid #cbd5e1;">
                            <div class="form-text text-muted small mt-2">مثال: رمز OTP (6 أرقام) أو رمز استرداد (XXXXX-XXXXX)</div>
                        </div>

                        <!-- Trusted Device Checkbox -->
                        <div class="mb-4 form-check form-switch d-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="remember_device" id="remember_device" value="1">
                            <label class="form-check-label text-dark small fw-bold" for="remember_device" style="font-family:'Cairo'; margin-right: 0.5rem; cursor: pointer;">
                                تذكر هذا الجهاز لمدة 30 يوماً
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-4 fw-bold" style="background: linear-gradient(135deg, #3b82f6, #2563eb); border:none;">
                                <i class="fa-solid fa-unlock-keyhole me-2"></i> تأكيد الهوية والدخول
                            </button>
                            <a href="/logout" class="btn btn-link text-muted small mt-2">تسجيل الخروج والعودة لاحقاً</a>
                        </div>
                    </form>

                    <script>
                        // دالة توليد البصمة الرقمية المتقدمة عالية الدقة (High-Entropy Client-Side Fingerprint)
                        async function generateDeviceFingerprint() {
                            const components = [];

                            try {
                                // 1. تفاصيل المتصفح والنظام الأساسية
                                components.push(navigator.userAgent);
                                components.push(navigator.language);
                                components.push(navigator.languages ? navigator.languages.join(',') : '');
                                components.push(screen.width + 'x' + screen.height);
                                components.push(screen.colorDepth);
                                components.push(screen.pixelDepth);
                                components.push(navigator.hardwareConcurrency || 'unknown');
                                components.push(navigator.deviceMemory || 'unknown');
                                components.push(navigator.platform || 'unknown');
                                components.push(navigator.cookieEnabled ? 'cookies:1' : 'cookies:0');
                                components.push(navigator.maxTouchPoints || 0);

                                // 2. اسم المنطقة الزمنية الجغرافية الدقيقة (مثال: Africa/Algiers)
                                try {
                                    components.push(Intl.DateTimeFormat().resolvedOptions().timeZone);
                                } catch (tzErr) {
                                    components.push(new Date().getTimezoneOffset());
                                }

                                // 3. بصمة لوحة الرسم (Canvas Fingerprinting) - رندرة نصوص وظلال مخصصة
                                try {
                                    const canvas = document.createElement('canvas');
                                    const ctx = canvas.getContext('2d');
                                    canvas.width = 200;
                                    canvas.height = 50;
                                    ctx.textBaseline = "top";
                                    ctx.font = "14px 'Arial', sans-serif";
                                    ctx.fillStyle = "#f60";
                                    ctx.fillRect(125, 1, 62, 20);
                                    ctx.fillStyle = "#069";
                                    ctx.fillText("SGFEP-Security,;!@#", 2, 15);
                                    ctx.fillStyle = "rgba(102, 204, 0, 0.7)";
                                    ctx.fillText("SGFEP-Security,;!@#", 4, 17);
                                    // إضافة تدرج لوني دقيق
                                    const grad = ctx.createLinearGradient(0, 0, canvas.width, 0);
                                    grad.addColorStop(0, "red");
                                    grad.addColorStop(1, "blue");
                                    ctx.fillStyle = grad;
                                    ctx.fillRect(10, 32, 80, 8);
                                    
                                    components.push(canvas.toDataURL());
                                } catch (canvasErr) {
                                    components.push('canvas-error');
                                }

                                // 4. بصمة معالج الرسوميات (WebGL GPU / Vendor Fingerprinting)
                                try {
                                    const canvas = document.createElement('canvas');
                                    const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                                    if (gl) {
                                        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                                        if (debugInfo) {
                                            components.push(gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL));
                                            components.push(gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL));
                                        }
                                        components.push(gl.getParameter(gl.VERSION));
                                        components.push(gl.getParameter(gl.SHADING_LANGUAGE_VERSION));
                                    } else {
                                        components.push('no-webgl');
                                    }
                                } catch (webglErr) {
                                    components.push('webgl-error');
                                }
                            } catch (err) {
                                components.push('general-error-' + err.message);
                            }

                            // 5. تشفير وحوسبة المكونات مجمعة باستخدام خوارزمية SHA-256
                            const dataString = components.join('###');
                            const msgBuffer = new TextEncoder().encode(dataString);
                            const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
                            const hashArray = Array.from(new Uint8Array(hashBuffer));
                            const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                            return hashHex;
                        }

                        // تعبئة الحقل المخفي قبل إرسال النموذج
                        document.getElementById('verify-form').addEventListener('submit', async function(e) {
                            e.preventDefault(); // منع الإرسال الفوري المؤقت
                            const form = this;
                            const fpInput = document.getElementById('device_fingerprint');
                            
                            try {
                                const fingerprint = await generateDeviceFingerprint();
                                fpInput.value = fingerprint;
                            } catch (err) {
                                // كخيار احتياطي إذا فشل التشفير، نرسل قيمة عشوائية معتمدة على الوقت
                                fpInput.value = 'fallback-' + Date.now() + '-' + Math.random().toString(36).substring(2, 9);
                            }
                            
                            form.submit(); // إرسال النموذج بعد تعبئة البصمة
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sig\resources\views/admin/security/mfa/verify.blade.php ENDPATH**/ ?>