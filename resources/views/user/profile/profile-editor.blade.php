<div>
    <div class="mb-6 flex items-center gap-4">
        <div class="bg-primary/10 text-primary flex size-12 shrink-0 items-center justify-center rounded-xl">
            <x-ts-icon name="user-circle" class="size-6" />
        </div>
        <div>
            <h2 class="text-xl font-bold">{{ __('profile.title') }}</h2>
            <p class="text-base-content/50 mt-0.5 text-sm">{{ __('profile.subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <x-ts-icon name="pencil-square" class="text-primary size-5" />
                        <span class="font-semibold">{{ __('profile.information') }}</span>
                    </div>
                </x-slot:header>
                <div class="text-base-content/60 -mt-2 mb-3 text-sm">
                    <span class="text-base-content/50 text-xs">{{ __('profile.information_desc') }}</span>
                </div>

                <form wire:submit="save">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @if (! $canChangeName)
                            <x-ui::components.display-field
                                :label="__('setup.wizard.full_name')"
                                :value="$user->name"
                                icon="shield-check"
                            />
                        @else
                            <x-ts-input
                                :label="__('setup.wizard.full_name')"
                                wire:model="profileForm.name"
                                :placeholder="__('profile.name_placeholder')"
                                icon="user"
                            />
                        @endif

                        @if (! $canChangeUsername)
                            <x-ui::components.display-field
                                :label="__('profile.sidebar.username')"
                                :value="$user->username"
                                icon="at-symbol"
                            />
                        @else
                            <x-ts-input
                                :label="__('profile.sidebar.username')"
                                wire:model="profileForm.username"
                                :placeholder="__('profile.sidebar.username')"
                                icon="at-symbol"
                            />
                        @endif
                        <x-ts-input
                            :label="__('profile.sidebar.email')"
                            wire:model="profileForm.email"
                            type="email"
                            :placeholder="__('profile.email_placeholder')"
                            icon="envelope"
                        />
                        <x-ts-input
                            :label="__('profile.sidebar.phone')"
                            wire:model="profileForm.phone"
                            :placeholder="__('profile.phone_placeholder')"
                            icon="phone"
                        />
                        <x-ts-textarea
                            :label="__('setup.wizard.school_address')"
                            wire:model="profileForm.address"
                            rows="2"
                            class="md:col-span-2"
                            :placeholder="__('profile.address_placeholder')"
                        />
                        <x-ts-textarea
                            :label="__('profile.bio')"
                            wire:model="profileForm.bio"
                            rows="3"
                            class="md:col-span-2"
                            :placeholder="__('profile.bio_placeholder')"
                        />
                    </div>

                    @if ($isStaff)
                        <div class="border-base-content/10 mt-6 border-t pt-6">
                            <div class="mb-1 flex items-center gap-2">
                                <x-ts-icon name="briefcase" class="text-primary size-5" />
                                <h3 class="font-semibold">{{ __('profile.staff_information') }}</h3>
                            </div>
                            <p class="text-base-content/50 mb-4 text-xs">{{ __('profile.staff_information_desc') }}</p>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-ts-select.native
                                    :label="__('profile.employment_status')"
                                    wire:model="profileForm.employment_status"
                                    :options="ts_options(\App\Modules\User\Enums\EmploymentStatus::options(), __('profile.select_option'))"
                                    icon="briefcase"
                                />
                                <x-ts-input
                                    :label="__('profile.job_title')"
                                    wire:model="profileForm.job_title"
                                    :placeholder="__('profile.job_title_placeholder')"
                                    icon="identification"
                                />
                                <x-ts-input
                                    :label="$this->getIdNumberLabel()"
                                    wire:model="profileForm.id_number"
                                    :placeholder="__('profile.id_number_placeholder')"
                                    icon="document-text"
                                />
                                <x-ts-input
                                    :label="__('profile.competence_field')"
                                    wire:model="profileForm.competence_field"
                                    class="md:col-span-2"
                                    :placeholder="__('profile.competence_field_placeholder')"
                                    icon="academic-cap"
                                />
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex justify-end gap-2">
                        <x-ts-button
                            :text="__('profile.save_profile')"
                            type="submit"
                            color="primary"
                            icon="check"
                            loading="save"
                        />
                    </div>
                </form>

                {{-- Password --}}
                <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                    <x-slot:header>
                        <div class="flex items-center gap-2">
                            <x-ts-icon name="lock-closed" class="text-primary size-5" />
                            <span class="font-semibold">{{ __('profile.password') }}</span>
                        </div>
                    </x-slot:header>
                    <div class="text-base-content/60 -mt-2 mb-3 text-sm">
                        <span class="text-base-content/50 text-xs">{{ __('profile.password_desc') }}</span>
                    </div>

                    <form wire:submit="updatePassword">
                        <div class="space-y-5">
                            <x-ts-password
                                :label="__('profile.current_password')"
                                wire:model="passwordForm.current_password"
                                :placeholder="__('profile.current_password_placeholder')"
                                icon="lock-closed"
                                right
                            />
                            <x-ts-password
                                :label="__('profile.new_password')"
                                wire:model="passwordForm.password"
                                :placeholder="__('profile.new_password_placeholder')"
                                icon="key"
                                right
                            />
                            <x-ts-password
                                :label="__('profile.confirm_password')"
                                wire:model="passwordForm.password_confirmation"
                                :placeholder="__('profile.confirm_password_placeholder')"
                                icon="key"
                                right
                            />
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <x-ts-button
                                aria-label="{{ __('common.actions.remove') }}"
                                :text="__('profile.update_password')"
                                type="submit"
                                color="primary"
                                icon="key"
                                loading="updatePassword"
                            />
                        </div>
                    </form>
            </x-ts-card>
                </x-ts-card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <div class="flex flex-col items-center py-6">
                    <div class="group relative mb-3">
                        <div class="relative cursor-pointer" @click="document.getElementById('avatar-upload').click()">
                            <input
                                id="avatar-upload"
                                type="file"
                                wire:model="avatar"
                                accept="image/png,image/jpeg,image/webp"
                                class="hidden"
                            />
                            <div class="bg-base-200 border-base-content/10 ring-primary/10 group-hover:ring-primary/30 flex size-24 items-center justify-center overflow-hidden rounded-full border-2 ring-2 transition-all">
                                @if ($this->avatarPreviewUrl() ?? $user->getFirstMediaUrl('avatar', 'thumb'))
                                    <img
                                        src="{{ $this->avatarPreviewUrl() ?? $user->getFirstMediaUrl('avatar', 'thumb') }}"
                                        alt="{{ __('common.avatar') }}"
                                        class="size-full object-cover"
                                    />
                                @else
                                    <span class="text-base-content/60 text-2xl font-bold">{{ $user->initials() }}</span>
                                @endif
                            </div>
                            <div class="bg-base-content/60 absolute inset-0 flex items-center justify-center rounded-full opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                <x-ts-icon name="camera" class="text-base-100 size-6" />
                            </div>
                        </div>
                        @if ($user->getFirstMediaUrl('avatar'))
                            <button
                                type="button"
                                wire:click="$set('showConfirm', true)"
                                class="bg-error text-error-content absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full opacity-0 transition-transform group-hover:opacity-100 hover:scale-110"
                            >
                                <x-ts-icon name="x-mark" class="size-3" />
                            </button>
                        @endif
                    </div>

                    @include('ui.components.confirm', [
                        'message' => __('profile.avatar_remove_confirm'),
                        'confirmText' => __('common.actions.remove'),
                    ])
                    <h3 class="mt-2 text-lg font-semibold">{{ $user->name }}</h3>
                    <p class="text-base-content/50 mt-0.5 text-xs">{{ '@'.$user->username }}</p>
                    <div class="mt-3 flex flex-wrap justify-center gap-1">
                        @foreach ($user->roles as $role)
                            <x-ts-badge :text="$role->name" class="badge-primary badge-sm font-medium" />
                        @endforeach
                    </div>
                </div>
                <div class="border-base-content/10 space-y-3 border-t px-5 pt-4 pb-2 text-sm">
                    <div class="text-base-content/70 flex items-center gap-3">
                        <x-ts-icon name="envelope" class="text-base-content/40 size-4 shrink-0" />
                        <span class="truncate">{{ $user->email }}</span>
                    </div>
                    @if ($user->profile?->phone)
                        <div class="text-base-content/70 flex items-center gap-3">
                            <x-ts-icon name="phone" class="text-base-content/40 size-4 shrink-0" />
                            <span>{{ $user->profile->phone }}</span>
                        </div>
                    @endif
                    <div class="text-base-content/40 flex items-center gap-3 text-xs">
                        <x-ts-icon name="calendar" class="size-4 shrink-0" />
                        <span>{{ __('profile.sidebar.joined', ['date' => $user->created_at->format('M Y')]) }}</span>
                    </div>
                </div>

                <a href="{{ route('profile.recovery') }}" wire:navigate>
                    <x-ts-card
                        shadowless
                        class="bg-base-100 border-base-content/10 hover:bg-base-200/50 cursor-pointer border transition-colors"
                    >
                        <div class="flex items-center gap-3 px-1">
                            <div class="bg-warning/10 text-warning flex size-10 shrink-0 items-center justify-center rounded-lg">
                                <x-ts-icon name="key" class="size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">{{ __('profile.recovery.title') }}</p>
                                <p class="text-base-content/50 truncate text-xs">
                                    {{ __('profile.recovery.subtitle') }}
                                </p>
                            </div>
                            <x-ts-icon name="chevron-right" class="text-base-content/20 size-4 shrink-0" />
                        </div>
                    </x-ts-card>
                </a>
            </x-ts-card>
        </div>
    </div>

    @include('user.profile.components.profile-guide')
</div>
