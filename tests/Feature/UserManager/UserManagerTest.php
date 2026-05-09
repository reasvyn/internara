<?php

declare(strict_types=1);

use App\Enums\Auth\AccountStatus;
use App\Livewire\User\Admin\UserManager;
use App\Models\User;
use App\Notifications\User\AccountStatusNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Super Admin']);
    $this->admin->assignRole('super_admin');

    $this->actingAs($this->admin);
});

describe('rendering', function () {

    it('renders the user manager page', function () {
        User::factory()->count(3)->create();

        Livewire::test(UserManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays users in the table', function () {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        Livewire::test(UserManager::class)
            ->assertSee('Alice')
            ->assertSee('Bob');
    });

});

describe('search', function () {

    it('filters users by name', function () {
        User::factory()->create(['name' => 'Unique Name']);
        User::factory()->create(['name' => 'Other Name']);

        Livewire::test(UserManager::class)
            ->set('search', 'Unique')
            ->assertSee('Unique Name')
            ->assertDontSee('Other Name');
    });

    it('filters users by email', function () {
        User::factory()->create(['email' => 'unique@example.com']);
        User::factory()->create(['email' => 'other@example.com']);

        Livewire::test(UserManager::class)
            ->set('search', 'unique@')
            ->assertSee('unique@example.com')
            ->assertDontSee('other@example.com');
    });

});

describe('create user', function () {

    it('opens the create modal', function () {
        Livewire::test(UserManager::class)
            ->call('createUser')
            ->assertSet('userModal', true)
            ->assertSet('userData.id', null);
    });

    it('creates a new user with roles', function () {
        Livewire::test(UserManager::class)
            ->call('createUser')
            ->set('userData.name', 'New User')
            ->set('userData.email', 'newuser@example.com')
            ->set('userData.password', 'password123')
            ->set('userData.roles', ['admin'])
            ->call('saveUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'newuser@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('New User');
        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('validates required fields on create', function () {
        Livewire::test(UserManager::class)
            ->call('createUser')
            ->call('saveUser')
            ->assertHasErrors([
                'userData.name' => 'required',
                'userData.email' => 'required',
                'userData.roles' => 'required',
                'userData.password' => 'required',
            ]);
    });

    it('validates email uniqueness on create', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(UserManager::class)
            ->call('createUser')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'existing@example.com')
            ->set('userData.password', 'password123')
            ->set('userData.roles', ['admin'])
            ->call('saveUser')
            ->assertHasErrors(['userData.email' => 'unique']);
    });

});

describe('edit user', function () {

    it('opens the edit modal with user data', function () {
        $user = User::factory()->create(['name' => 'Edit Me']);

        Livewire::test(UserManager::class)
            ->call('editUser', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('userData.id', $user->id)
            ->assertSet('userData.name', 'Edit Me');
    });

    it('updates user data', function () {
        $user = User::factory()->create(['name' => 'Before Edit', 'email' => 'before@example.com']);

        Livewire::test(UserManager::class)
            ->call('editUser', $user->id)
            ->set('userData.name', 'After Edit')
            ->set('userData.email', 'edited@example.com')
            ->set('userData.roles', ['admin'])
            ->call('saveUser')
            ->assertHasNoErrors();

        expect($user->fresh()->name)->toBe('After Edit');
        expect($user->fresh()->email)->toBe('edited@example.com');
    });

});

describe('toggle status', function () {

    it('toggles verified user to suspended', function () {
        $user = User::factory()->create();
        $user->setStatus(AccountStatus::VERIFIED);

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $user->id);

        expect($user->fresh()->latestStatus()->name)->toBe(AccountStatus::SUSPENDED->value);
    });

    it('toggles suspended user to verified', function () {
        $user = User::factory()->create();
        $user->setStatus(AccountStatus::SUSPENDED);

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $user->id);

        expect($user->fresh()->latestStatus()->name)->toBe(AccountStatus::VERIFIED->value);
    });

    it('prevents toggling own status', function () {
        Livewire::test(UserManager::class)
            ->call('toggleStatus', $this->admin->id);

        expect($this->admin->fresh()->latestStatus())->toBeNull();
    });

    it('sends notification on status change', function () {
        Notification::fake();
        $user = User::factory()->create();
        $user->setStatus(AccountStatus::VERIFIED);

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $user->id);

        Notification::assertSentTo($user, AccountStatusNotification::class);
    });

});

describe('reset password', function () {

    it('resets user password', function () {
        $user = User::factory()->create();
        $oldPassword = $user->password;

        Livewire::test(UserManager::class)
            ->call('resetPassword', $user->id);

        expect($user->fresh()->password)->not->toBe($oldPassword);
    });

});

describe('delete user', function () {

    it('deletes a user', function () {
        $user = User::factory()->create();

        Livewire::test(UserManager::class)
            ->call('deleteUser', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

    it('prevents self-deletion', function () {
        Livewire::test(UserManager::class)
            ->call('deleteUser', $this->admin->id);

        expect(User::find($this->admin->id))->not->toBeNull();
    });

});

describe('filtering', function () {

    it('filters by role', function () {
        $adminUser = User::factory()->create(['name' => 'Role Admin']);
        $adminUser->assignRole('admin');

        $studentUser = User::factory()->create(['name' => 'Role Student']);
        $studentUser->assignRole('student');

        Livewire::test(UserManager::class)
            ->set('filters.role', 'admin')
            ->assertSee('Role Admin')
            ->assertDontSee('Role Student');
    });

    it('filters by status', function () {
        $verifiedUser = User::factory()->create(['name' => 'Verified User']);
        $verifiedUser->setStatus(AccountStatus::VERIFIED);

        $suspendedUser = User::factory()->create(['name' => 'Suspended User']);
        $suspendedUser->setStatus(AccountStatus::SUSPENDED);

        Livewire::test(UserManager::class)
            ->set('filters.status', 'verified')
            ->assertSee('Verified User')
            ->assertDontSee('Suspended User');
    });

});

describe('bulk delete', function () {

    it('deletes selected users', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Livewire::test(UserManager::class)
            ->set('selectedIds', [$user1->id, $user2->id])
            ->call('deleteSelected');

        expect(User::find($user1->id))->toBeNull();
        expect(User::find($user2->id))->toBeNull();
    });

    it('skips self in bulk delete', function () {
        $other = User::factory()->create();

        Livewire::test(UserManager::class)
            ->set('selectedIds', [$this->admin->id, $other->id])
            ->call('deleteSelected');

        expect(User::find($this->admin->id))->not->toBeNull();
        expect(User::find($other->id))->toBeNull();
    });

});
