<?php

namespace Nexxtbi\PrintOne;

use Nexxtbi\PrintOne\Commands\PrintOneCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PrintOneServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('print-one')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_print-one_table')
            ->hasCommand(PrintOneCommand::class);
    }
}
