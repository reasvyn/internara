<?php

declare(strict_types=1);

namespace App\Domain\Setup\Services;

/**
 * Registry for setup requirement providers.
 *
 * [S3 - Scalable] Centralizes the management of external setup hooks.
 */
class SetupRequirementRegistry
{
    /**
     * @var array<string, mixed>
     */
    protected array $providers = [];

    /**
     * Registers a new provider.
     */
    public function register(mixed $provider): void
    {
        if (method_exists($provider, 'getRequirementIdentifier')) {
            $this->providers[$provider->getRequirementIdentifier()] = $provider;
        }
    }

    /**
     * Checks if a specific requirement is satisfied.
     */
    public function isRequirementSatisfied(string $identifier): bool
    {
        if (! isset($this->providers[$identifier])) {
            return false;
        }

        if (method_exists($this->providers[$identifier], 'isSatisfied')) {
            return $this->providers[$identifier]->isSatisfied();
        }

        return false;
    }
}
