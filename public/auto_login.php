<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $admin = DB::table('utilisateur')->first();
    if ($admin) {
        $admin = (array)$admin;
        $admin['role_code'] = $admin['role_code'] ?? 'admin';
        session(['user' => $admin]);
        
        header('Location: /dashboard/digital-cards?type=trainee');
        exit;
    } else {
        echo "No users found in database.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
