<div>
    <x-mary-card shadow class="card-enterprise">
        <div class="flex flex-col items-center text-center space-y-6">
            <div class="flex flex-col items-center">
                <div class="text-5xl font-black tracking-tighter text-primary mb-1" wire:poll.1s>
                    {{ now()->format('H:i:s') }}
                </div>
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30">
                    {{ now()->format('l, d F Y') }}
                </div>
            </div>

            <div class="w-full border-t border-base-200/50 pt-6">
                @if(!$todayLog)
                    <x-mary-button 
                        label="Clock In" 
                        icon="o-bolt" 
                        class="btn-primary btn-lg w-full rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-primary/20" 
                        wire:click="clockIn" 
                        spinner="clockIn" />
                @elseif(!$todayLog->clock_out)
                    <div class="w-full space-y-4">
                        <div class="bg-success/5 border border-success/20 rounded-2xl py-3 px-4 flex flex-col items-center gap-1">
                            <div class="flex items-center gap-2 text-success">
                                <x-mary-icon name="o-check-circle" class="size-4" />
                                <span class="text-xs font-black uppercase tracking-widest">Active Session</span>
                            </div>
                            <span class="text-lg font-black tracking-tight">Started at {{ $todayLog->clock_in->format('H:i') }}</span>
                        </div>
                        
                        <x-mary-button 
                            label="Clock Out" 
                            icon="o-stop-circle" 
                            class="btn-error btn-lg w-full rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-error/20" 
                            wire:click="clockOut" 
                            spinner="clockOut" />
                    </div>
                @else
                    <div class="w-full space-y-4">
                        <div class="bg-base-200/50 border border-base-content/5 rounded-2xl py-4 px-4 flex flex-col items-center gap-2">
                            <div class="size-10 rounded-xl bg-base-100 flex items-center justify-center text-base-content/30">
                                <x-mary-icon name="o-flag" class="size-6" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-black uppercase tracking-widest text-base-content/40">Work Day Completed</span>
                                <span class="text-sm font-bold">{{ $todayLog->clock_in->format('H:i') }} &mdash; {{ $todayLog->clock_out->format('H:i') }}</span>
                            </div>
                        </div>
                        
                        <div class="text-[10px] font-black uppercase tracking-widest text-base-content/20 italic">
                            Keep up the great work!
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-mary-card>
</div>
