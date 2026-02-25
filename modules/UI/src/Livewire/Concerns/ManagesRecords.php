<?php

declare(strict_types=1);

namespace Modules\UI\Livewire\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Trait ManagesRecords
 *
 * Provides a standardized workflow for managing Eloquent records within Livewire components.
 * This trait orchestrates pagination, sorting, searching, and basic CRUD interactions,
 * delegating business logic to the injected Service layer.
 */
trait ManagesRecords
{
    use WithFileUploads;
    use WithPagination;

    /**
     * The standard sorting configurations.
     */
    protected const DEFAULT_SORT_BY = 'created_at';

    protected const DEFAULT_SORT_DIR = 'desc';

    /**
     * Standard modal and event identifiers.
     */
    protected const MODAL_FORM = 'form-modal';

    protected const MODAL_CONFIRM = 'confirm-modal';

    protected const MODAL_IMPORT = 'import-modal';

    /**
     * The service instance responsible for domain logic.
     */
    protected EloquentQuery $service;

    /**
     * Optional prefix for component-specific events.
     */
    public string $eventPrefix = '';

    /**
     * Search query for filtering records.
     */
    #[Url(except: '')]
    public string $search = '';

    /**
     * The sorting configuration (column and direction).
     *
     * @var array{column: string, direction: string}
     */
    #[Url]
    public array $sortBy = [
        'column' => self::DEFAULT_SORT_BY,
        'direction' => self::DEFAULT_SORT_DIR,
    ];

    /**
     * Number of records to display per page.
     */
    #[Url(except: EloquentQuery::DEFAULT_PER_PAGE)]
    public int $perPage = EloquentQuery::DEFAULT_PER_PAGE;

    /**
     * Visibility state of the form modal.
     */
    public bool $formModal = false;

    /**
     * Visibility state of the confirmation modal.
     */
    public bool $confirmModal = false;

    /**
     * Visibility state of the import modal.
     */
    public bool $importModal = false;

    /**
     * The ID of the record currently being processed (e.g., for deletion).
     */
    public ?string $recordId = null;

    /**
     * Selected record IDs for bulk actions.
     */
    public array $selectedIds = [];

    /**
     * The file instance for CSV imports.
     */
    public $csvFile;

    /**
     * Initializes the trait's default state.
     */
    public function initializeManagesRecords(): void
    {
        $this->search = '';
        $this->perPage = EloquentQuery::DEFAULT_PER_PAGE;
        $this->sortBy = ['column' => self::DEFAULT_SORT_BY, 'direction' => self::DEFAULT_SORT_DIR];
        $this->selectedIds = [];
    }

    /**
     * Handle updated search term by resetting pagination.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    /**
     * Retrieves the paginated collection of records.
     */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        $filters = array_filter(
            [
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? self::DEFAULT_SORT_BY,
                'sort_dir' => $this->sortBy['direction'] ?? self::DEFAULT_SORT_DIR,
            ],
            fn ($value) => $value !== null && $value !== '',
        );

