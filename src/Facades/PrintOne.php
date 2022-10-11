<?php

namespace Nexibi\PrintOne\Facades;

use Illuminate\Support\Facades\Facade;
use Nexibi\PrintOne\Contracts\PrintOneApi;
use Nexibi\PrintOne\DTO\Template;

/**
 * @see \Nexibi\PrintOne\PrintOne
 * @mixin \Nexibi\PrintOne\PrintOne
 */
class PrintOne extends Facade
{
    public static function fake(Template ...$templates): PrintOneApi
    {
        static::swap($fake = new Fake($templates));

        return $fake;
    }

    protected static function getFacadeAccessor()
    {
        return \Nexibi\PrintOne\PrintOne::class;
    }
}
