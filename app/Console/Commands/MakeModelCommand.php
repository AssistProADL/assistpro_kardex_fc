<?php

namespace AssistPro\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModelCommand extends Command
{
    protected $signature = 'make:model {name : The name of the model} {--m|migration : Create a new migration file for the model}';
    protected $description = 'Create a new Eloquent model class';
    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $path = __DIR__ . '/../../Models/' . $name . '.php';

        if ($this->files->exists($path)) {
            $this->error('Model already exists!');
            return;
        }

        $stub = $this->getStub();
        $content = str_replace('{{ class }}', $name, $stub);

        $this->files->put($path, $content);
        $this->info('Model created successfully.');

        if ($this->option('migration')) {
            $this->call('make:migration', ['name' => "create_{$name}_table"]);
        }
    }

    protected function getStub()
    {
        return <<<EOT
<?php

namespace AssistPro\Models;

use Illuminate\Database\Eloquent\Model;

class {{ class }} extends Model
{
    protected \$guarded = [];
}
EOT;
    }
}
