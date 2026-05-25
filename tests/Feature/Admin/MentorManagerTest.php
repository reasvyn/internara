<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\MentorManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('MentorManager', function () {
    it('renders the page', function () {
        Livewire::test(MentorManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(MentorManager::class)
            ->assertForbidden();
    });

    it('creates a mentor', function () {
        Livewire::test(MentorManager::class)
            ->call('create')
            ->set('form.name', 'Mentor Satu')
            ->set('form.email', 'mentor@mitra.id')
            ->set('form.type', Mentor::TYPE_SCHOOL_TEACHER)
            ->set('form.is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        expect(Mentor::whereHas('user', fn ($q) => $q->where('email', 'mentor@mitra.id'))->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(MentorManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('edits a mentor', function () {
        $mentor = Mentor::factory()->schoolTeacher()->create();

        Livewire::test(MentorManager::class)
            ->call('edit', $mentor->id)
            ->set('form.type', Mentor::TYPE_INDUSTRY_SUPERVISOR)
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $mentor->refresh();
        expect($mentor->type)->toBe(Mentor::TYPE_INDUSTRY_SUPERVISOR)
            ->and($mentor->is_active)->toBeFalse();
    });

    it('deletes a mentor', function () {
        $mentor = Mentor::factory()->create();

        Livewire::test(MentorManager::class)
            ->call('delete', $mentor->id)
            ->assertHasNoErrors();

        expect(Mentor::find($mentor->id))->toBeNull();
    });
});
