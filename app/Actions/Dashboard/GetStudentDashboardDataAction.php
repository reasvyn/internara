<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Logbook;
use App\Models\Registration;

class GetStudentDashboardDataAction
{
    /**
     * @return array{registration: ?Registration, totalJournals: int, verifiedJournals: int}
     */
    public function execute(string $userId): array
    {
        $registration = Registration::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['placement.company', 'internship'])
            ->first();

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
