<?php

use App\Models\Pix;
use App\Models\User;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('PIX Model Core Functionality', function () {
    test('can create PIX with default values', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        expect($pix->user_id)->toBe($this->user->id)
            ->and($pix->status)->toBe(Pix::STATUS_GENERATED)
            ->and($pix->token)->not->toBeNull()
            ->and(Str::isUuid((string) $pix->token))->toBeTrue();
    });

    test('can manage PIX status', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $pix->markAsPaid();
        expect($pix->fresh()->status)->toBe(Pix::STATUS_PAID);

        $expiredPix = Pix::create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subMinutes(5),
        ]);
        
        expect($expiredPix->isExpired())->toBeTrue();
    });

    test('can generate payment data', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $shareableData = $pix->getShareableData();

        expect($shareableData)->toBeArray()
            ->and($shareableData)->toHaveKeys(['token', 'payment_link', 'qr_code_svg', 'qr_code_base64', 'expires_at', 'status']);
    });
});
