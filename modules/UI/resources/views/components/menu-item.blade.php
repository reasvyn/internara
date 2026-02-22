@props([
    'title' => null,
    'icon' => null,
    'link' => null,
    'external' => false,
])

@php
    $isActive = false;
    
    if ($link) {
        $path = ltrim($link, '/');
        $isActive = request()->is($path) || request()->is($path . '/*');
    }

    $classes = $isActive 
        ? 'bg-accent/10 text-accent font-bold' 
        : 'hover:bg-base-content/5 text-base-content/70 hover:text-base-content';
@endphp

<li>
    <a 
        {{ $attributes->merge([
            'href' => $link, 
            'wire:navigate' => !$external,
            'target' => $external ? '_blank' : '_self'
        ])->class(['flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group', $classes]) }}
        @click="document.getElementById('main-drawer').checked = false"
    >
        @if($icon)
            <x-ui::icon 
                :name="$icon" 
                @class([
                    'size-5 transition-transform duration-200 group-hover:scale-110',
                    'text-accent' => $isActive,
                    'opacity-50 group-hover:opacity-100' => !$isActive
                ]) 
            />
        @endif
        
        <span class="flex-1 truncate">
            {{ __($title) }}
        </span>

        @if($isActive)
            <span class="size-1.5 rounded-full bg-accent shadow-[0_0_8px_rgba(var(--color-accent),0.5)]"></span>
        @endif
    </a>
</li>
