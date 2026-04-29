<x-ui::badge
    :value="__('user::ui.manager.form.' . $user->display_status)"
    :variant="$manager->statusBadgeVariant($user->display_status)"
    class="badge-sm"
/>
