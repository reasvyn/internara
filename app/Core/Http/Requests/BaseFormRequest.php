<?php

declare(strict_types=1);

namespace App\Core\Http\Requests;

use App\Core\Exceptions\ValidationFailedException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;

abstract class BaseFormRequest extends LaravelFormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationFailedException(
            message: __('validation.failed'),
            hint: __('validation.failed_hint'),
            context: ['errors' => $validator->errors()->toArray()],
        );
    }
}
