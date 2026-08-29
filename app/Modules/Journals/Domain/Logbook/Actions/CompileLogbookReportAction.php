<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Logbook\Actions;

use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Logbook\Models\Logbook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;

final class CompileLogbookReportAction extends BaseReadAction
{
    public function execute(Registration $registration, bool $includeSupervisorNotes = true): Response
    {
        $entries = Logbook::query()
            ->with(['user', 'supervisor', 'media'])
            ->where('registration_id', $registration->id)
            ->where('status', 'verified')
            ->orderBy('date')
            ->get();

        $html = Blade::render(
            'logbook.report-pdf',
            [
                'registration' => $registration,
                'entries' => $entries,
                'includeSupervisorNotes' => $includeSupervisorNotes,
                'student' => $registration->mentee?->user,
                'company' => $registration->placement?->company,
            ],
            deleteCachedView: true,
        );

        $filename =
            'logbook-report-'.
            $registration->mentee?->user?->name ?? 'Unknown'.
            '-'.
            now()->format('Ymd').
            '.pdf';

        return Pdf::loadHTML($html)->setPaper('a4')->stream($filename);
    }
}
