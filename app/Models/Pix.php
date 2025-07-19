<?php

namespace App\Models;

use App\Services\PusherBeamsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Pix extends Model
{
    protected $table = 'pix';

    const STATUS_GENERATED = 'generated';
    const STATUS_PAID = 'paid';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'token',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pix) {
            if (empty($pix->token)) {
                $pix->token = Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);

        // Send push notification
        $pusherBeamsService = app(PusherBeamsService::class);
        $pusherBeamsService->sendPixExpiredNotification($this->user, $this->token);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);

        // Send push notification
        $pusherBeamsService = app(PusherBeamsService::class);
        $pusherBeamsService->sendPixPaidNotification($this->user, $this->token);
    }

    public function getPaymentLink(): string
    {
        return route('pix.confirm', $this->token);
    }

    public function getQrCodeSvg(): string
    {
        return QrCode::size(200)
            ->backgroundColor(255, 255, 255)
            ->color(0, 0, 0)
            ->generate($this->getPaymentLink());
    }

    public function getQrCodeBase64(): string
    {
        return base64_encode(
            QrCode::format('png')
                ->size(200)
                ->backgroundColor(255, 255, 255)
                ->color(0, 0, 0)
                ->generate($this->getPaymentLink())
        );
    }

    public function getShareableData(): array
    {
        return [
            'token' => $this->token,
            'payment_link' => $this->getPaymentLink(),
            'qr_code_svg' => $this->getQrCodeSvg(),
            'qr_code_base64' => $this->getQrCodeBase64(),
            'expires_at' => $this->expires_at,
            'status' => $this->status,
        ];
    }
}
