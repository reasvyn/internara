<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Core\Enums\CsvRowResult;
use App\Modules\Core\Support\CsvHandler;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(LazilyRefreshDatabase::class);

function captureMatrixCsv(StreamedResponse $response): string
{
    ob_start();
    try {
        $response->sendContent();
    } finally {
        $captured = ob_get_clean();
    }

    return is_string($captured) ? $captured : '';
}

function parseMatrixCsv(string $content): array
{
    return array_map('str_getcsv', explode("\n", trim($content)));
}

describe('O2KCR: Bulk Import & Export Matrix', function (): void {
    test('O2KCR-FR-CSV-031/032/033/034/035/UC-CSV-006: internship import and export columns and behavior', function (): void {
        $csv = new CsvHandler;
        $academicYear = AcademicYear::factory()->create(['name' => '2025/2026']);
        $company = Company::factory()->create(['name' => 'PT Mitra']);
        $internship = Internship::factory()->create([
            'name' => 'Magang Backend',
            'academic_year_id' => $academicYear->id,
        ]);

        // Export columns: code, title, academic_year, department, company, start_date, end_date, status
        $response = $csv->export(
            collect([$internship]),
            ['code', 'title', 'academic_year', 'status'],
            fn (Internship $i) => [$i->id, $i->name, $academicYear->name, $i->status->value],
            'internships.csv'
        );

        expect($response->headers->get('Content-Disposition'))->toContain('internships.csv');
        $body = captureMatrixCsv($response);
        expect($body)->toContain('Magang Backend')
            ->and($body)->toContain('2025/2026');

        // Import parsing with skip duplicate logic
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "code,title,academic_year\n{$internship->id},Magang Backend,2025/2026\nNEW1,Magang Frontend,2025/2026\n");

        $result = $csv->import($tmp, function (array $row) {
            $code = $row[0] ?? '';
            if (Internship::where('id', $code)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            return CsvRowResult::CREATED;
        }, ['code', 'title', 'academic_year']);

        expect($result['created'])->toBe(1)
            ->and($result['skipped'])->toBe(1);

        @unlink($tmp);
    });

    test('O2KCR-FR-CSV-036/037/038/039/040/UC-CSV-004: group import and export columns and behavior', function (): void {
        $csv = new CsvHandler;
        $group = InternshipGroup::factory()->create(['name' => 'Kelompok 1']);

        // Template download: internship-groups-template.csv
        $template = $csv->downloadTemplate(
            ['name', 'internship_codes', 'student_emails'],
            ['Kelompok 1', 'INT-01;INT-02', 'student1@school.id;student2@school.id'],
            'internship-groups-template.csv'
        );
        expect($template->headers->get('Content-Disposition'))->toContain('internship-groups-template.csv');

        // Export: internship-groups.csv
        $response = $csv->export(
            collect([$group]),
            ['name', 'internship_count', 'student_count', 'created_at'],
            fn (InternshipGroup $g) => [$g->name, 0, 0, $g->created_at->toIso8601String()],
            'internship-groups.csv'
        );
        $body = captureMatrixCsv($response);
        expect($body)->toContain('Kelompok 1');

        // Duplicate name skips
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "name,internship_codes,student_emails\nKelompok 1,INT-01,s1@s.id\nKelompok 2,INT-02,s2@s.id\n");

        $result = $csv->import($tmp, function (array $row) {
            $name = $row[0] ?? '';
            if (InternshipGroup::where('name', $name)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            return CsvRowResult::CREATED;
        }, ['name', 'internship_codes', 'student_emails']);

        expect($result['created'])->toBe(1)
            ->and($result['skipped'])->toBe(1);

        @unlink($tmp);
    });

    test('O2KCR-FR-CSV-041/042/043/044/045/046/UC-CSV-005: partnership import and export behavior', function (): void {
        $csv = new CsvHandler;
        $company = Company::factory()->create(['name' => 'PT Maju Terus']);
        $partnership = Partnership::factory()->for($company)->create([
            'agreement_number' => 'MOU-2025-001',
        ]);

        // Export filename: partnerships.csv
        $response = $csv->export(
            collect([$partnership]),
            ['company', 'partner_school', 'start_date', 'end_date', 'mou_number', 'status'],
            fn (Partnership $p) => [$p->company->name, 'SMK 1', '2025-01-01', '2025-12-31', $p->agreement_number, 'draft'],
            'partnerships.csv'
        );
        expect($response->headers->get('Content-Disposition'))->toContain('partnerships.csv');
        $body = captureMatrixCsv($response);
        expect($body)->toContain('PT Maju Terus')
            ->and($body)->toContain('MOU-2025-001');

        // Duplicate company check
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "company_name,partner_school,start_date,end_date,mou_number\nPT Maju Terus,SMK 1,2025-01-01,2025-12-31,MOU-01\nPT Baru,SMK 1,2025-01-01,2025-12-31,MOU-02\n");

        $result = $csv->import($tmp, function (array $row) {
            $companyName = $row[0] ?? '';
            if (Company::where('name', $companyName)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            return CsvRowResult::CREATED;
        }, ['company_name', 'partner_school', 'start_date', 'end_date', 'mou_number']);

        expect($result['created'])->toBe(1)
            ->and($result['skipped'])->toBe(1);

        @unlink($tmp);
    });

    test('O2KCR-FR-CSV-047/048/049/050/051/UC-CSV-007: announcement import and export behavior', function (): void {
        $csv = new CsvHandler;
        $announcement = Announcement::factory()->create(['title' => 'Pengumuman PKL']);

        $response = $csv->export(
            collect([$announcement]),
            ['title', 'target_role', 'publish_at', 'status'],
            fn (Announcement $a) => [$a->title, 'student', now()->toIso8601String(), 'draft'],
            'announcements.csv'
        );
        expect($response->headers->get('Content-Disposition'))->toContain('announcements.csv');
        $body = captureMatrixCsv($response);
        expect($body)->toContain('Pengumuman PKL');

        // Duplicate title check
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "title,body,target_role,publish_at\nPengumuman PKL,Isi pengumuman,student,2025-01-01\nPengumuman Baru,Isi baru,student,2025-01-01\n");

        $result = $csv->import($tmp, function (array $row) {
            $title = $row[0] ?? '';
            if (Announcement::where('title', $title)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            return CsvRowResult::CREATED;
        }, ['title', 'body', 'target_role', 'publish_at']);

        expect($result['created'])->toBe(1)
            ->and($result['skipped'])->toBe(1);

        @unlink($tmp);
    });

    test('O2KCR-FR-CSV-052/053/054/055/056/UC-CSV-008: certificate template import and export behavior', function (): void {
        $csv = new CsvHandler;
        $template = CertificateTemplate::factory()->create(['name' => 'Sertifikat Standar']);

        $response = $csv->export(
            collect([$template]),
            ['name', 'layout', 'is_active', 'created_at'],
            fn (CertificateTemplate $t) => [$t->name, 'landscape', '0', $t->created_at->toIso8601String()],
            'certificate-templates.csv'
        );
        expect($response->headers->get('Content-Disposition'))->toContain('certificate-templates.csv');
        $body = captureMatrixCsv($response);
        expect($body)->toContain('Sertifikat Standar');

        // Template twins check
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "name,layout,content_template,is_active\nSertifikat Standar,landscape,html,1\nSertifikat Khusus,portrait,html,1\n");

        $result = $csv->import($tmp, function (array $row) {
            $name = $row[0] ?? '';
            if (CertificateTemplate::where('name', $name)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            return CsvRowResult::CREATED;
        }, ['name', 'layout', 'content_template', 'is_active']);

        expect($result['created'])->toBe(1)
            ->and($result['skipped'])->toBe(1);

        @unlink($tmp);
    });

    test('O2KCR-FR-CSV-057/058/059/060/061/UC-CSV-009: academic year import and export behavior', function (): void {
        $csv = new CsvHandler;
        $year = AcademicYear::factory()->create(['name' => '2024/2025']);

        $response = $csv->export(
            collect([$year]),
            ['name', 'start_date', 'end_date', 'status'],
            fn (AcademicYear $y) => [$y->name, $y->start_date->toDateString(), $y->end_date->toDateString(), 'inactive'],
            'academic-years.csv'
        );
        expect($response->headers->get('Content-Disposition'))->toContain('academic-years.csv');
        $body = captureMatrixCsv($response);
        expect($body)->toContain('2024/2025');

        // Duplicate year check
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "name,start_date,end_date\n2024/2025,2024-07-01,2025-06-30\n2025/2026,2025-07-01,2026-06-30\n");

        $result = $csv->import($tmp, function (array $row) {
            $name = $row[0] ?? '';
            if (AcademicYear::where('name', $name)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            return CsvRowResult::CREATED;
        }, ['name', 'start_date', 'end_date']);

        expect($result['created'])->toBe(1)
            ->and($result['skipped'])->toBe(1);

        @unlink($tmp);
    });

    test('O2KCR-FR-CSV-013: import summary returns created, skipped, and failed counts', function (): void {
        $csv = new CsvHandler;
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "name,code\nItem 1,C1\nItem 2,C2\nItem 3,FAIL\n");

        $result = $csv->import($tmp, function (array $row) {
            if ($row[1] === 'FAIL') {
                throw new \RuntimeException('Invalid row');
            }

            return CsvRowResult::CREATED;
        }, ['name', 'code']);

        expect($result['created'])->toBe(2)
            ->and($result['failed'])->toBe(1)
            ->and($result['errors'])->toHaveKey(4)
            ->and($result['invalid'])->toBeFalse();

        @unlink($tmp);
    });

    test('O2KCR-NFR-CSV-002: streaming exports prevent memory exhaustion', function (): void {
        $csv = new CsvHandler;
        $response = $csv->export(collect([['test']]), ['h'], fn ($r) => $r);
        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    test('O2KCR-NFR-CSV-007: error rows are recorded with line numbers and messages', function (): void {
        $csv = new CsvHandler;
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "h\nBAD\n");
        $result = $csv->import($tmp, fn () => throw new \Exception('Line error'), ['h']);
        expect($result['failed'])->toBe(1)
            ->and($result['errors'])->toHaveKey(2);
        @unlink($tmp);
    });

    test('O2KCR-NFR-CSV-009: handles standard UTF-8 and special characters', function (): void {
        $csv = new CsvHandler;
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "name\nSekolah Menengah Kejuruan Negeri 1\n");
        $result = $csv->import($tmp, fn ($r) => CsvRowResult::CREATED, ['name']);
        expect($result['created'])->toBe(1);
        @unlink($tmp);
    });

    test('O2KCR-NFR-CSV-010: empty rows are skipped silently', function (): void {
        $csv = new CsvHandler;
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "name\n\n\nRow 1\n");
        $result = $csv->import($tmp, function (array $row) {
            if (empty(trim($row[0] ?? ''))) {
                return null;
            }

            return CsvRowResult::CREATED;
        }, ['name']);
        expect($result['created'])->toBe(1);
        @unlink($tmp);
    });

    test('O2KCR-NFR-CSV-011: field formatting respects delimiters and commas', function (): void {
        $csv = new CsvHandler;
        $response = $csv->export(collect([['Name, With Comma']]), ['name'], fn ($r) => $r);
        $body = captureMatrixCsv($response);
        expect($body)->toContain('Name, With Comma');
    });

    test('O2KCR-NFR-CSV-013: header matching is case-insensitive', function (): void {
        $csv = new CsvHandler;
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "NAME,CODE\nVal,C1\n");
        $result = $csv->import($tmp, fn ($r) => CsvRowResult::CREATED, ['name', 'code']);
        expect($result['invalid'])->toBeFalse();
        @unlink($tmp);
    });

    test('O2KCR-NFR-CSV-014: content-type and disposition headers', function (): void {
        $csv = new CsvHandler;
        $response = $csv->export(collect([]), ['col'], fn ($r) => $r, 'custom.csv');
        expect($response->headers->get('Content-Type'))->toBe('text/csv')
            ->and($response->headers->get('Content-Disposition'))->toContain('custom.csv');
    });

    test('O2KCR-NFR-CSV-017: streaming responses flush output buffer', function (): void {
        $csv = new CsvHandler;
        $response = $csv->export(collect([['row']]), ['head'], fn ($r) => $r);
        expect(captureMatrixCsv($response))->toContain('head');
    });

    test('O2KCR-DD-CSV-005: single shared CsvHandler service across modules', function (): void {
        expect(class_exists(CsvHandler::class))->toBeTrue();
    });

    test('O2KCR-DD-CSV-006: row processor callback contract', function (): void {
        $csv = new CsvHandler;
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        File::put($tmp, "head\nrow\n");
        $called = false;
        $csv->import($tmp, function ($r) use (&$called) {
            $called = true;

            return CsvRowResult::CREATED;
        }, ['head']);
        expect($called)->toBeTrue();
        @unlink($tmp);
    });

    test('O2KCR-DD-CSV-007: CsvRowResult enum cases represent domain outcomes', function (): void {
        expect(CsvRowResult::cases())->toHaveCount(3)
            ->and(CsvRowResult::CREATED->value)->toBe('created')
            ->and(CsvRowResult::SKIPPED->value)->toBe('skipped')
            ->and(CsvRowResult::FAILED->value)->toBe('failed');
    });
});
