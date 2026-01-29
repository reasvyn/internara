<?php

declare(strict_types=1);

namespace Modules\Report\Services;

use Illuminate\Support\Collection;
use Modules\Report\Jobs\GenerateReportJob;
use Modules\Report\Services\Contracts\ReportGenerator;
use Modules\Shared\Contracts\ExportableDataProvider;

class ReportService implements ReportGenerator
{
    /** @var Collection<string, ExportableDataProvider> */
    protected Collection $providers;

    public function __construct()
    {
        $this->providers = collect();
    }

    /**
     * Register a new data provider for reports.
     */
    public function registerProvider(ExportableDataProvider $provider): void
    {
        $this->providers->put($provider->getIdentifier(), $provider);
    }

    /**
     * {@inheritdoc}
     */
    public function queue(
        string $providerIdentifier,
        array $filters = [],
        ?string $userId = null,
    ): string {
        $jobId = uniqid('report_');
        $userId = $userId ?: auth()->id();

        GenerateReportJob::dispatch($providerIdentifier, $filters, $jobId, $userId);

        return $jobId;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(
        string $providerIdentifier,
        array $filters = [],
        ?string $userId = null,
    ): string {
        $provider = $this->providers->get($providerIdentifier);

        if (! $provider) {
            throw new \RuntimeException("Report Provider [{$providerIdentifier}] not registered.");
        }

        $data = $provider->getReportData($filters);
        $fileName = "reports/{$providerIdentifier}_".now()->format('YmdHis').'.pdf';
        $template = $provider->getTemplate();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($template, [
            'title' => $provider->getLabel(),
            'data' => $data,
            'filters' => $filters,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put($fileName, $pdf->output());

        $userId = $userId ?: auth()->id();

        // Persist metadata using local model
        \Modules\Report\Models\GeneratedReport::create([
            'user_id' => $userId,
            'provider_identifier' => $providerIdentifier,
            'file_path' => $fileName,
            'filters' => $filters,
        ]);

        return $fileName;
    }

    /**
     * {@inheritdoc}
     */
    public function getProviders(): Collection
    {
        return $this->providers;
    }
}
