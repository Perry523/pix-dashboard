<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Pusher\PushNotifications\PushNotifications;

class PusherBeamsService
{
    private PushNotifications $beamsClient;

    public function __construct()
    {
        $this->beamsClient = new PushNotifications([
            'instanceId' => config('services.pusher_beams.instance_id'),
            'secretKey' => config('services.pusher_beams.secret_key'),
        ]);
    }

    public function sendToUser(User $user, string $title, string $message, array $data = []): void
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $data['type'] ?? 'general',
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        try {
            $this->beamsClient->publishToInterests(
                ["$user->id"],
                [
                    'web' => [
                        'notification' => [
                            'title' => $title,
                            'body' => $message,
                        ],
                        'data' => array_merge($data, [
                            'notification_id' => $notification->id,
                            'url' => $data['url'] ?? '/dashboard',
                        ]),
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }

    private function getUserInterest(User $user): string
    {
        return 'user-' . str_replace(['@', '.'], ['-at-', '-dot-'], $user->email);
    }

    public function sendPixPaidNotification(User $user, string $pixToken): void
    {
        $this->sendToUser(
            $user,
            'PIX Pago!',
            "Seu PIX {$pixToken} foi pago com sucesso.",
            [
                'type' => 'pix_paid',
                'pix_token' => $pixToken,
                'url' => '/pix/list',
            ]
        );
    }

    public function sendPixExpiredNotification(User $user, string $pixToken): void
    {
        $this->sendToUser(
            $user,
            'PIX Expirado',
            "Seu PIX {$pixToken} expirou.",
            [
                'type' => 'pix_expired',
                'pix_token' => $pixToken,
                'url' => '/pix/list',
            ]
        );
    }

    public function sendPixCreatedNotification(User $user, string $pixToken): void
    {
        $this->sendToUser(
            $user,
            'PIX Criado!',
            "Seu PIX {$pixToken} foi criado com sucesso.",
            [
                'type' => 'pix_created',
                'pix_token' => $pixToken,
                'url' => '/dashboard',
            ]
        );
    }

    public function getUserInterestForFrontend(User $user): string
    {
        return $this->getUserInterest($user);
    }
}
