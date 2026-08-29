<?php

declare(strict_types=1);

use App\Modules\Core\Enums\CsvRowResult;
use App\Modules\Core\Support\CsvHandler;

test('C8F0D-FR-SUP3: export streams a CSV with headers and mapped rows', function () {
    $handler = new CsvHandler;
    $response = $handler->export(collect(['Alice', 'Bob']), ['name'], fn (string $name) => [$name], 'users.csv');

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('users.csv');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toBe("name\nAlice\nBob\n");
});

test('C8F0D-FR-SUP3: export escapes values that could break CSV structure', function () {
    $handler = new CsvHandler;
    $response = $handler->export(collect(['a,b; "quoted"']), ['value'], fn (string $value) => [$value]);

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('"a,b; ""quoted"""');
});

test('C8F0D-FR-SUP5: downloadTemplate streams headers and one example row', function () {
    $handler = new CsvHandler;
    $response = $handler->downloadTemplate(['name', 'email'], ['Alice', 'alice@test.com'], 'template.csv');

    expect($response->headers->get('Content-Disposition'))->toContain('template.csv');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toBe("name,email\nAlice,alice@test.com\n");
});

test('C8F0D-FR-SUP4: import counts created and skipped rows', function () {
    $path = sys_get_temp_dir().'/import-'.uniqid('', true).'.csv';
    File::put($path, "name,email\nAlice,alice@test.com\nBob,skip@test.com\nCarol,carol@test.com\n");

    $handler = new CsvHandler;
    $result = $handler->import($path, function (array $row) {
        if ($row === [null]) {
            return null;
        }

        return ($row[1] ?? '') === 'skip@test.com' ? CsvRowResult::SKIPPED : CsvRowResult::CREATED;
    }, ['name', 'email']);

    expect($result)->toBe(['created' => 2, 'skipped' => 1, 'invalid' => false]);

    File::delete($path);
});

test('C8F0D-FR-SUP4: import skips blank rows without counting them', function () {
    $path = sys_get_temp_dir().'/import-'.uniqid('', true).'.csv';
    File::put($path, "name\nAlice\n\nBob\n");

    $handler = new CsvHandler;
    $result = $handler->import($path, function (array $row) {
        return $row === [null] ? null : CsvRowResult::CREATED;
    }, ['name']);

    expect($result)->toBe(['created' => 2, 'skipped' => 0, 'invalid' => false]);

    File::delete($path);
});

test('C8F0D-FR-SUP4: import rejects files whose header row does not match expected headers', function () {
    $path = sys_get_temp_dir().'/import-'.uniqid('', true).'.csv';
    File::put($path, "wrong,nope\nAlice,alice@test.com\n");

    $handler = new CsvHandler;
    $result = $handler->import($path, fn (array $row) => CsvRowResult::CREATED, ['name', 'email']);

    expect($result)->toBe(['created' => 0, 'skipped' => 0, 'invalid' => true]);

    File::delete($path);
});
