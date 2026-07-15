<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "--- SCHEMAS ---\n";
    $columns = \Illuminate\Support\Facades\DB::select("DESCRIBE apprenant_section_semstre");
    foreach ($columns as $col) {
        echo "apprenant_section_semstre: {$col->Field} - {$col->Type}\n";
    }
    
    echo "\n";
    $columns2 = \Illuminate\Support\Facades\DB::select("DESCRIBE apprenant_section_semstre_module");
    foreach ($columns2 as $col) {
        echo "apprenant_section_semstre_module: {$col->Field} - {$col->Type}\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
