<?php

namespace Nexxtbi\PrintOne\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexxtbi\PrintOne\PrintOne
 */
class PrintOne extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Nexxtbi\PrintOne\PrintOne::class;
    }
}
