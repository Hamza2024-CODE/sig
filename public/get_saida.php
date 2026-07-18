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

    echo "=== Wilaya 20 (سعيدة) ===\n";
    $wRow = $db->query("SELECT IDWilayaa, Code, Nom, NomFr FROM wilaya WHERE Code = '20' OR Nom LIKE '%سعيدة%' OR NomFr LIKE '%Saida%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($wRow as $w) {
        echo "IDWilayaa={$w['IDWilayaa']} | Code={$w['Code']} | Nom={$w['Nom']} | NomFr={$w['NomFr']}\n";
    }

    echo "\n=== DFEP (Nature=4) etablissement for Saida ===\n";
    $stmt = $db->prepare("
        SELECT e.IDetablissement, e.Nom, e.nomUser, e.MotDePass, e.IDDFEP, e.activee,
               w.Code as wilaya_code, w.Nom as wilaya_nom
        FROM etablissement e
        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
        INNER JOIN nature_etsf nef ON e.IDNature_etsF = nef.IDNature_etsF
        WHERE nef.IDNature = 4
          AND (w.Code = '20' OR w.Nom LIKE '%سعيدة%' OR w.NomFr LIKE '%Saida%' OR e.Nom LIKE '%سعيدة%')
        ORDER BY e.IDetablissement ASC
    ");
    $stmt->execute();
    $etabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($etabs)) {
        echo "No result. Trying IDDFEP=20...\n";
        $stmt2 = $db->prepare("
            SELECT e.IDetablissement, e.Nom, e.nomUser, e.MotDePass, e.IDDFEP, e.activee,
                   nef.IDNature
            FROM etablissement e
            INNER JOIN nature_etsf nef ON e.IDNature_etsF = nef.IDNature_etsF
            WHERE nef.IDNature = 4 AND e.IDDFEP = 20
        ");
        $stmt2->execute();
        $etabs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    foreach ($etabs as $e) {
        $passDisplay = strlen($e['MotDePass'] ?? '') > 5 
            ? (str_starts_with($e['MotDePass'], '$2y$') ? '[HASHED]' : $e['MotDePass'])
            : '(empty)';
        echo "IDetablissement={$e['IDetablissement']} | Nom={$e['Nom']} | nomUser={$e['nomUser']} | MotDePass={$passDisplay} | IDDFEP={$e['IDDFEP']} | activee={$e['activee']}\n";
    }

    echo "\n=== Utilisateur passwords for nature 4 (DFEP secret codes) ===\n";
    $uStmt = $db->query("SELECT IDUtilisateur, NomUser, Nom, MotPass FROM utilisateur WHERE IDNature = 4 ORDER BY IDUtilisateur ASC");
    $users = $uStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $passDisplay = strlen($u['MotPass'] ?? '') > 5
            ? (str_starts_with($u['MotPass'], '$2y$') ? '[HASHED]' : $u['MotPass'])
            : '(empty)';
        echo "ID={$u['IDUtilisateur']} | NomUser={$u['NomUser']} | Nom={$u['Nom']} | MotPass={$passDisplay}\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
