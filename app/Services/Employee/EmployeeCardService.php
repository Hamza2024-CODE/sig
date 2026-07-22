<?php

namespace App\Services\Employee;

/**
 * EmployeeCardService
 *
 * Responsible for classifying employees and building the structured data
 * that populates the "Digital ID Card" sidebar panels.
 *
 * Returns plain PHP arrays (never HTML) so the JavaScript frontend
 * builds the DOM safely — eliminating XSS risk from server-generated HTML.
 */
class EmployeeCardService
{
    // ─────────────────────────────────────────────
    //  Classification
    // ─────────────────────────────────────────────

    private const PEDAGOGICAL_KEYWORDS = [
        'أستاذ', 'مكون', 'مؤطر', 'رئيس أشغال', 'بيداغوجي', 'تدريس', 'تكوين', 'تعليم', 'مدرس',
    ];

    private const ADMIN_KEYWORDS = [
        'حارس', 'سائق', 'عامل مهني', 'منظف', 'أمن', 'حراسة', 'خدمة', 'حاجب', 'طباخ',
        'مقتصد', 'محاسب', 'إداري', 'مكتب', 'كاتب', 'متصرف', 'مديرية', 'بستاني',
        'نسخ', 'موزع', 'مقسم', 'أمين', 'أمينة',
    ];

    /** Grade IDs that are always non-pedagogical (guards, drivers, workers) */
    private const NON_PEDAGOGICAL_GRADE_IDS = [101, 102, 103, 104, 105, 106];

