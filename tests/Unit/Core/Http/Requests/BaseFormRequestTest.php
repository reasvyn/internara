<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use App\Exceptions\ValidationFailedException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;
use Mockery;

class MockFormRequest extends BaseFormRequest
{
    public function triggerFailedValidation(Validator $validator): void
    {
        $this->failedValidation($validator);
    }
}

test('base form request throws validation failed exception', function () {
    $validator = Mockery::mock(Validator::class);
    $validator->shouldReceive('errors')->andReturn(new MessageBag(['email' => ['Invalid email']]));

    $request = new MockFormRequest;

    expect(fn () => $request->triggerFailedValidation($validator))->toThrow(
        ValidationFailedException::class,
    );
});
