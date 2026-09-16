<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\ApplyAccountAction;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\ApproveAccountApplicationAction;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\RejectAccountApplicationAction;
use App\Modules\Enrollment\Domain\AccountApplication\Data\RejectAccountApplicationData;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Events\AccountApplicationApproved;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\SysAdmin\Livewire\ApplicationReview;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function aaPipeInternship(): Internship
{
    return Internship::factory()->create(['status' => 'published']);
}

function aaPipePlacement(Internship $internship): Placement
{
    return Placement::factory()->create([
        'internship_id' => $internship->id,
        'quota' => 5,
        'filled_quota' => 0,
    ]);
}

/** @return array<string, mixed> */
function aaPipePayload(string $email, Internship $internship, ?Placement $placement = null, array $extra = []): array
{
    return array_merge([
        'name' => 'Pipeline Kid',
        'email' => $email,
        'phone' => '081234567890',
        'address' => 'Jl. Pipeline 1',
        'national_id_number' => 'NID-1',
        'student_id_number' => 'NIS-1001',
        'department_id' => Department::factory()->create()->id,
        'class_name' => 'XII-RPL-1',
        'entry_year' => 2024,
        'academic_year' => '2025/2026',
        'form_data' => [
            'phone' => '081234567890',
            'address' => 'Jl. Pipeline 1',
            'internship_id' => $internship->id,
            'placement_id' => $placement?->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ],
    ], $extra);
}

function aaPipeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

describe('920SO: submission guards', function (): void {
    test('920SO-FR-APPLY-005: second apply refused while a pending row lives', function (): void {
        app()->setLocale('en');
        $internship = aaPipeInternship();

        app(ApplyAccountAction::class)->execute(aaPipePayload('pipe-dup@example.com', $internship));

        try {
            app(ApplyAccountAction::class)->execute(aaPipePayload('pipe-dup@example.com', $internship));
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('registration.application_exists'));
        }

        expect(AccountApplication::where('email', 'pipe-dup@example.com')->count())->toBe(1)
            ->and(AccountApplication::where('email', 'pipe-dup@example.com')->first()->status)
            ->toBe(AccountApplicationStatus::PENDING);
    });

    test('920SO-FR-APPLY-005: approved email stays blocked', function (): void {
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-approved@example.com', $internship, $placement));
        app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

        expect(fn () => app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-approved@example.com', $internship, $placement)))
            ->toThrow(RejectedException::class);

        expect(AccountApplication::where('email', 'pipe-approved@example.com')->count())->toBe(1);
    });

    test('920SO-FR-APPLY-006: rejected email wakes the same row with fresh data', function (): void {
        $internship = aaPipeInternship();

        $original = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-return@example.com', $internship));

        $admin = aaPipeAdmin();
        test()->actingAs($admin);
        app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData(
            applicationId: $original->id,
            reason: 'Proposed company has no mentor.',
        ));

        $revived = app(ApplyAccountAction::class)->execute(
            aaPipePayload('pipe-return@example.com', $internship, null, ['name' => 'Pipeline Kid Revised'])
        );

        expect($revived->id)->toBe($original->id)
            ->and($revived->status)->toBe(AccountApplicationStatus::PENDING)
            ->and($revived->name)->toBe('Pipeline Kid Revised')
            ->and(AccountApplication::where('email', 'pipe-return@example.com')->count())->toBe(1);
    });

    test('920SO-UC-APPLY-003: rejected guest re-applies and the same record returns to pending', function (): void {
        $internship = aaPipeInternship();

        $original = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-retry@example.com', $internship));

        $admin = aaPipeAdmin();
        test()->actingAs($admin);
        app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData(
            applicationId: $original->id,
            reason: 'Incomplete documents.',
        ));
        expect($original->refresh()->status)->toBe(AccountApplicationStatus::REJECTED);

        $again = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-retry@example.com', $internship));

        expect(AccountApplication::where('email', 'pipe-retry@example.com')->count())->toBe(1)
            ->and($again->id)->toBe($original->id)
            ->and($again->status)->toBe(AccountApplicationStatus::PENDING);
    });

    test('920SO-FR-APPLY-007: failed re-activation keeps the old rejected row intact', function (): void {
        $internship = aaPipeInternship();

        $original = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-tx@example.com', $internship));

        $admin = aaPipeAdmin();
        test()->actingAs($admin);
        app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData(
            applicationId: $original->id,
            reason: 'Try again.',
        ));

        expect(fn () => app(ApplyAccountAction::class)->execute(
            aaPipePayload('pipe-tx@example.com', $internship, null, ['department_id' => null])
        ))->toThrow(QueryException::class);

        expect($original->refresh()->status)->toBe(AccountApplicationStatus::REJECTED)
            ->and($original->refresh()->name)->toBe('Pipeline Kid')
            ->and(AccountApplication::where('email', 'pipe-tx@example.com')->count())->toBe(1);
    });

    test('920SO-FR-APPLY-007: failed create leaves no row behind', function (): void {
        $internship = aaPipeInternship();

        expect(fn () => app(ApplyAccountAction::class)->execute(
            aaPipePayload('pipe-norow@example.com', $internship, null, ['department_id' => null])
        ))->toThrow(QueryException::class);

        expect(AccountApplication::where('email', 'pipe-norow@example.com')->count())->toBe(0);
    });
});

