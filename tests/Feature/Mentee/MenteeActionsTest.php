<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Mentee\Actions\CreateMenteeAction;
use App\Domain\Mentee\Actions\DeleteMenteeAction;
use App\Domain\Mentee\Actions\UpdateMenteeAction;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
});

describe('CreateMenteeAction', function () {
    it('creates a mentee with a new user', function () {
        $mentee = app(CreateMenteeAction::class)->execute([
            'name' => 'John Student',
            'email' => 'john@example.com',
            'username' => 'johnstudent',
        ]);

        expect($mentee)->toBeInstanceOf(Mentee::class)
            ->and($mentee->user->name)->toBe('John Student')
            ->and($mentee->user->email)->toBe('john@example.com')
            ->and($mentee->user->hasRole(Role::STUDENT->value))->toBeTrue()
            ->and($mentee->is_active)->toBeTrue();
    });

    it('creates a mentee with additional mentee data', function () {
        $mentee = app(CreateMenteeAction::class)->execute(
            [
                'name' => 'Jane Student',
                'email' => 'jane@example.com',
                'username' => 'janestudent',
            ],
            ['internal_notes' => 'Transferred from other program'],
        );

        expect($mentee->internal_notes)->toBe('Transferred from other program');
    });

    it('validates required user fields', function () {
        app(CreateMenteeAction::class)->execute(['name' => 'No Email']);
    })->throws(ValidationException::class);
});

describe('UpdateMenteeAction', function () {
    it('updates a mentee record', function () {
        $mentee = Mentee::factory()->create();

        $updated = app(UpdateMenteeAction::class)->execute($mentee, [
            'is_active' => false,
            'internal_notes' => 'Updated notes',
        ]);

        expect($updated->is_active)->toBeFalse()
            ->and($updated->internal_notes)->toBe('Updated notes');
    });
});

describe('DeleteMenteeAction', function () {
    it('deletes a mentee and associated user', function () {
        $mentee = Mentee::factory()->create();
        $userId = $mentee->user_id;

        app(DeleteMenteeAction::class)->execute($mentee);

        expect(Mentee::find($mentee->id))->toBeNull()
            ->and(User::find($userId))->toBeNull();
    });
});
