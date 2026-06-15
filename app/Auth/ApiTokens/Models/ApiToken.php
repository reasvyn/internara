<?php

declare(strict_types=1);

namespace App\Auth\ApiTokens\Models;

use App\Auth\AccountRecovery\Entities\RecoveryCodeState;
use App\Auth\ApiTokens\Entities\ActivationToken;
use App\Auth\ApiTokens\Entities\ApiTokenState;
use App\Core\Models\BaseModel;
use App\User\Models\User;
use Database\Factories\ApiTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

#[
    Fillable([
        'user_id',
        'token',
        'token_type',
        'name',
        'scopes',
        'expires_at',
        'attempts',
        'last_attempt_at',
        'last_used_at',
        'revoked_at',
    ]),
]
class ApiToken extends BaseModel
{
    use HasFactory;

    protected $table = 'api_tokens';

    protected $casts = [
        'expires_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'attempts' => 'integer',
        'scopes' => 'array',
    ];

    protected static function newFactory(): ApiTokenFactory
    {
        return ApiTokenFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asActivationToken(): ActivationToken
    {
        return ActivationToken::fromModel($this);
    }

    public function asApiTokenState(): ApiTokenState
    {
        return ApiTokenState::fromModel($this);
    }

    public function asRecoveryCodeState(): RecoveryCodeState
    {
        return RecoveryCodeState::fromModel($this);
    }

    public static function generateFor(User $user, string $type, array $options = []): array
    {
        $raw = bin2hex(random_bytes(32));
        $ttlDays = $options['ttl_days'] ?? match ($type) {
            'activation' => 30,
            'recovery' => 7,
            default => 1,
        };

        $token = self::updateOrCreate(
            ['user_id' => $user->id, 'token_type' => $type],
            [
                'token' => Hash::make($raw),
                'name' => $options['name'] ?? null,
                'scopes' => $options['scopes'] ?? null,
                'expires_at' => now()->addDays($ttlDays),
                'attempts' => 0,
                'last_attempt_at' => null,
                'last_used_at' => null,
                'revoked_at' => null,
            ],
        );

        return ['token' => $token, 'plain_text' => $raw];
    }

    public static function verify(User $user, string $type, string $plainText): bool
    {
        $record = self::where('user_id', $user->id)
            ->where('token_type', $type)
            ->whereNull('revoked_at')
            ->first();

        if (! $record) {
            return false;
        }

        if ($record->expires_at && $record->expires_at->isPast()) {
            return false;
        }

        if (! Hash::check($plainText, $record->token)) {
            $record->increment('attempts');
            $record->update(['last_attempt_at' => now()]);

            return false;
        }

        $record->update(['last_used_at' => now(), 'attempts' => 0]);

        return true;
    }

    public static function revokeFor(User $user, string $type): void
    {
        self::where('user_id', $user->id)
            ->where('token_type', $type)
            ->update(['revoked_at' => now()]);
    }

    public static function revokeAllExpired(): int
    {
        return self::whereNull('revoked_at')
            ->where('expires_at', '<', now())
            ->update(['revoked_at' => now()]);
    }
}
