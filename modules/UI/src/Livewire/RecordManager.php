<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Permission\Enums\Permission;
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

    #[Url(except: '')]
    public string $search = '';

    #[Url]
    public array $sortBy = [
        'column' => self::DEFAULT_SORT_BY,
        'direction' => self::DEFAULT_SORT_DIR,
    ];

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
        try {
            $this->items = collect($this->records->items())
                ->map(fn($item) => $item->toArray())
                ->toArray();
        } catch (\Exception $e) {
            $this->items = [];
        }
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
     * Define relationships to eager load for the primary record set.
     */
    protected function getWith(): array
    {
        return [];
    }

    /**
     * Define specific columns to select for performance.
     */
    protected function getColumns(): array
    {
        return ['*'];
    }

    /**
     * Apply module-specific query scoping or additional constraints.
     */
    protected function applyScoping(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Orchestrates the final data set using Client-side search and sort logic
     * on top of the Server-side base query.
     */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        $isSetupAuthorized = (bool) session('setup_authorized');

        if ($isSetupAuthorized) {
            $this->service->withoutAuthorization();
        }

        // 1. Resolve base query with module hooks
        $query = $this->service->query($this->filters, $this->getColumns());
        $query = $this->applyScoping($query);
        $query->with($this->getWith());

        // 2. Apply Client-side Search (Independent implementation)
        if ($this->search && !empty($this->searchable)) {
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
        $header = collect($this->getTableHeaders())->first(fn($h) => $h['key'] === $sortByColumn);
        $dbSortColumn = $header['sort_by'] ?? $sortByColumn;

        if (in_array($dbSortColumn, $this->sortable) || $dbSortColumn === self::DEFAULT_SORT_BY) {
            $query->orderBy($dbSortColumn, $this->sortBy['direction'] ?? self::DEFAULT_SORT_DIR);
        }

        // 4. Paginate and apply client-side mapping
        return $query->paginate($this->perPage)->through(function ($item) {
            $mapped = $this->mapRecord($item);

            // If mapping returns an array, we merge it into the model instance
            // to preserve the class identity for Gate/Policy checks.
            if (is_array($mapped)) {
                foreach ($mapped as $key => $value) {
                    // We only set properties that don't collide with model relations
                    // or essential attributes to avoid breaking the model.
                    if (!$item->relationLoaded($key)) {
                        $item->{$key} = $value;
                    }
                }
            }

            return $item;
        });
    }

    public function mount(): void
    {
        $this->initialize();
        if ($this->viewPermission) {
            $this->authorize($this->viewPermission);
        }
        $this->addLabel = $this->addLabel ?: __('ui::common.add');
        $this->deleteConfirmMessage =
            $this->deleteConfirmMessage ?: __('ui::common.delete_confirm');
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    public function refreshRecords(): void
    {
        $this->selectedIds = [];
    }

    public function can(string $action, mixed $target = null): bool
    {
        $isSetupAuthorized = (bool) session('setup_authorized');
        if ($isSetupAuthorized) {
            return true;
        }
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        $permission = $this->resolvePermission($action);

        return match ($action) {
            'view', 'create', 'update', 'delete' => $permission
                ? $user->can($permission->value)
                : ($target ? $user->can($action, $target) : true),
            default => false,
        };
    }

    protected function resolvePermission(string $action): ?Permission
    {
        return match ($action) {
            'view' => $this->viewPermission,
            'create' => $this->createPermission,
            'update' => $this->updatePermission,
            'delete' => $this->deletePermission,
            default => null,
        };
    }

    public function add(): void
    {
        if (property_exists($this, 'form')) {
            $this->form->reset();
        }
        $this->toggleModal(self::MODAL_FORM, true);
    }

    public function edit(mixed $id): void
    {
        $record = $this->service->find($id);
        if ($record) {
            if (!$this->can('update', $record)) {
                $this->authorize('update', $record);
            }
            if (property_exists($this, 'form')) {
                if (method_exists($this->form, 'setUser')) {
                    $this->form->setUser($record);
                } else {
                    $this->form->fill($record);
                }
                $this->toggleModal(self::MODAL_FORM, true, ['id' => $id]);
            }
        }
    }

    public function discard(mixed $id): void
    {
        $this->recordId = (string) $id;
        $this->toggleModal(self::MODAL_CONFIRM, true, ['id' => $id]);
    }

    public function save(): void
    {
        if (!property_exists($this, 'form')) {
            return;
        }
        $this->form->validate();
        $isSetupAuthorized = (bool) session('setup_authorized');
        try {
            if ($isSetupAuthorized) {
                $this->service->withoutAuthorization();
            }
            if ($this->form->id) {
                $record = $this->service->find($this->form->id);
                if (!$isSetupAuthorized && $record && $this->updatePermission) {
                    Gate::authorize($this->updatePermission, $record);
                }
                $this->service->update($this->form->id, $this->form->all());
            } else {
                if (!$isSetupAuthorized && $this->createPermission) {
                    $roles = property_exists($this->form, 'roles') ? $this->form->roles : null;
                    $authModel = $this->modelClass ?: config('auth.providers.users.model');
                    Gate::authorize($this->createPermission->value, [$authModel, $roles]);
                }
                $this->service->create($this->form->all());
            }
            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success('shared::messages.record_saved');
            $this->dispatch($this->getEventPrefix() . ':saved', exists: true);
        } catch (Throwable $e) {
            if (is_debug_mode()) {
                throw $e;
            }
            flash()->error('shared::messages.error_occurred');
        }
    }

    public function remove(mixed $id = null): void
    {
        $id = $id ?: $this->recordId;
        $record = $this->service->find($id);
        if ($record) {
            $isSetupAuthorized = (bool) session('setup_authorized');
            if ($isSetupAuthorized) {
                $this->service->withoutAuthorization();
            } else {
                $this->authorize('delete', $record);
            }
            if ($this->service->delete($id)) {
                $this->toggleModal(self::MODAL_CONFIRM, false);
                $this->recordId = null;
                flash()->success('shared::messages.record_deleted');
                $this->dispatch(
                    $this->getEventPrefix() . ':deleted',
                    exists: $this->service->exists(),
                );
            }
        }
    }

    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }
        $isSetupAuthorized = (bool) session('setup_authorized');
        try {
            if ($isSetupAuthorized) {
                $this->service->withoutAuthorization();
            } else {
                $records = $this->service->query()->whereIn('id', $this->selectedIds)->get();
                foreach ($records as $record) {
                    if ($record) {
                        $this->authorize('delete', $record);
                    }
                }
            }
            $count = $this->service->destroy($this->selectedIds);
            $this->selectedIds = [];
            flash()->success(__('shared::messages.records_deleted', ['count' => $count]));
            $this->dispatch($this->getEventPrefix() . ':bulk-deleted', count: $count);
        } catch (Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function exportCsv()
    {
        $records = $this->getExportQuery()->get();
        $filename = $this->getEventPrefix() . '-' . now()->format('Y-m-d-His') . '.csv';
        $headers = $this->getExportHeaders();

        return response()->streamDownload(
            function () use ($records, $headers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($headers));
                foreach ($records as $record) {
                    fputcsv($file, $this->mapRecordForExport($record, array_keys($headers)));
                }
                fclose($file);
            },
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    public function downloadTemplate()
    {
        $filename = $this->getEventPrefix() . '-template.csv';
        $headers = $this->getTemplateHeaders();

        return response()->streamDownload(
            function () use ($headers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($headers));
                fclose($file);
            },
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    public function importCsv(): void
    {
        $this->validate(['csvFile' => 'required|mimes:csv,txt|max:2048']);
        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');
        fgetcsv($file);
        $data = [];
        $keys = array_keys($this->getTemplateHeaders());
        while (($row = fgetcsv($file)) !== false) {
            if ($mapped = $this->mapImportRow($row, $keys)) {
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
        $this->dispatch($this->getEventPrefix() . ':imported');
        flash()->success(__('ui::common.imported_successfully', ['count' => $count]));
    }

    public function printPdf()
    {
        $records = $this->getExportQuery()->get();
        if (!($view = $this->getPdfView())) {
            flash()->error(__('shared::exceptions.pdf_view_undefined'));

            return null;
        }
        $pdf = Pdf::loadView($view, $this->getPdfData($records));

        return response()->streamDownload(
            fn() => print $pdf->output(),
            $this->getEventPrefix() . '-' . now()->format('Y-m-d') . '.pdf',
        );
    }

    protected function getExportHeaders(): array
    {
        return ['id' => 'ID', 'created_at' => 'Created At'];
    }

    protected function getTemplateHeaders(): array
    {
        return $this->getExportHeaders();
    }

    protected function mapRecordForExport($record, array $keys): array
    {
        return array_map(fn($key) => $record->{$key}, $keys);
    }

    protected function mapImportRow(array $row, array $keys): ?array
    {
        $data = [];
        foreach ($keys as $index => $key) {
            $data[$key] = $row[$index] ?? null;
        }

        return $data;
    }

    protected function getExportQuery(): Builder
    {
        return $this->service->query($this->filters);
    }

    protected function getPdfView(): ?string
    {
        return null;
    }

    protected function getPdfData($records): array
    {
        return ['records' => $records, 'date' => now()->translatedFormat('d F Y')];
    }

    protected function toggleModal(string $name, bool $visible, array $params = []): void
    {
        $property = $name === self::MODAL_FORM ? 'formModal' : 'confirmModal';
        $this->{$property} = $visible;
        $this->dispatch(
            $this->getEventPrefix() . ':' . ($visible ? 'open-modal' : 'close-modal'),
            $name,
            $params,
        );
    }

    protected function getEventPrefix(): string
    {
        return $this->eventPrefix ?: strtolower(class_basename($this));
    }

    protected function getListeners(): array
    {
        return [$this->getEventPrefix() . ':destroy-record' => 'remove'];
    }
}
