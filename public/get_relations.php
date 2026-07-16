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

    $ids_to_check = [1300, 1301, 1095, 1096, 376];
    $ids_str = implode(',', $ids_to_check);

    // 1. Sections count per IDEts_Form
    $sections = $pdo->query("SELECT IDEts_Form, COUNT(*) as cnt FROM section WHERE IDEts_Form IN ($ids_str) GROUP BY IDEts_Form")->fetchAll();

    // 2. Offers count per IDEts_Form
    $offres = $pdo->query("SELECT IDEts_Form, COUNT(*) as cnt FROM offre WHERE IDEts_Form IN ($ids_str) GROUP BY IDEts_Form")->fetchAll();

    // 3. Apprenants count per section.IDEts_Form
    $apprenants = $pdo->query("
        SELECT s.IDEts_Form, COUNT(a.IDapprenant) as cnt 
        FROM apprenant a 
        JOIN section s ON a.IDSection = s.IDSection 
        WHERE s.IDEts_Form IN ($ids_str) 
        GROUP BY s.IDEts_Form
    ")->fetchAll();

    // 4. Sample sections for Taj (1300 and 1301)
    $sample_sections_taj = $pdo->query("
        SELECT IDSection, CodeSection, IDEts_Form, IDOffre 
        FROM section WHERE IDEts_Form IN (1300, 1301) LIMIT 5
    ")->fetchAll();

    // 5. Sample sections for Khotwa (1095, 1096, 376)
    $sample_sections_khotwa = $pdo->query("
        SELECT IDSection, CodeSection, IDEts_Form, IDOffre 
        FROM section WHERE IDEts_Form IN (1095, 1096, 376) LIMIT 5
    ")->fetchAll();

    // 6. Sample apprenants active for Taj
    $sample_app_taj = $pdo->query("
        SELECT a.IDapprenant, a.statut, s.IDEts_Form, s.IDSection 
        FROM apprenant a 
        JOIN section s ON a.IDSection = s.IDSection 
        WHERE s.IDEts_Form IN (1300, 1301) AND a.statut = 'actif'
        LIMIT 5
    ")->fetchAll();

    // 7. What column is used in utilisateur for private establishments?
    $etab_users = $pdo->query("
        SELECT nomUser, IDetablissement, IDEts_Form
        FROM etablissement 
        WHERE IDetablissement IN (1300, 1301, 1095, 1096)
    ")->fetchAll();

    echo json_encode([
        'status' => 'success',
        'sections_per_etab' => $sections,
        'offres_per_etab' => $offres,
        'apprenants_per_etab' => $apprenants,
        'sample_sections_taj' => $sample_sections_taj,
        'sample_sections_khotwa' => $sample_sections_khotwa,
        'sample_apprenants_taj' => $sample_app_taj,
        'etab_users' => $etab_users
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
