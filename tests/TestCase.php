<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $this->guardAgainstRealDatabaseTesting($app);

        return $app;
    }

    private function guardAgainstRealDatabaseTesting($app): void
    {
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if (! $app->environment('testing') || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                'Pruebas bloqueadas: PHPUnit debe ejecutarse solo con APP_ENV=testing y DB_CONNECTION=sqlite, DB_DATABASE=:memory:. '
                .'Ejecute "php artisan optimize:clear" antes de correr pruebas.'
            );
        }
    }
}
