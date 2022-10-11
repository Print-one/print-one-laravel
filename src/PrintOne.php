<?php

namespace Nexibi\PrintOne;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Nexibi\PrintOne\Contracts\PrintOneApi;
use Nexibi\PrintOne\DTO\Address;
use Nexibi\PrintOne\DTO\Order;
use Nexibi\PrintOne\DTO\Postcard;
use Nexibi\PrintOne\DTO\Template;
use Nexibi\PrintOne\Exceptions\CouldNotFetchPreview;
use Nexibi\PrintOne\Exceptions\CouldNotFetchTemplates;
use Nexibi\PrintOne\Exceptions\CouldNotPlaceOrder;

class PrintOne implements PrintOneApi
{
    private string $baseUrl = 'https://api.print.one/v1/';

    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'X-Api-Key' => config('print-one.api_key'),
            ]);
    }

    public function templates(int $page, int $size): Collection
    {
        $response = $this->http->get('templates', ['page' => $page, 'size' => $size]);

        if ($response->serverError()) {
            throw new CouldNotFetchTemplates(
                'Something went wrong while fetching the templates from the Print.one API.'
            );
        }

        return $response
            ->collect('data')
            ->map(fn ($data) => Template::fromArray($data));
    }

    public function order(Postcard $postcard, array $mergeVariables, Address $sender, Address $recipient): Order
    {
        $response = $this->http->post('orders', [
            'sender' => $sender->toArray(),
            'recipient' => $recipient->toArray(),
            'format' => $postcard->format,
            'pages' => [
                $postcard->front,
                $postcard->back,
            ],
            'mergeVariables' => $mergeVariables,
        ]);

        if ($response->clientError()) {
            $firstError = $response->json('errors.0.message');

            throw new CouldNotPlaceOrder("The order is invalid: {$firstError}");
        }

        if ($response->serverError()) {
            throw new CouldNotPlaceOrder('Something went wrong while placing the order in the Print.one API.');
        }

        return Order::fromArray($response->json());
    }

    public function preview(Template $template, int $timeout = 30): string
    {
        $response = $this->http->get("templates/preview/{$template->id}/{$template->version}");

        if ($response->failed()) {
            throw new CouldNotFetchPreview('Something went wrong while fetching the preview from the Print.one API.');
        }

        $previewId = $response->body();

        $response = null;
        $waited = 0;

        while ($waited < $timeout) {
            $response = $this->http->get("storage/template/preview/{$previewId}");

            if ($response->successful()) {
                break;
            }

            sleep(5);
            $waited += 5;
        }

        if (! $response || $response->failed()) {
            throw new CouldNotFetchPreview('Something went wrong while fetching the preview from the Print.one API.');
        }

        return $response->body();
    }
}
