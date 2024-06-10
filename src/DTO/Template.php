<?php

namespace PrintOne\PrintOne\DTO;

use Carbon\Carbon;
use PrintOne\PrintOne\Enums\Format;

class Template
{
    public function __construct(
        public string $id,
        public string $name,
        public ?Format $format,
        public int $version,
        public Carbon $updatedAt
    ) {
        //
    }

    public static function fromArray(array $array): self
    {
        $format = (is_string($array['format'])) ? Format::tryFrom($array['format']) : $array['format'];

        return new self(
            id: $array['id'],
            name: $array['name'],
            format: $format,
            version: (int) $array['version'],
            updatedAt: Carbon::parse($array['updatedAt'], 'UTC'),
        );
    }
}
