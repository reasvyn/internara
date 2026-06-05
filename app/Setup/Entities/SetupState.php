<?php

declare(strict_types=1);

namespace App\Setup\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class SetupState extends BaseEntity
{
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
        return new self(
            dbInstalled: (bool) ($model->getAttribute('is_installed') ?? false),
            setupToken: $model->getAttribute('setup_token'),
            tokenExpiresAt: $model->getAttribute('token_expires_at'),
            completedSteps: $model->getAttribute('completed_steps') ?? [],
            recoveryKey: $model->getAttribute('recovery_key'),
            updatedAt: $model->getAttribute('updated_at'),
            tokenVersion: (int) ($model->getAttribute('token_version') ?? 0),
        );
    }

    public function isInstalled(): bool
    {
        return $this->dbInstalled;
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

    public function validateToken(string $decryptedStoredToken, string $inputToken, ?Carbon $now = null): bool
    {
        if ($this->isTokenExpired($now)) {
            return false;
        }

        return hash_equals($decryptedStoredToken, $inputToken);
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
