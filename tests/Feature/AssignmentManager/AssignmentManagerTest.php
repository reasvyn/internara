<?php

declare(strict_types=1);

use App\Actions\Assignment\CreateAssignmentAction;
use App\Actions\Assignment\DeleteAssignmentAction;
use App\Actions\Assignment\PublishAssignmentAction;
use App\Actions\Assignment\UpdateAssignmentAction;
use App\Livewire\Assignment\Admin\AssignmentManager;
use App\Models\Assignment;
use App\Models\AssignmentType;
use App\Models\Internship;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Super Admin']);
    $this->admin->assignRole('super_admin');

    $this->actingAs($this->admin);

    $this->internship = Internship::factory()->create();
    $this->type = AssignmentType::factory()->create();
});

/*
|--------------------------------------------------------------------------
| Rendering
|--------------------------------------------------------------------------
*/

describe('rendering', function () {

    it('renders the assignment manager page', function () {
        Livewire::test(AssignmentManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays assignments in the table', function () {
        Assignment::factory()->create(['title' => 'Laporan PKL']);
        Assignment::factory()->create(['title' => 'PPT Presentasi']);

        Livewire::test(AssignmentManager::class)
            ->assertSee('Laporan PKL')
            ->assertSee('PPT Presentasi');
    });

});

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

describe('search', function () {

    it('filters assignments by title', function () {
        Assignment::factory()->create(['title' => 'Laporan Akhir']);
        Assignment::factory()->create(['title' => 'Logbook Harian']);

        Livewire::test(AssignmentManager::class)
            ->set('search', 'Laporan')
            ->assertSee('Laporan Akhir')
            ->assertDontSee('Logbook Harian');
    });

});

/*
|--------------------------------------------------------------------------
| Create Assignment
|--------------------------------------------------------------------------
*/

describe('create assignment', function () {

    it('opens the create modal', function () {
        Livewire::test(AssignmentManager::class)
            ->call('create')
            ->assertSet('assignmentModal', true)
            ->assertSet('formData.id', null);
    });

    it('creates a new assignment', function () {
        Livewire::test(AssignmentManager::class)
            ->call('create')
            ->set('formData.assignment_type_id', $this->type->id)
            ->set('formData.internship_id', $this->internship->id)
            ->set('formData.title', 'Laporan PKL')
            ->set('formData.due_date', now()->addMonth()->format('Y-m-d'))
            ->set('formData.is_mandatory', true)
            ->call('save')
            ->assertHasNoErrors();

        $assignment = Assignment::where('title', 'Laporan PKL')->first();
        expect($assignment)->not->toBeNull()
            ->and($assignment->is_mandatory)->toBeTrue()
            ->and($assignment->status->value)->toBe('draft');
    });

    it('validates required fields on create', function () {
        Livewire::test(AssignmentManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'formData.assignment_type_id' => 'required',
                'formData.internship_id' => 'required',
                'formData.title' => 'required',
                'formData.due_date' => 'required',
            ]);
    });

    it('creates optional assignment', function () {
        Livewire::test(AssignmentManager::class)
            ->call('create')
            ->set('formData.assignment_type_id', $this->type->id)
            ->set('formData.internship_id', $this->internship->id)
            ->set('formData.title', 'PPT Presentasi')
            ->set('formData.due_date', now()->addMonth()->format('Y-m-d'))
            ->set('formData.is_mandatory', false)
            ->call('save')
            ->assertHasNoErrors();

        $assignment = Assignment::where('title', 'PPT Presentasi')->first();
        expect($assignment)->not->toBeNull()
            ->and($assignment->is_mandatory)->toBeFalse();
    });

});

/*
|--------------------------------------------------------------------------
| Edit Assignment
|--------------------------------------------------------------------------
*/

describe('edit assignment', function () {

    it('opens the edit modal with data', function () {
        $assignment = Assignment::factory()->create(['title' => 'Edit Me']);

        Livewire::test(AssignmentManager::class)
            ->call('edit', $assignment->id)
            ->assertSet('assignmentModal', true)
            ->assertSet('formData.id', $assignment->id)
            ->assertSet('formData.title', 'Edit Me');
    });

    it('updates assignment title', function () {
        $assignment = Assignment::factory()->create(['title' => 'Before Edit']);

        Livewire::test(AssignmentManager::class)
            ->call('edit', $assignment->id)
            ->set('formData.title', 'After Edit')
            ->call('save')
            ->assertHasNoErrors();

        expect($assignment->fresh()->title)->toBe('After Edit');
    });

    it('toggles mandatory flag on update', function () {
        $assignment = Assignment::factory()->create([
            'title' => 'Toggle Test',
            'is_mandatory' => true,
        ]);

        Livewire::test(AssignmentManager::class)
            ->call('edit', $assignment->id)
            ->set('formData.is_mandatory', false)
            ->call('save');

        expect($assignment->fresh()->is_mandatory)->toBeFalse();
    });

});

