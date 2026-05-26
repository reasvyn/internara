<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\School\Livewire\DepartmentManager;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    $this->user = User::factory()->create();
    RoleModel::firstOrCreate(['name' => Role::SUPER_ADMIN->value]);
    $this->user->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->user);

    School::factory()->create();
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('opens create modal', function () {
    Livewire::test(DepartmentManager::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->assertSet('form.name', '')
        ->assertSet('form.description', '');
});

it('creates a department', function () {
    Livewire::test(DepartmentManager::class)
        ->call('create')
        ->set('form.name', 'Teknik Komputer dan Informatika')
        ->set('form.description', 'Department for computer science')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect(Department::where('name', 'Teknik Komputer dan Informatika')->exists())->toBeTrue();
});

it('requires name when creating', function () {
    Livewire::test(DepartmentManager::class)
        ->call('create')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});

it('requires unique name when creating', function () {
    Department::factory()->create(['name' => 'Teknik Komputer']);

    Livewire::test(DepartmentManager::class)
        ->call('create')
        ->set('form.name', 'Teknik Komputer')
        ->call('save')
        ->assertHasErrors('form.name');
});

// ─── Edit ─────────────────────────────────────────────────────────────────────

it('opens edit modal with populated data', function () {
    $department = Department::factory()->create([
        'name' => 'Teknik Mesin',
        'description' => 'Mechanical engineering',
    ]);

    Livewire::test(DepartmentManager::class)
        ->call('edit', $department->id)
        ->assertSet('showModal', true)
        ->assertSet('form.id', $department->id)
        ->assertSet('form.name', 'Teknik Mesin')
        ->assertSet('form.description', 'Mechanical engineering');
});

it('updates a department', function () {
    $department = Department::factory()->create(['name' => 'Teknik Mesin']);

    Livewire::test(DepartmentManager::class)
        ->call('edit', $department->id)
        ->set('form.name', 'Teknik Mesin (Diperbarui)')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect($department->fresh()->name)->toBe('Teknik Mesin (Diperbarui)');
});

it('allows saving with unchanged name on edit', function () {
    $department = Department::factory()->create(['name' => 'Teknik Mesin']);

    Livewire::test(DepartmentManager::class)
        ->call('edit', $department->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);
});

it('rejects duplicate name when editing', function () {
    Department::factory()->create(['name' => 'Teknik Komputer']);
    $department = Department::factory()->create(['name' => 'Teknik Mesin']);

    Livewire::test(DepartmentManager::class)
        ->call('edit', $department->id)
        ->set('form.name', 'Teknik Komputer')
        ->call('save')
        ->assertHasErrors('form.name');
});

// ─── Delete Single ────────────────────────────────────────────────────────────

it('asks confirmation before deleting', function () {
    $department = Department::factory()->create();

    Livewire::test(DepartmentManager::class)
        ->call('askDelete', $department->id)
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'delete')
        ->assertSet('confirmTarget', $department->id);
});

it('deletes a department after confirmation', function () {
    $department = Department::factory()->create();

    Livewire::test(DepartmentManager::class)
        ->call('askDelete', $department->id)
        ->call('confirmAction')
        ->assertSet('showConfirm', false);

    expect(Department::find($department->id))->toBeNull();
});

it('cannot delete department with active profiles', function () {
    $department = Department::factory()->create();
    Profile::factory()->create(['department_id' => $department->id]);

    Livewire::test(DepartmentManager::class)
        ->call('askDelete', $department->id)
        ->call('confirmAction');

    expect(Department::find($department->id))->not->toBeNull();
});

// ─── Bulk Delete ──────────────────────────────────────────────────────────────

it('asks confirmation before bulk delete', function () {
    $departments = Department::factory()->count(3)->create();

    Livewire::test(DepartmentManager::class)
        ->set('selectedIds', $departments->pluck('id')->toArray())
        ->call('askDeleteSelected')
        ->assertSet('showConfirm', true)
        ->assertSet('confirmType', 'delete_selected');
});

it('does nothing on bulk delete when nothing selected', function () {
    Livewire::test(DepartmentManager::class)
        ->call('askDeleteSelected')
        ->assertSet('showConfirm', false);
});

it('deletes selected departments after confirmation', function () {
    $departments = Department::factory()->count(3)->create();

    Livewire::test(DepartmentManager::class)
        ->set('selectedIds', $departments->pluck('id')->toArray())
        ->call('askDeleteSelected')
        ->call('confirmAction')
        ->assertSet('showConfirm', false);

    foreach ($departments as $d) {
        expect(Department::find($d->id))->toBeNull();
    }
});

