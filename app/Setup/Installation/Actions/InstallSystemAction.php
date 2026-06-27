<?php

declare(strict_types=1);

namespace App\Setup\Installation\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\AuditReport;
use App\Core\Exceptions\RejectedException;
use App\Setup\Installation\Data\SetupTokenData;
use App\Setup\Installation\Support\SystemProvisioner;
use App\SysAdmin\Observability\Services\EnvironmentAuditor;

/**
 * Orchestrates the full technical installation:
 * audit -> provision -> generate token.
 */
final class InstallSystemAction extends BaseCommandAction
{
    public function __construct(
        protected readonly EnvironmentAuditor $auditor,
        protected readonly SystemProvisioner $provisioner,
        protected readonly GenerateSetupTokenAction $generateToken,
    ) {}

    /**
     * @throws RejectedException If audit fails or provisioning fails
     */
    public function execute(bool $force = false, ?AuditReport $report = null): SetupTokenData
    {
        if ($report === null) {
            $report = $this->auditor->audit();
        }

        if (! $report->passed()) {
            throw new RejectedException('System audit check failed.');
        }

        return $this->transaction(function () use ($force) {
            $this->provisioner->executeAll($force);

            $token = $this->generateToken->execute();

            $this->log('system_installed', null, ['token_id' => $token->token ?? null]);

            return $token;
        });
    }
}
