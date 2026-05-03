<?php

declare(strict_types=1);

namespace App\Data\Attendance;

/**
 * Data transfer object for clock-in action.
 */
final readonly class ClockInData
{
    public function __construct(
        public ?string $ip = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public string $status = 'present',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ip: $data['ip'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            status: $data['status'] ?? 'present',
        );
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
