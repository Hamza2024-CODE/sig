<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: application/json; charset=utf-8');
$user = session('user');
echo json_encode([
    'session_user' => $user,
    'session_all' => session()->all()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
