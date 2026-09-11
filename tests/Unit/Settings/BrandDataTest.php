<?php

declare(strict_types=1);

use App\Modules\Settings\Domain\Branding\Data\BrandData;

describe('52O1I: BrandData DTO', function (): void {
    test('52O1I-FR-BRAND-001: fromArray maps identity, assets, and metadata', function (): void {
        $dto = BrandData::fromArray([
            'name' => 'Internara',
            'title' => 'Sistem PKL',
            'logo' => '/storage/logo.png',
            'favicon' => '/storage/favicon.ico',
            'colors' => ['primary' => '#10b981'],
            'version' => '0.15.9',
            'authorName' => 'Reas Vyn',
            'authorEmail' => 'admin@smk.id',
            'description' => 'desc',
            'license' => 'MIT',
            'gitUrl' => 'https://github.com/reasvyn/internara',
        ]);

        expect($dto->name)->toBe('Internara');
        expect($dto->title)->toBe('Sistem PKL');
        expect($dto->logo)->toBe('/storage/logo.png');
        expect($dto->colors)->toBe(['primary' => '#10b981']);
        expect($dto->version)->toBe('0.15.9');
        expect($dto->license)->toBe('MIT');
    });

    test('52O1I-FR-BRAND-001: fromArray accepts snake_case keys', function (): void {
        $dto = BrandData::fromArray([
            'name' => 'N',
            'title' => 'T',
            'logo' => 'L',
            'favicon' => 'F',
            'colors' => [],
            'version' => '1',
            'author_name' => 'A',
            'author_email' => 'a@x.id',
            'description' => 'd',
            'license' => 'MIT',
            'git_url' => 'https://example.id',
        ]);

        expect($dto->authorName)->toBe('A');
        expect($dto->authorEmail)->toBe('a@x.id');
        expect($dto->gitUrl)->toBe('https://example.id');
    });

    test('52O1I-FR-BRAND-001: fromArray throws when the name is missing', function (): void {
        expect(fn (): BrandData => BrandData::fromArray(['title' => 'T']))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('52O1I-FR-BRAND-001: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = [
            'name' => 'N',
            'title' => 'T',
            'logo' => 'L',
            'favicon' => 'F',
            'colors' => [],
            'version' => '1',
            'authorName' => 'A',
            'authorEmail' => 'a@x.id',
            'description' => 'd',
            'license' => 'MIT',
            'gitUrl' => 'https://example.id',
        ];

        expect(BrandData::from($payload)->title)->toBe('T');

        $source = new class
        {
            public function toArray(): array
            {
                return [
                    'name' => 'N2',
                    'title' => 'T2',
                    'logo' => 'L2',
                    'favicon' => 'F2',
                    'colors' => ['primary' => '#000000'],
                    'version' => '2',
                    'authorName' => 'A2',
                    'authorEmail' => 'a2@x.id',
                    'description' => 'd2',
                    'license' => 'MIT',
                    'gitUrl' => 'https://example2.id',
                ];
            }
        };

        expect(BrandData::from($source)->version)->toBe('2');
        expect(fn (): BrandData => BrandData::from(1))->toThrow(InvalidArgumentException::class);
    });

    test('52O1I-FR-BRAND-001: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new BrandData(
            name: 'N',
            title: 'T',
            logo: 'L',
            favicon: 'F',
            colors: [],
            version: '1',
            authorName: 'A',
            authorEmail: 'a@x.id',
            description: 'd',
            license: 'MIT',
            gitUrl: 'https://example.id',
        );

        expect($dto->toArray()['name'])->toBe('N');
        expect($dto->only('name', 'version'))->toBe(['name' => 'N', 'version' => '1']);
        expect(array_key_exists('colors', $dto->except('name')))->toBeTrue();

        $merged = $dto->merge(['version' => '2']);

        expect($merged->version)->toBe('2');
        expect($dto->version)->toBe('1');
    });
});
