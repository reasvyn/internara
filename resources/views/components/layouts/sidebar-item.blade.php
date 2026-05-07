@props(['href', 'icon', 'active' => false])

<li>
    <a
        wire:navigate
        href="{{ $href }}"
        @class([
            'flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 rounded-xl sm:rounded-2xl transition-all duration-300 min-h-[44px]',
            'bg-primary/10 text-primary font-black' => $active,
            'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' => !$active,
        ])
    >
        <x-mary-icon
            :name="$icon"
            @class([
                'size-4 sm:size-5 shrink-0',
                'text-primary' => $active,
                'opacity-50' => !$active,
            ])
        />
        <span class="text-xs sm:text-sm truncate">{{ $slot }}</span>
    </a>
</li>
