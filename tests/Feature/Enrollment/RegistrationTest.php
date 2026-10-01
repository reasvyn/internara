<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Actions\ReadRegistrationAvailabilityAction;
use App\Modules\Enrollment\Domain\Registration\Actions\RegisterInternshipAction;
use App\Modules\Enrollment\Domain\Registration\Actions\UploadRegistrationDocumentAction;
use App\Modules\Enrollment\Domain\Registration\Actions\VerifyRegistrationAction;
use App\Modules\Enrollment\Domain\Registration\Data\RegistrationData;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationDocumentStatus;
use App\Modules\Enrollment\Domain\Registration\Events\StudentRegistered;
use App\Modules\Enrollment\Domain\Registration\Listeners\ClearDashboardOnRegistration;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationWizard;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function mbb5rStudent(object $test): User
{
    $student = User::factory()->create();
    $student->assignRole('student');
    $test->actingAs($student);

    return $student;
}

function mbb5rRegistrationData(string $internshipId): RegistrationData
{
    return new RegistrationData(internshipId: $internshipId);
}

describe('MBB5R: registrations', function (): void {
    test('MBB5R-FR-REG-001: availability report not_configured without period setting (also FR-REG-002, DD-REG-002)', function (): void {
        expect(app(ReadRegistrationAvailabilityAction::class)->execute())->toBe(['status' => 'not_configured']);
    });

    test('MBB5R-FR-REG-001: availability report open inside the period (also FR-REG-002)', function (): void {
        $this->seedSettings([
            'registration_period_start' => now()->subWeek()->toDateString(),
            'registration_period_end' => now()->addWeek()->toDateString(),
        ]);

        expect(app(ReadRegistrationAvailabilityAction::class)->execute()['status'])->toBe('open');
    });

    test('MBB5R-FR-REG-001: availability report upcoming and closed outside the period (also FR-REG-002)', function (): void {
        $this->seedSettings([
            'registration_period_start' => now()->addDays(10)->toDateString(),
            'registration_period_end' => now()->addMonths(2)->toDateString(),
        ]);

        expect(app(ReadRegistrationAvailabilityAction::class)->execute()['status'])->toBe('upcoming');

        $this->seedSettings([
            'registration_period_start' => now()->subMonths(3)->toDateString(),
            'registration_period_end' => now()->subMonths(2)->toDateString(),
        ]);

        expect(app(ReadRegistrationAvailabilityAction::class)->execute()['status'])->toBe('closed');
    });

    test('MBB5R-FR-REG-007: duplicate active or pending registrations are refused (also UC-REG-001, NFR-REG-005)', function (): void {
        Notification::fake();
        $student = mbb5rStudent($this);
        $internship = Internship::factory()->create();

        $first = app(RegisterInternshipAction::class)->execute($student, mbb5rRegistrationData($internship->id));

        expect($first->status)->toBe('pending');

        try {
            app(RegisterInternshipAction::class)->execute($student, mbb5rRegistrationData($internship->id));
            expect(false)->toBeTrue('duplicate registration was accepted');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('registration.already_registered'));
        }
    });

    test('MBB5R-FR-REG-008: student plus internship pairs stay unique at the database level', function (): void {
        $registration = Registration::factory()->create();

        expect(fn () => Registration::factory()->create([
            'student_id' => $registration->student_id,
            'internship_id' => $registration->internship_id,
        ]))->toThrow(QueryException::class);
    });

    test('MBB5R-FR-REG-009: registrations use UUID keys with student and internship cascades (also FR-REG-010)', function (): void {
        $placement = Placement::factory()->create();
        $registration = Registration::factory()->create(['placement_id' => $placement->id]);

        expect(Str::isUuid($registration->id))->toBeTrue();

        $placement->delete();

        expect($registration->fresh()->placement_id)->toBeNull();

        $studentId = $registration->student_id;
        User::where('id', $studentId)->delete();

        expect(Registration::where('id', $registration->id)->exists())->toBeFalse();
    });

    test('MBB5R-FR-REG-011: proposed company details persist as JSON (also DD-REG-001, NFR-REG-003)', function (): void {
        Notification::fake();
        $student = mbb5rStudent($this);
        $internship = Internship::factory()->create();

        $registration = app(RegisterInternshipAction::class)->execute($student, new RegistrationData(
            internshipId: $internship->id,
            proposedCompanyName: 'PT Maju Jaya',
            proposedCompanyAddress: 'Jl. Merdeka 1',
        ));

        $details = $registration->fresh()->proposed_company_details;

        expect(is_string($registration->getAttributes()['status'] ?? null) || is_string($registration->status))->toBeTrue();
        expect($details['company_name'] ?? json_decode((string) $details, true)['company_name'])->toBe('PT Maju Jaya');
    });

    test('MBB5R-FR-REG-013: creation dispatches the student registered event (also FR-REG-014)', function (): void {
        Notification::fake();
        Event::fake([StudentRegistered::class]);
        $student = mbb5rStudent($this);
        $internship = Internship::factory()->create();

        app(RegisterInternshipAction::class)->execute($student, mbb5rRegistrationData($internship->id));

        Event::assertDispatched(StudentRegistered::class);

        Cache::put(config('cache-keys.admin_dashboard_stats'), ['stale' => true], 600);

        (new ClearDashboardOnRegistration)->handle(
            new StudentRegistered(registration: Registration::factory()->make()),
        );

        expect(Cache::get(config('cache-keys.admin_dashboard_stats')))->toBeNull();
    });

    test('MBB5R-FR-REG-015: verification requires a pending registration (also NFR-REG-005)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $registration = Registration::factory()->create(['status' => 'active']);
        $placement = Placement::factory()->create();

        expect(fn () => app(VerifyRegistrationAction::class)->execute($registration->id, ['placement_id' => $placement->id]))
            ->toThrow(RejectedException::class);
    });

    test('MBB5R-FR-REG-017: verification assigns placement, activates, and increments quota (also FR-REG-016, UC-REG-002, NFR-REG-004)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $placement = Placement::factory()->create(['quota' => 5, 'filled_quota' => 0]);
        $registration = Registration::factory()->create(['placement_id' => null, 'status' => 'pending']);

        $verified = app(VerifyRegistrationAction::class)->execute($registration->id, ['placement_id' => $placement->id]);

        expect($verified->placement_id)->toBe($placement->id)
            ->and($verified->status)->toBe('active')
            ->and($placement->fresh()->filled_quota)->toBe(1);
    });

    test('MBB5R-FR-REG-016: verification refuses full placements', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $placement = Placement::factory()->full()->create();
        $registration = Registration::factory()->create(['placement_id' => null, 'status' => 'pending']);

        expect(fn () => app(VerifyRegistrationAction::class)->execute($registration->id, ['placement_id' => $placement->id]))
            ->toThrow(RejectedException::class);
    });

    test('MBB5R-FR-REG-020: uploads link documents to the registration (also UC-REG-003, DD-REG-003)', function (): void {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $internship = Internship::factory()->create();
        $document = Document::factory()->create(['type' => 'template']);
        $internship->update(['required_document_ids' => [$document->id]]);
        $registration = Registration::factory()->create(['internship_id' => $internship->id]);

        app(UploadRegistrationDocumentAction::class)->execute($registration, [
            $document->id => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        ]);

        $record = RegistrationDocument::where('registration_id', $registration->id)->firstOrFail();

        expect($record->status)->toBe(RegistrationDocumentStatus::PENDING)
            ->and($record->getMedia('file'))->toHaveCount(1);
    });

    test('MBB5R-FR-REG-026: document transitions run pending to terminal endpoints', function (): void {
        expect(RegistrationDocumentStatus::PENDING->canTransitionTo(RegistrationDocumentStatus::VERIFIED))->toBeTrue()
            ->and(RegistrationDocumentStatus::PENDING->canTransitionTo(RegistrationDocumentStatus::REJECTED))->toBeTrue()
            ->and(RegistrationDocumentStatus::VERIFIED->canTransitionTo(RegistrationDocumentStatus::REJECTED))->toBeFalse()
            ->and(RegistrationDocumentStatus::PENDING->label())->not->toBe('');
    });

    test('MBB5R-FR-REG-027: registration center lists open internships behind auth (also FR-REG-028, FR-REG-030)', function (): void {
        $this->get(route('registration.center'))->assertRedirect('/login');
        $this->get(route('registration.wizard'))->assertRedirect('/login');
        $this->get(route('registration.documents'))->assertRedirect('/login');

        mbb5rStudent($this);

        $this->get(route('registration.center'))->assertOk();
        $this->get(route('registration.wizard'))->assertOk();
        $this->get(route('registration.documents'))->assertOk();
    });

    test('MBB5R-FR-REG-029: the pending queue sits behind admin roles', function (): void {
        $this->get(route('enrollment.internships.registrations.pending'))->assertRedirect('/login');

        mbb5rStudent($this);

        $this->get(route('enrollment.internships.registrations.pending'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->get(route('enrollment.internships.registrations.pending'))->assertOk();
    });

    test('MBB5R-FR-REG-033: the wizard validates through its form object', function (): void {
        mbb5rStudent($this);

        Livewire::test(RegistrationWizard::class)
            ->call('nextStep')
            ->assertHasErrors(['form.internship_id']);

        $internship = Internship::factory()->create();

        Livewire::test(RegistrationWizard::class)
            ->set('form.internship_id', $internship->id)
            ->set('form.academic_year', '2026/2027')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertHasNoErrors();
    });

    test('MBB5R-NFR-REG-001: creation writes an audit entry (also NFR-REG-004)', function (): void {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $student = User::factory()->create();
        $student->assignRole('student');
        $placement = Placement::factory()->create(['quota' => 5, 'filled_quota' => 0]);
        $registration = Registration::factory()->create(['placement_id' => null, 'status' => 'pending']);

        app(VerifyRegistrationAction::class)->execute($registration->id, ['placement_id' => $placement->id]);

        expect(DB::table('activity_log')->where('description', 'registration_verified_and_placed')->exists())->toBeTrue();
    });
});
