<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Livewire;

use App\Core\Livewire\BaseRecordManager;
use App\SysAdmin\Backups\Actions\CreateBackupAction;
use App\SysAdmin\Backups\Actions\DeleteBackupAction;
use App\SysAdmin\Backups\Enums\BackupType;
use App\SysAdmin\Backups\Models\Backup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

final class BackupManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $showConfirmDelete = false;

    public ?string $deleteId = null;

    public string $filterType = '';

    public string $filterStatus = '';

    public function boot(): void
    {
        $this->authorize('viewAny', Backup::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'type', 'label' => __('backups.type_label'), 'sortable' => true],
            ['key' => 'status', 'label' => __('backups.status_label'), 'sortable' => true],
            ['key' => 'file_size', 'label' => __('backups.size_label'), 'sortable' => true],
            ['key' => 'creator.name', 'label' => __('backups.created_by_label')],
            ['key' => 'created_at', 'label' => __('backups.date_label'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Backup::query()->with('creator');
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filterType, fn ($q, $t) => $q->where('type', $t))
            ->when($this->filterStatus, fn ($q, $s) => $q->where('status', $s));
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Backup::count(),
            'completed' => Backup::where('status', 'completed')->count(),
            'failed' => Backup::where('status', 'failed')->count(),
            'latest' => Backup::where('status', 'completed')->latest()->first(),
        ];
    }

    public function createBackup(string $type): void
    {
        $this->authorize('create', Backup::class);

        $backupType = BackupType::from($type);

        try {
            app(CreateBackupAction::class)->execute($backupType, auth()->user());
            flash()->success(__('backups.create_success'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function confirmDelete(string $id): void
    {
        $this->deleteId = $id;
        $this->showConfirmDelete = true;
    }

    public function delete(DeleteBackupAction $action): void
    {
        $backup = Backup::findOrFail($this->deleteId);

        $this->authorize('delete', $backup);

        $action->execute($backup);

        $this->showConfirmDelete = false;
        $this->deleteId = null;

        flash()->success(__('backups.delete_success'));
    }

    public function cancelDelete(): void
    {
        $this->showConfirmDelete = false;
        $this->deleteId = null;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('sysadmin.backups.backup-manager');
    }
}
