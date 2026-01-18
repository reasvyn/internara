<div>
    <x-ui::card title="{{ __('Presensi Harian') }}" subtitle="{{ now()->translatedFormat('l, d F Y') }}" shadow separator>
        <div class="flex flex-col items-center justify-center space-y-6 py-4">
            <div class="text-4xl font-bold tracking-tighter" wire:poll.1s>
                {{ now()->format('H:i:s') }}
            </div>

            @if(!$todayLog)
                <div class="text-center space-y-4">
                    <p class="text-sm opacity-70">{{ __('Anda belum melakukan presensi masuk hari ini.') }}</p>
                    <x-ui::button 
                        label="{{ __('Presensi Masuk') }}" 
                        icon="tabler.fingerprint" 
                        class="btn-primary btn-lg" 
                        wire:click="clockIn" 
                        spinner="clockIn" 
                    />
                </div>
            @elseif(!$todayLog->check_out_at)
                <div class="text-center space-y-4 w-full">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="p-4 rounded-lg bg-base-200">
                            <div class="text-xs font-bold uppercase opacity-50">{{ __('Jam Masuk') }}</div>
                            <div class="text-lg font-bold">{{ $todayLog->check_in_at->format('H:i') }}</div>
                        </div>
                        <div class="p-4 rounded-lg bg-base-200">
                            <div class="text-xs font-bold uppercase opacity-50">{{ __('Status') }}</div>
                            <x-ui::badge :label="$todayLog->getStatusLabel()" :class="'badge-' . $todayLog->getStatusColor()" />
                        </div>
                    </div>
                    
                    <x-ui::button 
                        label="{{ __('Presensi Keluar') }}" 
                        icon="tabler.logout" 
                        class="btn-warning btn-lg" 
                        wire:click="clockOut" 
                        spinner="clockOut" 
                    />
                </div>
            @else
                <div class="text-center space-y-4 w-full">
                    <x-ui::alert icon="tabler.circle-check" class="alert-success">
                        {{ __('Anda telah menyelesaikan presensi hari ini.') }}
                    </x-ui::alert>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg bg-base-200">
                            <div class="text-xs font-bold uppercase opacity-50">{{ __('Jam Masuk') }}</div>
                            <div class="text-lg font-bold">{{ $todayLog->check_in_at->format('H:i') }}</div>
                        </div>
                        <div class="p-4 rounded-lg bg-base-200">
                            <div class="text-xs font-bold uppercase opacity-50">{{ __('Jam Keluar') }}</div>
                            <div class="text-lg font-bold">{{ $todayLog->check_out_at->format('H:i') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-ui::card>
</div>