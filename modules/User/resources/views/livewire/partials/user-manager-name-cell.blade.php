<div class="flex items-center gap-3">
    <x-ui::avatar :image="$user->avatar_url" :title="$user->name" size="w-8" />
    <div class="font-semibold">{{ $user->name }}</div>
</div>
