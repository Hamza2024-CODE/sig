<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

try {
    echo "Attempting to render view auth.login...\n";
    $html = view('auth.login', [
        'is_captcha_active' => false,
        'captcha_question'  => null,
    ])->render();
    echo "SUCCESS: View rendered successfully! HTML length: " . strlen($html) . "\n";
} catch (\Throwable $e) {
    echo "ERROR RENDERING VIEW: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
