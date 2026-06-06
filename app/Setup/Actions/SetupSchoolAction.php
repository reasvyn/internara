<?php

declare(strict_types=1);

namespace App\Setup\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Support\Settings;
use Illuminate\Support\Facades\Validator;

final class SetupSchoolAction extends BaseAction
{
    public function execute(array $data): void
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

        $this->transaction(function () use ($data) {
            Settings::set([
                'school.name' => ['value' => $data['name'], 'group' => 'school', 'type' => 'string'],
                'school.institutional_code' => ['value' => $data['institutional_code'], 'group' => 'school', 'type' => 'string'],
                'school.email' => ['value' => $data['email'], 'group' => 'school', 'type' => 'string'],
                'school.address' => ['value' => $data['address'] ?? '', 'group' => 'school', 'type' => 'string'],
                'school.phone' => ['value' => $data['phone'] ?? '', 'group' => 'school', 'type' => 'string'],
                'school.website' => ['value' => $data['website'] ?? '', 'group' => 'school', 'type' => 'string'],
                'school.principal_name' => ['value' => $data['principal_name'] ?? '', 'group' => 'school', 'type' => 'string'],
            ]);

            $this->log('school_setup_completed', null, [
                'name' => $data['name'],
                'code' => $data['institutional_code'],
            ]);
        });
    }
}
