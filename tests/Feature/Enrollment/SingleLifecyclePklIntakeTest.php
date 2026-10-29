<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Actions\ConfirmPlacementPlottingAction;
use App\Modules\Enrollment\Domain\Registration\Actions\ProvisionCohortRegistrationAction;
use App\Modules\Enrollment\Domain\Registration\Actions\RegisterInternshipAction;
use App\Modules\Enrollment\Domain\Registration\Actions\SubmitPlacementPreferenceAction;
use App\Modules\Enrollment\Domain\Registration\Data\ConfirmPlacementData;
use App\Modules\Enrollment\Domain\Registration\Data\PlacementPreferenceData;
use App\Modules\Enrollment\Domain\Registration\Data\RegistrationData;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;
use App\Modules\Enrollment\Domain\Registration\Events\PlacementConfirmed;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Policies\RegistrationPolicy;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

function spklStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

function spklAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

describe('1SPKL: Single Lifecycle PKL Intake', function (): void {
    test('1SPKL-FR-SPKL-001: ProvisionCohortRegistrationAction provisions preparation records for eligible students', function (): void {
        $internship = Internship::factory()->create();
        $studentA = spklStudent();
        $studentB = spklStudent();

        $action = app(ProvisionCohortRegistrationAction::class);
        $provisioned = $action->execute($internship, [$studentA, $studentB]);

        expect($provisioned)->toHaveCount(2)
            ->and($provisioned->first()->status)->toBe(RegistrationStatus::PREPARATION->value)
            ->and($provisioned->first()->student_id)->toBe($studentA->id)
            ->and($provisioned->last()->student_id)->toBe($studentB->id);

        $secondRun = $action->execute($internship, [$studentA, $studentB]);
        expect($secondRun)->toBeEmpty();
    });

    test('1SPKL-FR-SPKL-003: registration refuses duplicate active or pending registrations for the same student', function (): void {
        $internship1 = Internship::factory()->create();
        $internship2 = Internship::factory()->create();
        $student = spklStudent();

        Registration::create([
            'student_id' => $student->id,
            'internship_id' => $internship1->id,
            'status' => RegistrationStatus::ACTIVE->value,
        ]);

        $action = app(RegisterInternshipAction::class);

        expect(fn () => $action->execute($student, new RegistrationData(internshipId: $internship2->id)))
            ->toThrow(RejectedException::class);
    });

    test('1SPKL-FR-SPKL-005: SubmitPlacementPreferenceAction accepts available partner placement slot', function (): void {
        $internship = Internship::factory()->create();
        $company = Company::factory()->create();
        $placement = Placement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company->id,
            'quota' => 5,
            'filled_quota' => 0,
        ]);

        $student = spklStudent();
        $registration = Registration::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => RegistrationStatus::PREPARATION->value,
        ]);

        $action = app(SubmitPlacementPreferenceAction::class);
        $updated = $action->execute($registration, new PlacementPreferenceData(
            placementId: $placement->id,
        ));

        expect($updated->placement_id)->toBe($placement->id)
            ->and($updated->status)->toBe(RegistrationStatus::PENDING->value)
            ->and($updated->proposed_company_details)->toBeNull();
    });

    test('1SPKL-FR-SPKL-005: SubmitPlacementPreferenceAction accepts self-proposed company details', function (): void {
        $internship = Internship::factory()->create();
        $student = spklStudent();
        $registration = Registration::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => RegistrationStatus::PREPARATION->value,
        ]);

        $action = app(SubmitPlacementPreferenceAction::class);
        $updated = $action->execute($registration, new PlacementPreferenceData(
            proposedCompanyName: 'PT Solusi Teknologi Mandiri',
            proposedCompanyAddress: 'Jl. Industri No. 45, Jakarta',
        ));

        expect($updated->placement_id)->toBeNull()
            ->and($updated->status)->toBe(RegistrationStatus::PENDING->value)
            ->and($updated->proposed_company_details)->toMatchArray([
                'company_name' => 'PT Solusi Teknologi Mandiri',
                'company_address' => 'Jl. Industri No. 45, Jakarta',
            ]);
    });

    test('1SPKL-FR-SPKL-006: SubmitPlacementPreferenceAction refuses full placement capacity', function (): void {
        $internship = Internship::factory()->create();
        $company = Company::factory()->create();
        $placement = Placement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company->id,
            'quota' => 2,
            'filled_quota' => 2,
        ]);

        $student = spklStudent();
        $registration = Registration::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => RegistrationStatus::PREPARATION->value,
        ]);

        $action = app(SubmitPlacementPreferenceAction::class);

        expect(fn () => $action->execute($registration, new PlacementPreferenceData(placementId: $placement->id)))
            ->toThrow(RejectedException::class);
    });

    test('1SPKL-FR-SPKL-008: ConfirmPlacementPlottingAction confirms placement and assigns supervisor atomically', function (): void {
        Event::fake([PlacementConfirmed::class]);

        $internship = Internship::factory()->create([
            'start_date' => now()->addDays(14)->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
        ]);
        $company = Company::factory()->create();
        $placement = Placement::factory()->create([
            'internship_id' => $internship->id,
            'company_id' => $company->id,
            'quota' => 3,
            'filled_quota' => 0,
        ]);

        $student = spklStudent();
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $registration = Registration::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => RegistrationStatus::PENDING->value,
        ]);

        $action = app(ConfirmPlacementPlottingAction::class);
        $confirmed = $action->execute(new ConfirmPlacementData(
            registrationId: $registration->id,
            placementId: $placement->id,
            mentorIds: [$supervisor->id],
        ));

        expect($confirmed->status)->toBe(RegistrationStatus::CONFIRMED->value)
            ->and($confirmed->placement_id)->toBe($placement->id)
            ->and($placement->fresh()->filled_quota)->toBe(1)
            ->and($confirmed->mentors)->toHaveCount(1)
            ->and($confirmed->mentors->first()->id)->toBe($supervisor->id);

        Event::assertDispatched(PlacementConfirmed::class, function (PlacementConfirmed $event) use ($registration) {
            return $event->registration->id === $registration->id && $event->dispatchLetterRef !== null;
        });
    });

    test('1SPKL-FR-SPKL-012: RegistrationPolicy restricts student modification once confirmed', function (): void {
        $student = spklStudent();
        $policy = new RegistrationPolicy;

        $prepReg = Registration::create([
            'student_id' => $student->id,
            'internship_id' => Internship::factory()->create()->id,
            'status' => RegistrationStatus::PREPARATION->value,
        ]);

        $pendingReg = Registration::create([
            'student_id' => $student->id,
            'internship_id' => Internship::factory()->create()->id,
            'status' => RegistrationStatus::PENDING->value,
        ]);

        $confirmedReg = Registration::create([
            'student_id' => $student->id,
            'internship_id' => Internship::factory()->create()->id,
            'status' => RegistrationStatus::CONFIRMED->value,
        ]);

        $activeReg = Registration::create([
            'student_id' => $student->id,
            'internship_id' => Internship::factory()->create()->id,
            'status' => RegistrationStatus::ACTIVE->value,
        ]);

        expect($policy->update($student, $prepReg))->toBeTrue()
            ->and($policy->update($student, $pendingReg))->toBeTrue()
            ->and($policy->update($student, $confirmedReg))->toBeFalse()
            ->and($policy->update($student, $activeReg))->toBeFalse();
    });
});
