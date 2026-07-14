@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $list
 * @var array $stagiaires
 * @var array $employes
 * @var string $category
 */
$category = $category ?? 'all';
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <?php if ($category === 'stagiaire'): ?>
                    <i class="fa-solid fa-user-graduate text-primary me-2"></i> بوابة استخراج وثائق المتربصين والطلبة
                <?php elseif ($category === 'employe'): ?>
                    <i class="fa-solid fa-user-tie text-success me-2"></i> بوابة استخراج وثائق الموظفين والأساتذة
                <?php else: ?>
                    <i class="fa-solid fa-print text-primary me-2"></i> نظام استخراج المطبوعات والشهادات الإدارية
                <?php endif; ?>
            </h3>
            <p class="text-muted mb-0 small">
                <?php if ($category === 'stagiaire'): ?>
                    استخراج فوري وبقوة القانون للشهادات المدرسية، شهادات التسجيل، قرارات الإسقاط، وكشوف النقاط من قاعدة البيانات 100%
                <?php elseif ($category === 'employe'): ?>
                    استخراج فوري وبقوة القانون لشهادات العمل وكشوف الرواتب الخاصة بالموظفين والأساتذة من قاعدة البيانات 100%
                <?php else: ?>
                    استخراج فوري للشهادات المدرسية، شهادات التسجيل، شهادات العمل للعمال، وكشوف النقاط مؤمنة برمز QR
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Actions Header Area (Form + Quick Verification Widget) -->
    <div class="row g-4 mb-4">
        <!-- Document Generation Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0 text-dark" style="font-family:'Cairo';">
                        <i class="fa-solid fa-file-invoice text-primary me-2"></i> توليد مستند ومطبوع إداري جديد
                    </h5>
                    <p class="text-muted small mb-0 mt-1">اختر فئة المستفيد والاسم ونوع الوثيقة لتوليدها فورياً</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ url('dashboard/documents/demander') }}" method="POST" id="requestDocForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="user_type" class="form-label fw-bold text-secondary small">فئة المستفيد</label>
                                <select class="form-select rounded-3 border-light bg-light" id="user_type" name="user_type" required onchange="toggleUserSelect(this.value)">
                                    <option value="" disabled selected>-- اختر فئة المستفيد --</option>
                                    <option value="stagiaire">متربص متكون (Student)</option>
                                    <option value="employe">موظف / عامل (Employee)</option>
                                </select>
                            </div>

                            <!-- Stagiaire select -->
                            <div class="col-md-4 d-none" id="stagiaire_group">
                                <label for="user_id_stag" class="form-label fw-bold text-secondary small">اختر المتربص</label>
                                <select class="form-select rounded-3 border-light bg-light" id="user_id_stag" name="user_id_stag">
                                    <option value="" disabled selected>-- اختر متربص --</option>
                                    <?php foreach ($stagiaires as $st): ?>
                                        <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['nom_ar'] . ' ' . $st['prenom_ar'] . ' (رقم: ' . $st['numero_matricule'] . ')') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Employee select -->
                            <div class="col-md-4 d-none" id="employe_group">
                                <label for="user_id_emp" class="form-label fw-bold text-secondary small">اختر الموظف / الأستاذ</label>
                                <select class="form-select rounded-3 border-light bg-light" id="user_id_emp" name="user_id_emp">
                                    <option value="" disabled selected>-- اختر موظف --</option>
                                    <?php foreach ($employes as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nom_complet'] . ' — ر.ت: ' . ($emp['username'] ?? $emp['grade'] ?? 'غير محدد')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Hidden user_id input populated by JS -->
                            <input type="hidden" name="user_id" id="hidden_user_id" required>

                            <div class="col-md-4">
                                <label for="document_type" class="form-label fw-bold text-secondary small">نوع الوثيقة المطلوبة</label>
                                <select class="form-select rounded-3 border-light bg-light" id="document_type" name="document_type" required>
                                    <option value="" disabled selected>-- اختر نوع المطبوع --</option>
                                    <option value="certificat_scolaire" class="stag-doc d-none">شهادة مدرسية (Certificat Scolaire)</option>
                                    <option value="attestation_inscription" class="stag-doc d-none">شهادة تسجيل (Attestation Inscription)</option>
                                    <option value="bulletin_notes" class="stag-doc d-none">كشف النقاط السداسي (Bulletin de Notes)</option>
                                    <option value="decision_isqat" class="stag-doc d-none">قرار إسقاط بيداغوجي (Décision d'Exclusion)</option>
                                    <option value="basma_mouahada" class="stag-doc d-none">البصمة الرقمية الموحدة (Empreinte Digitale)</option>
                                    <option value="attestation_travail" class="emp-doc d-none">شهادة عمل (Attestation de Travail)</option>
                                </select>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; height: 41px;">
                                    <i class="fa-solid fa-file-signature me-1"></i> توليد الوثيقة
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Document Verification Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #1e1b4b 0%, #31106a 100%); color: white;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="rounded-3 bg-white bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-shield-halved fs-5 text-warning animate__animated animate__pulse animate__infinite"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-white" style="font-family:'Cairo';">بوابة التحقق السريع للوثائق</h5>
                        </div>
                        <p class="text-white-50 small mb-4">مطابقة أي كود أو رمز تحقق رقمي (مثال: BULL-2026-00010) والاطلاع على تفاصيل الوثيقة فورياً.</p>
                        
                        <form action="{{ url('verify') }}" method="GET" target="_blank" id="quickVerifyForm">
                            <div class="mb-3">
                                <label for="verify_code_input" class="form-label text-white-50 small fw-bold">رمز التحقق الرقمي</label>
                                <input type="text" 
                                       id="verify_code_input" 
                                       name="code" 
                                       class="form-control rounded-3 border-0 bg-white bg-opacity-10 text-white fw-bold px-3 py-2" 
                                       placeholder="أدخل الكود هنا..." 
                                       style="font-family:'Outfit','Cairo',sans-serif; letter-spacing: 0.5px;"
                                       required>
                            </div>
                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold w-100 text-dark mt-2">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> فحص ومطابقة الوثيقة
                            </button>
                        </form>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top border-white border-opacity-10 text-center">
                        <span class="small text-white-50"><i class="fa-solid fa-lock text-success me-1"></i> أرشيف رقمي مؤمن وبقوة القانون</span>
                    </div>
                </div>
            </div>
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

    <!-- Bento Grid Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي المستخرجات والشهادات الصادرة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['total']) ?> وثيقة</h2>
                    <span class="small"><i class="fa-solid fa-shield-halved"></i> مؤمنة ورقمية بالكامل</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">جاهزة للطباعة والتوقيع</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= number_format($stats['pret']) ?> شهادة</h2>
                    <span class="small text-muted"><i class="fa-solid fa-circle-check text-success"></i> جاهزة للتصدير الورقي</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">طلبات قيد المراجعة الإدارية</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['en_attente']) ?> طلب</h2>
                    <span class="small text-muted"><i class="fa-solid fa-spinner fa-spin text-warning"></i> تتطلب مطابقة بيداغوجية قبل الإصدار</span>
                </div>
            </div>
        </div>
    </div>

    <!-- New Section: Advanced Document Search & Extraction Portal -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold mb-0 text-dark" style="font-family:'Cairo';">
                <i class="fa-solid fa-file-pdf text-danger me-2"></i> بوابة استخراج الوثائق الرسمية الفورية
            </h5>
            <p class="text-muted small mb-0 mt-1">تصفية متطورة حسب الولاية والمركز للوصول لأي متكون أو موظف واستخراج وثائقه بصيغة PDF فورياً</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Wilaya Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary small"><i class="fa-solid fa-map-marker-alt text-primary me-1"></i> الولاية</label>
                    <select id="portal_wilaya" class="form-select rounded-3 border-light bg-light" style="font-family:'Cairo'; font-weight: 600;">
                        <option value="">-- اختر الولاية --</option>
                    </select>
                </div>

                <!-- Center Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary small"><i class="fa-solid fa-building-columns text-primary me-1"></i> المركز التكويني</label>
                    <select id="portal_etab" class="form-select rounded-3 border-light bg-light" style="font-family:'Cairo'; font-weight: 600;" disabled>
                        <option value="">-- اختر المركز --</option>
                    </select>
                </div>

                <!-- Training Mode Filter -->
                <div class="col-md-3 <?= $category === 'employe' ? 'd-none' : '' ?>" id="portal_mode_col">
                    <label class="form-label fw-bold text-secondary small"><i class="fa-solid fa-graduation-cap text-primary me-1"></i> نمط التكوين</label>
                    <select id="portal_mode" class="form-select rounded-3 border-light bg-light" style="font-family:'Cairo'; font-weight: 600;">
                        <option value="">-- كل الأنماط --</option>
                    </select>
                </div>

                <!-- Beneficiary Category -->
                <div class="col-md-3 <?= $category !== 'all' ? 'd-none' : '' ?>">
                    <label class="form-label fw-bold text-secondary small"><i class="fa-solid fa-users text-primary me-1"></i> فئة المستفيد</label>
                    <select id="portal_user_type" class="form-select rounded-3 border-light bg-light" style="font-family:'Cairo'; font-weight: 600;">
                        <option value="stagiaire" <?= $category === 'stagiaire' ? 'selected' : '' ?>>متربص متكون</option>
                        <option value="employe" <?= $category === 'employe' ? 'selected' : '' ?>>موظف / أستاذ</option>
                    </select>
                </div>

                <!-- Branch Filter -->
                <div class="col-md-4 <?= $category === 'employe' ? 'd-none' : '' ?>" id="portal_branch_col">
                    <label class="form-label fw-bold text-secondary small"><i class="fa-solid fa-layer-group text-primary me-1"></i> الشعبة المهنية</label>
                    <select id="portal_branch" class="form-select rounded-3 border-light bg-light" style="font-family:'Cairo'; font-weight: 600;">
                        <option value="">-- كل الشعب --</option>
                    </select>
                </div>

                <!-- Specialty Filter -->
                <div class="col-md-4 <?= $category === 'employe' ? 'd-none' : '' ?>" id="portal_specialty_col">
                    <label class="form-label fw-bold text-secondary small"><i class="fa-solid fa-book-open text-primary me-1"></i> التخصص الدراسي</label>
                    <select id="portal_specialty" class="form-select rounded-3 border-light bg-light" style="font-family:'Cairo'; font-weight: 600;">
                        <option value="">-- كل التخصصات --</option>
                    </select>
                </div>

                <!-- User Search Input -->
                <div class="col-md-4">
                    <label class="form-label fw-bold text-secondary small"><i class="fa-solid fa-user-tag text-primary me-1"></i> اسم المستفيد أو رقم التسجيل</label>
                    <div class="position-relative">
                        <input type="text" id="portal_search_user" class="form-control rounded-3 border-light bg-light" placeholder="اكتب للبحث..." autocomplete="off" style="font-family:'Cairo';" disabled>
                        <span id="search_spinner" class="position-absolute end-0 top-50 translate-middle-y me-3 d-none">
                            <i class="fa-solid fa-spinner fa-spin text-primary"></i>
                        </span>
                        <!-- Search Dropdown Results -->
                        <div id="search_results_dropdown" class="dropdown-menu w-100 border-0 shadow-lg mt-1 p-0 rounded-3 overflow-hidden" style="max-height: 250px; overflow-y: auto; display: none; z-index: 1000; direction: rtl; text-align: right;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- New: Dynamic Users Table -->
            <div id="portal_users_list_container" class="mt-4 d-none">
                <h6 class="fw-bold mb-3 text-dark" style="font-family:'Cairo';">
                    <i class="fa-solid fa-users text-primary me-2"></i> قائمة المنتسبين المتاحة بالمركز
                </h6>
                <div class="table-responsive rounded-4 border border-light shadow-sm bg-white overflow-hidden">
                    <table class="table table-hover align-middle mb-0" style="direction: rtl; text-align: right; font-family:'Cairo';">
                        <thead class="bg-light text-muted small fw-bold">
                            <tr>
                                <th class="ps-3 text-secondary" style="width: 25%;">الاسم واللقب</th>
                                <th class="text-secondary" style="width: 20%;">رقم التسجيل / المعرف</th>
                                <th class="text-secondary" style="width: 20%;">التخصص / الرتبة</th>
                                <th class="pe-3 text-end text-secondary" style="width: 35%;">استخراج وثيقة فورية</th>
                            </tr>
                        </thead>
                        <tbody id="portal_users_list_body">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dynamic Beneficiary Card & PDF Actions -->
            <div id="beneficiary_panel" class="mt-4 p-4 rounded-4 border-0 d-none" style="background: rgba(72, 43, 143, 0.03); border: 1px dashed rgba(72, 43, 143, 0.1) !important;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-4 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, #482b8f 0%, #643edb 100%) !important;">
                                <i id="beneficiary_avatar_icon" class="fa-solid fa-user-graduate fs-3"></i>
                            </div>
                            <div>
                                <h5 id="beneficiary_name" class="fw-bold text-dark mb-1" style="font-family:'Cairo';">--</h5>
                                <p class="text-muted mb-0 small">
                                    <span id="beneficiary_meta1" class="me-3"></span>
                                    <span id="beneficiary_meta2"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <h6 class="fw-bold text-secondary mb-2 small"><i class="fa-solid fa-file-export me-1 text-primary"></i> المطبوعات المتاحة للاستخراج الفوري:</h6>
                        <div id="pdf_actions_container" class="d-flex flex-wrap gap-2 justify-content-end"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Printable Categories Cards -->
    <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-folder-open text-primary me-2"></i> المطبوعات الرسمية المتاحة على المنصة</h5>
    <div class="row g-3 mb-4">
        <?php if ($category === 'all' || $category === 'stagiaire'): ?>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-2 bg-light-hover" style="background: white; border: 1px solid var(--border-portal) !important; transition: all 0.2s;" id="card_certificat">
                    <div class="card-body p-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-user-graduate fs-4 text-primary"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">شهادة مدرسية / Certificat</h6>
                        <p class="text-muted small mb-0" style="font-size: 0.72rem;">لإثبات الوضعية المدرسية للمتربصين النشطين</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-2 bg-light-hover" style="background: white; border: 1px solid var(--border-portal) !important; transition: all 0.2s;" id="card_inscription">
                    <div class="card-body p-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-id-card fs-4 text-success"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">شهادة التسجيل / Inscription</h6>
                        <p class="text-muted small mb-0" style="font-size: 0.72rem;">لإثبات تسجيل المترشحين الجدد في الدورة</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($category === 'all' || $category === 'employe'): ?>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-2 bg-light-hover" style="background: white; border: 1px solid var(--border-portal) !important; transition: all 0.2s;" id="card_travail">
                    <div class="card-body p-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-briefcase fs-4 text-warning"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">شهادة عمل / Attestation</h6>
                        <p class="text-muted small mb-0" style="font-size: 0.72rem;">للأساتذة والموظفين الإداريين بالقطاع</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($category === 'all' || $category === 'stagiaire'): ?>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-2 bg-light-hover" style="background: white; border: 1px solid var(--border-portal) !important; transition: all 0.2s;" id="card_notes">
                    <div class="card-body p-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-list-ol fs-4 text-danger"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">كشف النقاط / Bulletin</h6>
                        <p class="text-muted small mb-0" style="font-size: 0.72rem;">كشف مفصل للعلامات والتقييم السداسي</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-2 bg-light-hover" style="background: white; border: 1px solid var(--border-portal) !important; transition: all 0.2s;" id="card_isqat">
                    <div class="card-body p-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-user-slash fs-4 text-danger animate__animated animate__pulse animate__infinite"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">قرار إسقاط بيداغوجي</h6>
                        <p class="text-muted small mb-0" style="font-size: 0.72rem;">وثيقة شطب أو إسقاط نهائي للمتربص</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-2 bg-light-hover" style="background: white; border: 1px solid var(--border-portal) !important; transition: all 0.2s;" id="card_basma">
                    <div class="card-body p-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-fingerprint fs-4 text-info animate__animated animate__pulse animate__infinite"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">البصمة الرقمية الموحدة</h6>
                        <p class="text-muted small mb-0" style="font-size: 0.72rem;">بطاقة البصمة الحيوية الرقمية للمنتسب</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> السجل التاريخي لاستخراج المستندات والشهادات</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('docsTable', 'documents_generes.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('docsTable', 'documents_generes.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="docsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المستفيد / طالب الوثيقة</th>
                            <th>نوع المطبوع الإداري</th>
                            <th class="text-center">رمز التحقق الرقمي</th>
                            <th class="text-center">تاريخ التوليد</th>
                            <th class="text-center">عدد مرات الطباعة</th>
                            <th class="pe-4 text-end no-print no-export">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-file-circle-exclamation fs-1 mb-3"></i>
                                    <p class="mb-0">لا توجد طلبات أو شهادات صادرة حالياً.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $doc): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($doc['demandeur_nom'] ?? 'غير معرف') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($doc['identifier'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <?php 
                                            switch($doc['document_type']) {
                                                case 'certificat_scolaire': echo 'شهادة مدرسية'; break;
                                                case 'decision_isqat': echo 'قرار إسقاط بيداغوجي'; break;
                                                case 'basma_mouahada': echo 'البصمة الرقمية الموحدة'; break;
                                                case 'attestation_inscription': echo 'شهادة التسجيل'; break;
                                                case 'attestation_travail': echo 'شهادة عمل'; break;
                                                case 'bulletin_notes': echo 'كشف النقاط السداسي'; break;
                                            }
                                            ?>
                                        </div>
                                        <span class="text-muted small">فئة: <?= $doc['user_type'] === 'stagiaire' ? 'متربص' : 'موظف' ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border border-dark rounded-pill px-3 py-2 fw-bold" style="font-family: 'Outfit', sans-serif;">
                                            <i class="fa-solid fa-shield text-success me-1"></i> <?= htmlspecialchars($doc['code_verification']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-muted small"><?= htmlspecialchars(substr($doc['request_date'], 0, 10)) ?></td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($doc['print_count']) ?> مرات</td>
                                    <td class="pe-4 text-end no-print no-export">
                                        <?php if ($doc['statut'] === 'pret'): ?>
                                            <a href="{{ url('dashboard/documents/print') }}/<?= \App\Helpers\SecureIdHelper::encrypt($doc['id']) ?>?type=<?= $doc['user_type'] ?>&doc=<?= $doc['document_type'] ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                                                <i class="fa-solid fa-print me-1"></i> طباعة فورية
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-clock fa-spin me-1"></i> قيد المراجعة</span>
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

<script>
function toggleUserSelect(val) {
    const stagGroup = document.getElementById('stagiaire_group');
    const empGroup = document.getElementById('employe_group');
    const stagSelect = document.getElementById('user_id_stag');
    const empSelect = document.getElementById('user_id_emp');
    
    // Reset selections
    stagSelect.value = '';
    empSelect.value = '';
    document.getElementById('hidden_user_id').value = '';

    // Dynamically fetch and populate list of users for the selected center
    const etabId = document.getElementById('portal_etab').value;
    const wilayaId = document.getElementById('portal_wilaya').value;
    const modeId = document.getElementById('portal_mode') ? document.getElementById('portal_mode').value : '';
    
    const targetSelect = (val === 'stagiaire') ? stagSelect : empSelect;

    // Show/hide groups immediately
    if (val === 'stagiaire') {
        stagGroup.classList.remove('d-none');
        empGroup.classList.add('d-none');
        stagSelect.setAttribute('required', 'required');
        empSelect.removeAttribute('required');
    } else if (val === 'employe') {
        stagGroup.classList.add('d-none');
        empGroup.classList.remove('d-none');
        empSelect.setAttribute('required', 'required');
        stagSelect.removeAttribute('required');
    } else {
        stagGroup.classList.add('d-none');
        empGroup.classList.add('d-none');
    }
    
    if (val === 'stagiaire' || val === 'employe') {
        if (!etabId) {
            targetSelect.innerHTML = `<option value="" disabled selected>يرجى اختيار الولاية والمركز في الصفحة أولاً</option>`;
            return;
        }
        targetSelect.innerHTML = `<option value="" disabled selected>جاري تحميل القائمة...</option>`;
        
        fetch('{{ url("dashboard/documents/ajax/users") }}?wilaya_id=' + wilayaId + '&etab_id=' + etabId + '&mode_id=' + modeId + '&user_type=' + val)
            .then(res => res.json())
            .then(data => {
                targetSelect.innerHTML = `<option value="" disabled selected>-- اختر ${val === 'stagiaire' ? 'طالب' : 'موظف'} --</option>`;
                data.forEach(user => {
                    const opt = document.createElement('option');
                    opt.value = user.id;
                    if (val === 'stagiaire') {
                        opt.textContent = user.nom_ar + ' ' + (user.prenom_ar || '') + ' (رقم: ' + (user.numero_matricule || '') + ')';
                    } else {
                        opt.textContent = user.nom_ar + ' ' + (user.prenom_ar || '') + ' — ر.ت: ' + (user.numero_matricule || '');
                    }
                    targetSelect.appendChild(opt);
                });
            })
            .catch(err => {
                targetSelect.innerHTML = `<option value="" disabled selected>خطأ في تحميل القائمة</option>`;
            });
    }

    // Show/hide documents options based on category
    document.querySelectorAll('.stag-doc').forEach(el => {
        if(val === 'stagiaire') {
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    });

    document.querySelectorAll('.emp-doc').forEach(el => {
        if(val === 'employe') {
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    });

    // Reset document type selection
    document.getElementById('document_type').value = '';
}

// Update hidden user_id input when active select changes
document.getElementById('user_id_stag').addEventListener('change', function() {
    document.getElementById('hidden_user_id').value = this.value;
});
document.getElementById('user_id_emp').addEventListener('change', function() {
    document.getElementById('hidden_user_id').value = this.value;
});

// ==========================================
// ─── DYNAMIC DOCUMENT EXTRACTION PORTAL JS ───
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const userRole = '{{ strtolower(session("user")["role_code"] ?? "") }}';
    
    const portalWilaya = document.getElementById('portal_wilaya');
    const portalEtab = document.getElementById('portal_etab');
    const portalMode = document.getElementById('portal_mode');
    const portalBranch = document.getElementById('portal_branch');
    const portalSpecialty = document.getElementById('portal_specialty');
    const portalUserType = document.getElementById('portal_user_type');
    const portalSearchUser = document.getElementById('portal_search_user');
    const searchSpinner = document.getElementById('search_spinner');
    const searchDropdown = document.getElementById('search_results_dropdown');
    
    const beneficiaryPanel = document.getElementById('beneficiary_panel');
    const beneficiaryName = document.getElementById('beneficiary_name');
    const beneficiaryMeta1 = document.getElementById('beneficiary_meta1');
    const beneficiaryMeta2 = document.getElementById('beneficiary_meta2');
    const beneficiaryAvatarIcon = document.getElementById('beneficiary_avatar_icon');
    const pdfActionsContainer = document.getElementById('pdf_actions_container');
    
    let debounceTimer;

    function triggerUserSearch(force = false) {
        const query = portalSearchUser.value.trim();
        
        const usersListContainer = document.getElementById('portal_users_list_container');
        const usersListBody = document.getElementById('portal_users_list_body');
        const etabId = portalEtab.value;

        if (query.length < 2 && !etabId && !force) {
            searchDropdown.style.display = 'none';
            if (usersListContainer) usersListContainer.classList.add('d-none');
            return;
        }

        clearTimeout(debounceTimer);
        searchSpinner.classList.remove('d-none');

        debounceTimer = setTimeout(() => {
            const modeId = portalMode ? portalMode.value : '';
            const branchId = portalBranch ? portalBranch.value : '';
            const specialtyId = portalSpecialty ? portalSpecialty.value : '';
            const userType = portalUserType.value;
            const wilayaId = portalWilaya.value;
            
            if (userRole !== 'admin' && userRole !== 'central' && userRole !== 'high_admin' && userRole !== 'dfep' && !etabId) {
                searchSpinner.classList.add('d-none');
                if (usersListContainer) usersListContainer.classList.add('d-none');
                return;
            }
            if (userRole === 'dfep' && !portalWilaya.value) {
                searchSpinner.classList.add('d-none');
                if (usersListContainer) usersListContainer.classList.add('d-none');
                return;
            }

            fetch('{{ url("dashboard/documents/ajax/users") }}?wilaya_id=' + wilayaId + '&etab_id=' + etabId + '&mode_id=' + modeId + '&branch_id=' + branchId + '&specialty_id=' + specialtyId + '&user_type=' + userType + '&search=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    searchSpinner.classList.add('d-none');
                    searchDropdown.innerHTML = '';
                    if (usersListBody) usersListBody.innerHTML = '';
                    
                    if (data.length === 0) {
                        const item = document.createElement('div');
                        item.className = 'dropdown-item text-muted text-center py-2';
                        item.textContent = 'لا توجد نتائج مطابقة';
                        searchDropdown.appendChild(item);

                        if (usersListBody) {
                            usersListBody.innerHTML = `
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-user-slash fs-3 mb-2 text-warning"></i>
                                        <div>لا توجد نتائج مطابقة في قاعدة البيانات لهذه الفئة أو المركز.</div>
                                    </td>
                                </tr>
                            `;
                        }
                    } else {
                        data.forEach(user => {
                            // 1. Dropdown matching item (redundant/hidden but kept for compatibility)
                            const item = document.createElement('a');
                            item.className = 'dropdown-item py-2 px-3 border-bottom border-light';
                            item.style.cursor = 'pointer';
                            
                            const displayName = user.nom_ar + ' ' + (user.prenom_ar || '');
                            const displayMeta = 'رقم: ' + (user.numero_matricule || '') + ' (' + (user.spec_ar || 'بدون تخصص') + ')';
                            
                            item.innerHTML = `
                                <div class="fw-bold text-dark">${displayName}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">${displayMeta}</div>
                            `;
                            
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                selectBeneficiary(user, userType);
                            });
                            
                            searchDropdown.appendChild(item);

                            // 2. Main list table row
                            if (usersListBody) {
                                const tr = document.createElement('tr');
                                tr.className = 'align-middle';
                                tr.style.cursor = 'pointer';
                                
                                tr.addEventListener('click', function(e) {
                                    if (e.target.closest('button')) return;
                                    selectBeneficiary(user, userType);
                                    
                                    document.querySelectorAll('#portal_users_list_body tr').forEach(r => r.classList.remove('table-active'));
                                    tr.classList.add('table-active');
                                    
                                    document.getElementById('beneficiary_panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                });

                                let actionButtons = '';
                                if (userType === 'stagiaire') {
                                    actionButtons = `
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'certificat_scolaire')" class="btn btn-sm btn-outline-primary fw-bold py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> شهادة مدرسية
                                            </button>
                                            <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'attestation_inscription')" class="btn btn-sm btn-outline-success fw-bold py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> شهادة تسجيل
                                            </button>
                                            <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'bulletin_notes')" class="btn btn-sm btn-outline-warning fw-bold py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> كشف النقاط
                                            </button>
                                            <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'decision_isqat')" class="btn btn-sm btn-outline-danger fw-bold py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-user-slash text-danger me-1"></i> قرار إسقاط
                                            </button>
                                            <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'basma_mouahada')" class="btn btn-sm btn-outline-info fw-bold py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-fingerprint text-info me-1"></i> بصمة موحدة
                                            </button>
                                        </div>
                                    `;
                                } else {
                                    actionButtons = `
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <button type="button" onclick="generateAndPrintPDF(${user.id}, 'employe', 'attestation_travail')" class="btn btn-sm btn-outline-primary fw-bold py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> شهادة عمل
                                            </button>
                                            <button type="button" onclick="generateAndPrintPDF(${user.id}, 'employe', 'fiche_paie')" class="btn btn-sm btn-outline-success fw-bold py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> كشف الراتب
                                            </button>
                                        </div>
                                    `;
                                }

                                tr.innerHTML = `
                                    <td class="ps-3 fw-bold text-dark">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-light text-primary" style="width: 32px; height: 32px; font-size: 0.9rem;">
                                                <i class="fa-solid ${userType === 'stagiaire' ? 'fa-user-graduate' : 'fa-user-tie'}"></i>
                                            </div>
                                            <div>
                                                <div class="text-dark text-wrap">${user.nom_ar} ${(user.prenom_ar || '')}</div>
                                                <div class="text-muted small text-wrap" style="font-size: 0.7rem;">${user.nom_fr || ''} ${(user.prenom_fr || '')}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-secondary" style="font-family:'Outfit'; font-size: 0.85rem;">
                                        ${user.numero_matricule || 'غير متوفر'}
                                    </td>
                                    <td class="text-secondary small">
                                        ${user.spec_ar || 'بدون تخصص'}
                                    </td>
                                    <td class="pe-3 text-end">
                                        ${actionButtons}
                                    </td>
                                `;
                                usersListBody.appendChild(tr);
                            }
                        });
                    }
                    searchDropdown.style.display = 'none'; // Keep dropdown hidden for cleaner UI
                    if (usersListContainer) usersListContainer.classList.remove('d-none');
                })
                .catch(err => {
                    searchSpinner.classList.add('d-none');
                });
        }, 300);
    }
    
    function refreshSearchState() {
        if (userRole === 'admin' || userRole === 'central' || userRole === 'high_admin') {
            portalSearchUser.disabled = false;
            portalSearchUser.placeholder = 'ابحث في كل المراكز والولايات...';
        } else if (userRole === 'dfep') {
            if (portalWilaya.value) {
                portalSearchUser.disabled = false;
                portalSearchUser.placeholder = 'ابحث في كل مراكز الولاية...';
            } else {
                portalSearchUser.disabled = true;
                portalSearchUser.placeholder = 'اختر الولاية أولاً للبحث...';
            }
        } else {
            if (portalEtab.value) {
                portalSearchUser.disabled = false;
                portalSearchUser.placeholder = 'اكتب اسم المستفيد للبحث...';
            } else {
                portalSearchUser.disabled = true;
                portalSearchUser.placeholder = 'اختر المركز أولاً للبحث...';
            }
        }
    }

    refreshSearchState();
    
    // Load initial Wilayas
    fetch('{{ url("dashboard/documents/ajax/wilayas") }}')
        .then(res => res.json())
        .then(data => {
            data.forEach(w => {
                const opt = document.createElement('option');
                opt.value = w.id;
                opt.textContent = w.nom + ' (' + w.nom_fr + ')';
                portalWilaya.appendChild(opt);
            });
            
            // If user only has one Wilaya (due to scoping), select it and load centers
            if (data.length === 1) {
                portalWilaya.value = data[0].id;
                portalWilaya.dispatchEvent(new Event('change'));
                portalWilaya.disabled = true;
            }
        });

    if (portalMode) {
        // Load initial Training Modes
        fetch('{{ url("dashboard/documents/ajax/modes") }}')
            .then(res => res.json())
            .then(data => {
                data.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.libelle_ar;
                    portalMode.appendChild(opt);
                });
            });
            
        // Reset when training mode changes
        portalMode.addEventListener('change', function() {
            portalSearchUser.value = '';
            searchDropdown.style.display = 'none';
            beneficiaryPanel.classList.add('d-none');
            triggerUserSearch(true);
        });
    }

    function loadBranches() {
        if (!portalBranch) return;
        const etabId = portalEtab.value;
        
        fetch('{{ url("dashboard/documents/ajax/branches") }}?etab_id=' + etabId)
            .then(res => res.json())
            .then(data => {
                portalBranch.innerHTML = '<option value="">-- كل الشعب --</option>';
                data.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.nom;
                    portalBranch.appendChild(opt);
                });
                loadSpecialties();
            });
    }

    function loadSpecialties() {
        if (!portalSpecialty) return;
        const etabId = portalEtab.value;
        const branchId = portalBranch ? portalBranch.value : '';
        
        fetch('{{ url("dashboard/documents/ajax/specialties") }}?etab_id=' + etabId + '&branch_id=' + branchId)
            .then(res => res.json())
            .then(data => {
                portalSpecialty.innerHTML = '<option value="">-- كل التخصصات --</option>';
                data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.nom;
                    portalSpecialty.appendChild(opt);
                });
            });
    }

    if (portalBranch) {
        portalBranch.addEventListener('change', function() {
            loadSpecialties();
            portalSearchUser.value = '';
            searchDropdown.style.display = 'none';
            beneficiaryPanel.classList.add('d-none');
            triggerUserSearch(true);
        });
    }

    if (portalSpecialty) {
        portalSpecialty.addEventListener('change', function() {
            portalSearchUser.value = '';
            searchDropdown.style.display = 'none';
            beneficiaryPanel.classList.add('d-none');
            triggerUserSearch(true);
        });
    }

    // Call loadBranches initially to populate if established pre-selected
    loadBranches();

    // Wilaya change event
    portalWilaya.addEventListener('change', function() {
        const wilayaId = this.value;
        portalEtab.innerHTML = '<option value="">-- اختر المركز --</option>';
        portalEtab.disabled = true;
        portalSearchUser.value = '';
        searchDropdown.style.display = 'none';
        beneficiaryPanel.classList.add('d-none');
        
        loadBranches();
        refreshSearchState();
        triggerUserSearch(true);
        
        if (!wilayaId) return;

        fetch('{{ url("dashboard/documents/ajax/etablissements") }}?wilaya_id=' + wilayaId)
            .then(res => res.json())
            .then(data => {
                data.forEach(e => {
                    const opt = document.createElement('option');
                    opt.value = e.id;
                    opt.textContent = e.nom;
                    portalEtab.appendChild(opt);
                });
                portalEtab.disabled = false;
                
                // If user only has one establishment, select it
                if (data.length === 1) {
                    portalEtab.value = data[0].id;
                    portalEtab.dispatchEvent(new Event('change'));
                    portalEtab.disabled = true;
                }
            });
    });

    // Center change event
    portalEtab.addEventListener('change', function() {
        const etabId = this.value;
        portalSearchUser.value = '';
        searchDropdown.style.display = 'none';
        beneficiaryPanel.classList.add('d-none');
        
        loadBranches();
        refreshSearchState();
        triggerUserSearch(true);
    });

    // User type change event
    portalUserType.addEventListener('change', function() {
        const userType = this.value;
        const modeCol = document.getElementById('portal_mode_col');
        const branchCol = document.getElementById('portal_branch_col');
        const specialtyCol = document.getElementById('portal_specialty_col');
        
        if (userType === 'employe') {
            if (modeCol) modeCol.classList.add('d-none');
            if (branchCol) branchCol.classList.add('d-none');
            if (specialtyCol) specialtyCol.classList.add('d-none');
        } else {
            if (modeCol) modeCol.classList.remove('d-none');
            if (branchCol) branchCol.classList.remove('d-none');
            if (specialtyCol) specialtyCol.classList.remove('d-none');
        }
        
        portalSearchUser.value = '';
        searchDropdown.style.display = 'none';
        beneficiaryPanel.classList.add('d-none');
        triggerUserSearch(true);
    });

    // Search user text input change & focus events
    portalSearchUser.addEventListener('input', function() {
        triggerUserSearch(false);
    });
    portalSearchUser.addEventListener('focus', function() {
        triggerUserSearch(true);
    });
    portalSearchUser.addEventListener('click', function() {
        triggerUserSearch(true);
    });

    // Select beneficiary & build available PDF list
    function selectBeneficiary(user, userType) {
        portalSearchUser.value = user.nom_ar + ' ' + user.prenom_ar;
        searchDropdown.style.display = 'none';
        
        beneficiaryName.textContent = user.nom_ar + ' ' + user.prenom_ar;
        
        if (userType === 'stagiaire') {
            beneficiaryAvatarIcon.className = 'fa-solid fa-user-graduate fs-3';
            beneficiaryMeta1.innerHTML = `<i class="fa-solid fa-id-card-clip text-primary me-1"></i> رقم التسجيل: <strong>${user.numero_matricule}</strong>`;
            beneficiaryMeta2.innerHTML = `<i class="fa-solid fa-graduation-cap text-success me-1"></i> التخصص: <strong>${user.spec_ar || 'بدون تخصص'}</strong>`;
            
            // Build buttons for Stagiaire documents
            pdfActionsContainer.innerHTML = `
                <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'certificat_scolaire')" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 py-2 mb-2">
                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> شهادة مدرسية
                </button>
                <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'attestation_inscription')" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3 py-2 mb-2">
                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> شهادة تسجيل
                </button>
                <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'bulletin_notes')" class="btn btn-sm btn-outline-warning fw-bold rounded-pill px-3 py-2 mb-2">
                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> كشف النقاط
                </button>
                <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'decision_isqat')" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3 py-2 mb-2">
                    <i class="fa-solid fa-user-slash text-danger me-1"></i> قرار إسقاط
                </button>
                <button type="button" onclick="generateAndPrintPDF(${user.id}, 'stagiaire', 'basma_mouahada')" class="btn btn-sm btn-outline-info fw-bold rounded-pill px-3 py-2 mb-2">
                    <i class="fa-solid fa-fingerprint text-info me-1"></i> بصمة موحدة
                </button>
            `;
        } else {
            beneficiaryAvatarIcon.className = 'fa-solid fa-user-tie fs-3';
            beneficiaryMeta1.innerHTML = `<i class="fa-solid fa-id-card-clip text-primary me-1"></i> رقم التعريف الوطني (NIN): <strong>${user.numero_matricule || 'غير متوفر'}</strong>`;
            beneficiaryMeta2.innerHTML = `<i class="fa-solid fa-briefcase text-success me-1"></i> الوظيفة/الرتبة: <strong>${user.spec_ar || 'موظف'}</strong>`;
            
            // Build buttons for Employee documents
            pdfActionsContainer.innerHTML = `
                <button type="button" onclick="generateAndPrintPDF(${user.id}, 'employe', 'attestation_travail')" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 py-2">
                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> شهادة عمل
                </button>
                <button type="button" onclick="generateAndPrintPDF(${user.id}, 'employe', 'fiche_paie')" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3 py-2">
                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> كشف الراتب
                </button>
            `;
        }
        
        beneficiaryPanel.classList.remove('d-none');
    }

    // Hide dropdown if clicked outside
    document.addEventListener('click', function(e) {
        if (!portalSearchUser.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.style.display = 'none';
        }
    });

    // Handle URL focus query parameters (e.g. ?focus=isqat)
    const urlParams = new URLSearchParams(window.location.search);
    const focusParam = urlParams.get('focus');
    if (focusParam === 'isqat' || focusParam === 'basma') {
        // Highlight the card
        const cardId = focusParam === 'isqat' ? 'card_isqat' : 'card_basma';
        const cardEl = document.getElementById(cardId);
        if (cardEl) {
            cardEl.style.border = '2px solid var(--electric)';
            cardEl.style.boxShadow = '0 0 15px var(--electric-glow)';
            cardEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Auto populate the inline form fields
        const userTypeSel = document.getElementById('user_type');
        if (userTypeSel) {
            setTimeout(() => {
                userTypeSel.value = 'stagiaire';
                toggleUserSelect('stagiaire');
                setTimeout(() => {
                    const docTypeSel = document.getElementById('document_type');
                    if (docTypeSel) {
                        docTypeSel.value = (focusParam === 'isqat') ? 'decision_isqat' : 'basma_mouahada';
                    }
                }, 300);
            }, 500);
        }
    }
});

// Global function for AJAX PDF creation
function generateAndPrintPDF(userId, userType, docType) {
    Swal.fire({
        title: 'جاري توليد الوثيقة الرسمية...',
        text: 'يرجى الانتظار بينما يتم توقيع الوثيقة رقمياً وحفظها في قاعدة البيانات',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    fetch('{{ url("dashboard/documents/demander") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            csrf_token: csrfToken,
            user_type: userType,
            user_id: userId,
            document_type: docType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم توليد الوثيقة بنجاح!',
                text: 'رمز التحقق الرقمي: ' + data.code,
                timer: 2000,
                showConfirmButton: false
            });
            
            // Open print template in a new window/tab
            window.open(data.print_url, '_blank');
            
            // Wait a bit, then refresh history logs list
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'فشل توليد الوثيقة',
                text: data.error || 'حدث خطأ غير متوقع.'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'خطأ في النظام',
            text: 'تعذر الاتصال بالخادم. يرجى المحاولة مجدداً.'
        });
    });
}
</script>

@endsection
