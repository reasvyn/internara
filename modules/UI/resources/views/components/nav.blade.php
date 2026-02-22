@props([
    'brand' => null,
    'actions' => null,
    'hamburger' => null,
])

<nav {{ $attributes->merge(['class' => 'navbar px-4 lg:px-8 min-h-16 gap-4']) }}>
    {{-- Left Side: Hamburger & Brand --}}
    <div class="navbar-start w-auto flex items-center gap-2">
        @if($hamburger)
            <div class="lg:hidden">
                {{ $hamburger }}
            </div>
        @endif
        
        <div class="flex items-center">
            @isset($brand)
                {{ $brand }}
            @else
                <x-ui::brand />
            @endisset
        </div>
    </div>

    {{-- Center Side: Navigation Items --}}
    <div class="navbar-center hidden md:flex flex-1 justify-center items-center">
        {{ $slot }}
    </div>

    {{-- Right Side: User Menu / Actions --}}
    <div class="navbar-end w-auto hidden lg:flex items-center gap-2">
        {{ $actions ?? '' }}
    </div>
</nav>
