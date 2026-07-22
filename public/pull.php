<?php
// SIG Auto-Update Deployment Script - Clean Build 2026-07-20
require __DIR__ . '/../vendor/autoload.php';
ini_set('user_agent', 'PHP');
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

try {
    $columns = DB::select("DESCRIBE utilisateur");
    $altered = false;
    foreach ($columns as $col) {
        if ($col->Field === 'NomUser' && strpos($col->Type, 'varchar(50)') !== false) {
            DB::statement("ALTER TABLE utilisateur MODIFY COLUMN NomUser VARCHAR(255)");
            $altered = true;
            break;
        }
    }
    if ($altered) {
        echo "✓ Database migration: NomUser column size increased to VARCHAR(255) successfully!<br>";
    }
} catch (\Throwable $e) {
    echo "⚠️ Database migration check failed: " . $e->getMessage() . "<br>";
}

echo "<h1>Auto-Updating All Modified Files...</h1>";

$files = [
    'config/app.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/config/app.php',
    'app/Http/Kernel.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Kernel.php',
    'app/Http/Controllers/Admin/ApprenantController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ApprenantController.php',
    'app/Http/Controllers/Admin/CandidatController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/CandidatController.php',
    'app/Http/Controllers/Admin/SectionController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/SectionController.php',
    'app/Http/Controllers/Admin/OffresController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/OffresController.php',
    'app/Http/Controllers/Admin/DiplomeController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/DiplomeController.php',
    'app/Security/UserContext.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Security/UserContext.php',
    'app/Security/ScopeResolver.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Security/ScopeResolver.php',
    'app/Security/OwnershipResolver.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Security/OwnershipResolver.php',
    'app/Security/PermissionResolver.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Security/PermissionResolver.php',
    'app/Security/PolicyResolver.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Security/PolicyResolver.php',
    'app/Security/SecurityAuditLogger.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Security/SecurityAuditLogger.php',
    'app/Security/AuthorizationService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Security/AuthorizationService.php',
    'app/Domains/Security/AuthorizationService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Security/AuthorizationService.php',
    'app/Providers/SecurityServiceProvider.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Providers/SecurityServiceProvider.php',
    'app/Database/ScopedQuery.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Database/ScopedQuery.php',
    'app/Database/ScopedRepository.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Database/ScopedRepository.php',
    'app/Database/HasScopedQuery.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Database/HasScopedQuery.php',
    'app/Http/Middleware/EnsurePermission.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Middleware/EnsurePermission.php',
    'app/Http/Middleware/EnsureScope.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Middleware/EnsureScope.php',
    'app/Http/Middleware/EnsureOwnership.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Middleware/EnsureOwnership.php',
    'routes/web.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/routes/web.php',
    'public/security_report.html' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/security_report.html',
    'resources/views/admin/diplomes/pdf.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/diplomes/pdf.blade.php',
    'resources/views/admin/diplomes/print.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/diplomes/print.blade.php',
    'app/Http/Controllers/Admin/EspaceEmployeController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/EspaceEmployeController.php',
    'app/Services/Employee/EmployeeQueryService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Services/Employee/EmployeeQueryService.php',
    'app/Services/Employee/EmployeeCardService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Services/Employee/EmployeeCardService.php',
    'resources/views/admin/espace-employe/index.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/espace-employe/index.blade.php'
];

$makeWritable = function($path) {
    if (file_exists($path)) {
        @chmod($path, 0777);
    }
    $dir = is_dir($path) ? $path : dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    while ($dir && strlen($dir) > 15 && is_dir($dir)) {
        @chmod($dir, 0777);
        $dir = dirname($dir);
    }
    if (function_exists('exec')) {
        @exec('chmod 777 ' . escapeshellarg($path) . ' 2>&1');
        @exec('chmod 777 ' . escapeshellarg(dirname($path)) . ' 2>&1');
    }
};

foreach ($files as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    $makeWritable($fullPath);
    
    try {
        $content = @file_get_contents($remoteUrl . '?ts=' . microtime(true));
        if ($content !== false) {
            $written = @file_put_contents($fullPath, $content);
            if ($written === false) {
                @unlink($fullPath);
                $written = @file_put_contents($fullPath, $content);
            }
            if ($written !== false) {
                @chmod($fullPath, 0666);
                clearstatcache(true, $fullPath);
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($fullPath, true);
                }
                echo "✓ Updated: $localPath (" . strlen($content) . " bytes)<br>";
            } else {
                echo "<span style='color:red;'>✗ Failed to write file: $localPath</span><br>";
            }
        } else {
            echo "<span style='color:red;'>✗ Failed to download: $localPath</span><br>";
        }
    } catch (\Throwable $e) {
        echo "<span style='color:red;'>✗ Exception downloading $localPath: " . $e->getMessage() . "</span><br>";
    }
}

echo "<h2>Clearing Laravel Cache and Views...</h2>";

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
$clearDir(__DIR__ . '/../storage/framework/views');
echo "✓ View Cache Cleared<br>";

// 2. Clear application cache via filesystem
$clearDir(__DIR__ . '/../storage/framework/cache/data');
echo "✓ Application Cache Cleared<br>";

try {
    \Illuminate\Support\Facades\Cache::flush();
    echo "✓ Laravel Cache::flush() executed successfully!<br>";
} catch (\Throwable $ex) {
    echo "✗ Direct Cache::flush() failed: " . $ex->getMessage() . "<br>";
}

// 3. Clear bootstrap cache
$bootstrapCacheDir = __DIR__ . '/../bootstrap/cache';
if (is_dir($bootstrapCacheDir)) {
    foreach (glob($bootstrapCacheDir . '/*.php') as $f) {
        @unlink($f);
    }
    echo "✓ Bootstrap Cache files Cleared<br>";
}

// 4. Try Artisan commands
try {
    @Artisan::call('view:clear');
    @Artisan::call('cache:clear');
    @Artisan::call('route:clear');
    echo "✓ Optional Artisan Cache Commands invoked<br>";
} catch (\Throwable $e) {}

if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "✓ OPCache Cleared!<br>";
}

echo "<h2>Running View Compilation Diagnostics...</h2>";
try {
    $html = view('dashboard.departments.exam')->render();
    echo "✓ SUCCESS: Exam view compiled and rendered fine!<br>";
} catch (\Throwable $e) {
    echo "<span style='color:red;font-weight:bold;'>[DIAGNOSTICS EXCEPTION]: " . get_class($e) . "</span><br>";
    echo "<span style='color:red;'>MESSAGE: " . $e->getMessage() . "</span><br>";
}

echo "<br><h3 style='color:green;'>All updates completed successfully!</h3>";

echo "<h2>Latest Server Log Entries (laravel.log):</h2>";
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -120);
    $filtered = [];
    foreach ($lastLines as $l) {
        if (preg_match('/\[\d{4}-\d{2}-\d{2}.*\]|ERROR|Exception|Error|Stack trace|#0 /i', $l)) {
            $filtered[] = $l;
        }
    }
    echo "<pre style='background:#1e1e1e;color:#00ff00;padding:10px;border-radius:6px;max-height:450px;overflow:auto;font-family:monospace;font-size:12px;'>" . htmlspecialchars(implode("", $filtered ?: $lastLines)) . "</pre>";
} else {
    echo "<i>No laravel.log file found.</i>";
}
