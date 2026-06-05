<?php

declare(strict_types=1);

use App\Core\Enums\CsvRowResult;
use App\Core\Support\CsvHandler;
use Symfony\Component\HttpFoundation\StreamedResponse;

test('export returns streamed response with correct headers', function () {
    $handler = new CsvHandler;
    $items = collect([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]);

    $response = $handler->export(
        $items,
        ['Name', 'Email'],
        fn ($item) => [$item['name'], $item['email']],
        'users.csv',
    );

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('users.csv');
});

test('export handles empty collection', function () {
    $handler = new CsvHandler;
    $response = $handler->export(collect(), ['Name'], fn () => [], 'empty.csv');

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Disposition'))->toContain('empty.csv');
});

test('downloadTemplate returns streamed response', function () {
    $handler = new CsvHandler;
    $response = $handler->downloadTemplate(['Name', 'Email'], ['John Doe', 'john@example.com']);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
});

test('import processes rows correctly', function () {
    $handler = new CsvHandler;
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');

    $handle = fopen($tempFile, 'w');
    fputcsv($handle, ['Name', 'Email']);
    fputcsv($handle, ['Alice', 'alice@example.com']);
    fputcsv($handle, ['Bob', 'bob@example.com']);
    fclose($handle);

    $processed = [];
    $result = $handler->import($tempFile, function ($row) use (&$processed) {
        $processed[] = $row;

        return CsvRowResult::CREATED;
    }, ['Name', 'Email']);

    expect($result)->toEqual([
        'created' => 2,
        'skipped' => 0,
        'invalid' => false,
    ]);
    expect($processed)->toHaveCount(2);
    expect($processed[0][0])->toBe('Alice');

    unlink($tempFile);
});

test('import skips rows when processor returns SKIPPED', function () {
    $handler = new CsvHandler;
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');

    $handle = fopen($tempFile, 'w');
    fputcsv($handle, ['Name']);
    fputcsv($handle, ['Alice']);
    fputcsv($handle, ['Bob']);
    fputcsv($handle, ['Charlie']);
    fclose($handle);

    $result = $handler->import($tempFile, function ($row) {
        return $row[0] === 'Bob' ? CsvRowResult::SKIPPED : CsvRowResult::CREATED;
    }, ['Name']);

    expect($result)->toEqual([
        'created' => 2,
        'skipped' => 1,
        'invalid' => false,
    ]);

    unlink($tempFile);
});

test('import returns invalid when headers mismatch', function () {
    $handler = new CsvHandler;
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');

    $handle = fopen($tempFile, 'w');
    fputcsv($handle, ['WrongHeader']);
    fputcsv($handle, ['Alice']);
    fclose($handle);

    $result = $handler->import($tempFile, fn () => CsvRowResult::CREATED, ['ExpectedHeader']);

    expect($result)->toEqual([
        'created' => 0,
        'skipped' => 0,
        'invalid' => true,
    ]);

    unlink($tempFile);
});

test('import handles null return from processor (skip silently)', function () {
    $handler = new CsvHandler;
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_test');

    $handle = fopen($tempFile, 'w');
    fputcsv($handle, ['Name']);
    fputcsv($handle, ['Alice']);
    fputcsv($handle, ['Bob']);
    fclose($handle);

    $result = $handler->import($tempFile, function ($row) {
        if ($row[0] === 'Alice') {
            return null;
        }

        return CsvRowResult::CREATED;
    }, ['Name']);

    expect($result)->toEqual([
        'created' => 1,
        'skipped' => 0,
        'invalid' => false,
    ]);

    unlink($tempFile);
});
