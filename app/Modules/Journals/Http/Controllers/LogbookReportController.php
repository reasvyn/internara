<?php

declare(strict_types=1);

namespace App\Modules\Journals\Http\Controllers;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Logbook\Actions\CompileLogbookReportAction;
use Illuminate\Http\Request;

final class LogbookReportController
{
    public function __invoke(
        string $registrationId,
        CompileLogbookReportAction $action,
        Request $request,
    ): mixed {
        $registration = Registration::findOrFail($registrationId);

        $user = $request->user();

        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            $isMentor = $registration->mentors()->where('user_id', $user->id)->exists();

            if (! $isMentor) {
                throw new RejectedException(__('permission denied'));
            }
        }

        return $action->execute($registration);
    }
}
