@php
    use App\Modules\SysAdmin\Domain\Backup\Enums\BackupStatus;
    use App\Modules\SysAdmin\Domain\Backup\Enums\BackupType;
@endphp

<x-ui::components.record-manager :title="__('backup.title')" :subtitle="__('backup.subtitle')">
    <x-slot:headerActions>
        <x-ts-dropdown>
            <x-slot:action>
                <x-ts-button
                    text="{{ __('backup.create_button') }}"
                    icon="arrow-path"
                    color="primary"
                    sm
                    x-on:click="show = ! show"
                />
            </x-slot:action>
            <x-ts-dropdown.items
                text="{{ BackupType::DATABASE->label() }}"
                wire:click="createBackup('{{ BackupType::DATABASE->value }}')"
                icon="circle-stack"
            />
            <x-ts-dropdown.items
                text="{{ BackupType::STORAGE->label() }}"
                wire:click="createBackup('{{ BackupType::STORAGE->value }}')"
                icon="folder"
            />
            <x-ts-dropdown.items
                text="{{ BackupType::BOTH->label() }}"
                wire:click="createBackup('{{ BackupType::BOTH->value }}')"
                icon="archive-box"
            />
        </x-ts-dropdown>
    </x-slot:headerActions>

    <x-slot:stats>
        <x-ui::widgets.stat-card
            title="{{ __('backup.total') }}"
            value="{{ $this->stats['total'] }}"
            icon="archive-box"
        />
        <x-ui::widgets.stat-card
            title="{{ __('backup.completed') }}"
            value="{{ $this->stats['completed'] }}"
            icon="check-circle"
        />
        <x-ui::widgets.stat-card
            title="{{ __('backup.failed') }}"
            value="{{ $this->stats['failed'] }}"
            icon="exclamation-circle"
        />
        <x-ui::widgets.stat-card
            title="{{ __('backup.latest') }}"
            value="{{ $this->stats['latest']?->asBackupState()->formattedSize() ?? '--' }}"
            icon="clock"
        />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('backup.filter_type') }}</label>
        <x-ts-select.native wire:model.live="filterType" :options="ts_options(BackupType::cases(), __('common.all'))" />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('backup.filter_status') }}</label>
        <x-ts-select.native
            wire:model.live="filterStatus"
            :options="ts_options(BackupStatus::cases(), __('common.all'))"
        />
    </x-slot:filters>

    <x-ts-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_type', $backup)
            <x-ts-badge :text="$backup->type" xs />
        @endinteract

        @interact('column_status', $backup)
            @php
                $statusColor = match ($backup->status) {
                    'completed' => 'green',
                    'failed' => 'red',
                    'running' => 'yellow',
                    default => 'gray',
                };
            @endphp
            <x-ts-badge :text="$backup->status" :color="$statusColor" xs />
        @endinteract

        @interact('column_file_size', $backup)
            <span class="font-mono text-xs">{{ $backup->asBackupState()->formattedSize() }}</span>
        @endinteract

        @interact('column_actions', $backup)
            <x-ui::components.table-actions>
                @if ($backup->asBackupState()->isDeletable())
                    <x-ui::components.table-action
                        action="delete"
                        wire:click="confirmDelete('{{ $backup->id }}')"
                        wire:loading.attr="disabled"
                    />
                @endif
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="showConfirmDelete" title="{{ __('backup.confirm_delete_title') }}" class="backdrop-blur">
            <p>{{ __('backup.confirm_delete_message') }}</p>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        text="{{ __('common.actions.cancel') }}"
                        wire:click="cancelDelete"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button text="{{ __('common.actions.delete') }}" wire:click="deleteBackup" color="red" sm />
                </div>
            </x-slot:footer>
        </x-ts-modal>
    </x-slot:modal>

    <x-slot:auxiliary>
        @include('sysadmin.backups.components.backup-guide')
    </x-slot:auxiliary>
</x-ui::components.record-manager>
