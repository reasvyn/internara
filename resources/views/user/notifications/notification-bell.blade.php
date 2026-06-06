<div>
    <x-mary-button icon="o-bell" class="btn-ghost btn-sm relative" link="{{ route('notifications') }}">
        @if($unreadCount > 0)
            <span class="badge badge-error badge-xs absolute top-0 right-0 animate-pulse">{{ $unreadCount }}</span>
        @endif
    </x-mary-button>
</div>
