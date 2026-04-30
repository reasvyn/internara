<div>
    @if(!$isComplete)
        <div class="alert alert-error shadow-lg mb-6" role="alert">
            <x-ui::icon name="tabler.alert-circle" class="w-8 h-8" aria-hidden="true" />
            <div class="flex-1">
                <h2 class="font-bold text-base">{{ __('guidance::ui.requirements_title') }}</h2>
                <div class="text-xs">{{ __('guidance::ui.requirements_description') }}</div>
            </div>
            <div class="flex-none">
                <x-ui::button 
                    label="{{ __('guidance::ui.complete_now') }}" 
                    class="btn-sm btn-ghost bg-white/10" 
                    link="#handbook-hub" 
                    aria-label="{{ __('guidance::ui.complete_now') }}"
                />
            </div>
        </div>
    @endif
</div>
