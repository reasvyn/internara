@props([
    'sidebar' => null,
    'actions' => null, 
    'footer' => null,
])

<main {{ $attributes->merge(['class' => 'flex-1 relative focus:outline-none']) }}>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        {{-- Page Actions (optional top-right on page) --}}
        @if($actions)
            <div class="mb-6 flex justify-end">
                {{ $actions }}
            </div>
        @endif

        {{-- Main Page Content --}}
        <div class="min-h-screen">
            {{ $slot }}
        </div>

        {{-- Footer Slot --}}
        @if($footer)
            <footer {{ $footer->attributes->merge(['class' => 'mt-auto py-10 border-t border-base-200']) }}>
                {{ $footer }}
            </footer>
        @endif
    </div>
</main>
