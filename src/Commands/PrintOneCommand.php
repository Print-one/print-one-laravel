<?php

namespace Nexxtbi\PrintOne\Commands;

use Illuminate\Console\Command;

class PrintOneCommand extends Command
{
    public $signature = 'print-one';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
