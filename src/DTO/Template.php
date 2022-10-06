<?php

namespace Nexxtbi\PrintOne\DTO;

use Carbon\Carbon;

class Template
{
    public function __construct(
        public string $id,
        public string $name,
        public string $format,
        public int $version,
        public Carbon $updatedAt
    ) {
        //
    }

    public static function fromArray(array $array): self
    {
        return new self(
            id: $array['id'],
            name: $array['name'],
            format: $array['format'],
            version: (int) $array['version'],
            updatedAt: Carbon::parse($array['updatedAt'], 'UTC'),
        );
    }
}
