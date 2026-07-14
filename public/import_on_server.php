<?php
/**
 * سكريبت استيراد الجداول وتحديث البنية على السيرفر تلقائياً
 */
set_time_limit(0);
ini_set('memory_limit', '1024M');

// 1. محاولة قراءة ملف .env الخاص بـ Laravel
$dbHost = '127.0.0.1';
$dbName = '';
$dbUser = '';
$dbPass = '';

$envPaths = [
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env',
    dirname(dirname(__DIR__)) . '/.env'
];

foreach ($envPaths as $path) {
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
            if ($key) {
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if ($key === 'DB_HOST') $dbHost = $value;
                if ($key === 'DB_DATABASE') $dbName = $value;
                if ($key === 'DB_USERNAME') $dbUser = $value;
                if ($key === 'DB_PASSWORD') $dbPass = $value;
            }
        }
        break;
    }
}

// تحديد مسار مجلد ملفات SQL تلقائياً
$sqlDir = __DIR__;
$possibleDirs = [
    __DIR__,
    dirname(__DIR__),
    dirname(dirname(__DIR__)),
    dirname(dirname(__DIR__)) . '/hamzaftp',
    '/www/wwwroot/hamzaftp',
    '/www/wwwroot/tassyir-mfep.takwin.dz'
];
foreach ($possibleDirs as $dir) {
    if (file_exists($dir . '/missing_apprenant_absence_all.sql') || file_exists($dir . '/missing_apprenant_all.sql')) {
        $sqlDir = $dir;
        break;
    }
}

