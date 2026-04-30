<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Models\AcademicYear;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');
});

test('super admin can view academic years', function () {
    $response = $this->actingAs($this->superAdmin)
        ->get(route('admin.academic-years.index'));

    $response->assertOk();
})->todo('Implement academic year index route and view');

test('super admin can create academic year', function () {
    $response = $this->actingAs($this->superAdmin)
        ->post(route('admin.academic-years.store'), [
            'name' => '2026/2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('academic_years', ['name' => '2026/2027']);
})->todo('Implement academic year creation');

test('super admin can activate academic year', function () {
    $year = AcademicYear::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->superAdmin)
        ->post(route('admin.academic-years.activate', $year));

    $response->assertRedirect();
    expect($year->fresh()->is_active)->toBeTrue();
})->todo('Implement academic year activation');

test('only one academic year can be active at a time', function () {
    AcademicYear::factory()->create(['name' => '2025/2026', 'is_active' => true]);
    $newYear = AcademicYear::factory()->create(['name' => '2026/2027', 'is_active' => false]);

    $response = $this->actingAs($this->superAdmin)
        ->post(route('admin.academic-years.activate', $newYear));

    $this->assertDatabaseHas('academic_years', ['name' => '2025/2026', 'is_active' => false]);
    $this->assertDatabaseHas('academic_years', ['name' => '2026/2027', 'is_active' => true]);
})->todo('Implement single active year constraint');
