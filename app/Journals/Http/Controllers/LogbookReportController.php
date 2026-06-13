<?php

declare(strict_types=1);

namespace App\Journals\Http\Controllers;

use App\Enrollment\Models\Registration;
use App\Journals\Logbook\Actions\CompileLogbookReportAction;

final class LogbookReportController
{
    public function __invoke(string $registrationId, CompileLogbookReportAction $action): mixed
    {
        $registration = Registration::findOrFail($registrationId);

        return $action->download($registration);
    }
}
