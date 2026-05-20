<?php

declare(strict_types=1);

use App\Domain\Shared\Support\CsvHandler;
use Symfony\Component\HttpFoundation\StreamedResponse;

describe('CsvHandler', function () {
    it('exports CSV as StreamedResponse', function () {
        $handler = new CsvHandler;

        $response = $handler->export(
            collect([['name' => 'Alice', 'age' => 30]]),
            ['name', 'age'],
            fn ($item) => [$item['name'], $item['age']],
        );

        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    it('downloads template CSV', function () {
        $handler = new CsvHandler;

        $response = $handler->downloadTemplate(
            ['name', 'email'],
            ['Example', 'example@test.com'],
        );

        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    it('imports CSV with headers', function () {
        $csvPath = tempnam(sys_get_temp_dir(), 'csv_test');
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, ['name', 'email']);
        fputcsv($handle, ['Alice', 'alice@test.com']);
        fputcsv($handle, ['Bob', 'bob@test.com']);
        fclose($handle);

        $handler = new CsvHandler;
        $result = $handler->import($csvPath, function (array $row) {
            return 'created';
        }, ['name', 'email']);

        unlink($csvPath);

        expect($result['invalid'])->toBeFalse()
            ->and($result['created'])->toBe(2);
    });

    it('returns invalid for mismatched headers', function () {
        $csvPath = tempnam(sys_get_temp_dir(), 'csv_test');
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, ['wrong', 'headers']);
        fclose($handle);

        $handler = new CsvHandler;
        $result = $handler->import($csvPath, fn ($row) => 'created', ['name', 'email']);

        unlink($csvPath);

        expect($result['invalid'])->toBeTrue();
    });

    it('skips null rows from processor', function () {
        $csvPath = tempnam(sys_get_temp_dir(), 'csv_test');
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, ['value']);
        fputcsv($handle, ['keep']);
        fputcsv($handle, ['skip']);
        fclose($handle);

        $handler = new CsvHandler;
        $result = $handler->import($csvPath, function (array $row) {
            return $row[0] === 'skip' ? null : 'created';
        });

        unlink($csvPath);

        expect($result['created'])->toBe(1);
    });

    it('tracks skipped rows', function () {
        $csvPath = tempnam(sys_get_temp_dir(), 'csv_test');
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, ['name']);
        fputcsv($handle, ['Alice']);
        fputcsv($handle, ['Alice']); // duplicate
        fclose($handle);

        $handler = new CsvHandler;
        $result = $handler->import($csvPath, function (array $row) {
            static $seen = [];
            if (in_array($row[0], $seen, true)) {
                return 'skipped';
            }
            $seen[] = $row[0];

            return 'created';
        });

        unlink($csvPath);

        expect($result['created'])->toBe(1)
            ->and($result['skipped'])->toBe(1);
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(CsvHandler::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
