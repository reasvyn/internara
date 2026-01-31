<div>
    <x-ui::header title="{{ __('Manajemen Pembimbingan') }}" subtitle="{{ $registration->student->name }} - {{ $registration->placement->name }}">
        <x-slot:actions>
            @if(auth()->user()->hasRole('teacher'))
                <x-ui::button label="{{ __('Catat Kunjungan') }}" icon="tabler.map-pin" class="btn-primary" @click="$wire.visitModal = true" />
            @endif
            <x-ui::button label="{{ __('Berikan Feedback') }}" icon="tabler.message-plus" class="btn-secondary" @click="$wire.logModal = true" />
        </x-slot:actions>
    </x-ui::header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-8">
        <x-ui::card class="bg-base-200">
            <div class="flex items-center gap-4">
                <x-ui::icon name="tabler.map-pin" class="w-8 h-8 text-primary" />
                <div>
                    <div class="text-sm opacity-70">{{ __('Total Kunjungan') }}</div>
                    <div class="text-2xl font-bold">{{ $stats['visits_count'] }}</div>
                </div>
            </div>
        </x-ui::card>
        <x-ui::card class="bg-base-200">
            <div class="flex items-center gap-4">
                <x-ui::icon name="tabler.messages" class="w-8 h-8 text-secondary" />
                <div>
                    <div class="text-sm opacity-70">{{ __('Total Log/Feedback') }}</div>
                    <div class="text-2xl font-bold">{{ $stats['logs_count'] }}</div>
                </div>
            </div>
        </x-ui::card>
        <x-ui::card class="bg-base-200">
            <div class="flex items-center gap-4">
                <x-ui::icon name="tabler.calendar-event" class="w-8 h-8 text-accent" />
                <div>
                    <div class="text-sm opacity-70">{{ __('Kunjungan Terakhir') }}</div>
                    <div class="text-lg font-bold">{{ $stats['last_visit'] ? $stats['last_visit']->visit_date->format('d M Y') : '-' }}</div>
                </div>
            </div>
        </x-ui::card>
    </div>

    <x-ui::card title="{{ __('Timeline Pembimbingan') }}" subtitle="{{ __('Gabungan log bimbingan dan kunjungan lapangan secara kronologis.') }}" shadow>
        <div class="space-y-6">
            @forelse($timeline as $item)
                <div class="relative pl-8 border-l-2 {{ $item['type'] === 'visit' ? 'border-primary' : 'border-secondary' }}">
                    <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full {{ $item['type'] === 'visit' ? 'bg-primary' : 'bg-secondary' }}"></div>
                    
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <span class="font-bold text-lg">{{ $item['title'] }}</span>
                            <x-ui::badge :label="strtoupper($item['type'])" class="badge-sm badge-outline ml-2" />
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium">{{ \Illuminate\Support\Carbon::parse($item['date'])->format('d M Y') }}</div>
                            <div class="text-[10px] opacity-50">{{ \Illuminate\Support\Carbon::parse($item['date'])->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div class="bg-base-200 p-4 rounded-lg shadow-sm">
                        <p class="text-sm whitespace-pre-line">{{ $item['content'] }}</p>
                        
                        @if($item['type'] === 'visit' && !empty($item['metadata']))
                            <div class="mt-3 pt-3 border-t border-base-300">
                                <div class="text-xs font-bold opacity-70 mb-1">{{ __('Temuan Lapangan:') }}</div>
                                <ul class="list-disc list-inside text-xs space-y-1">
                                    @foreach($item['metadata'] as $finding)
                                        <li>{{ $finding }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mt-4 flex items-center gap-2 text-xs opacity-70">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-6">
                                    <span>{{ substr($item['causer']->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div>
                                <span class="font-bold">{{ $item['causer']->name }}</span>
                                <span class="mx-1">•</span>
                                <span>{{ $item['causer']->roles->first()?->name ?? 'User' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 opacity-50">
                    <x-ui::icon name="tabler.timeline" class="w-16 h-16 mx-auto mb-4" />
                    <p>{{ __('Belum ada aktivitas pembimbingan yang tercatat.') }}</p>
                </div>
            @endforelse
        </div>
    </x-ui::card>

    {{-- Visit Modal --}}
    <x-ui::modal wire:model="visitModal" title="{{ __('Catat Kunjungan Lapangan') }}" subtitle="{{ __('Dokumentasikan temuan saat kunjungan fisik.') }}">
        <x-ui::form wire:submit="recordVisit">
            <x-ui::input label="{{ __('Tanggal Kunjungan') }}" wire:model="visit_date" type="date" required />
            <x-ui::textarea label="{{ __('Catatan Temuan') }}" wire:model="visit_notes" rows="4" placeholder="{{ __('Jelaskan kondisi siswa dan progres di industri...') }}" />
            
            <x-slot:actions>
                <x-ui::button label="{{ __('Batal') }}" @click="$wire.visitModal = false" />
                <x-ui::button label="{{ __('Simpan Kunjungan') }}" type="submit" icon="tabler.check" class="btn-primary" spinner="recordVisit" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Log/Feedback Modal --}}
    <x-ui::modal wire:model="logModal" title="{{ __('Berikan Log/Feedback Bimbingan') }}" subtitle="{{ __('Catat sesi konsultasi atau berikan masukan bimbingan.') }}">
        <x-ui::form wire:submit="recordLog">
            <x-ui::select label="{{ __('Tipe Log') }}" wire:model="log_type" :options="[
                ['id' => 'feedback', 'name' => __('Feedback Rutin')],
                ['id' => 'session', 'name' => __('Sesi Bimbingan')],
                ['id' => 'advisory', 'name' => __('Konsultasi Masalah')],
            ]" />
            <x-ui::input label="{{ __('Subjek') }}" wire:model="log_subject" placeholder="{{ __('Contoh: Review Laporan Minggu 1') }}" required />
            <x-ui::textarea label="{{ __('Isi Feedback/Log') }}" wire:model="log_content" rows="4" placeholder="{{ __('Tuliskan detail bimbingan atau feedback...') }}" required />
            
            <x-slot:actions>
                <x-ui::button label="{{ __('Batal') }}" @click="$wire.logModal = false" />
                <x-ui::button label="{{ __('Simpan Log') }}" type="submit" icon="tabler.check" class="btn-secondary" spinner="recordLog" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
