<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Company\Data\CompanyData;

describe('XI3LB: CompanyData DTO', function (): void {
    test('XI3LB-FR-COMP-007: fromArray requires only the name', function (): void {
        $dto = CompanyData::fromArray(['name' => 'PT Maju Jaya']);

        expect($dto->name)->toBe('PT Maju Jaya');
        expect($dto->address)->toBeNull();
        expect($dto->phone)->toBeNull();
        expect($dto->email)->toBeNull();
        expect($dto->website)->toBeNull();
        expect($dto->description)->toBeNull();
        expect($dto->industrySector)->toBeNull();
    });

    test('XI3LB-FR-COMP-007: fromArray accepts snake_case keys for every optional field', function (): void {
        $dto = CompanyData::fromArray([
            'name' => 'PT Mundur',
            'address' => 'Jl. Merdeka 1',
            'phone' => '021-123',
            'email' => 'info@pt.co.id',
            'website' => 'https://pt.co.id',
            'description' => 'Manufaktur',
            'industry_sector' => 'Manufaktur',
        ]);

        expect($dto->address)->toBe('Jl. Merdeka 1');
        expect($dto->phone)->toBe('021-123');
        expect($dto->email)->toBe('info@pt.co.id');
        expect($dto->website)->toBe('https://pt.co.id');
        expect($dto->industrySector)->toBe('Manufaktur');
    });

    test('XI3LB-FR-COMP-007: fromArray throws when the name is missing', function (): void {
        expect(fn (): CompanyData => CompanyData::fromArray(['email' => 'info@pt.co.id']))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('XI3LB-FR-COMP-006: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(CompanyData::from(['name' => 'PT A'])->name)->toBe('PT A');

        $source = new class
        {
            public function toArray(): array
            {
                return ['name' => 'PT B', 'phone' => '0800'];
            }
        };

        expect(CompanyData::from($source)->phone)->toBe('0800');
        expect(fn (): CompanyData => CompanyData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('XI3LB-FR-COMP-006: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CompanyData(name: 'PT A');

        expect($dto->toArray())->toBe([
            'name' => 'PT A',
            'address' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'description' => null,
            'industrySector' => null,
        ]);
        expect($dto->only('name'))->toBe(['name' => 'PT A']);
        expect(array_key_exists('industrySector', $dto->except('name')))->toBeTrue();

        $merged = $dto->merge(['industrySector' => 'Teknologi']);

        expect($merged->industrySector)->toBe('Teknologi');
        expect($dto->industrySector)->toBeNull();
    });
});
