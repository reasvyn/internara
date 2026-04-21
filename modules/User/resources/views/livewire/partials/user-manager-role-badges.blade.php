<div class="flex flex-wrap gap-1">
    @foreach($user->roles as $role)
        <x-ui::badge
            :value="__('permission::roles.'.$role->name)"
            :variant="$manager->roleBadgeVariant($role->name)"
            class="badge-sm"
        />
    @endforeach
</div>
