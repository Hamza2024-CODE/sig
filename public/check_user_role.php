<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

session_start();

header('Content-Type: text/plain; charset=utf-8');

echo "=== SESSION USER & ROLE ===\n";
echo "Session Role: " . session('role') . "\n";
echo "Session User: \n";
print_r(session('user'));
echo "\n";
echo "Active Session ID: " . session()->getId() . "\n";
