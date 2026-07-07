@extends('layouts.main')
@section('title', 'مركز الاتصال الرقمي — SGFEP')
@section('content')
<?php
$api_key = $api_key ?? null;
$stats   = $stats   ?? [];
$clients = $clients ?? [];
?>
<div class="animate__animated animate__fadeIn">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family:'Cairo';">
                <i class="fa-solid fa-key text-warning me-2"></i>
                مركز الاتصال الرقمي وتكامل الأنظمة
            </h3>
            <p class="text-muted small mb-0">إدارة مفاتيح API الخاصة بك للربط مع بوابات الخدمات الحكومية والشركاء الاقتصاديين</p>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
            <a href="/dashboard" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة
            </a>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold" style="font-size:0.8rem;">
                <i class="fa-solid fa-shield-halved me-1"></i> نظام مؤمن
            </span>
        </div>
    </div>

    <!-- Hero Banner -->
    <div class="card border-0 mb-4 text-white shadow-sm position-relative overflow-hidden"
         style="border-radius:20px;background:linear-gradient(135deg,#006233 0%,#1e3a5f 70%,#0d2137 100%);min-height:160px;">
         <div style="position:absolute;top:-40px;right:-40px;width:250px;height:250px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>
         <div style="position:absolute;bottom:-60px;left:-30px;width:200px;height:200px;background:rgba(255,255,255,0.03);border-radius:50%;"></div>
         <div class="card-body p-4 position-relative" style="z-index:2;">
             <div class="d-flex align-items-center gap-3 mb-3">
                 <div class="rounded-circle d-flex align-items-center justify-content-center"
                      style="width:52px;height:52px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);">
                     <i class="fa-solid fa-satellite-dish fs-4 text-warning"></i>
                 </div>
                 <div>
                     <h5 class="fw-bold mb-0" style="font-family:'Cairo';">Unified API Gateway — SGFEP</h5>
                     <span class="text-white-50 small">منصة تسيير • البنية الرقمية الوطنية</span>
                 </div>
             </div>
             <p class="text-white-50 small mb-0" style="max-width:600px;line-height:1.7;">
                 يتيح مركز API ربط المنصة بجميع الخدمات الحكومية الخارجية مثل منصة Takwin، HFSQL، وبوابات الوزارة. جميع الطلبات تخضع للتشفير الكامل وتسجيل العمليات.
             </p>
         </div>
    </div>

    <div class="row g-4">
        <!-- API Key Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius:20px;">
                <h5 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">
                    <i class="fa-solid fa-shield-halved text-success me-2"></i> مفتاح API الحالي (Bearer Token)
                </h5>
                <p class="text-muted small mb-4">هذا المفتاح سري وخاص بحسابك كمسؤول للنظام. لا تشاركه مع أي طرف خارجي.</p>

                <div class="p-3 rounded-4 mb-4" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <label class="form-label fw-bold text-muted small">
                        <i class="fa-solid fa-lock text-muted me-1"></i> مفتاح API الخاص (API Bearer Key)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-light">
                            <i class="fa-solid fa-key text-warning"></i>
                        </span>
                        <input type="text" class="form-control border-light bg-white fw-bold text-truncate"
                               id="user-apiKey"
                               value="{{ $api_key ?? 'لم يتم توليد مفتاح بعد.' }}" readonly
                               style="font-family:'Outfit';font-size:0.85rem;color:var(--primary-color, #006233);">
                        <button class="btn btn-outline-secondary border-light bg-white px-3"
                                type="button" onclick="copyApiKey()" title="نسخ إلى الحافظة">
                            <i class="fa-regular fa-copy text-primary"></i> نسخ
                        </button>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap mb-4">
                    <button class="btn btn-outline-primary rounded-pill px-4 fw-bold"
                            type="button"
                            data-user-id="{{ session('user')['id'] ?? '' }}"
                            data-csrf-token="{{ csrf_token() }}"
                            onclick="regenerateUserApiKey(this)">
                        <i class="fa-solid fa-arrows-rotate me-2 text-warning"></i> تجديد مفتاح API
                    </button>
                    <button class="btn btn-outline-danger rounded-pill px-4 fw-bold"
                            type="button"
                            onclick="revokeApiKey()">
                        <i class="fa-solid fa-trash-can me-2"></i> إلغاء المفتاح
                    </button>
                </div>

                <div class="mt-4 border-top pt-3">
                    <h6 class="fw-bold text-dark mb-2" style="font-family:'Cairo'; font-size: 0.9rem;">
                        <i class="fa-solid fa-code text-success me-1"></i> دليل الاستخدام السريع لمفتاح المسؤول الحالي:
                    </h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <select class="form-select form-select-sm rounded-3 fw-bold text-dark" id="admin-doc-select" onchange="showAdminDocSnippet()">
                                <option value="">-- اختر الجدول لعرض طريقة الاستخدام وأمر cURL --</option>
                                <option value="stagiaires">جدول المتربصين (Stagiaires)</option>
                                <option value="offres">جدول عروض التكوين (Offres)</option>
                                <option value="employees">جدول الموظفين والمؤطرين (Employees)</option>
                                <option value="formateurs">ساعات التكوين الشاغرة (Vacant Hours)</option>
                                <option value="finance">التقارير المالية والميزانية (Finance/Budget)</option>
                                <option value="assets">طلبات العتاد والورشات (Assets/Requests)</option>
                            </select>
                        </div>
                    </div>
                    <div id="admin-doc-snippet-container" style="display:none;" class="animate__animated animate__fadeIn">
                        <div id="admin-doc-snippet"></div>
                    </div>
                </div>
            </div>

            <!-- External Integration Platforms -->
            <div class="card border-0 shadow-sm p-4 mt-4" style="border-radius:20px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-0" style="font-family:'Cairo';">
                            <i class="fa-solid fa-server text-primary me-2"></i> منصات الربط الخارجية (External APIs)
                        </h5>
                        <p class="text-muted small mb-0 mt-1">تسيير مفاتيح API للمنصات الخارجية الحكومية أو الشركاء الاقتصاديين.</p>
                    </div>
                    <button class="btn btn-primary rounded-pill px-3 fw-bold btn-sm"
                            type="button" data-bs-toggle="modal" data-bs-target="#createClientModal">
                        <i class="fa-solid fa-plus me-1"></i> إضافة منصة جديدة
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الجهة / المنصة</th>
                                <th>معدل الاستخدام (Rate)</th>
                                <th>IPs المسموح بها</th>
                                <th>آخر استخدام</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الحالة</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                            <tr id="client-row-{{ $client->id }}">
                                <td>
                                    <span class="fw-bold text-dark">{{ $client->client_name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 0.72rem;">60 req/min</span>
                                </td>
                                <td>
                                    @if(empty($client->allowed_ips))
                                        <span class="text-muted italic small">مفتوح (أي IP)</span>
                                    @else
                                        <code class="text-primary small" style="font-size: 0.75rem;">{{ $client->allowed_ips }}</code>
                                    @endif
                                </td>
                                <td>
                                    @if($client->last_used_at)
                                        <span class="text-dark small"><i class="fa-regular fa-clock me-1"></i> {{ $client->last_used_at->format('Y/m/d H:i') }}</span>
                                    @else
                                        <span class="text-muted small">لم يستخدم بعد</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $client->created_at->format('Y/m/d') }}</span>
                                </td>
                                <td>
                                    <div class="form-check form-switch p-0 m-0 d-flex justify-content-center">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" 
                                               id="switch-{{ $client->id }}"
                                               {{ $client->is_active ? 'checked' : '' }}
                                               onchange="toggleClientStatus({{ $client->id }}, '{{ csrf_token() }}')">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button class="btn btn-sm btn-outline-info rounded-circle" 
                                                onclick="showClientDocsModal('{{ $client->client_name }}', {{ json_encode($client->allowed_endpoints ?? []) }})" 
                                                title="عرض التوثيق والتعليمات">
                                            <i class="fa-solid fa-book" style="font-size: 0.75rem;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary rounded-circle" 
                                                onclick="showEditClientModal({{ $client->id }}, '{{ $client->client_name }}', '{{ $client->allowed_ips }}', {{ json_encode($client->allowed_endpoints ?? []) }})" 
                                                title="تعديل">
                                            <i class="fa-solid fa-pen" style="font-size: 0.75rem;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-circle" 
                                                onclick="deleteClient({{ $client->id }}, '{{ csrf_token() }}')" 
                                                title="حذف وسحب المفتاح">
                                            <i class="fa-solid fa-trash" style="font-size: 0.75rem;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-circle-nodes fs-3 mb-2 d-block text-black-50"></i>
                                    لا توجد منصات ربط خارجية مسجلة حالياً.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- API Usage Documentation -->
            <div class="card border-0 shadow-sm p-4 mt-4" style="border-radius:20px;">
                <h5 class="fw-bold text-dark mb-3" style="font-family:'Cairo';">
                    <i class="fa-solid fa-book-open text-primary me-2"></i> توثيق استخدام API
                </h5>

                <div class="accordion" id="apiDocsAccordion">
                    <!-- Authentication -->
                    <div class="accordion-item border-0 mb-2" style="border-radius:12px !important;overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold rounded-3 collapsed"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse-auth"
                                    style="font-family:'Cairo';font-size:0.9rem;">
                                <i class="fa-solid fa-lock text-success me-2"></i> المصادقة والتوثيق
                            </button>
                        </h2>
                        <div id="collapse-auth" class="accordion-collapse collapse" data-bs-parent="#apiDocsAccordion">
                            <div class="accordion-body bg-light rounded-3 text-start" style="direction:ltr;text-align:left;">
                                <p class="small text-muted mb-2" style="direction:rtl;text-align:right;">أضف المفتاح في رأس طلب HTTP كالتالي:</p>
                                <pre class="bg-dark text-success p-3 rounded-3 small" style="font-family:'Outfit';">Authorization: Bearer YOUR_API_KEY
