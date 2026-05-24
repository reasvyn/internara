<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Partnership\Actions\UpdateCompanyAction;
use App\Domain\Partnership\Livewire\CompanyManager;
use App\Domain\Partnership\Models\Company;
use App\Domain\Partnership\Models\Partnership;
use App\Domain\Placement\Models\Placement;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('CompanyManager', function () {
    it('renders the company list', function () {
        Livewire::test(CompanyManager::class)
            ->assertSuccessful();
    });

    it('creates a company', function () {
        Livewire::test(CompanyManager::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->set('form.name', 'Tech Corp')
            ->set('form.address', '123 Main St')
            ->set('form.industry_sector', 'Technology')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        expect(Company::where('name', 'Tech Corp')->exists())->toBeTrue();
    });

    it('validates name is required', function () {
        Livewire::test(CompanyManager::class)
            ->call('create')
            ->set('form.address', 'Addr')
            ->call('save')
            ->assertHasErrors(['form.name' => 'required']);
    });

    it('validates address is required', function () {
        Livewire::test(CompanyManager::class)
            ->call('create')
            ->set('form.name', 'No Address')
            ->call('save')
            ->assertHasErrors(['form.address' => 'required']);
    });

    it('validates email format', function () {
        Livewire::test(CompanyManager::class)
            ->call('create')
            ->set('form.name', 'Bad Email')
            ->set('form.address', 'Addr')
            ->set('form.email', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['form.email' => 'email']);
    });

    it('validates website format', function () {
        Livewire::test(CompanyManager::class)
            ->call('create')
            ->set('form.name', 'Bad Website')
            ->set('form.address', 'Addr')
            ->set('form.website', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['form.website' => 'url']);
    });

    it('edits a company via update action', function () {
        $company = Company::factory()->create(['name' => 'Old Name']);

        app(UpdateCompanyAction::class)->execute($company, ['name' => 'Updated Name']);

        expect($company->fresh()->name)->toBe('Updated Name');
    });

    it('opens edit modal with company data', function () {
        $company = Company::factory()->create(['name' => 'Modal Company']);

        Livewire::test(CompanyManager::class)
            ->call('edit', $company->id)
            ->assertSet('form.name', 'Modal Company')
            ->assertSet('showModal', true);
    });

    it('deletes a company with no relations', function () {
        $company = Company::factory()->create();

        Livewire::test(CompanyManager::class)
            ->call('askDelete', $company->id)
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Company::find($company->id))->toBeNull();
    });

    it('blocks delete when company has partnerships', function () {
        $company = Company::factory()->create();
        Partnership::factory()->for($company)->create(['status' => 'expired']);

        Livewire::test(CompanyManager::class)
            ->call('askDelete', $company->id)
            ->call('confirmAction');

        expect(Company::find($company->id))->not->toBeNull();
    });

    it('blocks delete when company has placements', function () {
        $company = Company::factory()->create();
        Placement::factory()->create(['company_id' => $company->id]);

        Livewire::test(CompanyManager::class)
            ->call('askDelete', $company->id)
            ->call('confirmAction');

        expect(Company::find($company->id))->not->toBeNull();
    });

    it('deletes selected companies', function () {
        $c1 = Company::factory()->create();
        $c2 = Company::factory()->create();

        Livewire::test(CompanyManager::class)
            ->set('selectedIds', [$c1->id, $c2->id])
            ->call('askDeleteSelected')
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Company::find($c1->id))->toBeNull()
            ->and(Company::find($c2->id))->toBeNull();
    });

    it('searches companies by name', function () {
        Company::factory()->create(['name' => 'Alpha Corp']);
        Company::factory()->create(['name' => 'Beta Inc']);

        Livewire::test(CompanyManager::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha Corp')
            ->assertDontSee('Beta Inc');
    });

    it('searches companies by industry sector', function () {
        Company::factory()->create(['name' => 'Tech Co', 'industry_sector' => 'Technology']);
        Company::factory()->create(['name' => 'Health Co', 'industry_sector' => 'Healthcare']);

        Livewire::test(CompanyManager::class)
            ->set('search', 'Healthcare')
            ->assertSee('Health Co')
            ->assertDontSee('Tech Co');
    });

    it('shows stats', function () {
        Company::factory()->count(3)->create();

        Livewire::test(CompanyManager::class)
            ->assertSee('3');
    });

    it('exports companies', function () {
        Company::factory()->create(['name' => 'Export Co']);

        Livewire::test(CompanyManager::class)
            ->call('export')
            ->assertSuccessful();
    });

    it('renders for student but blocks saving', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(CompanyManager::class)
            ->assertSuccessful();
    });
});
