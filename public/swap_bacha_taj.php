<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

header('Content-Type: text/html; charset=utf-8');

echo "<h2>جاري معالجة تبادل معرفات العروض والأقسام (Swapped Child Records) لمؤسستي التاج الأزرق ومدرسة الباشا...</h2>";

try {
    DB::beginTransaction();
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // 1. Swap in offre table
    // A. Temporarily change 1301 to 999999
    $a = DB::table('offre')->where('IDEts_Form', 1301)->update(['IDEts_Form' => 999999]);
    // B. Change 1302 to 1301, and set supervising parent to 352 (Setif)
    $b = DB::table('offre')->where('IDEts_Form', 1302)->update(['IDEts_Form' => 1301, 'IDEts_FormM' => 352]);
    // C. Change 999999 to 1302, and set supervising parent to 203 (Biskra)
    $c = DB::table('offre')->where('IDEts_Form', 999999)->update(['IDEts_Form' => 1302, 'IDEts_FormM' => 203]);

    echo "✓ تم تحديث جدول العروض (offre): تم تحويل $a صف إلى التاج الأزرق، و $b صف إلى مدرسة الباشا.<br>";

    // 2. Swap in section table
    // A. Temporarily change 1301 to 999999
    $d = DB::table('section')->where('IDEts_Form', 1301)->update(['IDEts_Form' => 999999]);
    // B. Change 1302 to 1301, and set supervising parent to 352 (Setif)
    $e = DB::table('section')->where('IDEts_Form', 1302)->update(['IDEts_Form' => 1301, 'IDEts_FormM' => 352]);
    // C. Change 999999 to 1302, and set supervising parent to 203 (Biskra)
    $f = DB::table('section')->where('IDEts_Form', 999999)->update(['IDEts_Form' => 1302, 'IDEts_FormM' => 203]);

    echo "✓ تم تحديث جدول الأقسام (section): تم تحويل $d صف إلى التاج الأزرق، و $e صف إلى مدرسة الباشا.<br>";

    // 3. Correct parent for etab 1685 (معهد الآفاق الإدريسية)
    $g = DB::table('etablissement')
        ->where('IDetablissement', 1685)
        ->update([
            'DeIDetablissementRatache' => 71,
            'DeIDetablissementRatacheInsfp' => 0
        ]);
    echo "✓ تم تصحيح ربط معهد الآفاق الإدريسية (1685) بالمركز العمومي بالإدريسية (71).<br>";

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    DB::commit();

    // Clear all cache
    Cache::flush();

    echo "<h3 style='color:green;'>✓ تم تصحيح ربط العروض والأقسام بنجاح تام!</h3>";
    echo "الآن:<br>";
    echo "- العروض والأقسام المنسوبة لـ <b>مؤسسة التاج الأزرق</b> (1301) ترتبط بالهضاب سطيف (352).<br>";
    echo "- العروض والأقسام المنسوبة لـ <b>مدرسة الباشا</b> (1302) ترتبط ببسكرة (203).<br>";

} catch (\Throwable $e) {
    DB::rollBack();
    echo "<h3 style='color:red;'>✗ حدث خطأ أثناء التحديث: " . $e->getMessage() . "</h3>";
}
