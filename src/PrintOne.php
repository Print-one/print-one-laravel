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
            ->map(fn($data) => Template::fromArray($data));
    }

    public function order(Postcard $postcard, array $mergeVariables, Address $sender, Address $recipient): Order
    {
        $data = [
            'sender' => $sender->toArray(),
            'recipient' => $recipient->toArray(),
            'format' => $postcard->format,
            'pages' => (object)[
                "1" => $postcard->front,
                "2" => $postcard->back,
            ],
        ];

        if (!empty($mergeVariables)) {
            $data['mergeVariables'] = $mergeVariables;
        }
        
        $response = $this->http->post('orders', $data);

        if ($response->clientError()) {
            $firstError = $response->json('errors.0.message');

            throw new CouldNotPlaceOrder("The order is invalid: {$firstError}");
        }

        if ($response->serverError()) {
            throw new CouldNotPlaceOrder('Something went wrong while placing the order in the Print.one API.');
        }

        return Order::fromArray($response->json());
    }
    
    /**
     * @throws CouldNotFetchOrder
     */
    public function getOrder(string $externId): Order
    {
        $response = $this->http->get("orders/{$externId}");
        if ($response->failed()) {
            throw new CouldNotFetchOrder('Something went wrong while fetching the order from the Print.one API.');
        }
        
        return Order::fromArray($response->json());
    }
    
    /**
     * @throws CouldNotFetchPreview
     */
    public function preview(Template $template, int $retryTimes = 5): string
    {
        $response = $this->http->post("templates/preview/{$template->id}/{$template->version}");
        if ($response->failed()) {
            throw new CouldNotFetchPreview('Something went wrong while fetching the preview from the Print.one API.');
        }

        $previewUrl = $response->json('url');

        $response = rescue(
            fn() => $this->http->retry($retryTimes, 1000)->get($previewUrl),
            fn($error) => $error->response
        );

        if (!$response || $response->failed()) {
            throw new CouldNotFetchPreview('Something went wrong while fetching the preview from the Print.one API.');
        }

        return $response->body();
    }

    /**
     * @throws CouldNotFetchPreview
     */
    public function previewOrder(Order $order): string
    {
        $response = $this->http->get("storage/order/preview/{$order->id}");

        if ($response->failed()) {
            throw new CouldNotFetchPreview('Something went wrong while fetching the preview from the Print.one API.');
        }

        return $response->body();
    }
}
