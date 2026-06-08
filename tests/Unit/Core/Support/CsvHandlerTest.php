<?php

declare(strict_types=1);

use App\Core\Enums\CsvRowResult;
use App\Core\Support\CsvHandler;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

test('export returns streamed response with correct headers', function () {
    $handler = new CsvHandler;
    $items = new Collection([['name' => 'John', 'age' => 30]]);

    $response = $handler->export(
        items: $items,
        headers: ['Name', 'Age'],
        rowMapper: fn ($item) => [$item['name'], $item['age']],
        filename: 'test.csv',
    );

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
    expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="test.csv"');
});

test('download template returns streamed response', function () {
    $handler = new CsvHandler;

    $response = $handler->downloadTemplate(
        headers: ['Name', 'Email'],
        exampleRow: ['John', 'john@example.com'],
        filename: 'template.csv',
    );

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
    expect($response->headers->get('Content-Disposition'))->toBe(
        'attachment; filename="template.csv"',
    );
});

test('import processes rows and returns counts', function () {
    $handler = new CsvHandler;
    $path = tempnam(sys_get_temp_dir(), 'csv_test_');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Name', 'Email'], escape: '');
    fputcsv($handle, ['John', 'john@test.com'], escape: '');
    fputcsv($handle, ['Jane', 'jane@test.com'], escape: '');
    fclose($handle);

    $result = $handler->import(filePath: $path, rowProcessor: fn ($row) => CsvRowResult::CREATED);

    expect($result)->toBe(['created' => 2, 'skipped' => 0, 'invalid' => false]);

    unlink($path);
});

test('import skips rows when processor returns null', function () {
    $handler = new CsvHandler;
    $path = tempnam(sys_get_temp_dir(), 'csv_test_');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Name'], escape: '');
    fputcsv($handle, ['John'], escape: '');
    fclose($handle);

    $result = $handler->import(filePath: $path, rowProcessor: fn ($row) => null);

    expect($result)->toBe(['created' => 0, 'skipped' => 0, 'invalid' => false]);

    unlink($path);
});

test('import counts skipped rows', function () {
    $handler = new CsvHandler;
    $path = tempnam(sys_get_temp_dir(), 'csv_test_');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Name'], escape: '');
    fputcsv($handle, ['John'], escape: '');
    fputcsv($handle, ['Jane'], escape: '');
    fclose($handle);

    $result = $handler->import(filePath: $path, rowProcessor: fn ($row) => CsvRowResult::SKIPPED);

    expect($result)->toBe(['created' => 0, 'skipped' => 2, 'invalid' => false]);

    unlink($path);
});

test('import validates expected headers', function () {
    $handler = new CsvHandler;
    $path = tempnam(sys_get_temp_dir(), 'csv_test_');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Name', 'Email'], escape: '');
    fputcsv($handle, ['John', 'john@test.com'], escape: '');
    fclose($handle);

    $result = $handler->import(
        filePath: $path,
        rowProcessor: fn ($row) => CsvRowResult::CREATED,
        expectedHeaders: ['Name', 'Email'],
    );

    expect($result['invalid'])->toBeFalse();
    expect($result['created'])->toBe(1);

    unlink($path);
});

test('import returns invalid when headers do not match', function () {
    $handler = new CsvHandler;
    $path = tempnam(sys_get_temp_dir(), 'csv_test_');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Name', 'Email'], escape: '');
    fputcsv($handle, ['John', 'john@test.com'], escape: '');
    fclose($handle);

    $result = $handler->import(
        filePath: $path,
        rowProcessor: fn ($row) => CsvRowResult::CREATED,
        expectedHeaders: ['Full Name', 'Email'],
    );

    expect($result['invalid'])->toBeTrue();
    expect($result['created'])->toBe(0);

    unlink($path);
});

test('import closes file handle when row processor throws', function () {
    $handler = new CsvHandler;
    $path = tempnam(sys_get_temp_dir(), 'csv_test_');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Name'], escape: '');
    fputcsv($handle, ['John'], escape: '');
    fputcsv($handle, ['Jane'], escape: '');
    fclose($handle);

    $caught = false;
    try {
        $handler->import(
            filePath: $path,
            rowProcessor: fn ($row) => throw new \RuntimeException('Processing failed'),
        );
    } catch (\RuntimeException $e) {
        $caught = true;
        expect($e->getMessage())->toBe('Processing failed');
    }

    expect($caught)->toBeTrue();
    unlink($path);
});

test('import header validation is case insensitive', function () {
    $handler = new CsvHandler;
    $path = tempnam(sys_get_temp_dir(), 'csv_test_');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['name', 'email'], escape: '');
    fputcsv($handle, ['John', 'john@test.com'], escape: '');
    fclose($handle);

    $result = $handler->import(
        filePath: $path,
        rowProcessor: fn ($row) => CsvRowResult::CREATED,
        expectedHeaders: ['Name', 'Email'],
    );

    expect($result['invalid'])->toBeFalse();

    unlink($path);
});
