<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseController
{
    private const RESERVED_KEYS = ['success', 'message', 'data', 'errors'];

    protected function jsonSuccess(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($this->mergeExtra($response, $extra), $code);
    }

    protected function jsonCreated(
        mixed $data = null,
        string $message = 'Resource created',
        array $extra = [],
    ): JsonResponse {
        return $this->jsonSuccess($data, $message, 201, $extra);
    }

    protected function jsonDeleted(?string $message = 'Resource deleted', array $extra = []): JsonResponse
    {
        return $this->jsonSuccess(null, $message, 200, $extra);
    }

    protected function jsonPaginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];

        return response()->json($this->mergeExtra($response, $extra), 200);
    }

    protected function jsonError(
        string $message = 'Error',
        int $code = 400,
        mixed $errors = null,
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($this->mergeExtra($response, $extra), $code);
    }

    protected function jsonValidationError(
        array $errors,
        string $message = 'Validation failed',
    ): JsonResponse {
        return $this->jsonError($message, 422, $errors);
    }

    protected function jsonNotFound(string $message = 'Resource not found', array $extra = []): JsonResponse
    {
        return $this->jsonError($message, 404, null, $extra);
    }

    protected function jsonForbidden(string $message = 'Forbidden', array $extra = []): JsonResponse
    {
        return $this->jsonError($message, 403, null, $extra);
    }

    private function mergeExtra(array $base, array $extra): array
    {
        foreach ($extra as $key => $value) {
            if (! in_array($key, self::RESERVED_KEYS, true)) {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
