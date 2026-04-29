<div>
    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="flex flex-col items-center text-center space-y-4">
            <div class="text-4xl font-mono font-bold text-primary" wire:poll.1s>
                {{ now()->format('H:i:s') }}
            </div>
            <div class="text-sm text-base-content/70">
                {{ now()->format('l, d F Y') }}
            </div>

            @if(!$todayLog)
                <x-mary-button 
                    label="Clock In" 
                    icon="o-play-circle" 
                    class="btn-primary btn-lg w-full" 
                    wire:click="clockIn" 
                    spinner="clockIn" />
            @elseif(!$todayLog->clock_out)
                <div class="w-full space-y-2">
                    <div class="alert alert-success py-2 text-sm flex justify-center gap-2">
                        <x-mary-icon name="o-check-circle" class="w-4 h-4" />
                        Clocked in at {{ $todayLog->clock_in->format('H:i') }}
                    </div>
                    <x-mary-button 
                        label="Clock Out" 
                        icon="o-stop-circle" 
                        class="btn-error btn-lg w-full" 
                        wire:click="clockOut" 
                        spinner="clockOut" />
                </div>
            @else
                <div class="w-full space-y-2">
                    <div class="alert alert-neutral py-2 text-sm flex flex-col items-center">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-flag" class="w-4 h-4" />
                            Day Completed
                        </div>
                        <div class="text-xs opacity-70">
                            {{ $todayLog->clock_in->format('H:i') }} - {{ $todayLog->clock_out->format('H:i') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-mary-card>
</div>
