@props(['header' => null])

<header class="bg-base-100/60 backdrop-blur-xl border-b border-base-content/5 sticky top-0 z-50">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Logo & Brand (Mobile) -->
            <div class="flex items-center gap-3 lg:hidden">
                <label for="main-drawer" class="btn btn-ghost btn-circle drawer-button">
                    <x-mary-icon name="o-bars-3" class="size-6" />
                </label>
                <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                    <div class="size-9 rounded-xl bg-primary flex items-center justify-center shadow-lg shadow-primary/20 transition-transform group-hover:scale-110 duration-500">
                        <img 
                            src="{{ brand('logo') }}" 
                            class="size-5 object-contain brightness-0 invert" 
                            alt="{{ brand('name') }}"
                        />
                    </div>
                </a>
            </div>

            <!-- Page Header (Desktop) -->
            <div class="hidden lg:block">
                @if($header)
                    <h1 class="text-xl font-black tracking-tighter">{{ $header }}</h1>
                @else
                    <div class="h-6 w-32 bg-base-content/5 rounded-full animate-pulse"></div>
                @endif
            </div>

            <!-- User Menu & Actions -->
            <div class="flex items-center gap-4">
                <!-- Theme Switcher -->
                <div class="hidden md:flex bg-base-200/50 p-1 rounded-xl border border-base-content/5">
                    <livewire:theme-switcher />
                </div>
                
                <!-- Language Switcher -->
                <div class="hidden md:block">
                    <livewire:language-switcher />
                </div>
                
                <!-- Notifications -->
                @auth
                    <div class="relative">
                        <livewire:shared.notification-bell />
                    </div>

                    <div class="divider divider-horizontal mx-1 opacity-20 h-8 self-center"></div>

                    <!-- User Dropdown -->
                    <x-mary-dropdown right>
                        <x-slot:trigger>
                            <button class="flex items-center gap-3 text-left group focus:outline-none">
                                <div class="flex flex-col items-end hidden md:flex">
                                    <span class="text-xs font-black tracking-tight leading-none group-hover:text-primary transition-colors">{{ auth()->user()->name }}</span>
                                    <span class="text-[9px] uppercase tracking-widest font-bold opacity-40 mt-1">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span>
                                </div>
                                <div class="relative">
                                    <x-mary-avatar 
                                        :title="auth()->user()->name" 
                                        class="size-10 rounded-2xl shadow-md ring-2 ring-transparent group-hover:ring-primary/50 transition-all duration-300" 
                                    />
                                    <div class="absolute -bottom-1 -right-1 size-3 rounded-full bg-success border-2 border-base-100"></div>
                                </div>
                            </button>
                        </x-slot:trigger>

                        <div class="px-4 py-3 border-b border-base-content/5 mb-1 bg-base-200/30">
                            <p class="text-sm font-bold">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] truncate opacity-50">{{ auth()->user()->email }}</p>
                        </div>
                        
                        <x-mary-menu-item :title="__('profile.title')" icon="o-user" link="{{ route('profile') }}" wire:navigate class="rounded-xl mx-1" />
                        
                        <x-mary-menu-separator />
                        
                        <x-mary-menu-item :title="__('auth.logout')" icon="o-power" 
                            class="text-error hover:bg-error/10 hover:text-error rounded-xl mx-1"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();" />
                        
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </x-mary-dropdown>
                @endauth
            </div>
        </div>
    </div>
</header>
