<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Http\Controllers;

use App\Core\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class MockController extends BaseController
{
    public function respondSuccess(mixed $data = null, string $message = 'Success', int $code = 200, array $extra = []): JsonResponse
    {
        return $this->jsonSuccess($data, $message, $code, $extra);
    }

    public function respondError(string $message = 'Error', int $code = 400, mixed $errors = null, array $extra = []): JsonResponse
    {
        return $this->jsonError($message, $code, $errors, $extra);
    }
}

test('base controller can be extended', function () {
    $controller = new MockController;

    expect($controller)->toBeInstanceOf(BaseController::class);
});

test('json success returns success response', function () {
    $controller = new MockController;
    $response = $controller->respondSuccess(['id' => 1], 'Created', 201);

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->status())->toBe(201);
    expect($response->getData(true))->toEqual([
        'success' => true,
        'message' => 'Created',
        'data' => ['id' => 1],
    ]);
});

test('json success returns default values', function () {
    $controller = new MockController;
    $response = $controller->respondSuccess();

    expect($response->status())->toBe(200);
    expect($response->getData(true))->toEqual([
        'success' => true,
        'message' => 'Success',
    ]);
});

test('json success merges extra data', function () {
    $controller = new MockController;
    $response = $controller->respondSuccess(['id' => 1], 'Success', 200, ['meta' => ['count' => 1]]);

    expect($response->getData(true))->toEqual([
        'success' => true,
        'message' => 'Success',
        'data' => ['id' => 1],
        'meta' => ['count' => 1],
    ]);
});

test('json error returns error response', function () {
    $controller = new MockController;
    $response = $controller->respondError('Not Found', 404, ['id' => 'Not found']);

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->status())->toBe(404);
    expect($response->getData(true))->toEqual([
        'success' => false,
        'message' => 'Not Found',
        'errors' => ['id' => 'Not found'],
    ]);
});

test('json error returns default values', function () {
    $controller = new MockController;
    $response = $controller->respondError();

    expect($response->status())->toBe(400);
    expect($response->getData(true))->toEqual([
        'success' => false,
        'message' => 'Error',
    ]);
});

test('json error merges extra data', function () {
    $controller = new MockController;
    $response = $controller->respondError('Bad Request', 400, null, ['request_id' => 'abc']);

    expect($response->getData(true))->toEqual([
        'success' => false,
        'message' => 'Bad Request',
        'request_id' => 'abc',
    ]);
});

test('json error with null errors omits key', function () {
    $controller = new MockController;
    $response = $controller->respondError('Server Error', 500);

    $data = $response->getData(true);

    expect($data)->not->toHaveKey('errors');
    expect($data['success'])->toBeFalse();
    expect($data['message'])->toBe('Server Error');
});
