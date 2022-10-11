<?php

namespace Nexibi\PrintOne\Contracts;

use Illuminate\Support\Collection;
use Nexibi\PrintOne\DTO\Address;
use Nexibi\PrintOne\DTO\Order;
use Nexibi\PrintOne\DTO\Postcard;
use Nexibi\PrintOne\DTO\Template;

interface PrintOneApi
{
    public function templates(int $page, int $size): Collection;

    public function order(
        Postcard $postcard,
        array $mergeVariables,
        Address $sender,
        Address $recipient
    ): Order;

    public function preview(Template $template, int $timeout = 30): string;
}
