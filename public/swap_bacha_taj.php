<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

header('Content-Type: text/html; charset=utf-8');

echo "<h2>جاري معالجة مشكلة تبادل المعرفات (Swap) لمؤسستي التاج الأزرق ومدرسة الباشا...</h2>";

try {
    DB::beginTransaction();

    // 1. Fetch original rows
    $etab1301 = DB::table('etablissement')->where('IDetablissement', 1301)->first();
    $etab1302 = DB::table('etablissement')->where('IDetablissement', 1302)->first();

    if (!$etab1301 || !$etab1302) {
        throw new Exception("لم يتم العثور على إحدى المؤسستين في قاعدة البيانات!");
    }

    echo "الحالة الحالية في MySQL قبل الإصلاح:<br>";
    echo "- معرف 1301: " . $etab1301->Nom . " (مستخدم: " . $etab1301->nomUser . ")<br>";
    echo "- معرف 1302: " . $etab1302->Nom . " (مستخدم: " . $etab1302->nomUser . ")<br><br>";

    // Convert stdClass to array for swapping
    $cols1 = (array)$etab1301;
    $cols2 = (array)$etab1302;

    // Sanitize any '0000-00-00' dates to null to avoid MySQL strict mode errors
    $sanitizeZeroDates = function(&$arr) {
        unset($arr['IDetablissement']); // Do not update the primary key
        foreach ($arr as $key => $val) {
            if ($val === '0000-00-00') {
                $arr[$key] = null;
            }
        }
    };
    $sanitizeZeroDates($cols1);
    $sanitizeZeroDates($cols2);

    // 2. Temporarily update 1302 to prevent unique constraint conflicts
    DB::table('etablissement')->where('IDetablissement', 1302)->update([
        'IDEts_Form' => 999999,
        'nomUser' => 'PETTS_temp'
    ]);

    // 3. Update row 1301 with cols2 (original 1302 - Bacha values)
    DB::table('etablissement')->where('IDetablissement', 1301)->update($cols2);

    // 4. Update row 1302 with cols1 (original 1301 - Taj values)
    DB::table('etablissement')->where('IDetablissement', 1302)->update($cols1);

    // 5. Restore proper parent relationships in offers table
    // Bacha offers (IDEts_Form = 1301) belong to Biskra (IDEts_FormM = 203)
    $bachaOffres = DB::table('offre')->where('IDEts_Form', 1301)->update(['IDEts_FormM' => 203]);
    // Taj offers (IDEts_Form = 1302) belong to Setif (IDEts_FormM = 352)
    $tajOffres = DB::table('offre')->where('IDEts_Form', 1302)->update(['IDEts_FormM' => 352]);

    // 6. Restore proper parent relationships in sections table
    $bachaSections = DB::table('section')->where('IDEts_Form', 1301)->update(['IDEts_FormM' => 203]);
    $tajSections = DB::table('section')->where('IDEts_Form', 1302)->update(['IDEts_FormM' => 352]);

    DB::commit();

    // Clear all cache
    Cache::flush();

    echo "<h3 style='color:green;'>✓ تم تصحيح البيانات والربط بنجاح تام!</h3>";
    echo "الآن:<br>";
    echo "- معرف 1301 أصبح: <b>مدرسة الباشا للإعلام الآلي</b> (ومربوطة ببسكرة 203) وحسابها <b>PETTS</b><br>";
    echo "- معرف 1302 أصبح: <b>مؤسسة التاج الازرق</b> (ومربوطة بالهضاب سطيف 352) وحسابها <b>rUQ1300</b><br>";
    echo "- تم تصحيح ربط جميع العروض والأقسام الخاصة بكل مؤسسة.<br>";

} catch (\Throwable $e) {
    DB::rollBack();
    echo "<h3 style='color:red;'>✗ حدث خطأ أثناء التحديث: " . $e->getMessage() . "</h3>";
}
