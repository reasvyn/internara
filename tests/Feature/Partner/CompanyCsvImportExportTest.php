<?php

declare(strict_types=1);

use App\Modules\Core\Support\CsvHandler;
use App\Modules\Partner\Domain\Company\Livewire\CompanyManager;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function makeCompanyCsvAdmin(object $test): void
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $test->actingAs($admin);
}

function companyCsvFile(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('companies.csv', $content);
}

function captureCompanyCsv(mixed $response): string
{
    ob_start();

    try {
        $response->sendContent();
    } finally {
        $captured = ob_get_clean();
    }

    return is_string($captured) ? $captured : '';
}

describe('O2KCR: company CSV import and export', function (): void {
    test('O2KCR-FR-CSV-026: company import persists all seven columns through the DTO (covers O2KCR-FR-CSV-028, O2KCR-FR-CSV-062)', function (): void {
        makeCompanyCsvAdmin($this);

        $content = "name,address,phone,email,website,description,industry_sector\n"
            ."PT Maju Jaya,Jl. Merdeka 1,0800111222,info@majujaya.test,https://majujaya.test,Partner setia,Technology\n";

        Livewire::test(CompanyManager::class)
            ->set('importFile', companyCsvFile($content))
            ->assertSet('importFile', null);

        $company = Company::where('name', 'PT Maju Jaya')->firstOrFail();

        expect($company->address)->toBe('Jl. Merdeka 1');
        expect($company->phone)->toBe('0800111222');
        expect($company->email)->toBe('info@majujaya.test');
        expect($company->website)->toBe('https://majujaya.test');
        expect($company->description)->toBe('Partner setia');
        expect($company->industry_sector)->toBe('Technology');
    });

    test('O2KCR-FR-CSV-027: rerunning the company file skips existing names (covers O2KCR-UC-CSV-003, O2KCR-FR-CSV-006)', function (): void {
        makeCompanyCsvAdmin($this);
        $content = "name,address,phone,email,website,description,industry_sector\n"
            ."  PT Maju Jaya  ,Jl. Merdeka 1,0800,info@majujaya.test,,,Technology\n";

        Livewire::test(CompanyManager::class)->set('importFile', companyCsvFile($content));
        expect(Company::where('name', 'PT Maju Jaya')->count())->toBe(1);

        Livewire::test(CompanyManager::class)->set('importFile', companyCsvFile($content));
        expect(Company::count())->toBe(1);
    });

    test('O2KCR-FR-CSV-029: company export mirrors the seven import columns (covers O2KCR-FR-CSV-030)', function (): void {
        makeCompanyCsvAdmin($this);
        Company::factory()->create(['name' => 'PT Maju Jaya']);

        $response = (new CompanyManager)->export(new CsvHandler);

        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="companies.csv"');

        $rows = array_map('str_getcsv', explode("\n", trim(captureCompanyCsv($response))));

        expect($rows[0])->toHaveCount(7);
        expect($rows[1][0])->toBe('PT Maju Jaya');
    });

    test('O2KCR-FR-CSV-012: company exportSelected exports exactly the checked ids (covers O2KCR-FR-CSV-030)', function (): void {
        makeCompanyCsvAdmin($this);
        $kept = Company::factory()->create(['name' => 'PT Maju Jaya']);
        Company::factory()->create(['name' => 'PT Mundur Jaya']);

        $manager = new CompanyManager;
        $manager->selectedIds = [$kept->id];
        $response = $manager->exportSelected(new CsvHandler);

        expect($response)->not->toBeNull();
        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="companies-selected.csv"');

        $body = captureCompanyCsv($response);

        expect($body)->toContain('PT Maju Jaya');
        expect($body)->not->toContain('PT Mundur Jaya');
    });

    test('O2KCR-FR-CSV-009: company import flashes the shared summary with counts', function (): void {
        makeCompanyCsvAdmin($this);

        $content = "name,address,phone,email,website,description,industry_sector\n"
            ."PT Maju Jaya,Jl. Merdeka 1,0800111222,info@majujaya.test,https://majujaya.test,Partner setia,Technology\n"
            ."PT Mundur Jaya,Jl. Mundur 2,0800333444,info@mundurjaya.test,https://mundurjaya.test,Arsip,Retail\n";

        Livewire::test(CompanyManager::class)
            ->set('importFile', companyCsvFile($content))
            ->assertSet('importFile', null)
            ->assertHasNoErrors();

        expect(Company::where('name', 'PT Maju Jaya')->exists())->toBeTrue();
        expect(Company::where('name', 'PT Mundur Jaya')->exists())->toBeTrue();

        $summary = __('common.actions.import_summary', ['created' => 2, 'skipped' => 0]);

        expect($summary)->toContain('2')
            ->and($summary)->not->toBe('common.actions.import_summary');
    });

    test('O2KCR-UC-CSV-012: company template downloads headers plus an example row (covers O2KCR-FR-CSV-030)', function (): void {
        makeCompanyCsvAdmin($this);

        $response = (new CompanyManager)->downloadTemplate(new CsvHandler);

        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="companies-template.csv"');

        $rows = array_map('str_getcsv', explode("\n", trim(captureCompanyCsv($response))));

        expect($rows)->toHaveCount(2);
        expect($rows[0])->toHaveCount(7);
    });
});
