<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Setup\Support\SystemProvisioner;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Orchestrates the full technical installation:
 * audit -> provision -> generate token.
 *
 * @return array{plaintext: string, expires_at: Carbon}
 */
final class InstallSystemAction extends BaseAction
{
    public function __construct(
        protected readonly EnvironmentAuditor $auditor,
        protected readonly SystemProvisioner $provisioner,
        protected readonly GenerateSetupTokenAction $generateToken,
    ) {}

    /**
     * @throws RuntimeException If audit fails
     *
     * @return array{plaintext: string, expires_at: Carbon}
     */
    public function execute(bool $force = false): array
    {
        $report = $this->auditor->audit();

        if (! $report->passed()) {
            throw new RuntimeException('System audit check failed.');
        }

        $this->withErrorHandling(
            fn () => $this->provisioner->executeAll($force),
            'System provisioning failed during installation',
        );

        return $this->withErrorHandling(
            fn () => $this->generateToken->execute(),
            'Failed to generate setup token',
        );
    }
}
