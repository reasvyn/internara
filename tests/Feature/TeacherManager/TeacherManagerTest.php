<?php

declare(strict_types=1);

use App\Enums\Auth\Role as RoleEnum;
use App\Livewire\User\Admin\TeacherManager;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Super Admin']);
    $this->admin->assignRole('super_admin');

    $this->actingAs($this->admin);
});

describe('access control', function () {

    it('allows super_admin to access', function () {
        Livewire::test(TeacherManager::class)
            ->assertSuccessful();
    });

    it('allows admin to access', function () {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(TeacherManager::class)
            ->assertSuccessful();
    });

    it('blocks student from accessing', function () {
        $user = User::factory()->create()->assignRole('student');
        $this->actingAs($user);

        Livewire::test(TeacherManager::class)
            ->assertForbidden();
    });

});

describe('rendering', function () {

    it('renders the teacher manager page', function () {
        Livewire::test(TeacherManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays teachers in the table', function () {
        User::factory()->create(['name' => 'Alice Teacher'])->assignRole('teacher');
        User::factory()->create(['name' => 'Bob Teacher'])->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->assertSee('Alice Teacher')
            ->assertSee('Bob Teacher');
    });

    it('does not display non-teacher users', function () {
        User::factory()->create(['name' => 'Some Student'])->assignRole('student');

        Livewire::test(TeacherManager::class)
            ->assertDontSee('Some Student');
    });

});

describe('search', function () {

    it('filters teachers by name', function () {
        User::factory()->create(['name' => 'Unique Teacher'])->assignRole('teacher');
        User::factory()->create(['name' => 'Other Teacher'])->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->set('search', 'Unique')
            ->assertSee('Unique Teacher')
            ->assertDontSee('Other Teacher');
    });

    it('filters teachers by email', function () {
        User::factory()->create(['email' => 'unique@example.com'])->assignRole('teacher');
        User::factory()->create(['email' => 'other@example.com'])->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->set('search', 'unique@')
            ->assertSee('unique@example.com')
            ->assertDontSee('other@example.com');
    });

});

describe('create', function () {

    it('opens the create modal', function () {
        Livewire::test(TeacherManager::class)
            ->call('create')
            ->assertSet('userModal', true)
            ->assertSet('userData.id', null);
    });

    it('creates a new teacher with role and profile', function () {
        Livewire::test(TeacherManager::class)
            ->call('create')
            ->set('userData.name', 'New Teacher')
            ->set('userData.email', 'newteacher@example.com')
            ->set('userData.registration_number', 'NIP-001')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'newteacher@example.com')->first();

        expect($user)->not->toBeNull();
        expect($user->name)->toBe('New Teacher');
        expect($user->email)->toBe('newteacher@example.com');
        expect($user->username)->toStartWith('u');
        expect(strlen($user->username))->toBe(9);
        expect($user->hasRole(RoleEnum::TEACHER->value))->toBeTrue();
        expect($user->profile)->not->toBeNull();
        expect($user->profile->registration_number)->toBe('NIP-001');
        expect(Hash::check('newteacher@example.com', $user->password))->toBeFalse();

        assertDatabaseHas('users', ['email' => 'newteacher@example.com']);
    });

    it('validates required fields on create', function () {
        Livewire::test(TeacherManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'userData.name' => 'required',
                'userData.email' => 'required',
            ]);
    });

    it('validates email uniqueness on create', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(TeacherManager::class)
            ->call('create')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'existing@example.com')
            ->call('save')
            ->assertHasErrors(['userData.email' => 'unique']);
    });

    it('creates teacher with minimal fields (no NIP)', function () {
        Livewire::test(TeacherManager::class)
            ->call('create')
            ->set('userData.name', 'Minimal Teacher')
            ->set('userData.email', 'minimal@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'minimal@example.com')->first();

        expect($user)->not->toBeNull();
        expect($user->hasRole('teacher'))->toBeTrue();
        expect($user->profile)->not->toBeNull();
        expect($user->profile->registration_number)->toBeEmpty();
    });

});

describe('edit', function () {

    it('opens the edit modal with user data', function () {
        $user = User::factory()->create(['name' => 'Edit Teacher'])->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('userData.id', $user->id)
            ->assertSet('userData.name', 'Edit Teacher');
    });

    it('loads registration number from profile into edit modal', function () {
        $user = User::factory()->create(['name' => 'NIP Teacher'])->assignRole('teacher');
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['registration_number' => 'NIP-EDIT'],
        );

        Livewire::test(TeacherManager::class)
            ->call('edit', $user->id)
            ->assertSet('userData.registration_number', 'NIP-EDIT');
    });

    it('updates teacher data and profile', function () {
        $user = User::factory()->create([
            'name' => 'Before Edit',
            'email' => 'before@example.com',
        ])->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('edit', $user->id)
            ->set('userData.name', 'After Edit')
            ->set('userData.email', 'after@example.com')
            ->set('userData.registration_number', 'NIP-UPDATED')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $user->fresh();

        expect($fresh->name)->toBe('After Edit');
        expect($fresh->email)->toBe('after@example.com');
        expect($fresh->profile->registration_number)->toBe('NIP-UPDATED');
        expect($fresh->hasRole('teacher'))->toBeTrue();

        assertDatabaseHas('users', ['id' => $user->id, 'name' => 'After Edit']);
    });

});

describe('delete', function () {

    it('deletes a teacher', function () {
        $user = User::factory()->create()->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
        assertDatabaseMissing('users', ['id' => $user->id]);
    });

});

describe('bulk delete', function () {

    it('deletes selected teachers', function () {
        $teacher1 = User::factory()->create()->assignRole('teacher');
        $teacher2 = User::factory()->create()->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->set('selectedIds', [$teacher1->id, $teacher2->id])
            ->call('deleteSelected');

        expect(User::find($teacher1->id))->toBeNull();
        expect(User::find($teacher2->id))->toBeNull();
        assertDatabaseMissing('users', ['id' => $teacher1->id]);
        assertDatabaseMissing('users', ['id' => $teacher2->id]);
    });

});
