<?php

declare(strict_types=1);

use App\Livewire\User\Admin\StudentManager;
use App\Models\Department;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $this->department = Department::factory()->create();

    $this->admin = User::factory()->create(['name' => 'Super Admin']);
    $this->admin->assignRole('super_admin');

    $this->actingAs($this->admin);
});

describe('access control', function () {

    it('allows super_admin to access', function () {
        Livewire::test(StudentManager::class)
            ->assertSuccessful();
    });

    it('allows admin to access', function () {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(StudentManager::class)
            ->assertSuccessful();
    });

    it('blocks teacher from accessing', function () {
        $user = User::factory()->create()->assignRole('teacher');
        $this->actingAs($user);

        Livewire::test(StudentManager::class)
            ->assertForbidden();
    });

    it('blocks student from accessing', function () {
        $user = User::factory()->create()->assignRole('student');
        $this->actingAs($user);

        Livewire::test(StudentManager::class)
            ->assertForbidden();
    });

});

describe('rendering', function () {

    it('renders the student manager page', function () {
        Livewire::test(StudentManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays students in the table', function () {
        User::factory()->create(['name' => 'Alice Student'])->assignRole('student');
        User::factory()->create(['name' => 'Bob Student'])->assignRole('student');

        Livewire::test(StudentManager::class)
            ->assertSee('Alice Student')
            ->assertSee('Bob Student');
    });

    it('does not display non-student users', function () {
        User::factory()->create(['name' => 'Some Admin'])->assignRole('admin');

        Livewire::test(StudentManager::class)
            ->assertDontSee('Some Admin');
    });

});

describe('search', function () {

    it('filters students by name', function () {
        User::factory()->create(['name' => 'Unique Student'])->assignRole('student');
        User::factory()->create(['name' => 'Other Student'])->assignRole('student');

        Livewire::test(StudentManager::class)
            ->set('search', 'Unique')
            ->assertSee('Unique Student')
            ->assertDontSee('Other Student');
    });

    it('filters students by email', function () {
        User::factory()->create(['email' => 'unique@example.com'])->assignRole('student');
        User::factory()->create(['email' => 'other@example.com'])->assignRole('student');

        Livewire::test(StudentManager::class)
            ->set('search', 'unique@')
            ->assertSee('unique@example.com')
            ->assertDontSee('other@example.com');
    });

});

describe('filter', function () {

    it('filters students by department', function () {
        $deptA = Department::factory()->create(['name' => 'Dept A']);
        $deptB = Department::factory()->create(['name' => 'Dept B']);

        $studentA = User::factory()->create(['name' => 'Student A'])->assignRole('student');
        $studentA->profile()->updateOrCreate(
            ['user_id' => $studentA->id],
            ['department_id' => $deptA->id],
        );

        $studentB = User::factory()->create(['name' => 'Student B'])->assignRole('student');
        $studentB->profile()->updateOrCreate(
            ['user_id' => $studentB->id],
            ['department_id' => $deptB->id],
        );

        Livewire::test(StudentManager::class)
            ->set('filters.department_id', $deptA->id)
            ->assertSee('Student A')
            ->assertDontSee('Student B');
    });

});

describe('create', function () {

    it('opens the create modal', function () {
        Livewire::test(StudentManager::class)
            ->call('create')
            ->assertSet('userModal', true)
            ->assertSet('userData.id', null);
    });

    it('creates a new student with profile', function () {
        Livewire::test(StudentManager::class)
            ->call('create')
            ->set('userData.name', 'New Student')
            ->set('userData.email', 'newstudent@example.com')
            ->set('userData.national_identifier', '1234567890')
            ->set('userData.registration_number', 'NIS001')
            ->set('userData.department_id', $this->department->id)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'newstudent@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('New Student');
        expect($user->hasRole('student'))->toBeTrue();
        expect($user->profile->national_identifier)->toBe('1234567890');
        expect($user->profile->department_id)->toBe($this->department->id);
    });

    it('validates required fields on create', function () {
        Livewire::test(StudentManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'userData.name' => 'required',
                'userData.email' => 'required',
                'userData.national_identifier' => 'required',
                'userData.department_id' => 'required',
            ]);
    });

    it('validates email uniqueness on create', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(StudentManager::class)
            ->call('create')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'existing@example.com')
            ->set('userData.national_identifier', '1234567890')
            ->set('userData.department_id', $this->department->id)
            ->call('save')
            ->assertHasErrors(['userData.email' => 'unique']);
    });

    it('validates department exists', function () {
        Livewire::test(StudentManager::class)
            ->call('create')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'test@example.com')
            ->set('userData.national_identifier', '1234567890')
            ->set('userData.department_id', 'non-existent-id')
            ->call('save')
            ->assertHasErrors(['userData.department_id' => 'exists']);
    });

});

describe('edit', function () {

    it('opens the edit modal with user data', function () {
        $user = User::factory()->create(['name' => 'Edit Student'])->assignRole('student');

        Livewire::test(StudentManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('userData.id', $user->id)
            ->assertSet('userData.name', 'Edit Student');
    });

    it('updates student data', function () {
        $user = User::factory()->create([
            'name' => 'Before Edit',
            'email' => 'before@example.com',
        ])->assignRole('student');

        Livewire::test(StudentManager::class)
            ->call('edit', $user->id)
            ->set('userData.name', 'After Edit')
            ->set('userData.email', 'after@example.com')
            ->set('userData.national_identifier', '9876543210')
            ->set('userData.department_id', $this->department->id)
            ->call('save')
            ->assertHasNoErrors();

        expect($user->fresh()->name)->toBe('After Edit');
        expect($user->fresh()->email)->toBe('after@example.com');
    });

});

describe('delete', function () {

    it('deletes a student', function () {
        $user = User::factory()->create()->assignRole('student');

        Livewire::test(StudentManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

});

describe('bulk delete', function () {

    it('deletes selected students', function () {
        $student1 = User::factory()->create()->assignRole('student');
        $student2 = User::factory()->create()->assignRole('student');

        Livewire::test(StudentManager::class)
            ->set('selectedIds', [$student1->id, $student2->id])
            ->call('deleteSelected');

        expect(User::find($student1->id))->toBeNull();
        expect(User::find($student2->id))->toBeNull();
    });

});
