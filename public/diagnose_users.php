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

    echo "=== UTILISATEUR TABLE ===\n";
    $stmt = $db->query("SELECT IDUtilisateur, NomUser, Nom, IDNature, IDBureau, Code, MotPass FROM utilisateur ORDER BY IDUtilisateur");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "ID: {$u['IDUtilisateur']} | NomUser: {$u['NomUser']} | Nom: {$u['Nom']} | IDNature: {$u['IDNature']} | MotPass: " . ($u['MotPass'] ? (strlen($u['MotPass']) > 20 ? '[BCRYPT]' : $u['MotPass']) : '(empty)') . "\n";
    }

    echo "\n=== DISTINCT NATURES IN ETABLISSEMENT ===\n";
    $stmt2 = $db->query("
        SELECT DISTINCT 
            Nature_etsF.IDNature, 
            NatureDirection.Nom AS NatureNom
        FROM Etablissement
        INNER JOIN Nature_etsF ON Etablissement.IDNature_etsF = Nature_etsF.IDNature_etsF
        INNER JOIN NatureDirection ON NatureDirection.IDNature = Nature_etsF.IDNature
    ");
    $natures = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($natures as $n) {
        echo "IDNature: {$n['IDNature']} | Name: {$n['NatureNom']}\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
