<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

echo "=== DIAGNOSING DIGITAL CARDS PAGE BLANK SCREEN ===\n\n";

try {
    // Simulated Admin User
    $adminUser = DB::table('utilisateur')->where('role_id', 1)->first()
              ?? DB::table('utilisateur')->first();
              
    if (!$adminUser) {
        echo "No users found in database.\n";
        exit;
    }
    
    $adminUser = (array)$adminUser;
    $adminUser['role_code'] = $adminUser['role_code'] ?? 'admin';
    session(['user' => $adminUser]);

    echo "Logged in user: {$adminUser['username']} | Role: {$adminUser['role_code']}\n\n";

    // Build the request
    $request = \Illuminate\Http\Request::create('/dashboard/digital-cards', 'GET', [
        'type' => 'trainee'
    ]);

    echo "Calling EspaceEmployeController@digitalCards...\n";
    
    $controller = new \App\Http\Controllers\Admin\EspaceEmployeController();
    $response = $controller->digitalCards($request);
    
    echo "✓ Call Success! Response Class: " . get_class($response) . "\n";
    if (method_exists($response, 'getContent')) {
        $content = $response->getContent();
        echo "Response Content Length: " . strlen($content) . " bytes\n";
        echo "First 500 chars of response:\n";
        echo substr($content, 0, 500) . "\n";
    } else {
        echo "Response: ";
        print_r($response);
    }
} catch (\Throwable $e) {
    echo "❌ FATAL EXCEPTION CAUGHT:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Trace: \n" . $e->getTraceAsString() . "\n";
}
