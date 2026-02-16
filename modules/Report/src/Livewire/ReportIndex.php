<?php

declare(strict_types=1);

namespace Modules\Report\Livewire;

use Livewire\Component;
use Modules\Report\Services\Contracts\ReportGenerator;
use Modules\Shared\Contracts\ExportableDataProvider;

class ReportIndex extends Component
{
    /**
     * The report filters.
     *
     * @var array<string, mixed>
     */
    public array $filters = [];

    /**
     * The selected report provider identifier.
     */
    public ?string $selectedProvider = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->filters['academic_year'] = setting('active_academic_year');
    }

    /**
     * Get the available report providers.
     *
     * @return array<int, array<string, string>>
     */
    public function getProvidersProperty(): array
    {
        /** @var \Modules\Report\Services\ReportService $service */
        $service = app(ReportGenerator::class);

        return $service
            ->getProviders()
            ->map(
                fn(ExportableDataProvider $p) => [
                    'id' => $p->getIdentifier(),
                    'label' => $p->getLabel(),
                ],
            )
            ->values()
            ->toArray();
    }

    /**
     * Get the available internships.
     */
    public function getInternshipsProperty()
    {
        return app(\Modules\Internship\Services\Contracts\InternshipService::class)
            ->all(['id', 'title'])
            ->map(fn($i) => ['id' => $i->id, 'name' => $i->title])
            ->toArray();
    }

    /**
     * Get the report history.
     */
    public function getHistoryProperty()
    {
        return app(\Modules\Report\Services\Contracts\GeneratedReportService::class)
            ->query()
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Generate the selected report.
     */
    public function generate(): void
    {
        if (!$this->selectedProvider) {
            return;
        }

        /** @var ReportGenerator $service */
        $service = app(ReportGenerator::class);

        try {
            $fileName = $service->generate($this->selectedProvider, $this->filters);

            notify(__('report::messages.generated', ['file' => $fileName]), 'success');
        } catch (\Exception $e) {
            notify($e->getMessage(), 'error');
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('report::livewire.report-index');
    }
}
