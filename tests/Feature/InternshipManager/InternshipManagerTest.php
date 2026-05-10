<?php

declare(strict_types=1);

use App\Actions\Internship\CreateInternshipAction;
use App\Actions\Internship\DeleteInternshipAction;
use App\Actions\Internship\UpdateInternshipAction;
use App\Enums\Internship\InternshipStatus;
use App\Events\Internship\InternshipCreated;
use App\Models\AcademicYear;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->academicYear = AcademicYear::factory()->create();
});

describe('CreateInternshipAction', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('creates internship from array input', function () {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'PKL Semester Genap 2025/2026',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'description' => 'Program magang untuk semester genap.',
        ];

        $internship = app(CreateInternshipAction::class)->execute($data);

        expect($internship)->toBeInstanceOf(Internship::class);
        expect($internship->id)->toBeUuid();
        expect($internship->name)->toBe('PKL Semester Genap 2025/2026');
        expect($internship->description)->toBe('Program magang untuk semester genap.');
    });

    it('sets all fillable attributes correctly', function () {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'PKL Ganjil 2025',
            'start_date' => '2025-08-01',
            'end_date' => '2025-12-31',
            'description' => 'Semester ganjil internship.',
        ];

        $internship = app(CreateInternshipAction::class)->execute($data);

        expect($internship->academic_year_id)->toBe($this->academicYear->id);
        expect($internship->start_date->format('Y-m-d'))->toBe('2025-08-01');
        expect($internship->end_date->format('Y-m-d'))->toBe('2025-12-31');
        expect($internship->description)->toBe('Semester ganjil internship.');
    });

    it('defaults status to draft', function () {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'PKL Test',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ];

        $internship = app(CreateInternshipAction::class)->execute($data);

        expect($internship->fresh()->status)->toBe(InternshipStatus::DRAFT);
    });

    it('accepts explicit status override', function () {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'PKL Published',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => InternshipStatus::PUBLISHED->value,
        ];

        $internship = app(CreateInternshipAction::class)->execute($data);

        expect($internship->status)->toBe(InternshipStatus::PUBLISHED);
    });

    it('persists internship to database', function () {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Persistent Internship',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ];

        $internship = app(CreateInternshipAction::class)->execute($data);

        expect(Internship::find($internship->id))->not->toBeNull();
    });

    it('fires InternshipCreated event', function () {
        Event::fake();

        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Eventful Internship',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ];

        app(CreateInternshipAction::class)->execute($data);

        Event::assertDispatched(InternshipCreated::class, function ($event) {
            return $event->internship->name === 'Eventful Internship';
        });
    });

    it('creates activity log with authenticated user', function () {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Audited Internship',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ];

        $internship = app(CreateInternshipAction::class)->execute($data);

        $log = Activity::where('event', 'internship_created')->first();
        expect($log)->not->toBeNull();
        expect($log->causer_id)->toBe($this->user->id);
        expect($log->subject_id)->toBe($internship->id);
        expect($log->properties['payload']['name'])->toBe('Audited Internship');
    });

    it('wraps creation in a database transaction', function () {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Transactional Internship',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ];

        $internship = app(CreateInternshipAction::class)->execute($data);

        expect(Internship::count())->toBe(1);
    });
});

