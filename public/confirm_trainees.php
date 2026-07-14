<?php
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px;'>";
echo "<h1>Staging Server Trainee Confirmation Tool</h1>";

$sectionId = 205629;
echo "<h3>Processing Section ID: $sectionId</h3>";

$updated = DB::table('apprenant')
    ->where('IDSection', $sectionId)
    ->where('Valide', 0)
    ->update([
        'Valide' => 1,
        'statut' => 'actif'
    ]);

echo "<p style='color: green; font-weight: bold;'>✓ Successfully confirmed/validated $updated trainees in the database!</p>";

$all = DB::table('apprenant')->where('IDSection', $sectionId)->get();
echo "<ul>";
echo "<li>Total Trainees: " . $all->count() . "</li>";
echo "<li>Confirmed (Valide = 1): " . $all->where('Valide', 1)->count() . "</li>";
echo "<li>Active (statut = 'actif'): " . $all->where('statut', 'actif')->count() . "</li>";
echo "</ul>";
echo "</div>";
