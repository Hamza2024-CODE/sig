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

    // Try a simple query that doesn't mention DeIDetablissementRatache at all.
    $row = $pdo->query("SELECT * FROM etablissement WHERE IDetablissement = 1301")->fetch();
    
    echo json_encode([
        'status' => 'success',
        'row' => $row
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
