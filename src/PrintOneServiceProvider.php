<?php

namespace Nexxtbi\PrintOne;

use Nexxtbi\PrintOne\Commands\PrintOneCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PrintOneServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('print-one')
            ->hasConfigFile();
    }
}
