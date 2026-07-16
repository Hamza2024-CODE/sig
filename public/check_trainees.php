<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) throw new Exception(".env not found");
    $env = [];
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $val) = explode('=', $line, 2);
        $env[trim($name)] = trim($val, '"\' ');
    }
    $dsn = "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'sgfep_windev') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Query 1: Where are the sections of 1301 (Taj) and 1300 (Sigma Pro) and 1096 (Khotwa) and 1095?
    $sections_count = $pdo->query("
        SELECT IDEts_Form, COUNT(*) as count 
        FROM section 
        WHERE IDEts_Form IN (1300, 1301, 1095, 1096, 376) 
        GROUP BY IDEts_Form
    ")->fetchAll();

    // Query 2: Where are the offers of 1301 and 1300 and 1096 and 1095?
    $offers_count = $pdo->query("
        SELECT IDEts_Form, COUNT(*) as count 
        FROM offre 
        WHERE IDEts_Form IN (1300, 1301, 1095, 1096, 376) 
        GROUP BY IDEts_Form
    ")->fetchAll();

    // Query 3: Search for sections having any link to Taj (1301 or 1300) or Khotwa (1096 or 1095)
    $sections_taj = $pdo->query("
        SELECT IDSection, CodeSection, IDEts_Form, IDSession 
        FROM section 
        WHERE IDEts_Form = 1301 OR IDEts_Form = 1300 
        LIMIT 10
    ")->fetchAll();

    $sections_khotwa = $pdo->query("
        SELECT IDSection, CodeSection, IDEts_Form, IDSession 
        FROM section 
        WHERE IDEts_Form = 1096 OR IDEts_Form = 1095 
        LIMIT 10
    ")->fetchAll();

    echo json_encode([
        'status' => 'success',
        'sections_count' => $sections_count,
        'offers_count' => $offers_count,
        'sections_taj' => $sections_taj,
        'sections_khotwa' => $sections_khotwa
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
