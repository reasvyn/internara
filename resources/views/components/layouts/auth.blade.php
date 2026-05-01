@props([
    'title' => null,
])

<x-layouts.base :$title body-class="h-full font-sans antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center bg-base-200 px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <!-- Logo / Brand -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-base-content">{{ config('app.name', 'Internara') }}</h1>
                @if ($title)
                    <p class="mt-2 text-sm text-base-content/60">{{ $title }}</p>
                @endif
            </div>

            <!-- Page Content -->
            {{ $slot }}

            <!-- Author Signature -->
            <livewire:layout.app-signature />
        </div>
    </div>
</x-layouts.base>
