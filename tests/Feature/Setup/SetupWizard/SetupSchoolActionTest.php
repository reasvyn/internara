<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\SetupWizard\Actions;

use App\Settings\Models\Setting;
use App\Setup\SetupWizard\Actions\SetupSchoolAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('setup school action successfully stores school data in settings', function () {
    $action = new SetupSchoolAction;

    $data = [
        'name' => 'State Vocational School 1',
        'institutional_code' => 'SCH-1002',
        'email' => 'smk1@sch.id',
        'address' => '123 Education St',
        'phone' => '021-5551234',
        'website' => 'https://smk1.sch.id',
        'principal_name' => 'Dr. John Doe',
    ];

    $action->execute($data);

    expect(Setting::where('key', 'school.name')->value('value'))->toBe('State Vocational School 1');
    expect(Setting::where('key', 'school.institutional_code')->value('value'))->toBe('SCH-1002');
    expect(Setting::where('key', 'school.email')->value('value'))->toBe('smk1@sch.id');
    expect(Setting::where('key', 'school.principal_name')->value('value'))->toBe('Dr. John Doe');
});

test('setup school action throws validation exception on invalid website', function () {
    $action = new SetupSchoolAction;

    $data = [
        'name' => 'State Vocational School 1',
        'institutional_code' => 'SCH-1002',
        'email' => 'smk1@sch.id',
        'website' => 'not-a-valid-url',
    ];

    expect(fn () => $action->execute($data))->toThrow(ValidationException::class);
});
