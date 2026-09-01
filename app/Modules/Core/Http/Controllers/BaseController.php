<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController
{
    private const RESERVED_KEYS = ['success', 'message', 'data', 'errors'];

    protected function jsonSuccess(
        mixed $data = null,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => __($message),
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
        return $this->jsonSuccess($data, $message, Response::HTTP_CREATED, $extra);
    }

    protected function jsonDeleted(?string $message = 'Resource deleted', array $extra = []): JsonResponse
    {
        return $this->jsonSuccess(null, $message, Response::HTTP_OK, $extra);
    }

    protected function jsonPaginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => __($message),
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

        return response()->json($this->mergeExtra($response, $extra), Response::HTTP_OK);
    }

    protected function jsonError(
        string $message = 'Error',
        int $code = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => __($message),
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
        return $this->jsonError($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    protected function jsonNotFound(string $message = 'Resource not found', array $extra = []): JsonResponse
    {
        return $this->jsonError($message, Response::HTTP_NOT_FOUND, null, $extra);
    }

    protected function jsonForbidden(string $message = 'Forbidden', array $extra = []): JsonResponse
    {
        return $this->jsonError($message, Response::HTTP_FORBIDDEN, null, $extra);
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
