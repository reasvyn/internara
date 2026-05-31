<?php

declare(strict_types=1);

namespace App\Domain\Logbook\Livewire;

use App\Domain\Logbook\Actions\SubmitLogbookAction;
use App\Domain\Logbook\Models\Logbook;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LogbookEntry extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public string $date = '';

    public string $content = '';

    public string $learning_outcomes = '';

    public string $journalId = '';

    public array $photos = [];

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
        $this->reset(['journalId', 'content', 'learning_outcomes', 'photos']);
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
            'photos.*' => 'nullable|image|max:10240',
        ]);

        try {
            $submitJournal->execute(auth()->user(), [
                'date' => $this->date,
                'content' => $this->content,
                'learning_outcomes' => $this->learning_outcomes,
                'photos' => $this->photos,
            ]);

            $this->showModal = false;
            flash()->success('Journal entry saved successfully.');
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        $journals = Logbook::where('user_id', auth()->id())
            ->latest('date')
            ->paginate(10);

        return view('logbook.logbook-entry', [
            'journals' => $journals,
        ]);
    }
}
