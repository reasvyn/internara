<?php

declare(strict_types=1);

namespace App\Setup\SetupWizard\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Support\Settings;
use Illuminate\Support\Facades\Validator;

final class SetupSchoolAction extends BaseAction
{
    private const array SCHOOL_FIELDS = [
        'name' => 'school.name',
        'institutional_code' => 'school.institutional_code',
        'email' => 'school.email',
        'address' => 'school.address',
        'phone' => 'school.phone',
        'website' => 'school.website',
        'principal_name' => 'school.principal_name',
    ];

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
            $payload = [];

            foreach (self::SCHOOL_FIELDS as $field => $key) {
                $payload[$key] = [
                    'value' => $data[$field] ?? '',
                    'group' => 'school',
                    'type' => 'string',
                ];
            }

            Settings::set($payload);

            $this->log('school_setup_completed', null, [
                'name' => $data['name'],
                'code' => $data['institutional_code'],
            ]);
        });
    }
}
