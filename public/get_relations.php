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

    // Query 1: Etablissement 1301 details
    $stmt = $pdo->query("SELECT IDetablissement, Nom, IDEts_Form, DeIDetablissementRatache, DeIDetablissementRatacheInsfp, IDDFEP FROM etablissement WHERE IDetablissement = 1301");
    $etab_1301 = $stmt->fetch();

    // Query 2: Etablissement 1096 details
    $stmt = $pdo->query("SELECT IDetablissement, Nom, IDEts_Form, DeIDetablissementRatache, DeIDetablissementRatacheInsfp, IDDFEP FROM etablissement WHERE IDetablissement = 1096");
    $etab_1096 = $stmt->fetch();

    // Query 3: Find Etablissements matching the linked IDs
    $linked_ids = array_filter([
        $etab_1301['DeIDetablissementRatache'] ?? null,
        $etab_1301['DeIDetablissementRatacheInsfp'] ?? null,
        $etab_1096['DeIDetablissementRatache'] ?? null,
        $etab_1096['DeIDetablissementRatacheInsfp'] ?? null,
    ]);

    $linked_etabs = [];
    if (!empty($linked_ids)) {
        $ids_str = implode(',', $linked_ids);
        $stmt = $pdo->query("SELECT IDetablissement, Nom, IDDFEP FROM etablissement WHERE IDetablissement IN ($ids_str)");
        $linked_etabs = $stmt->fetchAll();
    }

    echo json_encode([
        'status' => 'success',
        'etab_1301' => $etab_1301,
        'etab_1096' => $etab_1096,
        'linked_etabs' => $linked_etabs
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
