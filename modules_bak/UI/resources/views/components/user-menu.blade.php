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
                    <div class="flex flex-col items-start text-left">
                        <span class="text-sm font-bold leading-none">{{ $user->name }}</span>
                        <span class="text-[10px] uppercase tracking-wider text-base-content/60 font-semibold">{{ $role }}</span>
                    </div>
                </div>
            </x-slot:trigger>

            <x-ui::menu-item 
                icon="tabler.user" 
                title="ui::common.profile" 
                :link="$profileRoute" 
            />

            @if($user->hasRole('super-admin'))
                <x-ui::menu-item 
                    icon="tabler.settings" 
                    title="ui::common.settings" 
                    :link="route('profile.index', ['tab' => 'security'])" 
                />
            @endif
            
            <div class="divider my-1 opacity-5"></div>
            
            <li>
                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-base-content/5 text-base-content/70 hover:text-base-content"
                    >
                        <x-ui::icon name="tabler.logout" class="size-5 opacity-50 group-hover:opacity-100 transition-transform duration-200 group-hover:scale-110" />
                        <span class="flex-1 truncate text-left">{{ __('ui::common.logout') }}</span>
                    </button>
                </form>
            </li>
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
