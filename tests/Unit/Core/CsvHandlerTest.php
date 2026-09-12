<?php

declare(strict_types=1);

use App\Modules\Core\Enums\CsvRowResult;
use App\Modules\Core\Support\CsvHandler;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

function writeTempCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'csv-test-');

    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row, escape: '');
    }

    fclose($handle);

    return $path;
}

function captureStreamedResponse(StreamedResponse $response): string
{
    ob_start();

    try {
        $response->sendContent();
    } finally {
        $captured = ob_get_clean();
    }

    return is_string($captured) ? $captured : '';
}

describe('O2KCR: CsvHandler import', function (): void {
    test('O2KCR-FR-CSV-001: import counts created and skipped rows from a temp file', function (): void {
        $path = writeTempCsv([['name', 'description'], ['RPL', 'Software'], ['', ''], ['TKJ', 'Jaringan']]);

        try {
            $result = (new CsvHandler)->import($path, function (array $row): ?CsvRowResult {
                $name = trim($row[0] ?? '');

                if ($name === '') {
                    return null;
                }

                if ($name === 'TKJ') {
                    return CsvRowResult::SKIPPED;
                }

                return CsvRowResult::CREATED;
            });

            expect($result)->toBe(['created' => 1, 'skipped' => 1, 'failed' => 0, 'errors' => [], 'invalid' => false]);
        } finally {
            unlink($path);
        }
    });

    test('O2KCR-FR-CSV-007: throwing row is recorded as failed with line number and import continues', function (): void {
        $path = writeTempCsv([['name'], ['RPL'], ['TKJ'], ['BAD'], ['MM']]);

        try {
            $result = (new CsvHandler)->import($path, function (array $row): ?CsvRowResult {
                $name = trim($row[0] ?? '');

                if ($name === '') {
                    return null;
                }

                if ($name === 'TKJ') {
                    return CsvRowResult::SKIPPED;
                }

                if ($name === 'BAD') {
                    throw new RuntimeException('validation explosion');
                }

                return CsvRowResult::CREATED;
            });

            expect($result['created'])->toBe(2);
            expect($result['skipped'])->toBe(1);
            expect($result['failed'])->toBe(1);
            expect($result['errors'])->toBe([4 => 'validation explosion']);
            expect($result['invalid'])->toBeFalse();
        } finally {
            unlink($path);
        }
    });

    test('O2KCR-FR-CSV-008: summary carries failed counts with per-line reasons and empty rows stay silent', function (): void {
        $path = writeTempCsv([['name'], ['RPL'], [''], ['TKJ']]);

        try {
            $result = (new CsvHandler)->import($path, function (array $row): ?CsvRowResult {
                $name = trim($row[0] ?? '');

                if ($name === '') {
                    return null;
                }

                return CsvRowResult::CREATED;
            });

            expect($result)->toBe(['created' => 2, 'skipped' => 0, 'failed' => 0, 'errors' => [], 'invalid' => false]);
        } finally {
            unlink($path);
        }
    });

    test('O2KCR-FR-CSV-005: import refuses mismatched headers without processing rows', function (): void {
        $path = writeTempCsv([['wrong', 'columns'], ['RPL', 'Software']]);

        try {
            $processed = 0;
            $result = (new CsvHandler)->import($path, function (array $row) use (&$processed): ?CsvRowResult {
                $processed++;

                return CsvRowResult::CREATED;
            }, ['name', 'description']);

            expect($result)->toBe(['created' => 0, 'skipped' => 0, 'invalid' => true]);
            expect($processed)->toBe(0);
        } finally {
            unlink($path);
        }
    });

    test('O2KCR-FR-CSV-005: import accepts matching headers case-insensitively', function (): void {
        $path = writeTempCsv([['Name', 'Description'], ['RPL', 'Software']]);

        try {
            $result = (new CsvHandler)->import(
                $path,
                fn (array $row): ?CsvRowResult => CsvRowResult::CREATED,
                ['name', 'description'],
            );

            expect($result)->toBe(['created' => 1, 'skipped' => 0, 'failed' => 0, 'errors' => [], 'invalid' => false]);
        } finally {
            unlink($path);
        }
    });
});

describe('O2KCR: CsvHandler export', function (): void {
    test('O2KCR-FR-CSV-002: export streams headers and mapped rows as CSV', function (): void {
        $response = (new CsvHandler)->export(
            new Collection([['name' => 'RPL'], ['name' => 'TKJ']]),
            ['name'],
            fn (array $item): array => [$item['name']],
            'companies.csv',
        );

        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toBe('text/csv');
        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="companies.csv"');

        $body = captureStreamedResponse($response);

        expect($body)->toContain('name');
        expect($body)->toContain('RPL');
        expect($body)->toContain('TKJ');
    });

    test('O2KCR-FR-CSV-003: downloadTemplate streams headers plus the example row', function (): void {
        $response = (new CsvHandler)->downloadTemplate(['name', 'description'], ['RPL', 'Software'], 'template.csv');

        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toBe('text/csv');
        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="template.csv"');

        $body = captureStreamedResponse($response);

        expect($body)->toContain('name');
        expect($body)->toContain('RPL');
        expect($body)->toContain('Software');
    });
});
