<div>
    <div
        @class([
            'badge',
            $color,
            'badge-'.$size,
            'font-medium uppercase tracking-wider text-[10px]',
        ])
        role="status"
        aria-label="{{ __('permission::roles.'.$roleName) }}"
    >
        {{ __('permission::roles.'.$roleName) }}
    </div>
</div>
