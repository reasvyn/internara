<?php

declare(strict_types=1);

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Core\Policies\Concerns\AuthorizesOwnership;
use App\Domain\Core\Policies\Concerns\AuthorizesRoles;

test('BasePolicy is abstract', function () {
    $ref = new ReflectionClass(BasePolicy::class);
    expect($ref->isAbstract())->toBeTrue();
});

test('BasePolicy uses AuthorizesRoles trait', function () {
    $traits = (new ReflectionClass(BasePolicy::class))->getTraitNames();
    expect($traits)->toContain(AuthorizesRoles::class);
});

test('BasePolicy uses AuthorizesOwnership trait', function () {
    $traits = (new ReflectionClass(BasePolicy::class))->getTraitNames();
    expect($traits)->toContain(AuthorizesOwnership::class);
});
