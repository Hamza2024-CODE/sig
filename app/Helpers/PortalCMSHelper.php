<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class PortalCMSHelper
{
    /**
     * Checks if the `portal_pages` table exists, creates and seeds it if it doesn't.
     */
    public static function ensureTableExists(): void
    {
        if (Schema::hasTable('portal_pages')) {
            return;
        }

        // Create the table
        Schema::create('portal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('title_fr')->nullable();
            $table->string('icon')->default('fa-file-lines');
            $table->longText('content');
            $table->longText('content_fr')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Default content block for generic pages
        $defaultGenericContent = '<p class="fw-bold mb-3" style="font-size: 1.15rem;">وثائق وأدلة التسيير والمتابعة البيداغوجية</p><p>يقدم قطاع التكوين المهني دليلاً موحداً ومنصات رقمية متكاملة لتبسيط الإجراءات البيداغوجية والإدارية والمالية. بإمكان المكونين والطلبة والمؤسسات تحميل ومطالعة النسخ الرقمية المعتمدة هنا.</p><div class="portal-feature-grid mt-4"><div class="portal-feature-card"><i class="fa-solid fa-file-pdf text-danger mb-3" style="font-size: 2.2rem;"></i><h5 class="fw-bold text-dark">دليل المكون والأستاذ</h5><a href="#" class="btn btn-sm btn-light border mt-2">تحميل الدليل المرجعي</a></div><div class="portal-feature-card"><i class="fa-solid fa-laptop-code text-primary mb-3" style="font-size: 2.2rem;"></i><h5 class="fw-bold text-dark">دليل منصة تسيير (Tassyir)</h5><a href="#" class="btn btn-sm btn-light border mt-2">استعراض الخطوات الرسمية</a></div></div>';

        // Seed data for the 14 default pages
        $defaultPages = [
            [
                'slug' => 'ministere',
                'title' => 'تقديم الوزارة والهيكل التنظيمي',
                'title_fr' => 'Le Ministère',
                'icon' => 'fa-hotel',
                'sort_order' => 1,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">الهيكل التنظيمي لوزارة التكوين والتعليم المهنيين</p><p>تسهر وزارة التكوين والتعليم المهنيين على تنظيم وتسيير قطاع التكوين المهني عبر كامل التراب الوطني. تهدف الوزارة إلى إعداد وتطبيق السياسة الوطنية للتكوين بالتنسيق مع مختلف الشركاء الاقتصاديين والاجتماعيين لتلبية متطلبات سوق العمل.</p><div class="portal-premium-box my-4"><h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-building-columns text-primary me-2"></i>الإدارات والمصالح المركزية:</h6><ul class="portal-list-styled mb-0"><li>المديرية العامة للتعليم المهني والتكوين المتواصل.</li><li>مديرية التخطيط والتطوير والمتابعة.</li><li>مديرية الامتحانات والشهادات والتوجيه المدرسي.</li><li>المفتشية العامة للبيداغوجيا والتدقيق الإداري.</li></ul></div><button class="btn btn-primary px-4 py-2.5 rounded-pill fw-bold"><i class="fa-solid fa-download me-2"></i> تحميل الهيكل التنظيمي الكامل (PDF)</button>',
            ],
            [
                'slug' => 'directions',
                'title' => 'المديريات الولائية للتكوين المهني',
                'title_fr' => 'Directions de Wilayas',
                'icon' => 'fa-map-location-dot',
                'sort_order' => 2,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">دليل المديريات الولائية الـ 58 عبر التراب الوطني</p><p>تمثل المديرية الولائية (DFEP) السلطة التنفيذية للوزارة على المستوى المحلي، وتتوزع الصلاحيات حسب تصنيف الولاية (نوع 1، نوع 2، نوع 3) لتغطية كامل التراب الوطني.</p><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" /><script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script><div id="algeria-map" style="height: 500px; width: 100%; border-radius: 18px; border: 1px solid var(--color-border); margin: 2rem 0; z-index: 1;"></div><script>document.addEventListener("DOMContentLoaded", function() { var map = L.map("algeria-map").setView([28.0339, 1.6596], 5); L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 18, attribution: "© OpenStreetMap contributors" }).addTo(map); var wilayas = window.portalDfeps || []; wilayas.forEach(function(w) { var color = "#3b82f6"; var radius = 45000; if (w.type.includes("1")) { color = "#ef4444"; radius = 70000; } else if (w.type.includes("3")) { color = "#10b981"; radius = 25000; } var circle = L.circle([w.lat, w.lng], { color: color, fillColor: color, fillOpacity: 0.3, weight: 1.5, radius: radius }).addTo(map); circle.bindPopup(\'<div class="text-center p-2"><h6 class="fw-bold mb-2">\' + w.name + \'</h6><span class="badge \' + w.badgeClass + \' rounded-pill">\' + w.type + \'</span></div>\'); }); });</script><button class="btn btn-light border fw-bold px-4 py-2.5 rounded-pill mt-2"><i class="fa-solid fa-phone me-2"></i> الاتصال بالمديريات الولائية</button>',
            ],
            [
                'slug' => 'ingenierie',
                'title' => 'الهندسة البيداغوجية والبرامج',
                'title_fr' => 'Ingénierie Pédagogique',
                'icon' => 'fa-gears',
                'sort_order' => 3,
                'content' => $defaultGenericContent,
            ],
            [
                'slug' => 'instituts',
                'title' => 'المعاهد الوطنية المتخصصة (INSFP)',
                'title_fr' => 'Instituts Nationaux',
                'icon' => 'fa-graduation-cap',
                'sort_order' => 4,
                'content' => $defaultGenericContent,
            ],
            [
                'slug' => 'pedagogique',
                'title' => 'الجانب البيداغوجي وتسيير المتربصين',
                'title_fr' => 'Pédagogie',
                'icon' => 'fa-book-open',
                'sort_order' => 5,
                'content' => $defaultGenericContent,
            ],
            [
                'slug' => 'administratif',
                'title' => 'الجانب الإداري ودليل التسيير',
                'title_fr' => 'Gestion Administrative',
                'icon' => 'fa-file-invoice',
                'sort_order' => 6,
                'content' => $defaultGenericContent,
            ],
            [
                'slug' => 'financier',
                'title' => 'الجانب المالي والميزانيات',
                'title_fr' => 'Gestion Financière',
                'icon' => 'fa-wallet',
                'sort_order' => 7,
                'content' => $defaultGenericContent,
            ],
            [
                'slug' => 'nomenclature',
                'title' => 'مدونة الاختصاصات الوطنية للتكوين المهني',
                'title_fr' => 'Nomenclature',
                'icon' => 'fa-list-check',
                'sort_order' => 8,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">مدونة الاختصاصات الوطنية للتكوين والتعليم المهنيين</p><p>تعد مدونة الاختصاصات المرجع الوطني القانوني والتقني المعتمد لتعريف وتصنيف التخصصات وشروط الالتحاق بها ومستويات التأهيل المهني (من المستوى الأول إلى الخامس).</p><div class="portal-premium-box"><h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-list-check text-warning me-2"></i>تفاصيل المدونة:</h6><p class="mb-0">تحتوي على أكثر من <strong>490 تخصصاً مهنياً</strong> موزعة على <strong>22 شعبة مهنية</strong> (الفندقة، البناء، الاتصالات، الإعلام الآلي والرقمية)، وتخضع لتحديث دوري لمواكبة متطلبات التحول الرقمي والصناعي.</p></div><button class="btn btn-warning text-dark fw-bold px-4 py-2.5 rounded-pill mt-3"><i class="fa-solid fa-file-pdf me-2"></i> تحميل مدونة الاختصاصات الطبعة الأخيرة (PDF)</button>',
            ],
            [
                'slug' => 'guide-formateur',
                'title' => 'دليل المكون والأستاذ',
                'title_fr' => 'Guide du Formateur',
                'icon' => 'fa-chalkboard-user',
                'sort_order' => 9,
                'content' => $defaultGenericContent,
            ],
            [
                'slug' => 'lois',
                'title' => 'القوانين والمناشير الوزارية',
                'title_fr' => 'Textes Réglementaires',
                'icon' => 'fa-scale-balanced',
                'sort_order' => 10,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">الترسانة القانونية والمناشير الوزارية المعتمدة</p><p>تسيير كافة العمليات البيداغوجية، التسجيلات، الامتحانات وإمضاء الشهادات يعتمد على ترسانة من المراسيم التنفيذية والقوانين الرسمية الموثقة بالجريدة الرسمية.</p><div class="portal-timeline"><div class="portal-timeline-item"><div class="portal-timeline-badge">1</div><h6 class="fw-bold text-dark mb-1">المرسوم التنفيذي رقم 16-282</h6><p class="text-muted small">المحدد لنظام التكوين المهني والشهادات المتوجة له.</p></div><div class="portal-timeline-item"><div class="portal-timeline-badge">2</div><h6 class="fw-bold text-dark mb-1">القرار رقم 12 المعتمد</h6><p class="text-muted small">المحدد لكيفيات تسليم شهادات دولة للتكوين الأولي والتمهين.</p></div><div class="portal-timeline-item"><div class="portal-timeline-badge">3</div><h6 class="fw-bold text-dark mb-1">المناشير التوجيهية السنوية</h6><p class="text-muted small">لتنظيم امتحانات وشهادات نهاية التكوين على المستوى الوطني.</p></div></div><button class="btn btn-primary px-4 py-2.5 rounded-pill fw-bold"><i class="fa-solid fa-book me-2"></i> استكشاف الجريدة الرسمية للقطاع</button>',
            ],
            [
                'slug' => 'about',
                'title' => 'عن منصة تسيير',
                'title_fr' => 'À propos',
                'icon' => 'fa-info-circle',
                'sort_order' => 11,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">حول منصة تسيير المتكاملة ورقمنة قطاع التكوين المهني</p><p>تُعد منصة <strong>تسيير (Tassyir)</strong> البيئة الرقمية الوطنية الموحدة والمعتمدة من طرف وزارة التكوين والتعليم المهنيين لتسيير ومتابعة كافة العمليات البيداغوجية والإدارية والامتحانات الرسمية عبر التراب الوطني.</p><div class="portal-premium-box mt-4 mb-4"><h6 class="text-success fw-bold"><i class="fa-solid fa-compass-drafting me-2"></i>رؤية المنصة الإستراتيجية</h6><p class="mb-0 text-muted">الانتقال الكامل نحو الإدارة الذكية غير الورقية، لضمان أعلى مستويات الدقة والسرعة والأمان في معالجة ملفات المتربصين والنتائج المدرسية وحوكمة المداولات وإصدار الشهادات الرسمية المؤمنة.</p></div><div class="portal-premium-box"><h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-diagram-project text-primary me-2"></i>الأعمدة الأساسية للمنصة:</h6><ul class="portal-list-styled mb-0"><li><strong>تسيير البيداغوجيا والامتحانات:</strong> رقمنة إدخال النقاط والمداولات النهائية للجان الفنية.</li><li><strong>التسيير البيداغوجي والإداري المتكامل:</strong> إدارة مسار المتربص بالكامل، مع تمكين المكونين والإداريين من تتبع الحضور، العلامات، والنتائج بصفة لحظية.</li><li><strong>حوكمة المعطيات الإحصائية:</strong> توفير لوحة تحكم ذكية للوزارة والمديريات الولائية لمتابعة المؤشرات الوطنية.</li><li><strong>التكامل البرمجي التام:</strong> التنسيق الآمن وتبادل البيانات مع منصة تسجيل المتربصين (Takwin) والشركاء الاقتصاديين.</li></ul></div>',
            ],
            [
                'slug' => 'features',
                'title' => 'ميزات المنصة',
                'title_fr' => 'Caractéristiques',
                'icon' => 'fa-star',
                'sort_order' => 12,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">أبرز الخصائص والميزات التقنية التي تقدمها منصة تسيير</p><p>تم تطوير المنصة بأحدث التقنيات البرمجية لتلبية احتياجات الإدارة الحديثة وتوفير بيئة عمل مرنة وآمنة لجميع الفاعلين في القطاع.</p><div class="portal-feature-grid mt-4"><div class="portal-feature-card"><div class="portal-feature-icon"><i class="fa-solid fa-graduation-cap"></i></div><h5 class="fw-bold text-dark">التسيير البيداغوجي الشامل</h5><p class="text-muted small mt-2">رقمنة كاملة لمسار التكوين من تسجيل وحضور، تقييم دوري، حساب المعدلات وإصدار الوثائق البيداغوجية والشهادات الرسمية.</p></div><div class="portal-feature-card"><div class="portal-feature-icon"><i class="fa-solid fa-chart-line"></i></div><h5 class="fw-bold text-dark">إحصائيات وتحليلات لحظية</h5><p class="text-muted small mt-2">متابعة نسب النجاح، الغيابات، وتوزيع المتربصين حسب التخصصات والمؤسسات والولايات.</p></div><div class="portal-feature-card"><div class="portal-feature-icon"><i class="fa-solid fa-user-lock"></i></div><h5 class="fw-bold text-dark">صلاحيات حوكمة مرنة</h5><p class="text-muted small mt-2">نظام أمني متكامل للتحكم في الصلاحيات بناءً على الأدوار الوظيفية (أستاذ، رئيس مصلحة، مدير مؤسسة، مدير ولائي، إدارة مركزية).</p></div><div class="portal-feature-card"><div class="portal-feature-icon"><i class="fa-solid fa-bolt"></i></div><h5 class="fw-bold text-dark">أتمتة المداولات وإصدار الوثائق</h5><p class="text-muted small mt-2">حساب تلقائي للمعدلات وتوليد كشوف النقاط وشهادات التسجيل المدرسية وصناعة كشوف الحضور بكبسة زر.</p></div></div>',
            ],
            [
                'slug' => 'how-it-works',
                'title' => 'كيفية عمل المنصة',
                'title_fr' => 'Comment ça marche',
                'icon' => 'fa-sliders',
                'sort_order' => 13,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">كيفية سير وإدارة العمليات البيداغوجية عبر المنصة</p><p>تعمل منصة تسيير وفق سلسلة بيداغوجية مترابطة تضمن الانتقال السلس للمتربص من مرحلة التسجيل إلى غاية التخرج والاندماج المهني.</p><div class="portal-timeline mt-4"><div class="portal-timeline-item"><div class="portal-timeline-badge">1</div><h6 class="fw-bold text-dark mb-1">التسجيل والتوجيه البيداغوجي</h6><p class="text-muted small">استيراد بيانات المترشحين المقبولين من بوابة تكوين، وتوجيههم وتوزيعهم على الأفواج والمجموعات بصفة آلية.</p></div><div class="portal-timeline-item"><div class="portal-timeline-badge">2</div><h6 class="fw-bold text-dark mb-1">المتابعة اليومية وإدخال التقييمات</h6><p class="text-muted small">يقوم الأساتذة والمكونون بإدخال درجات المراقبة المستمرة، الفروض، الاختبارات وتسجيل الغيابات بانتظام من فضاءاتهم الشخصية.</p></div><div class="portal-timeline-item"><div class="portal-timeline-badge">3</div><h6 class="fw-bold text-dark mb-1">المداولات النهائية التلقائية</h6><p class="text-muted small">يقوم النظام بمعالجة وحساب المعدلات والبت القانوني في وضعية المتربصين (ناجح، معيد، مقصى) بناءً على التنظيم المعمول به.</p></div><div class="portal-timeline-item"><div class="portal-timeline-badge">4</div><h6 class="fw-bold text-dark mb-1">استخراج الشهادات الرسمية</h6><p class="text-muted small">توليد الشهادات النهائية المتوجة للتكوين وربطها برمز الاستجابة السريعة للتحقق الخارجي وبما يتوافق مع النماذج الرسمية للوزارة.</p></div></div>',
            ],
            [
                'slug' => 'privacy',
                'title' => 'سياسة خصوصية البيانات',
                'title_fr' => 'Politique de confidentialité',
                'icon' => 'fa-shield-halved',
                'sort_order' => 14,
                'content' => '<p class="fw-bold mb-3" style="font-size: 1.15rem;">التزام منصة تسيير بحماية وخصوصية بيانات المتربصين والموظفين</p><p>تضع وزارة التكوين والتعليم المهنيين أمن المعلومات وخصوصية البيانات الرقمية على رأس أولوياتها الاستراتيجية لحفظ الحقوق وسلامة البيانات.</p><div class="portal-premium-box mt-4 mb-4"><h6 class="fw-bold text-warning"><i class="fa-solid fa-user-shield me-2"></i>سياسة السرية والتحكم في البيانات</h6><p class="mb-0 text-muted">جميع المعلومات الشخصية والدراسة الأكاديمية والمهنية المخزنة في المنصة تخضع لأعلى معايير الحماية والتشفير الرقمي، وتتم معالجتها حصرياً للأغراض البيداغوجية والإدارية الرسمية للقطاع.</p></div><div class="portal-premium-box"><h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-lock text-primary me-2"></i>تدابير حماية البيانات المتخذة:</h6><ul class="portal-list-styled mb-0"><li>تشفير كلمات المرور والبيانات الحساسة بقواعد البيانات لتفادي أي وصول غير مصرح به.</li><li>تسجيل وتدقيق جميع العمليات (Logs) التي ينفذها مستخدمو النظام لتتبع التغييرات وضمان الحوكمة.</li><li>استضافة وتسيير الخوادم وقواعد البيانات داخل بيئة وطنية سحابية آمنة تابعة لقطاع الوزارة.</li><li>عدم مشاركة أي بيانات شخصية للمتربصين أو الأساتذة مع أي جهات خارجية غير مخولة قانوناً.</li></ul></div>',
            ]
        ];

        foreach ($defaultPages as $page) {
            DB::table('portal_pages')->insert(array_merge($page, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    /**
     * Get all pages ordered by sort_order.
     */
    public static function getPages(): array
    {
        self::ensureTableExists();
        return DB::table('portal_pages')->orderBy('sort_order', 'asc')->get()->all();
    }

    /**
     * Get a single page by its slug.
     */
    public static function getPage(string $slug)
    {
        self::ensureTableExists();
        return DB::table('portal_pages')->where('slug', $slug)->first();
    }

    /**
     * Add a new page.
     */
    public static function addPage(array $data): bool
    {
        self::ensureTableExists();
        
        $data['slug'] = strtolower(trim($data['slug'] ?? ''));
        $data['slug'] = preg_replace('/[^a-z0-9\-]/', '-', $data['slug']); // sanitize slug
        $data['slug'] = trim($data['slug'], '-');

        // Check uniqueness
        if (DB::table('portal_pages')->where('slug', $data['slug'])->exists()) {
            throw new \Exception('الرابط التعريفي (Slug) مستخدم بالفعل في صفحة أخرى.');
        }

        // Get max sort_order
        $maxOrder = DB::table('portal_pages')->max('sort_order') ?? 0;
        $data['sort_order'] = $maxOrder + 1;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return DB::table('portal_pages')->insert($data);
    }

    /**
     * Update an existing page.
     */
    public static function updatePage(string $slug, array $data): bool
    {
        self::ensureTableExists();
        $data['updated_at'] = now();
        
        return DB::table('portal_pages')->where('slug', $slug)->update($data) >= 0;
    }

    /**
     * Delete a page.
     */
    public static function deletePage(string $slug): bool
    {
        self::ensureTableExists();
        return DB::table('portal_pages')->where('slug', $slug)->delete() > 0;
    }
}
