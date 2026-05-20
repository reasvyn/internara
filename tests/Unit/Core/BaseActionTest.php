<?php

declare(strict_types=1);

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\HandlesActionErrors;
use Illuminate\Database\Eloquent\Model;

class TestActionModel extends Model
{
    protected $fillable = ['name'];
}

class TestAction extends BaseAction
{
    public function execute(mixed ...$params): mixed
    {
        return $this->transaction(function () use ($params) {
            return $params['value'] ?? 'ok';
        });
    }
}

describe('BaseAction', function () {
    it('executes within transaction', function () {
        $action = new TestAction;

        $result = $action->execute(value: 'transaction-result');

        expect($result)->toBe('transaction-result');
    });

    it('is an abstract class', function () {
        $ref = new ReflectionClass(BaseAction::class);

        expect($ref->isAbstract())->toBeTrue();
    });

    it('provides transaction method', function () {
        $ref = new ReflectionClass(BaseAction::class);

        expect($ref->hasMethod('transaction'))->toBeTrue();
    });

    it('uses HandlesActionErrors trait', function () {
        $traits = class_uses(BaseAction::class);

        expect($traits)->toContain(HandlesActionErrors::class);
    });
});
