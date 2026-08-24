@php
    use App\SysAdmin\Backups\Enums\BackupStatus;
    use App\SysAdmin\Backups\Enums\BackupType;
@endphp

<div>
    <x-core::ui.page-header :title="__('backups.title')" :description="__('backups.subtitle')">
        <x-slot:actions>
            <x-ts-dropdown>
                <x-slot:action>
                    <x-ts-button text="{{ __('backups.create_button') }}" icon="arrow-path" color="primary" />
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
        </x-slot:actions>
    </x-core::ui.page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <x-ts-stats
            title="{{ __('backups.total') }}"
            number="{{ $this->stats['total'] }}"
            icon="archive-box"
            class="bg-base-200"
        />
        <x-ts-stats
            title="{{ __('backups.completed') }}"
            number="{{ $this->stats['completed'] }}"
            icon="check-circle"
            class="text-success"
        />
        <x-ts-stats
            title="{{ __('backups.failed') }}"
            number="{{ $this->stats['failed'] }}"
            icon="exclamation-circle"
            class="text-error"
        />
        <x-ts-stats
            title="{{ __('backups.latest') }}"
            number="{{ $this->stats['latest']?->asBackupState()->formattedSize() ?? '--' }}"
            icon="clock"
            class="bg-base-200"
        />
    </div>

    <x-ts-card shadowless>
        <div class="mb-4 flex gap-4">
            <x-ts-select.native
                label="{{ __('backups.filter_type') }}"
                wire:model.live="filterType"
                :options="[
                    ['value' => '', 'label' => __('common.all')],
                    ...collect(BackupType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->toArray(),
                ]"
                class="w-48"
            />
            <x-ts-select.native
                label="{{ __('backups.filter_status') }}"
                wire:model.live="filterStatus"
                :options="[
                    ['value' => '', 'label' => __('common.all')],
                    ...collect(BackupStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->toArray(),
                ]"
                class="w-48"
            />
        </div>

        <x-ts-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination>
            @interact('column_type', $backup)
                <x-ts-badge :text="$backup->type" />
            @endinteract

            @interact('column_status', $backup)
                @php
                    $statusClass = match ($backup->status) {
                        'completed' => 'success',
                        'failed' => 'error',
                        'running' => 'warning',
                        default => 'info',
                    };
                @endphp
                <x-ts-badge :text="$backup->status" class="badge-{{ $statusClass }}" />
            @endinteract

            @interact('column_file_size', $backup)
                {{ $backup->asBackupState()->formattedSize() }}
            @endinteract

            @interact('column_action', $backup)
                <div class="flex gap-1">
                    @if ($backup->asBackupState()->isDeletable())
                        <x-ts-button
                            aria-label="{{ __('common.actions.delete') }}"
                            icon="trash"
                            class="text-error"
                            color="white"
                            sm
                            wire:click="confirmDelete('{{ $backup->id }}')"
                            wire:loading.attr="disabled"
                        />
                    @endif
                </div>
            @endinteract
        </x-ts-table>

        <x-ts-modal wire="showConfirmDelete" title="{{ __('backups.confirm_delete_title') }}" class="backdrop-blur">
            <p>{{ __('backups.confirm_delete_message') }}</p>

            <x-slot:footer>
                <x-ts-button text="{{ __('common.cancel') }}" wire:click="cancelDelete" />
                <x-ts-button text="{{ __('common.delete') }}" wire:click="delete" color="red" />
            </x-slot:footer>
        </x-ts-modal>

        @include('sysadmin.backups.components.backup-guide')
</div>
