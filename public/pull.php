<?php
require __DIR__.'/../vendor/autoload.php';
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
    'app/Domains/Academic/Repositories/OffresRepository.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Repositories/OffresRepository.php',
    'resources/views/admin/offres/index.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/offres/index.blade.php',
    'app/Http/Controllers/Admin/PreinscritController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/PreinscritController.php',
    'app/Http/Controllers/Admin/ModulesController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ModulesController.php',
    'app/Domains/Academic/Services/OffresService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Services/OffresService.php',
    'public/upload_photos.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/upload_photos.php',
    'public/check_ssh_port.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/check_ssh_port.php',
    'app/Http/Controllers/Admin/OffresController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/OffresController.php',
    'resources/views/admin/offres/validation.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/offres/validation.blade.php',
    'resources/views/layouts/main.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/layouts/main.blade.php',
    'resources/views/dashboard/departments/trak.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/dashboard/departments/trak.blade.php',
    'resources/views/admin/modules/distribution_detaillee.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/distribution_detaillee.blade.php',
    'resources/views/admin/modules/distribution_detaillee_pdf.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/distribution_detaillee_pdf.blade.php',
    'app/Http/Controllers/Formation/FormationController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Formation/FormationController.php',
    'resources/views/admin/modules/formation.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/formation.blade.php',
    'resources/views/admin/modules/inscriptions.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/inscriptions.blade.php',
    'app/Http/Controllers/Admin/SectionController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/SectionController.php',
    'resources/views/admin/sections/index.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/sections/index.blade.php',
    'app/Http/Controllers/Admin/ApprenantController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ApprenantController.php',
    'resources/views/admin/apprenants/index.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/apprenants/index.blade.php',
    'routes/web.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/routes/web.php',
    'resources/views/layouts/public.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/layouts/public.blade.php',
    'resources/views/layouts/apprenant.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/layouts/apprenant.blade.php',
    'resources/views/dashboard/departments/edu.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/dashboard/departments/edu.blade.php',
    'resources/views/admin/modules/documents.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/documents.blade.php',
    'resources/views/admin/modules/effectifs.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/effectifs.blade.php',
    'resources/views/admin/modules/integration.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/integration.blade.php',
    'resources/views/admin/modules/repas.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/repas.blade.php',
    'app/Http/Controllers/Admin/EspaceEmployeController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/EspaceEmployeController.php',
    'app/Http/Controllers/Admin/DiplomeController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/DiplomeController.php',
    'public/pull.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/pull.php'
];

foreach ($files as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    
    // Create directory if it doesn't exist
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $content = @file_get_contents($remoteUrl . '?v=' . time());
    if ($content !== false) {
        file_put_contents($fullPath, $content);
        clearstatcache(true, $fullPath);
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($fullPath, true);
        }
        echo "✓ Updated: $localPath (" . strlen($content) . " bytes)<br>";
    } else {
        echo "<span style='color:red;'>✗ Failed to download: $localPath</span><br>";
    }
}

echo "<h2>Clearing Laravel Cache and Views...</h2>";
try {
    Artisan::call('view:clear');
    echo "✓ View Cache Cleared: " . Artisan::output() . "<br>";
    
    Artisan::call('cache:clear');
    echo "✓ Application Cache Cleared: " . Artisan::output() . "<br>";
    
    Artisan::call('route:clear');
    echo "✓ Route Cache Cleared: " . Artisan::output() . "<br>";
    
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✓ OPCache Cleared!<br>";
    }
} catch (\Throwable $e) {
    echo "<span style='color:red;'>Error clearing cache: " . $e->getMessage() . "</span><br>";
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

echo "<br><h3 style='color:green;'>All updates completed successfully!</h3>";
