<?php

declare(strict_types=1);

use App\Livewire\Internship\RegistrationCenter;
use App\Models\Internship;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'teacher', 'student', 'supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

it('shows no open registration when none exist', function () {
    Internship::factory()->create(['status' => 'draft']);
    Internship::factory()->create(['status' => 'completed']);
    Internship::factory()->create(['status' => 'cancelled']);

    Livewire::test(RegistrationCenter::class)
        ->assertSee('Tidak Ada Pendaftaran Terbuka');
});

it('shows open registrations when within window', function () {
    Internship::factory()->create([
        'name' => 'PKL Ganjil 2026',
        'status' => 'published',
        'registration_start_date' => now()->subWeek(),
        'registration_end_date' => now()->addWeek(),
    ]);

    Livewire::test(RegistrationCenter::class)
        ->assertDontSee('Tidak Ada Pendaftaran Terbuka')
        ->assertSee('PKL Ganjil 2026');
});

it('hides internships when outside registration window', function () {
    Internship::factory()->create([
        'name' => 'PKL Masa Lalu',
        'status' => 'published',
        'registration_start_date' => now()->subMonth(),
        'registration_end_date' => now()->subWeek(),
    ]);

    Livewire::test(RegistrationCenter::class)
        ->assertSee('Tidak Ada Pendaftaran Terbuka')
        ->assertDontSee('PKL Masa Lalu');
});

it('hides internships when before window opens', function () {
    Internship::factory()->create([
        'name' => 'PKL Mendatang',
        'status' => 'published',
        'registration_start_date' => now()->addWeek(),
        'registration_end_date' => now()->addMonth(),
    ]);

    Livewire::test(RegistrationCenter::class)
        ->assertSee('Tidak Ada Pendaftaran Terbuka')
        ->assertDontSee('PKL Mendatang');
});

it('shows active internships without registration window limits', function () {
    Internship::factory()->create([
        'name' => 'PKL Aktif',
        'status' => 'active',
    ]);

    Livewire::test(RegistrationCenter::class)
        ->assertDontSee('Tidak Ada Pendaftaran Terbuka')
        ->assertSee('PKL Aktif');
});

it('shows register button for guest users', function () {
    Internship::factory()->create([
        'name' => 'PKL Terbuka',
        'status' => 'published',
        'registration_start_date' => now()->subWeek(),
        'registration_end_date' => now()->addWeek(),
    ]);

    Livewire::test(RegistrationCenter::class)
        ->assertSee('Daftar (Belum Punya Akun)');
});

it('shows different button for authenticated student', function () {
    $student = User::factory()->create()->assignRole('student');

    Internship::factory()->create([
        'name' => 'PKL Terbuka',
        'status' => 'published',
        'registration_start_date' => now()->subWeek(),
        'registration_end_date' => now()->addWeek(),
    ]);

    Livewire::actingAs($student)
        ->test(RegistrationCenter::class)
        ->assertSee('Daftar Sekarang');
});

it('redirects root to registration center', function () {
    $this->get('/')
        ->assertRedirect(route('register'));
});
