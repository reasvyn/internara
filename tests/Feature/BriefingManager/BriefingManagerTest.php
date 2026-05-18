<?php

declare(strict_types=1);

use App\Livewire\Internship\BriefingManager;
use App\Models\Briefing;
use App\Models\Internship;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($this->admin);

    $this->internship = Internship::factory()->create();
});

it('creates a briefing session', function () {
    Livewire::test(BriefingManager::class)
        ->call('create')
        ->set('formData.title', 'Pembekalan PKL 2025')
        ->set('formData.date', now()->addDays(7)->format('Y-m-d\TH:i'))
        ->set('formData.internship_id', $this->internship->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Briefing::where('title', 'Pembekalan PKL 2025')->exists())->toBeTrue();
});

it('shows briefing listing', function () {
    Briefing::create([
        'title' => 'Safety Briefing',
        'date' => now()->addDays(3),
        'is_mandatory' => true,
        'internship_id' => $this->internship->id,
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(BriefingManager::class)
        ->assertSuccessful()
        ->assertSee('Safety Briefing');
});
