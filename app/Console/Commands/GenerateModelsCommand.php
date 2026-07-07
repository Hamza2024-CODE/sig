<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class GenerateModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sgfep:generate:models 
                            {--table= : Generate a model for a specific table only}
                            {--force : Overwrite existing model files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and structure Eloquent models automatically from the database tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $targetTable = $this->option('table');
        $force = $this->option('force');

        $dbName = DB::connection()->getDatabaseName();
        $this->info("Scanning database: {$dbName}");

        // System tables to exclude from model generation
        $excludeTables = [
            'migrations',
            'failed_jobs',
            'jobs',
            'personal_access_tokens',
            'sessions'
        ];

        // Fetch tables
        $tablesQuery = DB::select("SHOW TABLES");
        $keyName = "Tables_in_" . $dbName;
        $tables = [];

        foreach ($tablesQuery as $tObj) {
            $tableName = $tObj->$keyName;
            if (in_array($tableName, $excludeTables)) {
                continue;
            }
            if ($targetTable && $tableName !== $targetTable) {
                continue;
            }
            $tables[] = $tableName;
        }

        if (empty($tables)) {
            $this->warn("No matching tables found to generate models.");
            return Command::SUCCESS;
        }

        $this->info("Found " . count($tables) . " tables to process.");
        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $overwritten = 0;

        $modelsDir = app_path('Models');
        if (!File::isDirectory($modelsDir)) {
            File::makeDirectory($modelsDir, 0755, true);
        }

        foreach ($tables as $table) {
            // Determine Model Name
            $modelName = Str::studly($table);
            $filePath = $modelsDir . '/' . $modelName . '.php';

            $exists = File::exists($filePath);

            if ($exists && !$force) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Inspect columns
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $primaryKey = 'id';
            $fillable = [];
            $hasTimestamps = false;
            $hasCreatedAt = false;
            $hasUpdatedAt = false;
            $docProperties = [];

            foreach ($columns as $col) {
                $field = $col->Field;
                $type = $this->mapColumnType($col->Type);
                $nullable = ($col->Null === 'YES') ? 'null|' : '';
                $docProperties[] = " * @property {$nullable}{$type} \${$field}";

                if ($col->Key === 'PRI') {
                    $primaryKey = $field;
                }

                if ($field === 'created_at') {
                    $hasCreatedAt = true;
                }
                if ($field === 'updated_at') {
                    $hasUpdatedAt = true;
                }

                // Fillable candidates (exclude auto-increment primary key and timestamps)
                if ($col->Key !== 'PRI' && !in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
                    $fillable[] = "'{$field}'";
                }
            }

            if ($hasCreatedAt && $hasUpdatedAt) {
                $hasTimestamps = true;
            }

            // Generate content
            $fillableStr = implode(",\n        ", $fillable);
            $timestampsStr = $hasTimestamps ? 'true' : 'false';

            $docPropertiesStr = implode("\n", $docProperties);

            $template = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class {$modelName}
 * 
 * Database Table: {$table}
 * 
{$docPropertiesStr}
 * 
 * @package App\Models
 */
class {$modelName} extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = '{$table}';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected \$primaryKey = '{$primaryKey}';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public \$timestamps = {$timestampsStr};

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected \$fillable = [
        {$fillableStr}
    ];
}
PHP;

            File::put($filePath, $template);

            if ($exists) {
                $overwritten++;
            } else {
                $generated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Model Generation Summary:");
        $this->line(" - Generated: {$generated}");
        $this->line(" - Overwritten: {$overwritten}");
        $this->line(" - Skipped (exists): {$skipped}");

        return Command::SUCCESS;
    }

    /**
     * Map database column types to PHP/PHPDoc types
     *
     * @param string $type
     * @return string
     */
    protected function mapColumnType($type)
    {
        $type = strtolower($type);

        if (Str::contains($type, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint'])) {
            return 'int';
        }
        if (Str::contains($type, ['float', 'double', 'decimal'])) {
            return 'float';
        }
        if (Str::contains($type, ['bool', 'boolean'])) {
            return 'bool';
        }
        if (Str::contains($type, ['date', 'time', 'datetime', 'timestamp'])) {
            return 'string'; // or \Carbon\Carbon if using cast
        }

        return 'string';
    }
}
