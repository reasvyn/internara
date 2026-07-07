<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Livewire;

use App\Core\Livewire\BaseRecordEntry;
use App\Journals\Logbook\Actions\SubmitLogbookAction;
use App\Journals\Logbook\Models\Logbook;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class LogbookEntry extends BaseRecordEntry
{
    use WithPagination;

    public ?string $journalId = null;

    public string $date = '';

    public string $content = '';

    public string $learning_outcomes = '';

    public array $photos = [];

    public function boot(): void
    {
        $this->authorize('create', Logbook::class);
    }

    public function mount(): void
    {
        $this->date = Carbon::today()->toDateString();
    }

    public function create(): void
    {
        parent::create();
        $this->reset(['journalId', 'content', 'learning_outcomes', 'photos']);
        $this->date = Carbon::today()->toDateString();
    }

    public function edit(string $id): void
    {
        $journal = Logbook::findOrFail($id);
        $this->journalId = $journal->id;
        $this->date = $journal->date->toDateString();
        $this->content = $journal->content;
        $this->learning_outcomes = $journal->learning_outcomes ?? '';
        $this->showModal = true;
    }

    public function save(SubmitLogbookAction $submitJournal): void
    {
        $this->authorize('create', Logbook::class);

        $this->validate([
            'date' => 'required|date',
            'content' => 'required|min:10',
            'learning_outcomes' => 'nullable|string',
            'photos.*' => 'nullable|image|max:10240',
        ]);

        $this->handleError(function () use ($submitJournal) {
            $submitJournal->execute(auth()->user(), [
                'date' => $this->date,
                'content' => $this->content,
                'learning_outcomes' => $this->learning_outcomes,
                'photos' => $this->photos,
            ]);

            $this->showModal = false;
            flash()->success('Journal entry saved successfully.');
        });
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $journals = Logbook::where('user_id', auth()->id())->latest('date')->paginate(10);

        return view('journals.logbook.logbook-entry', [
            'journals' => $journals,
        ]);
    }
}
