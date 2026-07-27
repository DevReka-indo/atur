<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    protected $fillable = [
        'email',
        'token',
        'pending_key',
        'type',
        'invitable_id',
        'invited_by',
        'role',
        'status',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'last_sent_at',
    ];

    protected $hidden = ['token', 'pending_key'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereNull('revoked_at');
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->revoked_at === null
            && ! $this->isExpired();
    }

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    public static function pendingKey(string $type, int $invitableId, string $email): string
    {
        return hash('sha256', implode(':', [$type, $invitableId, strtolower(trim($email))]));
    }

    public static function findByPlainTextToken(string $plainTextToken): ?self
    {
        return self::query()
            ->where(function (Builder $query) use ($plainTextToken): void {
                $query->where('token', self::hashToken($plainTextToken))
                    ->orWhere(function (Builder $legacyQuery) use ($plainTextToken): void {
                        $legacyQuery
                            ->where('token', $plainTextToken)
                            ->whereNull('last_sent_at');
                    });
            })
            ->first();
    }
}
