<?php

declare(strict_types=1);

namespace App\Livewire\Logbook;

use App\Domain\Logbook\Actions\SubmitLogbookEntryAction;
use App\Domain\Logbook\Models\LogbookEntry;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class LogbookManager extends Component
{
    use Toast, WithPagination;

    public bool $showModal = false;

    public string $date = '';

    public string $content = '';

    public string $learning_outcomes = '';

    public string $journalId = '';

    public function mount(): void
    {
        $this->date = Carbon::today()->toDateString();
    }

    public function create(): void
    {
        $this->reset(['journalId', 'content', 'learning_outcomes']);
        $this->date = Carbon::today()->toDateString();
        $this->showModal = true;
    }

    public function edit(LogbookEntry $journal): void
    {
        $this->journalId = $journal->id;
        $this->date = $journal->date->toDateString();
        $this->content = $journal->content;
        $this->learning_outcomes = $journal->learning_outcomes ?? '';
        $this->showModal = true;
    }

    public function save(SubmitLogbookEntryAction $submitJournal): void
    {
        $this->validate([
            'date' => 'required|date',
            'content' => 'required|min:10',
            'learning_outcomes' => 'nullable|string',
        ]);

        try {
            $submitJournal->execute(auth()->user(), [
                'date' => $this->date,
                'content' => $this->content,
                'learning_outcomes' => $this->learning_outcomes,
            ]);

            $this->showModal = false;
            $this->success('Journal entry saved successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $journals = LogbookEntry::where('user_id', auth()->id())
            ->latest('date')
            ->paginate(10);

        return view('livewire.logbook.logbook-manager', [
            'journals' => $journals,
        ]);
    }
}
