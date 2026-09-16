<?php

declare(strict_types=1);

use App\Modules\Core\Enums\CsvRowResult;
use App\Modules\Core\Support\CsvHandler;

function o2kcrWriteTempCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'o2kcr').'.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

describe('O2KCR: row results and malformed rows', function (): void {
    test('O2KCR-DD-CSV-003: row processors report created, skipped, or failed outcomes', function (): void {
        expect(CsvRowResult::cases())->toHaveCount(3);

        $path = o2kcrWriteTempCsv([['name'], ['RPL'], ['TKJ']]);

        try {
            $seen = [];
            $result = (new CsvHandler)->import($path, function (array $row) use (&$seen): ?CsvRowResult {
                if (($row[0] ?? '') === 'name') {
                    return null;
                }

                $seen[] = $row[0];

                return $row[0] === 'RPL' ? CsvRowResult::CREATED : CsvRowResult::SKIPPED;
            });

            expect($result['created'])->toBe(1)
                ->and($result['skipped'])->toBe(1)
                ->and($result['failed'])->toBe(0)
                ->and($result['invalid'])->toBeFalse()
                ->and($seen)->toBe(['RPL', 'TKJ']);
        } finally {
            unlink($path);
        }
    });

    test('O2KCR-NFR-CSV-006: a throwing row fails with its reason while valid rows still land', function (): void {
        $path = o2kcrWriteTempCsv([['name'], ['RPL'], ['BROKEN'], ['TKJ']]);

        try {
            $landed = [];
            $result = (new CsvHandler)->import($path, function (array $row) use (&$landed): ?CsvRowResult {
                if (($row[0] ?? '') === 'name') {
                    return null;
                }

                if ($row[0] === 'BROKEN') {
                    throw new RuntimeException('missing reference: CompanyGJH flats');
                }

                $landed[] = $row[0];

                return CsvRowResult::CREATED;
            });

            expect($result['created'])->toBe(2)
                ->and($result['failed'])->toBe(1)
                ->and($result['errors'][3])->toContain('missing reference')
                ->and($landed)->toBe(['RPL', 'TKJ']);
        } finally {
            unlink($path);
        }
    });

    test('O2KCR-FR-CSV-010: header mismatch aborts with invalid and a distinct error message', function (): void {
        $path = o2kcrWriteTempCsv([['wrong', 'columns'], ['RPL', 'Software']]);

        try {
            $processed = false;
            $result = (new CsvHandler)->import($path, function () use (&$processed): ?CsvRowResult {
                $processed = true;

                return CsvRowResult::CREATED;
            }, ['name', 'description']);

            expect($result['invalid'])->toBeTrue()
                ->and($result['created'])->toBe(0)
                ->and($processed)->toBeFalse()
                ->and(__('common.actions.import_invalid'))->not->toBe('common.actions.import_invalid');
        } finally {
            unlink($path);
        }
    });
});
