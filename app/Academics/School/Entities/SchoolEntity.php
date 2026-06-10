<?php

declare(strict_types=1);

namespace App\Academics\School\Entities;

use App\Core\Entities\BaseEntity;
use App\Settings\Support\Settings;
use Illuminate\Database\Eloquent\Model;

final readonly class SchoolEntity extends BaseEntity
{
    private const array KEYS = [
        'name' => 'school.name',
        'institutional_code' => 'school.institutional_code',
        'email' => 'school.email',
        'address' => 'school.address',
        'phone' => 'school.phone',
        'website' => 'school.website',
        'principal_name' => 'school.principal_name',
    ];

    public function __construct(
        private string $name,
        private string $institutionalCode,
        private string $email,
        private string $address = '',
        private string $phone = '',
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

    public static function get(): self
    {
        $values = Settings::get(array_values(self::KEYS));

        return new self(
            name: (string) ($values['school.name'] ?? ''),
            institutionalCode: (string) ($values['school.institutional_code'] ?? ''),
            email: (string) ($values['school.email'] ?? ''),
            address: (string) ($values['school.address'] ?? ''),
            phone: (string) ($values['school.phone'] ?? ''),
            website: (string) ($values['school.website'] ?? ''),
            principalName: (string) ($values['school.principal_name'] ?? ''),
        );
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

    public function website(): string
    {
        return $this->website;
    }

    public function principalName(): string
    {
        return $this->principalName;
    }
}
