@props(['items' => []])
@php
$currentRoute = request()->route()?->getName() ?? '';
$brandName = App\Support\Branding::brandName();
$brandLogo = App\Support\Branding::logo();
@endphp

<div class="drawer-side z-40">
    <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
    
    <aside class="bg-base-100 min-h-screen w-64 md:w-56 lg:w-64 shadow-xl border-r border-base-200 flex flex-col">
        <!-- Logo -->
        <div class="p-4 border-b border-base-200">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                <x-mary-avatar 
                    :image="$brandLogo" 
                    class="w-9 h-9 transition-transform group-hover:scale-110 duration-300" 
                />
                <div class="flex flex-col min-w-0">
                    <span class="font-black text-base truncate leading-tight">{{ $brandName }}</span>
                    <span class="text-[9px] uppercase tracking-widest font-black opacity-30 leading-none">Management</span>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            @auth
                @if(auth()->user()->hasRole('super_admin|admin'))
                    <ul class="menu menu-sm gap-1">
                        <li class="menu-title text-xs opacity-50">{{ trans('admin.title') ?: 'Administration' }}</li>
                        <li>
                            <a href="{{ route('admin.school') }}" 
                               class="{{ request()->routeIs('admin.school*') ? 'active' : '' }}">
                                <x-mary-icon name="o-academic-cap" class="size-5" />
                                {{ __('school.title') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.departments') }}" 
                               class="{{ request()->routeIs('admin.departments*') ? 'active' : '' }}">
                                <x-mary-icon name="o-rectangle-group" class="size-5" />
                                {{ __('department.title') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.internships') }}" 
                               class="{{ request()->routeIs('admin.internships*') ? 'active' : '' }}">
                                <x-mary-icon name="o-briefcase" class="size-5" />
                                {{ __('internship.title') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.companies') }}" 
                               class="{{ request()->routeIs('admin.companies*') ? 'active' : '' }}">
                                <x-mary-icon name="o-building-office" class="size-5" />
                                {{ __('company.title') }}
                            </a>
                        </li>
                        
                        <li class="menu-title text-xs opacity-50 mt-4">{{ trans('user.student.title') ?: 'Users' }}</li>
                        <li>
                            <a href="{{ route('admin.users.admins') }}" 
                               class="{{ request()->routeIs('admin.users.admins*') ? 'active' : '' }}">
                                <x-mary-icon name="o-user-circle" class="size-5" />
                                {{ __('user.admin.title') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.users.students') }}" 
                               class="{{ request()->routeIs('admin.users.students*') ? 'active' : '' }}">
                                <x-mary-icon name="o-user-group" class="size-5" />
                                {{ __('user.student.title') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.users.teachers') }}" 
                               class="{{ request()->routeIs('admin.users.teachers*') ? 'active' : '' }}">
                                <x-mary-icon name="o-academic-cap" class="size-5" />
                                {{ __('user.teacher.title') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.users.mentors') }}" 
                               class="{{ request()->routeIs('admin.users.mentors*') ? 'active' : '' }}">
                                <x-mary-icon name="o-user-plus" class="size-5" />
                                {{ __('user.mentor.title') }}
                            </a>
                        </li>
                        
                        <li class="menu-title text-xs opacity-50 mt-4">{{ trans('setting.groups.system') ?: 'System' }}</li>
                        <li>
                            <a href="{{ route('admin.settings') }}" 
                               class="{{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                                <x-mary-icon name="o-cog" class="size-5" />
                                {{ __('setting.title') }}
                            </a>
                        </li>
                    </ul>
                @endif

                @if(auth()->user()->hasRole('student'))
                    <ul class="menu menu-sm gap-1 mt-4">
                        <li class="menu-title text-xs opacity-50">{{ trans('student.title') ?: 'Student Portal' }}</li>
                        <li>
                            <a href="{{ route('student.dashboard') }}" 
                               class="{{ request()->routeIs('student.dashboard*') ? 'active' : '' }}">
                                <x-mary-icon name="o-home" class="size-5" />
                                {{ __('dashboard.title') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('student.journals') }}" 
                               class="{{ request()->routeIs('student.journals*') ? 'active' : '' }}">
                                <x-mary-icon name="o-book-open" class="size-5" />
                                {{ trans('journal.title') ?: 'Journals' }}
                            </a>
                        </li>
                    </ul>
                @endif

                @if(auth()->user()->hasRole('teacher|mentor'))
                    <ul class="menu menu-sm gap-1 mt-4">
                        <li class="menu-title text-xs opacity-50">{{ trans('supervision.title') ?: 'Supervision' }}</li>
                        <li>
                            <a href="{{ route('supervision.logs') }}" 
                               class="{{ request()->routeIs('supervision.logs*') ? 'active' : '' }}">
                                <x-mary-icon name="o-clipboard-check" class="size-5" />
                                {{ trans('supervision.logs') ?: 'Guidance Logs' }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('supervision.monitoring') }}" 
                               class="{{ request()->routeIs('supervision.monitoring*') ? 'active' : '' }}">
                                <x-mary-icon name="o-map-pin" class="size-5" />
                                {{ trans('supervision.monitoring') ?: 'Monitoring' }}
                            </a>
                        </li>
                    </ul>
                @endif
            @else
                <div class="p-4 text-center">
                    <p class="text-xs opacity-40 font-bold uppercase tracking-widest">Setup in progress</p>
                </div>
            @endauth
        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-base-200">
            <livewire:layout.app-signature />
        </div>
    </aside>
</div>
