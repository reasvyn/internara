<?php

declare(strict_types=1);

namespace Tests\Feature\School;

use App\Domain\School\Models\School;
use App\Domain\User\Models\User;
use App\Livewire\School\SchoolProfile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->school = School::factory()->create();
});

test('school profile page is accessible for authenticated super admin user', function () {
    Role::create(['name' => 'super_admin']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get(route('admin.school'))->assertOk()->assertSee($this->school->name);
});

test('super admin can update school profile', function () {
    Role::create(['name' => 'super_admin']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SchoolProfile::class)
        ->set('name', 'Updated School Name')
        ->set('institutional_code', 'UPDATED123')
        ->set('address', 'Updated Address')
        ->set('principal_name', 'New Principal')
        ->set('email', 'new@school.edu')
        ->set('phone', '+62 21 9999999')
        ->call('save')
        ->assertHasNoErrors();

    $this->school->refresh();

    expect($this->school->name)
        ->toBe('Updated School Name')
        ->and($this->school->institutional_code)
        ->toBe('UPDATED123')
        ->and($this->school->address)
        ->toBe('Updated Address')
        ->and($this->school->principal_name)
        ->toBe('New Principal')
        ->and($this->school->email)
        ->toBe('new@school.edu')
        ->and($this->school->phone)
        ->toBe('+62 21 9999999');
});

test('school profile requires name and institutional code', function () {
    Role::create(['name' => 'super_admin']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SchoolProfile::class)
        ->set('name', '')
        ->set('institutional_code', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'institutional_code' => 'required']);
});

test('school email must be valid email format', function () {
    Role::create(['name' => 'super_admin']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SchoolProfile::class)
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email' => 'email']);
});

test('school institutional code must be unique', function () {
    $anotherSchool = School::factory()->create(['institutional_code' => 'UNIQUE123']);

    Role::create(['name' => 'super_admin']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SchoolProfile::class)
        ->set('institutional_code', 'UNIQUE123')
        ->call('save')
        ->assertHasErrors(['institutional_code' => 'unique']);
});

test('unauthenticated user cannot access school profile', function () {
    $this->get(route('admin.school'))->assertRedirect(route('login'));
});
