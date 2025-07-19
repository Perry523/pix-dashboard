<?php

namespace App\Console\Commands;

use App\Services\PixExpirationService;
use Illuminate\Console\Command;

class ExpirePixTokens extends Command
{
    protected $signature = 'pix:expire';

    protected $description = 'Expire PIX tokens that have passed their expiration time and send notifications';

    public function handle(PixExpirationService $expirationService)
    {
        $startTime = microtime(true);
        $expiredCount = $expirationService->expireTokens();

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        if ($expiredCount > 0) {
            $this->info("✅ Expired {$expiredCount} PIX token(s) with notifications sent");
            $this->info("⏱️  Execution time: {$executionTime}ms");
        }

        return Command::SUCCESS;
    }
}
