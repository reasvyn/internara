<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;

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
