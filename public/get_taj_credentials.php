<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    $envFile = __DIR__ . '/../.env';
    $env = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\n\r\"'");
    }
    $db = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
        $env['DB_USERNAME'], $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== TAJ AL-AZRAQ ETABLISSEMENT ===\n";
    $stmt = $db->query("SELECT IDetablissement, Nom, NomFr, nomUser, MotDePass, IDNature_etsF FROM etablissement WHERE Nom LIKE '%تاج%' OR Nom LIKE '%Taj%'");
    $etabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($etabs as $e) {
        echo "ID: {$e['IDetablissement']} | Nom: {$e['Nom']} | NomFr: {$e['NomFr']} | nomUser: {$e['nomUser']} | MotDePass: " . ($e['MotDePass'] ? (strlen($e['MotDePass']) > 20 ? '[BCRYPT]' : $e['MotDePass']) : '(empty)') . " | IDNature_etsF: {$e['IDNature_etsF']}\n";
        
        // Find matching nature and direction
        $stmtNature = $db->prepare("
            SELECT Nature_etsF.IDNature, NatureDirection.Nom AS NatureNom
            FROM Nature_etsF
            INNER JOIN NatureDirection ON NatureDirection.IDNature = Nature_etsF.IDNature
            WHERE Nature_etsF.IDNature_etsF = ?
        ");
        $stmtNature->execute([$e['IDNature_etsF']]);
        $nature = $stmtNature->fetch(PDO::FETCH_ASSOC);
        if ($nature) {
            echo "   -> IDNature: {$nature['IDNature']} | NatureName: {$nature['NatureNom']}\n";
            
            // Fetch users for this IDNature
            $stmtUsers = $db->prepare("SELECT IDUtilisateur, NomUser, Nom, MotPass FROM utilisateur WHERE IDNature = ?");
            $stmtUsers->execute([$nature['IDNature']]);
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $u) {
                echo "      -> User: {$u['NomUser']} ({$u['Nom']}) | MotPass (Secret Code): " . ($u['MotPass'] ? (strlen($u['MotPass']) > 20 ? '[BCRYPT]' : $u['MotPass']) : '(empty)') . "\n";
            }
        }
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
