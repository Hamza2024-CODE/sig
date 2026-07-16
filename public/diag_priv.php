<?php
header('Content-Type: application/json; charset=utf-8');

// Use COMMIT HASH in GitHub URL to bypass CDN cache
$commit = '4b2f135'; // Commit with ApprenantController + ModulesController fixes

$files = [
    __DIR__.'/../app/Http/Controllers/Admin/ApprenantController.php'
        => "https://raw.githubusercontent.com/Hamza2024-CODE/sig/{$commit}/app/Http/Controllers/Admin/ApprenantController.php",
    __DIR__.'/../app/Http/Controllers/Admin/ModulesController.php'
        => "https://raw.githubusercontent.com/Hamza2024-CODE/sig/{$commit}/app/Http/Controllers/Admin/ModulesController.php",
];

$results = [];

foreach ($files as $dest => $url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header'  => "User-Agent: PHP\r\nCache-Control: no-cache\r\n",
        ]
    ]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content !== false && strlen($content) > 500) {
        if (@file_put_contents($dest, $content)) {
            $results[] = '✓ ' . basename($dest) . ' (' . strlen($content) . ' bytes)';
            // Invalidate OPcache for this file
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($dest, true);
            }
        } else {
            $results[] = '✗ ' . basename($dest) . ' (write failed - check permissions)';
        }
    } else {
        $results[] = '✗ ' . basename($dest) . ' (download failed)';
    }
}

// Also update pull.php using the newer commit that has new list
$pullCommit = 'e63d765';
$pullUrl = "https://raw.githubusercontent.com/Hamza2024-CODE/sig/{$pullCommit}/public/pull.php";
$ctx2 = stream_context_create(['http' => ['timeout' => 20, 'header' => "User-Agent: PHP\r\n"]]);
$pullContent = @file_get_contents($pullUrl, false, $ctx2);
if ($pullContent !== false && strlen($pullContent) > 500) {
    @file_put_contents(__DIR__.'/pull.php', $pullContent);
    if (function_exists('opcache_invalidate')) opcache_invalidate(__DIR__.'/pull.php', true);
    $results[] = '✓ pull.php updated (' . strlen($pullContent) . ' bytes)';
} else {
    $results[] = '✗ pull.php (download failed)';
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = '✓ opcache reset';
}

echo json_encode([
    'status' => 'done',
    'commit_used' => $commit,
    'results' => $results,
    'time' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
