@props(['auditResults', 'auditPassed'])

<div class="p-4 md:p-8">
    <div class="text-center py-10">
        <div class="inline-flex items-center justify-center size-24 rounded-3xl bg-primary/10 text-primary mb-6">
            <x-mary-icon name="o-rocket-launch" class="size-12" />
        </div>
        <h2 class="text-3xl font-black tracking-tight mb-3">{{ __('setup.wizard.welcome') }}</h2>
        <p class="text-base-content/60 max-w-md mx-auto leading-relaxed">
            {{ __('setup.wizard.welcome_desc') }}
        </p>
    </div>

    <div class="space-y-10 mb-10">
        @foreach($auditResults['categories'] as $key => $category)
            <div x-data="{ open: true }">
                <div class="divider uppercase text-[10px] font-bold tracking-[0.2em] opacity-30 mb-6 cursor-pointer hover:opacity-100 transition-opacity" @click="open = !open">
                    {{ $category['label'] }}
                    <x-mary-icon name="o-chevron-down" class="size-3 ml-2 transition-transform" ::class="open ? '' : '-rotate-90'" />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="open" x-collapse>
                    @foreach($category['checks'] as $check)
                        <div class="flex items-center gap-4 p-4 rounded-3xl border border-base-content/5 transition-all hover:shadow-lg hover:shadow-base-content/5 {{ $check['status'] === 'fail' ? 'bg-error/5 border-error/20' : ($check['status'] === 'warn' ? 'bg-warning/5 border-warning/20' : 'bg-base-100') }}">
                            <div class="shrink-0">
                                @if($check['status'] === 'pass')
                                    <div class="size-10 rounded-2xl bg-success/10 text-success flex items-center justify-center">
                                        <x-mary-icon name="o-check" class="size-5" />
                                    </div>
                                @elseif($check['status'] === 'fail')
                                    <div class="size-10 rounded-2xl bg-error/10 text-error flex items-center justify-center">
                                        <x-mary-icon name="o-x-mark" class="size-5" />
                                    </div>
                                @else
                                    <div class="size-10 rounded-2xl bg-warning/10 text-warning flex items-center justify-center">
                                        <x-mary-icon name="o-exclamation-triangle" class="size-5" />
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-black uppercase tracking-wide block leading-none mb-1">{{ $check['name'] }}</span>
                                <p class="text-[11px] text-base-content/50 truncate font-medium">{{ $check['message'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex justify-end pt-6 border-t border-base-200">
        @if($auditPassed)
            <x-mary-button 
                label="{{ __('setup.wizard.start_setup') }}" 
                icon-right="o-arrow-long-right" 
                class="btn-primary btn-wide rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-primary/20" 
                wire:click="nextStep" 
                spinner="nextStep"
            />
        @else
            <x-mary-button 
                label="{{ __('setup.wizard.recheck') }}" 
                icon="o-arrow-path" 
                class="btn-warning rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-warning/20" 
                wire:click="runAudit" 
                spinner="runAudit" 
            />
        @endif
    </div>
</div>
