@props(['header' => null])

<header class="bg-base-100 shadow border-b border-base-200 sticky top-0 z-50">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo & Brand -->
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                    <img 
                        src="{{ App\Support\Branding::logo() }}" 
                        class="w-8 h-8 rounded-lg object-cover shadow-sm transition-transform group-hover:scale-110 duration-300" 
                        alt="{{ App\Support\Branding::brandName() }}"
                    />
                    <span class="text-lg font-black hidden md:block">
                        {{ App\Support\Branding::brandName() }}
                    </span>
                </a>
            </div>

            <!-- Navigation (Desktop) -->
            <nav class="hidden lg:flex items-center gap-6">
                @auth
                    @if(auth()->user()->hasRole('super_admin|admin'))
                        <a href="{{ route('admin.dashboard') }}" 
                           class="text-[10px] font-black uppercase tracking-widest hover:text-primary {{ request()->routeIs('admin.dashboard') ? 'text-primary' : 'opacity-40' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('admin.internships') }}" 
                           class="text-[10px] font-black uppercase tracking-widest hover:text-primary {{ request()->routeIs('admin.internships*') ? 'text-primary' : 'opacity-40' }}">
                            {{ __('internship.title') }}
                        </a>
                        <a href="{{ route('admin.companies') }}" 
                           class="text-[10px] font-black uppercase tracking-widest hover:text-primary {{ request()->routeIs('admin.companies*') ? 'text-primary' : 'opacity-40' }}">
                            {{ __('company.title') }}
                        </a>
                    @endif
                @endauth
            </nav>

            <!-- User Menu -->
            <div class="flex items-center gap-3">
                <!-- Mobile menu button (toggle drawer) -->
                <label for="main-drawer" class="btn btn-ghost btn-sm drawer-button lg:hidden">
                    <x-mary-icon name="o-bars-3" class="size-6" />
                </label>

                <!-- Theme Switcher -->
                <livewire:theme-switcher />
                
                <!-- Language Switcher -->
                <livewire:language-switcher />
                
                <!-- Notifications -->
                @auth
                    <livewire:common.notification-bell />

                    <!-- User Dropdown -->
                    <x-mary-dropdown>
                        <x-slot:trigger>
                            <x-mary-avatar 
                                :title="auth()->user()->name" 
                                class="w-8 h-8 cursor-pointer ring-offset-2 ring-2 ring-transparent hover:ring-primary transition-all duration-300" 
                            />
                        </x-slot:trigger>

                        <x-mary-menu-item :title="auth()->user()->name" icon="o-user-circle" />
                        <x-mary-menu-item :title="__('profile.title')" icon="o-pencil-square" link="{{ route('profile') }}" />
                        
                        <x-mary-menu-separator />
                        
                        <x-mary-menu-item :title="__('auth.logout')" icon="o-arrow-right-on-rectangle" 
                            class="text-error"
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
