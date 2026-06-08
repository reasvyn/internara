<?php

declare(strict_types=1);

namespace App\Core\Exceptions\Concerns;

use App\Core\Support\PiiMasker;

trait HasExceptionContext
{
    protected ?string $hint = null;

    /** @var array<string, mixed> */
    protected array $context = [];

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

    public function toCliOutput(): string
    {
        $output = $this->getMessage();

        if ($this->hint !== null) {
            $output .= "\n  Hint: {$this->hint}";
        }

        if ($this->context !== []) {
            $sanitized = PiiMasker::maskArray($this->context);

            foreach ($sanitized as $key => $value) {
                if (is_scalar($value)) {
                    $output .= "\n  {$key}: {$value}";
                } else {
                    $output .= "\n  {$key}: ".json_encode($value, JSON_THROW_ON_ERROR);
                }
            }
        }

        return $output;
    }

    public function getSanitizedContext(): array
    {
        return PiiMasker::maskArray($this->context);
    }

    public function isUserFacing(): bool
    {
        return true;
    }

    public function shouldReport(): bool
    {
        return true;
    }
}
