<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Modules\Core\Data\ActionResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use TallStackUi\Interactions\Toast;

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

    /**
     * @param array<string, list<string>> $errors
     */
    protected function respondError(string $message, array $errors = []): ActionResponse
    {
        return ActionResponse::error($message, $errors);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     *
     * @return array<string, mixed>
     */
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
        $toast = new Toast;

        match ($type) {
            'error' => $toast->error($message),
            'warning' => $toast->warning($message),
            'info' => $toast->info($message),
            default => $toast->success($message),
        };

        $toast->send();
    }
}
