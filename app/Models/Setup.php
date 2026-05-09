<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Setup\SetupState;
use Database\Factories\SetupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Setup extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'is_installed',
        'setup_token',
        'token_expires_at',
        'completed_steps',
        'school_id',
        'department_id',
        'recovery_key',
    ];

    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'token_expires_at' => 'datetime',
            'completed_steps' => 'array',
        ];
    }

    public static function generateRecoveryKey(): string
    {
        $plaintext = Str::random(64);
        $encrypted = Crypt::encryptString($plaintext);

        $setup = self::firstOrCreate([]);
        $setup->update(['recovery_key' => $encrypted]);

        return $plaintext;
    }

    public static function validateRecoveryKey(string $plaintext): bool
    {
        $setup = self::first();

        if ($setup === null || $setup->recovery_key === null) {
            return false;
        }

        try {
            $stored = Crypt::decryptString($setup->recovery_key);

            return hash_equals($stored, $plaintext);
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function newFactory(): SetupFactory
    {
        return SetupFactory::new();
    }

    public function asSetupState(): SetupState
    {
        return SetupState::fromModel($this);
    }

    public static function state(): SetupState
    {
        return SetupState::fromModel(self::first() ?? new self);
    }

    public static function markInstalled(): void
    {
        $setup = self::firstOrCreate([]);
        $setup->update(['is_installed' => true]);

        File::put(base_path('.installed'), now()->toDateTimeString());
    }

    public static function generateToken(): array
    {
        $plaintext = Str::random(64);
        $encrypted = Crypt::encryptString($plaintext);
        $expiresAt = now()->addHour();

        $setup = self::firstOrCreate([]);
        $setup->update([
            'setup_token' => $encrypted,
            'token_expires_at' => $expiresAt,
        ]);

        return [
            'encrypted' => $encrypted,
            'plaintext' => $plaintext,
            'expires_at' => $expiresAt,
        ];
    }

    public static function invalidateToken(): void
    {
        $setup = self::first();

        if ($setup !== null) {
            $setup->update([
                'setup_token' => null,
                'token_expires_at' => null,
            ]);
        }
    }

    public static function markStepCompleted(string $step): void
    {
        $setup = self::firstOrCreate([]);
        $steps = $setup->completed_steps ?? [];

        if (! in_array($step, $steps)) {
            $steps[] = $step;
            $setup->update(['completed_steps' => $steps]);
        }
    }

    public static function storeCreatedEntity(string $type, string $uuid): void
    {
        $setup = self::firstOrCreate([]);

        match ($type) {
            'school' => $setup->update(['school_id' => $uuid]),
            'department' => $setup->update(['department_id' => $uuid]),
            default => null,
        };
    }

    public static function getCreatedEntity(string $type): ?string
    {
        $setup = self::first();

        if ($setup === null) {
            return null;
        }

        return match ($type) {
            'school' => $setup->school_id,
            'department' => $setup->department_id,
            default => null,
        };
    }
}
