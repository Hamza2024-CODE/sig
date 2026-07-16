<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');

echo "<h2>جاري تصحيح روابط جميع المؤسسات الخاصة البالغ عددها 756...</h2>";

try {
    $etabs = DB::table('etablissement')
        ->where('PublPrive', 1)
        ->get();

    $processed = 0;
    $totalOffresUpdated = 0;
    $totalSectionsUpdated = 0;

    DB::beginTransaction();

    foreach ($etabs as $e) {
        $etabId = (int)$e->IDetablissement;
        $cfpaId = (int)$e->DeIDetablissementRatache;
        $insfpId = (int)$e->DeIDetablissementRatacheInsfp;

        // Determine the correct public supervising center ID
        $supervisingId = $insfpId > 0 ? $insfpId : ($cfpaId > 0 ? $cfpaId : 0);

        if ($supervisingId > 0) {
            // Update offers ONLY if they don't have a supervising center (0 or null)
            $offresCount = DB::table('offre')
                ->where('IDEts_Form', $etabId)
                ->where(function($query) {
                    $query->whereNull('IDEts_FormM')
                          ->orWhere('IDEts_FormM', 0);
                })
                ->update(['IDEts_FormM' => $supervisingId]);

            // Update sections ONLY if they don't have a supervising center (0 or null)
            $sectionsCount = DB::table('section')
                ->where('IDEts_Form', $etabId)
                ->where(function($query) {
                    $query->whereNull('IDEts_FormM')
                          ->orWhere('IDEts_FormM', 0);
                })
                ->update(['IDEts_FormM' => $supervisingId]);

            if ($offresCount > 0 || $sectionsCount > 0) {
                echo "✓ <b>{$e->Nom} (ID: {$etabId})</b>: تم ربطها بالمركز المشرف <b>(ID: {$supervisingId})</b> | العروض المحدثة: {$offresCount} | الأقسام المحدثة: {$sectionsCount}<br>";
                $totalOffresUpdated += $offresCount;
                $totalSectionsUpdated += $sectionsCount;
                $processed++;
            }
        }
    }

    DB::commit();

    // Clear Cache
    \Illuminate\Support\Facades\Cache::flush();

    echo "<h3 style='color:green;'>=== انتهى تحديث قاعدة البيانات بنجاح ===</h3>";
    echo "إجمالي المؤسسات الخاصة التي تم تصحيح عروضها وأقسامها: <b>$processed</b> مؤسسة.<br>";
    echo "إجمالي عروض التكوين المحدثة: <b>$totalOffresUpdated</b> عرض.<br>";
    echo "إجمالي الأقسام المحدثة: <b>$totalSectionsUpdated</b> قسم.<br>";

} catch (\Throwable $e) {
    DB::rollBack();
    echo "<h3 style='color:red;'>✗ حدث خطأ أثناء التحديث: " . $e->getMessage() . "</h3>";
}
