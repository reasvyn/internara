<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Data\GenerateMagicLinkData;
use App\Modules\Assessment\Models\AssessmentMagicLink;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use Illuminate\Support\Str;

final class GenerateAssessmentMagicLinkAction extends BaseCommandAction
{
    public function execute(GenerateMagicLinkData $data): AssessmentMagicLink
    {
        $registration = Registration::findOrFail($data->registrationId);

        return $this->transaction(function () use ($registration, $data): AssessmentMagicLink {
            $existing = AssessmentMagicLink::where('registration_id', $registration->id)
                ->whereNull('submitted_at')
                ->where('expires_at', '>', now())
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $validityDays = max(1, $data->validityDays);

            $magicLink = AssessmentMagicLink::create([
                'registration_id' => $registration->id,
                'token' => Str::random(64),
                'expires_at' => now()->addDays($validityDays),
                'created_by' => $data->creatorId,
            ]);

            $this->log('assessment_magic_link_generated', $magicLink, [
                'registration_id' => $registration->id,
                'expires_at' => $magicLink->expires_at->toIso8601String(),
                'validity_days' => $validityDays,
            ]);

            return $magicLink;
        });
    }
}
