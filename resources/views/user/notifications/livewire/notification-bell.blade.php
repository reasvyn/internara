<div>
    <x-ts-button
        icon="bell"
        aria-label="{{ __('notifications.ui.title') }}"
        class="relative"
        color="white"
        sm
        href="{{ route('notifications') }}"
    >
        @if ($unreadCount > 0)
            <span class="badge badge-error badge-xs absolute top-0 right-0 animate-pulse">{{ $unreadCount }}</span>
        @endif
    </x-ts-button>
</div>
