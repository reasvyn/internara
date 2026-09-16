<?php

declare(strict_types=1);

use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Enums\InternshipGroupRole;
use App\Modules\Program\Domain\InternshipGroup\Livewire\InternshipGroupManager;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('IT0OE: internship group models, policy, and presentation', function (): void {
    test('IT0OE-FR-GROUP-001: the five group attributes mass-assign into a persisted row', function (): void {
        $internship = Internship::factory()->create();

        $group = InternshipGroup::create([
            'name' => 'Fillable cohort',
            'internship_id' => $internship->id,
            'placement_id' => null,
            'description' => 'Created through mass assignment',
            'is_active' => true,
        ]);

        expect(InternshipGroup::where('name', 'Fillable cohort')->exists())->toBeTrue()
            ->and($group->refresh()->description)->toBe('Created through mass assignment')
            ->and($group->is_active)->toBeTrue();
    });

    test('IT0OE-FR-GROUP-013: member rows mass-assign group, identity, role, and timestamp', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        $joinedAt = now()->subDay();

        $member = InternshipGroupMember::create([
            'internship_group_id' => $group->id,
            'registration_id' => $registration->id,
            'user_id' => null,
            'role' => 'student',
            'joined_at' => $joinedAt,
        ]);

        expect(InternshipGroupMember::where('id', $member->id)->exists())->toBeTrue()
            ->and($member->refresh()->registration_id)->toBe($registration->id)
            ->and($member->role)->toBe('student');
    });

    test('IT0OE-FR-GROUP-014: a member resolves its group, registration, and mentor links', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        $mentor = User::factory()->create();

        $studentRow = InternshipGroupMember::factory()->create([
            'internship_group_id' => $group->id,
            'registration_id' => $registration->id,
            'user_id' => null,
        ]);
        $mentorRow = InternshipGroupMember::factory()->create([
            'internship_group_id' => $group->id,
            'registration_id' => null,
            'user_id' => $mentor->id,
            'role' => 'school_teacher',
        ]);

        expect($studentRow->group->is($group))->toBeTrue()
            ->and($studentRow->registration->is($registration))->toBeTrue()
            ->and($mentorRow->user->is($mentor))->toBeTrue()
            ->and($group->members()->count())->toBe(2);
    });

    test('IT0OE-FR-GROUP-025: group reads stay open to every role including guests', function (): void {
        $group = InternshipGroup::factory()->create();

        expect(Gate::allows('viewAny', InternshipGroup::class))->toBeTrue()
            ->and(Gate::allows('view', $group))->toBeTrue();

        foreach (['student', 'teacher', 'supervisor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);

            expect(Gate::allows('viewAny', InternshipGroup::class))->toBeTrue()
                ->and(Gate::allows('view', $group))->toBeTrue();
        }
    });

    test('IT0OE-FR-GROUP-026: only admins may create, update, or delete groups', function (): void {
        $group = InternshipGroup::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        expect(Gate::allows('create', InternshipGroup::class))->toBeTrue()
            ->and(Gate::allows('update', $group))->toBeTrue()
            ->and(Gate::allows('delete', $group))->toBeTrue();

        foreach (['student', 'teacher', 'supervisor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);

            expect(Gate::allows('create', InternshipGroup::class))->toBeFalse()
                ->and(Gate::allows('update', $group))->toBeFalse()
                ->and(Gate::allows('delete', $group))->toBeFalse();
        }
    });

    test('IT0OE-NFR-GROUP-007: group strings resolve in both English and Indonesian', function (): void {
        $keys = [
            'internship.group_created',
            'internship.group_updated',
            'internship.group_deleted',
            'internship.member_removed',
            'internship.confirm_delete_group',
            'internship.delete_group_blocked',
        ];

        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            foreach ($keys as $key) {
                $resolved = __($key, ['name' => 'Kelompok A', 'count' => 3]);

                expect($resolved)->not->toBe($key, "missing {$locale} translation for {$key}");
            }
        }

        app()->setLocale('en');
        expect(__('internship.group_created'))->toBe('Group created.');
        app()->setLocale('id');
        expect(__('internship.group_created'))->toBe('Grup berhasil dibuat.');
    });

    test('IT0OE-NFR-GROUP-008: every group role renders a translated label', function (): void {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            foreach (InternshipGroupRole::cases() as $role) {
                $label = $role->label();

                expect($label)->toBeString()->not->toBeEmpty()
                    ->and($label)->not->toBe($role->value);
            }
        }
    });

    test('IT0OE-FR-GROUP-028: the manager inherits the shared record-manager behavior', function (): void {
        $manager = app(InternshipGroupManager::class);

        expect($manager)->toBeInstanceOf(BaseRecordManager::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        InternshipGroup::factory()->create(['name' => 'Alpha cohort']);

        Livewire::test(InternshipGroupManager::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha cohort');
    });
});
