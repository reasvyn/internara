<?php

declare(strict_types=1);

namespace App\Setup\SetupWizard\Actions;

use App\Core\Actions\BaseAction;
use App\Program\Internship\Actions\CreateInternshipAction;
use App\Program\Internship\Models\Internship;

final class SetupInternshipAction extends BaseAction
{
    public function __construct(
        protected readonly CreateInternshipAction $createInternship,
    ) {}

    public function execute(array $data): Internship
    {
        return $this->createInternship->execute($data);
    }
}
