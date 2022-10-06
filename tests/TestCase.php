<?php

namespace Nexxtbi\PrintOne\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexxtbi\PrintOne\PrintOneServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Nexxtbi\\PrintOne\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            PrintOneServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_print-one_table.php.stub';
        $migration->up();
        */
    }
}
