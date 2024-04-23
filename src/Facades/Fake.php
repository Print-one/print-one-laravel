<?php

namespace Nexibi\PrintOne\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nexibi\PrintOne\Contracts\PrintOneApi;
use Nexibi\PrintOne\DTO\Address;
use Nexibi\PrintOne\DTO\Order;
use Nexibi\PrintOne\DTO\Template;
use Nexibi\PrintOne\Enums\Finish;
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

    public function templates(int $page, int $limit): Collection
    {
        return $this->templates;
    }

    public function order(string $templateId, Finish $finish, Address $recipient, Address $sender, array $mergeVariables = []): Order
    {
        $this->orders->push(['templateId' => $templateId, 'finish' => $finish, 'from' => $sender, 'to' => $recipient]);

        return new Order(
            id: Str::uuid(), status: 'status', templateId: $templateId, finish: $finish, createdAt: now(), isBillable: false
        );
    }

    public function preview(Template $template, int $retryTimes = 30): string
    {
        $this->viewed->push($template);

        return '';
    }

    public function assertOrdered(string $templateId, Finish $finish, Address $from, Address $to): void
    {
        $order = $this->orders->where('templateId', $templateId)
            ->where('finish', $finish)
            ->where('from', $from)->where('to', $to)->first();
        Assert::assertNotNull(
            $order,
            "Failed asserting postcard with template ID: '{$templateId}' and finish: '{$finish->value}' was ordered from {$from->name} to {$to->name}"
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

    public function assertNothingOrdered(): void
    {
        Assert::assertEmpty(
            $this->orders,
            "Failed asserting that there was nothing orderded. Found orders: {{$this->orders->toJson(JSON_PRETTY_PRINT)}}"
        );
    }
}
