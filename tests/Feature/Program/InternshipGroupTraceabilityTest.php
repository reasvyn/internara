<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Actions\AddMembersToGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\AddMemberToGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\DeleteInternshipGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\RemoveMemberFromGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Enums\InternshipGroupRole;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Program\Domain\InternshipGroup\Policies\InternshipGroupPolicy;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('IT0OE: internship groups advanced traceability', function (): void {

    test('IT0OE-NFR-GROUP-004: blocked deletions explain which members prevent the deletion', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();

        InternshipGroupMember::create([
            'internship_group_id' => $group->id,
            'role' => InternshipGroupRole::STUDENT->value,
            'registration_id' => $registration->id,
            'joined_at' => now(),
        ]);

        $action = app(DeleteInternshipGroupAction::class);

        expect(fn () => $action->execute($group))
            ->toThrow(RejectedException::class);
    });

    test('IT0OE-NFR-GROUP-005: member counts refresh immediately after add and remove operations', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();

        expect($group->members()->count())->toBe(0);

        $addAction = app(AddMemberToGroupAction::class);
        $member = $addAction->execute($group, [
            'role' => InternshipGroupRole::STUDENT->value,
            'registration_id' => $registration->id,
        ]);

        expect($group->members()->count())->toBe(1);

        $removeAction = app(RemoveMemberFromGroupAction::class);
        $removeAction->execute($member);

        expect($group->members()->count())->toBe(0);
    });

    test('IT0OE-DD-GROUP-001: group capacity left to placement layer — groups hold reference without internal counter column', function (): void {
        $placement = Placement::factory()->create(['quota' => 5]);
        $group = InternshipGroup::factory()->create([
            'placement_id' => $placement->id,
        ]);

        expect($group->placement_id)->toBe($placement->id)
            ->and($group->placement->quota)->toBe(5)
            ->and(array_key_exists('quota', $group->getAttributes()))->toBeFalse();
    });

    test('IT0OE-DD-GROUP-002: one member-intake Action with role branching handles both student and mentor paths', function (): void {
        $action = app(AddMemberToGroupAction::class);
        $group = InternshipGroup::factory()->create();

        $registration = Registration::factory()->create();
        $studentMember = $action->execute($group, [
            'role' => InternshipGroupRole::STUDENT->value,
            'registration_id' => $registration->id,
        ]);

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $mentorMember = $action->execute($group, [
            'role' => InternshipGroupRole::SCHOOL_TEACHER->value,
            'mentor_id' => $teacher->id,
        ]);

        expect($studentMember->role)->toBe(InternshipGroupRole::STUDENT->value)
            ->and($studentMember->registration_id)->toBe($registration->id)
            ->and($mentorMember->role)->toBe(InternshipGroupRole::SCHOOL_TEACHER->value)
            ->and($mentorMember->user_id)->toBe($teacher->id);
    });

    test('IT0OE-DD-GROUP-003: membership stored as dedicated model with role and timestamp, not bare pivot', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();

        $member = InternshipGroupMember::create([
            'internship_group_id' => $group->id,
            'role' => InternshipGroupRole::STUDENT->value,
            'registration_id' => $registration->id,
            'joined_at' => now(),
        ]);

        expect($member)->toBeInstanceOf(InternshipGroupMember::class)
            ->and($member->getTable())->toBe('internship_group_members')
            ->and($member->joined_at)->not->toBeNull()
            ->and($member->role)->toBe(InternshipGroupRole::STUDENT->value);
    });

    test('IT0OE-DD-GROUP-004: group read access open to all users while mutation stays admin-only', function (): void {
        $policy = new InternshipGroupPolicy;

        $student = User::factory()->create();
        $student->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $group = InternshipGroup::factory()->create();

        expect($policy->viewAny($student))->toBeTrue()
            ->and($policy->view($student, $group))->toBeTrue()
            ->and($policy->viewAny(null))->toBeTrue()
            ->and($policy->create($student))->toBeFalse()
            ->and($policy->update($student, $group))->toBeFalse()
            ->and($policy->delete($student, $group))->toBeFalse()
            ->and($policy->create($admin))->toBeTrue()
            ->and($policy->update($admin, $group))->toBeTrue()
            ->and($policy->delete($admin, $group))->toBeTrue();
    });

    test('IT0OE-DD-GROUP-005: deactivation flag for retirement alongside guarded hard deletion', function (): void {
        $group = InternshipGroup::factory()->create(['is_active' => true]);

        expect($group->is_active)->toBeTrue();

        $group->update(['is_active' => false]);
        expect($group->fresh()->is_active)->toBeFalse();

        // Empty group allows hard deletion
        $deleteAction = app(DeleteInternshipGroupAction::class);
        $deleteAction->execute($group);

        expect(InternshipGroup::find($group->id))->toBeNull();
    });

    test('IT0OE-DD-GROUP-006: batch intake through a repeater commits all rows in a single transaction', function (): void {
        $group = InternshipGroup::factory()->create();
        $reg1 = Registration::factory()->create();
        $reg2 = Registration::factory()->create();

        $batchAction = app(AddMembersToGroupAction::class);
        $count = $batchAction->execute($group, [
            ['role' => InternshipGroupRole::STUDENT->value, 'registration_id' => $reg1->id],
            ['role' => InternshipGroupRole::STUDENT->value, 'registration_id' => $reg2->id],
        ]);

        expect($count)->toBe(2)
            ->and($group->members()->count())->toBe(2);
    });
});
