<?php

declare(strict_types=1);

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'token_expires_at' => 'datetime',
            'completed_steps' => 'array',
        ];
    }

    protected static function newFactory(): SetupFactory
    {
        return SetupFactory::new();
    }

    public static function isInstalled(): bool
    {
        if (File::exists(base_path('.installed'))) {
            return true;
        }

        $setup = self::first();

        return $setup !== null && $setup->is_installed;
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

    public static function validateToken(string $token): bool
    {
        $setup = self::first();

        if ($setup === null || $setup->setup_token === null) {
            return false;
        }

        if ($setup->token_expires_at === null || now()->greaterThan($setup->token_expires_at)) {
            return false;
        }

        try {
            $decrypted = Crypt::decryptString($setup->setup_token);
        } catch (\Exception) {
            return false;
        }

        return hash_equals($decrypted, $token);
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

    public static function getCurrentStep(): string
    {
        $setup = self::first();

        if ($setup === null || empty($setup->completed_steps)) {
            return 'welcome';
        }

        $steps = $setup->completed_steps;
        $orderedSteps = ['welcome', 'school', 'department'];

        foreach ($orderedSteps as $step) {
            if (! in_array($step, $steps)) {
                return $step;
            }
        }

        return 'complete';
    }

    public static function isStepCompleted(string $step): bool
    {
        $setup = self::first();

        return $setup !== null && in_array($step, $setup->completed_steps ?? []);
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
