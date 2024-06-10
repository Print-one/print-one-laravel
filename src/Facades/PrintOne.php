<?php

namespace PrintOne\PrintOne\Facades;

use Illuminate\Support\Facades\Facade;
use PrintOne\PrintOne\Contracts\PrintOneApi;
use PrintOne\PrintOne\DTO\Template;

/**
 * @see \PrintOne\PrintOne\PrintOne
 *
 * @mixin \PrintOne\PrintOne\PrintOne
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
        return \PrintOne\PrintOne\PrintOne::class;
    }
}
