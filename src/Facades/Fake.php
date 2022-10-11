<?php

namespace Nexibi\PrintOne\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nexibi\PrintOne\Contracts\PrintOneApi;
use Nexibi\PrintOne\DTO\Address;
use Nexibi\PrintOne\DTO\Order;
use Nexibi\PrintOne\DTO\Postcard;
use Nexibi\PrintOne\DTO\Template;
use PHPUnit\Framework\Assert;

class Fake implements PrintOneApi
{
    private Collection $templates;

    private Collection $orders;

    private Collection $viewed;

    public function __construct(array $templates = [])
    {
        $this->orders = collect();
        $this->viewed = collect();

        $this->templates = collect($templates);
    }

    public function templates(int $page, int $size): Collection
    {
        return $this->templates;
    }

    public function order(Postcard $postcard, array $mergeVariables, Address $sender, Address $recipient): Order
    {
        $this->orders->push(['postcard' => $postcard, 'from' => $sender, 'to' => $recipient]);

        return new Order(
            id: Str::uuid(), status: 'status', createdAt: now(), isBillable: false
        );
    }

    public function preview(Template $template, int $timeout = 30): string
    {
        $this->viewed->push($template);

        return '';
    }

    public function assertOrdered(Postcard $postcard, Address $from, Address $to): void
    {
        $order = $this->orders->where('postcard', $postcard)->where('from', $from)->where('to', $to)->first();
        Assert::assertNotNull(
            $order,
            "Failed asserting postcard with front: '{$postcard->front}' and back: '{$postcard->back}' was ordered from {$from->name} to {$to->name}"
        );
    }

    public function assertViewed(Template $template): void
    {
        $preview = $this->viewed->firstWhere('id', $template->id);

        Assert::assertNotNull(
            $preview,
            "Failed asserting template '{$template->name}'(#{$template->id}) was viewed"
        );
    }
}
