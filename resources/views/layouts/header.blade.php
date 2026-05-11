@props(['header' => null])

<header class="bg-base-100/70 backdrop-blur-xl border-b border-base-content/5 sticky top-0 z-50 transition-all duration-300">
    <div class="container mx-auto max-w-7xl px-4 md:px-8 lg:px-10">
        <div class="flex items-center justify-between h-20">
            
            <!-- Logo & Brand (Mobile Navigation Trigger) -->
            <div class="flex items-center gap-4 lg:hidden">
                <label for="main-drawer" class="btn btn-ghost btn-circle drawer-button hover:bg-base-200">
                    <x-mary-icon name="o-bars-3-bottom-left" class="size-6" />
                </label>
                <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                    <div class="size-9 rounded-xl bg-primary flex items-center justify-center shadow-lg shadow-primary/20 transform transition-all group-hover:scale-110">
                        <img src="{{ brand('logo') }}" class="size-5 object-contain brightness-0 invert" alt="{{ brand('name') }}" />
                    </div>
                </a>
            </div>

            <!-- Page Identity (Desktop) -->
            <div class="hidden lg:flex items-center gap-4">
                @if($header)
                    <div class="flex flex-col">
                        <h1 class="text-xl font-black tracking-tighter text-base-content">{{ $header }}</h1>
                        <div class="h-1 w-8 bg-primary rounded-full mt-1"></div>
                    </div>
                @else
                    <div class="flex items-center gap-2 opacity-20">
                        <div class="size-2 rounded-full bg-base-content animate-pulse"></div>
                        <div class="h-4 w-32 bg-base-content/10 rounded-full"></div>
                    </div>
                @endif
            </div>

            <!-- Controls & Actions -->
            <div class="flex items-center gap-2 sm:gap-4">
                
                {{-- Utility Bar (Theme, Language, Notifications) --}}
                <div class="flex items-center gap-1 sm:gap-2 p-1 bg-base-200/50 rounded-2xl border border-base-content/5">
                    <!-- Theme -->
                    <x-ui::theme-switcher class="px-2" />
                    
                    <div class="divider divider-horizontal mx-0 opacity-10 h-6"></div>
                    
                    <!-- Language -->
                    <x-ui::lang-switcher class="px-2" />
                </div>

                <!-- Notifications -->
                @auth
                    <div class="relative group">
                        <livewire:notification.notification-bell />
                    </div>

                    <div class="divider divider-horizontal mx-1 opacity-5 hidden sm:flex"></div>

                    <!-- User Account -->
                    <x-mary-dropdown right class="!z-[60]">
                        <x-slot:trigger>
                            <button class="flex items-center gap-3 p-1 rounded-2xl hover:bg-base-200 transition-all duration-300 group focus:outline-none">
                                <div class="flex flex-col items-end hidden sm:flex pr-1">
                                    <span class="text-xs font-black tracking-tight leading-none group-hover:text-primary transition-colors">{{ auth()->user()->name }}</span>
                                    <span class="text-[9px] uppercase tracking-widest font-black opacity-30 mt-1.5">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span>
                                </div>
                                <div class="relative">
                                    <div class="size-10 rounded-xl overflow-hidden shadow-inner ring-1 ring-base-content/5 group-hover:ring-primary/40 transition-all">
                                        <x-mary-avatar :title="auth()->user()->name" class="size-full" />
                                    </div>
                                    <div class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full bg-success border-2 border-base-100 shadow-sm"></div>
                                </div>
                            </button>
                        </x-slot:trigger>

                        {{-- Dropdown Content --}}
                        <div class="w-64 p-2 bg-base-100 rounded-3xl shadow-2xl border border-base-content/5">
                            <div class="px-4 py-4 mb-2 rounded-2xl bg-base-200/50">
                                <p class="text-sm font-black tracking-tight">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] font-bold opacity-40 truncate mt-0.5">{{ auth()->user()->email }}</p>
                            </div>
                            
                            <x-mary-menu-item :title="__('profile.title')" icon="o-user-circle" link="{{ route('profile') }}" wire:navigate class="rounded-xl !text-xs !font-bold py-3" />
                            <x-mary-menu-item :title="__('Settings')" icon="o-cog-6-tooth" class="rounded-xl !text-xs !font-bold py-3" />
                            
                            <x-mary-menu-separator class="opacity-5" />
                            
                            <x-mary-menu-item :title="__('auth.logout')" icon="o-arrow-left-on-rectangle" 
                                class="text-error hover:bg-error/10 rounded-xl !text-xs !font-bold py-3"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();" />
                            
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        </div>
                    </x-mary-dropdown>
                @endauth
            </div>
        </div>
    </div>
</header>
