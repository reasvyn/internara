@props([
    'id' => 'main-drawer',
])

<aside {{ $attributes->merge(['class' => 'bg-base-200 border-r border-base-300 w-full sm:w-80 flex flex-col transition-all duration-300 shadow-sm']) }}>
    {{-- Top Section: Brand --}}
    <div class="flex items-center h-16 min-h-[4rem] px-6 border-b border-base-300 bg-base-100">
        <x-ui::brand class="h-8" />
        <div class="flex-1"></div>
        <label for="main-drawer" class="btn btn-ghost btn-sm btn-circle lg:hidden" aria-label="{{ __('ui::common.close') }}">
            <x-ui::icon name="tabler.x" class="size-6 opacity-40" />
        </label>
    </div>

    {{-- Content Section --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
        <div class="flex flex-col gap-6">
            {{-- 1. Configuration Section (Utility actions like language/theme) --}}
            <div class="space-y-3">
                <div class="border-b border-base-300 pb-2 mb-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">{{ __('ui::common.settings') }}</span>
                </div>
                
                {{-- Language Switcher (Mobile) --}}
                <div class="bg-base-300/30 rounded-2xl p-4 border border-base-content/5 flex items-center justify-between">
                    <span class="text-sm font-semibold">{{ __('ui::common.language') }}</span>
                    @slotRender('navbar.actions', ['filter' => 'livewire:ui::language-switcher'])
                </div>

                {{-- Theme Toggle (Mobile) --}}
                <div class="bg-base-300/30 rounded-2xl p-4 border border-base-content/5 flex items-center justify-between">
                    <span class="text-sm font-semibold">{{ __('ui::common.toggle_theme') }}</span>
                    <x-ui::theme-toggle />
                </div>
            </div>

            {{-- 2. Menu Section (If passed via slot) --}}
            @if($slot->isNotEmpty())
                <div class="space-y-3">
                    <div class="border-b border-base-300 pb-2 mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">{{ __('ui::common.menu') }}</span>
                    </div>
                    <nav class="space-y-1">
                        <ul class="menu p-0 gap-1 w-full text-base-content/80 font-medium">
                            {{ $slot }}
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>

    {{-- Footer Section --}}
    @isset($footer)
        <div class="p-4 border-t border-base-300 bg-base-300/30">
            {{ $footer }}
        </div>
    @else
        <div class="p-6 border-t border-base-300 text-center">
            <x-ui::app-credit />
        </div>
    @endisset
</aside>
