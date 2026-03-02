<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Shared\Services\Contracts\EloquentQuery;
use Throwable;

/**
 * Class RecordManager
 *
 * Base abstract component for unified record management.
 * Acts as the "Client-side" manager with its own search and sort logic,
 * independent of the Service Layer ("Server") implementation.
 */
abstract class RecordManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected const MODAL_FORM = 'form-modal';
    protected const MODAL_CONFIRM = 'confirm-modal';
    protected const MODAL_IMPORT = 'import-modal';
    protected const DEFAULT_SORT_BY = 'created_at';
    protected const DEFAULT_SORT_DIR = 'desc';

    protected EloquentQuery $service;
    protected string $modelClass = '';
    
    /**
     * Client-side Logic: Define which columns are searchable and sortable in the UI.
     * These are independent of the Service Layer configuration.
     */
    protected array $searchable = [];
    protected array $sortable = [];

    public string $eventPrefix = '';
    protected string $viewPermission = '';
    protected string $createPermission = '';
    protected string $updatePermission = '';
    protected string $deletePermission = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url]
    public array $sortBy = ['column' => self::DEFAULT_SORT_BY, 'direction' => self::DEFAULT_SORT_DIR];

    #[Url(except: 10)]
    public int $perPage = 10;

    public bool $formModal = false;
    public bool $confirmModal = false;
    public bool $importModal = false;
    public ?string $recordId = null;
    public array $selectedIds = [];
    public $csvFile;

    /**
     * Contextual filters (Server-side scoping).
     */
    #[Url]
    public array $filters = [];

    public string $title = '';
    public string $subtitle = '';
    public string $context = '';
    public string $addLabel = '';
    public string $deleteConfirmMessage = '';
    public string $importInstructions = '';

    /**
     * Items synchronized for Alpine.js (Instant Client UI).
     */
    public array $items = [];

    abstract public function initialize(): void;
    abstract protected function getTableHeaders(): array;

    /**
     * Sync data for Alpine.js before rendering.
     */
    public function rendering(): void
    {
        $this->items = collect($this->records->items())
            ->map(fn ($item) => (array) $item)
            ->toArray();
    }

    /**
     * Resolves table headers for the UI.
     */
    #[Computed]
    public function headers(): array
    {
        return array_map(function ($header) {
            $header['sortable'] = ($header['sortable'] ?? false) === true;
            return $header;
        }, $this->getTableHeaders());
    }

    /**
     * Client-side formatting/transformation.
     */
    protected function mapRecord(mixed $record): array
    {
        return $record->toArray();
    }

    /**
     * Orchestrates the final data set using Client-side search and sort logic
     * on top of the Server-side base query.
     */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        // 1. Get base query from Server (Service Layer)
        // We only pass 'filters' (scoping), not UI-level search/sort.
        $query = $this->service->query($this->filters);

        // 2. Apply Client-side Search (Independent implementation)
        if ($this->search && ! empty($this->searchable)) {
            $query->where(function (Builder $q) {
                foreach ($this->searchable as $column) {
                    if (str_contains($column, '.')) {
                        $segments = explode('.', $column);
                        $col = array_pop($segments);
                        $relation = implode('.', $segments);
                        $q->orWhereRelation($relation, $col, 'like', "%{$this->search}%");
                    } else {
                        $q->orWhere($column, 'like', "%{$this->search}%");
                    }
                }
            });
        }

        // 3. Apply Client-side Sort (Independent implementation)
        $sortByColumn = $this->sortBy['column'] ?? self::DEFAULT_SORT_BY;
        $header = collect($this->getTableHeaders())->first(fn ($h) => $h['key'] === $sortByColumn);
        $dbSortColumn = $header['sort_by'] ?? $sortByColumn;

        if (in_array($dbSortColumn, $this->sortable) || $dbSortColumn === self::DEFAULT_SORT_BY) {
            $query->orderBy($dbSortColumn, $this->sortBy['direction'] ?? self::DEFAULT_SORT_DIR);
        }

        // 4. Paginate and apply client-side mapping
        return $query->paginate($this->perPage)->through(fn ($item) => (object) $this->mapRecord($item));
    }

    public function mount(): void
    {
        $this->initialize();
        if ($this->viewPermission) $this->authorize($this->viewPermission);
        $this->addLabel = $this->addLabel ?: __('ui::common.add');
        $this->deleteConfirmMessage = $this->deleteConfirmMessage ?: __('ui::common.delete_confirm');
    }

    public function updatedFilters(): void { $this->resetPage(); $this->selectedIds = []; }
    public function updatedSearch(): void { $this->resetPage(); $this->selectedIds = []; }

    public function can(string $action, mixed $target = null): bool
    {
        $isSetupAuthorized = session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) === true;
        if ($isSetupAuthorized) return true;
        $user = auth()->user();
        if (! $user) return false;
        $target = $target ?: ($this->modelClass ?: null);
        return match ($action) {
            'view' => $this->viewPermission ? $user->can($this->viewPermission) : true,
            'create' => $this->createPermission ? $user->can($this->createPermission) : true,
            'update' => $this->updatePermission ? $user->can($this->updatePermission) : ($target ? $user->can('update', $target) : true),
            'delete' => $this->deletePermission ? $user->can($this->deletePermission) : ($target ? $user->can('delete', $target) : true),
            default => false,
        };
    }

    public function add(): void { if (property_exists($this, 'form')) $this->form->reset(); $this->toggleModal(self::MODAL_FORM, true); }

    public function edit(mixed $id): void
    {
        $record = $this->service->find($id);
        if ($record) {
            if (! $this->can('update', $record)) $this->authorize('update', $record);
            if (property_exists($this, 'form')) {
                if (method_exists($this->form, 'setUser')) $this->form->setUser($record);
                else $this->form->fill($record);
                $this->toggleModal(self::MODAL_FORM, true, ['id' => $id]);
            }
        }
    }

    public function discard(mixed $id): void { $this->recordId = (string) $id; $this->toggleModal(self::MODAL_CONFIRM, true, ['id' => $id]); }

    public function save(): void
    {
        if (! property_exists($this, 'form')) return;
        $this->form->validate();
        $isSetupAuthorized = session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) === true;
        try {
            if ($isSetupAuthorized) $this->service->withoutAuthorization();
            if ($this->form->id) {
                $record = $this->service->find($this->form->id);
                if (! $isSetupAuthorized && $record && $this->updatePermission) \Illuminate\Support\Facades\Gate::authorize($this->updatePermission, $record);
                $this->service->update($this->form->id, $this->form->all());
            } else {
                if (! $isSetupAuthorized && $this->createPermission) {
                    $roles = property_exists($this->form, 'roles') ? $this->form->roles : null;
                    $authModel = $this->modelClass ?: config('auth.providers.users.model');
                    \Illuminate\Support\Facades\Gate::authorize($this->createPermission, [$authModel, $roles]);
                }
                $this->service->create($this->form->all());
            }
            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success('shared::messages.record_saved');
            $this->dispatch($this->getEventPrefix().':saved', exists: true);
        } catch (Throwable $e) {
            if (is_debug_mode()) throw $e;
            flash()->error('shared::messages.error_occurred');
        }
    }

    public function remove(mixed $id = null): void
    {
        $id = $id ?: $this->recordId;
        $record = $this->service->find($id);
        if ($record) {
            $isSetupAuthorized = session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) === true;
            if ($isSetupAuthorized) $this->service->withoutAuthorization();
            else $this->authorize('delete', $record);
            if ($this->service->delete($id)) {
                $this->toggleModal(self::MODAL_CONFIRM, false);
                $this->recordId = null;
                flash()->success('shared::messages.record_deleted');
                $this->dispatch($this->getEventPrefix().':deleted', exists: $this->service->exists());
            }
        }
    }

    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) return;
        $isSetupAuthorized = session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) === true;
        try {
            if ($isSetupAuthorized) $this->service->withoutAuthorization();
            else {
                $records = $this->service->query()->whereIn('id', $this->selectedIds)->get();
                foreach ($records as $record) if ($record) $this->authorize('delete', $record);
            }
            $count = $this->service->destroy($this->selectedIds);
            $this->selectedIds = [];
            flash()->success(__('shared::messages.records_deleted', ['count' => $count]));
            $this->dispatch($this->getEventPrefix().':bulk-deleted', count: $count);
        } catch (Throwable $e) { flash()->error($e->getMessage()); }
    }

    public function exportCsv() {
        $records = $this->service->all();
        $filename = $this->getEventPrefix().'-'.now()->format('Y-m-d-His').'.csv';
        $headers = $this->getExportHeaders();
        return response()->streamDownload(function () use ($records, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headers));
            foreach ($records as $record) fputcsv($file, $this->mapRecordForExport($record, array_keys($headers)));
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function downloadTemplate() {
        $filename = $this->getEventPrefix().'-template.csv';
        $headers = $this->getExportHeaders();
        return response()->streamDownload(function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($headers));
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importCsv(): void {
        $this->validate(['csvFile' => 'required|mimes:csv,txt|max:2048']);
        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');
        fgetcsv($file);
        $data = [];
        $keys = array_keys($this->getExportHeaders());
        while (($row = fgetcsv($file)) !== false) if ($mapped = $this->mapImportRow($row, $keys)) $data[] = $mapped;
        fclose($file);
        if (empty($data)) { flash()->error(__('ui::common.error')); return; }
        $count = $this->service->import($data);
        $this->importModal = false;
        $this->csvFile = null;
        $this->dispatch($this->getEventPrefix().':imported');
        flash()->success(__('ui::common.imported_successfully', ['count' => $count]));
    }

    public function printPdf() {
        $records = $this->service->all();
        if (! $view = $this->getPdfView()) { flash()->error(__('shared::exceptions.pdf_view_undefined')); return null; }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $this->getPdfData($records));
        return response()->streamDownload(fn () => print $pdf->output(), $this->getEventPrefix().'-'.now()->format('Y-m-d').'.pdf');
    }

    protected function getExportHeaders(): array { return ['id' => 'ID', 'created_at' => 'Created At']; }
    protected function mapRecordForExport($record, array $keys): array { return array_map(fn ($key) => $record->{$key}, $keys); }
    protected function mapImportRow(array $row, array $keys): ?array { $data = []; foreach ($keys as $index => $key) $data[$key] = $row[$index] ?? null; return $data; }
    protected function getPdfView(): ?string { return null; }
    protected function getPdfData($records): array { return ['records' => $records, 'date' => now()->translatedFormat('d F Y')]; }
    protected function toggleModal(string $name, bool $visible, array $params = []): void { $property = $name === self::MODAL_FORM ? 'formModal' : 'confirmModal'; $this->{$property} = $visible; $this->dispatch($this->getEventPrefix().':'.($visible ? 'open-modal' : 'close-modal'), $name, $params); }
    protected function getEventPrefix(): string { return $this->eventPrefix ?: strtolower(class_basename($this)); }
    protected function getListeners(): array { return [$this->getEventPrefix().':destroy-record' => 'remove']; }
}
