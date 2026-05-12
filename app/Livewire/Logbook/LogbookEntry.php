<?php

declare(strict_types=1);

namespace App\Livewire\Logbook;

use App\Actions\Logbook\SubmitLogbookAction;
use App\Models\Logbook;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class LogbookEntry extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $date = '';

    public string $content = '';

    public string $learning_outcomes = '';

    public string $journalId = '';

    public function boot(): void
    {
        if (! auth()->user()?->hasRole('student')) {
            abort(403);
        }
    }

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

    public function edit(Logbook $journal): void
    {
        $this->journalId = $journal->id;
        $this->date = $journal->date->toDateString();
        $this->content = $journal->content;
        $this->learning_outcomes = $journal->learning_outcomes ?? '';
        $this->showModal = true;
    }

    public function save(SubmitLogbookAction $submitJournal): void
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
            flash()->success('Journal entry saved successfully.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $journals = Logbook::where('user_id', auth()->id())
            ->latest('date')
            ->paginate(10);

        return view('livewire.logbook.logbook-entry', [
            'journals' => $journals,
        ]);
    }
}
