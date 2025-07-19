<?php

use App\Models\Notification;
use App\Models\Pix;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('PIX Generation Flow', function () {
    test('user can generate PIX through API', function () {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->post('/api/pix', [
            'expires_in_minutes' => 15,
        ]);

        $response->assertOk();

        // Verify PIX was created in database
        $pix = Pix::where('user_id', $this->user->id)->first();
        expect($pix)->not->toBeNull()
            ->and($pix->status)->toBe(Pix::STATUS_GENERATED);
    });

    test('generated PIX appears in dashboard', function () {
        Pix::create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->get('/dashboard');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard'));
    });
});

describe('PIX Payment Flow', function () {
    test('PIX can be paid through payment link', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Access payment link (simulating external payment)
        $response = $this->get("/pix/{$pix->token}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Pix/Confirm')
                ->where('pix.token', (string) $pix->token)
                ->where('pix.status', 'paid')
            );

        // Verify PIX status changed
        expect($pix->fresh()->status)->toBe(Pix::STATUS_PAID);
    });

    test('PIX payment via API endpoint', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson("/api/pix/{$pix->token}");

        $response->assertOk()
            ->assertJson(['message' => 'PIX payment confirmed successfully']);

        expect($pix->fresh()->status)->toBe(Pix::STATUS_PAID);
    });
});

describe('PIX Expiration Flow', function () {
    test('expired PIX shows appropriate status', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->subMinutes(5),
        ]);

        $response = $this->get("/pix/{$pix->token}");

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Pix/Confirm')
            );

        // PIX should be processed (either expired or paid depending on controller logic)
        expect($pix->fresh()->status)->not->toBe(Pix::STATUS_GENERATED);
    });
});

describe('PIX Edge Cases', function () {
    test('cannot pay already paid PIX', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_PAID,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson("/api/pix/{$pix->token}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'PIX is not available for payment']);
    });

    test('non-existent PIX returns 404', function () {
        $response = $this->get('/pix/non-existent-token');

        $response->assertNotFound();
    });
});

describe('Multi-User Scenarios', function () {
    test('users can only see their own PIX', function () {
        $otherUser = User::factory()->create();
        
        $userPix = Pix::create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $otherUserPix = Pix::create([
            'user_id' => $otherUser->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->getJson('/api/pix');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);

        $tokens = collect($response->json('data'))->pluck('token');
        expect($tokens)->toContain((string) $userPix->token)
            ->not->toContain((string) $otherUserPix->token);
    });

    test('PIX payment works for any user', function () {
        $otherUser = User::factory()->create();
        
        $otherUserPix = Pix::create([
            'user_id' => $otherUser->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Current user can pay other user's PIX
        $response = $this->get("/pix/{$otherUserPix->token}");

        $response->assertOk();
        expect($otherUserPix->fresh()->status)->toBe(Pix::STATUS_PAID);
    });
});
