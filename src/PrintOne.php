<?php

namespace Nexxtbi\PrintOne;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Nexxtbi\PrintOne\DTO\Address;
use Nexxtbi\PrintOne\DTO\Order;
use Nexxtbi\PrintOne\DTO\Template;
use Nexxtbi\PrintOne\Exceptions\CouldNotFetchTemplates;
use Nexxtbi\PrintOne\Exceptions\CouldNotPlaceOrder;

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

        if($response->serverError()){
            throw new CouldNotFetchTemplates("The Print.One API has an internal server error.");
        }

        return $response
            ->collect('data')
            ->map(fn ($data) => Template::fromArray($data));
    }

    public function order(Template $templateFront, Template $templateBack, array $mergeVariables, Address $sender, Address $recipient): Order
    {
        $response = $this->http->post('orders', [
            'sender' => $sender->toArray(),
            'recipient' => $recipient->toArray(),
            'format' => $templateFront->format,
            'pages' => [
                $templateFront->id,
                $templateBack->id,
            ],
            'mergeVariables' => $mergeVariables,
        ]);

        if ($response->clientError()) {
            $firstError = $response->json('errors.0.message');

            throw new CouldNotPlaceOrder("The order is invalid: {$firstError}");
        }

        if ($response->serverError()) {
            throw new CouldNotPlaceOrder('The Print.One API has an internal server error.');
        }

        return Order::fromArray($response->json());
    }
}
