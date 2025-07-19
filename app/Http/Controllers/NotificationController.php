<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\PusherBeamsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private PusherBeamsService $pusherBeamsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'total' => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
            ],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = Auth::user()
            ->notifications()
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()
            ->notifications()
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function beamsInterest(): JsonResponse
    {
        $interest = $this->pusherBeamsService->getUserInterestForFrontend(Auth::user());

        return response()->json(['interest' => $interest]);
    }
}
