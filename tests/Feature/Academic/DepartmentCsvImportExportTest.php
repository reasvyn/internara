<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Livewire\DepartmentManager;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Support\CsvHandler;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function departmentCsvUpload(string $content, string $name = 'departments.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function captureCsvBody(mixed $response): string
{
    ob_start();

    try {
        $response->sendContent();
    } finally {
        $captured = ob_get_clean();
    }

    return is_string($captured) ? $captured : '';
}

function parseCsvBody(string $body): array
{
    return array_map('str_getcsv', explode("\n", trim($body)));
}

function makeCsvAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

describe('O2KCR: department CSV import and export', function (): void {
    test('O2KCR-FR-CSV-021: department import creates rows from name and description columns', function (): void {
        $this->actingAs(makeCsvAdmin());

        Livewire::test(DepartmentManager::class)
            ->set('importFile', departmentCsvUpload("name,description\nRPL,Software Engineering\nTKJ,Computer Networks\n"))
            ->assertSet('importFile', null);

        expect(Department::where('name', 'RPL')->where('description', 'Software Engineering')->exists())->toBeTrue();
        expect(Department::where('name', 'TKJ')->where('description', 'Computer Networks')->exists())->toBeTrue();
    });

    test('O2KCR-UC-CSV-002: rerunning the same department file skips every row as duplicates (covers O2KCR-FR-CSV-022, O2KCR-FR-CSV-006)', function (): void {
        $this->actingAs(makeCsvAdmin());
        $content = "name,description\nRPL,Software\nTKJ,Networks\n";

        Livewire::test(DepartmentManager::class)->set('importFile', departmentCsvUpload($content));
        expect(Department::count())->toBe(2);

        Livewire::test(DepartmentManager::class)->set('importFile', departmentCsvUpload($content));
        expect(Department::count())->toBe(2);
    });

    test('O2KCR-FR-CSV-023: student upload is refused before any row is processed (covers O2KCR-NFR-CSV-005)', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        expect(Gate::allows('create', Department::class))->toBeFalse();

        $component = Livewire::test(DepartmentManager::class)
            ->set('importFile', departmentCsvUpload("name,description\nRPL,Software\n"));

        expect(Department::count())->toBe(0);
    });

    test('O2KCR-FR-CSV-004: non-csv upload fails validation with zero rows created (covers O2KCR-NFR-CSV-003)', function (): void {
        $this->actingAs(makeCsvAdmin());

        Livewire::test(DepartmentManager::class)
            ->set('importFile', departmentCsvUpload("name,description\nRPL,Software\n", 'departments.pdf'))
            ->assertHasErrors(['importFile']);

        expect(Department::count())->toBe(0);
    });

    test('O2KCR-FR-CSV-014: department import nulls importFile after processing', function (): void {
        $this->actingAs(makeCsvAdmin());

        Livewire::test(DepartmentManager::class)
            ->set('importFile', departmentCsvUpload("name,description\nRPL,Software\n"))
            ->assertSet('importFile', null);

        expect(Department::where('name', 'RPL')->exists())->toBeTrue();
    });

    test('O2KCR-FR-CSV-024: department export streams name and description columns (covers O2KCR-FR-CSV-025, O2KCR-NFR-CSV-012)', function (): void {
        $this->actingAs(makeCsvAdmin());
        Department::factory()->create(['name' => 'RPL', 'description' => 'Software']);

        $response = (new DepartmentManager)->export(new CsvHandler);

        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="departments.csv"');

        $rows = parseCsvBody(captureCsvBody($response));

        expect($rows[0])->toBe(['name', 'description']);
        expect($rows[1])->toBe(['RPL', 'Software']);
    });

    test('O2KCR-FR-CSV-011: department export applies the current search filter (covers O2KCR-UC-CSV-010)', function (): void {
        $this->actingAs(makeCsvAdmin());
        Department::factory()->create(['name' => 'RPL']);
        Department::factory()->create(['name' => 'TKJ']);

        $manager = new DepartmentManager;
        $manager->search = 'RPL';
        $body = captureCsvBody($manager->export(new CsvHandler));

        expect($body)->toContain('RPL');
        expect($body)->not->toContain('TKJ');
    });

    test('O2KCR-FR-CSV-012: department exportSelected exports exactly the checked ids (covers O2KCR-UC-CSV-011)', function (): void {
        $this->actingAs(makeCsvAdmin());
        $kept = Department::factory()->create(['name' => 'RPL']);
        Department::factory()->create(['name' => 'TKJ']);

        $manager = new DepartmentManager;
        $manager->selectedIds = [$kept->id];
        $response = $manager->exportSelected(new CsvHandler);

        expect($response)->not->toBeNull();
        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="departments-selected.csv"');

        $body = captureCsvBody($response);

        expect($body)->toContain('RPL');
        expect($body)->not->toContain('TKJ');
    });

    test('O2KCR-NFR-CSV-008: empty department table exports a headers-only file', function (): void {
        $this->actingAs(makeCsvAdmin());
        expect(Department::count())->toBe(0);

        $rows = parseCsvBody(captureCsvBody((new DepartmentManager)->export(new CsvHandler)));

        expect($rows)->toHaveCount(1);
        expect($rows[0])->toBe(['name', 'description']);
    });

    test('O2KCR-UC-CSV-012: department template downloads headers plus an example row (covers O2KCR-FR-CSV-025)', function (): void {
        $this->actingAs(makeCsvAdmin());

        $response = (new DepartmentManager)->downloadTemplate(new CsvHandler);

        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="departments-template.csv"');

        $rows = parseCsvBody(captureCsvBody($response));

        expect($rows)->toHaveCount(2);
        expect($rows[0])->toBe(['name', 'description']);
        expect($rows[1][0])->not->toBe('');
    });
});
