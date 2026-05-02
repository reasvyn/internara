<div class="p-8">
    <x-layouts.manager 
        :title="__('department.title')" 
        :subtitle="__('department.subtitle')" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Custom Stats Header --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <x-mary-stat :title="__('department.stats.total')" :value="$stats['total']" icon="o-building-office" icon-class="text-primary" class="rounded-[2rem]" />
            <x-mary-stat :title="__('department.stats.with_internships')" :value="$stats['with_internships']" icon="o-briefcase" icon-class="text-secondary" class="rounded-[2rem]" />
        </div>

        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button :label="__('department.add')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                label="Delete Selected" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                wire:confirm="Are you sure you want to delete the selected departments? Only departments without students will be deleted."
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Table Cell Overrides --}}
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
            <div class="flex justify-end gap-1">
                <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $department->id }}')" />
                <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                    wire:confirm="{{ __('department.delete_confirm') }}"
                    wire:click="delete('{{ $department->id }}')" />
            </div>
        @endscope
    </x-layouts.manager>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$formData['id'] ? __('department.edit') : __('department.new')" separator>
        <div class="space-y-6">
            <x-mary-input :label="__('department.name')" wire:model="formData.name" :placeholder="__('department.name_placeholder')" class="rounded-xl border-base-300" />
            <x-mary-textarea :label="__('department.description')" wire:model="formData.description" rows="3" class="rounded-xl border-base-300" />
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('department.cancel')" @click="$wire.showModal = false" class="rounded-xl" />
            <x-mary-button :label="__('department.save')" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
