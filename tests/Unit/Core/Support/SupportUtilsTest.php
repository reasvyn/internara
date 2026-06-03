<?php

declare(strict_types=1);

use App\Domain\Core\Enums\CsvRowResult;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\Core\Support\CsvHandler;
use App\Domain\Core\Support\HandlesActionErrors;
use App\Domain\Core\Support\Integrity;
use App\Domain\Core\Support\LangChecker;
use App\Domain\Core\Support\PasswordRules;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\ArrayLoader;
use Symfony\Component\HttpFoundation\StreamedResponse;

test('PasswordRules returns expected rules', function () {
    $rules = PasswordRules::default();
    expect($rules)->toBeArray()->toHaveCount(3);
    expect($rules[0])->toBe('required');
    expect($rules[1])->toBe('string');

    $rulesArray = PasswordRules::defaultAsArray();
    expect($rulesArray)->toBeArray()
        ->toContain('required')
        ->toContain('string')
        ->toContain('min:8');
});

test('CacheKeys contains expected constants', function () {
    expect(CacheKeys::SETUP_INSTALLED)->toBe('setup.is_installed');
    expect(CacheKeys::ADMIN_DASHBOARD_STATS)->toBe('admin.dashboard.stats');
    expect(CacheKeys::THEME_CSS_VARIABLES)->toBe('theme.css_variables');
});

test('CsvHandler exports collection as streamed response', function () {
    $handler = new CsvHandler;
    $items = collect([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]);

    $response = $handler->export(
        $items,
        ['Name', 'Email'],
        fn ($item) => [$item['name'], $item['email']],
        'users.csv'
    );

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('users.csv');
});

test('CsvHandler downloads template correctly', function () {
    $handler = new CsvHandler;
    $response = $handler->downloadTemplate(['Name', 'Email'], ['John Doe', 'john@example.com']);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
});

test('CsvHandler imports correctly', function () {
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

test('Integrity verify executes successfully when metadata is valid', function () {
    // Integrity::verify() should execute successfully without errors
    Integrity::verify();
    expect(true)->toBeTrue();
});

test('LangChecker logs missing translation key', function () {
    $loader = new ArrayLoader;
    $checker = new LangChecker($loader, 'en');

    Log::shouldReceive('warning')
        ->once()
        ->with(Mockery::on(fn ($msg) => str_contains($msg, 'Missing translation key: missing.key')), Mockery::any());

    $result = $checker->get('missing.key');
    expect($result)->toBe('missing.key');
});

test('HandlesActionErrors trait wraps execution and rethrows expected exception', function () {
    $classWithTrait = new class
    {
        use HandlesActionErrors;

        public function runSuccess()
        {
            return $this->withErrorHandling(fn () => 'success', 'Success context');
        }

        public function runRuntime()
        {
            return $this->withErrorHandling(function () {
                throw new RuntimeException('Runtime error');
            }, 'Runtime context');
        }

        public function runGeneric()
        {
            return $this->withErrorHandling(function () {
                throw new Exception('Generic error');
            }, 'Generic context');
        }
    };

    expect($classWithTrait->runSuccess())->toBe('success');

    expect(fn () => $classWithTrait->runRuntime())->toThrow(RuntimeException::class, 'Runtime error');

    Log::shouldReceive('error')->once();
    expect(fn () => $classWithTrait->runGeneric())->toThrow(RuntimeException::class, 'Generic context.');
});

test('SmartLogger logs success, info, warning, and error', function () {
    Log::shouldReceive('info')->with('Test success', Mockery::any())->once();
    SmartLogger::success('Test success')->systemOnly()->save();

    Log::shouldReceive('info')->with('Test info', Mockery::any())->once();
    SmartLogger::info('Test info')->systemOnly()->save();

    Log::shouldReceive('warning')->with('Test warning', Mockery::any())->once();
    SmartLogger::warning('Test warning')->systemOnly()->save();

    Log::shouldReceive('error')->with('Test error', Mockery::any())->once();
    SmartLogger::error('Test error')->systemOnly()->save();
});
