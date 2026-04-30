<div class="p-8">
    <x-mary-header :title="__('internship.title')" :subtitle="__('internship.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('internship.create_batch')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-mary-stat :value="$this->stats['total']" :title="__('internship.stats.total')" icon="o-calendar" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['active']" :title="__('internship.stats.active')" icon="o-play" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['total_placements']" :title="__('internship.stats.total_placements')" icon="o-briefcase" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['total_registrations']" :title="__('internship.stats.total_registrations')" icon="o-user-group" class="bg-base-100 border border-base-200" />
    </div>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" :placeholder="__('internship.search_placeholder')" icon="o-magnifying-glass" clearable />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => __('internship.batch_name')],
                ['key' => 'start_date', 'label' => __('internship.start_date')],
                ['key' => 'end_date', 'label' => __('internship.end_date')],
                ['key' => 'status', 'label' => __('internship.status')],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$internships" with-pagination>
            @scope('cell_start_date', $internship)
                {{ $internship->start_date->format('d M Y') }}
            @endscope

            @scope('cell_end_date', $internship)
                {{ $internship->end_date->format('d M Y') }}
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
                <x-mary-badge :value="__('internship.statuses.' . $internship->status->value)" class="{{ $statusClass }}" />
            @endscope

            @scope('actions', $internship)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $internship->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:confirm="{{ __('internship.delete_confirm') }}"
                        wire:click="delete('{{ $internship->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$this->internshipId ? __('internship.edit_batch') : __('internship.new_batch')" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            <x-mary-input :label="__('internship.name')" wire:model="name" :placeholder="__('internship.name_placeholder')" icon="o-academic-cap" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-datepicker :label="__('internship.start_date')" wire:model="start_date" icon="o-calendar" />
                <x-mary-datepicker :label="__('internship.end_date')" wire:model="end_date" icon="o-calendar" />
            </div>

            <x-mary-select :label="__('internship.status')" wire:model="status" :options="$this->statusOptions" icon="o-flag" />

            <x-mary-textarea :label="__('internship.description')" wire:model="description" :placeholder="__('internship.description_placeholder')" rows="2" icon="o-document-text" />
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('internship.cancel')" @click="$wire.showModal = false" />
            <x-mary-button :label="__('internship.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
