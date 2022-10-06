<?php

namespace Nexxtbi\PrintOne;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Nexxtbi\PrintOne\DTO\Template;

class PrintOne
{
    private string $baseUrl = 'https://api.print.one/v1/';

    private PendingRequest $http;

    public function __construct(string $key)
    {
        $this->http = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'X-Api-Key' => $key,
            ]);
    }

    public function templates(int $page, int $size): Collection
    {
        $response = $this->http->get('templates', ['page' => $page, 'size' => $size]);

        return $response
            ->collect('data')
            ->map(fn ($data) => Template::fromArray($data));
    }
}
