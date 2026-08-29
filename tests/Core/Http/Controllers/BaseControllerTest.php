<?php

declare(strict_types=1);

use App\Modules\Core\Http\Controllers\BaseController;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

final class BaseControllerTestStub extends BaseController
{
    public function success(mixed $data = null, string $message = 'Success', int $code = 200, array $extra = []): JsonResponse
    {
        return $this->jsonSuccess($data, $message, $code, $extra);
    }

    public function created(mixed $data = null, string $message = 'Resource created', array $extra = []): JsonResponse
    {
        return $this->jsonCreated($data, $message, $extra);
    }

    public function deleted(?string $message = 'Resource deleted', array $extra = []): JsonResponse
    {
        return $this->jsonDeleted($message, $extra);
    }

    public function paginated(LengthAwarePaginator $paginator, string $message = 'Success', array $extra = []): JsonResponse
    {
        return $this->jsonPaginated($paginator, $message, $extra);
    }

    public function error(string $message = 'Error', int $code = 400, mixed $errors = null, array $extra = []): JsonResponse
    {
        return $this->jsonError($message, $code, $errors, $extra);
    }

    public function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->jsonValidationError($errors, $message);
    }

    public function notFound(string $message = 'Resource not found', array $extra = []): JsonResponse
    {
        return $this->jsonNotFound($message, $extra);
    }

    public function forbidden(string $message = 'Forbidden', array $extra = []): JsonResponse
    {
        return $this->jsonForbidden($message, $extra);
    }
}

uses(LazilyRefreshDatabase::class);

test('SE5Q9-FR-L6: jsonSuccess returns success payload with data', function () {
    $json = (new BaseControllerTestStub)->success(['id' => 1], 'OK');

    expect($json->status())->toBe(200);
    expect($json->getData(true))->toBe([
        'success' => true,
        'message' => 'OK',
        'data' => ['id' => 1],
    ]);
});

test('SE5Q9-FR-L6: jsonSuccess omits the data key when data is null', function () {
    $payload = (new BaseControllerTestStub)->success()->getData(true);

    expect($payload)->toHaveKeys(['success', 'message']);
    expect($payload)->not->toHaveKey('data');
});

test('SE5Q9-FR-L6: jsonCreated returns 201', function () {
    $json = (new BaseControllerTestStub)->created(['id' => 1]);

    expect($json->status())->toBe(201);
    expect($json->getData(true)['message'])->toBe('Resource created');
});

test('SE5Q9-FR-L6: jsonDeleted returns 200 without a data key', function () {
    $payload = (new BaseControllerTestStub)->deleted()->getData(true);

    expect($payload['success'])->toBeTrue();
    expect($payload['message'])->toBe('Resource deleted');
    expect($payload)->not->toHaveKey('data');
});

test('SE5Q9-FR-L6: jsonPaginated returns pagination metadata', function () {
    User::factory()->count(5)->create();
    $paginator = User::paginate(2);

    $json = (new BaseControllerTestStub)->paginated($paginator);
    $payload = $json->getData(true);

    expect($json->status())->toBe(200);
    expect($payload['data'])->toHaveCount(2);
    expect($payload['meta'])->toBe([
        'current_page' => 1,
        'last_page' => 3,
        'per_page' => 2,
        'total' => 5,
        'from' => 1,
        'to' => 2,
    ]);
});

test('SE5Q9-FR-L6: jsonError returns success false and errors', function () {
    $json = (new BaseControllerTestStub)->error('Bad request', 400, ['name' => ['required']]);

    expect($json->status())->toBe(400);
    expect($json->getData(true))->toBe([
        'success' => false,
        'message' => 'Bad request',
        'errors' => ['name' => ['required']],
    ]);
});

test('SE5Q9-FR-L6: jsonValidationError returns 422', function () {
    $json = (new BaseControllerTestStub)->validationError(['email' => ['invalid']]);

    expect($json->status())->toBe(422);
    expect($json->getData(true)['errors'])->toBe(['email' => ['invalid']]);
});

test('SE5Q9-FR-L6: jsonNotFound and jsonForbidden return 404 and 403', function () {
    expect((new BaseControllerTestStub)->notFound()->status())->toBe(404);
    expect((new BaseControllerTestStub)->forbidden()->status())->toBe(403);
});

test('SE5Q9-FR-L6: extra metadata is merged but reserved keys are protected', function () {
    $payload = (new BaseControllerTestStub)->success(['id' => 1], 'OK', 200, [
        'trace_id' => 'abc',
        'success' => false,
        'data' => 'hijacked',
    ])->getData(true);

    expect($payload['trace_id'])->toBe('abc');
    expect($payload['success'])->toBeTrue();
    expect($payload['data'])->toBe(['id' => 1]);
});
