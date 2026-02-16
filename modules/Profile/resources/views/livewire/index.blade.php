<div>
    <x-ui::header
        :title="__('profile::ui.title')"
        :subtitle="__('profile::ui.subtitle')"
        separator
        progress-indicator
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <x-ui::card shadow class="text-center">
                <x-ui::file wire:model="avatar" accept="image/*" crop-after-change>
                    <x-ui::avatar 
                        :image="auth()->user()->avatar_url" 
                        :title="$name" 
                        size="w-40" 
                        class="mx-auto rounded-full border-4 border-base-200" 
                    />
                </x-ui::file>

                <div class="mt-4">
                    <h2 class="text-xl font-bold">{{ $name }}</h2>
                    <div class="flex justify-center gap-1 mt-1">
                        @foreach (auth()->user()->roles as $role)
                            <livewire:permission::role-badge :role="$role" size="xs" wire:key="role-{{ $role->id }}" />
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 text-left space-y-2 text-sm opacity-70">
                    <div class="flex items-center gap-2">
                        <x-ui::icon name="tabler.mail" />
                        <span>{{ $email }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui::icon name="tabler.at" />
                        <span>{{ $username }}</span>
                    </div>
                </div>
            </x-ui::card>
        </div>

        <div class="lg:col-span-2">
            <x-ui::tabs wire:model="activeTab" class="bg-base-100 rounded-lg shadow">
                <x-ui::tab name="info-tab" :label="__('profile::ui.tabs.basic_info')" icon="tabler.user">
                    <x-ui::form wire:submit="saveInfo">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-ui::input
                                :label="__('profile::ui.form.full_name')"
                                wire:model="name"
                                required
                            />
                            <x-ui::input
                                :label="__('profile::ui.form.username')"
                                wire:model="username"
                                required
                            />
                            <x-ui::input
                                :label="__('profile::ui.form.email')"
                                wire:model="email"
                                type="email"
                                required
                            />
                            <x-ui::input :label="__('profile::ui.form.phone')" wire:model="phone" />
                            <x-ui::select
                                :label="__('profile::ui.form.gender')"
                                wire:model="gender"
                                :options="[
                                    ['id' => 'male', 'name' => __('profile::enums.gender.male')],
                                    ['id' => 'female', 'name' => __('profile::enums.gender.female')]
                                ]"
                                placeholder="---"
                            />
                            <x-ui::select
                                :label="__('profile::ui.form.blood_type')"
                                wire:model="blood_type"
                                :options="[
                                    ['id' => 'A', 'name' => 'A'],
                                    ['id' => 'B', 'name' => 'B'],
                                    ['id' => 'AB', 'name' => 'AB'],
                                    ['id' => 'O', 'name' => 'O']
                                ]"
                                placeholder="---"
                            />
                        </div>

                        <x-ui::textarea
                            :label="__('profile::ui.form.address')"
                            wire:model="address"
                            rows="2"
                        />

                        <div class="divider text-xs opacity-50">{{ __('profile::ui.form.emergency_contact') }}</div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-ui::input
                                :label="__('profile::ui.form.emergency_contact_name')"
                                wire:model="emergency_contact_name"
                            />
                            <x-ui::input
                                :label="__('profile::ui.form.emergency_contact_phone')"
                                wire:model="emergency_contact_phone"
                            />
                        </div>
                        <x-ui::textarea
                            :label="__('profile::ui.form.emergency_contact_address')"
                            wire:model="emergency_contact_address"
                            rows="2"
                        />
                        <x-ui::textarea
                            :label="__('profile::ui.form.bio')"
                            wire:model="bio"
                            rows="2"
                            :hint="__('profile::ui.form.bio_hint')"
                        />

                        <x-slot:actions>
                            <x-ui::button
                                :label="__('profile::ui.form.update_info')"
                                type="submit"
                                icon="tabler.check"
                                priority="primary"
                                spinner="saveInfo"
                            />
                        </x-slot>
                    </x-ui::form>
                </x-ui::tab>

                @if (auth()->user()->hasAnyRole(['teacher', 'student']))
                    <x-ui::tab
                        name="special-tab"
                        :label="__('profile::ui.tabs.special_fields')"
                        icon="tabler.school"
                    >
                        <x-ui::form wire:submit="saveSpecialFields">
                            @if (auth()->user()->hasRole('teacher'))
                                <x-ui::input
                                    :label="__('profile::ui.form.nip')"
                                    wire:model="nip"
                                    required
                                    :hint="__('profile::ui.form.nip_hint')"
                                />
                            @endif

                            @if (auth()->user()->hasRole('student'))
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <x-ui::input
                                        :label="__('profile::ui.form.national_identifier')"
                                        wire:model="national_identifier"
                                        required
                                        :hint="__('profile::ui.form.national_identifier_hint')"
                                    />
                                    <x-ui::input
                                        :label="__('profile::ui.form.registration_number')"
                                        wire:model="registration_number"
                                        :hint="__('profile::ui.form.registration_number_hint')"
                                    />
                                    <x-ui::input
                                        :label="__('profile::ui.form.class_name')"
                                        wire:model="class_name"
                                        :hint="__('profile::ui.form.class_name_hint')"
                                    />
                                    
                                    <x-ui::file
                                        :label="__('profile::ui.form.passport_photo')"
                                        wire:model="passport_photo"
                                        accept="image/*"
                                        :hint="__('profile::ui.form.passport_photo_hint')"
                                    >
                                        @php
                                            $student = auth()->user()->profile->profileable;
                                            $passportUrl = $student?->getFirstMediaUrl(\Modules\Student\Models\Student::COLLECTION_PASSPORT_PHOTO);
                                        @endphp
                                        @if($passportUrl)
                                            <img src="{{ $passportUrl }}" class="w-24 h-32 object-cover rounded mt-2 border" />
                                        @endif
                                    </x-ui::file>
                                </div>
                            @endif

                            <x-slot:actions>
                                <x-ui::button
                                    :label="__('profile::ui.form.save_fields')"
                                    type="submit"
                                    icon="tabler.check"
                                    priority="primary"
                                    spinner="saveSpecialFields"
                                />
                            </x-slot>
                        </x-ui::form>
                    </x-ui::tab>
                @endif

                <x-ui::tab name="security-tab" :label="__('profile::ui.tabs.security')" icon="tabler.key">
                    <x-ui::form wire:submit="savePassword">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-ui::input
                                :label="__('profile::ui.form.new_password')"
                                wire:model="password"
                                type="password"
                                required
                            />
                            <x-ui::input
                                :label="__('profile::ui.form.confirm_password')"
                                wire:model="password_confirmation"
                                type="password"
                                required
                            />
                        </div>

                        <x-slot:actions>
                            <x-ui::button
                                :label="__('profile::ui.form.update_password')"
                                type="submit"
                                icon="tabler.lock"
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
