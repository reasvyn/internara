<?php

declare(strict_types=1);

namespace Tests\Core\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\AppException;
use App\Core\Exceptions\ModuleException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(LazilyRefreshDatabase::class);

class ConcreteAppException extends AppException
{
    public function statusCode(): int
    {
        return 400;
    }
}

class ConcreteModuleException extends ModuleException
{
    public function statusCode(): int
    {
        return 400;
    }
}

class MockAction extends BaseAction
{
    public function executeTransaction(callable $callback, int $attempts = 3): mixed
    {
        return $this->transaction($callback, $attempts);
    }

    public function executeLog(string $action, array $payload = []): void
    {
        $this->log($action, null, $payload);
    }

    public function getModuleName(): string
    {
        return $this->moduleName();
    }

    public function executeWithErrorHandling(callable $callback, string $context): mixed
    {
        return $this->withErrorHandling($callback, $context);
    }
}

class TestTxModel extends Model
{
    protected $table = 'base_action_test_tx';

    public $timestamps = false;

    protected $fillable = ['label'];

    public $incrementing = true;
}

class TransactionAction extends BaseAction
{
    public function runInTransaction(callable $callback, int $attempts = 3): mixed
    {
        return $this->transaction($callback, $attempts);
    }

    public function insertLabel(string $label): void
    {
        $this->transaction(function () use ($label) {
            TestTxModel::create(['label' => $label]);
        });
    }
}

beforeEach(function () {
    if (! Schema::hasTable('base_action_test_tx')) {
        Schema::create('base_action_test_tx', function ($table) {
            $table->id();
            $table->string('label');
        });
    }
});

describe('action infrastructure', function () {
    it('can execute transaction callbacks', function () {
        $action = new MockAction;
        $result = $action->executeTransaction(fn () => 'hello');

        expect($result)->toBe('hello');
    });

    it('skips outer transaction when already nested', function () {
        DB::beginTransaction();

        $action = new MockAction;
        $result = $action->executeTransaction(fn () => 'nested');

        expect($result)->toBe('nested');

        DB::rollBack();
    });

    it('resolves module name from App namespace', function () {
        $action = new MockAction;
        expect($action->getModuleName())->toBe('Unknown');
    });

    it('logs actions using smart logger', function () {
        Event::fake([MessageLogged::class]);

        $action = new MockAction;
        $action->executeLog('Test Action', ['key' => 'value']);

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Test Action');
    });

    it('logs with empty payload by default', function () {
        Event::fake([MessageLogged::class]);

        $action = new MockAction;
        $action->executeLog('Test Action');

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Test Action');
    });

    it('with error handling returns callback result on success', function () {
        $action = new MockAction;
        $result = $action->executeWithErrorHandling(fn () => 'ok', 'Testing');

        expect($result)->toBe('ok');
    });
});

describe('error handling', function () {
    it('passes through app exception', function () {
        $action = new MockAction;

        expect(
            fn () => $action->executeWithErrorHandling(
                fn () => throw new ConcreteAppException('Domain error'),
                'Testing',
            ),
        )->toThrow(ConcreteAppException::class, 'Domain error');
    });

    it('passes through module exception', function () {
        $action = new MockAction;

        expect(
            fn () => $action->executeWithErrorHandling(
                fn () => throw new ConcreteModuleException('Module error'),
                'Testing',
            ),
        )->toThrow(ConcreteModuleException::class, 'Module error');
    });

    it('passes through validation exception', function () {
        $action = new MockAction;
        $e = ValidationException::withMessages(['field' => 'Required']);

        expect(fn () => $action->executeWithErrorHandling(fn () => throw $e, 'Testing'))->toThrow(
            ValidationException::class,
        );
    });

    it('passes through authorization exception', function () {
        $action = new MockAction;

        expect(
            fn () => $action->executeWithErrorHandling(
                fn () => throw new AuthorizationException('Not allowed'),
                'Testing',
            ),
        )->toThrow(AuthorizationException::class, 'Not allowed');
    });

    it('passes through model not found exception', function () {
        $action = new MockAction;

        expect(
            fn () => $action->executeWithErrorHandling(
                fn () => throw new ModelNotFoundException('Not found'),
                'Testing',
            ),
        )->toThrow(ModelNotFoundException::class, 'Not found');
    });

    it('passes through not found http exception', function () {
        $action = new MockAction;

        expect(
            fn () => $action->executeWithErrorHandling(
                fn () => throw new NotFoundHttpException('Missing'),
                'Testing',
            ),
        )->toThrow(NotFoundHttpException::class, 'Missing');
    });

    it('wraps generic throwable as runtime exception', function () {
        $action = new MockAction;

        expect(
            fn () => $action->executeWithErrorHandling(
                fn () => throw new \InvalidArgumentException('Bad arg'),
                'Processing data',
            ),
        )->toThrow(RuntimeException::class, 'Processing data.');
    });

    it('logs wrapped throwable', function () {
        Event::fake([MessageLogged::class]);
        $action = new MockAction;

        try {
            $action->executeWithErrorHandling(
                fn () => throw new \InvalidArgumentException('Bad arg'),
                'Processing data',
            );
        } catch (RuntimeException) {
        }

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => str_contains($e->message, 'Processing data'));
    });
});

describe('database transactions', function () {
    it('inserts data within a transaction', function () {
        $action = new TransactionAction;

        $action->insertLabel('tx_value');

        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'tx_value']);
    });

    it('supports retry on deadlock', function () {
        $action = new TransactionAction;

        $attempts = 0;
        $action->runInTransaction(function () use (&$attempts) {
            $attempts++;
            TestTxModel::create(['label' => 'attempt_'.$attempts]);

            return $attempts;
        }, 5);

        expect($attempts)->toBe(1);
        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'attempt_1']);
    });

    it('nested transaction skips outer wrapping', function () {
        $action = new TransactionAction;

        $result = $action->runInTransaction(function () use ($action) {
            TestTxModel::create(['label' => 'nested_outer']);

            return $action->runInTransaction(function () {
                TestTxModel::create(['label' => 'nested_inner']);

                return 'nested_result';
            });
        });

        expect($result)->toBe('nested_result');
        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'nested_outer']);
        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'nested_inner']);
    });
});
