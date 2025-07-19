<?php

namespace App\Http\Controllers;

use App\Models\Pix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PixController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Expiration is handled by scheduled job every minute
        $query = Auth::user()->pix()->latest();

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $query->where('token', 'like', '%' . $request->search . '%');
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        if ($perPage === 'all') {
            $pixList = $query->get();
            $total = $pixList->count();
            $currentPage = 1;
            $lastPage = 1;
        } else {
            $paginated = $query->paginate((int)$perPage);
            $pixList = $paginated->items();
            $total = $paginated->total();
            $currentPage = $paginated->currentPage();
            $lastPage = $paginated->lastPage();
        }

        return response()->json([
            'data' => $pixList,
            'meta' => [
                'total' => $total,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
            ],
            'filters' => [
                'search' => $request->search,
                'status' => $request->status ?: 'all',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'expires_in_minutes' => 'nullable|integer|min:1|max:1440', // Max 24 hours
        ]);

        $expiresInMinutes = $request->input('expires_in_minutes', 10);

        $pix = Pix::create([
            'user_id' => Auth::id(),
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        return response()->json($pix->getShareableData());
    }

    public function confirm(string $token): Response
    {
        $pix = Pix::where('token', $token)->with('user')->firstOrFail();

        // Only mark as paid if still generated (not expired)
        // Expiration is handled by scheduled job every minute
        if ($pix->status === Pix::STATUS_GENERATED) {
            $pix->markAsPaid();
        }

        return Inertia::render('Pix/Confirm', [
            'pix' => $pix->fresh(['user']),
        ]);
    }

    public function qrcode(string $token)
    {
        $pix = Pix::where('token', $token)->firstOrFail();

        return response(
            QrCode::format('png')
                ->size(300)
                ->backgroundColor(255, 255, 255)
                ->color(0, 0, 0)
                ->generate($pix->getPaymentLink())
        )->header('Content-Type', 'image/png');
    }

    public function confirmPayment(string $token): JsonResponse
    {
        $pix = Pix::where('token', $token)->firstOrFail();

        if ($pix->status === Pix::STATUS_GENERATED) {
            $pix->markAsPaid();
            return response()->json(['message' => 'PIX payment confirmed successfully']);
        }

        return response()->json(['message' => 'PIX is not available for payment'], 400);
    }

    public function stats(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total' => $user->pix()->count(),
            'paid' => $user->pix()->where('status', Pix::STATUS_PAID)->count(),
            'expired' => $user->pix()->where('status', Pix::STATUS_EXPIRED)->count(),
            'generated' => $user->pix()->where('status', Pix::STATUS_GENERATED)->count(),
        ];

        return response()->json($stats);
    }
}
