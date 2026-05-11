<?php

declare(strict_types=1);

use App\Actions\School\SetupSchoolAction;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a school', function () {
        $school = app(SetupSchoolAction::class)->execute([
            'name' => 'SMK Negeri 1 Jakarta',
            'institutional_code' => '1234567890',
            'address' => 'Jl. Merdeka No. 1',
        ]);

        expect($school)->toBeInstanceOf(School::class)
            ->and($school->name)->toBe('SMK Negeri 1 Jakarta')
            ->and($school->institutional_code)->toBe('1234567890');
    });

    it('throws RuntimeException when school already exists', function () {
        app(SetupSchoolAction::class)->execute([
            'name' => 'First School',
            'institutional_code' => '1111111111',
        ]);

        expect(fn () => app(SetupSchoolAction::class)->execute([
            'name' => 'Second School',
            'institutional_code' => '2222222222',
        ]))->toThrow(RuntimeException::class, 'School already exists.');
    });
});