describe('920SO: approval pipeline', function (): void {
    test('920SO-FR-APPLY-009: approval mints user, profile and active registration in one go', function (): void {
        Event::fake([AccountApplicationApproved::class]);
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-mint@example.com', $internship, $placement));

        $registration = app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

        $user = User::where('email', 'pipe-mint@example.com')->firstOrFail();

        expect($application->refresh()->status)->toBe(AccountApplicationStatus::APPROVED)
            ->and($user->hasRole('student'))->toBeTrue()
            ->and($user->setup_required)->toBeTrue()
            ->and(Profile::where('user_id', $user->id)->firstOrFail()->phone)->toBe('081234567890')
            ->and(Profile::where('user_id', $user->id)->first()->address)->toBe('Jl. Pipeline 1')
            ->and(Profile::where('user_id', $user->id)->first()->department_id)->toBe($application->department_id)
            ->and($registration->student_id)->toBe($user->id)
            ->and($registration->internship_id)->toBe($internship->id)
            ->and($registration->placement_id)->toBe($placement->id)
            ->and($registration->refresh()->status)->toBe('active');

        Event::assertDispatched(AccountApplicationApproved::class);
    });

    test('920SO-FR-APPLY-010: approval refuses a row that is no longer pending', function (): void {
        app()->setLocale('en');
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-race@example.com', $internship, $placement));
        app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

        try {
            app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('registration.application_not_pending'));
        }

        expect(User::where('email', 'pipe-race@example.com')->count())->toBe(1)
            ->and(Registration::where('internship_id', $internship->id)->count())->toBe(1);
    });

    test('920SO-FR-APPLY-010: approval refuses a rejected row', function (): void {
        $internship = aaPipeInternship();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-rejfirst@example.com', $internship));

        $admin = aaPipeAdmin();
        test()->actingAs($admin);
        app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData(
            applicationId: $application->id,
            reason: 'No seats.',
        ));

        expect(fn () => app(ApproveAccountApplicationAction::class)->execute($application->id, $admin))
            ->toThrow(RejectedException::class);

        expect(User::where('email', 'pipe-rejfirst@example.com')->count())->toBe(0);
    });

    test('920SO-FR-APPLY-011: approval without an internship refuses with guidance', function (): void {
        app()->setLocale('en');
        $internship = aaPipeInternship();
        $admin = aaPipeAdmin();

        $payload = aaPipePayload('pipe-nointern@example.com', $internship);
        $payload['form_data'] = ['phone' => '0812', 'address' => 'Jl. Nowhere'];
        $application = app(ApplyAccountAction::class)->execute($payload);

        try {
            app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('registration.validation.missing_internship'));
        }

        expect($application->refresh()->status)->toBe(AccountApplicationStatus::PENDING)
            ->and(User::where('email', 'pipe-nointern@example.com')->count())->toBe(0);
    });

    test('920SO-NFR-APPLY-004: late pipeline failure leaves zero orphaned records', function (): void {
        $internship = aaPipeInternship();
        $admin = aaPipeAdmin();

        $payload = aaPipePayload('pipe-orphan@example.com', $internship);
        $payload['form_data']['placement_id'] = '00000000-0000-0000-0000-000000000000';
        $application = app(ApplyAccountAction::class)->execute($payload);

        $usersBefore = User::count();
        $profilesBefore = Profile::count();
        $registrationsBefore = Registration::count();

        expect(fn () => app(ApproveAccountApplicationAction::class)->execute($application->id, $admin))
            ->toThrow(QueryException::class);

        expect(User::count())->toBe($usersBefore)
            ->and(Profile::count())->toBe($profilesBefore)
            ->and(Registration::count())->toBe($registrationsBefore)
            ->and($application->refresh()->status)->toBe(AccountApplicationStatus::PENDING);
    });

    test('920SO-FR-APPLY-014: approval stamps decider and timestamp', function (): void {
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-stamp@example.com', $internship, $placement));

        expect($application->processed_by)->toBeNull();

        app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

        expect($application->refresh()->processed_by)->toBe($admin->id)
            ->and($application->refresh()->processed_at)->not->toBeNull();
    });

    test('920SO-NFR-APPLY-003: provisioned credentials are minted random secrets', function (): void {
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();

        $first = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-cred1@example.com', $internship, $placement));
        $second = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-cred2@example.com', $internship, $placement));

        app(ApproveAccountApplicationAction::class)->execute($first->id, $admin);
        app(ApproveAccountApplicationAction::class)->execute($second->id, $admin);

        $hashOne = User::where('email', 'pipe-cred1@example.com')->firstOrFail()->password;
        $hashTwo = User::where('email', 'pipe-cred2@example.com')->firstOrFail()->password;

        expect(Hash::info($hashOne)['algoName'])->toBe('bcrypt')
            ->and($hashOne)->not->toBe($hashTwo)
            ->and(Hash::check('Pipeline Kid', $hashOne))->toBeFalse()
            ->and(User::where('email', 'pipe-cred1@example.com')->firstOrFail()->setup_required)->toBeTrue()
            ->and(User::where('email', 'pipe-cred2@example.com')->firstOrFail()->setup_required)->toBeTrue();
    });
});

