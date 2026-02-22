<div class="flex items-center gap-4">
    @auth
        <x-ui::dropdown right variant="tertiary" class="rounded-2xl p-1">
            <x-slot:trigger>
                <div class="flex items-center gap-3 pr-2 cursor-pointer" aria-label="{{ __('ui::common.user_menu') }}">
                    <x-ui::avatar 
                        :image="$user->avatar_url" 
                        :title="$user->name" 
                        size="w-9"
                    />
                    <div class="hidden lg:flex flex-col items-start text-left">
                        <span class="text-sm font-bold leading-none">{{ $user->name }}</span>
                        <span class="text-[10px] uppercase tracking-wider text-base-content/60 font-semibold">{{ $role }}</span>
                    </div>
                </div>
            </x-slot:trigger>

            <x-ui::menu-item 
                icon="tabler.user" 
                :title="__('ui::common.profile')" 
                :link="$profileRoute" 
            />

            @if($user->hasRole('super-admin'))
                <x-ui::menu-item 
                    icon="tabler.settings" 
                    :title="__('ui::common.settings')" 
                    :link="route('profile.index', ['tab' => 'security'])" 
                />
            @endif
            
            <div class="divider my-1 opacity-5"></div>
            
            <x-ui::menu-item 
                icon="tabler.logout" 
                :title="__('ui::common.logout')" 
                :link="$logoutRoute"
                external
            />
        </x-ui::dropdown>
    @else
        <div class="flex flex-nowrap items-center gap-2">
            @if ($hasLogin)
                <x-ui::button 
                    variant="primary" 
                    :label="__('ui::common.login')" 
                    :link="$loginRoute" 
                />
            @endif

            @if ($hasRegister)
                <x-ui::button 
                    variant="secondary" 
                    :label="__('ui::common.register')" 
                    :link="$registerRoute" 
                />
            @endif
        </div>
    @endauth
</div>
