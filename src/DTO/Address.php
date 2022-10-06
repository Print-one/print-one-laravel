<?php

namespace Nexibi\PrintOne\DTO;

use Illuminate\Contracts\Support\Arrayable;

class Address implements Arrayable
{
    public function __construct(
        public string $name,
        public string $address,
        public string $postalCode,
        public string $city,
        public string $country,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }
}
