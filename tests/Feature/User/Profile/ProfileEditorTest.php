<?php

declare(strict_types=1);

use App\User\Enums\Role as RoleEnum;
use App\User\Models\User;
use App\User\Profile\Livewire\ProfileEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('user profile editor mounts and loads data correctly', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'username' => 'janedoe',
    ]);
    $user->assignRole('student');

    Livewire::actingAs($user)
        ->test(ProfileEditor::class)
        ->assertSet('canChangeName', true)
        ->assertSet('canChangeUsername', true)
        ->assertSet('profileForm.name', 'Jane Doe')
        ->assertSet('profileForm.email', 'jane@example.com')
        ->assertSet('profileForm.username', 'janedoe');
});

test('super admin profile editor does not allow name or username changes', function () {
    $superAdmin = User::factory()->create([
        'name' => 'Administrator',
        'email' => 'admin@example.com',
        'username' => 'superadmin',
    ]);
    $superAdmin->assignRole('super_admin');

    Livewire::actingAs($superAdmin)
        ->test(ProfileEditor::class)
        ->assertSet('canChangeName', false)
        ->assertSet('canChangeUsername', false);
});

test('user can save name, email, and username changes', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'username' => 'janedoe',
    ]);
    $user->assignRole('student');

    Livewire::actingAs($user)
        ->test(ProfileEditor::class)
        ->set('profileForm.name', 'Jane Smith')
        ->set('profileForm.email', 'janesmith@example.com')
        ->set('profileForm.username', 'janesmith')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Jane Smith');
    expect($user->email)->toBe('janesmith@example.com');
    expect($user->username)->toBe('janesmith');
});

test('cannot save duplicate username', function () {
    $user1 = User::factory()->create(['username' => 'userone']);
    $user2 = User::factory()->create(['username' => 'usertwo']);
    $user2->assignRole('student');

    Livewire::actingAs($user2)
        ->test(ProfileEditor::class)
        ->set('profileForm.username', 'userone')
        ->call('save')
        ->assertHasErrors(['profileForm.username' => 'unique']);
});

test('can upload avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('student');

    $avatar = UploadedFile::fake()->image('avatar.jpg');

    Livewire::actingAs($user)
        ->test(ProfileEditor::class)
        ->set('avatar', $avatar)
        ->assertHasNoErrors();

    expect($user->fresh()->hasMedia('avatar'))->toBeTrue();
});
