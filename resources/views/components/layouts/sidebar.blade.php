@props(['items' => []])
@php
$currentRoute = request()->route()?->getName() ?? '';
$brandName = brand('name');
$brandLogo = brand('logo');
@endphp

<div class="drawer-side z-[60]">
    <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
    
    <aside class="bg-base-100 min-h-screen w-[280px] border-r border-base-content/5 flex flex-col shadow-2xl lg:shadow-none">
        <!-- Logo -->
        <div class="h-20 px-6 border-b border-base-content/5 flex items-center shrink-0">
            <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-4 group w-full">
                <div class="size-10 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-primary/20 transition-transform group-hover:scale-110 duration-500 shrink-0">
                    <img 
                        src="{{ $brandLogo }}" 
                        class="size-6 object-contain brightness-0 invert" 
                        alt="{{ $brandName }}"
                    />
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-black text-lg truncate leading-none text-base-content group-hover:text-primary transition-colors">{{ $brandName }}</span>
                    <span class="text-[8px] uppercase tracking-[0.3em] font-black opacity-40 mt-1">Management</span>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-8 no-scrollbar">
            @auth
                @if(auth()->user()->hasRole('super_admin|admin'))
                    <div>
                        <h3 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-3">{{ trans('admin.title') ?: 'Administration' }}</h3>
                        <ul class="space-y-1">
                            <li>
                                <a wire:navigate href="{{ route('admin.school') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.school*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-academic-cap" class="size-5 shrink-0 {{ request()->routeIs('admin.school*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('school.title') }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('admin.departments') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.departments*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-rectangle-group" class="size-5 shrink-0 {{ request()->routeIs('admin.departments*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('department.title') }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('admin.internships') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.internships*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-briefcase" class="size-5 shrink-0 {{ request()->routeIs('admin.internships*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('internship.title') }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('admin.companies') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.companies*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-building-office" class="size-5 shrink-0 {{ request()->routeIs('admin.companies*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('company.title') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-3">{{ trans('user.student.title') ?: 'Users' }}</h3>
                        <ul class="space-y-1">
                            <li>
                                <a wire:navigate href="{{ route('admin.users.admins') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.users.admins*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-user-circle" class="size-5 shrink-0 {{ request()->routeIs('admin.users.admins*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('user.admin.title') }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('admin.users.students') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.users.students*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-user-group" class="size-5 shrink-0 {{ request()->routeIs('admin.users.students*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('user.student.title') }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('admin.users.teachers') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.users.teachers*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-academic-cap" class="size-5 shrink-0 {{ request()->routeIs('admin.users.teachers*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('user.teacher.title') }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('admin.users.mentors') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.users.mentors*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-user-plus" class="size-5 shrink-0 {{ request()->routeIs('admin.users.mentors*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('user.mentor.title') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-3">{{ trans('setting.groups.system') ?: 'System' }}</h3>
                        <ul class="space-y-1">
                            <li>
                                <a wire:navigate href="{{ route('admin.settings') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.settings*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-cog" class="size-5 shrink-0 {{ request()->routeIs('admin.settings*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('setting.title') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif

                @if(auth()->user()->hasRole('student'))
                    <div>
                        <h3 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-3">{{ trans('student.title') ?: 'Student Portal' }}</h3>
                        <ul class="space-y-1">
                            <li>
                                <a wire:navigate href="{{ route('student.dashboard') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('student.dashboard*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-home" class="size-5 shrink-0 {{ request()->routeIs('student.dashboard*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ __('dashboard.title') }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('student.journals') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('student.journals*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-book-open" class="size-5 shrink-0 {{ request()->routeIs('student.journals*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ trans('journal.title') ?: 'Journals' }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif

                @if(auth()->user()->hasRole('teacher|supervisor'))
                    <div>
                        <h3 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-3">{{ trans('supervision.title') ?: 'Supervision' }}</h3>
                        <ul class="space-y-1">
                            <li>
                                <a wire:navigate href="{{ route('supervision.logs') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->routeIs('supervision.logs*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-clipboard-check" class="size-5 shrink-0 {{ request()->routeIs('supervision.logs*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ trans('supervision.logs') ?: 'Guidance Logs' }}</span>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate href="{{ route('supervision.monitoring') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-300 {{ request()->requestIs('supervision.monitoring*') ? 'bg-primary/10 text-primary font-black' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content font-bold' }}">
                                    <x-mary-icon name="o-map-pin" class="size-5 shrink-0 {{ request()->routeIs('supervision.monitoring*') ? 'text-primary' : 'opacity-50' }}" />
                                    <span class="text-sm">{{ trans('supervision.monitoring') ?: 'Monitoring' }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif
            @else
                <div class="p-6 text-center bg-base-200/50 rounded-3xl border border-base-content/5">
                    <div class="size-12 rounded-full bg-base-content/5 flex items-center justify-center mx-auto mb-3">
                        <x-mary-icon name="o-cog" class="size-6 opacity-30 animate-spin-slow" />
                    </div>
                    <p class="text-[10px] opacity-40 font-black uppercase tracking-widest leading-relaxed">Setup in progress</p>
                </div>
            @endauth
        </nav>

        <!-- Footer -->
        <div class="p-6 border-t border-base-content/5 bg-base-200/30">
            <livewire:core.app-signature />
        </div>
    </aside>
</div>
