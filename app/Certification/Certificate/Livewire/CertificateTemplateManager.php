<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Livewire;

use App\Certification\Certificate\Actions\CreateCertificateTemplateAction;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Core\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;

class CertificateTemplateManager extends BaseRecordManager
{
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
            ['key' => 'name', 'label' => __('certificate.template_name'), 'sortable' => true],
            ['key' => 'layout', 'label' => __('certificate.layout')],
            ['key' => 'is_active', 'label' => __('certificate.is_active')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
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

        flash()->success(__('certificate.template_saved'));
        $this->showModal = false;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('certification.certificate.certificate-template-manager');
    }
}
