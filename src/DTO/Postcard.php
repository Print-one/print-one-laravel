<?php

namespace Nexibi\PrintOne\DTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class Postcard implements Arrayable, Jsonable
{
    public function __construct(public string $front, public string $back, public string $format) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['front'], $data['back'], $data['format']
        );
    }

    public function toArray(): array
    {
        return [
            'front' => $this->front,
            'back' => $this->back,
            'format' => $this->format,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
