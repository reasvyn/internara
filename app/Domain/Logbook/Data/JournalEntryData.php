<?php

declare(strict_types=1);

namespace App\Data\Journal;

/**
 * Data transfer object for journal entry submission.
 */
final readonly class LogbookEntryData
{
    public function __construct(
        public string $content,
        public ?string $date = null,
        public ?string $learningOutcomes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            date: $data['date'] ?? null,
            learningOutcomes: $data['learning_outcomes'] ?? null,
        );
    }
}
