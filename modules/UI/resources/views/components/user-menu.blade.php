<div class="flex items-center gap-4">
    @auth
        <x-mary-dropdown right class="btn-ghost rounded-2xl p-1">
            <x-slot:label>
                <div class="flex items-center gap-3 pr-2">
                    <x-ui::avatar 
                        :image="auth()->user()->avatar_url" 
                        :title="auth()->user()->name" 
                        size="w-9"
                    />
                    <div class="hidden lg:flex flex-col items-start text-left">
                        <span class="text-sm font-bold leading-none">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] uppercase tracking-wider text-base-content/60 font-semibold">{{ auth()->user()->roles->first()?->name ?? 'User' }}</span>
                    </div>
                </div>
            </x-slot:label>

            <x-mary-menu-item 
                icon="tabler.user" 
                :title="__('ui::common.profile')" 
                link="/profile" 
            />
            
            <x-mary-menu-separator />
            
            <x-mary-menu-item 
                icon="tabler.logout" 
                :title="__('ui::common.logout')" 
                link="/logout"
                no-wire-navigate 
            />
        </x-mary-dropdown>
    @else
        <div class="flex flex-nowrap items-center gap-2">
            @if (Route::has('login'))
                <x-ui::button 
                    priority="primary" 
                    :label="__('ui::common.login')" 
                    link="{{ route('login') }}" 
                />
            @endif

            @if (Route::has('register'))
                <x-ui::button 
                    priority="secondary" 
                    :label="__('ui::common.register')" 
                    link="{{ route('register') }}" 
                />
            @endif
        </div>
    @endauth
</div>
