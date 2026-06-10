<?php

declare(strict_types=1);

namespace App\Core\Data;

use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

final readonly class ActionResponse implements JsonSerializable
{
    private function __construct(
        public bool $success = true,
        public mixed $data = null,
        public ?string $message = null,
        public ?string $redirect = null,
        public array $errors = [],
    ) {}

    public static function ok(mixed $data = null, ?string $message = null): self
    {
        return new self(success: true, data: $data, message: $message);
    }

    public static function created(mixed $data = null, ?string $message = null): self
    {
        return new self(success: true, data: $data, message: $message ?? __('common.created'));
    }

    public static function updated(mixed $data = null, ?string $message = null): self
    {
        return new self(success: true, data: $data, message: $message ?? __('common.updated'));
    }

    public static function deleted(?string $message = null): self
    {
        return new self(success: true, message: $message ?? __('common.deleted'));
    }

    public static function error(string $message, array $errors = []): self
    {
        return new self(success: false, message: $message, errors: $errors);
    }

    public function withRedirect(string $url): self
    {
        return new self(
            success: $this->success,
            data: $this->data,
            message: $this->message,
            redirect: $url,
            errors: $this->errors,
        );
    }

    public function failed(): bool
    {
        return ! $this->success;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'success' => $this->success,
            'data' => $this->data instanceof Model ? $this->data->toArray() : $this->data,
            'message' => $this->message,
            'redirect' => $this->redirect,
            'errors' => $this->errors,
        ], fn (mixed $v) => $v !== null && $v !== []);
    }
}