describe('920SO: rejection path and review queue', function (): void {
    test('920SO-FR-APPLY-012: rejection stores the reason and closes the row', function (): void {
        $internship = aaPipeInternship();
        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-reject@example.com', $internship));

        $admin = aaPipeAdmin();
        test()->actingAs($admin);
        app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData(
            applicationId: $application->id,
            reason: 'Quota for the chosen placement is exhausted.',
        ));

        expect($application->refresh()->status)->toBe(AccountApplicationStatus::REJECTED)
            ->and($application->refresh()->rejection_reason)
            ->toBe('Quota for the chosen placement is exhausted.')
            ->and($application->refresh()->processed_by)->toBe($admin->id)
            ->and($application->refresh()->processed_at)->not->toBeNull();
    });

    test('920SO-FR-APPLY-013: rejection refuses a decided row', function (): void {
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-decided@example.com', $internship, $placement));
        app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

        expect(fn () => app(RejectAccountApplicationAction::class)->execute(
            new RejectAccountApplicationData(applicationId: $application->id, reason: 'Too late.')
        ))->toThrow(RejectedException::class);

        expect($application->refresh()->status)->toBe(AccountApplicationStatus::APPROVED);
    });

    test('920SO-UC-APPLY-002: admin clears the queue through approve and reject with attribution', function (): void {
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);

        $toApprove = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-q1@example.com', $internship, $placement));
        $toReject = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-q2@example.com', $internship, $placement));

        $admin = aaPipeAdmin();
        test()->actingAs($admin);

        Livewire::test(ApplicationReview::class)->call('approve', $toApprove->id);

        Livewire::test(ApplicationReview::class)
            ->set('rejectId', $toReject->id)
            ->set('rejectionReason', 'Documents incomplete, please re-apply.')
            ->call('reject');

        expect($toApprove->refresh()->status)->toBe(AccountApplicationStatus::APPROVED)
            ->and($toApprove->refresh()->processed_by)->toBe($admin->id)
            ->and(User::where('email', 'pipe-q1@example.com')->exists())->toBeTrue()
            ->and(Registration::where('internship_id', $internship->id)->whereHas('student', fn ($q) => $q->where('email', 'pipe-q1@example.com'))->exists())->toBeTrue()
            ->and($toReject->refresh()->status)->toBe(AccountApplicationStatus::REJECTED)
            ->and($toReject->refresh()->rejection_reason)->toBe('Documents incomplete, please re-apply.')
            ->and($toReject->refresh()->processed_by)->toBe($admin->id);
    });

    test('920SO-FR-APPLY-018: students cannot decide applications through the review screen', function (): void {
        $internship = aaPipeInternship();
        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-forbid@example.com', $internship));

        $student = User::factory()->create();
        $student->assignRole('student');
        test()->actingAs($student);

        Livewire::test(ApplicationReview::class)->call('approve', $application->id)->assertForbidden();

        expect($application->refresh()->status)->toBe(AccountApplicationStatus::PENDING)
            ->and(User::where('email', 'pipe-forbid@example.com')->count())->toBe(0);
    });
});

