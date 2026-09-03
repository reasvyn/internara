<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Livewire;

use App\Modules\Certification\Domain\Certificate\Actions\CreateCertificateTemplateAction;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Core\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class CertificateTemplateManager extends BaseRecordManager
{
    use Interactions;

    public bool $showModal = false;

    public array $formData = [
        'id' => null,
        'name' => '',
        'layout' => 'portrait',
        'content_template' => '',
        'is_active' => true,
    ];

    public function headers(): array
    {
        return [
            ['index' => 'name', 'label' => __('certificate.template_name'), 'sortable' => true],
            ['index' => 'layout', 'label' => __('certificate.layout')],
            ['index' => 'is_active', 'label' => __('certificate.is_active')],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function applySearch(Builder $query): Builder
    {
        $term = '%'.$this->search.'%';

        return $query->where('name', 'like', $term)->orWhere('layout', 'like', $term);
    }

    protected function query(): Builder
    {
        return CertificateTemplate::query();
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'name' => '',
            'layout' => 'portrait',
            'content_template' => '',
            'is_active' => true,
        ];
        $this->showModal = true;
    }

    public function saveTemplate(CreateCertificateTemplateAction $action): void
    {
        $this->validate([
            'formData.name' => ['required', 'string', 'max:255'],
            'formData.layout' => ['required', 'in:portrait,landscape'],
            'formData.content_template' => ['required', 'string'],
            'formData.is_active' => ['boolean'],
        ]);

        $action->execute([
            ...$this->formData,
            'is_active' => $this->formData['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        $this->toast()->success(__('certificate.template_saved'))->send();
        $this->showModal = false;
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('certification.certificate.certificate-template-manager');
    }
}
