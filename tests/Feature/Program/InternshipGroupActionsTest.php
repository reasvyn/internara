<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Actions\AddMembersToGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\AddMemberToGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\CreateInternshipGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\DeleteInternshipGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\RemoveMemberFromGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\UpdateInternshipGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('IT0OE: internship group actions', function (): void {
    test('IT0OE-UC-GROUP-001/IT0OE-FR-GROUP-001: admin creates a group with and without a placement', function (): void {
        $internship = Internship::factory()->create();
        $placement = Placement::factory()->create(['internship_id' => $internship->id]);

        $withSlot = app(CreateInternshipGroupAction::class)->execute([
            'name' => 'Kelompok A',
            'internship_id' => $internship->id,
            'placement_id' => $placement->id,
            'description' => 'Cohort for site one',
        ]);

        expect(InternshipGroup::where('name', 'Kelompok A')->exists())->toBeTrue()
            ->and($withSlot->placement->is($placement))->toBeTrue()
            ->and($withSlot->members()->count())->toBe(0);

        $withoutSlot = app(CreateInternshipGroupAction::class)->execute([
            'name' => 'Kelompok B',
            'internship_id' => $internship->id,
        ]);

        expect($withoutSlot->placement)->toBeNull()
            ->and(InternshipGroup::where('name', 'Kelompok B')->exists())->toBeTrue();
    });

    test('IT0OE-FR-GROUP-002/IT0OE-FR-GROUP-003: group belongs to its internship and placement links stay nullable', function (): void {
        $internship = Internship::factory()->create();
        $placement = Placement::factory()->create(['internship_id' => $internship->id]);

        $group = app(CreateInternshipGroupAction::class)->execute([
            'name' => 'Linked cohort',
            'internship_id' => $internship->id,
            'placement_id' => $placement->id,
        ]);

        expect($group->internship->is($internship))->toBeTrue()
            ->and($group->placement->is($placement))->toBeTrue();

        $groupId = $group->id;
        $internship->delete();

        expect(InternshipGroup::where('id', $groupId)->exists())->toBeFalse();
    });

    test('IT0OE-FR-GROUP-008/IT0OE-FR-GROUP-007: update persists renamed group fields', function (): void {
        $group = InternshipGroup::factory()->create(['name' => 'Old name']);

        $updated = app(UpdateInternshipGroupAction::class)->execute($group, [
            'name' => 'New name',
            'description' => 'Renamed for the new season',
        ]);

        expect($updated->refresh()->name)->toBe('New name')
            ->and(InternshipGroup::where('name', 'New name')->exists())->toBeTrue()
            ->and($updated->description)->toBe('Renamed for the new season');
    });

    test('IT0OE-UC-GROUP-003: deactivation retires the group while members stay put', function (): void {
        $group = InternshipGroup::factory()->create(['is_active' => true]);
        $registration = Registration::factory()->create();
        app(AddMemberToGroupAction::class)->execute($group, [
            'role' => 'student',
            'registration_id' => $registration->id,
        ]);

        app(UpdateInternshipGroupAction::class)->execute($group, ['is_active' => false]);

        expect($group->refresh()->is_active)->toBeFalse()
            ->and($group->members()->count())->toBe(1);
    });

    test('IT0OE-FR-GROUP-009: deletion is refused while members exist and succeeds when empty', function (): void {
        app()->setLocale('en');
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        app(AddMemberToGroupAction::class)->execute($group, [
            'role' => 'student',
            'registration_id' => $registration->id,
        ]);

        try {
            app(DeleteInternshipGroupAction::class)->execute($group->refresh());
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('internship.delete_group_blocked'));
        }

        expect(InternshipGroup::where('id', $group->id)->exists())->toBeTrue();

        $empty = InternshipGroup::factory()->create();
        app(DeleteInternshipGroupAction::class)->execute($empty);

        expect(InternshipGroup::where('id', $empty->id)->exists())->toBeFalse();
    });

    test('IT0OE-FR-GROUP-016/IT0OE-FR-GROUP-017: single student add stores the registration reference and join timestamp', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        $before = now()->subSecond();

        $member = app(AddMemberToGroupAction::class)->execute($group, [
            'role' => 'student',
            'registration_id' => $registration->id,
        ]);

        expect($member->registration_id)->toBe($registration->id)
            ->and($member->role)->toBe('student')
            ->and($member->joined_at)->not->toBeNull()
            ->and($member->joined_at->greaterThanOrEqualTo($before))->toBeTrue()
            ->and($group->members()->count())->toBe(1);
    });

    test('IT0OE-FR-GROUP-017: single add commits the member row together with its audit entry', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();

        $member = app(AddMemberToGroupAction::class)->execute($group, [
            'role' => 'student',
            'registration_id' => $registration->id,
        ]);

        expect(InternshipGroupMember::where('id', $member->id)->exists())->toBeTrue()
            ->and(Activity::where('event', 'internship_group_member_added')->exists())->toBeTrue();
    });

    test('IT0OE-FR-GROUP-018: removal deletes the member and leaves an audit trail', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        $member = app(AddMemberToGroupAction::class)->execute($group, [
            'role' => 'student',
            'registration_id' => $registration->id,
        ]);

        app(RemoveMemberFromGroupAction::class)->execute($member);

        expect(InternshipGroupMember::where('id', $member->id)->exists())->toBeFalse()
            ->and($group->members()->count())->toBe(0)
            ->and(Activity::where('event', 'internship_group_member_removed')->exists())->toBeTrue();
    });

    test('IT0OE-FR-GROUP-021/IT0OE-FR-GROUP-024: batch add writes every row in one go and report the count', function (): void {
        $group = InternshipGroup::factory()->create();
        $registrations = Registration::factory()->count(3)->create();

        $count = app(AddMembersToGroupAction::class)->execute($group, $registrations->map(
            fn (Registration $registration) => ['role' => 'student', 'registration_id' => $registration->id],
        )->all());

        expect($count)->toBe(3)
            ->and($group->members()->count())->toBe(3);
    });

    test('IT0OE-FR-GROUP-023/IT0OE-NFR-GROUP-002: a duplicate row inside the batch rolls the whole batch back', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        $other = Registration::factory()->create();

        try {
            app(AddMembersToGroupAction::class)->execute($group, [
                ['role' => 'student', 'registration_id' => $other->id],
                ['role' => 'student', 'registration_id' => $registration->id],
                ['role' => 'student', 'registration_id' => $registration->id],
            ]);
            expect(false)->toBeTrue('expected the duplicate row to fail the batch');
        } catch (QueryException) {
            expect(true)->toBeTrue();
        }

        expect($group->members()->count())->toBe(0);
    });

    test('IT0OE-NFR-GROUP-001/IT0OE-FR-GROUP-003: duplicate group-plus-registration pairs are refused at the database', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        app(AddMemberToGroupAction::class)->execute($group, [
            'role' => 'student',
            'registration_id' => $registration->id,
        ]);

        try {
            app(AddMemberToGroupAction::class)->execute($group, [
                'role' => 'student',
                'registration_id' => $registration->id,
            ]);
            expect(false)->toBeTrue('expected a unique-constraint violation');
        } catch (QueryException) {
            expect(true)->toBeTrue();
        }

        expect($group->members()->where('registration_id', $registration->id)->count())->toBe(1);
    });
});
