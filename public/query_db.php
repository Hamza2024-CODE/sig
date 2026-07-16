<?php
header('Content-Type: application/json; charset=utf-8');

try {
    // Parse .env file
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        throw new Exception(".env file not found");
    }
    
    $env = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value, '"\' ');
    }

    $dsn = "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'sgfep_windev') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Query 1: Etablissement 1301 details
    $stmt = $pdo->query("SELECT IDetablissement, IDDFEP, Nom, IDEts_Form FROM etablissement WHERE IDetablissement = 1301");
    $etab_1301 = $stmt->fetch();

    // Query 2: Etablissement 1096 (Khotwa) details
    $stmt = $pdo->query("SELECT IDetablissement, IDDFEP, Nom, IDEts_Form FROM etablissement WHERE IDetablissement = 1096");
    $etab_1096 = $stmt->fetch();

    // Query 3: dfep rows
    $stmt = $pdo->query("SELECT * FROM dfep");
    $dfep_rows = $stmt->fetchAll();

    // Query 4: wilaya rows
    $stmt = $pdo->query("SELECT * FROM wilaya");
    $wilaya_rows = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'etab_1301' => $etab_1301,
        'etab_1096' => $etab_1096,
        'dfep_rows' => $dfep_rows,
        'wilaya_rows' => $wilaya_rows
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
