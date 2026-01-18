<div>
    <x-ui::header title="{{ __('Jurnal Harian') }}" subtitle="{{ __('Catat dan pantau aktivitas magang setiap hari.') }}">
        <x-slot:actions>
            @can('create', \Modules\Journal\Models\JournalEntry::class)
                <x-ui::button label="{{ __('Buat Jurnal Baru') }}" icon="tabler.plus" class="btn-primary" link="{{ route('journal.create') }}" />
            @endcan
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-slot:sidebar>
            @if(auth()->user()->hasRole('student'))
                <x-ui::card title="{{ __('Minggu Ini') }}" subtitle="{{ __('Status jurnal harian Anda.') }}" shadow separator>
                    <div class="space-y-4">
                        @foreach($this->week_glance as $day)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-center justify-center w-10 h-10 rounded-lg {{ $day['status'] === 'approved' ? 'bg-success/10 text-success' : ($day['status'] === 'empty' ? 'bg-base-200 text-base-content/50' : 'bg-warning/10 text-warning') }}">
                                        <span class="text-xs font-bold uppercase">{{ $day['label'] }}</span>
                                        <span class="text-sm font-bold">{{ $day['day'] }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium">{{ $day['date']->translatedFormat('l') }}</div>
                                        <div class="text-xs opacity-50">{{ $day['status'] === 'empty' ? __('Belum diisi') : ucfirst($day['status']) }}</div>
                                    </div>
                                </div>
                                
                                @if($day['status'] === 'empty')
                                    <x-ui::button icon="tabler.plus" class="btn-ghost btn-xs" link="{{ route('journal.create', ['date' => $day['date']->format('Y-m-d')]) }}" />
                                @else
                                    <x-ui::button icon="tabler.eye" class="btn-ghost btn-xs" link="{{ route('journal.index', ['date' => $day['date']->format('Y-m-d')]) }}" />
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
                    <x-ui::input placeholder="{{ __('Cari topik atau kompetensi...') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
                @if($date)
                    <div class="flex items-center gap-2">
                        <x-ui::badge :label="__('Tanggal: :date', ['date' => \Carbon\Carbon::parse($date)->translatedFormat('d M Y')])" class="badge-primary" />
                        <x-ui::button icon="tabler.x" class="btn-ghost btn-xs" wire:click="$set('date', '')" />
                    </div>
                @endif
            </div>

            <x-ui::table :headers="[
                ['key' => 'date', 'label' => __('Tanggal')],
                ['key' => 'student.name', 'label' => __('Siswa'), 'hidden' => auth()->user()->hasRole('student')],
                ['key' => 'work_topic', 'label' => __('Topik Pekerjaan')],
                ['key' => 'status', 'label' => __('Status')],
            ]" :rows="$this->journals">
                @scope('cell_date', $entry)
                    <div class="font-medium">{{ $entry->date->translatedFormat('d F Y') }}</div>
                    <div class="text-xs opacity-50">{{ $entry->date->translatedFormat('l') }}</div>
                @endscope

                @scope('cell_status', $entry)
                    <x-ui::badge :label="$entry->getStatusLabel()" :class="'badge-' . $entry->getStatusColor()" />
                @endscope

                @scope('actions', $entry)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.eye" class="btn-ghost btn-sm text-info" tooltip="{{ __('Lihat Detail') }}" wire:click="showDetail('{{ $entry->id }}')" />
                        
                        @can('update', $entry)
                            <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-warning" tooltip="{{ __('Edit') }}" link="{{ route('journal.edit', $entry->id) }}" />
                        @endcan

                        @can('validate', $entry)
                            @if($entry->latestStatus()?->name !== 'approved')
                                <x-ui::button icon="tabler.check" class="btn-ghost btn-sm text-success" tooltip="{{ __('Setujui') }}" wire:click="approve('{{ $entry->id }}')" wire:confirm="{{ __('Setujui jurnal ini?') }}" />
                                <x-ui::button icon="tabler.x" class="btn-ghost btn-sm text-error" tooltip="{{ __('Tolak') }}" wire:click="reject('{{ $entry->id }}')" wire:confirm="{{ __('Tolak jurnal ini?') }}" />
                            @endif
                        @endcan
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    <x-ui::modal wire:model="journalDetailModal" title="{{ __('Detail Jurnal') }}" subtitle="{{ $selectedEntry?->date->translatedFormat('d F Y') }}" separator>
        @if($selectedEntry)
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui::badge :label="$selectedEntry->getStatusLabel()" :class="'badge-' . $selectedEntry->getStatusColor()" />
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase text-base-content/50 mb-1">{{ __('Topik Pekerjaan') }}</h4>
                    <p class="font-semibold text-lg">{{ $selectedEntry->work_topic }}</p>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase text-base-content/50 mb-1">{{ __('Deskripsi Aktivitas') }}</h4>
                    <div class="whitespace-pre-line text-sm">{{ $selectedEntry->activity_description }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-xs font-bold uppercase text-base-content/50 mb-1">{{ __('Kompetensi Dasar') }}</h4>
                        <p class="text-sm">{{ $selectedEntry->basic_competence ?: '-' }}</p>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase text-base-content/50 mb-1">{{ __('Nilai Karakter') }}</h4>
                        <p class="text-sm">{{ $selectedEntry->character_values ?: '-' }}</p>
                    </div>
                </div>

                @if($selectedEntry->reflection)
                    <div>
                        <h4 class="text-xs font-bold uppercase text-base-content/50 mb-1">{{ __('Refleksi & Pembelajaran') }}</h4>
                        <p class="text-sm italic italic">"{{ $selectedEntry->reflection }}"</p>
                    </div>
                @endif

                @if($selectedEntry->hasMedia('attachments'))
                    <div>
                        <h4 class="text-xs font-bold uppercase text-base-content/50 mb-2">{{ __('Lampiran') }}</h4>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($selectedEntry->getMedia('attachments') as $media)
                                <a href="{{ $media->getTemporaryUrl(now()->addMinutes(5)) }}" target="_blank" class="flex items-center gap-2 p-2 rounded-lg border border-base-300 hover:bg-base-200 transition-colors">
                                    <x-ui::icon name="tabler.paperclip" class="w-4 h-4" />
                                    <span class="text-xs truncate">{{ $media->file_name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <x-slot:actions>
            @if($selectedEntry && auth()->user()->id !== $selectedEntry->student_id && $selectedEntry->latestStatus()?->name !== 'approved')
                <x-ui::button label="{{ __('Tolak') }}" class="btn-error btn-outline" wire:click="reject('{{ $selectedEntry->id }}')" wire:confirm="{{ __('Tolak jurnal ini?') }}" />
                <x-ui::button label="{{ __('Setujui') }}" class="btn-success" wire:click="approve('{{ $selectedEntry->id }}')" wire:confirm="{{ __('Setujui jurnal ini?') }}" />
            @endif
            <x-ui::button label="{{ __('Tutup') }}" x-on:click="$wire.journalDetailModal = false" />
        </x-slot:actions>
    </x-ui::modal>
</div>