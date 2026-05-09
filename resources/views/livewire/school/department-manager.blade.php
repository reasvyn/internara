<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header :title="__('department.title')" :subtitle="__('department.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('department.add')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Header --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <x-mary-stat :title="__('department.stats.total')" :value="$stats['total']" icon="o-building-office" icon-class="text-primary" class="rounded-[2rem]" />
        <x-mary-stat :title="__('department.stats.with_internships')" :value="$stats['with_internships']" icon="o-briefcase" icon-class="text-secondary" class="rounded-[2rem]" />
    </div>

    {{-- Controls Section --}}
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div class="w-full lg:max-w-md">
            <x-mary-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search records...') }}" 
                icon="o-magnifying-glass" 
                clearable 
                class="rounded-2xl border-base-300 focus:border-primary transition-all duration-300 shadow-sm"
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    @if($this->selected_count > 0)
        <div class="mb-6 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2 duration-500 shadow-xl shadow-primary/5">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/20">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">{{ __('Records Selected') }}</h4>
                    <p class="text-[10px] uppercase font-black tracking-widest opacity-40">{{ __('Apply bulk operations') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <x-mary-button 
                        label="Delete Selected" 
                        icon="o-trash" 
                        class="btn-sm btn-error text-white font-bold rounded-lg" 
                        wire:confirm="Are you sure you want to delete the selected departments? Only departments without students will be deleted."
                        wire:click="deleteSelected" 
                    />
                </div>
                <div class="divider divider-horizontal mx-1"></div>
                <x-mary-button 
                    label="{{ __('Cancel') }}" 
                    wire:click="clearSelection" 
                    class="btn-sm btn-ghost rounded-xl font-black uppercase tracking-widest text-[10px]" 
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-mary-card shadow class="card-enterprise">
        <div class="table-enterprise">
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
            </x-mary-table>
        </div>
    </x-mary-card>

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
