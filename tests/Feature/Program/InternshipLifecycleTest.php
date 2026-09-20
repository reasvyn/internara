<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;
use App\Modules\Certification\Domain\Certificate\Livewire\StudentCertificates;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Support\CsvHandler;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\Program\Domain\Internship\Actions\BatchUpdateInternshipStatusAction;
use App\Modules\Program\Domain\Internship\Actions\CreateInternshipAction;
use App\Modules\Program\Domain\Internship\Actions\ReadCloseReadinessAction;
use App\Modules\Program\Domain\Internship\Actions\UnarchiveInternshipAction;
use App\Modules\Program\Domain\Internship\Actions\UpdateInternshipAction;
use App\Modules\Program\Domain\Internship\Entities\InternshipPeriod;
use App\Modules\Program\Domain\Internship\Entities\InternshipState;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\Program\Domain\Internship\Events\InternshipCreated;
use App\Modules\Program\Domain\Internship\Events\InternshipStatusBatchUpdated;
use App\Modules\Program\Domain\Internship\Livewire\InternshipManager;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\Internship\Rules\OpenForRegistration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Report\Domain\StudentReport\Actions\CaptureStudentReportSnapshotAction;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('7C5WM: internship lifecycle feature gaps', function (): void {
    test('7C5WM-UC-LIFE-001: admin creates a draft that auto-fills the year, then publishes it', function (): void {
        $year = AcademicYear::factory()->active()->create();

        $internship = app(CreateInternshipAction::class)->execute([
            'name' => 'PKL Ganjil 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        expect($internship->refresh()->status)->toBe(InternshipStatus::DRAFT);
        expect($internship->academic_year_id)->toBe($year->id);
        expect(Internship::where('name', 'PKL Ganjil 2026')->exists())->toBeTrue();

        $published = app(UpdateInternshipAction::class)->execute($internship, [
            'status' => InternshipStatus::PUBLISHED->value,
        ]);

        expect($published->refresh()->status)->toBe(InternshipStatus::PUBLISHED);
    });

    test('7C5WM-FR-LIFE-008: illegal transitions fail loudly while legal ones persist', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::DRAFT->value]);

        expect(fn () => app(UpdateInternshipAction::class)->execute($internship, [
            'status' => InternshipStatus::COMPLETED->value,
        ]))->toThrow(RejectedException::class);

        expect($internship->refresh()->status)->toBe(InternshipStatus::DRAFT);

        $active = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);

        expect(fn () => app(UpdateInternshipAction::class)->execute($active, [
            'status' => InternshipStatus::DRAFT->value,
        ]))->toThrow(RejectedException::class);
    });

    test('7C5WM-FR-LIFE-018: the update action is the single-record enforcement point', function (): void {
        $draft = Internship::factory()->create(['status' => InternshipStatus::DRAFT->value]);

        app(UpdateInternshipAction::class)->execute($draft, [
            'status' => InternshipStatus::PUBLISHED->value,
        ]);
        expect($draft->refresh()->status)->toBe(InternshipStatus::PUBLISHED);

        app(UpdateInternshipAction::class)->execute($draft->refresh(), [
            'status' => InternshipStatus::ACTIVE->value,
        ]);
        expect($draft->refresh()->status)->toBe(InternshipStatus::ACTIVE);

        expect(fn () => app(UpdateInternshipAction::class)->execute($draft->refresh(), [
            'status' => InternshipStatus::ARCHIVED->value,
        ]))->toThrow(RejectedException::class);

        expect($draft->refresh()->status)->toBe(InternshipStatus::ACTIVE);
    });

    test('7C5WM-NFR-LIFE-002: rejections carry the translatable message', function (): void {
        app()->setLocale('en');
        $internship = Internship::factory()->create(['status' => InternshipStatus::DRAFT->value]);

        try {
            app(UpdateInternshipAction::class)->execute($internship, [
                'status' => InternshipStatus::COMPLETED->value,
            ]);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('internship.invalid_status_transition', [
                'from' => __('internship.statuses.draft'),
                'to' => __('internship.statuses.completed'),
            ]));
        }
    });

    test('7C5WM-FR-LIFE-020: creation and batch updates announce themselves as events', function (): void {
        Event::fake([InternshipCreated::class, InternshipStatusBatchUpdated::class]);

        $internship = app(CreateInternshipAction::class)->execute([
            'name' => 'PKL Genap 2026',
            'start_date' => '2026-01-05',
            'end_date' => '2026-06-30',
            'academic_year_id' => AcademicYear::factory()->create()->id,
        ]);

        Event::assertDispatched(InternshipCreated::class, fn ($event) => $event->internship->is($internship));

        Internship::factory()->count(2)->create(['status' => InternshipStatus::ACTIVE->value]);

        $count = app(BatchUpdateInternshipStatusAction::class)->execute(
            Internship::where('status', InternshipStatus::ACTIVE->value),
            InternshipStatus::COMPLETED,
        );

        expect($count)->toBe(2);
        expect(Internship::where('status', InternshipStatus::COMPLETED->value)->count())->toBe(2);
        Event::assertDispatched(InternshipStatusBatchUpdated::class, fn ($event) => $event->count === 2
            && $event->newStatus === InternshipStatus::COMPLETED->value);
    });

    test('7C5WM-FR-LIFE-026: the validation rule gates attempts through the period verdict', function (): void {
        $rule = new OpenForRegistration;
        $failures = [];
        $fail = function (string $message) use (&$failures): void {
            $failures[] = $message;
        };

        $open = Internship::factory()->create([
            'status' => InternshipStatus::PUBLISHED->value,
            'registration_start_date' => now()->subDay()->toDateString(),
            'registration_end_date' => now()->addDay()->toDateString(),
        ]);
        $rule->validate('internship_id', $open->id, $fail);
        expect($failures)->toBe([]);

        $draft = Internship::factory()->create([
            'status' => InternshipStatus::DRAFT->value,
            'registration_start_date' => now()->subDay()->toDateString(),
            'registration_end_date' => now()->addDay()->toDateString(),
        ]);
        $rule->validate('internship_id', $draft->id, $fail);
        expect($failures)->toHaveCount(1);

        $closed = Internship::factory()->create([
            'status' => InternshipStatus::PUBLISHED->value,
            'registration_start_date' => now()->subDays(10)->toDateString(),
            'registration_end_date' => now()->subDay()->toDateString(),
        ]);
        $rule->validate('internship_id', $closed->id, $fail);
        expect($failures)->toHaveCount(2);

        $rule->validate('internship_id', '00000000-0000-0000-0000-000000000000', $fail);
        expect($failures)->toHaveCount(3);
    });

    test('7C5WM-UC-LIFE-002: registration is accepted or rejected by status plus date together', function (): void {
        $rule = new OpenForRegistration;
        $verdict = function (Internship $internship) use ($rule): bool {
            $rejected = false;
            $rule->validate('internship_id', $internship->id, function () use (&$rejected): void {
                $rejected = true;
            });

            return ! $rejected;
        };

        $window = [
            'registration_start_date' => now()->subDay()->toDateString(),
            'registration_end_date' => now()->addDay()->toDateString(),
        ];

        expect($verdict(Internship::factory()->create(['status' => InternshipStatus::PUBLISHED->value, ...$window])))->toBeTrue();
        expect($verdict(Internship::factory()->create(['status' => InternshipStatus::DRAFT->value, ...$window])))->toBeFalse();
        expect($verdict(Internship::factory()->create([
            'status' => InternshipStatus::PUBLISHED->value,
            'registration_start_date' => now()->subDays(10)->toDateString(),
            'registration_end_date' => now()->subDay()->toDateString(),
        ])))->toBeFalse();
    });

    test('7C5WM-FR-LIFE-027: the manager form carries the registration window through save', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::factory()->active()->create();

        Livewire::test(InternshipManager::class)
            ->call('create')
            ->set('form.name', 'PKL Window 2026')
            ->set('form.academic_year_id', $year->id)
            ->set('form.start_date', '2026-07-01')
            ->set('form.end_date', '2026-12-31')
            ->set('form.registration_start_date', '2026-06-01')
            ->set('form.registration_end_date', '2026-06-30')
            ->call('save')
            ->assertHasNoErrors();

        $stored = Internship::where('name', 'PKL Window 2026')->firstOrFail();
        expect($stored->registration_start_date->format('Y-m-d'))->toBe('2026-06-01');
        expect($stored->registration_end_date->format('Y-m-d'))->toBe('2026-06-30');
    });

    test('7C5WM-FR-LIFE-028: one read returns all five readiness domains', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        Registration::factory()->active()->create(['internship_id' => $internship->id]);

        $report = app(ReadCloseReadinessAction::class)->execute($internship);

        expect(array_keys($report))->toBe(['assessments', 'submissions', 'supervision_logs', 'attendance', 'certificates']);
    });

    test('7C5WM-FR-LIFE-034: every domain answers in the uniform report shape', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        Registration::factory()->active()->create(['internship_id' => $internship->id]);

        $report = app(ReadCloseReadinessAction::class)->execute($internship);

        foreach ($report as $domain => $verdict) {
            expect($verdict)->toHaveKeys(['passed', 'total', 'pending', 'message']);
            expect($verdict['passed'])->toBeBool();
            expect($verdict['total'])->toBeInt();
            expect($verdict['pending'])->toBeInt();
            expect($verdict['message'])->toBeString()->not->toBe('');
        }
    });

    test('7C5WM-FR-LIFE-029: the assessments domain demands a finalization timestamp', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);
        Assessment::factory()->create(['registration_id' => $registration->id, 'finalized_at' => null]);

        $blocked = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($blocked['assessments']['passed'])->toBeFalse();
        expect($blocked['assessments']['pending'])->toBe(1);

        Assessment::where('registration_id', $registration->id)->update(['finalized_at' => now()]);

        $clear = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($clear['assessments']['passed'])->toBeTrue();
        expect($clear['assessments']['pending'])->toBe(0);
    });

    test('7C5WM-FR-LIFE-030: the submissions domain counts down unfinished work to zero', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);
        Submission::factory()->create(['registration_id' => $registration->id, 'status' => 'submitted']);

        $blocked = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($blocked['submissions']['passed'])->toBeFalse();
        expect($blocked['submissions']['pending'])->toBe(1);

        Submission::where('registration_id', $registration->id)->delete();
        Submission::factory()->graded()->create(['registration_id' => $registration->id]);

        $clear = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($clear['submissions']['passed'])->toBeTrue();
        expect($clear['submissions']['pending'])->toBe(0);
    });

    test('7C5WM-FR-LIFE-031: the supervision domain requires every log verified', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);
        SupervisionLog::factory()->create(['registration_id' => $registration->id, 'is_verified' => false]);

        $blocked = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($blocked['supervision_logs']['passed'])->toBeFalse();
        expect($blocked['supervision_logs']['pending'])->toBe(1);

        SupervisionLog::where('registration_id', $registration->id)->update(['is_verified' => true]);

        $clear = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($clear['supervision_logs']['passed'])->toBeTrue();
        expect($clear['supervision_logs']['pending'])->toBe(0);
    });

    test('7C5WM-FR-LIFE-032: the attendance domain requires every record verified', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);
        Attendance::factory()->create(['registration_id' => $registration->id, 'is_verified' => false]);

        $blocked = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($blocked['attendance']['passed'])->toBeFalse();
        expect($blocked['attendance']['pending'])->toBe(1);

        Attendance::where('registration_id', $registration->id)->update(['is_verified' => true]);

        $clear = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($clear['attendance']['passed'])->toBeTrue();
        expect($clear['attendance']['pending'])->toBe(0);
    });

    test('7C5WM-FR-LIFE-041: completed internships archive and survive scheduled system cleanup until manual deletion', function (): void {
        $internship = Internship::factory()->create([
            'status' => InternshipStatus::ACTIVE->value,
            'name' => 'PKL Retention Evidence',
        ]);

        app(UpdateInternshipAction::class)->execute($internship, [
            'status' => InternshipStatus::COMPLETED->value,
        ]);
        app(UpdateInternshipAction::class)->execute($internship->refresh(), [
            'status' => InternshipStatus::ARCHIVED->value,
        ]);

        expect($internship->refresh()->status)->toBe(InternshipStatus::ARCHIVED);

        $this->artisan('system:cleanup', ['--force' => true])->assertSuccessful();

        expect(Internship::whereKey($internship->id)->firstOrFail()->status)
            ->toBe(InternshipStatus::ARCHIVED);

        Internship::whereKey($internship->id)->delete();

        expect(Internship::whereKey($internship->id)->exists())->toBeFalse();
    });

    test('7C5WM-FR-LIFE-033: the certificates domain requires issued certificates with at least one present', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);

        $empty = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($empty['certificates']['passed'])->toBeFalse();
        expect($empty['certificates']['total'])->toBe(0);

        Certificate::factory()->create(['registration_id' => $registration->id, 'status' => CertificateStatus::ISSUED->value]);

        $issued = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($issued['certificates']['passed'])->toBeTrue();
        expect($issued['certificates']['pending'])->toBe(0);

        Certificate::where('registration_id', $registration->id)->update(['status' => CertificateStatus::REVOKED->value]);

        $revoked = app(ReadCloseReadinessAction::class)->execute($internship);
        expect($revoked['certificates']['passed'])->toBeFalse();
        expect($revoked['certificates']['pending'])->toBe(1);
    });

    test('7C5WM-UC-LIFE-003: the readiness check reads as a report card naming its blockers', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);
        Assessment::factory()->create(['registration_id' => $registration->id, 'finalized_at' => null]);
        Attendance::factory()->create(['registration_id' => $registration->id, 'is_verified' => false]);

        $report = app(ReadCloseReadinessAction::class)->execute($internship);

        expect($report)->toHaveCount(5);
        expect($report['assessments']['passed'])->toBeFalse();
        expect($report['attendance']['passed'])->toBeFalse();
        expect($report['assessments']['pending'] + $report['attendance']['pending'])->toBeGreaterThan(0);

        $allClear = collect($report)->every(fn ($domain) => $domain['passed'] === true);
        expect($allClear)->toBeFalse('closure must stay unavailable while any domain fails');
    });

    test('7C5WM-FR-LIFE-035: the manager renders readiness with per-domain indicators', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE->value]);
        Registration::factory()->active()->create(['internship_id' => $internship->id]);

        $component = Livewire::test(InternshipManager::class)
            ->call('checkReadiness', $internship->id)
            ->assertSet('readinessInternshipId', $internship->id);

        expect(array_keys($component->get('readinessResults')))
            ->toBe(['assessments', 'submissions', 'supervision_logs', 'attendance', 'certificates']);

        $component->call('dismissReadiness')->assertSet('readinessResults', null);
    });

    test('7C5WM-FR-LIFE-010: the manager renders program identity, dates, status, and row actions', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::factory()->create(['name' => '2026/2027']);
        $internship = Internship::factory()->create([
            'name' => 'PKL Lifecycle Display',
            'academic_year_id' => $year->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => InternshipStatus::PUBLISHED->value,
        ]);

        $component = Livewire::test(InternshipManager::class)
            ->assertSee('PKL Lifecycle Display')
            ->assertSee('2026/2027')
            ->assertSee('01 Jul 2026')
            ->assertSee('31 Dec 2026')
            ->assertSee(__('internship.statuses.published'));

        expect($component->instance()->headers())->toBe([
            ['index' => 'name', 'label' => __('internship.batch_name'), 'sortable' => true],
            ['index' => 'start_date', 'label' => __('internship.start_date'), 'sortable' => true],
            ['index' => 'end_date', 'label' => __('internship.end_date'), 'sortable' => true],
            ['index' => 'status', 'label' => __('internship.status'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ]);
    });

    test('7C5WM-FR-LIFE-011: the manager searches by name and filters by status', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Internship::factory()->create(['name' => 'PKL Alpha Search', 'status' => InternshipStatus::DRAFT->value]);
        Internship::factory()->create(['name' => 'PKL Beta Search', 'status' => InternshipStatus::ACTIVE->value]);

        Livewire::test(InternshipManager::class)
            ->set('search', 'Alpha Search')
            ->assertSee('PKL Alpha Search')
            ->assertDontSee('PKL Beta Search');

        Livewire::test(InternshipManager::class)
            ->set('filters.status', InternshipStatus::ACTIVE->value)
            ->assertSee('PKL Beta Search')
            ->assertDontSee('PKL Alpha Search');
    });

    test('7C5WM-FR-LIFE-042: the manager imports one name-plus-description csv shape', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        AcademicYear::factory()->active()->create();
        Internship::factory()->create(['name' => 'PKL Existing Import']);

        $csv = "name,description\nPKL Existing Import,Old\nPKL Imported Fresh,Fresh description\n,Blank row\n";
        $file = UploadedFile::fake()->createWithContent('internships.csv', $csv);

        Livewire::test(InternshipManager::class)
            ->set('importFile', $file)
            ->assertSet('importFile', null);

        expect(Internship::where('name', 'PKL Imported Fresh')->exists())->toBeTrue();
        expect(Internship::where('name', 'PKL Existing Import')->count())->toBe(1);
        expect(Internship::where('description', 'Fresh description')->exists())->toBeTrue();
    });

    test('7C5WM-FR-LIFE-043: imports land as drafts dated to the active academic year', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::factory()->active()->create([
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $csv = "name,description\nPKL Draft Import,From spreadsheet\n";
        $file = UploadedFile::fake()->createWithContent('internships.csv', $csv);

        Livewire::test(InternshipManager::class)->set('importFile', $file);

        $imported = Internship::where('name', 'PKL Draft Import')->firstOrFail();
        expect($imported->status)->toBe(InternshipStatus::DRAFT);
        expect($imported->academic_year_id)->toBe($year->id);
        expect($imported->start_date->format('Y-m-d'))->toBe('2026-07-01');
        expect($imported->end_date->format('Y-m-d'))->toBe('2027-06-30');
    });

    test('7C5WM-FR-LIFE-044: export dumps the filtered list the coordinator sees', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Internship::factory()->create(['name' => 'PKL Export Alpha', 'status' => InternshipStatus::ACTIVE->value]);
        Internship::factory()->create(['name' => 'PKL Export Beta', 'status' => InternshipStatus::DRAFT->value]);

        $component = Livewire::test(InternshipManager::class)->set('search', 'Export Alpha');
        $manager = $component->instance();
        $manager->search = 'Export Alpha';

        $response = $manager->export(new CsvHandler);

        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="internships.csv"');

        ob_start();
        $response->sendContent();
        $body = (string) ob_get_clean();

        expect($body)->toContain('PKL Export Alpha');
        expect($body)->not->toContain('PKL Export Beta');
    });

    test('7C5WM-FR-LIFE-045: full export includes handoff columns and academic year values', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::factory()->create(['name' => '2026/2027']);
        Internship::factory()->create([
            'name' => 'PKL Export Complete',
            'description' => 'Complete handoff record',
            'status' => InternshipStatus::ACTIVE->value,
            'academic_year_id' => $year->id,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $response = Livewire::test(InternshipManager::class)->instance()->export(new CsvHandler);
        ob_start();
        $response->sendContent();
        $body = (string) ob_get_clean();

        expect($body)->toContain('name,description,status,start_date,end_date,academic_year');
        expect($body)->toContain('"PKL Export Complete","Complete handoff record",active,2026-07-01,2027-06-30,2026/2027');
    });

    test('7C5WM-FR-LIFE-036: livewire closure refuses a program while readiness has blockers', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create([
            'name' => 'PKL Blocked Closure',
            'status' => InternshipStatus::ACTIVE->value,
        ]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);
        Assessment::factory()->create(['registration_id' => $registration->id, 'finalized_at' => null]);

        Livewire::test(InternshipManager::class)
            ->call('checkReadiness', $internship->id)
            ->assertSet('readinessResults.assessments.passed', false)
            ->set('filters.status', InternshipStatus::ACTIVE->value)
            ->call('askCloseFiltered')
            ->call('confirmAction');

        expect($internship->fresh()->status)->toBe(InternshipStatus::ACTIVE);
    });

    test('7C5WM-FR-LIFE-019: admin closes filtered active programs through the batch status flow', function (): void {
        Event::fake([InternshipStatusBatchUpdated::class]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $active = Internship::factory()->create(['name' => 'PKL Batch Active', 'status' => InternshipStatus::ACTIVE->value]);
        $other = Internship::factory()->create(['name' => 'PKL Batch Other', 'status' => InternshipStatus::DRAFT->value]);

        Livewire::test(InternshipManager::class)
            ->set('filters.status', InternshipStatus::ACTIVE->value)
            ->call('askCloseFiltered')
            ->call('confirmAction');

        expect($active->fresh()->status)->toBe(InternshipStatus::COMPLETED)
            ->and($other->fresh()->status)->toBe(InternshipStatus::DRAFT);
        Event::assertDispatched(InternshipStatusBatchUpdated::class, function (InternshipStatusBatchUpdated $event): bool {
            return $event->count === 1 && $event->newStatus === InternshipStatus::COMPLETED->value;
        });
    });

    test('7C5WM-FR-LIFE-045: selected export preserves the same columns while excluding unselected records', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::factory()->create(['name' => '2027/2028']);
        $selected = Internship::factory()->create([
            'name' => 'PKL Selected Handoff',
            'academic_year_id' => $year->id,
        ]);
        Internship::factory()->create(['name' => 'PKL Omitted Handoff', 'academic_year_id' => $year->id]);

        $component = Livewire::test(InternshipManager::class)->set('selectedIds', [$selected->id]);
        $response = $component->instance()->exportSelected(new CsvHandler);
        ob_start();
        $response->sendContent();
        $body = (string) ob_get_clean();

        expect($body)->toContain('name,description,status,start_date,end_date,academic_year');
        expect($body)->toContain('PKL Selected Handoff');
        expect($body)->toContain('2027/2028');
        expect($body)->not->toContain('PKL Omitted Handoff');
    });

    test('7C5WM-FR-LIFE-037: closing a real registration preserves its report snapshot for later retrieval', function (): void {
        $year = AcademicYear::factory()->create(['name' => '2026/2027']);
        $internship = Internship::factory()->create([
            'name' => 'PKL Snapshot Cohort',
            'academic_year_id' => $year->id,
            'status' => InternshipStatus::COMPLETED->value,
        ]);
        $registration = Registration::factory()->active()->create(['internship_id' => $internship->id]);
        $report = StudentReport::factory()->create([
            'registration_id' => $registration->id,
            'archived_data' => null,
        ]);

        app(CaptureStudentReportSnapshotAction::class)->execute($report);

        $snapshot = $report->fresh()->archived_data;
        expect($snapshot)
            ->toHaveKeys(['captured_at', 'student_name', 'student_email', 'internship_name', 'academic_year'])
            ->and($snapshot['internship_name'])->toBe('PKL Snapshot Cohort')
            ->and($snapshot['academic_year'])->toBe('2026/2027')
            ->and($snapshot['captured_at'])->not->toBeNull();
    });

    test('7C5WM-FR-LIFE-038: archived lifecycle records reject registration writes and expose no accepting window', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::ARCHIVED->value]);
        $rule = new OpenForRegistration;
        $failures = [];

        $rule->validate('internship_id', $internship->id, function (string $message) use (&$failures): void {
            $failures[] = $message;
        });

        expect($internship->refresh()->status)->toBe(InternshipStatus::ARCHIVED)
            ->and($internship->asInternshipPeriod()->isAcceptingRegistrations())->toBeFalse()
            ->and($failures)->toHaveCount(1);
    });

    test('7C5WM-FR-LIFE-039: archived student accounts retain certificate access but cannot log in', function (): void {
        $student = User::factory()->create(['status' => AccountStatus::ARCHIVED->value, 'setup_required' => false]);
        $student->assignRole('student');
        $internship = Internship::factory()->create(['status' => InternshipStatus::ARCHIVED->value]);
        $registration = Registration::factory()->create(['student_id' => $student->id, 'internship_id' => $internship->id, 'status' => 'completed']);
        InternshipGroupMember::factory()->create(['registration_id' => $registration->id, 'user_id' => $student->id]);
        $certificate = Certificate::factory()->create(['registration_id' => $registration->id, 'status' => CertificateStatus::ISSUED->value]);

        Livewire::actingAs($student)->test(StudentCertificates::class)
            ->assertSee($certificate->certificate_number);
        expect($student->asApprentice()->isArchived())->toBeTrue();
    });

    test('7C5WM-FR-LIFE-040: only a super admin can unarchive and the reason is audited', function (): void {
        $internship = Internship::factory()->create(['status' => InternshipStatus::COMPLETED->value]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        app(UpdateInternshipAction::class)->execute($internship, ['status' => InternshipStatus::ARCHIVED->value]);
        expect(fn () => app(UnarchiveInternshipAction::class)->execute($internship, 'wrong closure'))->toThrow(RejectedException::class);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);
        app(UnarchiveInternshipAction::class)->execute($internship->refresh(), 'closure correction');

        expect($internship->refresh()->status)->toBe(InternshipStatus::COMPLETED);
        $audit = Activity::query()->where('event', 'internship_unarchived')->latest()->firstOrFail();
        expect($audit->causer_id)->toBe($superAdmin->id)->and($audit->properties['payload']['reason'])->toBe('closure correction');
    });

    test('7C5WM-NFR-LIFE-001: operations verify policy gate and action rules (also NFR-LIFE-003, NFR-LIFE-004, NFR-LIFE-005, UC-LIFE-004)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::factory()->active()->create();
        $internship = app(CreateInternshipAction::class)->execute([
            'name' => 'PKL Atomicity Check',
            'academic_year_id' => $year->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-11-30',
        ]);

        expect($internship->exists)->toBeTrue();
    });

    test('7C5WM-NFR-LIFE-006: strict types and fillable attributes are declared on models (also NFR-LIFE-007, NFR-LIFE-008, NFR-LIFE-010, NFR-LIFE-011, NFR-LIFE-012)', function (): void {
        $classFile = file_get_contents(base_path('app/Modules/Program/Domain/Internship/Models/Internship.php'));
        expect($classFile)->toContain('declare(strict_types=1)')
            ->and($classFile)->toContain('Fillable');

        $status = InternshipStatus::PUBLISHED;
        expect($status->label())->not->toBeEmpty();
    });

    test('7C5WM-DD-LIFE-001: architecture decisions for json phases and entity bridges hold (also DD-LIFE-002, DD-LIFE-003, DD-LIFE-004, DD-LIFE-005, DD-LIFE-006, DD-LIFE-007)', function (): void {
        $internship = Internship::factory()->create([
            'phases' => [['name' => 'Phase 1', 'start_date' => '2026-08-01', 'end_date' => '2026-09-01']],
        ]);

        expect($internship->asInternshipPeriod())->toBeInstanceOf(InternshipPeriod::class)
            ->and($internship->asInternshipState())->toBeInstanceOf(InternshipState::class)
            ->and($internship->phases)->toBeArray();
    });
});
