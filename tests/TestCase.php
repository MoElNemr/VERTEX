<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

$_ENV['APP_BASE_PATH'] = dirname(__DIR__);
$_SERVER['APP_BASE_PATH'] = dirname(__DIR__);
putenv('APP_BASE_PATH='.dirname(__DIR__));

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $_ENV['APP_BASE_PATH'] = dirname(__DIR__);
        $_SERVER['APP_BASE_PATH'] = dirname(__DIR__);
        putenv('APP_BASE_PATH='.dirname(__DIR__));

        $app = require dirname(__DIR__).'/bootstrap/app.php';

        $this->traitsUsedByTest = class_uses_recursive(static::class);

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
