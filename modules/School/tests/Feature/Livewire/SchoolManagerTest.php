<?php

declare(strict_types=1);

namespace Modules\School\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\School\Livewire\SchoolManager;
use Modules\School\Models\School;
use Modules\User\Models\User;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('SchoolManager Component', function () {
    test('it renders school data correctly', function () {
        $school = School::factory()->create(['name' => 'SMK Negeri 1 Test', 'npsn' => '12345678']);
        $user = User::factory()->create();
        $user->givePermissionTo('school.manage');
        $this->actingAs($user);

        Livewire::test(SchoolManager::class)
            ->assertStatus(200)
            ->assertSet('form.name', 'SMK Negeri 1 Test');
    });

    test('it can update school information', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('school.manage');
        $this->actingAs($user);

        Livewire::test(SchoolManager::class)
            ->set('form.name', 'Updated School Name')
            ->set('form.email', 'school@test.com')
            ->set('form.npsn', '12345678')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('school_saved');

        $this->assertDatabaseHas('schools', [
            'name' => 'Updated School Name',
            'email' => 'school@test.com',
            'npsn' => '12345678',
        ]);
    });

    test('it allows setup sessions to bypass permissions', function () {
        // No user authenticated, but setup is authorized via session
        session(['setup_authorized' => true]);

        Livewire::test(SchoolManager::class)
            ->set('form.name', 'Setup School')
            ->set('form.npsn', '12345678')
            ->call('save')
            ->assertHasNoErrors()
            ->assertStatus(200);

        expect(School::first()->name)->toBe('Setup School');
    });

    test('it handles institutional logo uploads', function () {
        Storage::fake('public');
        session(['setup_authorized' => true]);

        $logo = UploadedFile::fake()->image('school-logo.png');

        Livewire::test(SchoolManager::class)
            ->set('form.name', 'Branded School')
            ->set('form.npsn', '12345678')
            ->set('form.logo_file', $logo)
            ->call('save')
            ->assertHasNoErrors();

        $school = School::first();
        expect($school->logo_url)->not->toBeNull();
    });
});
