<?php

namespace AssistPro\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeControllerCommand extends Command
{
    protected $signature = 'make:controller {name : The name of the controller}';
    protected $description = 'Create a new Controller class';
    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $path = __DIR__ . '/../../Http/Controllers/' . $name . '.php';

        // Ensure directory exists
        if (!$this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if ($this->files->exists($path)) {
            $this->error('Controller already exists!');
            return;
        }

        $stub = $this->getStub();
        $content = str_replace('{{ class }}', $name, $stub);

        $this->files->put($path, $content);
        $this->info('Controller created successfully.');
    }

    protected function getStub()
    {
        return <<<EOT
<?php

namespace AssistPro\Http\Controllers;

class {{ class }}
{
    public function index()
    {
        //
    }
}
EOT;
    }
}
