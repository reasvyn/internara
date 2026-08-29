<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Domain\Handbook\Actions\AcknowledgeHandbookAction;
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

    public function acknowledge(string $id, AcknowledgeHandbookAction $action): void
    {
        $handbook = Document::ofType(DocumentCategory::HANDBOOK->value)->findOrFail($id);

        if (! $handbook->asHandbook()->isTargetedAt(auth()->user())) {
            abort(403);
        }

        try {
            $action->execute($handbook, auth()->user());
            $this->toast()->success(__('handbook.acknowledged'))->send();
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

    #[Computed]
    public function handbooks(): Collection
    {
        $user = auth()->user();

        $all = Document::ofType(DocumentCategory::HANDBOOK->value)
            ->where('is_active', true)
            ->get();

        return $all->filter(fn ($doc) => $doc->asHandbook()->isTargetedAt($user));
    }

    #[Computed]
    public function acknowledgments(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        $acknowledgments = Activity::causedBy($user)
            ->forEvent('acknowledged')
            ->where('subject_type', Document::class)
            ->latest()
            ->get()
            ->keyBy('subject_id');

        return $acknowledgments;
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('document.handbook.student-handbook-list');
    }
}
