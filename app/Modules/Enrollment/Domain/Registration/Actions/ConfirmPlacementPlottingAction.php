<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Data\ConfirmPlacementData;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;
use App\Modules\Enrollment\Domain\Registration\Events\PlacementConfirmed;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class ConfirmPlacementPlottingAction extends BaseCommandAction
{
    public function execute(ConfirmPlacementData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = Registration::with(['student', 'internship', 'documents', 'placement'])
                ->findOrFail($data->registrationId);

            if (in_array($registration->status, [
                RegistrationStatus::CONFIRMED->value,
                RegistrationStatus::ACTIVE->value,
                RegistrationStatus::COMPLETED->value,
            ], true)) {
                throw new RejectedException(__('registration.already_confirmed'));
            }

            if ($registration->documents->isNotEmpty() && $registration->documents->contains(fn ($doc) => ! $doc->status->isVerified())) {
                throw new RejectedException(__('registration.documents_not_verified'));
            }

            $placementId = $data->placementId ?? $registration->placement_id;

            if ($placementId !== null) {
                $placement = Placement::findOrFail($placementId);

                if ($placement->id !== $registration->placement_id && $placement->asPlacementCapacity()->isFull()) {
                    throw new RejectedException(__('placement.quota_full'));
                }

                if ($registration->placement_id === null) {
                    $placement->increment('filled_quota');
                }

                $registration->placement_id = $placement->id;
            }

            $startDate = $data->startDate ?? $registration->start_date ?? $registration->internship?->start_date;
            $endDate = $data->endDate ?? $registration->end_date ?? $registration->internship?->end_date;

            $registration->start_date = $startDate;
            $registration->end_date = $endDate;

            $isStartingNow = $startDate && Carbon::parse($startDate)->isPast();
            $targetStatus = $isStartingNow ? RegistrationStatus::ACTIVE : RegistrationStatus::CONFIRMED;

            $registration->setStatus($targetStatus);

            if ($data->mentorIds !== []) {
                $group = InternshipGroup::firstOrCreate(
                    [
                        'internship_id' => $registration->internship_id,
                        'placement_id' => $registration->placement_id,
                    ],
                    [
                        'name' => 'Group '.($registration->placement?->name ?? 'Default'),
                        'is_active' => true,
                    ]
                );

                $mentors = User::whereIn('id', $data->mentorIds)->get();
                $attachData = [];
                foreach ($mentors as $mentor) {
                    $attachData[$mentor->id] = [
                        'id' => (string) Str::uuid(),
                        'internship_group_id' => $group->id,
                        'role' => $mentor->hasRole('supervisor') ? 'supervisor' : 'teacher',
                    ];
                }
                $registration->mentors()->syncWithoutDetaching($attachData);
            }

            $dispatchRef = $data->dispatchLetterRef ?? ('ST/PKL/'.date('Y').'/'.strtoupper(Str::random(6)));

            $this->log('placement_plotting_confirmed', $registration, [
                'dispatch_ref' => $dispatchRef,
                'target_status' => $targetStatus->value,
                'placement_id' => $registration->placement_id,
            ]);

            $this->dispatchEvent(new PlacementConfirmed($registration, $dispatchRef));

            return $registration;
        });
    }
}
