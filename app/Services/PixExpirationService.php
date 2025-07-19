<?php

namespace App\Services;

use App\Models\Pix;
use Illuminate\Support\Facades\Log;

class PixExpirationService
{
    /**
     * Expire all tokens that have passed their expiration time
     * This method is called by the scheduled command every minute
     */
    public function expireTokens(): int
    {
        // Get expired PIX tokens with user relationship for notifications
        // Uses optimized index: pix_status_expires_at_index
        $expiredPixList = Pix::with('user')
            ->where('status', Pix::STATUS_GENERATED)
            ->where('expires_at', '<', now())
            ->get();

        $expiredCount = 0;

        // Process each expired PIX individually to trigger notifications
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
