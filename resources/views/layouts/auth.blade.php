@props(['title' => null])

@php
    $brandName = brand('name');
    $brandLogo = brand('logo');
@endphp

<x-layouts.base :$title bodyClass="min-h-screen bg-base-300 flex items-center justify-center py-12 relative overflow-hidden">
    {{-- Decorative Background Elements (Modern UI Pattern) --}}
    <div class="absolute -top-[10%] -left-[10%] size-[40%] rounded-full bg-primary/5 blur-[120px] animate-pulse"></div>
    <div class="absolute -bottom-[10%] -right-[10%] size-[40%] rounded-full bg-secondary/5 blur-[120px] animate-pulse" style="animation-delay: 2s"></div>

    <div class="w-full max-w-md px-4 relative z-10">
        <!-- Logo -->
        <div class="text-center mb-10 animate-in fade-in slide-in-from-top-8 duration-700">
            <a href="{{ route('dashboard') }}" class="inline-flex flex-col items-center gap-4 group" wire:navigate>
                <div class="size-20 rounded-[2rem] bg-primary flex items-center justify-center shadow-2xl shadow-primary/20 border border-primary/10 transition-transform group-hover:scale-110 duration-500">
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="size-12 object-contain brightness-0 invert" />
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-3xl font-black tracking-tighter text-base-content group-hover:text-primary transition-colors leading-none">
                        {{ $brandName }}
                    </span>
                    <span class="text-[9px] uppercase tracking-[0.3em] font-black opacity-30 mt-2">Authentication</span>
                </div>
            </a>
        </div>

        {{ $slot }}

        <!-- Footer / Language Toggle -->
        <div class="mt-12 text-center flex items-center justify-center gap-4 animate-in fade-in duration-1000 delay-500">
            <livewire:theme-switcher />
            <livewire:language-switcher />
        </div>
    </div>
</x-layouts.base>
