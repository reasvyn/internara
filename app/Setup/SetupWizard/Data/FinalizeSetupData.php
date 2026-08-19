<?php

declare(strict_types=1);

namespace App\Setup\SetupWizard\Data;

use App\Core\Data\BaseData;

final readonly class FinalizeSetupData extends BaseData
{
    public function __construct(
        /** @var array{name: string, address?: string, phone?: string, email?: string, website?: string, logo_path?: string} */
        public array $schoolData,
        /** @var array{name: string, code?: string, description?: string} */
        public array $departmentData,
        /** @var array{email: string, password: string} */
        public array $adminData,
        /** @var array<int, string> */
        public array $stepsToComplete = ['account', 'school', 'department'],
    ) {}
}
