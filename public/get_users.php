<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    $envFile = __DIR__ . '/../.env';
    $env = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\n\r\"'");
    }
    $db = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
        $env['DB_USERNAME'], $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== UTILISATEUR list for nature 4 (DFEP) ===\n";
    $stmt = $db->prepare("SELECT IDUtilisateur, NomUser, Nom, IDNature, IDBureau, Code FROM utilisateur WHERE IDNature = 4");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "ID: {$u['IDUtilisateur']} | NomUser: {$u['NomUser']} | Nom: {$u['Nom']} | IDBureau: {$u['IDBureau']} | Code: {$u['Code']}\n";
    }
    
    echo "\n=== ALL nature 4 etablissement details ===\n";
    $stmt2 = $db->prepare("SELECT Idetablissement, Nom, nomUser, IDNature_etsF FROM etablissement WHERE nomUser = '3100'");
    $stmt2->execute();
    $etabs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($etabs as $e) {
        echo "Ets ID: {$e['Idetablissement']} | Nom: {$e['Nom']} | NomUser: {$e['nomUser']} | IDNature_etsF: {$e['IDNature_etsF']}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
