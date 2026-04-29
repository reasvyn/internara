<div class="p-8">
    <x-mary-header title="Daily Journals" subtitle="Record your daily internship activities" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Write Journal" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 gap-6">
        <x-mary-card shadow class="bg-base-100 border border-base-200">
            @php
                $headers = [
                    ['key' => 'date', 'label' => 'Date'],
                    ['key' => 'content', 'label' => 'Activity Content'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'actions', 'label' => '']
                ];
            @endphp

            <x-mary-table :headers="$headers" :rows="$journals" with-pagination>
                @scope('cell_date', $journal)
                    <div class="font-medium">{{ $journal->date->format('d M Y') }}</div>
                    <div class="text-xs text-base-content/50">{{ $journal->date->format('l') }}</div>
                @endscope

                @scope('cell_content', $journal)
                    <div class="max-w-md truncate text-sm">
                        {{ $journal->content }}
                    </div>
                @endscope

                @scope('cell_status', $journal)
                    @if($journal->is_verified)
                        <x-mary-badge value="Verified" class="badge-success" />
                    @else
                        <x-mary-badge value="Submitted" class="badge-neutral" />
                    @endif
                @endscope

                @scope('actions', $journal)
                    @if(!$journal->is_verified)
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $journal->id }}')" />
                    @endif
                @endscope
            </x-mary-table>
        </x-mary-card>
    </div>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="Log Daily Journal" separator>
        <div class="space-y-6">
            <x-mary-datepicker label="Date" wire:model="date" icon="o-calendar" />
            <x-mary-textarea 
                label="What did you do today?" 
                wire:model="content" 
                placeholder="Describe your activities in detail..." 
                rows="5" />
            <x-mary-textarea 
                label="Learning Outcomes" 
                wire:model="learning_outcomes" 
                placeholder="What did you learn today?" 
                rows="3" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
            <x-mary-button label="Save Journal" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
