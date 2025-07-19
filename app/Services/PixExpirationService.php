<?php

namespace App\Services;

use App\Models\Pix;
use Illuminate\Support\Facades\Log;

class PixExpirationService
{
    public function expireTokens(): int
    {
        $expiredPixList = Pix::with('user')
            ->where('status', Pix::STATUS_GENERATED)
            ->where('expires_at', '<', now())
            ->get();

        $expiredCount = 0;

        foreach ($expiredPixList as $pix) {
            $pix->markAsExpired();
            $expiredCount++;
        }

        if ($expiredCount > 0) {
            Log::info("Scheduled expiration: {$expiredCount} PIX tokens expired with notifications sent");
        }

        return $expiredCount;
    }
}
