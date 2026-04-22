<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Internship\Livewire\CompanyManager;
use Modules\Internship\Models\Company;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('CompanyManager Component Authorization', function () {
    test('admin user can access company manager', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Company::factory()->create(['name' => 'PT Teknologi Indonesia']);

        Livewire::test(CompanyManager::class)
            ->assertStatus(200)
            ->assertSee('PT Teknologi Indonesia');
    });

    test('super-admin can access company manager', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $this->actingAs($superAdmin);

        Company::factory()->create(['name' => 'PT Teknologi Indonesia']);

        Livewire::test(CompanyManager::class)
            ->assertStatus(200)
            ->assertSee('PT Teknologi Indonesia');
    });

    test('teacher cannot access company manager', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(CompanyManager::class);
    });

    test('student cannot access company manager', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(CompanyManager::class);
    });
});

describe('CompanyManager CRUD Operations', function () {
    test('admin can create a company', function () {
        session(['setup_authorized' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(CompanyManager::class)
            ->set('form.name', 'PT Baru')
            ->set('form.business_field', 'Technology')
            ->set('form.email', 'contact@ptnew.com')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Baru',
            'business_field' => 'Technology',
        ]);
    });

    test('admin can update a company', function () {
        session(['setup_authorized' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $company = Company::factory()->create(['name' => 'Old Name']);

        Livewire::test(CompanyManager::class)
            ->call('edit', $company->id)
            ->set('form.name', 'New Name')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'New Name',
        ]);
    });

    test('admin can delete a company', function () {
        session(['setup_authorized' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $company = Company::factory()->create();

        Livewire::test(CompanyManager::class)
            ->call('discard', $company->id)
            ->set('recordId', $company->id)
            ->call('remove', $company->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
        ]);
    });
});

describe('CompanyManager Validation', function () {
    test('it validates required company name', function () {
        session(['setup_authorized' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(CompanyManager::class)
            ->set('form.name', '')
            ->call('save')
            ->assertHasErrors('form.name');
    });

    test('it validates email format', function () {
        session(['setup_authorized' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(CompanyManager::class)
            ->set('form.name', 'PT Test')
            ->set('form.email', 'invalid-email')
            ->call('save')
            ->assertHasErrors('form.email');
    });
});
