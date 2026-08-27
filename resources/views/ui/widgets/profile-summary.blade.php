@props([
    'user' => null,
    'showEdit' => true,
])

@php
    $user = $user ?? auth()->user();
@endphp

<x-ts-card color="white">
    <div class="flex flex-col items-center py-4 text-center">
        <x-ui::ui.avatar :user="$user" size="size-16" class="mb-3" />
        <h3 class="font-semibold">{{ $user->name }}</h3>
        <p class="text-base-content/50 mt-0.5 text-xs">{{ $user->getRoleNames()->first() }}</p>
        @if ($showEdit)
            <div class="mt-4 w-full">
                <x-ts-button
                    :text="__('dashboard.edit_profile')"
                    icon="user"
                    color="white"
                    sm
                    class="w-full"
                    :href="route('profile')"
                    wire:navigate
                />
            </div>
        @endif
    </div>
</x-ts-card>
