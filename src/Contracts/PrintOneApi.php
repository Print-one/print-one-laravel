<?php

namespace PrintOne\PrintOne\Contracts;

use Illuminate\Support\Collection;
use PrintOne\PrintOne\DTO\Address;
use PrintOne\PrintOne\DTO\Order;
use PrintOne\PrintOne\DTO\Template;
use PrintOne\PrintOne\Enums\Finish;

interface PrintOneApi
{
    public function templates(int $page, int $limit): Collection;

    public function order(
        string $templateId,
        Finish $finish,
        Address $recipient,
        Address $sender,
        array $mergeVariables = []
    ): Order;

    public function preview(Template $template, int $retryTimes = 5): string;
}
