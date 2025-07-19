<?php

use App\Models\Pix;
use App\Models\User;
use App\Services\PixExpirationService;
use App\Services\PusherBeamsService;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = new PixExpirationService();
});

describe('PIX Expiration Service Basic Functionality', function () {
    test('can expire expired PIX tokens', function () {
        $expiredPix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(5),
        ]);

        $validPix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->mock(PusherBeamsService::class, function ($mock) {
            $mock->shouldReceive('sendPixExpiredNotification')->once();
        });

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(1)
            ->and($expiredPix->fresh()->status)->toBe(Pix::STATUS_EXPIRED)
            ->and($validPix->fresh()->status)->toBe(Pix::STATUS_GENERATED);
    });

    test('does not expire already paid PIX', function () {
        $paidPix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_PAID,
            'expires_at' => now()->subMinutes(5),
        ]);

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(0)
            ->and($paidPix->fresh()->status)->toBe(Pix::STATUS_PAID);
    });

    test('does not expire already expired PIX', function () {
        $alreadyExpiredPix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_EXPIRED,
            'expires_at' => now()->subMinutes(5),
        ]);

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(0)
            ->and($alreadyExpiredPix->fresh()->status)->toBe(Pix::STATUS_EXPIRED);
    });

    test('returns zero when no PIX need expiration', function () {
        $validPix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(10),
        ]);

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(0)
            ->and($validPix->fresh()->status)->toBe(Pix::STATUS_GENERATED);
    });
});

describe('PIX Expiration Service Multiple Users', function () {
    test('expires PIX for multiple users', function () {
        $user2 = User::factory()->create();

        $expiredPix1 = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(5),
        ]);

        $expiredPix2 = Pix::create([
            'user_id' => $user2->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(3),
        ]);

        $this->mock(PusherBeamsService::class, function ($mock) {
            $mock->shouldReceive('sendPixExpiredNotification')->twice();
        });

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(2)
            ->and($expiredPix1->fresh()->status)->toBe(Pix::STATUS_EXPIRED)
            ->and($expiredPix2->fresh()->status)->toBe(Pix::STATUS_EXPIRED);
    });
});

describe('PIX Expiration Service Performance', function () {
    test('efficiently processes large number of expired PIX', function () {
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        for ($i = 0; $i < 50; $i++) {
            $user = collect([$this->user, $user2, $user3])->random();
            Pix::create([
                'user_id' => $user->id,
                'status' => Pix::STATUS_GENERATED,
                'expires_at' => now()->subMinutes(rand(1, 30)),
            ]);
        }

        // Create 25 valid PIX
        for ($i = 0; $i < 25; $i++) {
            $user = collect([$this->user, $user2, $user3])->random();
            Pix::create([
                'user_id' => $user->id,
                'status' => Pix::STATUS_GENERATED,
                'expires_at' => now()->addMinutes(rand(5, 30)),
            ]);
        }

        $this->mock(PusherBeamsService::class, function ($mock) {
            $mock->shouldReceive('sendPixExpiredNotification')->times(50);
        });

        $startTime = microtime(true);
        $expiredCount = $this->service->expireTokens();
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000;

        expect($expiredCount)->toBe(50)
            ->and($executionTime)->toBeLessThan(300);

        $expiredPixCount = Pix::where('status', Pix::STATUS_EXPIRED)->count();
        $generatedPixCount = Pix::where('status', Pix::STATUS_GENERATED)->count();

        expect($expiredPixCount)->toBe(50)
            ->and($generatedPixCount)->toBe(25);
    });

    test('handles empty database efficiently', function () {
        $startTime = microtime(true);
        $expiredCount = $this->service->expireTokens();
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000;

        expect($expiredCount)->toBe(0)
            ->and($executionTime)->toBeLessThan(50); // Should be very fast with no data
    });
});

describe('PIX Expiration Service Edge Cases', function () {
    test('handles PIX expiring exactly now', function () {
        $pixExpiringNow = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subSecond(), // Make it clearly expired
        ]);

        $this->mock(PusherBeamsService::class, function ($mock) {
            $mock->shouldReceive('sendPixExpiredNotification')->once();
        });

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(1)
            ->and($pixExpiringNow->fresh()->status)->toBe(Pix::STATUS_EXPIRED);
    });

    test('handles PIX with future expiration correctly', function () {
        $futurePix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addSeconds(1),
        ]);

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(0)
            ->and($futurePix->fresh()->status)->toBe(Pix::STATUS_GENERATED);
    });

    test('handles mixed expiration times correctly', function () {
        $expiredPix1 = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subHours(2),
        ]);

        $expiredPix2 = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(1),
        ]);

        $validPix1 = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(5),
        ]);

        $validPix2 = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addHours(1),
        ]);

        $this->mock(PusherBeamsService::class, function ($mock) {
            $mock->shouldReceive('sendPixExpiredNotification')->twice();
        });

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(2)
            ->and($expiredPix1->fresh()->status)->toBe(Pix::STATUS_EXPIRED)
            ->and($expiredPix2->fresh()->status)->toBe(Pix::STATUS_EXPIRED)
            ->and($validPix1->fresh()->status)->toBe(Pix::STATUS_GENERATED)
            ->and($validPix2->fresh()->status)->toBe(Pix::STATUS_GENERATED);
    });
});

describe('PIX Expiration Service Notifications', function () {
    test('sends notification for each expired PIX', function () {
        $expiredPix1 = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(5),
        ]);

        $expiredPix2 = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(3),
        ]);

        $this->mock(PusherBeamsService::class, function ($mock) {
            $mock->shouldReceive('sendPixExpiredNotification')
                ->twice()
                ->with(\Mockery::type(User::class), \Mockery::type('string'));
        });

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(2);
    });

    test('handles notification failures gracefully', function () {
        $expiredPix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->mock(PusherBeamsService::class, function ($mock) {
            $mock->shouldReceive('sendPixExpiredNotification')
                ->once()
                ->with(\Mockery::type(User::class), \Mockery::type('string'))
                ->andThrow(new \Exception('Notification service error'));
        });

        $expiredCount = $this->service->expireTokens();

        expect($expiredCount)->toBe(1)
            ->and($expiredPix->fresh()->status)->toBe(Pix::STATUS_EXPIRED);
    });
});
