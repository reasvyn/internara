<div class="p-8">
    <x-mary-header :title="__('department.title')" :subtitle="__('department.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('department.add')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <x-mary-stat :title="__('department.stats.total')" :value="$stats['total']" icon="o-building-office" icon-class="text-primary" />
        <x-mary-stat :title="__('department.stats.with_internships')" :value="$stats['with_internships']" icon="o-briefcase" icon-class="text-secondary" />
    </div>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" :placeholder="__('department.search_placeholder')" icon="o-magnifying-glass" clearable />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => __('department.name')],
                ['key' => 'description', 'label' => __('department.description')],
                ['key' => 'created_at', 'label' => __('department.created_at'), 'sortable' => false],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$departments" with-pagination>
            @scope('cell_description', $department)
                <span class="text-sm text-base-content/70">
                    {{ Str::limit($department->description ?? '-', 50) }}
                </span>
            @endscope

            @scope('cell_created_at', $department)
                <span class="text-sm">
                    {{ $department->created_at->format('M d, Y') }}
                </span>
            @endscope

            @scope('actions', $department)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $department->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:confirm="{{ __('department.delete_confirm') }}"
                        wire:click="delete('{{ $department->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$departmentId ? __('department.edit') : __('department.new')" separator>
        <div class="space-y-6">
            <x-mary-input :label="__('department.name')" wire:model="name" :placeholder="__('department.name_placeholder')" />
            <x-mary-textarea :label="__('department.description')" wire:model="description" rows="3" />
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('department.cancel')" @click="$wire.showModal = false" />
            <x-mary-button :label="__('department.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
