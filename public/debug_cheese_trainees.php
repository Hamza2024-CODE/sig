<?php
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px;'>";
echo "<h1>Staging Server Trainee Diagnostic Tool</h1>";

$sectionId = 205629;
echo "<h3>Checking Section ID: $sectionId</h3>";

$section = DB::table('section')->where('IDSection', $sectionId)->first();
if ($section) {
    echo "<p style='color: green; font-weight: bold;'>✓ Section found: {$section->Nom} (Groupe: {$section->Groupe})</p>";
    echo "<ul>";
    echo "<li><strong>Start Date:</strong> {$section->DateDF}</li>";
    echo "<li><strong>End Date:</strong> {$section->DateFF}</li>";
    echo "<li><strong>Etab ID:</strong> {$section->IDEts_Form}</li>";
    echo "</ul>";
} else {
    echo "<p style='color:red; font-weight: bold;'>❌ Section $sectionId NOT found in database!</p>";
}

$allTrainees = DB::table('apprenant')->where('IDSection', $sectionId)->get();
echo "<p>Total trainees linked to this section in database (without any filters): <strong style='font-size: 1.2rem; color: #007bff;'>" . $allTrainees->count() . "</strong></p>";

if ($allTrainees->isNotEmpty()) {
    echo "<h4>Status breakdown for these trainees:</h4><ul>";
    $statuses = DB::table('apprenant')->where('IDSection', $sectionId)->select('statut', DB::raw('count(*) as cnt'))->groupBy('statut')->get();
    foreach ($statuses as $st) {
        echo "<li>Statut: '<code>" . htmlspecialchars($st->statut) . "</code>' - Count: {$st->cnt}</li>";
    }
    echo "</ul>";

    echo "<h4>Testing joins for first 5 trainees:</h4>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'><th>IDapprenant</th><th>Statut</th><th>Has Candidate Row?</th><th>Candidate Name</th></tr>";
    foreach ($allTrainees->take(5) as $app) {
        $cand = DB::table('candidat')->where('IDCandidat', $app->IDCandidat)->first();
        echo "<tr>";
        echo "<td>{$app->IDapprenant}</td>";
        echo "<td><code>" . htmlspecialchars($app->statut) . "</code></td>";
        echo "<td>" . ($cand ? "<span style='color: green;'>Yes</span>" : "<span style='color: red;'>No</span>") . "</td>";
        echo "<td>" . ($cand ? htmlspecialchars("{$cand->Nom} {$cand->Prenom}") : "—") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Checking another section for same specialty (IDSection: 205630)</h3>";
$allTrainees2 = DB::table('apprenant')->where('IDSection', 205630)->get();
echo "<p>Total trainees in section 205630: <strong>" . $allTrainees2->count() . "</strong></p>";

echo "</div>";
