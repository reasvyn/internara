<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Support\Setup\SystemProvisioner;

class ProvisionSystemAction
{
    public function __construct(
        protected readonly SystemProvisioner $provisioner,
    ) {}

    public function execute(bool $force = false): void
    {
        $this->provisioner->executeAll($force);
    }
}
