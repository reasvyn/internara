<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('profile.title') }}</h2>
        <p class="text-sm text-base-content/50 mt-1">{{ __('profile.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main form --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Profile Information --}}
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title><span class="font-semibold">{{ __('profile.information') }}</span></x-slot:title>
                <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('profile.information_desc') }}</span></x-slot:subtitle>

                <x-mary-form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="{{ __('profile.sidebar.username') }}" :value="$user->username" readonly />
                        <x-mary-input label="{{ __('profile.sidebar.email') }}" wire:model="data.email" type="email" />
                        <x-mary-input label="{{ __('setup.wizard.full_name') }}" wire:model="data.name" />
                        <x-mary-input label="{{ __('profile.sidebar.phone') }}" wire:model="data.phone" />
                        <x-mary-textarea label="{{ __('setup.wizard.school_address') }}" wire:model="data.address" rows="2" class="md:col-span-2" />
                        <x-mary-textarea label="Bio" wire:model="data.bio" rows="3" class="md:col-span-2" />
                    </div>

                    <x-slot:actions>
                        <x-mary-button :label="__('profile.save_profile')" type="submit" class="btn-primary" icon="o-check" spinner="save" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>

            {{-- Password --}}
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title><span class="font-semibold">{{ __('profile.password') }}</span></x-slot:title>
                <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('profile.password_desc') }}</span></x-slot:subtitle>

                <x-mary-form wire:submit="updatePassword">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-password :label="__('profile.current_password')" wire:model="passwordData.current_password" />
                        <div></div>
                        <x-mary-password :label="__('profile.new_password')" wire:model="passwordData.password" />
                        <x-mary-password :label="__('profile.confirm_password')" wire:model="passwordData.password_confirmation" />
                    </div>

                    <x-slot:actions>
                        <x-mary-button :label="__('profile.update_password')" type="submit" class="btn-primary" icon="o-key" spinner="updatePassword" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <div class="flex flex-col items-center py-4">
                    <div class="relative mb-3 group">
                        <div class="cursor-pointer relative" onclick="document.getElementById('avatar-upload').click()">
                            <input id="avatar-upload" type="file" wire:model="avatar" accept="image/png,image/jpeg,image/webp" class="hidden" />
                            <x-mary-avatar
                                :image="$avatar?->temporaryUrl() ?? $user->getFirstMediaUrl('avatar', 'thumb') ?: null"
                                placeholder="{{ $user->initials() }}"
                                class="!w-20 !h-20"
                            />
                            <div class="absolute inset-0 flex items-center justify-center rounded-full bg-base-content/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <x-mary-icon name="o-camera" class="size-6 text-base-100" />
                            </div>
                        </div>
                        @if($avatar || $user->getFirstMediaUrl('avatar'))
                            <button type="button" wire:click="$set('avatar', null)" class="absolute -top-1 -right-1 size-5 bg-error text-error-content rounded-full flex items-center justify-center hover:scale-110 transition-transform">
                                <x-mary-icon name="o-x-mark" class="size-3" />
                            </button>
                        @endif
                    </div>
                    <h3 class="font-semibold text-lg mt-3">{{ $user->name }}</h3>
                    <p class="text-xs text-base-content/50">{{ '@'.$user->username }}</p>
                    <div class="flex flex-wrap justify-center gap-1 mt-3">
                        @foreach($user->roles as $role)
                            <x-mary-badge :value="$role->name" class="badge-primary badge-sm" />
                        @endforeach
                    </div>
                </div>
                <div class="border-t border-base-content/10 pt-4 pb-2 px-4 space-y-3 text-sm">
                    <div class="flex items-center gap-3 text-base-content/60">
                        <x-mary-icon name="o-envelope" class="size-4 shrink-0" />
                        <span class="truncate">{{ $user->email }}</span>
                    </div>
                    @if($user->profile?->phone)
                        <div class="flex items-center gap-3 text-base-content/60">
                            <x-mary-icon name="o-phone" class="size-4 shrink-0" />
                            <span>{{ $user->profile->phone }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-3 text-base-content/40 text-xs">
                        <x-mary-icon name="o-calendar" class="size-4 shrink-0" />
                        <span>{{ __('profile.sidebar.joined', ['date' => $user->created_at->format('M Y')]) }}</span>
                    </div>
                </div>
            </x-mary-card>

            <x-mary-card class="bg-warning/5 border border-warning/20">
                <div class="flex items-start gap-3">
                    <x-mary-icon name="o-shield-exclamation" class="size-5 text-warning shrink-0 mt-0.5" />
                    <div>
                        <h4 class="text-sm font-semibold text-warning mb-1">{{ __('profile.password') }}</h4>
                        <p class="text-xs text-warning/70">{{ __('profile.password_desc') }}</p>
                    </div>
                </div>
            </x-mary-card>

            <a href="{{ route('profile.recovery') }}" wire:navigate>
                <x-mary-card class="bg-base-100 border border-base-content/10 hover:bg-base-200/50 transition-colors cursor-pointer">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-key" class="size-5 text-base-content/40 shrink-0" />
                        <div>
                            <p class="text-sm font-medium">{{ __('profile.recovery.title') }}</p>
                            <p class="text-xs text-base-content/50">{{ __('profile.recovery.subtitle') }}</p>
                        </div>
                        <x-mary-icon name="o-chevron-right" class="size-4 text-base-content/20 ml-auto shrink-0" />
                    </div>
                </x-mary-card>
            </a>
        </div>
    </div>
</div>
