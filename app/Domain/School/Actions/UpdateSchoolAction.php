<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\School\Models\School;

/**
 * Action to update the institution's profile.
 *
 * S1 - Secure: Logged for accountability.
 * S2 - Sustain: Atomic updates.
 */
final class UpdateSchoolAction extends BaseAction
{
    public function execute(School $school, array $data): School
    {
        return $this->transaction(function () use ($school, $data) {
            // Extract logo file if present
            $logoFile = $data['logo_file'] ?? null;
            unset($data['logo_file']);

            $school->update($data);

            // Handle logo if provided
            if ($logoFile !== null) {
                $school->clearMediaCollection(School::COLLECTION_LOGO);
                $school->addMedia($logoFile)->toMediaCollection(School::COLLECTION_LOGO);
            }

            $this->log('school_profile_updated', $school, $data);

            return $school;
        });
    }
}
