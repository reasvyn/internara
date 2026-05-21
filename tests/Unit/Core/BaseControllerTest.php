<?php

declare(strict_types=1);

use App\Domain\Core\Http\Controllers\BaseController;

describe('BaseController', function () {
    it('is an abstract class', function () {
        $ref = new ReflectionClass(BaseController::class);

        expect($ref->isAbstract())->toBeTrue();
    });

    it('has no methods', function () {
        $ref = new ReflectionClass(BaseController::class);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

        expect($methods)->toBeEmpty();
    });
});
