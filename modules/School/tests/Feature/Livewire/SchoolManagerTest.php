<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\School\Livewire\SchoolManager;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService;
use Modules\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed permissions for testing
    $this->seed(\Modules\Permission\Database\Seeders\PermissionDatabaseSeeder::class);

    // Create a school if not exists (Service handles singleton logic, but for test setup we can factory it)
    if (School::count() === 0) {
        School::factory()->create();
    }
});

test('school settings page is forbidden for unauthorized users', function () {
    $user = User::factory()->create();
    actingAs($user);

    get(route('school.settings'))->assertForbidden();
});

test('school settings page is accessible by authorized users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('school.manage');
    actingAs($user);

    get(route('school.settings'))->assertOk()->assertSee('Data Sekolah');
});

test('it can update school information', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('school.manage');
    actingAs($user);

    $school = app(SchoolService::class)->first();

    Livewire::test(SchoolManager::class)
        ->set('form.name', 'Updated School Name')
        ->set('form.email', 'updated@school.com')
        // Ensure required/validated fields like phone/fax meet min length if not set by factory correctly,
        // but since we updated factory, it should be fine. Just in case, let's explicit.
        ->call('save')
        ->assertHasNoErrors();

    expect($school->refresh())
        ->name->toBe('Updated School Name')
        ->email->toBe('updated@school.com');
});

test('it can upload school logo', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('school.manage');
    actingAs($user);

    $file = UploadedFile::fake()->image('logo.png');
    $school = app(SchoolService::class)->first();

    Livewire::test(SchoolManager::class)
        ->set('form.logo_file', $file)
        ->call('save')
        ->assertHasNoErrors();

    expect($school->refresh()->getFirstMedia(School::COLLECTION_LOGO))->not->toBeNull();
});
