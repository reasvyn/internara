<div class="p-8">
    <x-layouts.manager 
        :title="__('internship.title')" 
        :subtitle="__('internship.subtitle')" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Stats Header --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <x-mary-stat :value="$this->stats['total']" :title="__('internship.stats.total')" icon="o-calendar" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['active']" :title="__('internship.stats.active')" icon="o-play" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['total_placements']" :title="__('internship.stats.total_placements')" icon="o-briefcase" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['total_registrations']" :title="__('internship.stats.total_registrations')" icon="o-user-group" class="rounded-[2rem] bg-base-100 border border-base-200" />
        </div>

        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button :label="__('internship.create_batch')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                label="Delete Selected" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                wire:confirm="Delete selected internship batches? Only empty batches will be deleted."
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Mass Actions --}}
        <x-slot:massActions>
            <x-mary-button 
                label="Complete All Filtered" 
                icon="o-check-circle" 
                class="btn-sm btn-outline border-base-300 rounded-xl" 
                wire:confirm="Set all filtered internship batches to COMPLETED? Continue?"
                wire:click="closeAllFiltered"
            />
        </x-slot:massActions>

        {{-- Table Cell Overrides --}}
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
    </x-layouts.manager>

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
