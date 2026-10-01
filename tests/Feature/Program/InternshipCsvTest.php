<?php

declare(strict_types=1);

use App\Modules\Core\Support\CsvHandler;
use App\Modules\Program\Domain\Internship\Livewire\InternshipManager;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(LazilyRefreshDatabase::class);

function o2kcrInternshipUpload(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('internships.csv', $content);
}

function o2kcrCaptureInternship(mixed $response): string
{
    ob_start();

    try {
        $response->sendContent();
    } finally {
        $captured = ob_get_clean();
    }

    return is_string($captured) ? $captured : '';
}

function o2kcrInternshipAdmin(object $test): void
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $test->actingAs($admin);
}

describe('O2KCR: internship CSV flows', function (): void {
    test('O2KCR-UC-CSV-010: export streams only the filtered view', function (): void {
        o2kcrInternshipAdmin($this);
        Internship::factory()->create(['name' => 'Magang RPL']);
        Internship::factory()->create(['name' => 'Magang TKJ']);

        $manager = new InternshipManager;
        $manager->search = 'RPL';
        $body = o2kcrCaptureInternship($manager->export(new CsvHandler));

        expect($body)->toContain('Magang RPL')
            ->and($body)->not->toContain('Magang TKJ');
    });

    test('O2KCR-UC-CSV-011: exportSelected streams exactly the checked rows', function (): void {
        o2kcrInternshipAdmin($this);
        $kept = Internship::factory()->create(['name' => 'Magang RPL']);
        Internship::factory()->create(['name' => 'Magang TKJ']);

        $manager = new InternshipManager;
        $manager->selectedIds = [$kept->id];
        $body = o2kcrCaptureInternship($manager->exportSelected(new CsvHandler));

        expect($body)->toContain('Magang RPL')
            ->and($body)->not->toContain('Magang TKJ');
    });

    test('O2KCR-UC-CSV-012: template carries headers plus one example row', function (): void {
        o2kcrInternshipAdmin($this);

        $response = (new InternshipManager)->downloadTemplate(new CsvHandler);
        $rows = array_map('str_getcsv', explode("\n", trim(o2kcrCaptureInternship($response))));

        expect($rows)->toHaveCount(2);
        expect($rows[0])->toBe(['name', 'description', 'status', 'start_date', 'end_date']);
        expect($rows[1][2])->toBe('draft');
    });

    test('O2KCR-DD-CSV-001: rerunning the same internship file skips every duplicate', function (): void {
        o2kcrInternshipAdmin($this);
        $content = "name,description\nMagang RPL,Rekayasa Perangkat Lunak\n";

        Livewire::test(InternshipManager::class)->set('importFile', o2kcrInternshipUpload($content));
        $count = Internship::where('name', 'Magang RPL')->count();

        Livewire::test(InternshipManager::class)->set('importFile', o2kcrInternshipUpload($content));

        expect(Internship::where('name', 'Magang RPL')->count())->toBe($count)
            ->and($count)->toBeGreaterThanOrEqual(1);
    });

    test('O2KCR-DD-CSV-004: import and export share one CsvHandler service', function (): void {
        o2kcrInternshipAdmin($this);
        $csv = new CsvHandler;

        Livewire::test(InternshipManager::class)
            ->set('importFile', o2kcrInternshipUpload("name,description\nMagang RPL,Software\n"))
            ->assertSet('importFile', null);

        $body = o2kcrCaptureInternship((new InternshipManager)->export($csv));

        expect(Internship::where('name', 'Magang RPL')->exists())->toBeTrue()
            ->and($body)->toContain('Magang RPL');
    });

    test('O2KCR-NFR-CSV-001: exports stream with constant-memory responses', function (): void {
        o2kcrInternshipAdmin($this);
        Internship::factory()->create(['name' => 'Magang RPL']);

        $response = (new InternshipManager)->export(new CsvHandler);

        expect($response)->toBeInstanceOf(StreamedResponse::class)
            ->and($response->headers->get('Content-Type'))->toContain('text/csv');
    });

    test('O2KCR-DD-CSV-002: exported file reimports through the streaming reader', function (): void {
        o2kcrInternshipAdmin($this);
        Internship::factory()->create(['name' => 'Magang RPL']);

        $body = o2kcrCaptureInternship((new InternshipManager)->export(new CsvHandler));

        Internship::query()->delete();

        Livewire::test(InternshipManager::class)
            ->set('importFile', o2kcrInternshipUpload($body))
            ->assertSet('importFile', null);

        expect(Internship::where('name', 'Magang RPL')->exists())->toBeTrue();
    });
});
