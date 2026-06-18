<?php

declare(strict_types=1);

use App\Academics\School\Actions\SaveSchoolProfileAction;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(LazilyRefreshDatabase::class);

describe('SaveSchoolProfileAction', function () {
    test('saves school settings without logo', function () {
        $data = [
            'name' => 'International School',
            'address' => '123 Main Street',
            'phone' => '+62123456789',
        ];

        app(SaveSchoolProfileAction::class)->execute($data);

        expect(Setting::where('key', 'school.name')->exists())->toBeTrue();
        expect(Setting::where('key', 'school.address')->exists())->toBeTrue();
        expect(Setting::where('key', 'school.phone')->exists())->toBeTrue();
        expect(Setting::find('school.name')?->value)->toBe('International School');
    });

    test('saves school settings with logo upload', function () {
        $data = ['name' => 'Test School'];
        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        app(SaveSchoolProfileAction::class)->execute($data, $logo);

        expect(Setting::where('key', 'school.name')->exists())->toBeTrue();
    });
});
