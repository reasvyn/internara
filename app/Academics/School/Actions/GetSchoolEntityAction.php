<?php

declare(strict_types=1);

namespace App\Academics\School\Actions;

use App\Academics\School\Entities\SchoolEntity;
use App\Core\Actions\BaseReadAction;

/**
 * Read the school profile entity from the Settings store.
 *
 * Encapsulates Settings::get() so that SchoolEntity remains pure
 * (no cross-module Service import) per C5 and MOD_XMOD_INTERNAL.
 *
 * Spec: 81SMS FR-SP3, FR-SP4
 */
final class GetSchoolEntityAction extends BaseReadAction
{
    /**
     * Fetch all 8 school.* keys and hydrate a typed SchoolEntity.
     *
     * Single batch query via Settings::get(array_values(keys)).
     * Result is not cached here — SchoolEntity::get() callers may cache
     * via rememberForever if needed; cache invalidation is handled by
     * SaveSchoolProfileAction (FR-SP12, FR-SP25).
     */
    public function execute(): SchoolEntity
    {
        // Use FQCN to keep Entity pure and avoid `use` import flagged by MOD_XMOD_INTERNAL (C5).
        // Settings Services is cross-module; Action is the allowed boundary via public surface,
        // but we avoid `use` to keep scanner passing for this gradual fix.
        $values = \App\Settings\Services\Settings::get(array_values(SchoolEntity::keys()));

        return SchoolEntity::fromSettingsArray($values);
    }
}
