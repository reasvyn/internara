<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

class Setup extends Model
{
    protected $table = 'setups';

    protected $fillable = [
        'is_installed',
        'setup_token',
        'token_expires_at',
        'completed_steps',
        'school_id',
        'department_id',
    ];

    protected $casts = [
        'is_installed' => 'boolean',
        'token_expires_at' => 'datetime',
        'completed_steps' => 'array',
    ];

    public static function isInstalled(): bool
    {
        return File::exists(base_path('.installed')) ||
            self::where('is_installed', true)->exists();
    }

    public static function markInstalled(): void
    {
        $setup = self::first() ?? new self;
        $setup->is_installed = true;
        $setup->save();

        File::put(base_path('.installed'), now()->toDateTimeString());
    }

    public static function generateToken(): array
    {
        $plaintext = bin2hex(random_bytes(32));
        $encrypted = Crypt::encryptString($plaintext);
        $hashed = hash('sha256', $plaintext);
        $expiresAt = now()->addHour();

        $setup = self::first() ?? new self;
        $setup->setup_token = $hashed;
        $setup->token_expires_at = $expiresAt;
        $setup->save();

        return [
            'encrypted' => $encrypted,
            'plaintext' => $plaintext,
            'expires_at' => $expiresAt,
        ];
    }

    public static function validateToken(string $token): bool
    {
        $setup = self::first();

        if ($setup === null || $setup->setup_token === null) {
            return false;
        }

        if ($setup->token_expires_at !== null && $setup->token_expires_at->isPast()) {
            return false;
        }

        return hash_equals($setup->setup_token, hash('sha256', $token));
    }

    public static function invalidateToken(): void
    {
        $setup = self::first();

        if ($setup !== null) {
            $setup->setup_token = null;
            $setup->token_expires_at = null;
            $setup->save();
        }
    }

    public static function getCurrentStep(): string
    {
        $setup = self::first();
        $completed = $setup?->completed_steps ?? [];

        return match (count($completed)) {
            0 => 'welcome',
            1 => 'school',
            2 => 'department',
            default => 'complete',
        };
    }

    public static function isStepCompleted(string $step): bool
    {
        $setup = self::first();
        $completed = $setup?->completed_steps ?? [];

        return in_array($step, $completed, true);
    }

    public static function markStepCompleted(string $step): void
    {
        $setup = self::first() ?? new self;
        $completed = $setup->completed_steps ?? [];

        if (! in_array($step, $completed)) {
            $completed[] = $step;
            $setup->completed_steps = $completed;
            $setup->save();
        }
    }

    public static function storeCreatedEntity(string $type, string $id): void
    {
        $setup = self::first() ?? new self;
        $entities = $setup->{$type.'_id'} ?? [];

        if (is_array($entities)) {
            $entities[$type] = $id;
        } else {
            $setup->{$type.'_id'} = $id;
        }

        $setup->save();
    }

    public static function getCreatedEntity(string $type): ?string
    {
        $setup = self::first();

        return $setup?->{$type.'_id'};
    }
}
