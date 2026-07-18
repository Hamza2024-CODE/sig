<?php
require __DIR__.'/../vendor/autoload.php';
ini_set('user_agent', 'PHP');
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (isset($_GET['debug_db'])) {
    header('Content-Type: text/plain; charset=utf-8');
    try {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $t0 = microtime(true);
        $db->query("SELECT COUNT(*) FROM branche")->fetchColumn();
        $t1 = microtime(true);
        echo "1. branche count: " . round(($t1 - $t0) * 1000, 1) . " ms\n";

        $t0 = microtime(true);
        $db->query("SELECT COUNT(*) FROM specialite")->fetchColumn();
        $t1 = microtime(true);
        echo "2. specialite count: " . round(($t1 - $t0) * 1000, 1) . " ms\n";

        $t0 = microtime(true);
        $db->query("SELECT COUNT(*) FROM section")->fetchColumn();
        $t1 = microtime(true);
        echo "3. section count: " . round(($t1 - $t0) * 1000, 1) . " ms\n";

        $t0 = microtime(true);
        $db->query("SELECT IDetablissement, Nom, Abr, NomFr FROM etablissement")->fetchAll(PDO::FETCH_ASSOC);
        $t1 = microtime(true);
        echo "4. etablissement describe/fetch: " . round(($t1 - $t0) * 1000, 1) . " ms\n";

        $t0 = microtime(true);
        $db->query("SELECT IDBranche, Code, Nom, NomFr FROM branche")->fetchAll(PDO::FETCH_ASSOC);
        $t1 = microtime(true);
        echo "5. branch fetch: " . round(($t1 - $t0) * 1000, 1) . " ms\n";

        $t0 = microtime(true);
        $db->query("SELECT IDSpecialite, IDBranche FROM specialite")->fetchAll(PDO::FETCH_ASSOC);
        $t1 = microtime(true);
        echo "6. specialite fetch: " . round(($t1 - $t0) * 1000, 1) . " ms\n";

        $t0 = microtime(true);
        $db->query("SELECT IDEts_Form, IDSpecialite, NbrInscr, NbrInscrf FROM offre")->fetchAll(PDO::FETCH_ASSOC);
        $t1 = microtime(true);
        echo "7. offre fetch (full): " . round(($t1 - $t0) * 1000, 1) . " ms\n";

    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    exit;
}

use Illuminate\Support\Facades\Artisan;

echo "<h1>Auto-Updating All Modified Files...</h1>";

$files = [
    'resources/views/layouts/main.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/layouts/main.blade.php',
    'resources/views/admin/grades/index.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/grades/index.blade.php',
    'resources/views/admin/grades/input.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/grades/input.blade.php',
    'app/Http/Controllers/Admin/GradesController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/GradesController.php',
    'app/Http/Controllers/Admin/ApprenantController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ApprenantController.php',
    'app/Domains/Academic/Services/ApprenantService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Services/ApprenantService.php',
    'app/Domains/Academic/Services/GradingSystemService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Services/GradingSystemService.php',
    'app/Domains/Academic/Repositories/OffresRepository.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Repositories/OffresRepository.php',
    'resources/views/dashboard/index.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/dashboard/index.blade.php',
    'app/Http/Controllers/DashboardController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/DashboardController.php',
    'app/Services/KpiCache.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Services/KpiCache.php',
    'app/Http/Controllers/Admin/CandidatController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/CandidatController.php',
    'app/Domains/Academic/Services/CandidatService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Services/CandidatService.php',
    'app/Domains/Academic/Services/OffresService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Services/OffresService.php',
    'app/Http/Controllers/Admin/OffresController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/OffresController.php',
    'app/Helpers/BepGradingHelper.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Helpers/BepGradingHelper.php',
    'app/Helpers/DepartmentHelper.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Helpers/DepartmentHelper.php',
    'public/check_view_content.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/check_view_content.php',
    'public/check_etab_details.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/check_etab_details.php',
    'public/check_etab_cols.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/check_etab_cols.php',
    'public/check_sessions.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/check_sessions.php',
    'public/set_oran_credentials.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/set_oran_credentials.php',
    'public/sig_login_guide.html' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/sig_login_guide.html',
    'public/sig_grading_guide.html' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/sig_grading_guide.html',
    'resources/views/admin/modules/print_template.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/print_template.blade.php',
    'app/Http/Controllers/Admin/ModulesController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ModulesController.php',
    'app/Providers/AppServiceProvider.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Providers/AppServiceProvider.php',
    'app/Http/Middleware/SecurityHeadersMiddleware.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Middleware/SecurityHeadersMiddleware.php',
    'public/fix_https_env.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/fix_https_env.php',
    'public/set_credentials_pdo.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/set_credentials_pdo.php',
    'public/pull.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/pull.php'
];

foreach ($files as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    
    // Create directory if it doesn't exist
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    try {
        $content = @file_get_contents($remoteUrl . '?v=' . time());
        if ($content !== false) {
            file_put_contents($fullPath, $content);
            clearstatcache(true, $fullPath);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($fullPath, true);
            }
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            echo "✓ Updated: $localPath (" . strlen($content) . " bytes)<br>";
        } else {
            echo "<span style='color:red;'>✗ Failed to download: $localPath</span><br>";
        }
    } catch (\Throwable $e) {
        echo "<span style='color:red;'>✗ Exception downloading $localPath: " . $e->getMessage() . "</span><br>";
    }
}

echo "<h2>Clearing Laravel Cache and Views...</h2>";

