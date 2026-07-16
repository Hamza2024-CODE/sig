<?php

namespace App\Helpers;

/**
 * هيلبر تقييم وحساب نتائج شهادة التعليم المهني (BEP)
 * 
 * !!! مهم جداً !!!
 * نطاق التطبيق: نمط التعليم المهني (حصرياً)
 * هذا الكود مخصص بصفة استثنائية وحصرية لنمط التعليم المهني ولا يُخلط مع الأنماط الأخرى.
 */
class BepGradingHelper
{
    /**
     * حساب المعدل السنوي لمواد التعليم العام ومرحلة الانتقال.
     * (معدل الثلاثيات الثلاث)
     *
     * @param float $t1 معدل الثلاثي الأول
     * @param float $t2 معدل الثلاثي الثاني
     * @param float $t3 معدل الثلاثي الثالث
     * @return array يحتوي على المعدل والقرار (نجاح مباشر، إنقاذ، إعادة السنة)
     */
    public static function calculateAnnualGeneralEdAverage(float $t1, float $t2, float $t3): array
    {
        $average = round((($t1 + $t2 + $t3) / 3.0) * 100) / 100;
        
        $decision = 'إعادة السنة';
        if ($average >= 10.00) {
            $decision = 'نجاح مباشر';
        } elseif ($average >= 9.00) {
            $decision = 'نجاح بالإنقاذ';
        }
        
        return [
            'average' => $average,
            'decision' => $decision
        ];
    }

    /**
     * تقييم امتحان الـ ECF (الامتحانات أثناء التكوين الخاصة بالتخصص) للانتقال السنوي.
     *
     * @param float $ecfMark نقطة الـ ECF
     * @return array يحتوي على النقطة والقرار
     */
    public static function evaluateEcf(float $ecfMark): array
    {
        $decision = 'إعادة السنة';
        if ($ecfMark >= 10.00) {
            $decision = 'نجاح مباشر';
        } elseif ($ecfMark >= 9.00) {
            $decision = 'نجاح بالإنقاذ';
        }
        
        return [
            'mark' => $ecfMark,
            'decision' => $decision
        ];
    }

    /**
     * حساب النقطة النهائية للمترشح لشهادة التعليم المهني (BEP) في السنة الثالثة.
     * المعادلة: (معدل الـ ECF للسنوات الـ3 * 3/6) + (المعدل العام لمواد التعليم العام للسنوات الـ3 * 2/6) + (تقرير نهاية الدراسة * 1/6)
     *
     * @param float $ecf3YearsAvg معدل امتحانات الـ ECF للسنوات الثلاثة
     * @param float $generalEd3YearsAvg المعدل العام للتعليم العام للسنوات الثلاثة
     * @param float $endOfStudyReport نقطة تقرير نهاية الدراسة
     * @return array يحتوي على المعدل النهائي والقرار النهائي للمترشح
     */
    public static function calculateFinalBepMark(float $ecf3YearsAvg, float $generalEd3YearsAvg, float $endOfStudyReport): array
    {
        // تطبيق المعاملات: ECF (3/6)، التعليم العام (2/6)، التقرير (1/6)
        $finalMark = ($ecf3YearsAvg * (3.0 / 6.0)) + ($generalEd3YearsAvg * (2.0 / 6.0)) + ($endOfStudyReport * (1.0 / 6.0));
        $finalMark = round($finalMark * 100) / 100;
        
        $decision = 'راسب';
        if ($finalMark >= 10.00) {
            $decision = 'نجاح مباشر';
        } elseif ($finalMark >= 9.00) {
            $decision = 'نجاح بالإنقاذ';
        }
        
        return [
            'final_mark' => $finalMark,
            'decision' => $decision
        ];
    }
}
