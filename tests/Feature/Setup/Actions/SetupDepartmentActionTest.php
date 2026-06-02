<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\Setup\Actions\SetupDepartmentAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('SetupDepartmentAction', function () {
    it('creates a department linked to a school', function () {
        $school = School::factory()->create();

        $department = app(SetupDepartmentAction::class)->execute($school->id, [
            'name' => 'Teknik Komputer dan Jaringan',
            'description' => 'Bidang keahlian TKJ',
        ]);

        expect($department)->toBeInstanceOf(Department::class)
            ->and($department->exists)->toBeTrue()
            ->and($department->school_id)->toBe($school->id)
            ->and($department->name)->toBe('Teknik Komputer dan Jaringan');
    });

    it('uses updateOrCreate for same school and name', function () {
        $school = School::factory()->create();
        $data = ['name' => 'Rekayasa Perangkat Lunak', 'description' => 'Bidang RPL'];

        $first = app(SetupDepartmentAction::class)->execute($school->id, $data);
        $second = app(SetupDepartmentAction::class)->execute($school->id, $data);

        expect($first->id)->toBe($second->id);
    });

    it('allows different departments for same school', function () {
        $school = School::factory()->create();

        $tkj = app(SetupDepartmentAction::class)->execute($school->id, ['name' => 'TKJ']);
        $rpl = app(SetupDepartmentAction::class)->execute($school->id, ['name' => 'RPL']);

        expect($tkj->id)->not->toBe($rpl->id);
    });

    it('validates required fields', function () {
        $school = School::factory()->create();

        app(SetupDepartmentAction::class)->execute($school->id, []);
    })->throws(ValidationException::class);

    it('creates department with only required fields', function () {
        $school = School::factory()->create();

        $department = app(SetupDepartmentAction::class)->execute($school->id, [
            'name' => 'Akuntansi',
        ]);

        expect($department->exists)->toBeTrue()
            ->and($department->description)->toBeNull();
    });
});
