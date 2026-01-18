<?php

declare(strict_types=1);

namespace Modules\Journal\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;
use Modules\Journal\Livewire\Forms\JournalForm;
use Modules\Journal\Services\Contracts\JournalService;

class JournalEntryManager extends Component
{
    use WithFileUploads;

    public JournalForm $form;

    protected JournalService $journalService;

    protected InternshipRegistrationService $registrationService;

    public function boot(
        JournalService $journalService,
        InternshipRegistrationService $registrationService,
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
                    throw new \Exception(__('internship::messages.no_active_registration'));
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

            $this->dispatch(
                'notify',
                message: $asDraft
                    ? __('shared::messages.record_saved')
                    : __('shared::messages.record_submitted'),
                type: 'success',
            );
            $this->redirect(route('journal.index'), navigate: true);
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function render(): View
    {
        return view('journal::livewire.journal-entry-manager')->layout(
            'dashboard::components.layouts.dashboard',
            [
                'title' => $this->form->id ? __('Edit Jurnal') : __('Buat Jurnal Baru'),
            ],
        );
    }
}
