<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$user = session('user');

echo "=== CURRENT LOGGED IN USER SESSION ===\n\n";
if ($user) {
    print_r($user);
} else {
    echo "No user is logged in (session('user') is null).\n";
}
