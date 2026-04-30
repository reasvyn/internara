<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

/**
 * Interface WizardService
 *
 * Defines the generic contract for services that power multi-step wizards.
 * This allows the Shared wizard logic to remain decoupled from specific
 * business implementations like Setup or Installation.
 */
interface WizardService
{
    /**
     * Checks if the user has access to the specified wizard step.
     */
    public function requireSetupAccess(string $prevStep = ''): bool;

    /**
     * Performs the business logic for a specific wizard step.
     */
    public function performSetupStep(string $step, ?string $reqRecord = null): bool;

    /**
     * Checks if a specific required record exists for the current step.
     */
    public function isRecordExists(string $recordName): bool;
}
