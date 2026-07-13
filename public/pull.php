<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (isset($_GET['debug_db'])) {
    header('Content-Type: text/plain; charset=utf-8');
    try {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        echo "1. Checking table columns for 'ets_form':\n";
        $cols = $db->query("DESCRIBE ets_form")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo " - {$c['Field']} ({$c['Type']})\n";
        }

        echo "\n2. Checking table columns for 'etablissement':\n";
        $cols2 = $db->query("DESCRIBE etablissement")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols2 as $c) {
            echo " - {$c['Field']} ({$c['Type']})\n";
        }

        echo "\n3. Testing the subquery for filter_wilaya = 1:\n";
        $wId = 1;
        $sql = "
            SELECT ets.IDEts_Form FROM ets_form ets
            INNER JOIN etablissement e ON ets.IDetablissement = e.IDetablissement
            WHERE e.IDDFEP = $wId
            UNION
            SELECT e2.IDetablissement FROM etablissement e2
            LEFT JOIN ets_form ets2 ON ets2.IDetablissement = e2.IDetablissement
            WHERE e2.IDDFEP = $wId AND ets2.IDEts_Form IS NULL
        ";
        $db->query($sql)->fetchAll();
        echo " -> Subquery succeeded!\n";

    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
        echo $e->getTraceAsString() . "\n";
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
    'resources/views/admin/modules/formation.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/formation.blade.php'
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

echo "<br><h3 style='color:green;'>All updates completed successfully!</h3>";
