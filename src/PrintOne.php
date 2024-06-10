<?php

namespace Nexibi\PrintOne;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Nexibi\PrintOne\Contracts\PrintOneApi;
use Nexibi\PrintOne\DTO\Address;
use Nexibi\PrintOne\DTO\Order;
use Nexibi\PrintOne\DTO\Template;
use Nexibi\PrintOne\Enums\Finish;
use Nexibi\PrintOne\Exceptions\CouldNotFetchOrder;
use Nexibi\PrintOne\Exceptions\CouldNotFetchPreview;
use Nexibi\PrintOne\Exceptions\CouldNotFetchTemplates;
use Nexibi\PrintOne\Exceptions\CouldNotPlaceOrder;

class PrintOne implements PrintOneApi
{
    private string $baseUrl = 'https://api.print.one/v2/';

    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'X-Api-Key' => config('print-one.api_key'),
            ]);
    }

    /**
     * @throws CouldNotFetchOrder
     */
    public function templates(int $page = 0, int $limit = 20): Collection
    {
        $response = $this->http->get('templates', ['page' => $page, 'limit' => $limit]);

        if ($response->serverError()) {
            throw new CouldNotFetchTemplates(
                'Something went wrong while fetching the templates from the Print.one API.'
            );
        }

        return $response
            ->collect('data')
            ->map(fn ($data) => Template::fromArray($data));
    }

    public function order(string $templateId, Finish $finish, Address $recipient, Address $sender, array $mergeVariables = []): Order
    {
        $data = [
            'sender' => $sender->toArray(),
            'recipient' => $recipient->toArray(),
            'templateId' => $templateId,
            'finish' => $finish->value,
        ];

        if (! empty($mergeVariables)) {
            $data['mergeVariables'] = (object) $mergeVariables;
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
    public function getOrder(string $orderId): Order
    {
        $response = $this->http->get("orders/{$orderId}");
        if ($response->failed()) {
            throw new CouldNotFetchOrder('Something went wrong while fetching the order from the Print.one API.');
        }

        return Order::fromArray($response->json());
    }

    /**
     * @throws CouldNotFetchPreview
     */
    public function preview(Template $template, int $retryTimes = 10): string
    {
        $response = $this->http->post("templates/preview/{$template->id}/{$template->version}", ['mergeVariables' => (object) []]);

        if ($response->failed()) {
            throw new CouldNotFetchPreview('Something went wrong while fetching the preview from the Print.one API.');
        }

        $responseData = collect($response->json())
            ->map(fn (array $a) => (object) $a);

        // The first item is often the front side of the template
        $previewUrl = $responseData->first()->url;

        $response = rescue(
            fn () => $this->http->retry($retryTimes, 1000)->get($previewUrl),
            fn ($error) => $error->response
        );

        if (! $response || $response->failed()) {
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
