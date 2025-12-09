<?php

// Cargar helpers globales
require_once __DIR__ . '/helpers.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Extend Container to add missing methods for console compatibility
class ExtendedContainer extends Container
{
    public function runningUnitTests()
    {
        return false;
    }

    public function databasePath($path = '')
    {
        $basePath = __DIR__ . '/../database';
        return $path ? $basePath . '/' . $path : $basePath;
    }

    public function environment(...$environments)
    {
        $env = $_ENV['APP_ENV'] ?? 'production';

        if (count($environments) > 0) {
            return in_array($env, is_array($environments[0]) ? $environments[0] : $environments);
        }

        return $env;
    }
}

// Create Container
$container = new ExtendedContainer;
Container::setInstance($container);
$container->instance(Container::class, $container);
$container->instance('Illuminate\Container\Container', $container);

// Create Dispatcher
$events = new Dispatcher($container);
$container->instance('Illuminate\Events\Dispatcher', $events);
$container->instance('Illuminate\Contracts\Events\Dispatcher', $events);

// Configure Eloquent
$capsule = new Capsule;

// Load existing DB configuration
require_once __DIR__ . '/db.php';
$dbConfig = db_config();

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $dbConfig['host'],
    'database' => $dbConfig['name'],
    'username' => $dbConfig['user'],
    'password' => $dbConfig['pass'],
    'charset' => $dbConfig['charset'],
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'port' => $dbConfig['port'],
]);

$capsule->setEventDispatcher($events);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Bind 'db' manager
$container->singleton('db', function () use ($capsule) {
    return $capsule->getDatabaseManager();
});

// Bind 'files' (Filesystem)
$container->singleton('files', function () {
    return new \Illuminate\Filesystem\Filesystem;
});

// Aliases
$container->alias('Illuminate\Events\Dispatcher', 'events');
$container->alias('Illuminate\Contracts\Events\Dispatcher', 'events');
$container->alias('db', 'Illuminate\Database\DatabaseManager');
$container->alias('files', 'Illuminate\Filesystem\Filesystem');

// Bind database connection for Schema facade
$container->singleton('db.connection', function ($container) {
    return $container['db']->connection();
});

// Bind Schema Builder - this is what Schema facade resolves to
$container->singleton('db.schema', function ($container) {
    return $container['db.connection']->getSchemaBuilder();
});

// Set Facade Application - allows facades to resolve from container
\Illuminate\Support\Facades\Facade::setFacadeApplication($container);

// Bind Dispatchers for Routing
$container->bind(
    \Illuminate\Routing\Contracts\CallableDispatcher::class,
    \Illuminate\Routing\CallableDispatcher::class
);
$container->bind(
    \Illuminate\Routing\Contracts\ControllerDispatcher::class,
    \Illuminate\Routing\ControllerDispatcher::class
);

// Configure Validator
$container->singleton('validator', function ($container) {
    $filesystem = $container['files'];
    $loader = new \Illuminate\Translation\FileLoader($filesystem, __DIR__ . '/../resources/lang');
    $loader->addNamespace('lang', __DIR__ . '/../resources/lang');

    $translator = new \Illuminate\Translation\Translator($loader, 'es');

    return new \Illuminate\Validation\Factory($translator, $container);
});

$container->alias('validator', 'Illuminate\Contracts\Validation\Factory');
$container->alias('validator', 'Illuminate\Validation\Factory');

// Configure View Factory
$container->singleton('view', function ($container) {
    $filesystem = $container['files'];
    $eventDispatcher = $container['events'];

    $viewPaths = [__DIR__ . '/../resources/views'];
    $cachePath = __DIR__ . '/../storage/framework/views';

    $bladeCompiler = new \Illuminate\View\Compilers\BladeCompiler($filesystem, $cachePath);

    $engineResolver = new \Illuminate\View\Engines\EngineResolver;
    $engineResolver->register('blade', function () use ($bladeCompiler) {
        return new \Illuminate\View\Engines\CompilerEngine($bladeCompiler);
    });
    $engineResolver->register('php', function () use ($filesystem) {
        return new \Illuminate\View\Engines\PhpEngine($filesystem);
    });

    $finder = new \Illuminate\View\FileViewFinder($filesystem, $viewPaths);
    $factory = new \Illuminate\View\Factory($engineResolver, $finder, $eventDispatcher);

    $factory->setContainer($container);
    return $factory;
});

$container->alias('view', 'Illuminate\Contracts\View\Factory');

// Helper function for views
if (!function_exists('view')) {
    function view($view = null, $data = [], $mergeData = [])
    {
        $factory = \Illuminate\Container\Container::getInstance()->make('view');

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }
}

// Return the container (useful for the router)
return $container;
