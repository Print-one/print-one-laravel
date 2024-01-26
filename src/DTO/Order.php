<?php

namespace Nexibi\PrintOne\DTO;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

class Order implements Arrayable
{
    public function __construct(
        public string $id,
        public string $status,
        public string $templateId,
        public string $finish,
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
            templateId: $data['templateId'],
            finish: $data['finish'],
            createdAt: Carbon::parse($data['createdAt']),
            isBillable: $data['isBillable']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'templateId' => $this->templateId,
            'finish' => $this->finish,
            'createdAt' => $this->createdAt->toDateTimeString(),
            'isBillable' => $this->isBillable,
        ];
    }
}
