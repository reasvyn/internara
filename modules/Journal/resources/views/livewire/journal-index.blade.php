<div>
    <x-ui::header :title="__('journal::ui.index.title')" :subtitle="__('journal::ui.index.subtitle')">
        <x-slot:actions>
            @can('create', \Modules\Journal\Models\JournalEntry::class)
                <x-ui::button :label="__('journal::ui.index.create_new')" icon="tabler.plus" priority="primary" link="{{ route('journal.create') }}" />
            @endcan
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-slot:sidebar>
            @if(auth()->user()->hasRole('student'))
                <x-ui::card :title="__('journal::ui.index.this_week')" :subtitle="__('journal::ui.index.week_subtitle')" shadow separator>
                    <div class="space-y-4">
                        @foreach($this->week_glance as $day)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-center justify-center w-10 h-10 rounded-lg {{ $day['status'] === 'approved' ? 'bg-success/10 text-success' : ($day['status'] === 'empty' ? 'bg-base-200 text-base-content/50' : 'bg-warning/10 text-warning') }}">
                                        <span class="text-[10px] font-bold uppercase tracking-tighter">{{ $day['label'] }}</span>
                                        <span class="text-sm font-bold">{{ $day['day'] }}</span>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium">{{ $day['date']->translatedFormat('l') }}</div>
                                        <div class="text-[10px] opacity-50 uppercase tracking-wider">{{ $day['status'] === 'empty' ? __('journal::ui.index.not_filled') : ucfirst($day['status']) }}</div>
                                    </div>
                                </div>
                                
                                @if($day['status'] === 'empty')
                                    <x-ui::button icon="tabler.plus" priority="tertiary" class="btn-xs" link="{{ route('journal.create', ['date' => $day['date']->format('Y-m-d')]) }}" />
                                @else
                                    <x-ui::button icon="tabler.eye" priority="tertiary" class="btn-xs" link="{{ route('journal.index', ['date' => $day['date']->format('Y-m-d')]) }}" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-ui::card>
            @endif
        </x-slot:sidebar>

        <x-ui::card>
            <div class="mb-4 flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <x-ui::input :placeholder="__('journal::ui.index.search_placeholder')" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
                @if($date)
                    <div class="flex items-center gap-2">
                        <x-ui::badge :value="__('journal::ui.index.filter_date', ['date' => \Carbon\Carbon::parse($date)->translatedFormat('d M Y')])" priority="primary" />
                        <x-ui::button icon="tabler.x" priority="tertiary" class="btn-xs" wire:click="$set('date', '')" />
                    </div>
                @endif
            </div>

            <x-ui::table :headers="[
                ['key' => 'date', 'label' => __('journal::ui.index.table.date')],
                ['key' => 'student.name', 'label' => __('journal::ui.index.table.student'), 'hidden' => auth()->user()->hasRole('student')],
                ['key' => 'work_topic', 'label' => __('journal::ui.index.table.work_topic')],
                ['key' => 'status', 'label' => __('journal::ui.index.table.status')],
            ]" :rows="$this->journals" with-pagination>
                @scope('cell_date', $entry)
                    <div class="font-medium text-sm">{{ $entry->date->translatedFormat('d F Y') }}</div>
                    <div class="text-[10px] uppercase tracking-wider opacity-50">{{ $entry->date->translatedFormat('l') }}</div>
                @endscope

                @scope('cell_status', $entry)
                    <x-ui::badge 
                        :value="$entry->getStatusLabel()" 
                        :priority="$entry->getStatusColor() === 'success' ? 'primary' : 'secondary'" 
                        class="badge-sm" 
                    />
                @endscope

                @scope('actions', $entry)
                    <div class="flex gap-1">
                        <x-ui::button icon="tabler.eye" priority="tertiary" class="text-info btn-sm" tooltip="{{ __('journal::ui.index.actions.view_detail') }}" wire:click="showDetail('{{ $entry->id }}')" />
                        
                        @can('update', $entry)
                            <x-ui::button icon="tabler.edit" priority="tertiary" class="text-warning btn-sm" tooltip="{{ __('ui::common.edit') }}" link="{{ route('journal.edit', $entry->id) }}" />
                        @endcan

                        @can('validate', $entry)
                            @if($entry->latestStatus()?->name !== 'approved')
                                <x-ui::button icon="tabler.check" priority="tertiary" class="text-success btn-sm" tooltip="{{ __('journal::ui.index.actions.approve') }}" wire:click="approve('{{ $entry->id }}')" wire:confirm="{{ __('journal::ui.index.actions.approve_confirm') }}" />
                                <x-ui::button icon="tabler.x" priority="tertiary" class="text-error btn-sm" tooltip="{{ __('journal::ui.index.actions.reject') }}" wire:click="reject('{{ $entry->id }}')" wire:confirm="{{ __('journal::ui.index.actions.reject_confirm') }}" />
                            @endif
                        @endcan
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    <x-ui::modal wire:model="journalDetailModal" :title="__('journal::ui.index.modal.title')" :subtitle="$selectedEntry?->date->translatedFormat('d F Y')" separator>
        @if($selectedEntry)
            <div class="space-y-6">
                <div class="flex">
                    <x-ui::badge 
                        :value="$selectedEntry->getStatusLabel()" 
                        :priority="$selectedEntry->getStatusColor() === 'success' ? 'primary' : 'secondary'" 
                    />
                </div>

                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-base-content/50 mb-1">{{ __('journal::ui.index.modal.work_topic') }}</h4>
                    <p class="font-bold text-lg leading-tight">{{ $selectedEntry->work_topic }}</p>
                </div>

                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-base-content/50 mb-1">{{ __('journal::ui.index.modal.description') }}</h4>
                    <div class="whitespace-pre-line text-sm opacity-90">{{ $selectedEntry->activity_description }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-base-content/50 mb-1">{{ __('journal::ui.index.modal.competence') }}</h4>
                        <p class="text-sm">{{ $selectedEntry->basic_competence ?: '-' }}</p>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-base-content/50 mb-1">{{ __('journal::ui.index.modal.character') }}</h4>
                        <p class="text-sm">{{ $selectedEntry->character_values ?: '-' }}</p>
                    </div>
                </div>

                @if($selectedEntry->reflection)
                    <div>
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-base-content/50 mb-1">{{ __('journal::ui.index.modal.reflection') }}</h4>
                        <p class="text-sm italic opacity-80">"{{ $selectedEntry->reflection }}"</p>
                    </div>
                @endif

                @if($selectedEntry->hasMedia('attachments'))
                    <div>
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-base-content/50 mb-2">{{ __('journal::ui.index.modal.attachments') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($selectedEntry->getMedia('attachments') as $media)
                                <a href="{{ $media->getTemporaryUrl(now()->addMinutes(5)) }}" target="_blank" class="flex items-center gap-2 p-3 rounded-xl border border-base-300 hover:bg-base-200 transition-all group">
                                    <x-ui::icon name="tabler.paperclip" class="size-4 opacity-50 group-hover:opacity-100" />
                                    <span class="text-xs truncate font-medium">{{ $media->file_name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <x-slot:actions>
            @if($selectedEntry && auth()->user()->id !== $selectedEntry->student_id && $selectedEntry->latestStatus()?->name !== 'approved')
                <x-ui::button :label="__('journal::ui.index.actions.reject')" priority="secondary" class="btn-error" wire:click="reject('{{ $selectedEntry->id }}')" wire:confirm="{{ __('journal::ui.index.actions.reject_confirm') }}" />
                <x-ui::button :label="__('journal::ui.index.actions.approve')" priority="primary" class="btn-success" wire:click="approve('{{ $selectedEntry->id }}')" wire:confirm="{{ __('journal::ui.index.actions.approve_confirm') }}" />
            @endif
            <x-ui::button :label="__('ui::common.close')" x-on:click="$wire.journalDetailModal = false" />
        </x-slot:actions>
    </x-ui::modal>
</div>