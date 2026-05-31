<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

/**
 * Contract for entities or DTOs that can provide shared validation rules.
 *
 * Implemented by domain entities that define reusable validation rules used
 * by both Livewire Form Objects and HTTP Form Requests. This eliminates
 * duplicate validation logic across UI layers.
 *
 * Usage in a Form Object:
 *   $validated = $this->validate(InternshipPeriod::rules($excludeId));
 *
 * Usage in a Form Request:
 *   public function rules(): array
 *   {
 *       return InternshipPeriod::rules($this->route('id'));
 *   }
 */
interface HasValidationRules
{
    /**
     * Return validation rules for the entity.
     *
     * @param string|null $excludeId Optional ID to exclude (for unique rules on update).
     *
     * @return array<string, mixed>
     */
    public static function rules(?string $excludeId = null): array;

    /**
     * Return custom validation messages for the rules.
     *
     * @return array<string, string>
     */
    public static function messages(): array;
}
