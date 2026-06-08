<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Http\Requests;

use App\Core\Exceptions\ValidationFailedException;
use App\Core\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Validator;

class MockFormRequest extends BaseFormRequest
{
    public function triggerFailedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $this->failedValidation($validator);
    }
}

test('base form request throws validation failed exception', function () {
    $validator = Validator::make(
        ['email' => 'not-an-email'],
        ['email' => 'required|email'],
    );

    $request = new MockFormRequest;

    expect(fn () => $request->triggerFailedValidation($validator))->toThrow(
        ValidationFailedException::class,
    );
});
