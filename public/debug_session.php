<?php
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #464eb5; border-bottom: 2px solid #464eb5; padding-bottom: 10px;'>Diagnostic Tool: Concurrent Session Checker</h1>";

$user = session('user');
if (!$user) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $user = $_SESSION['user'] ?? null;
    $source = "Native PHP \$_SESSION (Legacy Bridge)";
} else {
    $source = "Laravel Session (Primary)";
}

if (!$user) {
    echo "<p style='color: red; font-size: 1.2rem; font-weight: bold;'>❌ No user is currently logged in!</p>";
    echo "<p>Please log in to the dashboard first in this browser, and then visit this page again.</p>";
    echo "</div>";
    exit;
}

$userKey = strtolower($user['role_code'] ?? 'user') . '_' . ($user['id'] ?? '0') . '_' . strtolower($user['username'] ?? '');
$currentSessionId = session()->getId();

echo "<h3>1. Session Data</h3>";
echo "<ul>";
echo "<li><strong>Auth Source:</strong> $source</li>";
echo "<li><strong>User Key:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 4px;'>$userKey</code></li>";
echo "<li><strong>Username:</strong> " . ($user['username'] ?? '—') . "</li>";
echo "<li><strong>Role Code:</strong> " . ($user['role_code'] ?? '—') . "</li>";
echo "<li><strong>User ID:</strong> " . ($user['id'] ?? '—') . "</li>";
echo "<li><strong>Current Browser Session ID:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 4px;'>$currentSessionId</code></li>";
echo "<li><strong>Current PHP Native Session ID:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 4px;'>" . session_id() . "</code></li>";
echo "</ul>";

$activeSession = DB::table('active_sessions')->where('user_key', $userKey)->first();

echo "<h3>2. Database Status (active_sessions table)</h3>";
if ($activeSession) {
    echo "<ul>";
    echo "<li><strong>Stored Session ID:</strong> <code style='background: #eee; padding: 2px 6px; border-radius: 4px;'>{$activeSession->session_id}</code></li>";
    echo "<li><strong>Last Updated At:</strong> {$activeSession->updated_at}</li>";
    echo "</ul>";
    
    if ($activeSession->session_id === $currentSessionId) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin: 15px 0; font-weight: bold;'>";
        echo "✓ Match! This browser holds the ACTIVE session. It will NOT be logged out.";
        echo "</div>";
    } else {
        echo "<div style='background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; margin: 15px 0; font-weight: bold;'>";
        echo "⚠️ Mismatch! This browser session ID is different from the active one stored in the database.";
        echo "<br>Active in DB: <code>{$activeSession->session_id}</code>";
        echo "<br>This Browser: <code>$currentSessionId</code>";
        echo "<br><br><span style='color: red;'>👉 THIS DEVICE/BROWSER SHOULD BE LOGGED OUT ON THE NEXT ACTION!</span>";
        echo "</div>";
        echo "<p>If you click around the dashboard on this browser now and do NOT get redirected to login, please check if OPCache is cached or Nginx routing is incorrect.</p>";
    }
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ No active session row found in database for user key: $userKey</p>";
}

echo "<h3>3. Active Sessions in Database (Last 10 rows)</h3>";
$all = DB::table('active_sessions')->orderBy('updated_at', 'desc')->limit(10)->get();
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; text-align: left;'>";
echo "<tr style='background-color: #f2f2f2;'><th>User Key</th><th>Session ID</th><th>Updated At</th></tr>";
foreach ($all as $row) {
    $style = ($row->user_key === $userKey) ? "style='background-color: #e6ffe6; font-weight: bold;'" : "";
    echo "<tr $style><td>{$row->user_key}</td><td><code>{$row->session_id}</code></td><td>{$row->updated_at}</td></tr>";
}
echo "</table>";
echo "</div>";
