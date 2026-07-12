<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__ . '/uploads/hfsql_sync/equipement_memo/photo';
echo "=== TARGET DIRECTORY FOR EQUIPEMENT_MEMO ===\n";
echo "Path: $dir\n";

if (is_dir($dir)) {
    $files = scandir($dir);
    $count = 0;
    $preview = [];
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $count++;
            if ($count <= 20) {
                $preview[] = $file;
            }
        }
    }
    echo "Status: EXISTS\n";
    echo "Total Files: $count\n";
    echo "First 20 Files: \n";
    foreach ($preview as $p) {
        echo " - $p\n";
    }
} else {
    echo "Status: DOES NOT EXIST\n";
}

echo "\n=== SOURCE DIRECTORY FROM hamzaftp ===\n";
$src = '/www/wwwroot/hamzaftp/equipement_memo';
if (is_dir($src)) {
    echo "Path: $src\n";
    $sub = scandir($src);
    echo "Contents of $src:\n";
    foreach ($sub as $s) {
        if ($s !== '.' && $s !== '..') {
            $full = $src . '/' . $s;
            echo " - $s (" . (is_dir($full) ? 'DIR' : 'FILE') . ")\n";
            if (is_dir($full)) {
                $subSub = scandir($full);
                echo "   Contains: " . (count($subSub) - 2) . " items\n";
                // Preview first 5
                $c = 0;
                foreach ($subSub as $ssf) {
                    if ($ssf !== '.' && $ssf !== '..') {
                        $c++;
                        if ($c <= 5) {
                            echo "     * $ssf\n";
                        }
                    }
                }
            }
        }
    }
} else {
    echo "Source not found: $src\n";
}
