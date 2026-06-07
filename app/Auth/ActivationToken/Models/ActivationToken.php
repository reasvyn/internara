<?php

declare(strict_types=1);

namespace App\Auth\ActivationToken\Models;

use App\Core\Models\BaseModel;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ActivationToken extends BaseModel
{
    protected $fillable = [
        'user_id',
        'token',
        'token_type',
        'expires_at',
        'attempts',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateFor(User $user, int $ttlDays = 30): string
    {
        $raw = strtoupper(Str::random(16));
        $code = implode('-', str_split($raw, 4));

        self::updateOrCreate(
            ['user_id' => $user->id, 'token_type' => 'activation'],
            [
                'token' => Hash::make($code),
                'expires_at' => now()->addDays($ttlDays),
                'attempts' => 0,
                'last_attempt_at' => null,
            ],
        );

        return $code;
    }

    public static function revokeFor(User $user): void
    {
        self::where('user_id', $user->id)->where('token_type', 'activation')->delete();
    }
}
