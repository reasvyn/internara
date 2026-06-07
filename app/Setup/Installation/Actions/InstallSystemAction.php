<?php

declare(strict_types=1);

namespace App\Setup\Installation\Actions;

use App\Core\Actions\BaseAction;
use App\Data\AuditReport;
use App\Setup\Installation\Data\SetupTokenData;
use App\Setup\Installation\Support\SystemProvisioner;
use App\SysAdmin\Observability\Services\EnvironmentAuditor;
use RuntimeException;

/**
 * Orchestrates the full technical installation:
 * audit -> provision -> generate token.
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
     */
    public function execute(bool $force = false, ?AuditReport $report = null): SetupTokenData
    {
        if ($report === null) {
            $report = $this->auditor->audit();
        }

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
