<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Logbook\Models\Logbook;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Actions\GetStudentDashboardDataAction;
use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
});

describe('UpdateProfileAction', function () {
    it('updates user profile fields', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'phone' => '08123456789',
            'address' => '123 Main St',
            'bio' => 'A brief bio',
        ]);

        expect($profile->user_id)->toBe($user->id)
            ->and($profile->phone)->toBe('08123456789')
            ->and($profile->address)->toBe('123 Main St');
    });

    it('updates user name and email when provided', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);

        app(UpdateProfileAction::class)->execute($user, [], 'New Name', 'newemail@example.com');

        expect($user->fresh()->name)->toBe('New Name')
            ->and($user->fresh()->email)->toBe('newemail@example.com');
    });

    it('creates profile if none exists', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);

        expect($user->profile)->toBeNull();

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'phone' => '08123456789',
        ]);

        expect($profile)->not->toBeNull()
            ->and($profile->phone)->toBe('08123456789');
    });

    it('validates profile data', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN->value);

        app(UpdateProfileAction::class)->execute($user, [
            'phone' => str_repeat('x', 50),
        ]);
    })->throws(ValidationException::class);
});

describe('GetStudentDashboardDataAction', function () {
    it('returns empty dashboard data for user without registration', function () {
        $user = User::factory()->create();

        $data = app(GetStudentDashboardDataAction::class)->execute($user->id);

        expect($data)->toHaveKeys(['registration', 'totalJournals', 'verifiedJournals'])
            ->and($data['registration'])->toBeNull()
            ->and($data['totalJournals'])->toBe(0)
            ->and($data['verifiedJournals'])->toBe(0);
    });

    it('returns dashboard data with journal counts', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);
        $mentee = Mentee::factory()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $registration->setStatus('active');

        foreach (range(1, 3) as $i) {
            Logbook::factory()->create([
                'user_id' => $user->id,
                'registration_id' => $registration->id,
                'date' => now()->subDays(5 - $i)->toDateString(),
                'is_verified' => true,
            ]);
        }
        foreach (range(1, 2) as $i) {
            Logbook::factory()->create([
                'user_id' => $user->id,
                'registration_id' => $registration->id,
                'date' => now()->subDays(2 - $i)->toDateString(),
                'is_verified' => false,
            ]);
        }

        $data = app(GetStudentDashboardDataAction::class)->execute($user->id);

        expect($data['registration'])->not->toBeNull()
            ->and($data['totalJournals'])->toBe(5)
            ->and($data['verifiedJournals'])->toBe(3);
    });
});
