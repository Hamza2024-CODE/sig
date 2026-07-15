<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\StatsService;
use Illuminate\Support\Facades\Cache;

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Running Stats Service Refresh via Web PHP...</h1>";

try {
    echo "<p>Computing stats...</p>";
    $stats = StatsService::refreshAll();
    
    echo "<h2>Refresh Completed Successfully!</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "tr><th>Stat Key</th><th>Computed Value</th></tr>";
    foreach ($stats as $key => $val) {
        echo "<tr><td><code>{$key}</code></td><td><strong>{$val}</strong></td></tr>";
    }
    echo "</table>";
} catch (\Throwable $e) {
    echo "<h2 style='color:red;'>Error refreshing stats:</h2>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}
