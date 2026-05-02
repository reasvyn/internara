<div class="p-8">
    <x-mary-header title="Daily Journals" subtitle="Record your daily internship activities" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Write Journal" icon="o-pencil-square" class="btn-primary rounded-2xl font-black uppercase tracking-widest px-6 shadow-lg shadow-primary/20" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 gap-6">
        <x-mary-card shadow class="card-enterprise">
            @php
                $headers = [
                    ['key' => 'date', 'label' => 'Date'],
                    ['key' => 'content', 'label' => 'Activity Content'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'actions', 'label' => '']
                ];
            @endphp

            <div class="table-enterprise">
                <x-mary-table :headers="$headers" :rows="$journals" with-pagination>
                    @scope('cell_date', $journal)
                        <div class="flex flex-col">
                            <span class="font-black text-sm tracking-tight text-base-content">{{ $journal->date->format('d M Y') }}</span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-base-content/30 leading-none mt-0.5">{{ $journal->date->format('l') }}</span>
                        </div>
                    @endscope

                    @scope('cell_content', $journal)
                        <div class="max-w-md truncate text-sm font-medium text-base-content/70">
                            {{ $journal->content }}
                        </div>
                    @endscope

                    @scope('cell_status', $journal)
                        @if($journal->is_verified)
                            <x-mary-badge value="Verified" class="badge-success font-black text-[10px] uppercase" />
                        @else
                            <x-mary-badge value="Submitted" class="badge-neutral font-black text-[10px] uppercase" />
                        @endif
                    @endscope

                    @scope('actions', $journal)
                        <div class="flex justify-end gap-2">
                            @if(!$journal->is_verified)
                                <x-mary-button icon="o-pencil-square" class="btn-ghost btn-sm text-primary transition-transform hover:scale-110" wire:click="edit('{{ $journal->id }}')" />
                                <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error transition-transform hover:scale-110" wire:click="delete('{{ $journal->id }}')" />
                            @else
                                <x-mary-icon name="o-check-badge" class="size-5 text-success/40" />
                            @endif
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </x-mary-card>
    </div>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="Log Daily Activity" separator class="backdrop-blur-sm">
        <div class="space-y-6 py-4">
            <x-mary-datepicker label="Activity Date" wire:model="date" icon="o-calendar" class="rounded-2xl" />
            
            <x-mary-textarea 
                label="Activity Content" 
                wire:model="content" 
                placeholder="What did you do today? Describe your tasks and achievements..." 
                rows="6"
                class="rounded-2xl border-base-200 focus:border-primary" />
            
            <x-mary-textarea 
                label="Learning Outcomes" 
                wire:model="learning_outcomes" 
                placeholder="What technical or soft skills did you learn today?" 
                rows="3"
                class="rounded-2xl border-base-200 focus:border-primary" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Discard" @click="$wire.showModal = false" class="btn-ghost font-bold uppercase tracking-widest text-[10px]" />
            <x-mary-button label="Save Activity" class="btn-primary px-8 rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
