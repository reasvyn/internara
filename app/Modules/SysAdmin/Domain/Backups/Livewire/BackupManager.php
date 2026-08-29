<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backups\Livewire;

use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\SysAdmin\Domain\Backups\Actions\CreateBackupAction;
use App\Modules\SysAdmin\Domain\Backups\Actions\DeleteBackupAction;
use App\Modules\SysAdmin\Domain\Backups\Actions\ReadBackupStatsAction;
use App\Modules\SysAdmin\Domain\Backups\Enums\BackupType;
use App\Modules\SysAdmin\Domain\Backups\Models\Backup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

final class BackupManager extends BaseRecordManager
{
    use AuthorizesRequests;
    use Interactions;

    public ?string $deleteId = null;

    public string $filterType = '';

    public string $filterStatus = '';

    private ReadBackupStatsAction $statsAction;

    public function boot(ReadBackupStatsAction $statsAction): void
    {
        $this->authorize('viewAny', Backup::class);

        $this->statsAction = $statsAction;
    }

    public function headers(): array
    {
        return [
            ['index' => 'type', 'label' => __('backups.type_label'), 'sortable' => true],
            ['index' => 'status', 'label' => __('backups.status_label'), 'sortable' => true],
            ['index' => 'file_size', 'label' => __('backups.size_label'), 'sortable' => true],
            ['index' => 'creator.name', 'label' => __('backups.created_by_label')],
            ['index' => 'created_at', 'label' => __('backups.date_label'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
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
        return $this->statsAction->execute();
    }

    public function createBackup(string $type, CreateBackupAction $action): void
    {
        $this->authorize('create', Backup::class);

        $backupType = BackupType::from($type);

        try {
            $action->execute($backupType, auth()->user());
            $this->toast()->success(__('backups.create_success'))->send();
        } catch (\Throwable $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function confirmDelete(string $id): void
    {
        $this->deleteId = $id;
        $this->dialog()
            ->question(__('common.actions.confirm_action'), __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmDelete')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function delete(DeleteBackupAction $action): void
    {
        $backup = Backup::findOrFail($this->deleteId);

        $this->authorize('delete', $backup);

        $action->execute($backup);
        $this->deleteId = null;

        $this->toast()->success(__('backups.delete_success'))->send();
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('sysadmin.backups.backup-manager');
    }
}
