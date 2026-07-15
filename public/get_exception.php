<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Check if we can compile and render the exam view
    $html = view('dashboard.departments.exam')->render();
    echo "SUCCESS: Exam view compiled and rendered fine!\n";
} catch (\Throwable $e) {
    echo "EXCEPTION TYPE: " . get_class($e) . "\n";
    echo "MESSAGE: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . "\n";
    echo "LINE: " . $e->getLine() . "\n";
}
