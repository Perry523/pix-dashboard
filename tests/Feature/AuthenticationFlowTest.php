<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('User Registration', function () {
    test('user can register with valid data', function () {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        // Verify user was created
        $user = User::where('email', 'john@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('John Doe');

        $this->assertAuthenticated();
    });

    test('registration fails with invalid data', function () {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    });
});

describe('User Login', function () {
    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    });

    test('login fails with invalid credentials', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });
});

describe('User Logout', function () {
    test('authenticated user can logout', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    });
});

describe('Dashboard Access', function () {
    test('authenticated user can access dashboard', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard'));
    });

    test('guest is redirected to login', function () {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    });
});

describe('API Authentication', function () {
    test('API requests require authentication', function () {
        $response = $this->getJson('/api/pix');

        $response->assertUnauthorized();
    });

    test('API requests work with valid token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/pix');

        $response->assertOk();
    });
});