it('skips departments with profiles during bulk delete', function () {
    $deletable = Department::factory()->create();
    $blocked = Department::factory()->create();
    Profile::factory()->create(['department_id' => $blocked->id]);

    Livewire::test(DepartmentManager::class)
        ->set('selectedIds', [$deletable->id, $blocked->id])
        ->call('askDeleteSelected')
        ->call('confirmAction');

    expect(Department::whereIn('id', [$deletable->id, $blocked->id])->count())->toBe(1);
});

// ─── Export ───────────────────────────────────────────────────────────────────

it('exports all departments without error', function () {
    Department::factory()->count(3)->create();

    Livewire::test(DepartmentManager::class)
        ->call('export')
        ->assertHasNoErrors();
});

it('exports filtered departments by search without error', function () {
    Department::factory()->create(['name' => 'Teknik Komputer']);
    Department::factory()->create(['name' => 'Teknik Mesin']);

    Livewire::test(DepartmentManager::class)
        ->set('search', 'Mesin')
        ->call('export')
        ->assertHasNoErrors();
});

// ─── Export Selected ──────────────────────────────────────────────────────────

it('exports selected departments', function () {
    $departments = Department::factory()->count(3)->create();

    Livewire::test(DepartmentManager::class)
        ->set('selectedIds', $departments->pluck('id')->toArray())
        ->call('exportSelected')
        ->assertHasNoErrors();
});

it('shows warning when exporting with no selection', function () {
    Livewire::test(DepartmentManager::class)
        ->call('exportSelected')
        ->assertHasNoErrors();
});

// ─── Download Template ────────────────────────────────────────────────────────

it('downloads import template', function () {
    Livewire::test(DepartmentManager::class)
        ->call('downloadTemplate')
        ->assertHasNoErrors();
});

// ─── Import ───────────────────────────────────────────────────────────────────

it('imports departments from csv', function () {
    $csv = "name,description\nTeknik Komputer,Computer Science\nTeknik Mesin,Mechanical Engineering";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $component = Livewire::test(DepartmentManager::class);
    $component->set('importFile', $file);

    expect(Department::where('name', 'Teknik Komputer')->exists())->toBeTrue();
    expect(Department::where('name', 'Teknik Mesin')->exists())->toBeTrue();
});

it('skips duplicate names during import', function () {
    Department::factory()->create(['name' => 'Teknik Komputer']);

    $csv = "name,description\nTeknik Komputer,Computer Science\nTeknik Mesin,Mechanical Engineering";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    Livewire::test(DepartmentManager::class)
        ->set('importFile', $file);

    expect(Department::where('name', 'Teknik Komputer')->count())->toBe(1);
    expect(Department::where('name', 'Teknik Mesin')->exists())->toBeTrue();
});

it('skips empty name rows during import', function () {
    $csv = "name,description\n\nTeknik Mesin,Mechanical Engineering";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    Livewire::test(DepartmentManager::class)
        ->set('importFile', $file);

    expect(Department::where('name', 'Teknik Mesin')->exists())->toBeTrue();
    expect(Department::count())->toBe(1);
});

it('rejects invalid csv format', function () {
    $file = UploadedFile::fake()->createWithContent('bad.csv', 'not,csv,format');

    Livewire::test(DepartmentManager::class)
        ->set('importFile', $file)
        ->assertHasNoErrors();
});

// ─── Selection ────────────────────────────────────────────────────────────────

it('selects and clears all visible records', function () {
    Department::factory()->count(3)->create();

    Livewire::test(DepartmentManager::class)
        ->set('selectedIds', ['some-id', 'another-id'])
        ->assertSet('selectedIds', ['some-id', 'another-id'])
        ->call('clearSelection')
        ->assertSet('selectedIds', []);
});

// ─── Search ───────────────────────────────────────────────────────────────────

it('filters departments by search', function () {
    Department::factory()->create(['name' => 'Teknik Komputer']);
    Department::factory()->create(['name' => 'Teknik Mesin']);

    $component = Livewire::test(DepartmentManager::class);

    $component->set('search', 'Komputer');
    $component->assertSet('search', 'Komputer');
});

// ─── Stats ────────────────────────────────────────────────────────────────────

it('renders stats', function () {
    Department::factory()->count(3)->create();

    $component = Livewire::test(DepartmentManager::class);

    $stats = $component->viewData('stats');

    expect($stats['total'])->toBe(3);
});
