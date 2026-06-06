<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class BaseController
{
    protected function jsonSuccess(mixed $data = null, string $message = 'Success', int $code = 200, array $extra = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json(array_merge($response, $extra), $code);
    }

    protected function jsonError(string $message = 'Error', int $code = 400, mixed $errors = null, array $extra = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json(array_merge($response, $extra), $code);
    }
}
