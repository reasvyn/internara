<?php

declare(strict_types=1);

use App\Modules\Document\Domain\Handbook\Data\HandbookData;
use App\Modules\Document\Domain\Handbook\Enums\HandbookAudience;
use Illuminate\Http\UploadedFile;

describe('ZUFG8: HandbookData DTO', function (): void {
    test('ZUFG8-FR-HAND-003: fromArray maps title and audience with active default', function (): void {
        $dto = HandbookData::fromArray(['title' => 'Panduan PKL', 'audience' => HandbookAudience::STUDENT]);

        expect($dto->title)->toBe('Panduan PKL');
        expect($dto->audience)->toBe(HandbookAudience::STUDENT);
        expect($dto->description)->toBeNull();
        expect($dto->isActive)->toBeTrue();
        expect($dto->file)->toBeNull();
    });

    test('ZUFG8-FR-HAND-003: fromArray accepts every audience and snake_case keys', function (): void {
        $dto = HandbookData::fromArray([
            'title' => 'Panduan Pembimbing',
            'audience' => HandbookAudience::SUPERVISOR,
            'is_active' => false,
        ]);

        expect($dto->audience)->toBe(HandbookAudience::SUPERVISOR);
        expect($dto->isActive)->toBeFalse();
    });

    test('ZUFG8-FR-HAND-001: fromArray throws when the title is missing', function (): void {
        expect(fn (): HandbookData => HandbookData::fromArray(['audience' => HandbookAudience::ALL]))
            ->toThrow(InvalidArgumentException::class, 'title');
    });

    test('ZUFG8-FR-HAND-001: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['title' => 'T', 'audience' => HandbookAudience::TEACHER];

        expect(HandbookData::from($payload)->audience)->toBe(HandbookAudience::TEACHER);

        $source = new class
        {
            public function toArray(): array
            {
                return ['title' => 'T2', 'audience' => HandbookAudience::ALL, 'description' => 'desc'];
            }
        };

        expect(HandbookData::from($source)->description)->toBe('desc');
        expect(fn (): HandbookData => HandbookData::from(1))->toThrow(InvalidArgumentException::class);
    });

    test('ZUFG8-FR-HAND-004: toArray, only, except, and merge shape the payload', function (): void {
        $file = UploadedFile::fake()->create('panduan.pdf', 100, 'application/pdf');
        $dto = new HandbookData(title: 'T', audience: HandbookAudience::ALL, file: $file);

        expect($dto->toArray()['audience'])->toBe(HandbookAudience::ALL);
        expect($dto->toArray()['file'])->toBe($file);
        expect($dto->only('title'))->toBe(['title' => 'T']);
        expect(array_key_exists('audience', $dto->except('file')))->toBeTrue();

        $merged = $dto->merge(['isActive' => false]);

        expect($merged->isActive)->toBeFalse();
        expect($dto->isActive)->toBeTrue();
    });
});
