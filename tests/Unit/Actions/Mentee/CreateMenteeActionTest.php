<?php

declare(strict_types=1);

use App\Actions\Mentee\CreateMenteeAction;
use App\Models\Mentee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('creates a mentee with user and student role', function () {
        $mentee = app(CreateMenteeAction::class)->execute([
            'name' => 'Jane Student',
            'email' => 'jane@example.com',
        ]);

        expect($mentee)->toBeInstanceOf(Mentee::class)
            ->and($mentee->user->name)->toBe('Jane Student')
            ->and($mentee->user->email)->toBe('jane@example.com')
            ->and($mentee->user->hasRole('student'))->toBeTrue();
    });

    it('throws validation error with missing required fields', function () {
        expect(fn () => app(CreateMenteeAction::class)->execute([
            'name' => 'Incomplete',
        ]))->toThrow(ValidationException::class);
    });
});
