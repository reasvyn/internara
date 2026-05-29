<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

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

    public static function generateFor(User $user, int $ttlDays = 7): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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
}
