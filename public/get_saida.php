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

    $action = $_GET['action'] ?? 'show';

    if ($action === 'show_raw') {
        // Show raw (unmasked) password values
        echo "=== RAW MotDePass for Saida (IDetablissement=375) ===\n";
        $r = $db->query("SELECT IDetablissement, nomUser, MotDePass FROM etablissement WHERE IDetablissement=375")->fetch(PDO::FETCH_ASSOC);
        echo "nomUser={$r['nomUser']} | RAW_MotDePass=" . ($r['MotDePass'] ?: '(empty)') . "\n";

        echo "\n=== RAW MotPass for all DFEP utilisateurs ===\n";
        $users = $db->query("SELECT IDUtilisateur, NomUser, MotPass FROM utilisateur WHERE IDNature=4 ORDER BY IDUtilisateur")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            echo "ID={$u['IDUtilisateur']} | NomUser={$u['NomUser']} | RAW_MotPass=" . ($u['MotPass'] ?: '(empty)') . "\n";
        }

    } elseif ($action === 'reset') {
        // Reset Saida DFEP password and DFEPS secret code to known values
        $newEtabPass   = password_hash('saida2024', PASSWORD_BCRYPT, ['cost' => 12]);
        $newSecretDFEPS = password_hash('dfeps2024', PASSWORD_BCRYPT, ['cost' => 12]);

        // Reset institution password for Saida (IDetablissement=375)
        $stmt = $db->prepare("UPDATE etablissement SET MotDePass=? WHERE IDetablissement=375");
        $stmt->execute([$newEtabPass]);
        echo "✓ Saida institution password reset to: saida2024\n";

        // Reset DFEPS utilisateur secret code
        $stmt2 = $db->prepare("UPDATE utilisateur SET MotPass=? WHERE NomUser='DFEPS'");
        $stmt2->execute([$newSecretDFEPS]);
        echo "✓ DFEPS secret code reset to: dfeps2024\n";

        echo "\n=== Login Credentials for Saida DFEP ===\n";
        echo "Type: Etablissement\n";
        echo "Username (nomUser): 2000\n";
        echo "Password: saida2024\n";
        echo "Secret Code: dfeps2024\n";
        echo "\nPlease test at: https://test.tassyir.dz/login\n";

    } else {
        echo "Usage:\n";
        echo "  ?action=show_raw  — Show raw password values\n";
        echo "  ?action=reset     — Reset Saida password to 'saida2024' and DFEPS code to 'dfeps2024'\n";
        echo "\n";
        echo "Saida IDetablissement: 375\n";
        echo "Saida nomUser: 2000\n";
        echo "All passwords are currently [HASHED]\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
