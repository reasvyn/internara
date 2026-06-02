<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\School\Models\School;
use App\Domain\Setup\Actions\SetupSchoolAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('SetupSchoolAction', function () {
    it('creates a school with valid data', function () {
        $school = app(SetupSchoolAction::class)->execute([
            'name' => 'SMK Negeri 1 Malang',
            'institutional_code' => 'SMKN1MLG',
            'email' => 'info@smkn1-mlg.sch.id',
            'address' => 'Jl. Veteran No. 1',
            'phone' => '0341555123',
            'website' => 'https://smkn1-mlg.sch.id',
            'principal_name' => 'Dr. Ahmad Fauzi',
        ]);

        expect($school)->toBeInstanceOf(School::class)
            ->and($school->exists)->toBeTrue()
            ->and($school->name)->toBe('SMK Negeri 1 Malang')
            ->and($school->institutional_code)->toBe('SMKN1MLG');
    });

    it('uses updateOrCreate so running twice returns same record', function () {
        $data = [
            'name' => 'SMK Negeri 1 Malang',
            'institutional_code' => 'SMKN1MLG',
            'email' => 'info@smkn1-mlg.sch.id',
        ];

        $first = app(SetupSchoolAction::class)->execute($data);
        $second = app(SetupSchoolAction::class)->execute($data);

        expect($first->id)->toBe($second->id);
    });

    it('validates required fields', function () {
        app(SetupSchoolAction::class)->execute([]);
    })->throws(ValidationException::class);

    it('validates email format', function () {
        app(SetupSchoolAction::class)->execute([
            'name' => 'Test',
            'institutional_code' => 'TST',
            'email' => 'not-an-email',
        ]);
    })->throws(ValidationException::class);

    it('validates website url', function () {
        app(SetupSchoolAction::class)->execute([
            'name' => 'Test',
            'institutional_code' => 'TST',
            'email' => 'test@test.com',
            'website' => 'not-a-url',
        ]);
    })->throws(ValidationException::class);

    it('creates school with only required fields', function () {
        $school = app(SetupSchoolAction::class)->execute([
            'name' => 'Minimal School',
            'institutional_code' => 'MIN',
            'email' => 'min@school.sch.id',
        ]);

        expect($school->exists)->toBeTrue();
    });
});
