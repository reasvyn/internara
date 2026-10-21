@props([
    'context' => null,
])

{{--
    UI Wrapper: Breadcrumbs
    Provides unified breadcrumb navigation with non-sharp corners and theme integration.
--}}
@if ($context)
    <nav
        aria-label="Breadcrumb"
        {{ $attributes->merge(['class' => 'text-base-content/60 mb-3.5 sm:mb-4 flex items-center gap-2 text-xs']) }}
    >
        <a wire:navigate href="{{ route('dashboard') }}" class="hover:text-primary transition-colors">
            {{ brand('name') }}
        </a>
        <span class="opacity-40">/</span>
        <span class="text-primary font-medium"> {{ __($context) }} </span>
    </nav>
@else
    <x-ts-breadcrumbs {{ $attributes->class(['rounded-xl']) }} />
@endif
