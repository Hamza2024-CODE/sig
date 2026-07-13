<?php
$content = file_get_contents(__DIR__ . '/../routes/web.php');
if (strpos($content, 'liste-2021-present') !== false) {
    echo "<h1>routes/web.php contains liste-2021-present</h1>";
} else {
    echo "<h1>routes/web.php DOES NOT contain liste-2021-present</h1>";
}

// Check route cache
$cacheFile = __DIR__ . '/../bootstrap/cache/routes-v7.php';
if (file_exists($cacheFile)) {
    echo "<h1>Route cache file exists!</h1>";
    $cacheContent = file_get_contents($cacheFile);
    if (strpos($cacheContent, 'liste-2021-present') !== false) {
        echo "<h1>Route cache contains liste-2021-present</h1>";
    } else {
        echo "<h1>Route cache DOES NOT contain liste-2021-present</h1>";
    }
} else {
    echo "<h1>Route cache file does not exist (not cached)</h1>";
}
