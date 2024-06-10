<?php

namespace PrintOne\PrintOne\DTO;

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

    public static function fromArray(array $data): self
    {
        return new Address(
            name: $data['name'],
            address: $data['address'],
            postalCode: $data['postalCode'],
            city: $data['city'],
            country: $data['country']
        );
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