X-API-Key: YOUR_API_KEY</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Endpoints -->
                    <div class="accordion-item border-0 mb-2" style="border-radius:12px !important;overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold rounded-3 collapsed"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse-endpoints"
                                    style="font-family:'Cairo';font-size:0.9rem;">
                                <i class="fa-solid fa-network-wired text-primary me-2"></i> نقاط النهاية المتاحة (Endpoints)
                            </button>
                        </h2>
                        <div id="collapse-endpoints" class="accordion-collapse collapse" data-bs-parent="#apiDocsAccordion">
                            <div class="accordion-body bg-light rounded-3">
                                <table class="table table-sm align-middle mb-0" style="font-size:0.82rem;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>الطريقة</th>
                                            <th>المسار</th>
                                            <th>الوصف</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-family:'Outfit';">
                                        <tr>
                                            <td><span class="badge bg-success">GET</span></td>
                                            <td>/api/v1/verify</td>
                                            <td class="text-muted">التحقق من صحة المفتاح</td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-success">GET</span></td>
                                            <td>/api/v1/stagiaires</td>
                                            <td class="text-muted">قائمة المتربصين المرشحين (محددة بـ 50 نتيجة بالصفحة)</td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-success">GET</span></td>
                                            <td>/api/v1/offres</td>
                                            <td class="text-muted">عروض التكوين النشطة</td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-primary">POST</span></td>
                                            <td>/api/v1/auth/token</td>
                                            <td class="text-muted">طلب رمز JWT جديد باستخدام الـ API Key</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Rate Limits -->
                    <div class="accordion-item border-0" style="border-radius:12px !important;overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold rounded-3 collapsed"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse-limits"
                                    style="font-family:'Cairo';font-size:0.9rem;">
                                <i class="fa-solid fa-gauge-high text-warning me-2"></i> حدود معدل الاستخدام (Rate Limits)
                            </button>
                        </h2>
                        <div id="collapse-limits" class="accordion-collapse collapse" data-bs-parent="#apiDocsAccordion">
                            <div class="accordion-body bg-light rounded-3">
                                <ul class="list-unstyled mb-0 small text-muted">
                                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>60 طلب في الدقيقة للـ API العام</li>
                                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>300 طلب في الساعة للحسابات الموثوقة</li>
                                    <li><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>تجاوز الحد يؤدي إلى إرجاع رمز الخطأ HTTP 429 Too Many Requests</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar stats + info -->
        <div class="col-lg-4">
            <!-- Status Badge -->
            <div class="card border-0 p-3 mb-4 text-center shadow-sm" style="border-radius:16px;background:linear-gradient(135deg,#ecfdf5,#f0fdf4);">
                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center"
                     style="width:52px;height:52px;background:#10b981;color:#fff;">
                    <i class="fa-solid fa-circle-check fs-4"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">حالة الاتصال</h6>
                <span class="badge bg-success-subtle text-success px-3 py-2 fw-bold" style="border-radius:30px;">
                    نشط ومتصل / API Active
                </span>
                <p class="text-muted small mt-2 mb-0">البنية التحتية الرقمية تعمل بكفاءة كاملة</p>
            </div>

            <!-- Security Info -->
            <div class="card border-0 p-3 mb-4 shadow-sm" style="border-radius:16px;">
                <h6 class="fw-bold text-dark mb-3" style="font-family:'Cairo';">
                    <i class="fa-solid fa-shield-halved text-primary me-2"></i> معلومات الأمان
                </h6>
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-2 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check-circle text-success mt-1 flex-shrink-0"></i>
                        <span>المفاتيح الخارجية مشفرة بتعمية هجين (Hashed SHA-256) على مستوى خادم MySQL</span>
                    </li>
                    <li class="mb-2 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check-circle text-success mt-1 flex-shrink-0"></i>
                        <span>تقييد عناوين الـ IP يمنع استخدام المفتاح من خوادم غير مصرح بها</span>
                    </li>
                    <li class="mb-2 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check-circle text-success mt-1 flex-shrink-0"></i>
                        <span>يتم تسجيل جميع استخدامات المفتاح في سجل التدقيق والـ Activity Logs</span>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-warning mt-1 flex-shrink-0"></i>
                        <span>يظهر المفتاح الجديد مرة واحدة فقط عند الإنشاء لضمان أعلى معايير الخصوصية</span>
                    </li>
                </ul>
            </div>

            <!-- Quick Links -->
            <div class="card border-0 p-3 shadow-sm" style="border-radius:16px;">
                <h6 class="fw-bold text-dark mb-3" style="font-family:'Cairo';">
                    <i class="fa-solid fa-link text-primary me-2"></i> روابط سريعة
                </h6>
                <div class="d-flex flex-column gap-2">
                    <a href="/dashboard/audit-logs" class="btn btn-outline-secondary rounded-3 text-start fw-bold" style="font-size:0.82rem;">
                        <i class="fa-solid fa-list-check text-primary me-2"></i> سجل العمليات
                    </a>
                    <a href="/dashboard/users" class="btn btn-outline-secondary rounded-3 text-start fw-bold" style="font-size:0.82rem;">
                        <i class="fa-solid fa-users-gear text-success me-2"></i> إدارة الحسابات
                    </a>
                    <a href="/dashboard/settings" class="btn btn-outline-secondary rounded-3 text-start fw-bold" style="font-size:0.82rem;">
                        <i class="fa-solid fa-sliders text-warning me-2"></i> إعدادات المنصة
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Create Client Modal -->
<div class="modal fade" id="createClientModal" tabindex="-1" aria-labelledby="createClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="createClientModalLabel" style="font-family: 'Cairo';">
                    <i class="fa-solid fa-plus text-primary me-2"></i> إضافة منصة ربط خارجية
                </h5>
                <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createClientForm" onsubmit="createApiClient(event)">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="client_name" class="form-label fw-bold small">اسم الجهة أو المنصة الشريكة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="client_name" name="client_name" required placeholder="مثال: وزارة التكوين المهني، شريك اقتصادي X">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">صلاحيات الوصول الممنوحة (الجداول المسموحة) <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column gap-2 p-2 rounded-3 bg-light border border-light-subtle">
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="stagiaires" id="chk_stagiaires" checked>
                                <label class="form-check-label fw-bold text-dark small" for="chk_stagiaires">جدول المتربصين (Apprenant / Stagiaires)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="offres" id="chk_offres" checked>
                                <label class="form-check-label fw-bold text-dark small" for="chk_offres">جدول عروض التكوين (Offre / Offres)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="employees" id="chk_employees" checked>
                                <label class="form-check-label fw-bold text-dark small" for="chk_employees">جدول الموظفين والمؤطرين (Encadrement / Employees)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="formateurs" id="chk_formateurs">
                                <label class="form-check-label fw-bold text-dark small" for="chk_formateurs">ساعات التكوين الشاغرة (Formateurs / Vacant Hours)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="finance" id="chk_finance">
                                <label class="form-check-label fw-bold text-dark small" for="chk_finance">التقارير المالية والميزانية (Finance / Budget Reports)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="assets" id="chk_assets">
                                <label class="form-check-label fw-bold text-dark small" for="chk_assets">طلبات العتاد والورشات (Assets / Equipment Requests)</label>
                            </div>
                        </div>
                        
                        <!-- Container for dynamic instructions -->
                        <div id="create-api-instructions-container" class="mt-3" style="display:none;">
                            <h6 class="fw-bold text-dark mb-2" style="font-family: 'Cairo'; font-size: 0.85rem;">
                                <i class="fa-solid fa-code text-primary me-1"></i> دليل استخدام الـ API للجداول المحددة:
                            </h6>
                            <div id="create-api-instructions" class="p-2 rounded-3 bg-dark text-light border border-secondary" style="max-height: 250px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="allowed_ips" class="form-label fw-bold small">عناوين IP المسموح بها (اختياري)</label>
                        <input type="text" class="form-control rounded-3" id="allowed_ips" name="allowed_ips" placeholder="مثال: 197.112.10.25, 8.8.8.8">
                        <div class="form-text text-muted" style="font-size: 0.75rem;">افصل بين العناوين بفاصلة. اتركه فارغاً للسماح بالوصول من أي عنوان.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">توليد المفتاح</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editClientModalLabel" style="font-family: 'Cairo';">
                    <i class="fa-solid fa-pen text-primary me-2"></i> تعديل بيانات منصة الربط
                </h5>
                <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editClientForm" onsubmit="updateApiClient(event)">
                @csrf
                <input type="hidden" id="edit_client_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_client_name" class="form-label fw-bold small">اسم الجهة أو المنصة الشريكة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="edit_client_name" name="client_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">صلاحيات الوصول الممنوحة (الجداول المسموحة) <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column gap-2 p-2 rounded-3 bg-light border border-light-subtle">
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="stagiaires" id="edit_chk_stagiaires">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_stagiaires">جدول المتربصين (Apprenant / Stagiaires)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="offres" id="edit_chk_offres">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_offres">جدول عروض التكوين (Offre / Offres)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="employees" id="edit_chk_employees">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_employees">جدول الموظفين والمؤطرين (Encadrement / Employees)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="formateurs" id="edit_chk_formateurs">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_formateurs">ساعات التكوين الشاغرة (Formateurs / Vacant Hours)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="finance" id="edit_chk_finance">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_finance">التقارير المالية والميزانية (Finance / Budget Reports)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-endpoint-chk" type="checkbox" name="allowed_endpoints[]" value="assets" id="edit_chk_assets">
                                <label class="form-check-label fw-bold text-dark small" for="edit_chk_assets">طلبات العتاد والورشات (Assets / Equipment Requests)</label>
                            </div>
                        </div>
                        
                        <!-- Container for dynamic instructions -->
                        <div id="edit-api-instructions-container" class="mt-3" style="display:none;">
                            <h6 class="fw-bold text-dark mb-2" style="font-family: 'Cairo'; font-size: 0.85rem;">
                                <i class="fa-solid fa-code text-primary me-1"></i> دليل استخدام الـ API للجداول المحددة:
                            </h6>
                            <div id="edit-api-instructions" class="p-2 rounded-3 bg-dark text-light border border-secondary" style="max-height: 250px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_allowed_ips" class="form-label fw-bold small">عناوين IP المسموح بها (اختياري)</label>
                        <input type="text" class="form-control rounded-3" id="edit_allowed_ips" name="allowed_ips">
                        <div class="form-text text-muted" style="font-size: 0.75rem;">افصل بين العناوين بفاصلة. اتركه فارغاً للسماح بالوصول من أي عنوان.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyApiKey() {
    const input = document.getElementById('user-apiKey');
    if (!input || !input.value || input.value.includes('لم يتم')) return;
    navigator.clipboard.writeText(input.value).then(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ مفتاح API للحافظة!', showConfirmButton: false, timer: 2000, timerProgressBar: true });
        }
    });
}

