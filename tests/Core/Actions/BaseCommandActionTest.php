<?php

declare(strict_types=1);

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class TestCommandAction extends BaseCommandAction
{
    public function execute(): ActionResponse
    {
        return ActionResponse::ok();
    }

    public function doRespond(mixed $data, ?string $message = null, bool $created = false): ActionResponse
    {
        return $this->respond($data, $message, $created);
    }

    public function doRespondDeleted(?string $message = null): ActionResponse
    {
        return $this->respondDeleted($message);
    }

    public function doRespondError(string $message, array $errors = []): ActionResponse
    {
        return $this->respondError($message, $errors);
    }

    public function doValidate(array $data, array $rules): array
    {
        return $this->validate($data, $rules);
    }

    public function doAuthorize(string $ability, mixed $arguments = []): void
    {
        $this->authorize($ability, $arguments);
    }

    public function doFlash(string $message, string $type = 'success'): void
    {
        $this->flash($message, $type);
    }
}

it('FR-A2: respond() returns ok() or created() based on the flag', function () {
    $action = new TestCommandAction;

    expect($action->doRespond('data'))->toBeInstanceOf(ActionResponse::class);
    expect($action->doRespond('data')->success)->toBeTrue();
    expect($action->doRespond('data', null, true)->message)->toBe(__('common.created'));
    expect($action->doRespond('data', 'Custom message')->message)->toBe('Custom message');
});

it('FR-A2: respondDeleted() returns a deleted response', function () {
    $response = (new TestCommandAction)->doRespondDeleted();

    expect($response->success)->toBeTrue();
    expect($response->message)->toBe(__('common.deleted'));
});

it('FR-A2: respondError() returns a failed response with errors', function () {
    $response = (new TestCommandAction)->doRespondError('Nope', ['name' => ['required']]);

    expect($response->failed())->toBeTrue();
    expect($response->errors)->toBe(['name' => ['required']]);
});

it('FR-A2: validate() validates data and throws ValidationException on failure', function () {
    $action = new TestCommandAction;

    expect($action->doValidate(['name' => 'John'], ['name' => 'required']))->toBe(['name' => 'John']);
    expect(fn () => $action->doValidate([], ['name' => 'required']))->toThrow(ValidationException::class);
});

it('FR-A2: authorize() throws AuthorizationException when denied', function () {
    expect(fn () => (new TestCommandAction)->doAuthorize('missing.ability'))->toThrow(AuthorizationException::class);
});

it('FR-A2: flash() queues success, error, warning and info notifications', function () {
    $action = new TestCommandAction;

    foreach (['success', 'error', 'warning', 'info'] as $type) {
        expect(fn () => $action->doFlash('Message', $type))->not->toThrow(Throwable::class);
    }
});
