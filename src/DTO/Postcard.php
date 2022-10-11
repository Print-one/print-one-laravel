<?php

namespace Nexibi\PrintOne\DTO;

class Postcard
{
    public function __construct(public string $front, public string $back, public string $format)
    {
    }
}