function regenerateUserApiKey(btn) {
    if (!btn) return;
    const userId = btn.getAttribute('data-user-id');
    const csrfToken = btn.getAttribute('data-csrf-token');

    if (!confirm('هل أنت متأكد من رغبتك في تجديد مفتاح API الخاص بك؟ سيتعطل المفتاح القديم فوراً.')) return;

    fetch('/sig/dashboard/users/generate-api-key', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${userId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('user-apiKey').value = data.api_key;
            Swal.fire({ icon: 'success', title: 'تم تجديد المفتاح!', text: 'تم توليد مفتاح API جديد بنجاح.', confirmButtonColor: '#006233' });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: data.message || 'فشل توليد المفتاح.', confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

function revokeApiKey() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'warning', title: 'إلغاء المفتاح', text: 'هذه الميزة ستكون متاحة قريباً.', confirmButtonColor: '#006233' });
    }
}

// -----------------------------------------------------------------------------
// External API Clients CRUD Operations
// -----------------------------------------------------------------------------

function createApiClient(e) {
    e.preventDefault();
    const form = document.getElementById('createClientForm');
    const formData = new FormData(form);

    fetch('/sig/dashboard/api-center/store', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            const modalEl = document.getElementById('createClientModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            form.reset();

            // Display Plain API Key once securely in SweetAlert
            Swal.fire({
                icon: 'success',
                title: 'تم إنشاء المفتاح بنجاح!',
                html: `
                    <p class="text-danger small fw-bold">انسخ هذا المفتاح الآن! لن تتمكن من رؤيته مجدداً لأسباب أمنية:</p>
                    <div class="input-group my-3">
                        <input type="text" id="plain-api-key" class="form-control text-center fw-bold text-success border-primary bg-light" readonly value="${data.plain_key}" style="font-family:'Outfit';">
                        <button class="btn btn-primary" onclick="navigator.clipboard.writeText('${data.plain_key}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'تم نسخ المفتاح!',showConfirmButton:false,timer:1500});">
                            <i class="fa-regular fa-copy"></i> نسخ
                        </button>
                    </div>
                `,
                confirmButtonText: 'إغلاق ومتابعة',
                confirmButtonColor: '#006233',
                allowOutsideClick: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ في الحفظ', text: data.message, confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

<!-- View Client Docs Modal -->
<div class="modal fade" id="viewClientDocsModal" tabindex="-1" aria-labelledby="viewClientDocsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; background: white;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="viewClientDocsModalLabel" style="font-family: 'Cairo';">
                    <i class="fa-solid fa-book text-success me-2"></i> دليل الربط والتعليمات للمنصة الشريكة: <span id="viewDocsClientName" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3 animate__animated animate__fadeIn" style="font-family:'Cairo';">
                    <i class="fa-solid fa-circle-info me-1"></i> استخدم رأس الطلب HTTP التالي للمصادقة: <code>X-API-Key: YOUR_API_KEY</code>
                </div>
                <div id="client-docs-content" style="max-height: 450px; overflow-y: auto; padding-right: 5px;">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
            </div>
        </div>
    </div>
</div>

<script>
const apiDocs = {
    stagiaires: {
        title_ar: "جدول المتربصين (Apprenant / Stagiaires)",
        title_en: "Trainees Endpoint",
        method: "GET",
        path: "/api/v1/stagiaires",
        description: "يجلب قائمة المتربصين مع إمكانية التصفية والبحث الفوري.",
        params: [
            { name: "valide", type: "integer", desc: "1 للنشطين، 0 لغير النشطين (Statut: active/inactive)" },
            { name: "etablissement_id", type: "integer", desc: "معرف المؤسسة التكوينية (Ets ID)" },
            { name: "specialite_id", type: "integer", desc: "معرف التخصص (Speciality ID)" },
            { name: "sexe", type: "string (M/F)", desc: "الجنس (M للذكور، F للإناث)" },
            { name: "q", type: "string", desc: "البحث بالاسم واللقب ورقم التسجيل" }
        ],
        body: null
    },
    offres: {
        title_ar: "جدول عروض التكوين (Offre / Offres)",
        title_en: "Training Offers Endpoint",
        method: "GET",
        path: "/api/v1/offres",
        description: "يجلب قائمة عروض التكوين المفتوحة والنشطة.",
        params: [
            { name: "etablissement_id", type: "integer", desc: "تصفية حسب معرف المؤسسة" },
            { name: "specialite_id", type: "integer", desc: "تصفية حسب معرف التخصص" },
            { name: "session_id", type: "integer", desc: "تصفية حسب معرف دورة التكوين" },
            { name: "valide_centrale", type: "integer (0/1)", desc: "1 لجلب العروض المعتمدة مركزياً فقط" }
        ],
        body: null
    },
    employees: {
        title_ar: "جدول الموظفين والمؤطرين (Encadrement / Employees)",
        title_en: "Employees / Staff Endpoint",
        method: "GET",
        path: "/api/v1/employees",
        description: "يجلب قائمة الموظفين، المؤطرين، الإداريين، والأساتذة.",
        params: [
            { name: "etablissement_id", type: "integer", desc: "تصفية حسب معرف المؤسسة" },
            { name: "grade", type: "string", desc: "تصفية حسب الرتبة" },
            { name: "fonction", type: "string", desc: "تصفية حسب الوظيفة" },
            { name: "q", type: "string", desc: "البحث بالاسم، اللقب، البريد الإلكتروني، أو NIN" }
        ],
        body: null
    },
    formateurs: {
        title_ar: "ساعات التكوين الشاغرة (Formateurs / Vacant Hours)",
        title_en: "Vacant Hours Endpoint",
        method: "GET",
        path: "/api/v1/hr/formateurs/vacant-hours",
        description: "حساب ساعات التكوين الشاغرة لجميع المؤطرين والأساتذة لجدولة الحصص.",
        params: [],
        body: null
    },
    finance: {
        title_ar: "التقارير المالية والميزانية (Finance / Budget Reports)",
        title_en: "Finance & Budget Endpoint",
        method: "GET",
        path: "/api/v1/finance/reports/budget",
        description: "تحميل التقارير المالية للمؤسسة مشفرة بخوارزمية AES-256-CBC لحماية البيانات الحساسة.",
        params: [],
        body: null
    },
    assets: {
        title_ar: "طلبات العتاد والورشات (Assets / Equipment Requests)",
        title_en: "Assets Request Endpoint",
        method: "POST",
        path: "/api/v1/assets/requests",
        description: "تقديم طلب عتاد تقني جديد أو تجهيز ورشات لفائدة المؤسسة التكوينية.",
        params: [],
        body: [
            { name: "designation", type: "string", required: true, desc: "تسمية العتاد المطلوب (مثال: جهاز عرض رقمي)" },
            { name: "specialite_id", type: "integer", required: true, desc: "معرف التخصص المرتبط بالعتاد" },
            { name: "etablissement_id", type: "integer", required: true, desc: "معرف المؤسسة المستفيدة (مطلوب لطلبات API)" },
            { name: "description", type: "string", required: false, desc: "تفاصيل ووصف إضافي للطلب" }
        ]
    }
};

function generateApiDocHtml(key, apiKey = 'YOUR_API_KEY') {
    const doc = apiDocs[key];
    if (!doc) return '';

    const baseHost = "{{ request()->getSchemeAndHttpHost() }}";
    const fullUrl = `${baseHost}${doc.path}`;
    
    let curlCmd = '';
    if (doc.method === 'GET') {
        const queryParams = doc.params.map(p => `${p.name}=value`).join('&');
        const urlWithQuery = queryParams ? `${fullUrl}?${queryParams}` : fullUrl;
        curlCmd = `curl -X GET "${urlWithQuery}" \\\n  -H "X-API-Key: ${apiKey}"`;
    } else {
        const bodyObj = {};
        if (doc.body) {
            doc.body.forEach(b => {
                bodyObj[b.name] = b.type === 'integer' ? 123 : "text";
            });
        }
        curlCmd = `curl -X POST "${fullUrl}" \\\n  -H "X-API-Key: ${apiKey}" \\\n  -H "Content-Type: application/json" \\\n  -d '${JSON.stringify(bodyObj, null, 2)}'`;
    }

    let paramsHtml = '';
    if (doc.params && doc.params.length > 0) {
        paramsHtml = `
            <div class="mt-2 text-warning small fw-bold" style="text-align:left; direction:ltr;">Query Parameters:</div>
            <ul class="list-unstyled ps-2 mb-2 text-white-50 text-start" style="font-size: 0.78rem; text-align:left; direction:ltr;">
                ${doc.params.map(p => `<li><code>${p.name}</code> (${p.type}): ${p.desc}</li>`).join('')}
            </ul>
        `;
    }

    let bodyHtml = '';
    if (doc.body && doc.body.length > 0) {
        bodyHtml = `
            <div class="mt-2 text-warning small fw-bold" style="text-align:left; direction:ltr;">Request Body (JSON):</div>
            <ul class="list-unstyled ps-2 mb-2 text-white-50 text-start" style="font-size: 0.78rem; text-align:left; direction:ltr;">
                ${doc.body.map(b => `<li><code>${b.name}</code> (${b.type})${b.required ? ' <span class="text-danger">*</span>' : ''}: ${b.desc}</li>`).join('')}
            </ul>
        `;
    }

    const docId = `doc-block-${key}-${Math.floor(Math.random() * 100000)}`;
    return `
        <div class="api-doc-item mb-3 p-3 rounded-3 border border-secondary" style="background: #111827; border-color: #374151; color: #fff;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge ${doc.method === 'GET' ? 'bg-success' : 'bg-primary'} fw-bold" style="font-size: 0.75rem;">${doc.method}</span>
                <span class="text-light fw-bold small" style="font-family: 'Cairo';">${doc.title_ar}</span>
            </div>
            <div class="text-white-50 small mb-2" style="font-family: 'Cairo';">${doc.description}</div>
            
            <div class="input-group input-group-sm mb-2" style="direction: ltr;">
                <span class="input-group-text bg-dark border-secondary text-light small" style="font-size:0.7rem;">Endpoint</span>
                <input type="text" class="form-control bg-dark border-secondary text-success fw-bold text-truncate" style="font-size:0.75rem; font-family:'Outfit';" readonly value="${doc.path}">
            </div>

            ${paramsHtml}
            ${bodyHtml}

            <div class="position-relative" style="direction: ltr;">
                <pre class="bg-black text-info p-2 rounded-2 mb-0 overflow-auto" id="${docId}" style="font-size:0.72rem; font-family:'Outfit'; max-height:120px; text-align:left; direction:ltr;">${curlCmd}</pre>
                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-1" type="button" style="font-size:0.6rem; padding:2px 6px;" onclick="copyCodeText('${docId}')">
                    <i class="fa-regular fa-copy"></i> نسخ
                </button>
            </div>
        </div>
    `;
}

window.copyCodeText = function(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ أمر cURL!', showConfirmButton: false, timer: 1500 });
    });
}

function handleEndpointCheckboxChange(containerId, listClass) {
    const container = document.getElementById(containerId + '-container');
    const displayDiv = document.getElementById(containerId);
    if (!container || !displayDiv) return;
    
    // Get all checked checkboxes
    const checked = Array.from(document.querySelectorAll('.' + listClass))
        .filter(chk => chk.checked)
        .map(chk => chk.value);
        
    if (checked.length === 0) {
        container.style.display = 'none';
        displayDiv.innerHTML = '';
        return;
    }
    
    let html = '';
    checked.forEach(key => {
        html += generateApiDocHtml(key);
    });
    
    displayDiv.innerHTML = html;
    container.style.display = 'block';
}

function showClientDocsModal(name, endpoints) {
    document.getElementById('viewDocsClientName').innerText = name;
    
    const container = document.getElementById('client-docs-content');
    if (!Array.isArray(endpoints) || endpoints.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fa-solid fa-triangle-exclamation fs-3 mb-2 text-warning d-block"></i>
                لم يتم منح أي صلاحيات وصول لهذه المنصة بعد.
            </div>
        `;
    } else {
        let html = '';
        endpoints.forEach(ep => {
            html += generateApiDocHtml(ep, 'YOUR_API_KEY');
        });
        container.innerHTML = html;
    }
    
    const modalEl = document.getElementById('viewClientDocsModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function copyApiKey() {
    const input = document.getElementById('user-apiKey');
    if (!input || !input.value || input.value.includes('لم يتم')) return;
    navigator.clipboard.writeText(input.value).then(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ مفتاح API للحافظة!', showConfirmButton: false, timer: 2000, timerProgressBar: true });
        }
    });
}

function regenerateUserApiKey(btn) {
    if (!btn) return;
    const userId = btn.getAttribute('data-user-id');
    const csrfToken = btn.getAttribute('data-csrf-token');

    if (!confirm('هل أنت متأكد من رغبتك في تجديد مفتاح API الخاص بك؟ سيتعطل المفتاح القديم فوراً.')) return;

    fetch('/sig/dashboard/users/generate-api-key', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${userId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('user-apiKey').value = data.api_key;
            Swal.fire({ icon: 'success', title: 'تم تجديد المفتاح!', text: 'تم توليد مفتاح API جديد بنجاح.', confirmButtonColor: '#006233' });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: data.message || 'فشل توليد المفتاح.', confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

function revokeApiKey() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'warning', title: 'إلغاء المفتاح', text: 'هذه الميزة ستكون متاحة قريباً.', confirmButtonColor: '#006233' });
    }
}

// -----------------------------------------------------------------------------
// External API Clients CRUD Operations
// -----------------------------------------------------------------------------

function createApiClient(e) {
    e.preventDefault();
    const form = document.getElementById('createClientForm');
    const formData = new FormData(form);

    fetch('/sig/dashboard/api-center/store', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            const modalEl = document.getElementById('createClientModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            form.reset();

            // Display Plain API Key once securely in SweetAlert
            Swal.fire({
                icon: 'success',
                title: 'تم إنشاء المفتاح بنجاح!',
                html: `
                    <p class="text-danger small fw-bold">انسخ هذا المفتاح الآن! لن تتمكن من رؤيته مجدداً لأسباب أمنية:</p>
                    <div class="input-group my-3">
                        <input type="text" id="plain-api-key" class="form-control text-center fw-bold text-success border-primary bg-light" readonly value="${data.plain_key}" style="font-family:'Outfit';">
                        <button class="btn btn-primary" onclick="navigator.clipboard.writeText('${data.plain_key}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'تم نسخ المفتاح!',showConfirmButton:false,timer:1500});">
                            <i class="fa-regular fa-copy"></i> نسخ
                        </button>
                    </div>
                `,
                confirmButtonText: 'إغلاق ومتابعة',
                confirmButtonColor: '#006233',
                allowOutsideClick: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ في الحفظ', text: data.message, confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

function showEditClientModal(id, name, ips, endpoints) {
    document.getElementById('edit_client_id').value = id;
    document.getElementById('edit_client_name').value = name;
    document.getElementById('edit_allowed_ips').value = (ips === 'null' || !ips) ? '' : ips;
    
    // Reset checkboxes
    document.querySelectorAll('.edit-endpoint-chk').forEach(chk => {
        chk.checked = false;
    });
    
    // Select correct checkboxes
    if (Array.isArray(endpoints)) {
        endpoints.forEach(ep => {
            const chk = document.getElementById('edit_chk_' + ep);
            if (chk) chk.checked = true;
        });
    }
    
    // Trigger instructions update
    handleEndpointCheckboxChange('edit-api-instructions', 'edit-endpoint-chk');
    
    const modalEl = document.getElementById('editClientModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function updateApiClient(e) {
    e.preventDefault();
    const id = document.getElementById('edit_client_id').value;
    const form = document.getElementById('editClientForm');
    const formData = new FormData(form);

    fetch(`/sig/dashboard/api-center/update/${id}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('editClientModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            Swal.fire({ icon: 'success', title: 'تم التحديث!', text: data.message, confirmButtonColor: '#006233' }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: data.message, confirmButtonColor: '#006233' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' }));
}

function toggleClientStatus(id, token) {
    fetch(`/sig/dashboard/api-center/toggle/${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `_token=${token}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000, timerProgressBar: true });
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: data.message, confirmButtonColor: '#006233' });
            // Revert switch status
            const sw = document.getElementById(`switch-${id}`);
            sw.checked = !sw.checked;
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.', confirmButtonColor: '#006233' });
        const sw = document.getElementById(`switch-${id}`);
        sw.checked = !sw.checked;
    });
}