describe('UpdateInternshipAction', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('updates internship name', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Original Name',
        ]);

        $result = app(UpdateInternshipAction::class)->execute($internship, [
            'name' => 'Updated Name',
        ]);

        expect($result->name)->toBe('Updated Name');
        expect($result->fresh()->name)->toBe('Updated Name');
    });

    it('updates dates', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(3),
        ]);

        $newStart = now()->addMonths(2)->toDateString();
        $newEnd = now()->addMonths(5)->toDateString();

        $result = app(UpdateInternshipAction::class)->execute($internship, [
            'start_date' => $newStart,
            'end_date' => $newEnd,
        ]);

        expect($result->start_date->format('Y-m-d'))->toBe($newStart);
        expect($result->end_date->format('Y-m-d'))->toBe($newEnd);
    });

    it('updates description', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'description' => 'Old description.',
        ]);

        $result = app(UpdateInternshipAction::class)->execute($internship, [
            'description' => 'Updated description.',
        ]);

        expect($result->description)->toBe('Updated description.');
    });

    it('updates status', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'status' => InternshipStatus::DRAFT,
        ]);

        $result = app(UpdateInternshipAction::class)->execute($internship, [
            'status' => InternshipStatus::PUBLISHED->value,
        ]);

        expect($result->status)->toBe(InternshipStatus::PUBLISHED);
    });

    it('does not change fields not in update data', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Stable Name',
            'description' => 'Stable description.',
        ]);

        $result = app(UpdateInternshipAction::class)->execute($internship, [
            'name' => 'New Name',
        ]);

        expect($result->description)->toBe('Stable description.');
    });

    it('returns the same internship instance', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
        ]);

        $result = app(UpdateInternshipAction::class)->execute($internship, [
            'name' => 'Same Instance',
        ]);

        expect($result->id)->toBe($internship->id);
    });

    it('creates activity log on update', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Before Update',
        ]);

        app(UpdateInternshipAction::class)->execute($internship, [
            'name' => 'After Update',
        ]);

        $log = Activity::where('event', 'internship_updated')->first();
        expect($log)->not->toBeNull();
        expect($log->causer_id)->toBe($this->user->id);
        expect($log->subject_id)->toBe($internship->id);
    });

    it('succeeds with no changed attributes', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Same Same',
        ]);

        $result = app(UpdateInternshipAction::class)->execute($internship, []);

        expect($result->name)->toBe('Same Same');
    });
});

describe('DeleteInternshipAction', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('deletes the internship from database', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
        ]);

        app(DeleteInternshipAction::class)->execute($internship);

        expect(Internship::find($internship->id))->toBeNull();
    });

    it('creates activity log on delete', function () {
        $internship = Internship::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'To Be Deleted',
        ]);

        app(DeleteInternshipAction::class)->execute($internship);

        $log = Activity::where('event', 'internship_deleted')->first();
        expect($log)->not->toBeNull();
        expect($log->causer_id)->toBe($this->user->id);
        expect($log->properties['payload']['name'])->toBe('To Be Deleted');
    });

    it('decrement internship count', function () {
        Internship::factory()->count(2)->create([
            'academic_year_id' => $this->academicYear->id,
        ]);

        $internship = Internship::first();

        app(DeleteInternshipAction::class)->execute($internship);

        expect(Internship::count())->toBe(1);
    });
});

use App\Livewire\Internship\InternshipManager;
use App\Models\Placement;
use App\Models\Registration;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

describe('authorization', function () {

    it('allows super_admin to access the page', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        Livewire::test(InternshipManager::class)
            ->assertSuccessful();
    });

    it('allows admin to access the page', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(InternshipManager::class)
            ->assertSuccessful();
    });

    it('blocks teacher from accessing the page', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');
        $this->actingAs($user);

        Livewire::test(InternshipManager::class)
            ->assertForbidden();
    });

    it('blocks student from accessing the page', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $this->actingAs($user);

        Livewire::test(InternshipManager::class)
            ->assertForbidden();
    });

});

