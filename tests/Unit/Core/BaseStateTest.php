<?php

declare(strict_types=1);

use App\Domain\Core\States\BaseState;
use Illuminate\Database\Eloquent\Model;

describe('BaseState', function () {
    it('returns a non-empty label by default', function () {
        $state = new class($this->createMock(Model::class)) extends BaseState {};

        expect($state->label())->toBeString()->not->toBeEmpty();
    });

    it('returns false for isTerminal by default', function () {
        $state = new class($this->createMock(Model::class)) extends BaseState {};

        expect($state->isTerminal())->toBeFalse();
    });

    it('returns null for toEnum by default', function () {
        $state = new class($this->createMock(Model::class)) extends BaseState {};

        expect($state->toEnum())->toBeNull();
    });

    it('allows overriding isTerminal in concrete state', function () {
        $state = new class($this->createMock(Model::class)) extends BaseState
        {
            public function isTerminal(): bool
            {
                return true;
            }
        };

        expect($state->isTerminal())->toBeTrue();
    });

    it('allows overriding label in concrete state', function () {
        $state = new class($this->createMock(Model::class)) extends BaseState
        {
            public function label(): string
            {
                return 'Custom Label';
            }
        };

        expect($state->label())->toBe('Custom Label');
    });
});
