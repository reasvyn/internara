<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    private ?string $hint = null;

    private array $context = [];

    public function withHint(?string $hint): self
    {
        $this->hint = $hint;

        return $this;
    }

    public function withContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toCliOutput(): string
    {
        $output = parent::getMessage();

        if ($this->hint !== null) {
            $output .= "\n  Hint: {$this->hint}";
        }

        if ($this->context !== []) {
            foreach ($this->context as $key => $value) {
                $output .= "\n  {$key}: {$value}";
            }
        }

        return $output;
    }
}
