<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Actions;

use App\Core\Actions\BaseAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

class MockAction extends BaseAction
{
    public function executeTransaction(callable $callback): mixed
    {
        return $this->transaction($callback);
    }

    public function executeLog(string $action, ?array $payload = null): void
    {
        $this->log($action, null, $payload);
    }

    public function getModuleName(): string
    {
        return $this->moduleName();
    }
}

test('it can execute transaction callbacks', function () {
    $action = new MockAction;
    $result = $action->executeTransaction(fn () => 'hello');

    expect($result)->toBe('hello');
});

test('it skips outer transaction when already nested', function () {
    DB::shouldReceive('transactionLevel')
        ->once()
        ->andReturn(1);

    $action = new MockAction;
    $result = $action->executeTransaction(fn () => 'nested');

    expect($result)->toBe('nested');
});

test('it can resolve module name from class namespace', function () {
    $action = new MockAction;
    // Class is Tests\Unit\Core\MockAction, so index 1 in parts is 'Unit'
    expect($action->getModuleName())->toBe('Unit');
});

test('it logs actions using smart logger', function () {
    $log = Log::spy();

    $action = new MockAction;
    $action->executeLog('Test Action', ['key' => 'value']);

    $log->shouldHaveReceived('info')
        ->once()
        ->with('Test Action', Mockery::type('array'));
});
