<?php

namespace AssistPro\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;

class GenerateMigrationsCommand extends Command
{
    protected $signature = 'migrate:generate {tables? : Optional comma-separated list of tables} {--views : Generate migrations for views} {--triggers : Generate migrations for triggers} {--procedures : Generate migrations for stored procedures}';
    protected $description = 'Generate migration files from existing database tables, views, triggers, and procedures';
    protected $files;
    protected $timestamp;
    protected $foreignKeysMap = [];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
        $this->timestamp = time();
    }

    public function handle()
    {
        if ($this->option('views')) {
            $this->generateViewsMigration();
        }

        if ($this->option('triggers')) {
            $this->generateTriggersMigration();
        }

        if ($this->option('procedures')) {
            $this->generateProceduresMigration();
        }

        if ($this->option('views') || $this->option('triggers') || $this->option('procedures')) {
            return;
        }

        $tables = $this->argument('tables') 
            ? explode(',', $this->argument('tables')) 
            : $this->getAllTables();

        $this->info('Analyzing table dependencies...');
        
        // Analyze all foreign keys first
        $this->analyzeForeignKeys($tables);
        
        // Check for existing migrations and detect changes
        $existingMigrations = $this->getExistingMigrations();
        $newTables = [];
        $changedTables = [];
        
        foreach ($tables as $table) {
            if (!isset($existingMigrations[$table])) {
                $newTables[] = $table;
            } else {
                // Check if table structure has changed
                if ($this->hasTableChanged($table, $existingMigrations[$table])) {
                    $changedTables[] = $table;
                }
            }
        }
        
        if (empty($newTables) && empty($changedTables)) {
            $this->info('No changes detected. All tables are up to date.');
            return;
        }
        
        // Sort new tables by dependencies (topological sort)
        if (!empty($newTables)) {
            $sortedTables = $this->sortTablesByDependencies($newTables);
            
            $this->info('Generating migrations for ' . count($sortedTables) . ' new tables in dependency order...');

            // Generate table structure migrations (without foreign keys)
            foreach ($sortedTables as $table) {
                $this->generateMigration($table, false);
                $this->timestamp++; // Increment timestamp to ensure order
            }
            
            // Generate foreign keys migration separately
            $this->generateForeignKeysMigration();
        }
        
        // Generate ALTER migrations for changed tables
        if (!empty($changedTables)) {
            $this->info('Generating ALTER migrations for ' . count($changedTables) . ' changed tables...');
            
            foreach ($changedTables as $table) {
                $this->generateAlterMigration($table, $existingMigrations[$table]);
                $this->timestamp++;
            }
        }

        $this->info('Migrations generated successfully!');
    }
    
    /**
     * Analyze all foreign keys in the database
     */
    protected function analyzeForeignKeys($tables)
    {
        $cfg = db_config();
        $params = [
            'dbname' => $cfg['name'],
            'user' => $cfg['user'],
            'password' => $cfg['pass'],
            'host' => $cfg['host'],
            'driver' => 'pdo_mysql',
            'port' => $cfg['port'],
        ];
        $dbalConn = \Doctrine\DBAL\DriverManager::getConnection($params);
        
        $this->registerCustomTypes($dbalConn);
        
        foreach ($tables as $table) {
            try {
                $foreignKeys = $dbalConn->createSchemaManager()->listTableForeignKeys($table);
                if (!empty($foreignKeys)) {
                    $this->foreignKeysMap[$table] = $foreignKeys;
                }
            } catch (\Throwable $e) {
                $this->warn("Could not analyze foreign keys for table: $table");
            }
        }
    }
    
    /**
     * Sort tables by dependencies using topological sort
     */
    protected function sortTablesByDependencies($tables)
    {
        $dependencies = [];
        $sorted = [];
        $visited = [];
        
        // Build dependency graph
        foreach ($tables as $table) {
            $dependencies[$table] = [];
            if (isset($this->foreignKeysMap[$table])) {
                foreach ($this->foreignKeysMap[$table] as $fk) {
                    $foreignTable = $fk->getForeignTableName();
                    if (in_array($foreignTable, $tables) && $foreignTable !== $table) {
                        $dependencies[$table][] = $foreignTable;
                    }
                }
            }
        }
        
        // Topological sort using DFS
        $visit = function($table) use (&$visit, &$visited, &$sorted, $dependencies) {
            if (isset($visited[$table])) {
                return;
            }
            
            $visited[$table] = true;
            
            if (isset($dependencies[$table])) {
                foreach ($dependencies[$table] as $dep) {
                    $visit($dep);
                }
            }
            
            $sorted[] = $table;
        };
        
        foreach ($tables as $table) {
            $visit($table);
        }
        
        return $sorted;
    }
    
    /**
     * Generate a separate migration for all foreign keys
     */
    protected function generateForeignKeysMigration()
    {
        if (empty($this->foreignKeysMap)) {
            $this->info('No foreign keys found.');
            return;
        }
        
        $this->info('Generating foreign keys migration...');
        
        $upStatements = [];
        $downStatements = [];
        
        foreach ($this->foreignKeysMap as $table => $foreignKeys) {
            foreach ($foreignKeys as $fk) {
                $fkName = $fk->getName();
                $localColumns = implode("', '", $fk->getLocalColumns());
                $foreignTable = $fk->getForeignTableName();
                $foreignColumns = implode("', '", $fk->getForeignColumns());
                
                $onUpdate = $fk->hasOption('onUpdate') ? $fk->getOption('onUpdate') : 'RESTRICT';
                $onDelete = $fk->hasOption('onDelete') ? $fk->getOption('onDelete') : 'RESTRICT';
                
                $constraintCode = "                \$table->foreign(['$localColumns'], '$fkName')";
                $constraintCode .= "\n                      ->references(['$foreignColumns'])";
                $constraintCode .= "\n                      ->on('$foreignTable')";
                
                if ($onUpdate !== 'RESTRICT') {
                    $constraintCode .= "\n                      ->onUpdate('$onUpdate')";
                }
                if ($onDelete !== 'RESTRICT') {
                    $constraintCode .= "\n                      ->onDelete('$onDelete')";
                }
                $constraintCode .= ";";
                
                $upStatements[$table][] = $constraintCode;
                $downStatements[$table][] = "                \$table->dropForeign('$fkName');";
            }
        }
        
        $upCode = '';
        $downCode = '';
        
        foreach ($upStatements as $table => $statements) {
            $upCode .= "\n        Schema::table('$table', function (Blueprint \$table) {\n";
            $upCode .= implode("\n", $statements);
            $upCode .= "\n        });\n";
        }
        
        foreach ($downStatements as $table => $statements) {
            $downCode .= "\n        Schema::table('$table', function (Blueprint \$table) {\n";
            $downCode .= implode("\n", $statements);
            $downCode .= "\n        });\n";
        }
        
        $timestamp = date('Y_m_d_His', $this->timestamp);
        $fileName = $timestamp . '_add_foreign_keys.php';
        $path = __DIR__ . '/../../../database/migrations/' . $fileName;
        
        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Foreign keys are added in a separate migration to ensure all tables exist first.
     */
    public function up(): void
    {$upCode
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {$downCode
    }
};
EOT;
        
        $this->files->put($path, $stub);
        $this->line("<comment>Created:</comment> $fileName");
    }

    protected function getAllTables()
    {
        $cfg = db_config();
        $params = [
            'dbname' => $cfg['name'],
            'user' => $cfg['user'],
            'password' => $cfg['pass'],
            'host' => $cfg['host'],
            'driver' => 'pdo_mysql',
            'port' => $cfg['port'],
        ];
        $dbalConn = \Doctrine\DBAL\DriverManager::getConnection($params);
        
        $this->registerCustomTypes($dbalConn);
        
        return $dbalConn->createSchemaManager()->listTableNames();
    }
    
    /**
     * Register custom MySQL types with Doctrine DBAL
     */
    protected function registerCustomTypes($dbalConn)
    {
        try {
            $platform = $dbalConn->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('enum', 'string');
            $platform->registerDoctrineTypeMapping('bit', 'boolean');
            $platform->registerDoctrineTypeMapping('geometry', 'string');
            $platform->registerDoctrineTypeMapping('point', 'string');
            $platform->registerDoctrineTypeMapping('tinyint', 'boolean');
        } catch (\Throwable $e) {
            // Ignore type registration errors
        }
    }

    protected function generateMigration($table, $includeForeignKeys = true)
    {
        $className = 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $table))) . 'Table';
        $timestamp = date('Y_m_d_His', $this->timestamp);
        $fileName = $timestamp . '_create_' . $table . '_table.php';
        $path = __DIR__ . '/../../../database/migrations/' . $fileName;

        if (!$this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $upSchema = $this->getIntrospectionCode($table, $includeForeignKeys);
        
        // Get database-level charset and collation (same for all tables)
        $dbInfo = $this->getDatabaseCharsetAndCollation();
        $charset = $dbInfo['charset'];
        $collation = $dbInfo['collation'];
        
        // Get table-specific engine
        $engine = $this->getTableEngine($table);

        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('$table')) {
            Schema::create('$table', function (Blueprint \$table) {
$upSchema
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `$table` ENGINE = $engine, CHARSET = $charset, COLLATE = $collation");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('$table');
    }
};
EOT;

        $this->files->put($path, $stub);
        $this->line("<comment>Created:</comment> $fileName <info>[$engine, $charset, $collation]</info>");
    }
    
    /**
     * Get database-level charset and collation
     */
    protected function getDatabaseCharsetAndCollation()
    {
        $cfg = db_config();
        $pdo = new \PDO("mysql:host={$cfg['host']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass']);
        
        $stmt = $pdo->query("
            SELECT 
                DEFAULT_CHARACTER_SET_NAME as charset,
                DEFAULT_COLLATION_NAME as collation
            FROM information_schema.SCHEMATA
            WHERE SCHEMA_NAME = '{$cfg['name']}'
        ");
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'charset' => $result['charset'] ?? 'utf8mb4',
            'collation' => $result['collation'] ?? 'utf8mb4_unicode_ci'
        ];
    }
    
    /**
     * Get table engine
     */
    protected function getTableEngine($table)
    {
        $cfg = db_config();
        $pdo = new \PDO("mysql:host={$cfg['host']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass']);
        
        $stmt = $pdo->query("
            SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = '{$cfg['name']}' 
            AND TABLE_NAME = '$table'
        ");
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['ENGINE'] ?? 'InnoDB';
    }
    
    /**
     * Get existing migrations mapped by table name
     */
    protected function getExistingMigrations()
    {
        $path = __DIR__ . '/../../../database/migrations';
        $existing = [];
        
        if (!$this->files->exists($path)) {
            return $existing;
        }
        
        $files = $this->files->files($path);
        
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            
            // Match pattern: YYYY_MM_DD_HHMMSS_create_tablename_table.php
            if (preg_match('/_create_(.+)_table\.php$/', $fileName, $matches)) {
                $tableName = $matches[1];
                $existing[$tableName] = $fileName;
            }
        }
        
        return $existing;
    }
    
    /**
     * Check if table structure has changed compared to existing migration
     */
    protected function hasTableChanged($table, $migrationFile)
    {
        $path = __DIR__ . '/../../../database/migrations/' . $migrationFile;
        
        if (!$this->files->exists($path)) {
            return true;
        }
        
        // Get current table structure
        $currentStructure = $this->getTableStructureHash($table);
        
        // Get migration file content
        $migrationContent = $this->files->get($path);
        
        // Extract the schema definition from migration
        if (preg_match('/Schema::create\([^,]+,\s*function\s*\([^)]+\)\s*{(.+?)}\);/s', $migrationContent, $matches)) {
            $existingSchema = trim($matches[1]);
            $existingHash = md5($existingSchema);
            
            return $currentStructure !== $existingHash;
        }
        
        return true;
    }
    
    /**
     * Get a hash of the table structure for comparison
     */
    protected function getTableStructureHash($table)
    {
        $cfg = db_config();
        $params = [
            'dbname' => $cfg['name'],
            'user' => $cfg['user'],
            'password' => $cfg['pass'],
            'host' => $cfg['host'],
            'driver' => 'pdo_mysql',
            'port' => $cfg['port'],
        ];
        $dbalConn = \Doctrine\DBAL\DriverManager::getConnection($params);
        $this->registerCustomTypes($dbalConn);
        
        try {
            $columns = $dbalConn->createSchemaManager()->listTableColumns($table);
            $indexes = $dbalConn->createSchemaManager()->listTableIndexes($table);
            $foreignKeys = $dbalConn->createSchemaManager()->listTableForeignKeys($table);
            
            $structure = [
                'columns' => [],
                'indexes' => [],
                'foreign_keys' => []
            ];
            
            foreach ($columns as $column) {
                $structure['columns'][] = [
                    'name' => $column->getName(),
                    'type' => $column->getType()->getName(),
                    'notnull' => $column->getNotnull(),
                    'default' => $column->getDefault()
                ];
            }
            
            foreach ($indexes as $index) {
                $structure['indexes'][] = [
                    'name' => $index->getName(),
                    'columns' => $index->getColumns(),
                    'unique' => $index->isUnique(),
                    'primary' => $index->isPrimary()
                ];
            }
            
            foreach ($foreignKeys as $fk) {
                $structure['foreign_keys'][] = [
                    'name' => $fk->getName(),
                    'local' => $fk->getLocalColumns(),
                    'foreign' => $fk->getForeignColumns(),
                    'table' => $fk->getForeignTableName()
                ];
            }
            
            return md5(json_encode($structure));
        } catch (\Throwable $e) {
            return '';
        }
    }
    
    /**
     * Generate ALTER migration for a changed table
     */
    protected function generateAlterMigration($table, $existingMigrationFile)
    {
        $timestamp = date('Y_m_d_His', $this->timestamp);
        $fileName = $timestamp . '_alter_' . $table . '_table.php';
        $path = __DIR__ . '/../../../database/migrations/' . $fileName;
        
        // Detect changes
        $changes = $this->detectTableChanges($table, $existingMigrationFile);
        
        if (empty($changes['add']) && empty($changes['modify']) && empty($changes['drop'])) {
            $this->line("<comment>No structural changes detected for:</comment> $table");
            return;
        }
        
        $upStatements = [];
        $downStatements = [];
        
        // Add new columns
        foreach ($changes['add'] as $column) {
            $upStatements[] = "                \$table->{$column['method']}('{$column['name']}'){$column['modifiers']};";
            $downStatements[] = "                \$table->dropColumn('{$column['name']}');";
        }
        
        // Modify columns
        foreach ($changes['modify'] as $column) {
            $upStatements[] = "                \$table->{$column['method']}('{$column['name']}'){$column['modifiers']}->change();";
            // Down would need old definition - simplified for now
            $downStatements[] = "                // Revert {$column['name']} changes manually if needed";
        }
        
        // Drop columns
        foreach ($changes['drop'] as $columnName) {
            $upStatements[] = "                \$table->dropColumn('$columnName');";
            $downStatements[] = "                // Add back $columnName manually if needed";
        }
        
        $upCode = implode("\n", $upStatements);
        $downCode = implode("\n", $downStatements);
        
        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ALTER migration for table: $table
     */
    public function up(): void
    {
        Schema::table('$table', function (Blueprint \$table) {
$upCode
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('$table', function (Blueprint \$table) {
$downCode
        });
    }
};
EOT;
        
        $this->files->put($path, $stub);
        $this->line("<info>Created ALTER:</info> $fileName");
    }
    
    /**
     * Detect specific changes in table structure
     */
    protected function detectTableChanges($table, $existingMigrationFile)
    {
        // This is a simplified version - in production you'd want more detailed comparison
        $changes = [
            'add' => [],
            'modify' => [],
            'drop' => []
        ];
        
        // For now, we'll just detect new columns
        // A full implementation would parse the existing migration and compare
        
        return $changes;
    }

    protected function generateViewsMigration()
    {
        $this->info('Generating views migration...');
        
        try {
            $cfg = db_config();
            $this->line("Connecting to: {$cfg['host']}:{$cfg['port']}/{$cfg['name']}");
            
            $pdo = new \PDO("mysql:host={$cfg['host']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $this->line("Querying for views...");
            $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $views = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            if (empty($views)) {
                $this->warn('No views found in database.');
                return;
            }
            
            $this->info('Found ' . count($views) . ' views: ' . implode(', ', $views));
            $this->line('Generating migration...');
            
            $viewDefinitions = [];
            foreach ($views as $view) {
                $this->line("Processing view: $view");
                $stmt = $pdo->query("SHOW CREATE VIEW `$view`");
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $sql = $row['Create View'];
                
                // Remove DEFINER
                $sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $sql);
                $sql = preg_replace('/SQL SECURITY [A-Z]+/', '', $sql);
                
                $viewDefinitions[$view] = addslashes($sql);
            }
        } catch (\Exception $e) {
            $this->error('Error generating views migration: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return;
        }
        
        $viewsExport = var_export($viewDefinitions, true);
        
        $fileName = date('Y_m_d_His') . '_create_database_views.php';
        $path = __DIR__ . '/../../../database/migrations/' . $fileName;
        $this->info("Writing to: " . realpath(dirname($path)) . DIRECTORY_SEPARATOR . $fileName);
        
        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \$views = $viewsExport;
        
        \$maxRetries = 10;
        \$attempt = 0;
        
        while (count(\$views) > 0 && \$attempt < \$maxRetries) {
            \$attempt++;
            foreach (\$views as \$name => \$sql) {
                try {
                    DB::statement("DROP VIEW IF EXISTS `\$name`");
                    DB::statement(\$sql);
                    unset(\$views[\$name]);
                } catch (\Throwable \$e) {
                    // Ignore and retry
                }
            }
        }
        
        if (count(\$views) > 0) {
            throw new \Exception("Could not create views: " . implode(', ', array_keys(\$views)));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \$views = array_keys($viewsExport);
        foreach (\$views as \$view) {
            DB::statement("DROP VIEW IF EXISTS `\$view`");
        }
    }
};
EOT;

        $this->files->put($path, $stub);
        $this->info("Created views migration: $fileName");
    }

    protected function generateTriggersMigration()
    {
        $this->info('Generating triggers migration...');
        
        $cfg = db_config();
        $pdo = new \PDO("mysql:host={$cfg['host']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass']);
        
        $triggers = $pdo->query("SHOW TRIGGERS")->fetchAll(\PDO::FETCH_ASSOC);
        
        if (empty($triggers)) {
            $this->info('No triggers found.');
            return;
        }

        $triggerDefinitions = [];
        foreach ($triggers as $trigger) {
            $name = $trigger['Trigger'];
            $stmt = $pdo->query("SHOW CREATE TRIGGER `$name`");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $sql = $row['SQL Original Statement'] ?? $row['Create Trigger'];
            
            // Remove DEFINER
            $sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $sql);
            
            $triggerDefinitions[$name] = addslashes($sql);
        }
        
        $triggersExport = var_export($triggerDefinitions, true);
        
        $fileName = date('Y_m_d_His') . '_create_database_triggers.php';
        $path = __DIR__ . '/../../../database/migrations/' . $fileName;
        
        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \$triggers = $triggersExport;
        
        foreach (\$triggers as \$name => \$sql) {
            DB::unprepared("DROP TRIGGER IF EXISTS `\$name`");
            DB::unprepared(\$sql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \$triggers = array_keys($triggersExport);
        foreach (\$triggers as \$name) {
            DB::unprepared("DROP TRIGGER IF EXISTS `\$name`");
        }
    }
};
EOT;

        $this->files->put($path, $stub);
        $this->info("Created triggers migration: $fileName");
    }

    protected function generateProceduresMigration()
    {
        $this->info('Generating procedures migration...');
        
        $cfg = db_config();
        $pdo = new \PDO("mysql:host={$cfg['host']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass']);
        
        $procedures = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = '{$cfg['name']}'")->fetchAll(\PDO::FETCH_ASSOC);
        
        if (empty($procedures)) {
            $this->info('No procedures found.');
            return;
        }

        $procDefinitions = [];
        foreach ($procedures as $proc) {
            $name = $proc['Name'];
            $stmt = $pdo->query("SHOW CREATE PROCEDURE `$name`");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $sql = $row['Create Procedure'];
            
            // Remove DEFINER
            $sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $sql);
            
            $procDefinitions[$name] = addslashes($sql);
        }
        
        $procsExport = var_export($procDefinitions, true);
        
        $fileName = date('Y_m_d_His') . '_create_database_procedures.php';
        $path = __DIR__ . '/../../../database/migrations/' . $fileName;
        
        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \$procedures = $procsExport;
        
        foreach (\$procedures as \$name => \$sql) {
            DB::unprepared("DROP PROCEDURE IF EXISTS `\$name`");
            DB::unprepared(\$sql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \$procedures = array_keys($procsExport);
        foreach (\$procedures as \$name) {
            DB::unprepared("DROP PROCEDURE IF EXISTS `\$name`");
        }
    }
};
EOT;

        $this->files->put($path, $stub);
        $this->info("Created procedures migration: $fileName");
    }

    protected function getIntrospectionCode($table, $includeForeignKeys = true)
    {
        $cfg = db_config();
        $params = [
            'dbname' => $cfg['name'],
            'user' => $cfg['user'],
            'password' => $cfg['pass'],
            'host' => $cfg['host'],
            'driver' => 'pdo_mysql',
            'port' => $cfg['port'],
        ];
        $dbalConn = \Doctrine\DBAL\DriverManager::getConnection($params);
        
        $this->registerCustomTypes($dbalConn);
        
        try {
            $columns = $dbalConn->createSchemaManager()->listTableColumns($table);
            $indexes = $dbalConn->createSchemaManager()->listTableIndexes($table);
        } catch (\Throwable $e) {
            $this->error("Introspection error: " . $e->getMessage());
            return '';
        }
        
        $lines = [];
        $primaryKey = null;
        $hasActivo = false;
        $hasCreatedAt = false;
        $hasUpdatedAt = false;

        // Process columns
        foreach ($columns as $column) {
            $typeObj = $column->getType();
            try {
                $type = \Doctrine\DBAL\Types\Type::getTypeRegistry()->lookupName($typeObj);
            } catch (\Throwable $e) {
                // Fallback to class name or default
                $type = 'string';
            }
            
            $name = $column->getName();
            
            // Track if standard columns exist
            if (strtolower($name) === 'activo') $hasActivo = true;
            if (strtolower($name) === 'created_at') $hasCreatedAt = true;
            if (strtolower($name) === 'updated_at') $hasUpdatedAt = true;
            
            $nullable = !$column->getNotnull() ? '->nullable()' : '';
            $default = $column->getDefault() !== null ? "->default('{$column->getDefault()}')" : '';
            
            // Check if this is part of primary key
            foreach ($indexes as $index) {
                if ($index->isPrimary() && in_array($name, $index->getColumns())) {
                    if (count($index->getColumns()) === 1 && $name === 'id') {
                        // Single column primary key named 'id'
                        $lines[] = "                \$table->id();";
                        continue 2;
                    } else {
                        // Composite or non-standard primary key
                        $primaryKey = $index->getColumns();
                    }
                }
            }
            
            // Basic mapping - NORMALIZED TYPES:
            // - int/integer -> bigInteger (for consistency with Laravel's id())
            // - text -> string with length 1000 (to allow indexing)
            // - columns ending in _id -> unsignedBigInteger (for FK compatibility)
            $isForeignKey = str_ends_with(strtolower($name), '_id');
            
            $method = match($type) {
                'int', 'integer' => $isForeignKey ? 'unsignedBigInteger' : 'bigInteger',
                'bigint' => $isForeignKey ? 'unsignedBigInteger' : 'bigInteger',
                'varchar', 'string' => 'string',
                'text' => 'string',
                'timestamp', 'datetime' => 'timestamp',
                'date' => 'date',
                'decimal' => 'decimal',
                'boolean', 'tinyint' => 'boolean',
                default => 'string'
            };

            // For text fields that were converted to string, add length parameter
            $lengthParam = '';
            if ($type === 'text') {
                $lengthParam = ', 1000';
            }

            $lines[] = "                \$table->$method('$name'$lengthParam)$nullable$default;";
        }
        
        // Add standard columns if they don't exist
        if (!$hasActivo) {
            $lines[] = "                \$table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');";
        }
        
        if (!$hasCreatedAt && !$hasUpdatedAt) {
            // Use Laravel's timestamps() helper for both
            $lines[] = "                \$table->timestamps(); // created_at y updated_at";
        } else {
            // Add individually if only one is missing
            if (!$hasCreatedAt) {
                $lines[] = "                \$table->timestamp('created_at')->nullable()->useCurrent();";
            }
            if (!$hasUpdatedAt) {
                $lines[] = "                \$table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();";
            }
        }
        
        // Add indexes and unique constraints
        foreach ($indexes as $index) {
            if ($index->isPrimary()) {
                // Handle composite primary keys
                if ($primaryKey && count($primaryKey) > 1) {
                    $columns = implode("', '", $primaryKey);
                    $lines[] = "                \$table->primary(['$columns']);";
                }
            } elseif ($index->isUnique()) {
                $columns = implode("', '", $index->getColumns());
                $indexName = $index->getName();
                if (count($index->getColumns()) === 1) {
                    $lines[] = "                \$table->unique('{$index->getColumns()[0]}', '$indexName');";
                } else {
                    $lines[] = "                \$table->unique(['$columns'], '$indexName');";
                }
            } else {
                // Regular index
                $columns = implode("', '", $index->getColumns());
                $indexName = $index->getName();
                if (count($index->getColumns()) === 1) {
                    $lines[] = "                \$table->index('{$index->getColumns()[0]}', '$indexName');";
                } else {
                    $lines[] = "                \$table->index(['$columns'], '$indexName');";
                }
            }
        }
        
        return implode("\n", $lines);
    }
}
