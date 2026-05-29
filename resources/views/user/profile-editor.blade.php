<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('profile.title') }}</h2>
        <p class="text-sm text-base-content/50 mt-1">{{ __('profile.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title><span class="font-semibold">{{ __('profile.information') }}</span></x-slot:title>
                <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('profile.information_desc') }}</span></x-slot:subtitle>

                <x-mary-form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if(!$canChangeName)
                            <x-shared::ui.display-field
                                :label="__('setup.wizard.full_name')"
                                :value="$user->name"
                                icon="o-shield-check"
                            />
                            <x-shared::ui.display-field
                                :label="__('profile.sidebar.username')"
                                :value="$user->username"
                                icon="o-at-symbol"
                            />
                        @else
                            <x-mary-input :label="__('setup.wizard.full_name')" wire:model="profileForm.name" :placeholder="__('profile.name_placeholder')" icon="o-user" />
                        @endif
                        <x-mary-input :label="__('profile.sidebar.email')" wire:model="profileForm.email" type="email" :placeholder="__('profile.email_placeholder')" icon="o-envelope" />
                        <x-mary-input :label="__('profile.sidebar.phone')" wire:model="profileForm.phone" :placeholder="__('profile.phone_placeholder')" icon="o-phone" />
                        <x-mary-textarea :label="__('setup.wizard.school_address')" wire:model="profileForm.address" rows="2" class="md:col-span-2" :placeholder="__('profile.address_placeholder')" icon="o-map-pin" />
                        <x-mary-textarea :label="__('profile.bio')" wire:model="profileForm.bio" rows="3" class="md:col-span-2" :placeholder="__('profile.bio_placeholder')" icon="o-document-text" />
                    </div>

                    @if($isStaff)
                        <hr class="my-6 border-base-content/10" />

                        <h3 class="font-semibold mb-4">{{ __('profile.staff_information') }}</h3>
                        <p class="text-xs text-base-content/50 mb-4">{{ __('profile.staff_information_desc') }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-mary-select :label="__('profile.employment_status')" wire:model="profileForm.employment_status" :placeholder="__('profile.select_option')" :options="\App\Domain\User\Enums\EmploymentStatus::options()" icon="o-briefcase" />
                            <x-mary-input :label="__('profile.position')" wire:model="profileForm.position" :placeholder="__('profile.position_placeholder')" icon="o-identification" />
                            <x-mary-input :label="__('profile.nip')" wire:model="profileForm.nip" :placeholder="__('profile.nip_placeholder')" icon="o-document-text" />
                            <x-mary-input :label="__('profile.nuptk')" wire:model="profileForm.nuptk" :placeholder="__('profile.nuptk_placeholder')" icon="o-document-text" />
                            <x-mary-input :label="__('profile.competence_field')" wire:model="profileForm.competence_field" class="md:col-span-2" :placeholder="__('profile.competence_field_placeholder')" icon="o-academic-cap" />
                        </div>
                    @endif

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
                    <div class="space-y-5">
                        <x-mary-password :label="__('profile.current_password')" wire:model="passwordForm.current_password" :placeholder="__('profile.current_password_placeholder')" icon="o-lock-closed" right />
                        <x-mary-password :label="__('profile.new_password')" wire:model="passwordForm.password" :placeholder="__('profile.new_password_placeholder')" icon="o-key" right />
                        <x-mary-password :label="__('profile.confirm_password')" wire:model="passwordForm.password_confirmation" :placeholder="__('profile.confirm_password_placeholder')" icon="o-key" right />
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
                            <div class="size-20 rounded-full bg-base-200 flex items-center justify-center overflow-hidden border-2 border-base-content/10">
                                @if($this->avatarPreviewUrl() ?? $user->getFirstMediaUrl('avatar', 'thumb'))
                                    <img src="{{ $this->avatarPreviewUrl() ?? $user->getFirstMediaUrl('avatar', 'thumb') }}" alt="Avatar" class="size-full object-cover" />
                                @else
                                    <span class="text-lg font-medium text-base-content/60">{{ $user->initials() }}</span>
                                @endif
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center rounded-full bg-base-content/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <x-mary-icon name="o-camera" class="size-6 text-base-100" />
                            </div>
                        </div>
                        @if($user->getFirstMediaUrl('avatar'))
                            <button type="button"
                                wire:click="$set('showConfirm', true)"
                                class="absolute -top-1 -right-1 size-5 bg-error text-error-content rounded-full flex items-center justify-center hover:scale-110 transition-transform opacity-0 group-hover:opacity-100">
                                <x-mary-icon name="o-x-mark" class="size-3" />
                            </button>
                        @endif
                    </div>

                    @include('shared.ui.confirm', [
                        'message' => __('profile.avatar_remove_confirm'),
                        'confirmText' => __('common.actions.remove'),
                    ])
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
