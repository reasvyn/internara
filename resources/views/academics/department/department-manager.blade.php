<div>
<x-core::ui.record-manager
    :title="__('department.title')"
    :subtitle="__('department.subtitle')"

>
    <x-slot:headerActions>
        <x-mary-button :label="__('department.add')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" onclick="document.getElementById('import-csv').click()" />
        <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" wire:click="downloadTemplate" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-core::widgets.stat-card :title="__('department.stats.total')" :value="$stats['total']" icon="o-building-library" color="text-primary" class="lg:col-span-2" />
        <x-core::widgets.stat-card :title="__('department.stats.with_students')" :value="$stats['with_internships']" icon="o-users" color="text-secondary" class="lg:col-span-2" />
    </x-slot:stats>

    <x-core::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item :title="__('common.actions.export_selected')" icon="o-arrow-down-tray"
                    wire:click="exportSelected" />
                <hr class="border-base-content/10" />
                <x-mary-menu-item :title="__('common.actions.delete_selected')" icon="o-trash" class="text-error"
                    wire:click="askDeleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-core::ui.selection-bar>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @scope('cell_description', $department)
                <span class="text-sm text-base-content/60">
                    {{ Str::limit($department->description ?? '—', 50) }}
                </span>
            @endscope

            @scope('cell_created_at', $department)
                <time datetime="{{ $department->created_at->toIso8601String() }}" class="text-sm text-base-content/50">
                    {{ $department->created_at->format('M d, Y') }}
                </time>
            @endscope

            @scope('actions', $department)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $department->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:click="askDelete('{{ $department->id }}')"
                        :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    {{-- Confirm Dialog --}}
    <x-core::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        confirmClass="btn-error"
    />

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('department.edit') : __('department.new')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input
                        :label="__('department.name')"
                        wire:model="form.name"
                        :placeholder="__('department.name_placeholder')"
                        icon="o-building-library"
                    />
                    <x-mary-textarea
                        :label="__('department.description')"
                        wire:model="form.description"
                        rows="3"
                        icon="o-document-text"
                    />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('department.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>

@include('setup.components.department-guide')
</div>
