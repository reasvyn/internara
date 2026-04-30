@props([
    'title' => null,
    'subtitle' => null,
    'separator' => false,
    'shadow' => true,
])

<div {{ $attributes->merge(['class' => 'bg-base-100 border border-base-200 rounded-2xl flex flex-col' . ($shadow ? ' shadow-md hover:shadow-lg transition-all duration-300' : '')]) }}>
    {{-- Card Header --}}
    @if($title || $subtitle || isset($menu))
        <div @class([
            'px-6 py-5 flex items-center gap-4',
            'justify-between' => isset($menu),
            'justify-center' => !isset($menu) && str_contains($attributes->get('class', ''), 'text-center')
        ])>
            <div @class(['flex-1' => !isset($menu) && str_contains($attributes->get('class', ''), 'text-center')])>
                @if($title)
                    <h3 class="text-lg font-black tracking-tight text-base-content">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-xs opacity-50 font-medium leading-relaxed">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($menu)
                <div class="flex items-center gap-2">
                    {{ $menu }}
                </div>
            @endisset
        </div>

        @if($separator)
            <div class="divider m-0 opacity-10 px-6"></div>
        @endif
    @endif

    {{-- Card Body --}}
    <div class="p-6 flex-1">
        {{ $slot }}
    </div>

    {{-- Card Footer --}}
    @isset($actions)
        <div class="px-6 py-4 bg-base-200/30 border-t border-base-200 rounded-b-2xl flex items-center justify-end gap-3">
            {{ $actions }}
        </div>
    @endisset
</div>
