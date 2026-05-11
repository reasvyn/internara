<?php

declare(strict_types=1);

use App\Actions\Mentor\CreateMentorAction;
use App\Models\Mentor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('creates a mentor with user', function () {
        $mentor = app(CreateMentorAction::class)->execute(
            userData: [
                'name' => 'Mr. Smith',
                'email' => 'smith@school.edu',
            ],
            mentorData: ['employee_id' => 'EMP001'],
            role: 'teacher',
        );

        expect($mentor)->toBeInstanceOf(Mentor::class)
            ->and($mentor->user->name)->toBe('Mr. Smith')
            ->and($mentor->user->email)->toBe('smith@school.edu')
            ->and($mentor->user->hasRole('teacher'))->toBeTrue();
    });

    it('throws validation error with missing required fields', function () {
        expect(fn () => app(CreateMentorAction::class)->execute(
            userData: ['name' => 'Incomplete'],
            mentorData: [],
        ))->toThrow(ValidationException::class);
    });
});
