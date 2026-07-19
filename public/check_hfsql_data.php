<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

// Read HFSQL connection config from Laravel
$dsn = env('HFSQL_DSN', "Driver={HFSQL};Server Name=197.112.101.166;Server Port=4900;Database=sig;IntegrityCheck=0");
$user = env('HFSQL_USERNAME', "sig");
$pass = env('HFSQL_PASSWORD', "Sig@2023#2025");
$connString = $dsn . ';UID=' . $user . ';PWD=' . $pass;

echo "🔌 Connecting to HFSQL DSN: $dsn ...\n";

try {
    $conn = @odbc_connect($connString, '', '');
    if (!$conn) {
        throw new Exception("Connection to HFSQL failed: " . odbc_errormsg());
    }
    echo "✓ Connected to HFSQL successfully!\n\n";

    // 1. Check active trainees in HFSQL for both 1301 and 1302
    echo "=== COUNTING ACTIVE TRAINEES IN HFSQL ===\n";
    foreach ([1301, 1302] as $etabId) {
        $resName = @odbc_exec($conn, "SELECT Nom FROM etablissement WHERE IDetablissement = $etabId");
        $etabName = 'Unknown';
        if ($resName) {
            $rowName = odbc_fetch_array($resName);
            if ($rowName && isset($rowName['Nom'])) {
                $etabName = mb_convert_encoding($rowName['Nom'], "UTF-8", "Windows-1256");
            }
        }

        $queryT = "
            SELECT COUNT(a.IDapprenant) as total 
            FROM apprenant a
            INNER JOIN section s ON a.IDSection = s.IDSection
            INNER JOIN offre o ON s.IDOffre = o.IDOffre
            WHERE o.IDEts_Form = $etabId
              AND a.statut = 'actif'
        ";
        $resT = @odbc_exec($conn, $queryT);
        $total = 0;
        if ($resT) {
            $rowT = odbc_fetch_array($resT);
            $total = (int)($rowT['total'] ?? 0);
        }

        echo "Etab ID in HFSQL: $etabId ($etabName) | Total active trainees in HFSQL: $total\n";
    }

    // 2. Fetch all private establishments in HFSQL with their parents and offers count
    echo "\n=== ALL PRIVATE ESTABLISHMENTS IN HFSQL ===\n";
    $queryPrivate = "
        SELECT IDetablissement, Nom, IDDFEP, DeIDetablissementRatache, DeIDetablissementRatacheInsfp
        FROM etablissement
        WHERE PublPrive = 1
        ORDER BY IDDFEP, Nom
    ";
    $resPrivate = @odbc_exec($conn, $queryPrivate);
    if ($resPrivate) {
        echo sprintf("%-6s | %-50s | %-4s | %-8s | %-8s | %-12s\n", "ID", "Nom", "DFEP", "Ratache", "RatacheI", "Offers Count");
        echo str_repeat("-", 100) . "\n";
        while ($row = odbc_fetch_array($resPrivate)) {
            $etabId = (int)$row['IDetablissement'];
            $resOffers = @odbc_exec($conn, "SELECT COUNT(*) as c FROM offre WHERE IDEts_Form = $etabId");
            $offersCount = 0;
            if ($resOffers) {
                $rowOffers = odbc_fetch_array($resOffers);
                $offersCount = (int)($rowOffers['c'] ?? 0);
            }

            $name = isset($row['Nom']) ? mb_convert_encoding($row['Nom'], "UTF-8", "Windows-1256") : 'Unknown';

            echo sprintf(
                "%-6d | %-50s | %-4d | %-8d | %-8d | %-12d\n",
                $etabId,
                $name,
                (int)($row['IDDFEP'] ?? 0),
                (int)($row['DeIDetablissementRatache'] ?? 0),
                (int)($row['DeIDetablissementRatacheInsfp'] ?? 0),
                $offersCount
            );
        }
    } else {
        echo "Failed to query private institutions from HFSQL.\n";
    }

    odbc_close($conn);

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
