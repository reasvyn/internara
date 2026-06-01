<?php

declare(strict_types=1);

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\HandlesActionErrors;

class TestBaseAction extends BaseAction
{
    public function execute(mixed ...$params): mixed
    {
        return $this->transaction(fn () => $params['value'] ?? 'ok');
    }
}

describe('BaseAction', function () {
    it('is abstract', function () {
        expect((new ReflectionClass(BaseAction::class))->isAbstract())->toBeTrue();
    });

    it('uses HandlesActionErrors trait', function () {
        expect(class_uses(BaseAction::class))->toContain(HandlesActionErrors::class);
    });

    it('executes callback within transaction', function () {
        $action = new TestBaseAction;

        expect($action->execute(value: 'done'))->toBe('done');
    });
});
