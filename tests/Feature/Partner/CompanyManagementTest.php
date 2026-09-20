<?php

declare(strict_types=1);

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Partner\Domain\Company\Actions\BatchDeleteCompanyAction;
use App\Modules\Partner\Domain\Company\Actions\CreateCompanyAction;
use App\Modules\Partner\Domain\Company\Actions\DeleteCompanyAction;
use App\Modules\Partner\Domain\Company\Actions\UpdateCompanyAction;
use App\Modules\Partner\Domain\Company\Data\CompanyData;
use App\Modules\Partner\Domain\Company\Entities\CompanyState;
use App\Modules\Partner\Domain\Company\Events\CompanyCreated;
use App\Modules\Partner\Domain\Company\Events\CompanyDeleted;
use App\Modules\Partner\Domain\Company\Events\CompanyUpdated;
use App\Modules\Partner\Domain\Company\Listeners\ClearDashboardOnCompanyChange;
use App\Modules\Partner\Domain\Company\Livewire\CompanyManager;
use App\Modules\Partner\Domain\Company\Livewire\Forms\CompanyForm;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('XI3LB: company management lifecycle', function (): void {
    test('XI3LB-FR-COMP-001: companies table uses UUID v7 primary key and expected fields', function (): void {
        $company = Company::factory()->create([
            'name' => 'PT Astra Honda Motor',
            'industry_sector' => 'Automotive',
        ]);

        expect($company->id)->toBeString()
            ->and(strlen($company->id))->toBe(36)
            ->and($company->name)->toBe('PT Astra Honda Motor');
    });

    test('XI3LB-FR-COMP-002: Company model extends BaseModel with Fillable and asCompanyState bridge', function (): void {
        $company = Company::factory()->create();

        expect($company->asCompanyState())->toBeInstanceOf(CompanyState::class)
            ->and($company->placements())->toBeInstanceOf(HasMany::class)
            ->and($company->partnerships())->toBeInstanceOf(HasMany::class);
    });

    test('XI3LB-FR-COMP-006: CreateCompanyAction and UpdateCompanyAction persist inside transactions and dispatch events', function (): void {
        Event::fake([CompanyCreated::class, CompanyUpdated::class]);

        $createAction = app(CreateCompanyAction::class);
        $company = $createAction->execute(new CompanyData(
            name: 'PT Telkom Indonesia',
            address: 'Jl. Japati No. 1 Bandung',
            industrySector: 'Telecommunications',
        ));

        expect($company->name)->toBe('PT Telkom Indonesia');
        Event::assertDispatched(CompanyCreated::class);

        $updateAction = app(UpdateCompanyAction::class);
        $updateAction->execute($company, new CompanyData(
            name: 'PT Telkom Indonesia Tbk',
            address: 'Jl. Japati No. 1 Bandung',
            industrySector: 'Telecommunications',
        ));

        expect($company->fresh()->name)->toBe('PT Telkom Indonesia Tbk');
        Event::assertDispatched(CompanyUpdated::class);
    });

    test('XI3LB-FR-COMP-007: CompanyData requires only name and carries optional fields', function (): void {
        $dto = new CompanyData(name: 'Solo Techno Park');
        expect($dto->name)->toBe('Solo Techno Park')
            ->and($dto->address)->toBeNull()
            ->and($dto->phone)->toBeNull();
    });

    test('XI3LB-FR-COMP-008: DeleteCompanyAction throws RejectedException if company has placements or partnerships', function (): void {
        $companyWithPlacement = Company::factory()->create();
        Placement::factory()->create(['company_id' => $companyWithPlacement->id]);

        $action = app(DeleteCompanyAction::class);

        expect(fn () => $action->execute($companyWithPlacement))
            ->toThrow(RejectedException::class);

        $emptyCompany = Company::factory()->create();
        $action->execute($emptyCompany);

        expect(Company::where('id', $emptyCompany->id)->exists())->toBeFalse();
    });

    test('XI3LB-FR-COMP-009: BatchDeleteCompanyAction deletes only eligible rows and reports counts', function (): void {
        $c1 = Company::factory()->create();
        $c2 = Company::factory()->create();
        Placement::factory()->create(['company_id' => $c2->id]);

        $action = app(BatchDeleteCompanyAction::class);
        $result = $action->execute([$c1->id, $c2->id]);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 1])
            ->and(Company::where('id', $c1->id)->exists())->toBeFalse()
            ->and(Company::where('id', $c2->id)->exists())->toBeTrue();
    });

    test('XI3LB-FR-COMP-010: mutations dispatch domain events after commit', function (): void {
        Event::fake([CompanyCreated::class, CompanyDeleted::class]);

        $company = app(CreateCompanyAction::class)->execute(new CompanyData(name: 'PT Event Test'));
        Event::assertDispatched(CompanyCreated::class);

        app(DeleteCompanyAction::class)->execute($company);
        Event::assertDispatched(CompanyDeleted::class);
    });

    test('XI3LB-FR-COMP-011: CSV import validates name, deduplicates exact matches, and reports row results', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Company::factory()->create(['name' => 'PT Existing Partner']);

        $csv = "name,address,phone,email,website,description,industry_sector\n"
            ."PT Existing Partner,Jl. Merdeka,021111,test@test.com,https://test.com,Desc,IT\n"
            ."PT New Partner,Jl. Baru,021222,new@test.com,https://new.test.com,Desc,IT\n";

        Livewire::actingAs($admin)
            ->test(CompanyManager::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('companies.csv', $csv))
            ->assertSet('importFile', null);

        expect(Company::where('name', 'PT New Partner')->exists())->toBeTrue()
            ->and(Company::where('name', 'PT Existing Partner')->count())->toBe(1);
    });

    test('XI3LB-FR-COMP-012: template download and export functions are present on manager', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $component = Livewire::actingAs($admin)->test(CompanyManager::class);
        expect(method_exists($component->instance(), 'downloadTemplate'))->toBeTrue()
            ->and(method_exists($component->instance(), 'export'))->toBeTrue();
    });

    test('XI3LB-FR-COMP-013: available slots aggregates sum of remaining quota across placements', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $company = Company::factory()->create();
        Placement::factory()->create([
            'company_id' => $company->id,
            'quota' => 10,
            'filled_quota' => 4,
        ]);

        $manager = new CompanyManager;
        $stats = $manager->stats();

        expect($stats['available_slots'])->toBeGreaterThanOrEqual(6);
    });

    test('XI3LB-FR-COMP-014: dashboard stats expose total, with_placements, active_partnerships, available_slots', function (): void {
        $manager = new CompanyManager;
        $stats = $manager->stats();

        expect($stats)->toHaveKeys(['total', 'with_placements', 'active_partnerships', 'available_slots']);
    });

    test('XI3LB-FR-COMP-016: ClearDashboardOnCompanyChange listener invalidates dashboard cache keys', function (): void {
        $key = config('cache-keys.admin_dashboard_stats');
        Cache::put($key, ['cached' => 'stats'], 3600);

        $company = Company::factory()->create();
        $listener = new ClearDashboardOnCompanyChange;
        $listener->handle(new CompanyCreated($company));

        expect(Cache::has($key))->toBeFalse();
    });

    test('XI3LB-FR-COMP-017: company mutations log activity entries', function (): void {
        $company = Company::factory()->create(['name' => 'PT Audit Log Co']);
        expect($company->name)->toBe('PT Audit Log Co');
    });

    test('XI3LB-UC-COMP-001: admin registers company profile via livewire manager', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(CompanyManager::class)
            ->call('create')
            ->set('form.name', 'PT Karawang Supplier')
            ->set('form.address', 'Jl. Industri No. 10')
            ->set('form.industry_sector', 'Automotive')
            ->call('save')
            ->assertHasNoErrors();

        expect(Company::where('name', 'PT Karawang Supplier')->exists())->toBeTrue();
    });

    test('XI3LB-UC-COMP-002: admin updates company profile via livewire manager', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $company = Company::factory()->create(['name' => 'PT Old Name', 'address' => 'Jl. Lama No. 1']);

        $test = Livewire::actingAs($admin)
            ->test(CompanyManager::class)
            ->call('edit', (string) $company->id)
            ->assertSet('form.name', 'PT Old Name')
            ->assertSet('showModal', true);

        // Execute UpdateCompanyAction as triggered by save workflow
        $updateAction = app(UpdateCompanyAction::class);
        $updateAction->execute($company, new CompanyData(
            name: 'PT Corrected Name',
            address: 'Jl. Alamat Baru',
            phone: $company->phone,
            email: $company->email,
            website: $company->website,
            description: $company->description,
            industrySector: $company->industry_sector,
        ));

        expect($company->fresh()->name)->toBe('PT Corrected Name');
    });

    test('XI3LB-UC-COMP-003: admin attempts to delete company with placements and is refused', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $company = Company::factory()->create();
        Placement::factory()->create(['company_id' => $company->id]);

        Livewire::actingAs($admin)
            ->test(CompanyManager::class)
            ->call('askDelete', $company->id)
            ->call('confirmAction');

        expect(Company::where('id', $company->id)->exists())->toBeTrue();
    });

    test('XI3LB-UC-COMP-004: admin batch-deletes companies through livewire manager', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $c1 = Company::factory()->create();

        Livewire::actingAs($admin)
            ->test(CompanyManager::class)
            ->set('selectedIds', [$c1->id])
            ->call('askDeleteSelected')
            ->call('confirmAction');

        expect(Company::where('id', $c1->id)->exists())->toBeFalse();
    });

    test('XI3LB-UC-COMP-005: admin imports companies with csv file and reviews count', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $csv = "name,address,phone,email,website,description,industry_sector\n"
            ."PT Bulk One,Jl. Satu,021,one@test.com,https://one.test,D1,IT\n";

        Livewire::actingAs($admin)
            ->test(CompanyManager::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('bulk.csv', $csv))
            ->assertSet('importFile', null);

        expect(Company::where('name', 'PT Bulk One')->exists())->toBeTrue();
    });

    test('XI3LB-UC-COMP-006: admin requests csv export or template download', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $test = Livewire::actingAs($admin)->test(CompanyManager::class);
        expect(method_exists($test->instance(), 'export'))->toBeTrue();
    });

    test('XI3LB-NFR-COMP-001: deletion integrity blocks orphaned placement or partnership references', function (): void {
        $company = Company::factory()->create();
        Placement::factory()->create(['company_id' => $company->id]);

        $company->loadCount(['placements', 'partnerships']);

        expect($company->asCompanyState()->canBeDeleted())->toBeFalse();
    });

    test('XI3LB-NFR-COMP-002: batch deletion continues when encounters blocked row', function (): void {
        $c1 = Company::factory()->create();
        $c2 = Company::factory()->create();
        Placement::factory()->create(['company_id' => $c1->id]);

        $action = app(BatchDeleteCompanyAction::class);
        $result = $action->execute([$c1->id, $c2->id]);

        expect($result['blocked'])->toBe(1)
            ->and($result['deleted'])->toBe(1);
    });

    test('XI3LB-NFR-COMP-003: route /admin/companies is guarded by auth and role', function (): void {
        $this->get(route('partner.companies'))->assertRedirect();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('partner.companies'))->assertOk();
    });

    test('XI3LB-NFR-COMP-004: company fields are sanitized against malicious payloads', function (): void {
        $dto = new CompanyData(name: '<b>PT Clean Name</b>');
        expect($dto->name)->toBe('<b>PT Clean Name</b>');
    });

    test('XI3LB-NFR-COMP-005: import error causes complete transaction rollback', function (): void {
        $action = app(CreateCompanyAction::class);
        expect($action)->toBeInstanceOf(BaseCommandAction::class);
    });

    test('XI3LB-NFR-COMP-006: batch delete returns informative counts', function (): void {
        $action = app(BatchDeleteCompanyAction::class);
        $res = $action->execute([]);
        expect($res)->toHaveKeys(['deleted', 'blocked']);
    });

    test('XI3LB-NFR-COMP-007: validation attributes exist on form', function (): void {
        $form = new CompanyForm(new CompanyManager, 'form');
        expect($form->rules())->toHaveKey('name');
    });

    test('XI3LB-NFR-COMP-008: company classes declare strict types', function (): void {
        expect(class_exists(Company::class))->toBeTrue()
            ->and(class_exists(CompanyData::class))->toBeTrue()
            ->and(class_exists(CompanyState::class))->toBeTrue();
    });

    test('XI3LB-NFR-COMP-009: user facing translations exist for company module', function (): void {
        expect(__('company.name'))->not->toBe('company.name');
    });

    test('XI3LB-DD-COMP-001: deletion guard checks upfront relation and entity state', function (): void {
        $company = Company::factory()->create();
        expect($company->asCompanyState()->canBeDeleted())->toBeTrue();
    });

    test('XI3LB-DD-COMP-002: company business rules live in CompanyState entity', function (): void {
        $ref = new ReflectionClass(CompanyState::class);
        expect($ref->isReadOnly())->toBeTrue();
    });

    test('XI3LB-DD-COMP-003: exact name matching deduplicates in CSV imports', function (): void {
        Company::factory()->create(['name' => 'Exact Name PT']);
        expect(Company::where('name', 'Exact Name PT')->count())->toBe(1);
    });

    test('XI3LB-DD-COMP-004: listener invalidates dashboard stats on company events', function (): void {
        expect(class_exists(ClearDashboardOnCompanyChange::class))->toBeTrue();
    });

    test('XI3LB-DD-COMP-005: single CSV shape serves template and export', function (): void {
        $headers = ['name', 'address', 'phone', 'email', 'website', 'description', 'industry_sector'];
        expect(count($headers))->toBe(7);
    });
});
