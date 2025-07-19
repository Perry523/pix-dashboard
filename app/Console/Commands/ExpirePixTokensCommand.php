<?php

namespace App\Console\Commands;

use App\Jobs\ExpirePixTokens;
use Illuminate\Console\Command;

class ExpirePixTokensCommand extends Command
{
    protected $signature = 'pix:expire';

    protected $description = 'Expire PIX tokens that have passed their expiration time';

    public function handle()
    {
        $this->info('Checking for expired PIX tokens...');

        ExpirePixTokens::dispatchSync();

        $this->info('PIX expiration completed successfully.');
    }
}
