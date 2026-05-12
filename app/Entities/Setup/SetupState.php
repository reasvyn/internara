<?php

declare(strict_types=1);

namespace App\Entities\Setup;

use App\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class SetupState extends BaseEntity
{
    public function __construct(
        private bool $dbInstalled,
        private ?string $setupToken,
        private ?Carbon $tokenExpiresAt,
        private array $completedSteps,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            dbInstalled: (bool) ($model->getAttribute('is_installed') ?? false),
            setupToken: $model->getAttribute('setup_token'),
            tokenExpiresAt: $model->getAttribute('token_expires_at'),
            completedSteps: $model->getAttribute('completed_steps') ?? [],
        );
    }

    /**
     * Check if the application is fully installed.
     */
    public function isInstalled(): bool
    {
        return $this->dbInstalled;
    }

    /**
     * Validate the provided input token against the stored token.
     */
    public function validateToken(string $decryptedStoredToken, string $inputToken, ?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        if ($this->tokenExpiresAt === null || $now->greaterThan($this->tokenExpiresAt)) {
            return false;
        }

        return hash_equals($decryptedStoredToken, $inputToken);
    }

    /**
     * Check if a specific step is completed.
     */
    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->completedSteps, true);
    }

    public function hasStoredToken(): bool
    {
        return $this->setupToken !== null;
    }
}
