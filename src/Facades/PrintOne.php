<?php

namespace Nexibi\PrintOne\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexibi\PrintOne\PrintOne
 */
class PrintOne extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Nexibi\PrintOne\PrintOne::class;
    }
}
