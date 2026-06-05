<?php

declare(strict_types=1);

use App\Core\Exceptions\ValidationFailedException;
use App\Core\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Validator;

test('BaseFormRequest throws ValidationFailedException on failed validation', function () {
    $request = new class extends BaseFormRequest
    {
        public function rules(): array
        {
            return ['name' => 'required'];
        }

        public function authorize(): bool
        {
            return true;
        }
    };

    $validator = mock(Validator::class);
    $validator->shouldReceive('errors->toArray')->andReturn(['name' => ['The name field is required.']]);
    $validator->shouldReceive('failed')->andReturn([]);

    $ref = new ReflectionMethod($request, 'failedValidation');
    $ref->invoke($request, $validator);
})->throws(ValidationFailedException::class);

test('BaseFormRequest extends Laravel FormRequest', function () {
    $ref = new ReflectionClass(BaseFormRequest::class);
    expect($ref->getParentClass()->getName())->toBe('Illuminate\Foundation\Http\FormRequest');
});
