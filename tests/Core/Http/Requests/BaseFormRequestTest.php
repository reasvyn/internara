<?php

declare(strict_types=1);

use App\Core\Exceptions\ValidationFailedException;
use App\Core\Http\Requests\BaseFormRequest;

final class BaseFormRequestTestStub extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }
}

test('SE5Q9-FR-L7: authorize returns true by default', function () {
    expect((new BaseFormRequestTestStub)->authorize())->toBeTrue();
});

test('SE5Q9-FR-L7: rules returns an empty array by default on the base class', function () {
    expect((new class extends BaseFormRequest {})->rules())->toBe([]);
});

test('SE5Q9-FR-L7: failed validation throws ValidationFailedException with errors context', function () {
    $request = BaseFormRequestTestStub::create('/submit', 'POST', ['name' => '']);
    $request->setContainer(app());
    app()->instance('request', $request);

    expect(fn () => $request->validateResolved())
        ->toThrow(function (ValidationFailedException $exception) {
            expect($exception->getMessage())->toBe(__('validation.failed'));
            expect($exception->getHint())->toBe(__('validation.failed_hint'));
            expect($exception->getContext()['errors'])->toHaveKey('name');

            return true;
        });
});

test('SE5Q9-FR-L7: valid input passes validation without exception', function () {
    $request = BaseFormRequestTestStub::create('/submit', 'POST', ['name' => 'valid']);
    $request->setContainer(app());
    app()->instance('request', $request);

    expect(fn () => $request->validateResolved())->not->toThrow(Throwable::class);
});