// معالجة طلب الاستيراد عبر AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    $reqDbName = $_POST['db_name'] ?? $dbName;
    $reqDbUser = $_POST['db_user'] ?? $dbUser;
    $reqDbPass = $_POST['db_pass'] ?? $dbPass;
    $reqDbHost = $_POST['db_host'] ?? $dbHost;

    if (empty($reqDbName) || empty($reqDbUser)) {
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال معلومات الاتصال بقاعدة البيانات.']);
        exit;
    }

    // Parse host and port if colon is present
    $hostParts = explode(':', $reqDbHost);
    $host = $hostParts[0];
    $port = $hostParts[1] ?? '';

    try {
        $dsn = "mysql:host=$host;" . (!empty($port) ? "port=$port;" : "") . "dbname=$reqDbName;charset=utf8";
        $pdo = new PDO($dsn, $reqDbUser, $reqDbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage()]);
        exit;
    }

    if ($action === 'alter_schema') {
        try {
            // إجراء تعديلات البنية للسماح بقيم NULL
            $queries = [
                "ALTER TABLE candidat MODIFY create_time timestamp NULL DEFAULT NULL, MODIFY update_time date NULL DEFAULT NULL, MODIFY data_sync_time date NULL DEFAULT NULL",
                "ALTER TABLE apprenant MODIFY create_time date NULL DEFAULT NULL, MODIFY update_time date NULL DEFAULT NULL, MODIFY data_sync_time date NULL DEFAULT NULL",
                "ALTER TABLE apprenant_fin MODIFY create_time date NULL DEFAULT NULL, MODIFY update_time date NULL DEFAULT NULL, MODIFY data_sync_time date NULL DEFAULT NULL",
                "ALTER TABLE apprenant_section_semstre MODIFY create_time timestamp NULL DEFAULT NULL, MODIFY update_time date NULL DEFAULT NULL, MODIFY data_sync_time date NULL DEFAULT NULL"
            ];
            foreach ($queries as $q) {
                try {
                    $pdo->exec($q);
                } catch (Exception $ex) {
                    // نتخطى الأخطاء إذا كانت الأعمدة معدلة بالفعل
                }
            }
            echo json_encode(['success' => true, 'message' => 'تم تعديل بنية الجداول بنجاح للسماح بالقيم الفارغة (NULL).']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'خطأ أثناء تعديل البنية: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'import_file') {
        $file = $_POST['file'] ?? '';
        $targetFilePath = $sqlDir . '/' . $file;
        if (empty($file) || !file_exists($targetFilePath)) {
            echo json_encode(['success' => false, 'message' => "الملف $file غير موجود على السيرفر في المسار $sqlDir"]);
            exit;
        }

        $filePath = realpath($targetFilePath);
        $startTime = microtime(true);
        $cliSuccess = false;
        $duration = 0;
        $output = [];

        // Check if exec() is enabled and try CLI (fastest)
        if (function_exists('exec')) {
            $mysqlCmd = 'mysql';
            $paths = ['/usr/bin/mysql', '/usr/local/bin/mysql', '/www/server/mysql/bin/mysql'];
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $mysqlCmd = $p;
                    break;
                }
            }

            $passOpt = !empty($reqDbPass) ? "-p" . escapeshellarg($reqDbPass) : "";
            $portOpt = !empty($port) ? "-P " . escapeshellarg($port) : "";
            $command = escapeshellcmd($mysqlCmd) . " -h " . escapeshellarg($host) . " $portOpt -u " . escapeshellarg($reqDbUser) . " $passOpt " . escapeshellarg($reqDbName) . " < " . escapeshellarg($filePath) . " 2>&1";

            $returnVar = 0;
            exec($command, $output, $returnVar);
            if ($returnVar === 0) {
                $cliSuccess = true;
                $duration = round(microtime(true) - $startTime, 2);
                echo json_encode(['success' => true, 'duration' => $duration, 'method' => 'CLI']);
                exit;
            }
        }

        // Fallback: Read line-by-line and execute in a transaction (Memory-safe and Fast)
        try {
            $fp = fopen($filePath, 'r');
            if (!$fp) {
                throw new Exception("فشل في فتح ملف SQL للقراءة.");
            }

            $pdo->beginTransaction();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $sqlBuffer = '';
            while (($line = fgets($fp)) !== false) {
                $lineTrim = trim($line);
                if (empty($lineTrim) || str_starts_with($lineTrim, '--') || str_starts_with($lineTrim, '/*') || str_starts_with($lineTrim, '#')) {
                    continue;
                }
                
                $sqlBuffer .= $line;
                if (str_ends_with($lineTrim, ';')) {
                    $pdo->exec($sqlBuffer);
                    $sqlBuffer = '';
                }
            }
            
            if (!empty(trim($sqlBuffer))) {
                $pdo->exec($sqlBuffer);
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->commit();
            fclose($fp);

            $duration = round(microtime(true) - $startTime, 2);
            echo json_encode(['success' => true, 'duration' => $duration, 'method' => 'PDO_LineByLine']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode([
                'success' => false, 
                'message' => 'فشل الاستيراد. خطأ: ' . $e->getMessage() . (isset($returnVar) ? "\nتفاصيل خطأ النظام (CLI): " . implode("\n", $output) : "")
            ]);
        }
        exit;
    }
}

// قائمة الملفات المطلوب استيرادها بالترتيب
$filesToImport = [
    'missing_hrt_specialty.sql',
    'missing_etablissement_all.sql',
    'missing_specialite_all.sql',
    'missing_offre_all.sql',
    'missing_section_all.sql',
    'missing_candidat_all.sql',
    'missing_apprenant_all.sql',
    'missing_apprenant_absence_all.sql',
    'missing_apprenant_fin_all.sql',
    'missing_apprenant_section_semstre_all.sql',
    'missing_apprenant_section_semstre_module_all.sql'
];

$availableFiles = [];
foreach ($filesToImport as $file) {
    $targetFilePath = $sqlDir . '/' . $file;
    if (file_exists($targetFilePath)) {
        $availableFiles[] = [
            'name' => $file,
            'size' => round(filesize($targetFilePath) / (1024 * 1024), 2)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تزامن واستيراد البيانات | مديرية التكوين المهني</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Cairo:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.7);
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --error-color: #ef4444;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Cairo', 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 45%),
                              radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 45%);
        }

        .container {
            width: 100%;
            max-width: 900px;
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 8px;
            background: linear-gradient(to left, #3b82f6, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-title {
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .grid-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-group label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 700;
        }

        .input-group input {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .btn {
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success-color);
        }

        .file-list {
            margin-top: 16px;
            border-collapse: collapse;
            width: 100%;
        }

        .file-list th, .file-list td {
            text-align: right;
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .file-list th {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
        }

        .file-list td {
            font-size: 14px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
        }

        .status-badge.waiting {
            color: #eab308;
            background: rgba(234, 179, 8, 0.1);
        }

        .status-badge.success {
            color: var(--success-color);
            background: rgba(16, 185, 129, 0.1);
        }

        .status-badge.error {
            color: var(--error-color);
            background: rgba(239, 68, 68, 0.1);
        }

        .status-badge.running {
            color: var(--accent-color);
            background: rgba(59, 130, 246, 0.1);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .console {
            background: #090d16;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            font-family: monospace;
            font-size: 13px;
            color: #a7f3d0;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 نظام استيراد وتزامن جداول الطلاب للوزارة</h1>
        <div class="subtitle">تحديث بنية جداول MySQL المحلي واستيراد ملفات SQL المصدرة بنجاح</div>

        <!-- كرت معلومات الاتصال بقاعدة البيانات -->
        <div class="card">
            <div class="card-title">🔌 إعدادات الاتصال بقاعدة البيانات</div>
            <div class="grid-inputs">
                <div class="input-group">
                    <label>خادم قاعدة البيانات</label>
                    <input type="text" id="db_host" value="<?php echo htmlspecialchars($dbHost); ?>">
                </div>
                <div class="input-group">
                    <label>اسم قاعدة البيانات</label>
                    <input type="text" id="db_name" value="<?php echo htmlspecialchars($dbName); ?>">
                </div>
                <div class="input-group">
                    <label>اسم المستخدم</label>
                    <input type="text" id="db_user" value="<?php echo htmlspecialchars($dbUser); ?>">
                </div>
                <div class="input-group">
                    <label>كلمة المرور</label>
                    <input type="password" id="db_pass" value="<?php echo htmlspecialchars($dbPass); ?>">
                </div>
            </div>
        </div>

        <!-- كرت الإجراءات والملفات المكتشفة -->
        <div class="card">
            <div class="card-title">📂 الملفات الجاهزة للاستيراد</div>
            
            <?php if (empty($availableFiles)): ?>
                <div style="color: var(--error-color); font-weight: 700;">
                    ✗ لم يتم العثور على أي ملفات SQL جاهزة للاستيراد. يرجى التأكد من رفع ملفات SQL المصدرة إلى مسار المشروع.
                </div>
            <?php else: ?>
                <table class="file-list">
                    <thead>
                        <tr>
                            <th>اسم الملف</th>
                            <th>الحجم (ميغابايت)</th>
                            <th>حالة الاستيراد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availableFiles as $idx => $file): ?>
                            <tr class="file-row" data-name="<?php echo htmlspecialchars($file['name']); ?>">
                                <td><?php echo htmlspecialchars($file['name']); ?></td>
                                <td><?php echo htmlspecialchars($file['size']); ?> MB</td>
                                <td>
                                    <span class="status-badge waiting" id="status-<?php echo $idx; ?>">بانتظار البدء</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 30px; display: flex; gap: 16px;">
                    <button class="btn" id="btn-alter">1. تحديث بنية الجداول</button>
                    <button class="btn btn-success" id="btn-start" disabled>2. ابدأ الاستيراد الشامل</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- شاشة الإخراج البرمجي -->
        <div class="console" id="console">بانتظار بدء العمليات...</div>
    </div>

    <script>
        const availableFiles = <?php echo json_encode($availableFiles); ?>;
        const consoleEl = document.getElementById('console');
        
        function log(message, type = 'info') {
            const time = new Date().toLocaleTimeString();
            let color = '#a7f3d0';
            if (type === 'error') color = '#ef4444';
            if (type === 'success') color = '#10b981';
            if (type === 'warn') color = '#fbbf24';
            
            consoleEl.innerHTML += `<span style="color: ${color}">[${time}] ${message}</span>\n`;
            consoleEl.scrollTop = consoleEl.scrollHeight;
        }

        function getDbCredentials() {
            return {
                db_host: document.getElementById('db_host').value,
                db_name: document.getElementById('db_name').value,
                db_user: document.getElementById('db_user').value,
                db_pass: document.getElementById('db_pass').value
            };
        }

        // 1. زر تعديل البنية
        document.getElementById('btn-alter')?.addEventListener('click', function() {
            log('جاري تهيئة قاعدة البيانات وتحديث قيود الأعمدة...');
            
            const fd = new FormData();
            const creds = getDbCredentials();
            for (let k in creds) fd.append(k, creds[k]);

            fetch('?action=alter_schema', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    log(data.message, 'success');
                    document.getElementById('btn-start').disabled = false;
                } else {
                    log(data.message, 'error');
                }
            })
            .catch(err => {
                log('حدث خطأ أثناء إرسال الطلب: ' + err, 'error');
            });
        });

        // 2. زر بدء الاستيراد
        document.getElementById('btn-start')?.addEventListener('click', async function() {
            document.getElementById('btn-start').disabled = true;
            document.getElementById('btn-alter').disabled = true;
            
            log('🚀 بدء عملية الاستيراد التتابعية لملفات SQL...', 'warn');

            for (let i = 0; i < availableFiles.length; i++) {
                const file = availableFiles[i];
                const badge = document.getElementById(`status-${i}`);
                
                badge.className = 'status-badge running';
                badge.innerText = 'جاري الاستيراد...';
                log(`بدء استيراد الملف: ${file.name} (${file.size} MB)...`);

                const fd = new FormData();
                const creds = getDbCredentials();
                for (let k in creds) fd.append(k, creds[k]);
                fd.append('file', file.name);

                try {
                    const res = await fetch('?action=import_file', {
                        method: 'POST',
                        body: fd
                    });
                    const resText = await res.text();
                    let data;
                    try {
                        data = JSON.parse(resText);
                    } catch (jsonErr) {
                        throw new Error("استجابة غير صالحة من السيرفر: " + resText.substring(0, 300));
                    }

                    if (data.success) {
                        badge.className = 'status-badge success';
                        badge.innerText = 'تم الاستيراد';
                        log(`✓ تم استيراد ${file.name} بنجاح خلال ${data.duration} ثانية.`, 'success');
                    } else {
                        badge.className = 'status-badge error';
                        badge.innerText = 'فشل';
                        log(`✗ فشل استيراد ${file.name}: ${data.message}`, 'error');
                    }
                } catch (e) {
                    badge.className = 'status-badge error';
                    badge.innerText = 'خطأ اتصال';
                    log(`✗ خطأ: ${e.message || e}`, 'error');
                }
            }

            log('🎉 اكتملت عملية الاستيراد بالكامل!', 'success');
            document.getElementById('btn-start').disabled = false;
            document.getElementById('btn-alter').disabled = false;
        });
    </script>
</body>
</html>
