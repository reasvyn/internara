<?php

declare(strict_types=1);

namespace App\Jobs\Journals;

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Actions\CompileLogbookReportAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompileLogbookReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [2, 10, 30];

    public function __construct(
        protected readonly string $studentId,
        protected readonly string $internshipId,
    ) {}

    public function handle(CompileLogbookReportAction $compileReport): void
    {
        $registration = Registration::query()
            ->where('student_id', $this->studentId)
            ->where('internship_id', $this->internshipId)
            ->firstOrFail();

        $compileReport->execute($registration);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('Logbook report compilation failed', [
            'student_id' => $this->studentId,
            'internship_id' => $this->internshipId,
            'error' => $e->getMessage(),
        ]);
    }
}
