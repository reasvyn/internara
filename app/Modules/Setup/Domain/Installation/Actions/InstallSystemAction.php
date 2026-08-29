<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\Installation\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\AuditReport;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Setup\Domain\Installation\Data\SetupTokenData;
use App\Modules\Setup\Domain\Installation\Services\SystemProvisioner;
use App\Modules\SysAdmin\Domain\Observability\Services\EnvironmentAuditor;

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
            throw new RejectedException(__('setup.audit_failed'));
        }

        return $this->transaction(function () use ($force) {
            $this->provisioner->executeAll($force);

            $token = $this->generateToken->execute();

            $this->log('system_installed', null, ['token_id' => $token->token ?? null]);

            return $token;
        });
    }
}
