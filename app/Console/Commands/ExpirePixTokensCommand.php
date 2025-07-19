<?php

namespace App\Console\Commands;

use App\Jobs\ExpirePixTokens;
use Illuminate\Console\Command;

class ExpirePixTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pix:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire PIX tokens that have passed their expiration time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired PIX tokens...');

        // Run synchronously for immediate execution
        ExpirePixTokens::dispatchSync();

        $this->info('PIX expiration completed successfully.');
    }
}
