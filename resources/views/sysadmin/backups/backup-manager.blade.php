@php
    use App\SysAdmin\Backups\Enums\BackupType;
    use App\SysAdmin\Backups\Enums\BackupStatus;
@endphp

<div>
    <x-header :title="__('backups.title')" :subtitle="__('backups.subtitle')" separator>
        <x-slot:actions>
            <x-dropdown label="{{ __('backups.create_button') }}" icon="o-arrow-path" class="btn-primary">
                <x-dropdown.item
                    label="{{ BackupType::DATABASE->label() }}"
                    wire:click="createBackup('{{ BackupType::DATABASE->value }}')"
                    icon="o-circle-stack"
                />
                <x-dropdown.item
                    label="{{ BackupType::STORAGE->label() }}"
                    wire:click="createBackup('{{ BackupType::STORAGE->value }}')"
                    icon="o-folder"
                />
                <x-dropdown.item
                    label="{{ BackupType::BOTH->label() }}"
                    wire:click="createBackup('{{ BackupType::BOTH->value }}')"
                    icon="o-archive-box"
                />
            </x-dropdown>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat
            title="{{ __('backups.total') }}"
            value="{{ $this->stats['total'] }}"
            icon="o-archive-box"
            class="bg-base-200"
        />
        <x-stat
            title="{{ __('backups.completed') }}"
            value="{{ $this->stats['completed'] }}"
            icon="o-check-circle"
            class="text-success"
        />
        <x-stat
            title="{{ __('backups.failed') }}"
            value="{{ $this->stats['failed'] }}"
            icon="o-exclamation-circle"
            class="text-error"
        />
        <x-stat
            title="{{ __('backups.latest') }}"
            value="{{ $this->stats['latest']?->asBackupState()->formattedSize() ?? '--' }}"
            icon="o-clock"
            class="bg-base-200"
        />
    </div>

    <x-card>
        <div class="flex gap-4 mb-4">
            <x-select
                label="{{ __('backups.filter_type') }}"
                wire:model.live="filterType"
                :options="[
                    ['value' => '', 'label' => __('common.all')],
                    ...collect(BackupType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->toArray(),
                ]"
                class="w-48"
            />
            <x-select
                label="{{ __('backups.filter_status') }}"
                wire:model.live="filterStatus"
                :options="[
                    ['value' => '', 'label' => __('common.all')],
                    ...collect(BackupStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->toArray(),
                ]"
                class="w-48"
            />
        </div>

        <x-table :headers="$this->headers()" :rows="$this->data" :sort="$this->sort" link="javascript:void(0)" with-pagination>
            @scope('cell_type', $backup)
                <x-badge :value="$backup->type" />
            @endscope

            @scope('cell_status', $backup)
                @php
                    $statusClass = match ($backup->status) {
                        'completed' => 'success',
                        'failed' => 'error',
                        'running' => 'warning',
                        default => 'info',
                    };
                @endscope
                <x-badge :value="$backup->status" class="badge-{{ $statusClass }}" />
            @endscope

            @scope('cell_file_size', $backup)
                {{ $backup->asBackupState()->formattedSize() }}
            @endscope

            @scope('cell_actions', $backup)
                <div class="flex gap-1">
                    @if ($backup->asBackupState()->isDeletable())
                        <x-button
                            icon="o-trash"
                            class="btn-ghost btn-sm text-error"
                            wire:click="confirmDelete('{{ $backup->id }}')"
                            wire:loading.attr="disabled"
                        />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>

    <x-modal wire:model="showConfirmDelete" title="{{ __('backups.confirm_delete_title') }}" class="backdrop-blur">
        <p>{{ __('backups.confirm_delete_message') }}</p>

        <x-slot:actions>
            <x-button label="{{ __('common.cancel') }}" wire:click="cancelDelete" />
            <x-button label="{{ __('common.delete') }}" wire:click="delete" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>

@include('sysadmin.backups.components.backup-guide')
