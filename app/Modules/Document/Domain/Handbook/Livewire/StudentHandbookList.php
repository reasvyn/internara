<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Document\Domain\Handbook\Actions\AcknowledgeHandbookAction;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TallStackUi\Traits\Interactions;

class StudentHandbookList extends Component
{
    use Interactions;

    public string $search = '';

    public string $filter = 'all';

    public ?string $readingHandbookId = null;

    public bool $showReaderModal = false;

    public function openReader(string $id): void
    {
        $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($id);

        if (! $handbook->asHandbook()->isTargetedAt(auth()->user())) {
            abort(403);
        }

        $this->readingHandbookId = $id;
        $this->showReaderModal = true;
    }

    public function closeReader(): void
    {
        $this->showReaderModal = false;
        $this->readingHandbookId = null;
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'pending', 'completed'], true)) {
            $this->filter = $filter;
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filter = 'all';
    }

    public function acknowledge(string $id, AcknowledgeHandbookAction $action): void
    {
        $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($id);

        if (! $handbook->asHandbook()->isTargetedAt(auth()->user())) {
            abort(403);
        }

        try {
            $action->execute($handbook, auth()->user());
            $this->toast()->success(__('handbook.acknowledged'))->send();
            unset($this->acknowledgments, $this->allHandbooks, $this->handbooks, $this->stats);
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function download(string $id): StreamedResponse
    {
        $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($id);
        $entity = $handbook->asHandbook();

        if (! $entity->isTargetedAt(auth()->user())) {
            abort(403);
        }

        if (! $entity->isAvailable()) {
            abort(404);
        }

        $media = $handbook->getFirstMedia('handbook_file');

        abort_unless($media, 404);

        return $media->toResponse(request());
    }

    /**
     * @return Collection<int, Document>
     */
    #[Computed]
    public function allHandbooks(): Collection
    {
        $user = auth()->user();

        return Document::ofType(DocumentCategory::HANDBOOK->value)
            ->where('is_active', true)
            ->with(['media'])
            ->latest('updated_at')
            ->get()
            ->filter(fn ($doc) => $doc->asHandbook()->isTargetedAt($user))
            ->values();
    }

    /**
     * @return array{total: int, read: int, pending: int, percent: int}
     */
    #[Computed]
    public function stats(): array
    {
        $total = $this->allHandbooks->count();
        $read = $this->allHandbooks->filter(function (Document $doc) {
            $lastAck = $this->acknowledgments->get($doc->id);

            return ! $doc->asHandbook()->isNewerThan($lastAck);
        })->count();

        $pending = max(0, $total - $read);
        $percent = $total > 0 ? (int) round(($read / $total) * 100) : 100;

        return [
            'total' => $total,
            'read' => $read,
            'pending' => $pending,
            'percent' => $percent,
        ];
    }

    /**
     * @return Collection<int, Document>
     */
    #[Computed]
    public function handbooks(): Collection
    {
        $query = $this->allHandbooks;

        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $query = $query->filter(function (Document $doc) use ($term) {
                $title = mb_strtolower($doc->title);
                $desc = mb_strtolower($doc->asHandbook()->description() ?? '');
                $content = mb_strtolower($doc->content ?? '');

                return str_contains($title, $term) || str_contains($desc, $term) || str_contains($content, $term);
            });
        }

        if ($this->filter === 'pending') {
            $query = $query->filter(function (Document $doc) {
                $lastAck = $this->acknowledgments->get($doc->id);

                return $doc->asHandbook()->isNewerThan($lastAck);
            });
        } elseif ($this->filter === 'completed') {
            $query = $query->filter(function (Document $doc) {
                $lastAck = $this->acknowledgments->get($doc->id);

                return ! $doc->asHandbook()->isNewerThan($lastAck);
            });
        }

        return $query->values();
    }

    #[Computed]
    public function selectedHandbook(): ?Document
    {
        if (! $this->readingHandbookId) {
            return null;
        }

        return Document::ofType(DocumentCategory::HANDBOOK->value)
            ->with(['media'])
            ->find($this->readingHandbookId);
    }

    /**
     * @return Collection<string, Activity>
     */
    #[Computed]
    public function acknowledgments(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        return Activity::causedBy($user)
            ->forEvent('acknowledged')
            ->where('subject_type', Document::class)
            ->latest()
            ->get()
            ->keyBy('subject_id');
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('document.handbook.student-handbook-list');
    }
}
