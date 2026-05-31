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

            {{-- Photo Upload --}}
            <div class="space-y-3">
                <p class="text-sm font-semibold text-base-content/70">Activity Photos</p>
                <p class="text-xs text-base-content/50">Capture photos from your camera or upload from your device as evidence of your daily activities.</p>

                <div class="flex gap-3">
                    {{-- Camera Capture --}}
                    <label class="flex items-center gap-2 px-4 py-3 rounded-2xl border-2 border-dashed border-base-300 hover:border-primary cursor-pointer transition-colors flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                        </svg>
                        <span class="text-sm font-medium">Take Photo</span>
                        <input type="file" accept="image/*" capture="environment" wire:model="photos" multiple class="hidden" />
                    </label>

                    {{-- Manual Upload --}}
                    <label class="flex items-center gap-2 px-4 py-3 rounded-2xl border-2 border-dashed border-base-300 hover:border-primary cursor-pointer transition-colors flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span class="text-sm font-medium">Upload Photos</span>
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" wire:model="photos" multiple class="hidden" />
                    </label>
                </div>

                {{-- Photo Previews --}}
                @if($photos)
                    <div class="grid grid-cols-3 gap-3 mt-3">
                        @foreach($photos as $index => $photo)
                            <div class="relative group rounded-xl overflow-hidden border border-base-200">
                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-32 object-cover" />
                                <button type="button" wire:click="removePhoto({{ $index }})" class="absolute top-1 right-1 size-6 rounded-full bg-error text-error-content flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p class="text-[10px] text-base-content/40">Supported formats: JPEG, PNG, WebP, HEIC. Max 10 MB per photo.</p>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Discard" @click="$wire.showModal = false" class="btn-ghost font-bold uppercase tracking-widest text-[10px]" />
            <x-mary-button label="Save Activity" class="btn-primary px-8 rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-primary/20" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
