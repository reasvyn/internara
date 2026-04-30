@props(['title' => null])

<header class="bg-base-100 shadow border-b border-base-200 sticky top-0 z-50">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo & Brand -->
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <x-mary-avatar 
                        :image="App\Support\AppInfo::get('logo_url')" 
                        class="w-8 h-8" 
                        title="{{ App\Support\AppInfo::get('name', config('app.name')) }}"
                    />
                    <span class="text-lg font-bold hidden md:block">
                        {{ App\Support\AppInfo::get('name', config('app.name')) }}
                    </span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="hidden md:flex items-center gap-6">
                @if(auth()->user()->hasRole('super_admin|admin'))
                    <a href="{{ route('admin.school') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('admin.school') ? 'text-primary' : '' }}">
                        {{ __('school.title') }}
                    </a>
                    <a href="{{ route('admin.departments') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('admin.departments') ? 'text-primary' : '' }}">
                        {{ __('department.title') }}
                    </a>
                    <a href="{{ route('admin.internships') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('admin.internships*') ? 'text-primary' : '' }}">
                        {{ __('internship.title') }}
                    </a>
                    <a href="{{ route('admin.companies') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('admin.companies') ? 'text-primary' : '' }}">
                        {{ __('company.title') }}
                    </a>
                    <a href="{{ route('admin.settings') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('admin.settings') ? 'text-primary' : '' }}">
                        {{ __('setting.title') }}
                    </a>
                @endif

                @if(auth()->user()->hasRole('student'))
                    <a href="{{ route('student.dashboard') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('student.dashboard') ? 'text-primary' : '' }}">
                        {{ __('student.dashboard') }}
                    </a>
                    <a href="{{ route('student.journals') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('student.journals*') ? 'text-primary' : '' }}">
                        {{ __('journal.title') }}
                    </a>
                @endif

                @if(auth()->user()->hasRole('teacher|mentor'))
                    <a href="{{ route('supervision.logs') }}" 
                       class="text-sm font-medium hover:text-primary {{ request()->routeIs('supervision.*') ? 'text-primary' : '' }}">
                        {{ __('supervision.title') }}
                    </a>
                @endif
            </nav>

            <!-- User Menu -->
            <div class="flex items-center gap-3">
                <!-- Mobile menu button -->
                <x-mary-button 
                    icon="o-bars-3" 
                    class="btn-ghost btn-sm md:hidden" 
                    @click="$dispatch('toggle-sidebar')"
                />

                <!-- Theme Switcher -->
                <livewire:theme-switcher />
                
                <!-- Language Switcher -->
                <livewire:language-switcher />
                
                <!-- Notifications -->
                <x-mary-button icon="o-bell" class="btn-ghost btn-sm" badge="3" />

                <!-- User Dropdown -->
                <x-mary-dropdown>
                    <x-slot:trigger>
                        <x-mary-avatar 
                            :title="auth()->user()->name" 
                            class="w-8 h-8 cursor-pointer" 
                        />
                    </x-slot:trigger>

                    <x-mary-menu-item title="{{ auth()->user()->name }}" />
                    <x-mary-menu-item title="{{ __('profile.title') }}" icon="o-user" />
                    <x-mary-menu-item title="{{ __('auth.logout') }}" icon="o-arrow-right-on-rectangle" 
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();" />
                    
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </x-mary-dropdown>
            </div>
        </div>
    </div>
</header>
