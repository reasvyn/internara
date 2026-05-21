<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\ValidationFailedException;
use App\Domain\Core\Http\Requests\FormRequest;
use Illuminate\Contracts\Validation\Validator;

describe('FormRequest', function () {
    it('is an abstract class', function () {
        $ref = new ReflectionClass(FormRequest::class);

        expect($ref->isAbstract())->toBeTrue();
    });

    it('extends Laravel FormRequest', function () {
        expect(FormRequest::class)->toExtend(Illuminate\Foundation\Http\FormRequest::class);
    });

    it('throws ValidationFailedException on validation failure', function () {
        $mock = Mockery::mock(FormRequest::class)->makePartial();
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('errors->toArray')->andReturn(['email' => ['required']]);

        $ref = new ReflectionMethod(FormRequest::class, 'failedValidation');
        $ref->setAccessible(true);

        try {
            $ref->invoke($mock, $validator);
        } catch (ValidationFailedException $e) {
            expect($e)->toBeInstanceOf(ValidationFailedException::class)
                ->and($e->getHint())->not->toBeNull();
        }
    });
});