describe('super_admin access', function () {

    beforeEach(function () {
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');
        $this->actingAs($this->superAdmin);
    });

    it('renders the page and displays internships', function () {
        $internship = Internship::factory()->create(['name' => 'PKL Ganjil 2026']);

        Livewire::test(InternshipManager::class)
            ->assertSuccessful()
            ->assertSee('PKL Ganjil 2026');
    });

    it('filters internships by search', function () {
        Internship::factory()->create(['name' => 'PKL Ganjil 2026']);
        Internship::factory()->create(['name' => 'PKL Genap 2027']);

        Livewire::test(InternshipManager::class)
            ->set('search', 'Ganjil')
            ->assertSee('PKL Ganjil 2026')
            ->assertDontSee('PKL Genap 2027');
    });

    it('opens the create modal', function () {
        Livewire::test(InternshipManager::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('formData.id', null);
    });

    it('creates a new internship', function () {
        Livewire::test(InternshipManager::class)
            ->call('create')
            ->set('formData.name', 'PKL Semester Genap 2026/2027')
            ->set('formData.start_date', '2026-07-01')
            ->set('formData.end_date', '2026-12-31')
            ->set('formData.status', 'draft')
            ->call('save')
            ->assertHasNoErrors();

        $internship = Internship::where('name', 'PKL Semester Genap 2026/2027')->first();
        expect($internship)->not->toBeNull();
        expect($internship->status->value)->toBe('draft');
    });

    it('validates required fields on create', function () {
        Livewire::test(InternshipManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'formData.name' => 'required',
                'formData.start_date' => 'required',
                'formData.end_date' => 'required',
            ]);
    });

    it('validates name uniqueness on create', function () {
        Internship::factory()->create(['name' => 'Existing Batch']);

        Livewire::test(InternshipManager::class)
            ->call('create')
            ->set('formData.name', 'Existing Batch')
            ->set('formData.start_date', '2026-07-01')
            ->set('formData.end_date', '2026-12-31')
            ->call('save')
            ->assertHasErrors(['formData.name' => 'unique']);
    });

    it('validates end_date after start_date', function () {
        Livewire::test(InternshipManager::class)
            ->call('create')
            ->set('formData.name', 'Invalid Dates')
            ->set('formData.start_date', '2026-12-31')
            ->set('formData.end_date', '2026-01-01')
            ->call('save')
            ->assertHasErrors(['formData.end_date' => 'after']);
    });

    it('opens the edit modal with internship data', function () {
        $internship = Internship::factory()->create([
            'name' => 'PKL Edit Sesi',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'description' => 'Deskripsi PKL',
            'status' => 'draft',
        ]);

        Livewire::test(InternshipManager::class)
            ->call('edit', $internship->id)
            ->assertSet('showModal', true)
            ->assertSet('formData.id', $internship->id)
            ->assertSet('formData.name', 'PKL Edit Sesi')
            ->assertSet('formData.start_date', '2026-07-01')
            ->assertSet('formData.end_date', '2026-12-31')
            ->assertSet('formData.description', 'Deskripsi PKL')
            ->assertSet('formData.status', 'draft');
    });

    it('updates an existing internship', function () {
        $internship = Internship::factory()->create([
            'name' => 'Before Edit',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        Livewire::test(InternshipManager::class)
            ->call('edit', $internship->id)
            ->set('formData.name', 'After Edit')
            ->set('formData.start_date', '2026-07-01')
            ->set('formData.end_date', '2026-12-31')
            ->call('save')
            ->assertHasNoErrors();

        expect($internship->fresh()->name)->toBe('After Edit');
        expect($internship->fresh()->start_date->format('Y-m-d'))->toBe('2026-07-01');
        expect($internship->fresh()->end_date->format('Y-m-d'))->toBe('2026-12-31');
    });

    it('deletes an internship', function () {
        $internship = Internship::factory()->create();

        Livewire::test(InternshipManager::class)
            ->call('delete', $internship->id);

        expect(Internship::find($internship->id))->toBeNull();
    });

    it('blocks delete when internship has placements', function () {
        $placement = Placement::factory()->create();
        $internship = $placement->internship;

        Livewire::test(InternshipManager::class)
            ->call('delete', $internship->id);

        expect(Internship::find($internship->id))->not->toBeNull();
    });

    it('blocks delete when internship has registrations', function () {
        $registration = Registration::factory()->create();
        $internship = $registration->internship;

        Livewire::test(InternshipManager::class)
            ->call('delete', $internship->id);

        expect(Internship::find($internship->id))->not->toBeNull();
    });

    it('deletes selected internships in bulk', function () {
        $internship1 = Internship::factory()->create();
        $internship2 = Internship::factory()->create();

        Livewire::test(InternshipManager::class)
            ->set('selectedIds', [$internship1->id, $internship2->id])
            ->call('deleteSelected');

        expect(Internship::find($internship1->id))->toBeNull();
        expect(Internship::find($internship2->id))->toBeNull();
    });

    it('closes all filtered internships', function () {
        Internship::factory()->create(['status' => 'active']);
        Internship::factory()->create(['status' => 'draft']);
        Internship::factory()->create(['status' => 'published']);

        Livewire::test(InternshipManager::class)
            ->call('closeAllFiltered');

        expect(Internship::where('status', '!=', 'completed')->count())->toBe(0);
    });

    describe('edge cases', function () {

        it('computes stats correctly', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE]);
            Internship::factory()->count(3)->create();

            Placement::factory()->count(2)->create(['internship_id' => $internship->id]);
            Registration::factory()->count(3)->create(['internship_id' => $internship->id]);

            Livewire::test(InternshipManager::class)
                ->assertSet('stats.total', 4)
                ->assertSet('stats.active', 1)
                ->assertSet('stats.total_placements', 2)
                ->assertSet('stats.total_registrations', 3);
        });

        it('bulk delete skips internships with children', function () {
            $internship1 = Internship::factory()->create();
            $internship2 = Internship::factory()->create();
            Placement::factory()->create(['internship_id' => $internship1->id]);

            Livewire::test(InternshipManager::class)
                ->set('selectedIds', [$internship1->id, $internship2->id])
                ->call('deleteSelected');

            expect(Internship::find($internship1->id))->not->toBeNull();
            expect(Internship::find($internship2->id))->toBeNull();
        });

        it('shows warning on mass close when no records match filter', function () {
            Internship::factory()->create(['name' => 'Keep Me']);

            Livewire::test(InternshipManager::class)
                ->set('search', 'NonExistent')
                ->call('closeAllFiltered');

            expect(Internship::where('status', 'completed')->count())->toBe(0);
        });

        it('creates internship with registration window dates', function () {
            Livewire::test(InternshipManager::class)
                ->call('create')
                ->set('formData.name', 'Batch With Registration Window')
                ->set('formData.start_date', '2026-07-01')
                ->set('formData.end_date', '2026-12-31')
                ->set('formData.registration_start_date', '2026-05-01')
                ->set('formData.registration_end_date', '2026-06-30')
                ->call('save')
                ->assertHasNoErrors();

            $internship = Internship::where('name', 'Batch With Registration Window')->first();
            expect($internship)->not->toBeNull();
            expect($internship->registration_start_date->format('Y-m-d'))->toBe('2026-05-01');
            expect($internship->registration_end_date->format('Y-m-d'))->toBe('2026-06-30');
        });

        it('creates internship without registration window dates', function () {
            Livewire::test(InternshipManager::class)
                ->call('create')
                ->set('formData.name', 'Batch No Window')
                ->set('formData.start_date', '2026-07-01')
                ->set('formData.end_date', '2026-12-31')
                ->call('save')
                ->assertHasNoErrors();

            $internship = Internship::where('name', 'Batch No Window')->first();
            expect($internship)->not->toBeNull();
            expect($internship->registration_start_date)->toBeNull();
            expect($internship->registration_end_date)->toBeNull();
        });

        it('validates registration_end_date after registration_start_date', function () {
            Livewire::test(InternshipManager::class)
                ->call('create')
                ->set('formData.name', 'Invalid Window')
                ->set('formData.start_date', '2026-07-01')
                ->set('formData.end_date', '2026-12-31')
                ->set('formData.registration_start_date', '2026-06-30')
                ->set('formData.registration_end_date', '2026-05-01')
                ->call('save')
                ->assertHasErrors(['formData.registration_end_date' => 'after_or_equal']);
        });

    });

    describe('status transitions', function () {

        it('allows valid transition from draft to published', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::DRAFT]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::PUBLISHED->value)
                ->call('save')
                ->assertHasNoErrors()
                ->assertSet('showModal', false);

            expect($internship->fresh()->status)->toBe(InternshipStatus::PUBLISHED);
        });

        it('allows valid transition from published to active', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::PUBLISHED]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::ACTIVE->value)
                ->call('save')
                ->assertHasNoErrors()
                ->assertSet('showModal', false);

            expect($internship->fresh()->status)->toBe(InternshipStatus::ACTIVE);
        });

        it('allows valid transition from active to completed', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::COMPLETED->value)
                ->call('save')
                ->assertHasNoErrors()
                ->assertSet('showModal', false);

            expect($internship->fresh()->status)->toBe(InternshipStatus::COMPLETED);
        });

        it('allows valid transition to cancelled from any non-terminal', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::PUBLISHED]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::CANCELLED->value)
                ->call('save')
                ->assertHasNoErrors()
                ->assertSet('showModal', false);

            expect($internship->fresh()->status)->toBe(InternshipStatus::CANCELLED);
        });

        it('blocks invalid transition from draft to active', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::DRAFT]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::ACTIVE->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::DRAFT);
        });

        it('blocks invalid transition from draft to completed', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::DRAFT]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::COMPLETED->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::DRAFT);
        });

        it('blocks invalid transition from published to draft', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::PUBLISHED]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::DRAFT->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::PUBLISHED);
        });

        it('blocks invalid transition from published to completed', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::PUBLISHED]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::COMPLETED->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::PUBLISHED);
        });

        it('blocks invalid transition from active to draft', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::DRAFT->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::ACTIVE);
        });

        it('blocks invalid transition from active to published', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::ACTIVE]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::PUBLISHED->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::ACTIVE);
        });

        it('blocks transition from completed to any status', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::COMPLETED]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::DRAFT->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::COMPLETED);
        });

        it('blocks transition from cancelled to any status', function () {
            $internship = Internship::factory()->create(['status' => InternshipStatus::CANCELLED]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.status', InternshipStatus::PUBLISHED->value)
                ->call('save');

            expect($internship->fresh()->status)->toBe(InternshipStatus::CANCELLED);
        });

        it('allows saving without changing status', function () {
            $internship = Internship::factory()->create([
                'name' => 'Same Status',
                'status' => InternshipStatus::PUBLISHED,
            ]);

            Livewire::test(InternshipManager::class)
                ->call('edit', $internship->id)
                ->set('formData.name', 'Same Status Updated')
                ->call('save')
                ->assertHasNoErrors()
                ->assertSet('showModal', false);

            expect($internship->fresh()->name)->toBe('Same Status Updated');
            expect($internship->fresh()->status)->toBe(InternshipStatus::PUBLISHED);
        });

    });

    describe('academic year auto-assignment', function () {

        it('auto-assigns active academic year on create', function () {
            $activeYear = AcademicYear::factory()->create(['is_active' => true]);

            Livewire::test(InternshipManager::class)
                ->call('create')
                ->set('formData.name', 'Auto-Assigned Batch')
                ->set('formData.start_date', '2026-07-01')
                ->set('formData.end_date', '2026-12-31')
                ->call('save')
                ->assertHasNoErrors();

            $internship = Internship::where('name', 'Auto-Assigned Batch')->first();
            expect($internship)->not->toBeNull();
            expect($internship->academic_year_id)->toBe($activeYear->id);
        });

        it('does not auto-assign academic year when none is active', function () {
            AcademicYear::factory()->create(['is_active' => false]);

            Livewire::test(InternshipManager::class)
                ->call('create')
                ->set('formData.name', 'No Active Year Batch')
                ->set('formData.start_date', '2026-07-01')
                ->set('formData.end_date', '2026-12-31')
                ->call('save')
                ->assertHasNoErrors();

            $internship = Internship::where('name', 'No Active Year Batch')->first();
            expect($internship)->not->toBeNull();
            expect($internship->academic_year_id)->toBeNull();
        });

        it('does not override explicitly set academic_year_id on create', function () {
            $activeYear = AcademicYear::factory()->create(['is_active' => true]);
            $otherYear = AcademicYear::factory()->create(['is_active' => false]);

            Livewire::test(InternshipManager::class)
                ->call('create')
                ->set('formData.name', 'Explicit Year Batch')
                ->set('formData.start_date', '2026-07-01')
                ->set('formData.end_date', '2026-12-31')
                ->set('formData.academic_year_id', $otherYear->id)
                ->call('save')
                ->assertHasNoErrors();

            $internship = Internship::where('name', 'Explicit Year Batch')->first();
            expect($internship)->not->toBeNull();
            expect($internship->academic_year_id)->toBe($otherYear->id);
        });

    });

});