function deleteClient(id, token) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيؤدي هذا إلى حذف منصة الربط وإلغاء صلاحية مفتاح الـ API المرتبط بها فوراً وكلياً!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف المفتاح!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/sig/dashboard/api-center/delete/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `_token=${token}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'تم الحذف!', text: data.message, confirmButtonColor: '#006233' }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'خطأ', text: data.message, confirmButtonColor: '#006233' });
        }
    });
}

function showAdminDocSnippet() {
    const select = document.getElementById('admin-doc-select');
    const container = document.getElementById('admin-doc-snippet-container');
    const display = document.getElementById('admin-doc-snippet');
    const adminKey = document.getElementById('user-apiKey').value;
    
    if (!select || !select.value || adminKey.includes('لم يتم')) {
        if (container) container.style.display = 'none';
        if (display) display.innerHTML = '';
        return;
    }
    
    const key = select.value;
    display.innerHTML = generateApiDocHtml(key, adminKey);
    container.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', () => {
    // Add change listeners for Create checkboxes
    document.querySelectorAll('.create-endpoint-chk').forEach(chk => {
        chk.addEventListener('change', () => {
            handleEndpointCheckboxChange('create-api-instructions', 'create-endpoint-chk');
        });
    });
    
    // Add change listeners for Edit checkboxes
    document.querySelectorAll('.edit-endpoint-chk').forEach(chk => {
        chk.addEventListener('change', () => {
            handleEndpointCheckboxChange('edit-api-instructions', 'edit-endpoint-chk');
        });
    });
    
    // Trigger initial state for Create modal
    handleEndpointCheckboxChange('create-api-instructions', 'create-endpoint-chk');
});
</script>
@endsection
