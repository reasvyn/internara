<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Mentor\Actions\CreateMentorAction;
use App\Domain\Mentor\Actions\CreateMentorProfileAction;
use App\Domain\Mentor\Actions\CreateSupervisionLogAction;
use App\Domain\Mentor\Actions\DeleteMentorAction;
use App\Domain\Mentor\Actions\ToggleMentorActiveAction;
use App\Domain\Mentor\Actions\UpdateMentorAction;
use App\Domain\Mentor\Actions\UpdateMentorProfileAction;
use App\Domain\Mentor\Actions\VerifySupervisionLogAction;
use App\Domain\Mentor\Enums\SupervisionLogStatus;
use App\Domain\Mentor\Enums\SupervisionType;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::SUPERVISOR->value, 'guard_name' => 'web']);
});

describe('CreateMentorAction', function () {
    it('creates a mentor with user and role', function () {
        $mentor = app(CreateMentorAction::class)->execute(
            userData: [
                'name' => 'Mentor User',
                'email' => 'mentor@example.com',
                'username' => 'mentoruser',
            ],
            mentorData: [
                'type' => Mentor::TYPE_SCHOOL_TEACHER,
            ],
        );

        expect($mentor)->toBeInstanceOf(Mentor::class)
            ->and($mentor->user->name)->toBe('Mentor User')
            ->and($mentor->user->hasRole(Role::TEACHER->value))->toBeTrue()
            ->and($mentor->type)->toBe(Mentor::TYPE_SCHOOL_TEACHER);
    });
});

describe('UpdateMentorAction', function () {
    it('updates a mentor', function () {
        $mentor = Mentor::factory()->schoolTeacher()->create();

        $updated = app(UpdateMentorAction::class)->execute($mentor, [
            'type' => Mentor::TYPE_INDUSTRY_SUPERVISOR,
        ], Role::SUPERVISOR->value);

        expect($updated->type)->toBe(Mentor::TYPE_INDUSTRY_SUPERVISOR)
            ->and($mentor->fresh()->user->hasRole(Role::SUPERVISOR->value))->toBeTrue();
    });
});

describe('DeleteMentorAction', function () {
    it('deletes a mentor and its user', function () {
        $mentor = Mentor::factory()->schoolTeacher()->create();
        $userId = $mentor->user_id;

        app(DeleteMentorAction::class)->execute($mentor);

        expect(Mentor::find($mentor->id))->toBeNull()
            ->and(User::find($userId))->toBeNull();
    });
});

describe('CreateMentorProfileAction', function () {
    it('creates a mentor profile from existing user', function () {
        $user = User::factory()->create();

        $mentor = app(CreateMentorProfileAction::class)->execute(
            userId: $user->id,
            type: Mentor::TYPE_INDUSTRY_SUPERVISOR,
        );

        expect($mentor)->toBeInstanceOf(Mentor::class)
            ->and($mentor->user_id)->toBe($user->id)
            ->and($mentor->type)->toBe(Mentor::TYPE_INDUSTRY_SUPERVISOR)
            ->and($user->fresh()->hasRole('supervisor'))->toBeTrue();
    });
});

describe('UpdateMentorProfileAction', function () {
    it('updates a mentor profile', function () {
        $mentor = Mentor::factory()->industrySupervisor()->create();

        $updated = app(UpdateMentorProfileAction::class)->execute(
            $mentor,
            type: Mentor::TYPE_SCHOOL_TEACHER,
        );

        expect($updated->type)->toBe(Mentor::TYPE_SCHOOL_TEACHER)
            ->and($updated->is_active)->toBeTrue();
    });
});

describe('ToggleMentorActiveAction', function () {
    it('toggles is_active flag', function () {
        $mentor = Mentor::factory()->create(['is_active' => true]);

        $toggled = app(ToggleMentorActiveAction::class)->execute($mentor);

        expect($toggled->is_active)->toBeFalse();

        $toggledAgain = app(ToggleMentorActiveAction::class)->execute($toggled);

        expect($toggledAgain->is_active)->toBeTrue();
    });
});

describe('CreateSupervisionLogAction', function () {
    it('creates a supervision log for a teacher mentor', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::TEACHER->value);
        $mentor = Mentor::factory()->schoolTeacher()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create();
        $registration->mentors()->attach($mentor->id, ['role' => Mentor::TYPE_SCHOOL_TEACHER]);

        $log = app(CreateSupervisionLogAction::class)->execute($user, $registration->id, [
            'topic' => 'Weekly guidance',
            'notes' => 'Discussed progress',
        ]);

        expect($log)->toBeInstanceOf(SupervisionLog::class)
            ->and($log->type->value)->toBe('guidance')
            ->and($log->is_verified)->toBeTrue()
            ->and($log->status)->toBe(SupervisionLogStatus::COMPLETED);
    });
});

describe('VerifySupervisionLogAction', function () {
    it('verifies a supervision log', function () {
        $verifier = User::factory()->create();
        $verifier->assignRole(Role::TEACHER->value);
        $this->actingAs($verifier);

        $logId = (string) Str::uuid();
        DB::table('supervision_logs')->insert([
            'id' => $logId,
            'registration_id' => Registration::factory()->create()->id,
            'supervisor_id' => User::factory()->create()->id,
            'type' => SupervisionType::MONITORING->value,
            'date' => now()->toDateString(),
            'topic' => 'Test topic',
            'notes' => 'Test notes',
            'is_verified' => false,
            'status' => SupervisionLogStatus::SUBMITTED->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $log = SupervisionLog::find($logId);

        $verified = app(VerifySupervisionLogAction::class)->execute($log, $verifier);

        expect($verified->is_verified)->toBeTrue()
            ->and($verified->status)->toBe(SupervisionLogStatus::VERIFIED);
    });
});
