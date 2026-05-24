<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Domain\Partnership\Actions\RenewPartnershipAction;
use App\Domain\Partnership\Actions\TerminatePartnershipAction;
use App\Domain\Partnership\Livewire\PartnershipManager;
use App\Domain\Partnership\Models\Company;
use App\Domain\Partnership\Models\Partnership;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('PartnershipManager', function () {
    it('renders the partnership list', function () {
        Livewire::test(PartnershipManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(PartnershipManager::class)
            ->assertForbidden();
    });

    it('creates a partnership', function () {
        $company = Company::factory()->create();

        Livewire::test(PartnershipManager::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->set('form.company_id', $company->id)
            ->set('form.agreement_number', 'AG-'.str()->random(8))
            ->set('form.title', 'Test Agreement')
            ->set('form.start_date', now()->toDateString())
            ->set('form.end_date', now()->addYear()->toDateString())
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        expect(Partnership::where('title', 'Test Agreement')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(PartnershipManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'form.company_id' => 'required',
                'form.agreement_number' => 'required',
                'form.title' => 'required',
            ]);
    });

    it('validates end_date after start_date', function () {
        $company = Company::factory()->create();

        Livewire::test(PartnershipManager::class)
            ->call('create')
            ->set('form.company_id', $company->id)
            ->set('form.agreement_number', 'AG-TEST')
            ->set('form.title', 'Test')
            ->set('form.start_date', '2025-07-01')
            ->set('form.end_date', '2024-06-30')
            ->call('save')
            ->assertHasErrors(['form.end_date' => 'after_or_equal']);
    });

    it('validates agreement_number uniqueness', function () {
        $company = Company::factory()->create();
        Partnership::factory()->for($company)->create(['agreement_number' => 'AG-UNIQUE']);

        Livewire::test(PartnershipManager::class)
            ->call('create')
            ->set('form.company_id', $company->id)
            ->set('form.agreement_number', 'AG-UNIQUE')
            ->set('form.title', 'Duplicate')
            ->set('form.start_date', now()->toDateString())
            ->set('form.end_date', now()->addYear()->toDateString())
            ->call('save')
            ->assertHasErrors(['form.agreement_number' => 'unique']);
    });

    it('opens edit modal with partnership data', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['title' => 'Edit Title']);

        Livewire::test(PartnershipManager::class)
            ->call('edit', $partnership->id)
            ->assertSet('form.title', 'Edit Title')
            ->assertSet('showModal', true);
    });

    it('terminates an active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'active']);

        Livewire::test(PartnershipManager::class)
            ->call('askTerminate', $partnership->id)
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect($partnership->fresh()->status->value)->toBe('terminated');
    });

    it('deletes a non-active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'expired']);

        Livewire::test(PartnershipManager::class)
            ->call('askDelete', $partnership->id)
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Partnership::find($partnership->id))->toBeNull();
    });

    it('blocks delete for active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'active']);

        Livewire::test(PartnershipManager::class)
            ->call('askDelete', $partnership->id)
            ->call('confirmAction');

        expect(Partnership::find($partnership->id))->not->toBeNull();
    });

    it('deletes selected partnerships', function () {
        $company = Company::factory()->create();
        $p1 = Partnership::factory()->for($company)->create(['status' => 'expired']);
        $p2 = Partnership::factory()->for($company)->create(['status' => 'expired']);

        Livewire::test(PartnershipManager::class)
            ->set('selectedIds', [$p1->id, $p2->id])
            ->call('askDeleteSelected')
            ->assertSet('showConfirm', true)
            ->call('confirmAction')
            ->assertSet('showConfirm', false);

        expect(Partnership::find($p1->id))->toBeNull()
            ->and(Partnership::find($p2->id))->toBeNull();
    });

    it('searches partnerships by agreement number', function () {
        $company = Company::factory()->create();
        Partnership::factory()->for($company)->create(['agreement_number' => 'AG-001', 'title' => 'First']);
        Partnership::factory()->for($company)->create(['agreement_number' => 'AG-002', 'title' => 'Second']);

        Livewire::test(PartnershipManager::class)
            ->set('search', 'AG-001')
            ->assertSee('First')
            ->assertDontSee('Second');
    });

    it('searches partnerships by company name', function () {
        $companyA = Company::factory()->create(['name' => 'Alpha Corp']);
        $companyB = Company::factory()->create(['name' => 'Beta Corp']);
        Partnership::factory()->for($companyA)->create(['agreement_number' => 'AG-001', 'title' => 'Alpha Deal']);
        Partnership::factory()->for($companyB)->create(['agreement_number' => 'AG-002', 'title' => 'Beta Deal']);

        Livewire::test(PartnershipManager::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha Deal')
            ->assertDontSee('Beta Deal');
    });

    it('filters by status', function () {
        $company = Company::factory()->create();
        Partnership::factory()->for($company)->create(['status' => 'active', 'agreement_number' => 'AG-ACTIVE']);
        Partnership::factory()->for($company)->create(['status' => 'expired', 'agreement_number' => 'AG-EXPIRED']);

        Livewire::test(PartnershipManager::class)
            ->set('filters.status', 'active')
            ->assertSee('AG-ACTIVE')
            ->assertDontSee('AG-EXPIRED');
    });

    it('shows stats', function () {
        $company = Company::factory()->create();
        Partnership::factory()->for($company)->create(['status' => 'active']);
        Partnership::factory()->for($company)->create(['status' => 'expired']);

        Livewire::test(PartnershipManager::class)
            ->assertSee('1');
    });

    it('exports partnerships', function () {
        $company = Company::factory()->create();
        Partnership::factory()->for($company)->create(['agreement_number' => 'AG-EXPORT']);

        Livewire::test(PartnershipManager::class)
            ->call('export')
            ->assertSuccessful();
    });
});

describe('Partnership Actions', function () {
    it('TerminatePartnershipAction blocks non-active', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'expired']);

        app(TerminatePartnershipAction::class)->execute($partnership);
    })->throws(RejectedException::class);

    it('DeletePartnershipAction blocks active', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'active']);

        app(DeletePartnershipAction::class)->execute($partnership);
    })->throws(RejectedException::class);

    it('RenewPartnershipAction blocks active', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->for($company)->create(['status' => 'active']);

        app(RenewPartnershipAction::class)->execute($partnership, [
            'agreement_number' => 'AG-NEW',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);
    })->throws(RejectedException::class);
});
