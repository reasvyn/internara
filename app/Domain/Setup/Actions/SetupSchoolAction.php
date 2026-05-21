<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\School\Models\School;

class SetupSchoolAction extends BaseAction
{
    public function execute(array $data): School
    {
        return $this->withErrorHandling(function () use ($data) {
            return $this->transaction(function () use ($data) {
                $school = School::updateOrCreate(
                    [],
                    $data,
                );

                $this->log('school_setup_completed', $school, [
                    'name' => $data['name'],
                    'code' => $data['institutional_code'],
                ]);

                return $school;
            });
        }, 'Failed to setup school');
    }
}
