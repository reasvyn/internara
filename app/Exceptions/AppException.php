<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

abstract class AppException extends RuntimeException
{
    protected ?string $hint = null;

    /** @var array<string, mixed> */
    protected array $context = [];

    /**
     * Set a user-friendly hint or resolution suggestion.
     */
    public function withHint(?string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    /**
     * Set contextual data for logging/debugging.
     *
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Format the exception for CLI output.
     */
    public function toCliOutput(): string
    {
        $output = parent::getMessage();

        if ($this->hint !== null) {
            $output .= "\n  Hint: {$this->hint}";
        }

        if ($this->context !== []) {
            foreach ($this->context as $key => $value) {
                if (is_scalar($value)) {
                    $output .= "\n  {$key}: {$value}";
                } else {
                    $output .= "\n  {$key}: ".json_encode($value);
                }
            }
        }

        return $output;
    }

    /**
     * Determine if the exception message is safe to display to the user.
     * Overridden by specific layer exceptions (e.g., Infrastructure is usually false).
     */
    public function isUserFacing(): bool
    {
        return true;
    }

    /**
     * Determine if this exception should be reported/logged.
     */
    public function shouldReport(): bool
    {
        return true;
    }
}
