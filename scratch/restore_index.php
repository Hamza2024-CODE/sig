<?php
echo "Attempting to restore index.blade.php using git...\n";

// Try running git checkout
$output = shell_exec('git checkout resources/views/dashboard/index.blade.php 2>&1');
echo "Output 1: $output\n";

// Try common git paths
$paths = [
    'C:\\Program Files\\Git\\bin\\git.exe',
    'C:\\Program Files\\Git\\cmd\\git.exe',
    'C:\\Program Files (x86)\\Git\\bin\\git.exe',
    'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
    'C:\\Users\\Bouba\\AppData\\Local\\Programs\\Git\\cmd\\git.exe',
    'C:\\Users\\Bouba\\AppData\\Local\\Programs\\Git\\bin\\git.exe',
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "Found git at: $path\n";
        $escapedPath = escapeshellarg($path);
        $output = shell_exec("$escapedPath checkout resources/views/dashboard/index.blade.php 2>&1");
        echo "Output from $path: $output\n";
        break;
    }
}
