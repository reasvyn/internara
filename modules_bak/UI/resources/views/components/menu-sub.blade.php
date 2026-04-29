@props([
    'title' => null,
    'icon' => null,
    'open' => false,
])

<li>
    <details {{ $open ? 'open' : '' }} class="group">
        <summary @class([
            'flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-base-content/5 text-base-content/70 hover:text-base-content transition-all duration-200 cursor-pointer list-none group-open:text-base-content',
        ])>
            @if($icon)
                <x-ui::icon :name="$icon" class="size-5 opacity-50 group-hover:opacity-100 group-open:opacity-100 transition-all" />
            @endif
            
            <span class="flex-1 font-medium">{{ __($title) }}</span>
            
            <x-ui::icon name="tabler.chevron-right" class="size-4 opacity-30 group-open:rotate-90 transition-transform duration-200" />
        </summary>
        
        <ul class="mt-1 ml-4 border-l border-base-300 pl-2 space-y-1">
            {{ $slot }}
        </ul>
    </details>
</li>
