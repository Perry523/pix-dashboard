<?php

use App\Models\Notification;
use App\Models\User;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Notification Model Core Functionality', function () {
    test('can create notification', function () {
        $user = User::factory()->create();
        
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'Test message',
        ]);

        expect($notification->user_id)->toBe($user->id)
            ->and($notification->type)->toBe('test')
            ->and($notification->title)->toBe('Test Notification')
            ->and($notification->isRead())->toBeFalse();
    });

    test('can mark notification as read', function () {
        $user = User::factory()->create();
        
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test',
            'message' => 'Test message',
        ]);

        $notification->markAsRead();

        expect($notification->fresh()->isRead())->toBeTrue();
    });

    test('belongs to user', function () {
        $user = User::factory()->create();
        
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test',
            'message' => 'Test message',
        ]);

        expect($notification->user)->toBeInstanceOf(User::class)
            ->and($notification->user->id)->toBe($user->id);
    });
});
