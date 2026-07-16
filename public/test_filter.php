<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domains\Academic\Services\CandidatService;
use App\Domains\Academic\Repositories\CandidatRepository;
use App\Domains\Security\AuthorizationService;
use App\Audit\AuditService;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $repo = new CandidatRepository();
    $auth = new AuthorizationService();
    $audit = new AuditService();

    $service = new CandidatService($repo, $auth, $audit);

    $user = [
        'role_code' => 'admin',
        'username' => 'admin',
        'iddfep' => null,
        'etablissement_id' => null,
    ];

    echo "--- Database Connection Success ---\n";

    // 1. Total count
    $totalCount = DB::table('candidat')->count();
    echo "Total Candidates in DB: {$totalCount}\n";

    // 2. Count by Wilaya 13
    $wilayaId = 13; // Tlemcen
    $filters = ['wilaya_id' => $wilayaId];
    $results = $service->listCandidats($user, 'all', $filters);
    echo "Total listCandidats (Wilaya 13): " . count($results) . "\n";
    if (count($results) > 0) {
        echo "First Candidate: {$results[0]['nom_ar']} {$results[0]['prenom_ar']} | Etab: {$results[0]['etab_nom']}\n";
    }

    // 3. Let's inspect some etablissements
    $etabs = DB::table('etablissement')->limit(5)->get();
    echo "\nSample Etablissements:\n";
    foreach ($etabs as $e) {
        echo "ID: {$e->IDetablissement} | Nom: {$e->Nom} | IDDFEP: {$e->IDDFEP}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine();
}
