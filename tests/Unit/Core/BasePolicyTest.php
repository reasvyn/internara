<?php

declare(strict_types=1);

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Core\Policies\Concerns\AuthorizesOwnership;
use App\Domain\Core\Policies\Concerns\AuthorizesRoles;

describe('BasePolicy', function () {
    it('uses AuthorizesRoles trait', function () {
        $traits = class_uses(BasePolicy::class);

        expect($traits)->toContain(AuthorizesRoles::class);
    });

    it('uses AuthorizesOwnership trait', function () {
        $traits = class_uses(BasePolicy::class);

        expect($traits)->toContain(AuthorizesOwnership::class);
    });

    it('uses both authorization traits', function () {
        $traits = class_uses(BasePolicy::class);

        expect($traits)->toHaveKey(AuthorizesRoles::class)
            ->toHaveKey(AuthorizesOwnership::class);
    });

    it('is abstract', function () {
        $reflection = new ReflectionClass(BasePolicy::class);

        expect($reflection->isAbstract())->toBeTrue();
    });
});
