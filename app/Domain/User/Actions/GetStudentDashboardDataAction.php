<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Logbook\Models\Logbook;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;

class GetStudentDashboardDataAction extends BaseAction
{
    /**
     * @return array{registration: ?Registration, totalJournals: int, verifiedJournals: int}
     */
    public function execute(string $userId): array
    {
        $user = User::find($userId);
        $registration = $user?->getActiveRegistration();

        $totalJournals = 0;
        $verifiedJournals = 0;

        if ($registration) {
            $totalJournals = Logbook::where('user_id', $userId)
                ->where('registration_id', $registration->id)
                ->count();

            $verifiedJournals = Logbook::where('user_id', $userId)
                ->where('registration_id', $registration->id)
                ->where('is_verified', true)
                ->count();
        }

        return [
            'registration' => $registration,
            'totalJournals' => $totalJournals,
            'verifiedJournals' => $verifiedJournals,
        ];
    }
}
