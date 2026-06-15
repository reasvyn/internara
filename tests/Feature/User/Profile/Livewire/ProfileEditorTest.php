<?php

declare(strict_types=1);

use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\Profile\Livewire\ProfileEditor;
use App\User\Profile\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()
        ->has(Profile::factory())
        ->create(['status' => AccountStatus::ACTIVATED]);
    $user->assignRole('student');
    test()->actingAs($user);
});

test('renders the profile editor component', function () {
    Livewire::test(ProfileEditor::class)
        ->assertSuccessful()
        ->assertSet('user.email', fn ($email) => $email !== null);
});

test('displays profile form fields', function () {
    Livewire::test(ProfileEditor::class)
        ->assertSet('canChangeName', true);
});

test('updates profile via save action', function () {
    $user = auth()->user();

    Livewire::test(ProfileEditor::class)
        ->set('profileForm.email', 'updated@example.com')
        ->set('profileForm.phone', '08123456789')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->email)->toBe('updated@example.com');
});

test('validates email uniqueness on save', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(ProfileEditor::class)
        ->set('profileForm.email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['profileForm.email']);
});

test('handles avatar upload', function () {
    Livewire::test(ProfileEditor::class)
        ->set('avatar', UploadedFile::fake()->image('avatar.jpg', 100, 100))
        ->assertHasNoErrors();
});

test('removes avatar', function () {
    Livewire::test(ProfileEditor::class)
        ->call('confirmRemoveAvatar')
        ->assertStatus(200);
});
