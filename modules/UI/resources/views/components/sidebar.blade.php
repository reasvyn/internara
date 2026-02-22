@props([
    'collapsible' => false,
])

<aside {{ $attributes->merge(['class' => 'bg-base-200 border-r border-base-300 w-80 flex flex-col transition-all duration-300 shadow-sm']) }}>
    {{-- Sidebar Top / Brand (Mobile only) --}}
    <div class="lg:hidden flex items-center h-16 min-h-[4rem] px-6 border-b border-base-300 bg-base-100">
        <x-ui::brand class="h-8" />
    </div>

    {{-- Sidebar Content / Menu --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
        {{-- Mobile Actions (Hidden on Desktop) --}}
        <div class="lg:hidden mb-6 flex flex-col gap-4 px-2">
            {{-- Header Label --}}
            <div class="border-b border-base-300 pb-2 mb-2">
                <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">{{ __('ui::common.menu') }}</span>
            </div>

            {{-- 1. Primary User Identity (Top) --}}
            <div class="bg-base-300/30 rounded-2xl p-4 shadow-inner border border-base-content/5">
                @slotRender('navbar.actions', ['filter' => 'ui::user-menu'])
            </div>
            
            {{-- 2. Configuration Group (Bottom Flex Row) --}}
            <div class="flex items-center justify-between gap-4 bg-base-300/30 rounded-2xl px-4 py-2 border border-base-content/5">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold">{{ __('ui::common.language') }}</span>
                    @slotRender('navbar.actions', ['filter' => 'livewire:ui::language-switcher'])
                </div>
                <div class="h-6 w-px bg-base-content/10"></div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold">{{ __('ui::common.toggle_theme') }}</span>
                    <x-ui::theme-toggle class="scale-90" />
                </div>
            </div>
        </div>

        <nav class="space-y-1">
            <ul class="menu p-0 gap-1 w-full text-base-content/80 font-medium">
                @slotRender('sidebar.menu')
            </ul>
        </nav>
    </div>

    {{-- Sidebar Footer / Bottom Actions --}}
    @isset($footer)
        <div class="p-4 border-t border-base-300 bg-base-300/30">
            {{ $footer }}
        </div>
    @endisset
</aside>
