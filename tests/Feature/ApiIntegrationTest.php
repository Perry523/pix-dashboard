<?php

use App\Models\Notification;
use App\Models\Pix;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

describe('PIX API Endpoints', function () {
    test('GET /api/pix returns paginated PIX list', function () {
        // Create test PIX
        for ($i = 0; $i < 3; $i++) {
            Pix::create([
                'user_id' => $this->user->id,
                'expires_at' => now()->addMinutes(10),
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/pix');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'token', 'status', 'expires_at', 'created_at']
                ],
                'meta' => ['total', 'current_page', 'last_page', 'per_page'],
            ])
            ->assertJsonPath('meta.total', 3);
    });

    test('POST /api/pix creates new PIX', function () {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/pix', [
            'expires_in_minutes' => 15,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'payment_link',
                'qr_code_svg',
                'qr_code_base64',
                'expires_at',
                'status'
            ])
            ->assertJsonPath('status', 'generated');

        expect(Pix::count())->toBe(1);
    });

    test('POST /api/pix/{token} confirms PIX payment', function () {
        $pix = Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post("/api/pix/{$pix->token}");

        $response->assertOk()
            ->assertJson(['message' => 'PIX payment confirmed successfully']);

        expect($pix->fresh()->status)->toBe(Pix::STATUS_PAID);
    });

    test('GET /api/pix/stats returns PIX statistics', function () {
        Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_GENERATED,
            'expires_at' => now()->addMinutes(10),
        ]);

        Pix::create([
            'user_id' => $this->user->id,
            'status' => Pix::STATUS_PAID,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/pix/stats');

        $response->assertOk()
            ->assertJsonStructure(['total', 'paid', 'expired', 'generated'])
            ->assertJsonPath('total', 2)
            ->assertJsonPath('paid', 1)
            ->assertJsonPath('generated', 1);
    });
});

describe('Notification API Endpoints', function () {
    test('GET /api/notifications returns user notifications', function () {
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'pix_created',
            'title' => 'PIX Created',
            'message' => 'Your PIX was created',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'title', 'message', 'read_at', 'created_at']
                ],
                'meta' => ['total', 'current_page', 'last_page', 'per_page']
            ])
            ->assertJsonPath('meta.total', 1);
    });

    test('GET /api/notifications/unread-count returns unread count', function () {
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'test',
            'title' => 'Unread',
            'message' => 'Unread message',
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'test',
            'title' => 'Read',
            'message' => 'Read message',
            'read_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('count', 1);
    });
});

describe('API Authentication', function () {
    test('API endpoints require authentication', function () {
        $endpoints = [
            'GET /api/pix',
            'POST /api/pix',
            'GET /api/pix/stats',
            'GET /api/notifications',
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint);
            
            $response = $this->json($method, $url);
            
            $response->assertUnauthorized();
        }
    });

    test('users can only access their own data', function () {
        $otherUser = User::factory()->create();
        
        $userPix = Pix::create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $otherUserPix = Pix::create([
            'user_id' => $otherUser->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/pix');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);

        $tokens = collect($response->json('data'))->pluck('token');
        expect($tokens)->toContain((string) $userPix->token)
            ->not->toContain((string) $otherUserPix->token);
    });
});
