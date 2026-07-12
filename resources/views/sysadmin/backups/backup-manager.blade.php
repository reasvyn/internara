@php
    use App\SysAdmin\Backups\Enums\BackupType;
    use App\SysAdmin\Backups\Enums\BackupStatus;
@endphp

<div>
    <x-mary-header :title="__('backups.title')" :subtitle="__('backups.subtitle')" separator>
        <x-slot:actions>
            <x-mary-dropdown label="{{ __('backups.create_button') }}" icon="o-arrow-path" class="btn-primary">
                <x-slot:trigger>
                    <x-mary-button label="{{ __('backups.create_button') }}" icon="o-arrow-path" class="btn-primary" />
                </x-slot:trigger>
                <x-mary-menu-item title="{{ BackupType::DATABASE->label() }}" wire:click="createBackup('{{ BackupType::DATABASE->value }}')" icon="o-circle-stack" />
                <x-mary-menu-item title="{{ BackupType::STORAGE->label() }}" wire:click="createBackup('{{ BackupType::STORAGE->value }}')" icon="o-folder" />
                <x-mary-menu-item title="{{ BackupType::BOTH->label() }}" wire:click="createBackup('{{ BackupType::BOTH->value }}')" icon="o-archive-box" />
            </x-mary-dropdown>
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-mary-stat
            title="{{ __('backups.total') }}"
            value="{{ $this->stats['total'] }}"
            icon="o-archive-box"
            class="bg-base-200"
        />
        <x-mary-stat
            title="{{ __('backups.completed') }}"
            value="{{ $this->stats['completed'] }}"
            icon="o-check-circle"
            class="text-success"
        />
        <x-mary-stat
            title="{{ __('backups.failed') }}"
            value="{{ $this->stats['failed'] }}"
            icon="o-exclamation-circle"
            class="text-error"
        />
        <x-mary-stat
            title="{{ __('backups.latest') }}"
            value="{{ $this->stats['latest']?->asBackupState()->formattedSize() ?? '--' }}"
            icon="o-clock"
            class="bg-base-200"
        />
    </div>

    <x-mary-card>
        <div class="flex gap-4 mb-4">
            <x-mary-select
                label="{{ __('backups.filter_type') }}"
                wire:model.live="filterType"
                :options="[
                    ['value' => '', 'label' => __('common.all')],
                    ...collect(BackupType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->toArray(),
                ]"
                class="w-48"
            />
            <x-mary-select
                label="{{ __('backups.filter_status') }}"
                wire:model.live="filterStatus"
                :options="[
                    ['value' => '', 'label' => __('common.all')],
                    ...collect(BackupStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->toArray(),
                ]"
                class="w-48"
            />
        </div>

        <x-mary-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination>
            @scope('cell_type', $backup)
                <x-mary-badge :value="$backup->type" />
            @endscope

            @scope('cell_status', $backup)
                @php
                    $statusClass = match ($backup->status) {
                        'completed' => 'success',
                        'failed' => 'error',
                        'running' => 'warning',
                        default => 'info',
                    };
                @endphp
                <x-mary-badge :value="$backup->status" class="badge-{{ $statusClass }}" />
            @endscope

            @scope('cell_file_size', $backup)
                {{ $backup->asBackupState()->formattedSize() }}
            @endscope

            @scope('cell_actions', $backup)
                <div class="flex gap-1">
                    @if ($backup->asBackupState()->isDeletable())
                        <x-mary-button
                            icon="o-trash"
                            class="btn-ghost btn-sm text-error"
                            wire:click="confirmDelete('{{ $backup->id }}')"
                            wire:loading.attr="disabled"
                        />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showConfirmDelete" title="{{ __('backups.confirm_delete_title') }}" class="backdrop-blur">
        <p>{{ __('backups.confirm_delete_message') }}</p>

        <x-slot:actions>
            <x-mary-button label="{{ __('common.cancel') }}" wire:click="cancelDelete" />
            <x-mary-button label="{{ __('common.delete') }}" wire:click="delete" class="btn-error" />
        </x-slot:actions>
    </x-mary-modal>

    @include('sysadmin.backups.components.backup-guide')
</div>
