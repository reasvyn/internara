<?php

declare(strict_types=1);

namespace App\Setup\Entities;

use App\Core\Entities\BaseEntity;
use App\Settings\Support\Settings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class SetupEntity extends BaseEntity
{
    private const array TYPE_MAP = [
        'is_installed' => 'boolean',
        'completed_steps' => 'json',
        'install_token' => 'string',
        'token_expires_at' => 'datetime',
        'install_recovery_key' => 'string',
        'token_version' => 'integer',
        'updated_at' => 'datetime',
    ];

    public function __construct(
        private bool $dbInstalled,
        private ?string $setupToken,
        private ?Carbon $tokenExpiresAt,
        private array $completedSteps,
        private ?string $recoveryKey,
        private ?Carbon $updatedAt = null,
        private int $tokenVersion = 0,
    ) {}

    public static function fromModel(Model $model): static
    {
        return self::get();
    }

    public static function get(): static
    {
        $values = Settings::get([
            'setup.is_installed',
            'setup.install_token',
            'setup.token_expires_at',
            'setup.completed_steps',
            'setup.install_recovery_key',
            'setup.token_version',
            'setup.updated_at',
        ]);

        return new self(
            dbInstalled: (bool) ($values['setup.is_installed'] ?? false),
            setupToken: $values['setup.install_token'],
            tokenExpiresAt: isset($values['setup.token_expires_at'])
                ? Carbon::parse($values['setup.token_expires_at'])
                : null,
            completedSteps: $values['setup.completed_steps'] ?? [],
            recoveryKey: $values['setup.install_recovery_key'],
            tokenVersion: (int) ($values['setup.token_version'] ?? 0),
            updatedAt: isset($values['setup.updated_at'])
                ? Carbon::parse($values['setup.updated_at'])
                : null,
        );
    }

    public static function update(array $attributes): void
    {
        $payload = [];

        foreach ($attributes as $key => $value) {
            $type = self::TYPE_MAP[$key]
                ?? (is_bool($value) ? 'boolean'
                    : (is_array($value) ? 'json'
                        : (is_int($value) ? 'integer' : 'string')));

            $payload["setup.{$key}"] = [
                'value' => $value,
                'group' => 'setup',
                'type' => $type,
            ];
        }

        Settings::set($payload);
    }

    public function isInstalled(): bool
    {
        return $this->dbInstalled;
    }

    public function setupToken(): ?string
    {
        return $this->setupToken;
    }

    public function tokenExpiresAt(): ?Carbon
    {
        return $this->tokenExpiresAt;
    }

    public function recoveryKey(): ?string
    {
        return $this->recoveryKey;
    }

    public function hasStoredToken(): bool
    {
        return $this->setupToken !== null;
    }

    public function isTokenExpired(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->tokenExpiresAt === null || $now->greaterThan($this->tokenExpiresAt);
    }

    public function validateToken(
        string $decryptedStoredToken,
        string $inputToken,
        ?Carbon $now = null,
    ): bool {
        if ($this->isTokenExpired($now)) {
            return false;
        }

        return hash_equals($decryptedStoredToken, $inputToken);
    }

    public function completedSteps(): array
    {
        return $this->completedSteps;
    }

    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->completedSteps, true);
    }

    public function allStepsCompleted(): bool
    {
        $expectedSteps = config('setup.wizard.step_keys', []);

        if ($expectedSteps === []) {
            return $this->completedSteps !== [];
        }

        return ! array_diff($expectedSteps, $this->completedSteps);
    }

    public function updatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function isWithinFinalizationWindow(int $minutes = 5): bool
    {
        if ($this->updatedAt === null) {
            return false;
        }

        return $this->updatedAt->diffInMinutes(now()) < $minutes;
    }

    public function isWithinFinalizationWindowSeconds(int $seconds = 30): bool
    {
        if ($this->updatedAt === null) {
            return false;
        }

        return $this->updatedAt->diffInSeconds(now()) < $seconds;
    }

    public function hasRecoveryKey(): bool
    {
        return $this->recoveryKey !== null;
    }

    public function tokenVersion(): int
    {
        return $this->tokenVersion;
    }
}
