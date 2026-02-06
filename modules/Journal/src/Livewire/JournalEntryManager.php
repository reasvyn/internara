<?php

declare(strict_types=1);

namespace Modules\Journal\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Livewire\Forms\JournalForm;
use Modules\Journal\Services\Contracts\JournalService;

class JournalEntryManager extends Component
{
    use WithFileUploads;

    public JournalForm $form;

    protected JournalService $journalService;

    protected RegistrationService $registrationService;

    public function boot(
        JournalService $journalService,
        RegistrationService $registrationService,
    ): void {
        $this->journalService = $journalService;
        $this->registrationService = $registrationService;
    }

    public function mount(?string $id = null): void
    {
        if ($id) {
            $entry = $this->journalService->find($id);
            $this->authorize('update', $entry);
            $this->form->setEntry($entry);
        } else {
            $this->authorize('create', \Modules\Journal\Models\JournalEntry::class);
            $this->form->date = request()->query('date', now()->format('Y-m-d'));
        }
    }

    public function save(bool $asDraft = false): void
    {
        $this->form->validate();

        try {
            if ($this->form->id) {
                $entry = $this->journalService->update(
                    $this->form->id,
                    $this->form->except('entry', 'attachments'),
                );
            } else {
                // Find active registration for current student
                $registration = $this->registrationService->first([
                    'student_id' => auth()->id(),
                    'latest_status' => 'active',
                ]);

                if (! $registration) {
                    throw new \Modules\Exception\AppException(
                        userMessage: 'internship::messages.no_active_registration',
                        code: 404,
                    );
                }

                $data = $this->form->except('entry', 'attachments');
                $data['student_id'] = auth()->id();
                $data['registration_id'] = $registration->id;

                $entry = $this->journalService->create($data);
            }

            // Set status
            $status = $asDraft ? 'draft' : 'submitted';
            $entry->setStatus($status, $asDraft ? 'Journal saved as draft.' : 'Journal submitted.');

            if (! empty($this->form->attachments)) {
                $this->journalService->attachMedia($entry->id, $this->form->attachments);
            }

            notify(
                $asDraft
                    ? __('shared::messages.record_saved')
                    : __('shared::messages.record_submitted'),
                'success',
            );
            $this->redirect(route('journal.index'), navigate: true);
        } catch (\Throwable $e) {
            $message =
                $e instanceof \Modules\Exception\AppException
                    ? $e->getUserMessage()
                    : $e->getMessage();

            notify($message, 'error');
        }
    }

    public function render(): View
    {
        $registration = $this->registrationService->first([
            'student_id' => auth()->id(),
            'latest_status' => 'active',
        ]);

        $availableCompetencies = [];
        if ($registration) {
            $profile = app(\Modules\Profile\Services\Contracts\ProfileService::class)->getByUserId(
                auth()->id(),
            );

            if ($profile && $profile->department_id) {
                $availableCompetencies = app(
                    \Modules\Assessment\Services\Contracts\CompetencyService::class,
                )->getForDepartment($profile->department_id);
            }
        }

        return view('journal::livewire.journal-entry-manager', [
            'availableCompetencies' => $availableCompetencies,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $this->form->id ? __('Edit Jurnal') : __('Buat Jurnal Baru'),
        ]);
    }
}