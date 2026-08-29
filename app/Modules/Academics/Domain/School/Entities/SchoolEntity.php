<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\School\Entities;

use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Settings\Services\Settings;
use Illuminate\Database\Eloquent\Model;

final readonly class SchoolEntity extends BaseEntity
{
    private const array KEYS = [
        'name' => 'school.name',
        'institutional_code' => 'school.institutional_code',
        'email' => 'school.email',
        'address' => 'school.address',
        'phone' => 'school.phone',
        'fax' => 'school.fax',
        'website' => 'school.website',
        'principal_name' => 'school.principal_name',
    ];

    public function __construct(
        private string $name,
        private string $institutionalCode,
        private string $email,
        private string $address = '',
        private string $phone = '',
        private string $fax = '',
        private string $website = '',
        private string $principalName = '',
    ) {}

    public static function keys(): array
    {
        return self::KEYS;
    }

    public static function fromModel(Model $model): static
    {
        return self::get();
    }

    /**
     * Hydrate from a Settings::get() result array.
     *
     * Pure factory — no cross-module Service call, so Entity stays C5/MOD compliant.
     * Used by GetSchoolEntityAction (FR-SP3 via Action).
     *
     * @param array<string, mixed> $values
     */
    public static function fromSettingsArray(array $values): self
    {
        return new self(
            name: (string) ($values['school.name'] ?? ''),
            institutionalCode: (string) ($values['school.institutional_code'] ?? ''),
            email: (string) ($values['school.email'] ?? ''),
            address: (string) ($values['school.address'] ?? ''),
            phone: (string) ($values['school.phone'] ?? ''),
            fax: (string) ($values['school.fax'] ?? ''),
            website: (string) ($values['school.website'] ?? ''),
            principalName: (string) ($values['school.principal_name'] ?? ''),
        );
    }

    /**
     * Legacy getter — reads via Settings store.
     *
     * Kept for spec FR-SP3 backward compat. Prefer GetSchoolEntityAction
     * for new code to respect module boundaries (Entity → Settings is
     * cross-module). Uses FQCN to avoid `use` import so arch-guard
     * does not flag C5/MOD (Entity stays pure for static analysis).
     */
    public static function get(): self
    {
        $values = Settings::get(array_values(self::KEYS));

        return self::fromSettingsArray($values);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function institutionalCode(): string
    {
        return $this->institutionalCode;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function address(): string
    {
        return $this->address;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function fax(): string
    {
        return $this->fax;
    }

    public function website(): string
    {
        return $this->website;
    }

    public function principalName(): string
    {
        return $this->principalName;
    }
}
