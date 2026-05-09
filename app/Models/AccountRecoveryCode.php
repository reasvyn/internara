<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['user_id', 'code_hash', 'generated_at', 'used_at', 'expires_at'])]
class AccountRecoveryCode extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'generated_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        return $this->used_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    public static function generateCode(): string
    {
        $length = 12;
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, $length))
            ->map(fn () => $chars[random_int(0, strlen($chars) - 1)])
            ->implode('');
    }
}
