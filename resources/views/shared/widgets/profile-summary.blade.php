@props([
    'user' => null,
    'showEdit' => true,
])

@php
    $user = $user ?? auth()->user();
@endphp

<x-mary-card class="bg-base-100 border border-base-content/10">
    <div class="flex flex-col items-center py-4 text-center">
        <x-mary-avatar
            :image="$user->getFirstMediaUrl('avatar', 'thumb') ?: null"
            placeholder="{{ $user->initials() }}"
            class="!w-16 !h-16 mb-3"
        />
        <h3 class="font-semibold">{{ $user->name }}</h3>
        <p class="text-xs text-base-content/50 mt-0.5">{{ $user->getRoleNames()->first() }}</p>
        @if($showEdit)
            <div class="w-full mt-4">
                <x-mary-button
                    :label="__('dashboard.edit_profile')"
                    icon="o-user"
                    class="btn-ghost btn-sm w-full"
                    link="{{ route('profile') }}"
                />
            </div>
        @endif
    </div>
</x-mary-card>
