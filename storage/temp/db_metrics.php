<?php
// Bootstrap Laravel
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $dbName = config('database.connections.mysql.database');
    echo "Auditing database: $dbName\n";

    // 1. Get total tables
    $tables = DB::select("SHOW TABLES");
    $key = "Tables_in_" . $dbName;
    $totalTables = count($tables);
    echo "Total tables: $totalTables\n";

    // 2. Count tables without primary keys and indexes
    $tablesWithoutPK = [];
    $totalIndexes = 0;
    $tablesWithoutIndexes = [];
    $columnCount = 0;

    foreach ($tables as $tObj) {
        $table = $tObj->$key;
        
        // Check PK
        $columns = DB::select("SHOW COLUMNS FROM `$table`");
        $columnCount += count($columns);
        $hasPK = false;
        foreach ($columns as $col) {
            if ($col->Key === 'PRI') {
                $hasPK = true;
            }
        }
        if (!$hasPK) {
            $tablesWithoutPK[] = $table;
        }

        // Check indexes
        $indexes = DB::select("SHOW INDEX FROM `$table`");
        $totalIndexes += count($indexes);
        if (count($indexes) === 0) {
            $tablesWithoutIndexes[] = $table;
        }
    }

    echo "Tables without primary keys: " . count($tablesWithoutPK) . "\n";
    if (count($tablesWithoutPK) > 0) {
        echo "Sample tables without PK: " . implode(', ', array_slice($tablesWithoutPK, 0, 10)) . "\n";
    }

    echo "Total columns: $columnCount\n";
    echo "Total indexes: $totalIndexes\n";
    echo "Tables without indexes: " . count($tablesWithoutIndexes) . "\n";

    // 3. Count foreign keys / constraints
    $fkQuery = "
        SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
    ";
    $foreignKeys = DB::select($fkQuery, [$dbName]);
    echo "Total Foreign Keys (Constraints): " . count($foreignKeys) . "\n";
    if (count($foreignKeys) > 0) {
        echo "Sample FKs: \n";
        foreach (array_slice($foreignKeys, 0, 5) as $fk) {
            echo "  - {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