// Helper function to recursively empty directories
$clearDir = function($dir) {
    if (!is_dir($dir)) return;
    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = $fileinfo->getRealPath();
            if ($fileinfo->isDir()) {
                @rmdir($todo);
            } else {
                @unlink($todo);
            }
        }
    } catch (\Throwable $ex) {}
};

// 1. Clear view cache via filesystem
$viewCacheDir = __DIR__ . '/../storage/framework/views';
$clearDir($viewCacheDir);
echo "✓ View Cache Cleared (Filesystem Traversal)<br>";

// 2. Clear application cache via filesystem
$appCacheDir = __DIR__ . '/../storage/framework/cache/data';
$clearDir($appCacheDir);
echo "✓ Application Cache Cleared (Filesystem Traversal)<br>";

try {
    \Illuminate\Support\Facades\Cache::flush();
    echo "✓ Laravel Cache::flush() executed successfully!<br>";
} catch (\Throwable $ex) {
    echo "✗ Direct Cache::flush() failed: " . $ex->getMessage() . "<br>";
}

// 3. Clear bootstrap cache (services.php, packages.php)
$bootstrapCacheDir = __DIR__ . '/../bootstrap/cache';
if (is_dir($bootstrapCacheDir)) {
    foreach (glob($bootstrapCacheDir . '/*.php') as $f) {
        @unlink($f);
    }
    echo "✓ Bootstrap Cache files Cleared<br>";
}

// 4. Try Artisan commands individually as optional fallback
try {
    @Artisan::call('view:clear');
    @Artisan::call('cache:clear');
    @Artisan::call('route:clear');
    echo "✓ Optional Artisan Cache Commands invoked<br>";
} catch (\Throwable $e) {
    echo "<i>Note: Artisan command skipped/failed (" . $e->getMessage() . ") - Filesystem cleaning used instead.</i><br>";
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPCache Cleared!<br>";
}

echo "<h2>Running Global Terminology Replacements on Server...</h2>";
try {
    $targetDirs = [
        __DIR__ . '/../resources/views',
        __DIR__ . '/../app/Http/Controllers'
    ];

    $replacements = [
        '/(?<!\\p{L})المتربصين والطلاب(?!\\p{L})/u' => 'المتربصين',
        '/(?<!\\p{L})إحصائيات المتربصين والطلاب(?!\\p{L})/u' => 'إحصائيات المتربصين',
        '/(?<!\\p{L})تقرير المتربصين والطلاب(?!\\p{L})/u' => 'تقرير المتربصين',
        '/(?<!\\p{L})الطلاب(?!\\p{L})/u' => 'المتربصين',
        '/(?<!\\p{L})الطلبة(?!\\p{L})/u' => 'المتربصين',
        '/(?<!\\p{L})طلاب(?!\\p{L})/u' => 'متربصين',
        '/(?<!\\p{L})طالبي التكوين(?!\\p{L})/u' => 'متربصي التكوين',
        '/(?<!\\p{L})طالبي(?!\\p{L})/u' => 'متربصي',
        '/(?<!\\p{L})طالبين(?!\\p{L})/u' => 'متربصين',
        '/(?<!\\p{L})طالبان(?!\\p{L})/u' => 'متربصان',
        '/(?<!\\p{L})طالبون(?!\\p{L})/u' => 'متربصون',
        '/(?<!\\p{L})طالباً(?!\\p{L})/u' => 'متربصاً',
        '/(?<!\\p{L})الطالبات(?!\\p{L})/u' => 'المتربصات',
        '/(?<!\\p{L})طالبة(?!\\p{L})/u' => 'متربصة',
        '/(?<!\\p{L})طالب(?!\\p{L})/u' => 'متربص'
    ];

    $modifiedCount = 0;
    foreach ($targetDirs as $dir) {
        if (!is_dir($dir)) continue;
        $it = new RecursiveDirectoryIterator($dir);
        $display = new RecursiveIteratorIterator($it);
        foreach ($display as $file) {
            if ($file->isFile()) {
                $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
                if (in_array($ext, ['php', 'html'])) {
                    $filePath = $file->getPathname();
                    $content = file_get_contents($filePath);
                    $original = $content;
                    foreach ($replacements as $pattern => $replacement) {
                        $content = preg_replace($pattern, $replacement, $content);
                    }
                    if ($content !== $original) {
                        file_put_contents($filePath, $content);
                        $modifiedCount++;
                    }
                }
            }
        }
    }
    echo "✓ Global Terminology Update: modified $modifiedCount files.<br>";
} catch (\Throwable $e) {
    echo "<span style='color:red;'>Error running terminology replacement: " . $e->getMessage() . "</span><br>";
}

// Clean up temporary check_index.php if it exists
$checkIndexPath = __DIR__ . '/check_index.php';
if (file_exists($checkIndexPath)) {
    @unlink($checkIndexPath);
    echo "✓ Diagnostic check_index.php cleaned up.<br>";
}

echo "<h2>Running View Compilation Diagnostics...</h2>";
try {
    // Attempt compile-rendering department views to check for syntax/variable errors
    $html = view('dashboard.departments.exam')->render();
    echo "✓ SUCCESS: Exam view compiled and rendered fine!<br>";
} catch (\Throwable $e) {
    echo "<span style='color:red;font-weight:bold;'>[DIAGNOSTICS EXCEPTION]: " . get_class($e) . "</span><br>";
    echo "<span style='color:red;'>MESSAGE: " . $e->getMessage() . "</span><br>";
    echo "<span style='color:red;'>FILE: " . $e->getFile() . "</span><br>";
    echo "<span style='color:red;'>LINE: " . $e->getLine() . "</span><br>";
}

echo "<br><h3 style='color:green;'>All updates completed successfully!</h3>";