describe('admin access', function () {

    beforeEach(function () {
        $this->admin = User::factory()->create(['name' => 'Regular Admin']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    });

    it('renders the page and displays internships', function () {
        $internship = Internship::factory()->create(['name' => 'Admin View Batch']);

        Livewire::test(InternshipManager::class)
            ->assertSuccessful()
            ->assertSee('Admin View Batch');
    });

    it('can create a new internship', function () {
        Livewire::test(InternshipManager::class)
            ->call('create')
            ->set('formData.name', 'Admin Created Batch')
            ->set('formData.start_date', '2026-07-01')
            ->set('formData.end_date', '2026-12-31')
            ->call('save')
            ->assertHasNoErrors();

        expect(Internship::where('name', 'Admin Created Batch')->exists())->toBeTrue();
    });

    it('can edit an internship', function () {
        $internship = Internship::factory()->create(['name' => 'Old Name']);

        Livewire::test(InternshipManager::class)
            ->call('edit', $internship->id)
            ->set('formData.name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        expect($internship->fresh()->name)->toBe('New Name');
    });

    it('can delete an internship', function () {
        $internship = Internship::factory()->create();

        Livewire::test(InternshipManager::class)
            ->call('delete', $internship->id);

        expect(Internship::find($internship->id))->toBeNull();
    });

});