/*
|--------------------------------------------------------------------------
| Publish Assignment
|--------------------------------------------------------------------------
*/

describe('publish assignment', function () {

    it('publishes a draft assignment', function () {
        $assignment = Assignment::factory()->create(['status' => 'draft']);

        Livewire::test(AssignmentManager::class)
            ->call('publish', $assignment->id)
            ->assertHasNoErrors();

        expect($assignment->fresh()->status->value)->toBe('published');
    });

});

/*
|--------------------------------------------------------------------------
| Delete Assignment
|--------------------------------------------------------------------------
*/

describe('delete assignment', function () {

    it('deletes an assignment without submissions', function () {
        $assignment = Assignment::factory()->create();

        Livewire::test(AssignmentManager::class)
            ->call('delete', $assignment->id);

        expect(Assignment::find($assignment->id))->toBeNull();
    });

    it('bulk deletes selected assignments', function () {
        $a1 = Assignment::factory()->create();
        $a2 = Assignment::factory()->create();
        $a3 = Assignment::factory()->create();

        Livewire::test(AssignmentManager::class)
            ->set('selectedIds', [$a1->id, $a3->id])
            ->call('deleteSelected');

        expect(Assignment::find($a1->id))->toBeNull();
        expect(Assignment::find($a2->id))->not->toBeNull();
        expect(Assignment::find($a3->id))->toBeNull();
    });

});

/*
|--------------------------------------------------------------------------
| Actions (Direct Unit Tests)
|--------------------------------------------------------------------------
*/

describe('CreateAssignmentAction', function () {

    it('creates an assignment as draft by default', function () {
        $assignment = app(CreateAssignmentAction::class)->execute(
            $this->type->id,
            $this->internship->id,
            'Direct Test',
        );

        expect($assignment)->toBeInstanceOf(Assignment::class)
            ->and($assignment->title)->toBe('Direct Test')
            ->and($assignment->status->value)->toBe('draft');
    });

    it('creates mandatory assignment', function () {
        $assignment = app(CreateAssignmentAction::class)->execute(
            $this->type->id,
            $this->internship->id,
            'Mandatory Task',
            isMandatory: true,
        );

        expect($assignment->is_mandatory)->toBeTrue();
    });

    it('creates optional assignment', function () {
        $assignment = app(CreateAssignmentAction::class)->execute(
            $this->type->id,
            $this->internship->id,
            'Optional Task',
            isMandatory: false,
        );

        expect($assignment->is_mandatory)->toBeFalse();
    });

    it('sets due date when provided', function () {
        $dueDate = now()->addMonth()->format('Y-m-d');

        $assignment = app(CreateAssignmentAction::class)->execute(
            $this->type->id,
            $this->internship->id,
            'Dated Task',
            dueDate: $dueDate,
        );

        expect($assignment->due_date->format('Y-m-d'))->toBe($dueDate);
    });

});

describe('UpdateAssignmentAction', function () {

    it('updates assignment fields', function () {
        $assignment = Assignment::factory()->create(['title' => 'Original']);

        app(UpdateAssignmentAction::class)->execute(
            $assignment,
            title: 'Updated',
            description: 'New description',
            isMandatory: true,
        );

        expect($assignment->fresh()->title)->toBe('Updated')
            ->and($assignment->fresh()->description)->toBe('New description')
            ->and($assignment->fresh()->is_mandatory)->toBeTrue();
    });

});

describe('PublishAssignmentAction', function () {

    it('publishes a draft assignment', function () {
        $assignment = Assignment::factory()->create(['status' => 'draft']);

        app(PublishAssignmentAction::class)->execute($assignment);

        expect($assignment->fresh()->status->value)->toBe('published');
    });

    it('throws when publishing non-draft assignment', function () {
        $assignment = Assignment::factory()->create(['status' => 'published']);

        expect(fn () => app(PublishAssignmentAction::class)->execute($assignment))
            ->toThrow(InvalidArgumentException::class);
    });

});

describe('DeleteAssignmentAction', function () {

    it('deletes an assignment', function () {
        $assignment = Assignment::factory()->create();

        app(DeleteAssignmentAction::class)->execute($assignment);

        expect(Assignment::find($assignment->id))->toBeNull();
    });

});

/*
|--------------------------------------------------------------------------
| Teacher Access
|--------------------------------------------------------------------------
*/

describe('teacher access', function () {

    beforeEach(function () {
        $this->teacher = User::factory()->create(['name' => 'Teacher User']);
        $this->teacher->assignRole('teacher');
        $this->actingAs($this->teacher);
    });

    it('teacher can view assignments', function () {
        Assignment::factory()->create(['title' => 'Teacher View Test']);

        Livewire::test(AssignmentManager::class)
            ->assertSee('Teacher View Test');
    });

});
