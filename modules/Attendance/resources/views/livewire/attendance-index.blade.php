<div>
    <x-ui::header title="{{ __('Log Presensi') }}" subtitle="{{ __('Riwayat kehadiran selama masa magang.') }}" />

    <x-ui::main>
        <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            @if(!auth()->user()->hasRole('student'))
                <x-ui::input icon="tabler.search" placeholder="{{ __('Cari siswa...') }}" wire:model.live.debounce="search" />
            @endif
            <x-ui::input type="date" label="{{ __('Dari Tanggal') }}" wire:model.live="date_from" />
            <x-ui::input type="date" label="{{ __('Sampai Tanggal') }}" wire:model.live="date_to" />
        </div>

        <x-ui::card>
            <x-ui::table :headers="[
                ['key' => 'date', 'label' => __('Tanggal')],
                ['key' => 'student.name', 'label' => __('Siswa'), 'hidden' => auth()->user()->hasRole('student')],
                ['key' => 'check_in_at', 'label' => __('Jam Masuk')],
                ['key' => 'check_out_at', 'label' => __('Jam Keluar')],
                ['key' => 'status', 'label' => __('Status')],
            ]" :rows="$this->logs">
                @scope('cell_date', $log)
                    <div class="font-medium">{{ $log->date->translatedFormat('d F Y') }}</div>
                    <div class="text-xs opacity-50">{{ $log->date->translatedFormat('l') }}</div>
                @endscope

                @scope('cell_check_in_at', $log)
                    {{ $log->check_in_at?->format('H:i') ?: '-' }}
                @endscope

                @scope('cell_check_out_at', $log)
                    {{ $log->check_out_at?->format('H:i') ?: '-' }}
                @endscope

                @scope('cell_status', $log)
                    <x-ui::badge :label="$log->getStatusLabel()" :class="'badge-' . $log->getStatusColor()" />
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>
</div>