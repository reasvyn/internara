<?php

declare(strict_types=1);

use App\Modules\Core\Data\BaseData;

final readonly class DataTestDouble extends BaseData
{
    public function __construct(
        public string $userName,
        public string $email,
        public string $role = 'student',
    ) {}
}

describe('SE5Q9: base data transfer object', function (): void {
    test('SE5Q9-FR-BASE-012: fromArray maps exact and snake_case keys with defaults', function (): void {
        $dto = DataTestDouble::fromArray(['user_name' => 'sinta', 'email' => 'sinta@smk.id']);

        expect($dto->userName)->toBe('sinta');
        expect($dto->role)->toBe('student');
    });

    test('SE5Q9-FR-BASE-012: fromArray rejects a missing required param', function (): void {
        expect(fn (): DataTestDouble => DataTestDouble::fromArray(['email' => 'sinta@smk.id']))
            ->toThrow(InvalidArgumentException::class, 'userName');
    });

    test('SE5Q9-FR-BASE-012: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(DataTestDouble::from(['userName' => 's', 'email' => 'e'])->userName)->toBe('s');

        $source = new class
        {
            public function toArray(): array
            {
                return ['userName' => 't', 'email' => 't@x.id'];
            }
        };

        expect(DataTestDouble::from($source)->userName)->toBe('t');
        expect(fn (): DataTestDouble => DataTestDouble::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('QLHDO-FR-GLB-009: toArray exposes scalar state for persistence', function (): void {
        $dto = new DataTestDouble(userName: 'sinta', email: 'sinta@smk.id', role: 'teacher');

        expect($dto->toArray())->toBe(['userName' => 'sinta', 'email' => 'sinta@smk.id', 'role' => 'teacher']);
        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    test('SE5Q9-FR-BASE-012: only, except, and merge shape the payload without mutation', function (): void {
        $dto = new DataTestDouble(userName: 'sinta', email: 'sinta@smk.id');

        expect($dto->only('email'))->toBe(['email' => 'sinta@smk.id']);
        expect($dto->except('role'))->toBe(['userName' => 'sinta', 'email' => 'sinta@smk.id']);

        $merged = $dto->merge(['role' => 'teacher']);

        expect($merged->role)->toBe('teacher');
        expect($dto->role)->toBe('student');
    });
});
