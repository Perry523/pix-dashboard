<?php

use App\Models\Pix;
use App\Models\User;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('User Model Core Functionality', function () {
    test('can create user with factory', function () {
        $user = User::factory()->create();

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->email)->toBeString()
            ->and($user->name)->toBeString();
    });

    test('has PIX relationship', function () {
        $user = User::factory()->create();
        
        Pix::create([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        expect($user->pix)->toHaveCount(1)
            ->and($user->pix->first())->toBeInstanceOf(Pix::class);
    });

    test('can create API tokens', function () {
        $user = User::factory()->create();
        
        $token = $user->createToken('test-token');
        
        expect($token)->toBeInstanceOf(\Laravel\Sanctum\NewAccessToken::class)
            ->and($user->tokens)->toHaveCount(1);
    });
});
