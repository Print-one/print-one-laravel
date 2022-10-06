<?php

namespace Nexxtbi\PrintOne;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Nexxtbi\PrintOne\Commands\PrintOneCommand;

class PrintOneServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('print-one')
            ->hasConfigFile();
    }
}
