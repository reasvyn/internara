<?php

declare(strict_types=1);

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Core\Policies\Concerns\AuthorizesOwnership;
use App\Domain\Core\Policies\Concerns\AuthorizesRoles;

describe('BasePolicy', function () {
    it('is abstract', function () {
        expect((new ReflectionClass(BasePolicy::class))->isAbstract())->toBeTrue();
    });

    it('bundles AuthorizesRoles trait', function () {
        expect(class_uses(BasePolicy::class))->toContain(AuthorizesRoles::class);
    });

    it('bundles AuthorizesOwnership trait', function () {
        expect(class_uses(BasePolicy::class))->toContain(AuthorizesOwnership::class);
    });
});
