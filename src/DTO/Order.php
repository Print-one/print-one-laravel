<?php

namespace Nexxtbi\PrintOne\DTO;

use Carbon\Carbon;

class Order
{
    public function __construct(
        public string $id,
        public string $status,
        public Carbon $createdAt,
        public bool $isBillable,
    ) {
        //
    }

    public static function fromArray(array $data): self
    {
        return new Order(
            id: $data['id'],
            status: $data['status'],
            createdAt: Carbon::parse($data['createdAt']),
            isBillable: $data['isBillable']
        );
    }
}
