<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Modules\Setup\Services\Contracts\SetupRequirementProvider;

/**
 * Registry for setup requirement providers.
 * 
 * [S3 - Scalable] Centralizes the management of external setup hooks.
 */
class SetupRequirementRegistry
{
    /**
     * @var array<string, SetupRequirementProvider>
     */
    protected array $providers = [];

    /**
     * Registers a new provider.
     */
    public function register(SetupRequirementProvider $provider): void
    {
        $this->providers[$provider->getRequirementIdentifier()] = $provider;
    }

    /**
     * Checks if a specific requirement is satisfied.
     */
    public function isRequirementSatisfied(string $identifier): bool
    {
        if (! isset($this->providers[$identifier])) {
            throw new \InvalidArgumentException("No setup requirement provider registered for identifier '{$identifier}'.");
        }

        return $this->providers[$identifier]->isSatisfied();
    }
}
