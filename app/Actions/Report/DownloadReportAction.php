<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Models\GeneratedReport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Handles report file download with authorization check.
 *
 * S1 - Secure: Ensures user can only download their own reports (or admins can download any).
 */
class DownloadReportAction
{
    public function execute(User $user, GeneratedReport $report): ?string
    {
        if (! $report->isCompleted()) {
            throw new \Exception('Report is not ready for download.');
        }

        if (! $user->hasRole('super_admin') && $report->user_id !== $user->id) {
            throw new \Exception('Unauthorized to download this report.');
        }

        if (! Storage::disk('private')->exists($report->file_path)) {
            throw new \Exception('Report file not found.');
        }

        return Storage::disk('private')->get($report->file_path);
    }
}
