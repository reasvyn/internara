<?php

declare(strict_types=1);

namespace App\SysAdmin\Setup\Actions;

use App\Academics\School\Models\School;
use App\Core\Actions\BaseAction;
use Illuminate\Support\Facades\Validator;

final class SetupSchoolAction extends BaseAction
{
    public function execute(array $data): School
    {
        Validator::validate($data, [
            'name' => 'required|string|max:255',
            'institutional_code' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'principal_name' => 'nullable|string|max:255',
        ]);

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
    }
}
