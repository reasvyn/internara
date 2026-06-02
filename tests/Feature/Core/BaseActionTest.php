<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\HandlesActionErrors;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

class TestBaseAction extends BaseAction
{
    public function execute(mixed ...$params): mixed
    {
        return $this->transaction(fn () => $params['value'] ?? 'ok');
    }

    public function exposeLog(string $action, ?array $payload = null): void
    {
        $this->log($action, null, $payload);
    }
}

class TestModuleNameAction extends BaseAction
{
    public function expose(): string
    {
        return $this->moduleName();
    }
}

describe('BaseAction', function () {
    it('is abstract', function () {
        expect((new \ReflectionClass(BaseAction::class))->isAbstract())->toBeTrue();
    });

    it('uses HandlesActionErrors trait', function () {
        expect(class_uses(BaseAction::class))->toContain(HandlesActionErrors::class);
    });

    it('executes callback within transaction', function () {
        $action = new TestBaseAction;

        expect($action->execute(value: 'done'))->toBe('done');
    });

    it('logs without throwing', function () {
        $action = new TestBaseAction;

        $action->exposeLog('test:action', ['key' => 'value']);

        expect(true)->toBeTrue();
    });
});
