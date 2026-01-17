<div>
    <x-ui::header
        title="{{ __('My Profile') }}"
        subtitle="{{ __('Manage your personal information and security.') }}"
        separator
        progress-indicator
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <x-ui::card shadow class="text-center">
                <x-ui::file wire:model="avatar" accept="image/*" crop-after-change>
                    <img
                        src="{{ auth()->user()->avatar_url ?? '/avatar.png' }}"
                        class="h-40 w-40 mx-auto rounded-full object-cover border-4 border-base-200"
                    />
                </x-ui::file>

                <div class="mt-4">
                    <h2 class="text-xl font-bold">{{ $name }}</h2>
                    <div class="flex justify-center gap-1 mt-1">
                        @foreach (auth()->user()->roles as $role)
                            <livewire:permission::role-badge :role="$role" size="xs" />
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 text-left space-y-2 text-sm opacity-70">
                    <div class="flex items-center gap-2">
                        <x-ui::icon name="o-envelope" />
                        {{ $email }}
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui::icon name="o-at-symbol" />
                        {{ $username }}
                    </div>
                </div>
            </x-ui::card>
        </div>

        <div class="lg:col-span-2">
            <x-ui::tabs wire:model="activeTab" class="bg-base-100 rounded-lg shadow">
                <x-ui::tab name="info-tab" label="{{ __('Basic Info') }}" icon="o-user">
                    <x-ui::form wire:submit="saveInfo">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-ui::input
                                label="{{ __('Full Name') }}"
                                wire:model="name"
                                required
                            />
                            <x-ui::input
                                label="{{ __('Username') }}"
                                wire:model="username"
                                required
                            />
                            <x-ui::input
                                label="{{ __('Email') }}"
                                wire:model="email"
                                type="email"
                                required
                            />
                            <x-ui::input label="{{ __('Phone') }}" wire:model="phone" />
                        </div>
                        <x-ui::textarea
                            label="{{ __('Address') }}"
                            wire:model="address"
                            rows="3"
                        />
                        <x-ui::textarea
                            label="{{ __('Bio') }}"
                            wire:model="bio"
                            rows="2"
                            hint="{{ __('Tell us a bit about yourself.') }}"
                        />

                        <x-slot:actions>
                            <x-ui::button
                                label="{{ __('Update Info') }}"
                                type="submit"
                                icon="o-check"
                                class="btn-primary"
                                spinner="saveInfo"
                            />
                        </x-slot>
                    </x-ui::form>
                </x-ui::tab>

                @if (auth()->user()->hasAnyRole(['teacher', 'student']))
                    <x-ui::tab
                        name="special-tab"
                        label="{{ __('Special Fields') }}"
                        icon="o-academic-cap"
                    >
                        <x-ui::form wire:submit="saveSpecialFields">
                            @if (auth()->user()->hasRole('teacher'))
                                <x-ui::input
                                    label="{{ __('NIP (Employee ID)') }}"
                                    wire:model="nip"
                                    required
                                    hint="{{ __('Mandatory for Teacher role.') }}"
                                />
                            @endif

                            @if (auth()->user()->hasRole('student'))
                                <x-ui::input
                                    label="{{ __('NISN (National Student ID)') }}"
                                    wire:model="nisn"
                                    required
                                    hint="{{ __('Mandatory for Student role.') }}"
                                />
                            @endif

                            <x-slot:actions>
                                <x-ui::button
                                    label="{{ __('Save Fields') }}"
                                    type="submit"
                                    icon="o-check"
                                    class="btn-primary"
                                    spinner="saveSpecialFields"
                                />
                            </x-slot>
                        </x-ui::form>
                    </x-ui::tab>
                @endif

                <x-ui::tab name="security-tab" label="{{ __('Security') }}" icon="o-key">
                    <x-ui::form wire:submit="savePassword">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-ui::input
                                label="{{ __('New Password') }}"
                                wire:model="password"
                                type="password"
                                required
                            />
                            <x-ui::input
                                label="{{ __('Confirm Password') }}"
                                wire:model="password_confirmation"
                                type="password"
                                required
                            />
                        </div>

                        <x-slot:actions>
                            <x-ui::button
                                label="{{ __('Update Password') }}"
                                type="submit"
                                icon="o-lock-closed"
                                class="btn-error"
                                spinner="savePassword"
                            />
                        </x-slot>
                    </x-ui::form>
                </x-ui::tab>
            </x-ui::tabs>
        </div>
    </div>
</div>
