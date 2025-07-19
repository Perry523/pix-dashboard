<?php

namespace App\Jobs;

use App\Services\PixExpirationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpirePixTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PixExpirationService $expirationService): void
    {
        $expiredCount = $expirationService->expireTokens();

        if ($expiredCount > 0) {
            Log::info("Expired {$expiredCount} PIX tokens via job");
        }
    }
}
