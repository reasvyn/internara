<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header :title="__('internship.title')" :subtitle="__('internship.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('internship.create_batch')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Header --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <x-mary-stat :value="$this->stats['total']" :title="__('internship.stats.total')" icon="o-calendar" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['active']" :title="__('internship.stats.active')" icon="o-play" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['total_placements']" :title="__('internship.stats.total_placements')" icon="o-briefcase" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['total_registrations']" :title="__('internship.stats.total_registrations')" icon="o-user-group" class="rounded-[2rem] bg-base-100 border border-base-200" />
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
                        wire:confirm="Delete selected internship batches? Only empty batches will be deleted."
                        wire:click="deleteSelected" 
                    />
                    <x-mary-button 
                        label="Complete All Filtered" 
                        icon="o-check-circle" 
                        class="btn-sm btn-outline border-base-300 rounded-xl" 
                        wire:confirm="Set all filtered internship batches to COMPLETED? Continue?"
                        wire:click="closeAllFiltered"
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
                @scope('cell_start_date', $internship)
                    <span class="text-sm font-medium">{{ $internship->start_date->format('d M Y') }}</span>
                @endscope

                @scope('cell_end_date', $internship)
                    <span class="text-sm font-medium">{{ $internship->end_date->format('d M Y') }}</span>
                @endscope

                @scope('cell_status', $internship)
                    @php
                        $statusClass = match($internship->status) {
                            \App\Enums\InternshipStatus::ACTIVE => 'badge-success',
                            \App\Enums\InternshipStatus::PUBLISHED => 'badge-info',
                            \App\Enums\InternshipStatus::COMPLETED => 'badge-neutral',
                            \App\Enums\InternshipStatus::CANCELLED => 'badge-error',
                            default => 'badge-ghost',
                        };
                    @endphp
                    <x-mary-badge :value="__('internship.statuses.' . $internship->status->value)" class="{{ $statusClass }} font-bold text-[10px] uppercase tracking-tighter" />
                @endscope

                @scope('actions', $internship)
                    <div class="flex justify-end gap-1">
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $internship->id }}')" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                            wire:confirm="{{ __('internship.delete_confirm') }}"
                            wire:click="delete('{{ $internship->id }}')" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$formData['id'] ? __('internship.edit_batch') : __('internship.new_batch')" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            <x-mary-input :label="__('internship.name')" wire:model="formData.name" :placeholder="__('internship.name_placeholder')" icon="o-academic-cap" class="rounded-xl border-base-300" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-datepicker :label="__('internship.start_date')" wire:model="formData.start_date" icon="o-calendar" class="rounded-xl border-base-300" />
                <x-mary-datepicker :label="__('internship.end_date')" wire:model="formData.end_date" icon="o-calendar" class="rounded-xl border-base-300" />
            </div>

            <x-mary-select :label="__('internship.status')" wire:model="formData.status" :options="$this->statusOptions" icon="o-flag" class="rounded-xl border-base-300" />

            <x-mary-textarea :label="__('internship.description')" wire:model="formData.description" :placeholder="__('internship.description_placeholder')" rows="2" icon="o-document-text" class="rounded-xl border-base-300" />
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('internship.cancel')" @click="$wire.showModal = false" class="rounded-xl" />
            <x-mary-button :label="__('internship.save')" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
