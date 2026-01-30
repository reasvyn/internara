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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <x-ui::card title="{{ __('Riwayat Kunjungan') }}" shadow>
            @forelse($visits as $visit)
                <div class="mb-4 p-4 border-l-4 border-primary bg-base-100 rounded-r shadow-sm">
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-bold text-primary">{{ $visit->visit_date->format('d M Y') }}</span>
                        <span class="text-xs opacity-50">{{ $visit->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm">{{ $visit->notes }}</p>
                </div>
            @empty
                <div class="text-center py-8 opacity-50">{{ __('Belum ada catatan kunjungan.') }}</div>
            @endforelse
        </x-ui::card>

        <x-ui::card title="{{ __('Log & Feedback Bimbingan') }}" shadow>
            @forelse($logs as $log)
                <div class="mb-4 p-4 border-l-4 border-secondary bg-base-100 rounded-r shadow-sm">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-bold">{{ $log->subject }}</span>
                            <x-ui::badge :label="strtoupper($log->type)" class="badge-sm badge-outline" />
                        </div>
                        <span class="text-xs opacity-50">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm italic mb-2">"{{ $log->content }}"</p>
                    <div class="text-xs opacity-70 flex items-center gap-1">
                        <x-ui::icon name="tabler.user" class="w-3 h-3" />
                        {{ $log->causer->name }}
                    </div>
                </div>
            @empty
                <div class="text-center py-8 opacity-50">{{ __('Belum ada log bimbingan.') }}</div>
            @endforelse
        </x-ui::card>
    </div>

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
