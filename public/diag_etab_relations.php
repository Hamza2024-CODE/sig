<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) throw new Exception(".env not found");
    $env = [];
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $val) = explode('=', $line, 2);
        $env[trim($name)] = trim($val, '"\' ');
    }
    $dsn = "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'sgfep_windev') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Query 1: Get Etablissement 1301 (El Tadj El Azrak)
    $etab1301 = $pdo->query("SELECT * FROM etablissement WHERE IDetablissement = 1301")->fetch();

    // Query 2: Get Etablissement 1300
    $etab1300 = $pdo->query("SELECT * FROM etablissement WHERE IDetablissement = 1300")->fetch();

    // Query 3: Find all public/private establishments in Setif (IDDFEP = 19)
    $setifEtabs = $pdo->query("SELECT IDetablissement, Nom, NomFr, IDEts_Form, DeIDetablissementRatache, DeIDetablissementRatacheInsfp, IDDFEP FROM etablissement WHERE IDDFEP = 19 OR IDetablissement IN (1301, 1300) LIMIT 100")->fetchAll();

    // Query 4: Check if there are other entries in the database for Etablissement El Tadj El Azrak
    $likeTaj = $pdo->query("SELECT IDetablissement, Nom, NomFr, IDEts_Form, DeIDetablissementRatache, DeIDetablissementRatacheInsfp, IDDFEP FROM etablissement WHERE Nom LIKE '%التاج%' OR Nom LIKE '%TAJ%' OR NomFr LIKE '%TAJ%' LIMIT 100")->fetchAll();

    echo json_encode([
        'status' => 'success',
        'etab1301' => $etab1301,
        'etab1300' => $etab1300,
        'setifEtabs' => $setifEtabs,
        'likeTaj' => $likeTaj
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
