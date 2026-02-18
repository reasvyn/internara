<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Support\Services\Contracts\OnboardingService;

/**
 * Class BatchOnboarding
 *
 * Provides a UI for mass importing stakeholders via CSV.
 */
class BatchOnboarding extends Component
{
    use WithFileUploads;

    /**
     * The CSV file to upload.
     */
    public $file;

    /**
     * The type of stakeholders to import.
     */
    public string $type = 'student';

    /**
     * The results of the import operation.
     */
    public ?array $results = null;

    /**
     * Download the CSV template.
     */
    public function downloadTemplate(OnboardingService $service)
    {
        $content = $service->getTemplate($this->type);
        $fileName = "template_{$this->type}.csv";

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName);
    }

    /**
     * Process the CSV import.
     */
    public function import(OnboardingService $service): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'type' => 'required|in:student,teacher,mentor',
        ]);

        try {
            $path = $this->file->getRealPath();
            $this->results = $service->importFromCsv($path, $this->type);

            $message = __('Import completed with :success success and :failure failure.', [
                'success' => $this->results['success'],
                'failure' => $this->results['failure'],
            ]);

            if ($this->results['failure'] > 0) {
                flash()->warning($message);
            } else {
                flash()->success($message);
            }
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return view('admin::livewire.batch-onboarding');
    }
}
