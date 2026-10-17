<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// Prepend autoloader so worktree App\ classes take precedence over junctioned vendor classmap
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
}, prepend: true);

$_ENV['APP_BASE_PATH'] = dirname(__DIR__);
$_SERVER['APP_BASE_PATH'] = dirname(__DIR__);
putenv('APP_BASE_PATH=' . dirname(__DIR__));

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $_ENV['APP_BASE_PATH'] = dirname(__DIR__);
        $_SERVER['APP_BASE_PATH'] = dirname(__DIR__);
        putenv('APP_BASE_PATH=' . dirname(__DIR__));

        $app = require dirname(__DIR__) . '/bootstrap/app.php';

        $this->traitsUsedByTest = class_uses_recursive(static::class);

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');
    }
}