    /**
     * Determine whether this employee is pedagogical or administrative staff.
     */
    public function isPedagogicalStaff(array $emp): bool
    {
        $grade    = mb_strtolower($emp['grade_nom'] ?? ($emp['Grade'] ?? ''));
        $task     = mb_strtolower($emp['TachesPrincipale'] ?? '');
        $spec     = mb_strtolower($emp['Specialite'] ?? '');

        // IDGrade override — always non-pedagogical
        if (in_array((int)($emp['IDGrade'] ?? 0), self::NON_PEDAGOGICAL_GRADE_IDS, true)) {
            return false;
        }

        // Admin grade keywords override pedagogical keywords
        foreach (self::ADMIN_KEYWORDS as $kw) {
            if (mb_stripos($grade, $kw) !== false) {
                return false;
            }
        }

        // Pedagogical indicators in grade, task, or specialty
        foreach (self::PEDAGOGICAL_KEYWORDS as $kw) {
            if (
                mb_stripos($grade, $kw) !== false ||
                mb_stripos($task,  $kw) !== false ||
                mb_stripos($spec,  $kw) !== false
            ) {
                return true;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────
    //  Structured career data (replaces getDynamicWidgetHtml)
    // ─────────────────────────────────────────────

    /**
     * Build raw task/activity rows for the "Career" sidebar tab.
     * Returns a plain PHP array — the frontend JavaScript renders safe HTML from it.
     */
    public function buildTachesData(array $emp): array
    {
        $isPedagogical = $this->isPedagogicalStaff($emp);
        $specialty     = $emp['Specialite'] ?? 'غير محددة';
        $task          = $emp['TachesPrincipale'] ?? ($isPedagogical ? 'مؤطر بيداغوجي' : 'عون دعم إداري');
        $grade         = mb_strtolower($emp['grade_nom'] ?? ($emp['Grade'] ?? ''));

        if ($isPedagogical) {
            return [
                'type' => 'pedagogical',
                'rows' => [
                    ['activity' => $task,                                'specialty' => $specialty, 'nature' => 'تأطير بيداغوجي وتسيير ورشات', 'hours' => '18 ساعة / أسبوع',   'status' => 'active'],
                    ['activity' => 'أعمال تطبيقية ومرافقة متمهنين', 'specialty' => $specialty, 'nature' => 'زيارات ميدانية للمؤسسات',       'hours' => '6 ساعات / أسبوع', 'status' => 'progress'],
                ],
            ];
        }

        $rows = $this->resolveAdminRows($grade);

        return ['type' => 'administrative', 'rows' => $rows];
    }

    /**
     * Resolve task rows for administrative / support staff based on grade keywords.
     */
    private function resolveAdminRows(string $grade): array
    {
        if (mb_stripos($grade, 'حارس') !== false || mb_stripos($grade, 'أمن') !== false) {
            return [
                ['activity' => 'حراسة وتأمين مرافق المؤسسة والتحقق من الهويات',         'office' => 'المدخل الرئيسي ومركز المراقبة', 'nature' => 'دوريات بالتناوب',  'hours' => '08 ساعات / يوم',     'status' => 'active'],
                ['activity' => 'التبليغ عن الأعطال وضمان سلامة الأجهزة الأمنية',       'office' => 'محيط الجناح البيداغوجي',          'nature' => 'خطة الطوارئ',       'hours' => 'مستمر',              'status' => 'progress'],
            ];
        }

        if (mb_stripos($grade, 'سائق') !== false) {
            return [
                ['activity' => 'تأمين تنقلات الموظفين والوفود الرسمية',       'office' => 'حظيرة السيارات',              'nature' => 'تأمين تحركات الهيئة', 'hours' => '8:00 - 16:30',        'status' => 'active'],
                ['activity' => 'الصيانة الدورية للمركبة الرسمية',             'office' => 'مصلحة الوسائل العامة',         'nature' => 'صيانة وقائية',         'hours' => '4 ساعات / أسبوع',    'status' => 'progress'],
            ];
        }

        if (
            mb_stripos($grade, 'إداري') !== false ||
            mb_stripos($grade, 'كاتب') !== false  ||
            mb_stripos($grade, 'متصرف') !== false ||
            mb_stripos($grade, 'أمين') !== false  ||
            mb_stripos($grade, 'محاسب') !== false
        ) {
            return [
                ['activity' => 'معالجة وتوثيق الملفات الإدارية والمراسلات الرسمية', 'office' => 'مصلحة الموظفين / الأمانة العامة', 'nature' => 'تسيير الأرشيف والرقمنة', 'hours' => '8:00 - 16:30', 'status' => 'active'],
                ['activity' => 'إعداد التقارير الدورية وتحيين السجلات',             'office' => 'مكتب الإدارة والوسائل العامة',    'nature' => 'تنسيق داخلي',             'hours' => '8:00 - 16:30', 'status' => 'progress'],
            ];
        }

        // Default
        return [
            ['activity' => 'متابعة تسيير الوسائل العامة وصيانة المرفق',     'office' => 'مصلحة الصيانة والوسائل العامة', 'nature' => 'تنفيذ التكاليف الإدارية', 'hours' => '8:00 - 16:30', 'status' => 'active'],
            ['activity' => 'تقديم الدعم اللوجستي وتأمين متطلبات الأجنحة', 'office' => 'هياكل المؤسسة المختلفة',           'nature' => 'دعم ميداني',                'hours' => 'عند الطلب',     'status' => 'progress'],
        ];
    }

    // ─────────────────────────────────────────────
    //  Career timeline (replaces getDynamicTimelineHtml)
    // ─────────────────────────────────────────────

    /**
     * Build raw career milestone events for the "Path" sidebar tab.
     * Returns a plain PHP array — the frontend JavaScript renders safe HTML from it.
     */
    public function buildCareerMilestones(array $emp): array
    {
        $echelon       = (int)($emp['Echlo'] ?? 1);
        $spec          = $emp['Specialite'] ?? 'التخصص المهني';
        $grade         = $emp['grade_nom'] ?? ($emp['Grade'] ?? 'الرتبة المعنية');
        $dateRecr      = $emp['Daterecr'] ?? null;
        $isPedagogical = $this->isPedagogicalStaff($emp);

        if ($isPedagogical) {
            return [
                ['color' => 'success', 'title' => 'آخر ترقية اختيارية في الدرجة',   'desc' => 'الترقية إلى الدرجة ' . $echelon . ' بناءً على تقييم الأداء السنوي ونشاط التأطير.'],
                ['color' => 'primary', 'title' => 'إثبات التوظيف وتأكيد الرتبة', 'desc' => ($dateRecr ?? '—') . ' • تثبيت في سلك التأطير لتخصص ' . $spec . '.'],
            ];
        }

        return [
            ['color' => 'success', 'title' => 'ترقية اختيارية في الدرجة الإدارية', 'desc' => 'الترقية إلى الدرجة ' . $echelon . ' بناءً على نقطة المردودية والالتزام المستمر.'],
            ['color' => 'info',    'title' => 'التثبيت والترسيم في المنصب الإداري', 'desc' => 'تم التثبيت رسمياً في رتبة ' . $grade . ' بعد انقضاء فترة التربص بنجاح.'],
            ['color' => 'primary', 'title' => 'التوظيف والتعيين الأولي',            'desc' => ($dateRecr ?? '—') . ' • تعيين بصفة متربص في رتبة ' . $grade . ' بالمؤسسة التكوينية.'],
        ];
    }

    // ─────────────────────────────────────────────
    //  Enrichment (convenience wrapper)
    // ─────────────────────────────────────────────

    /**
     * Add all computed fields to an employee array.
     * Call this after fetching from DB, before sanitization.
     */
    public function enrich(array $employee): array
    {
        $sitFamilleMap = [1 => 'أعزب / عزباء', 2 => 'متزوج / متزوجة', 3 => 'مطلق / مطلقة', 4 => 'أرمل / أرملة'];

        $employee['sitfamille_text']       = $sitFamilleMap[(int)($employee['IDSitfamille'] ?? 1)] ?? 'غير محدد';
        $employee['is_pedagogical']        = $this->isPedagogicalStaff($employee);
        $employee['taches_data']           = $this->buildTachesData($employee);
        $employee['career_milestones']     = $this->buildCareerMilestones($employee);
        $employee['paystub_code']          = 'FP-ENC-' . sprintf('%03d', ((int)($employee['IDEncadrement'] ?? 0)) % 1000);
        $employee['work_certificate_code'] = 'AT-ENC-' . sprintf('%03d', ((int)($employee['IDEncadrement'] ?? 0)) % 1000);

        $militaireMap = [1 => 'مؤدى', 2 => 'معفى', 3 => 'مؤجل', 4 => 'غير معني'];
        $employee['sit_militaire_text']    = $militaireMap[(int)($employee['SitMilitaire'] ?? 4)] ?? 'غير معني';
        $employee['endicape_text']         = ((int)($employee['endicape'] ?? 0) === 1) ? 'نعم' : 'لا';

        $id = (int)($employee['IDEncadrement'] ?? 0);
        $employee['grades_history'] = \Illuminate\Support\Facades\DB::select("
            SELECT eg.*, g.Nom AS grade_nom, sa.Nom AS situation_nom, mp.Nom AS mode_promotion_nom
            FROM encadrement_grade eg
            LEFT JOIN grade g ON eg.IDGrade = g.IDGrade
            LEFT JOIN situationadministrat sa ON eg.IDSituationAdministrat = sa.IDSituationAdministrat
            LEFT JOIN mode_promotion mp ON eg.IDMode_Promotion = mp.IDMode_Promotion
            WHERE eg.IDEncadrement = ?
            ORDER BY eg.dateinstal DESC
        ", [$id]);

        $employee['fonctions_history'] = \Illuminate\Support\Facades\DB::select("
            SELECT ef.*, f.Nom AS fonction_nom, sa.Nom AS situation_nom
            FROM encadrement_fonctions ef
            LEFT JOIN fonctions f ON ef.IDFonctions = f.IDFonctions
            LEFT JOIN situationadministrat sa ON ef.IDSituationAdministrat = sa.IDSituationAdministrat
            WHERE ef.IDEncadrement = ?
            ORDER BY ef.Dateinstal DESC
        ", [$id]);

        return $employee;
    }
}
