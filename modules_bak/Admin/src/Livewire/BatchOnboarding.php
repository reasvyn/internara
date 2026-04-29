<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Setup\Onboarding\Services\Contracts\OnboardingService;

/**
 * Class BatchOnboarding
 *
 * Provides a UI for mass importing stakeholders via CSV.
 * After import, activation codes are shown once in a credential slips modal.
 */
class BatchOnboarding extends Component
{
    use WithFileUploads;

    public $file;

    public string $type = 'student';

    public string $batchName = '';

    public ?array $results = null;

    public array $credentialSlips = [];

    public bool $credentialSlipsModal = false;

    public function downloadTemplate(OnboardingService $service)
    {
        $content = $service->getTemplate($this->type);
        $fileName = "template_{$this->type}.csv";

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName);
    }

    public function import(OnboardingService $service): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'type' => 'required|in:student,teacher,mentor',
            'batchName' => 'nullable|string|max:100',
        ]);

        try {
            $path = $this->file->getRealPath();
            $this->results = $service->importFromCsv($path, $this->type);

            if (!empty($this->results['credentials'])) {
                $this->credentialSlips = $this->results['credentials'];
                $this->credentialSlipsModal = true;
            }

            $message = __('admin::ui.batch_onboarding.import_completed', [
                'success' => $this->results['success'],
                'failure' => $this->results['failure'],
            ]);

            if ($this->results['failure'] > 0) {
                flash()->warning($message);
            } else {
                flash()->success($message);
            }

            $this->reset('file');
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function closeCredentialSlips(): void
    {
        $this->credentialSlipsModal = false;
        $this->credentialSlips = [];
    }

    public function render(): View
    {
        return view('admin::livewire.batch-onboarding');
    }
}
