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

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
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

        $this->info('Generating migrations for ' . count($tables) . ' tables...');

        foreach ($tables as $table) {
            $this->generateMigration($table);
        }

        $this->info('Migrations generated successfully!');
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
        
        return $dbalConn->createSchemaManager()->listTableNames();
    }

    protected function generateMigration($table)
    {
        $className = 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $table))) . 'Table';
        $fileName = date('Y_m_d_His') . '_create_' . $table . '_table.php';
        $path = __DIR__ . '/../../../database/migrations/' . $fileName;

        if (!$this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $upSchema = $this->getIntrospectionCode($table);

        $stub = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        $this->line("<comment>Created:</comment> $fileName");
        
        // Sleep to avoid collision in timestamps
        sleep(1);
    }

    protected function generateViewsMigration()
    {
        $this->info('Generating views migration...');
        
        $cfg = db_config();
        $pdo = new \PDO("mysql:host={$cfg['host']};dbname={$cfg['name']};charset={$cfg['charset']}", $cfg['user'], $cfg['pass']);
        
        $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(\PDO::FETCH_COLUMN);
        
        $viewDefinitions = [];
        foreach ($views as $view) {
            $stmt = $pdo->query("SHOW CREATE VIEW `$view`");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $sql = $row['Create View'];
            
            // Remove DEFINER
            $sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $sql);
            $sql = preg_replace('/SQL SECURITY [A-Z]+/', '', $sql);
            
            $viewDefinitions[$view] = addslashes($sql);
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

    protected function getIntrospectionCode($table)
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
        
        try {
            $columns = $dbalConn->createSchemaManager()->listTableColumns($table);
        } catch (\Throwable $e) {
            $this->error("Introspection error: " . $e->getMessage());
            return '';
        }
        $lines = [];

        foreach ($columns as $column) {
            $typeObj = $column->getType();
            try {
                $type = \Doctrine\DBAL\Types\Type::getTypeRegistry()->lookupName($typeObj);
            } catch (\Throwable $e) {
                // Fallback to class name or default
                $type = 'string';
            }
            
            $name = $column->getName();
            $nullable = !$column->getNotnull() ? '->nullable()' : '';
            $default = $column->getDefault() !== null ? "->default('{$column->getDefault()}')" : '';
            
            // Basic mapping
            $method = match($type) {
                'int', 'integer' => 'integer',
                'bigint' => 'bigInteger',
                'varchar', 'string' => 'string',
                'text' => 'text',
                'timestamp', 'datetime' => 'timestamp',
                'date' => 'date',
                'decimal' => 'decimal',
                'boolean', 'tinyint' => 'boolean',
                default => 'string'
            };

            if ($name === 'id') {
                $lines[] = "                \$table->id();";
            } else {
                $lines[] = "                \$table->$method('$name')$nullable$default;";
            }
        }
        
        return implode("\n", $lines);
    }
}
