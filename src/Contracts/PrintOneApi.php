<?php

namespace Nexibi\PrintOne\Contracts;

use Illuminate\Support\Collection;
use Nexibi\PrintOne\DTO\Address;
use Nexibi\PrintOne\DTO\Order;
use Nexibi\PrintOne\DTO\Template;
use Nexibi\PrintOne\Enums\Finish;

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
