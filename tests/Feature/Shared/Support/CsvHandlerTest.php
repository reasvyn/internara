<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\CsvRowResult;
use App\Domain\Shared\Support\CsvHandler;
use Illuminate\Support\Collection;

describe('CsvHandler', function () {

    describe('export', function () {
        it('returns StreamedResponse with correct headers', function () {
            $handler = new CsvHandler;
            $items = new Collection([
                ['name' => 'Alice', 'age' => 25],
            ]);

            $response = $handler->export(
                $items, ['Name', 'Age'],
                fn ($item) => [$item['name'], (string) $item['age']],
                'test.csv',
            );

            expect($response->headers->get('Content-Type'))->toBe('text/csv');
            expect($response->headers->get('Content-Disposition'))->toContain('test.csv');
        });

        it('generates CSV with header and data rows', function () {
            $handler = new CsvHandler;
            $items = new Collection([
                ['name' => 'Alice', 'age' => 25],
                ['name' => 'Bob', 'age' => 30],
            ]);

            $response = $handler->export(
                $items, ['Name', 'Age'],
                fn ($item) => [$item['name'], (string) $item['age']],
            );

            ob_start();
            $response->sendContent();
            $content = ob_get_clean();

            expect($content)->toContain('Name,Age');
            expect($content)->toContain('Alice,25');
            expect($content)->toContain('Bob,30');
        });

        it('handles empty collection', function () {
            $handler = new CsvHandler;

            $response = $handler->export(
                new Collection, ['Name', 'Age'],
                fn ($item) => [],
            );

            ob_start();
            $response->sendContent();
            $content = ob_get_clean();

            expect($content)->toContain('Name,Age');
        });
    });

    describe('downloadTemplate', function () {
        it('returns template CSV with headers and example row', function () {
            $handler = new CsvHandler;

            $response = $handler->downloadTemplate(
                ['Name', 'Email'],
                ['John Doe', 'john@example.com'],
                'template.csv',
            );

            expect($response->headers->get('Content-Type'))->toBe('text/csv');

            ob_start();
            $response->sendContent();
            $content = ob_get_clean();

            expect($content)->toContain('Name,Email');
            expect($content)->toContain('John Doe');
            expect($content)->toContain('john@example.com');
        });
    });

    describe('import', function () {
        it('imports valid CSV and returns counts', function () {
            $handler = new CsvHandler;
            $csv = "name,age\nAlice,25\nBob,30\n";
            $path = tempnam(sys_get_temp_dir(), 'csv');
            file_put_contents($path, $csv);

            $result = $handler->import($path, fn ($row) => CsvRowResult::CREATED);

            unlink($path);

            expect($result['created'])->toBe(2);
            expect($result['skipped'])->toBe(0);
            expect($result['invalid'])->toBeFalse();
        });

        it('validates expected headers', function () {
            $handler = new CsvHandler;
            $csv = "name,age\nAlice,25\n";
            $path = tempnam(sys_get_temp_dir(), 'csv');
            file_put_contents($path, $csv);

            $result = $handler->import($path, fn ($row) => CsvRowResult::CREATED, ['Unexpected', 'Headers']);

            unlink($path);

            expect($result['invalid'])->toBeTrue();
            expect($result['created'])->toBe(0);
        });

        it('validates expected headers (case-insensitive)', function () {
            $handler = new CsvHandler;
            $csv = "Name,Age\nAlice,25\n";
            $path = tempnam(sys_get_temp_dir(), 'csv');
            file_put_contents($path, $csv);

            $result = $handler->import($path, fn ($row) => CsvRowResult::CREATED, ['Name', 'Age']);

            unlink($path);

            expect($result['invalid'])->toBeFalse();
            expect($result['created'])->toBe(1);
        });

        it('counts CREATED and SKIPPED results', function () {
            $handler = new CsvHandler;
            $csv = "value\na\nb\nc\n";
            $path = tempnam(sys_get_temp_dir(), 'csv');
            file_put_contents($path, $csv);

            $i = 0;
            $result = $handler->import($path, function ($row) use (&$i) {
                $i++;

                return $i === 2 ? CsvRowResult::SKIPPED : CsvRowResult::CREATED;
            });

            unlink($path);

            expect($result['created'])->toBe(2);
            expect($result['skipped'])->toBe(1);
        });

        it('skips rows where processor returns null', function () {
            $handler = new CsvHandler;
            $csv = "value\na\nb\nc\n";
            $path = tempnam(sys_get_temp_dir(), 'csv');
            file_put_contents($path, $csv);

            $i = 0;
            $result = $handler->import($path, function ($row) use (&$i) {
                $i++;

                return $i === 2 ? null : CsvRowResult::CREATED;
            });

            unlink($path);

            expect($result['created'])->toBe(2);
            expect($result['skipped'])->toBe(0);
        });
    });
});
