<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Livewire;

use App\Core\Livewire\BaseRecordManager;
use App\Guidance\Handbook\Actions\CreateHandbookAction;
use App\Guidance\Handbook\Actions\DeleteHandbookAction;
use App\Guidance\Handbook\Actions\UpdateHandbookAction;
use App\Guidance\Handbook\Livewire\Forms\HandbookForm;
use App\Guidance\Handbook\Models\Handbook;
use App\Guidance\HandbookAcknowledgement\Actions\AcknowledgeHandbookAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithFileUploads;

class HandbookManager extends BaseRecordManager
{
    use AuthorizesRequests, WithFileUploads;

    public bool $showModal = false;

    public HandbookForm $form;

    public $file = null;

    public bool $removeFile = false;

    protected function rules(): array
    {
        return [
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function boot(): void
    {
        $this->authorize('create', Handbook::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => __('handbooks.title_field'), 'sortable' => true],
            ['key' => 'version', 'label' => __('handbooks.version_field'), 'sortable' => true],
            ['key' => 'author.name', 'label' => __('handbooks.author')],
            ['key' => 'is_active', 'label' => __('handbooks.status')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Handbook::query()->with('author');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('content', 'like', "%{$this->search}%");
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['target_audience'] ?? null, function ($q, $audience) {
                $q->where('target_audience', $audience);
            })
            ->when($this->filters['is_active'] ?? null, function ($q, $active) {
                $q->where('is_active', $active === '1');
            });
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->file = null;
        $this->removeFile = false;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $handbook = Handbook::findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $handbook->id,
            'title' => $handbook->title,
            'content' => $handbook->content,
            'version' => (string) $handbook->version,
            'is_active' => $handbook->is_active,
            'target_audience' => $handbook->target_audience,
        ]);
        $this->file = null;
        $this->removeFile = false;
        $this->showModal = true;
    }

    public function store(CreateHandbookAction $createAction, UpdateHandbookAction $updateAction): void
    {
        $this->form->validate();
        $this->validate();

        $files = $this->file ? [$this->file] : [];

        if ($this->form->id) {
            $handbook = Handbook::findOrFail($this->form->id);
            $removeFileIds = $this->removeFile
                ? $handbook->media()->where('collection_name', 'files')->pluck('uuid')->toArray()
                : [];
            $updateAction->execute($handbook, $this->form->all(), $files, $removeFileIds);
            flash()->success(__('handbook.updated'));
        } else {
            $createAction->execute(auth()->user(), $this->form->all(), $files);
            flash()->success(__('handbook.created'));
        }

        $this->showModal = false;
        $this->form->reset();
        $this->file = null;
        $this->removeFile = false;
    }

    public function acknowledge(string $id, AcknowledgeHandbookAction $action): void
    {
        $handbook = Handbook::findOrFail($id);
        $action->execute(auth()->user(), $handbook);
        flash()->success(__('handbook.acknowledged'));
    }

    public function delete(string $id, DeleteHandbookAction $action): void
    {
        $handbook = Handbook::findOrFail($id);
        $action->execute($handbook);
        flash()->success(__('handbook.deleted'));
    }

    public function render(): View
    {
        return view('guidance.handbook.handbook-manager');
    }
}
