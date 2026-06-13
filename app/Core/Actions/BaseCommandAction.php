<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Data\ActionResponse;
use App\Core\Exceptions\RejectedException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

abstract class BaseCommandAction extends BaseAction
{
    protected function respond(mixed $data, ?string $message = null, bool $created = false): ActionResponse
    {
        return $created
            ? ActionResponse::created($data, $message)
            : ActionResponse::ok($data, $message);
    }

    protected function respondDeleted(?string $message = null): ActionResponse
    {
        return ActionResponse::deleted($message);
    }

    protected function respondError(string $message, array $errors = []): ActionResponse
    {
        return ActionResponse::error($message, $errors);
    }

    protected function validate(array $data, array $rules): array
    {
        return Validator::validate($data, $rules);
    }

    protected function authorize(string $ability, mixed $arguments = []): void
    {
        Gate::authorize($ability, $arguments);
    }

    protected function flash(string $message, string $type = 'success'): void
    {
        match ($type) {
            'success' => flash()->success($message),
            'error' => flash()->error($message),
            'warning' => flash()->warning($message),
            'info' => flash()->info($message),
            default => flash()->success($message),
        };
    }

    protected function fail(string $message, array $context = []): never
    {
        throw new RejectedException($message, context: $context);
    }
}