        return $this->service->paginate($filters, $this->perPage);
    }

    /**
     * Prepares the component for creating a new record.
     */
    public function add(): void
    {
        if (property_exists($this, 'form')) {
            $this->form->reset();
        }

        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * Prepares the component for editing an existing record.
     */
    public function edit(mixed $id): void
    {
        $record = $this->service->find($id);

        if ($record && property_exists($this, 'form')) {
            $this->form->fill($record);
            $this->toggleModal(self::MODAL_FORM, true, ['id' => $id]);
        }
    }

    /**
     * Triggers the confirmation flow for removing a record.
     */
    public function discard(mixed $id): void
    {
        $this->recordId = (string) $id;
        $this->toggleModal(self::MODAL_CONFIRM, true, ['id' => $id]);
    }

    /**
     * Persists the record changes to the database.
     */
    public function save(): void
    {
        if (property_exists($this, 'form')) {
            $this->form->validate();

            $this->service->save(['id' => $this->form->id], $this->form->except('id'));

            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success(__('shared::messages.record_saved'));
            $this->dispatch($this->getEventPrefix().':saved', exists: true);
        }
    }

    /**
     * Finalizes the removal of a record.
     */
    public function remove(mixed $id = null): void
    {
        $id = $id ?: $this->recordId;

        if ($id && $this->service->delete($id)) {
            $this->toggleModal(self::MODAL_CONFIRM, false);
            $this->recordId = null;
            flash()->success(__('shared::messages.record_deleted'));
            $this->dispatch($this->getEventPrefix().':deleted', exists: $this->service->exists());
        }
    }

    /**
     * Remove all selected records.
     */
    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $count = $this->service->destroy($this->selectedIds);
            $this->selectedIds = [];
            flash()->success($this->getBulkDeleteMessage($count));
            $this->dispatch($this->getEventPrefix().':bulk-deleted', count: $count);
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Get the success message after a bulk delete operation.
     */
    protected function getBulkDeleteMessage(int $count): string
    {
        return __('shared::messages.records_deleted', ['count' => $count]);
    }

    /**
     * Export all records to CSV format.
     */
    public function exportCsv()
    {
        $records = $this->service->all();
        $filename = $this->getEventPrefix().'-'.now()->format('Y-m-d-His').'.csv';
        $headers = $this->getExportHeaders();

        $callback = function () use ($records, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headers));

            foreach ($records as $record) {
                fputcsv($file, $this->mapRecordForExport($record, array_keys($headers)));
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Downloads a CSV template for bulk data ingestion.
     */
    public function downloadTemplate()
    {
        $filename = $this->getEventPrefix().'-template.csv';
        $headers = $this->getExportHeaders();

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headers));
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Processes bulk record ingestion from a CSV file.
     */
    public function importCsv(): void
    {
        $this->validate([
            'csvFile' => 'required|mimes:csv,txt|max:2048',
        ]);

        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');
        fgetcsv($file); // Skip authoritative header row

        $data = [];
        $keys = array_keys($this->getExportHeaders());

        while (($row = fgetcsv($file)) !== false) {
            $mapped = $this->mapImportRow($row, $keys);
            if ($mapped) {
                $data[] = $mapped;
            }
        }
        fclose($file);

        if (empty($data)) {
            flash()->error(__('ui::common.error'));

            return;
        }

        $count = $this->service->import($data);

        $this->importModal = false;
        $this->csvFile = null;

        $this->dispatch($this->getEventPrefix().':imported');
        flash()->success(__('ui::common.imported_successfully', ['count' => $count]));
    }

    /**
     * Orchestrates the generation of a domain PDF document.
     */
    public function printPdf()
    {
        $records = $this->service->all();
        $view = $this->getPdfView();

        if (! $view) {
            flash()->error(__('shared::exceptions.pdf_view_undefined'));

            return null;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $this->getPdfData($records));

        return response()->streamDownload(
            fn () => print $pdf->output(),
            $this->getEventPrefix().'-'.now()->format('Y-m-d').'.pdf',
        );
    }

    /**
     * Defines the authoritative headers for CSV operations.
     */
    protected function getExportHeaders(): array
    {
        return ['id' => 'ID', 'created_at' => 'Created At'];
    }

    /**
     * Maps a singular domain entity into a CSV-compatible array.
     */
    protected function mapRecordForExport($record, array $keys): array
    {
        return array_map(fn ($key) => $record->{$key}, $keys);
    }

    /**
     * Maps a raw CSV row into a domain-specific attribute set.
     */
    protected function mapImportRow(array $row, array $keys): ?array
    {
        $data = [];
        foreach ($keys as $index => $key) {
            $data[$key] = $row[$index] ?? null;
        }

        return $data;
    }

    /**
     * Resolves the view path for PDF rendering.
     */
    protected function getPdfView(): ?string
    {
        return null;
    }

    /**
     * Constructs the data payload for the PDF view.
     */
    protected function getPdfData($records): array
    {
        return [
            'records' => $records,
            'date' => now()->translatedFormat('d F Y'),
        ];
    }

    /**
     * Manages modal visibility and dispatches corresponding UI events.
     */
    protected function toggleModal(string $name, bool $visible, array $params = []): void
    {
        $property = $name === self::MODAL_FORM ? 'formModal' : 'confirmModal';
        $this->{$property} = $visible;

        $event = $visible ? 'open-modal' : 'close-modal';
        $this->dispatch($this->getEventPrefix().':'.$event, $name, $params);
    }

    /**
     * Resolves the event prefix for the component.
     */
    protected function getEventPrefix(): string
    {
        return $this->eventPrefix ?: strtolower(class_basename($this));
    }

    /**
     * Registers component listeners.
     */
    protected function getListeners(): array
    {
        return [
            $this->getEventPrefix().':destroy-record' => 'remove',
        ];
    }
}
