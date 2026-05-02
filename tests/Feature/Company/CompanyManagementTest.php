<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Livewire\Admin\Company\CompanyIndex;
use App\Models\InternshipCompany;
use App\Models\InternshipPlacement;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'admin']);
});

test('company index page is accessible for authenticated admin user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    InternshipCompany::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('admin.companies'))
        ->assertOk()
        ->assertSee(__('company.title'));
});

test('admin can create a new company', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->set('formData.name', 'PT Maju Mundur')
        ->set('formData.address', 'Jl. Contoh No. 123')
        ->set('formData.industry_sector', 'Technology')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('internship_companies', [
        'name' => 'PT Maju Mundur',
        'industry_sector' => 'Technology',
    ]);
});

test('admin can edit an existing company', function () {
    $company = InternshipCompany::factory()->create(['name' => 'Old Name']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->call('edit', $company)
        ->assertSet('formData.name', 'Old Name')
        ->assertSet('showModal', true)
        ->set('formData.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $company->refresh();
    expect($company->name)->toBe('New Name');
});

test('admin cannot delete company with active placements', function () {
    $company = InternshipCompany::factory()->create();
    InternshipPlacement::factory()->create(['company_id' => $company->id]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->call('delete', $company);

    $this->assertDatabaseHas('internship_companies', ['id' => $company->id]);
});

test('admin can delete company without placements', function () {
    $company = InternshipCompany::factory()->create();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->call('delete', $company);

    $this->assertDatabaseMissing('internship_companies', ['id' => $company->id]);
});

test('company name must be unique', function () {
    InternshipCompany::factory()->create(['name' => 'Existing Company']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->call('create')
        ->set('formData.name', 'Existing Company')
        ->set('formData.address', 'Some Address')
        ->call('save')
        ->assertHasErrors(['formData.name' => 'unique']);
});

test('company index shows stats', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    InternshipCompany::factory()->count(2)->create();

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->assertSee(__('company.stats.total'));
});

test('company search filters by name', function () {
    InternshipCompany::factory()->create(['name' => 'Tech Corp']);
    InternshipCompany::factory()->create(['name' => 'Finance Ltd']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->set('search', 'Tech')
        ->assertSee('Tech Corp')
        ->assertDontSee('Finance Ltd');
});

test('unauthenticated user cannot access company index', function () {
    $this->get(route('admin.companies'))
        ->assertRedirect(route('login'));
});

test('company email must be valid format', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->call('create')
        ->set('formData.name', 'Test Company')
        ->set('formData.address', 'Test Address')
        ->set('formData.email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['formData.email' => 'email']);
});

test('company website must be valid url', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(CompanyIndex::class)
        ->call('create')
        ->set('formData.name', 'Test Company')
        ->set('formData.address', 'Test Address')
        ->set('formData.website', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['formData.website' => 'url']);
});
