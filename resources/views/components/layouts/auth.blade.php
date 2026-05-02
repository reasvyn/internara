@props(['title' => null])

@php
    $brandName = App\Support\Branding::brandName();
    $brandLogo = App\Support\Branding::logo();
@endphp

<x-layouts.base :$title bodyClass="min-h-screen bg-base-200 flex items-center justify-center py-12">
    <div class="w-full max-w-md px-4">
        <!-- Logo -->
        <div class="text-center mb-10">
            <a href="{{ route('dashboard') }}" class="inline-flex flex-col items-center gap-4 group">
                <div class="size-20 rounded-3xl bg-white shadow-xl shadow-primary/5 border border-base-300/50 p-3 overflow-hidden transition-transform group-hover:scale-110 duration-300">
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="size-full object-contain rounded-2xl" />
                </div>
                <span class="text-3xl font-black tracking-tighter text-base-content group-hover:text-primary transition-colors">
                    {{ $brandName }}
                </span>
            </a>
        </div>

        {{ $slot }}
    </div>
</x-layouts.base>
