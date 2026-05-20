<?php

declare(strict_types=1);

namespace App\Domain\Core\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithHttp
{
    protected function respond(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function respondSuccess(mixed $data = null, string $message = 'OK'): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    protected function respondCreated(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    protected function respondError(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return new JsonResponse($payload, $status);
    }

    protected function respondNoContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    protected function respondValidationError(string $message, array $errors): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }
}
