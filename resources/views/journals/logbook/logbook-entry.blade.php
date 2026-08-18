<div class="p-8">
    <x-mary-header
        :title="__('logbook.daily_journals')"
        :subtitle="__('logbook.daily_journals_subtitle')"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                :label="__('logbook.write_journal')"
                icon="o-pencil-square"
                class="btn-primary shadow-primary/20 rounded-2xl px-6 font-black tracking-widest uppercase shadow-lg"
                wire:click="create"
            />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 gap-6">
        <x-mary-card shadow class="card-enterprise">
            @php
                $headers = [
                    ['key' => 'date', 'label' => __('logbook.date')],
                    ['key' => 'content', 'label' => __('logbook.activity_content')],
                    ['key' => 'status', 'label' => __('logbook.status')],
                    ['key' => 'actions', 'label' => ''],
                ];
            @endphp

            <div class="table-enterprise">
                <x-mary-table :headers="$headers" :rows="$journals" with-pagination>
                    @scope('cell_date', $journal)
                        <div class="flex flex-col">
                            <span class="text-base-content text-sm font-black tracking-tight">{{ $journal->date->format('d M Y') }}</span>
                            <span class="text-base-content/30 mt-0.5 text-[10px] leading-none font-black tracking-widest uppercase">{{ $journal->date->format('l') }}</span>
                        </div>
                    @endscope

                    @scope('cell_content', $journal)
                        <div class="text-base-content/70 max-w-md truncate text-sm font-medium">
                            {{ $journal->content }}
                        </div>
                    @endscope

                    @scope('cell_status', $journal)
                        @if ($journal->is_verified)
                            <x-mary-badge
                                :value="__('logbook.verified')"
                                class="badge-success text-[10px] font-black uppercase"
                            />
                        @else
                            <x-mary-badge
                                :value="__('logbook.submitted')"
                                class="badge-neutral text-[10px] font-black uppercase"
                            />
                        @endif
                    @endscope

                    @scope('actions', $journal)
                        <div class="flex justify-end gap-2">
                            @if (! $journal->is_verified)
                                <x-mary-button
                                    icon="o-pencil-square"
                                    class="btn-ghost btn-sm text-primary transition-transform hover:scale-110"
                                    wire:click="edit('{{ $journal->id }}')"
                                />
                            @else
                                <x-mary-icon name="o-check-badge" class="text-success/40 size-5" />
                            @endif
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </x-mary-card>
    </div>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="__('logbook.log_daily')" separator class="backdrop-blur-sm">
        <div class="space-y-6 py-4">
            <x-mary-datepicker
                :label="__('logbook.activity_date')"
                wire:model="date"
                icon="o-calendar"
                class="rounded-2xl"
            />

            <x-mary-textarea
                :label="__('logbook.activity_content')"
                wire:model="content"
                :placeholder="__('logbook.placeholder_content')"
                rows="6"
                class="border-base-200 focus:border-primary rounded-2xl"
            />

            <x-mary-textarea
                :label="__('logbook.learning_outcomes')"
                wire:model="learning_outcomes"
                :placeholder="__('logbook.placeholder_outcomes')"
                rows="3"
                class="border-base-200 focus:border-primary rounded-2xl"
            />

            {{-- Photo Upload --}}
            <div class="space-y-3">
                <p class="text-base-content/70 text-sm font-semibold">{{ __('logbook.activity_photos') }}</p>
                <p class="text-base-content/50 text-xs">{{ __('logbook.activity_photos_hint') }}</p>

                <div class="flex gap-3">
                    {{-- Camera Capture --}}
                    <label class="border-base-300 hover:border-primary flex flex-1 cursor-pointer items-center gap-2 rounded-2xl border-2 border-dashed px-4 py-3 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="text-primary size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                        </svg>
                        <span class="text-sm font-medium">{{ __('logbook.take_photo') }}</span>
                        <input
                            type="file"
                            accept="image/*"
                            capture="environment"
                            wire:model="photos"
                            multiple
                            class="hidden"
                        />
                    </label>

                    {{-- Manual Upload --}}
                    <label class="border-base-300 hover:border-primary flex flex-1 cursor-pointer items-center gap-2 rounded-2xl border-2 border-dashed px-4 py-3 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="text-primary size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span class="text-sm font-medium">{{ __('logbook.upload_photos') }}</span>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                            wire:model="photos"
                            multiple
                            class="hidden"
                        />
                    </label>
                </div>

                {{-- Photo Previews --}}
                @if ($photos)
                    <div class="mt-3 grid grid-cols-3 gap-3">
                        @foreach ($photos as $index => $photo)
                            <div class="group border-base-200 relative overflow-hidden rounded-xl border">
                                <img src="{{ $photo->temporaryUrl() }}" class="h-32 w-full object-cover" />
                                <button
                                    type="button"
                                    wire:click="removePhoto({{ $index }})"
                                    class="bg-error text-error-content absolute top-1 right-1 flex size-6 items-center justify-center rounded-full opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p class="text-base-content/40 text-[10px]">{{ __('logbook.supported_formats') }}</p>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button
                :label="__('logbook.discard')"
                @click="$wire.showModal = false"
                class="btn-ghost text-[10px] font-bold tracking-widest uppercase"
            />
            <x-mary-button
                :label="__('logbook.save_activity')"
                class="btn-primary shadow-primary/20 rounded-2xl px-8 font-black tracking-widest uppercase shadow-lg"
                wire:click="save"
                spinner="save"
            />
        </x-slot:actions>
    </x-mary-modal>
</div>