describe('920SO: model shape and audit trail', function (): void {
    test('920SO-FR-APPLY-003: flexible fields ride in the JSON column', function (): void {
        $internship = aaPipeInternship();

        $payload = aaPipePayload('pipe-json@example.com', $internship);
        $payload['form_data']['hobby'] = 'Robotics club';
        $payload['form_data']['guardian'] = ['name' => 'Ibu Pipeline', 'phone' => '080000'];
        $application = app(ApplyAccountAction::class)->execute($payload);

        $fresh = $application->fresh();

        expect($fresh->form_data)->toBeArray()
            ->and($fresh->form_data['hobby'])->toBe('Robotics club')
            ->and($fresh->form_data['guardian']['name'])->toBe('Ibu Pipeline')
            ->and($fresh->form_data['internship_id'])->toBe($internship->id);
    });

    test('920SO-FR-APPLY-004: application resolves department and decider', function (): void {
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-rel@example.com', $internship, $placement));

        expect($application->department)->toBeInstanceOf(Department::class)
            ->and($application->department->id)->toBe($application->department_id)
            ->and($application->processor)->toBeNull();

        app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

        expect($application->refresh()->processor)->toBeInstanceOf(User::class)
            ->and($application->refresh()->processor->id)->toBe($admin->id);
    });

    test('920SO-FR-APPLY-017: approval writes a masked activity entry naming the actor', function (): void {
        $internship = aaPipeInternship();
        $placement = aaPipePlacement($internship);
        $admin = aaPipeAdmin();
        test()->actingAs($admin);

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-log1@example.com', $internship, $placement));
        app(ApproveAccountApplicationAction::class)->execute($application->id, $admin);

        $row = DB::table('activity_log')
            ->where('description', 'account_application_approved')
            ->where('subject_id', $application->id)
            ->first();

        expect($row)->not->toBeNull()
            ->and((string) $row->causer_id)->toBe((string) $admin->id)
            ->and($row->log_name)->toBe('Enrollment')
            ->and(str_contains((string) $row->properties, 'pipe-log1@example.com'))->toBeFalse()
            ->and(str_contains((string) $row->properties, '081234567890'))->toBeFalse();
    });

    test('920SO-FR-APPLY-017: rejection writes an auditable entry carrying the reason', function (): void {
        $internship = aaPipeInternship();
        $admin = aaPipeAdmin();
        test()->actingAs($admin);

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-log2@example.com', $internship));

        app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData(
            applicationId: $application->id,
            reason: 'Missing guardian contact.',
        ));

        $row = DB::table('activity_log')
            ->where('description', 'account_application_rejected')
            ->where('subject_id', $application->id)
            ->first();

        expect($row)->not->toBeNull()
            ->and((string) $row->causer_id)->toBe((string) $admin->id)
            ->and(str_contains((string) $row->properties, 'Missing guardian contact.'))->toBeTrue();
    });

    test('920SO-NFR-APPLY-005: rejection reason outlives re-activation in the log', function (): void {
        $internship = aaPipeInternship();

        $application = app(ApplyAccountAction::class)
            ->execute(aaPipePayload('pipe-history@example.com', $internship));

        $admin = aaPipeAdmin();
        test()->actingAs($admin);
        app(RejectAccountApplicationAction::class)->execute(new RejectAccountApplicationData(
            applicationId: $application->id,
            reason: 'First attempt lacked documents.',
        ));

        app(ApplyAccountAction::class)->execute(aaPipePayload('pipe-history@example.com', $internship));

        $row = DB::table('activity_log')
            ->where('description', 'account_application_rejected')
            ->where('subject_id', $application->id)
            ->first();

        expect($row)->not->toBeNull()
            ->and(str_contains((string) $row->properties, 'First attempt lacked documents.'))->toBeTrue()
            ->and($application->refresh()->status)->toBe(AccountApplicationStatus::PENDING);
    });
});

describe('920SO: guest entry point', function (): void {
    test('920SO-FR-APPLY-019: apply page is public but bounces signed-in users', function (): void {
        test()->get('/apply')->assertOk();

        $student = User::factory()->create();
        $student->assignRole('student');

        test()->actingAs($student)->get('/apply')->assertRedirect('/dashboard');
    });
});
