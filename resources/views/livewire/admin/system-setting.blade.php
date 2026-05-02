<div class="p-8">
    <x-mary-header title="{{ __('setting.title') }}" subtitle="{{ __('setting.subtitle') }}" separator progress-indicator />

    <x-mary-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                {{-- General & Branding --}}
                <x-mary-card title="{{ __('setting.groups.general') }}" shadow separator class="card-enterprise">
                    <div class="grid grid-cols-1 gap-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-mary-input label="{{ __('setting.fields.brand_name') }}" icon="o-identification" wire:model="brand_name" class="rounded-2xl" />
                            <x-mary-select
                                label="{{ __('setting.fields.default_locale') }}"
                                icon="o-language"
                                wire:model="default_locale"
                                :options="[
                                    ['id' => 'id', 'name' => 'Bahasa Indonesia'],
                                    ['id' => 'en', 'name' => 'English'],
                                ]"
                                class="rounded-2xl"
                            />
                            <x-mary-input
                                label="{{ __('setting.fields.active_academic_year') }}"
                                icon="o-calendar"
                                wire:model="active_academic_year"
                                placeholder="e.g. 2025/2026"
                                class="md:col-span-2 rounded-2xl"
                            />
                        </div>
                        <x-mary-input label="{{ __('setting.fields.site_title') }}" icon="o-globe-alt" wire:model="site_title" class="rounded-2xl" />
                    </div>
                </x-mary-card>

                {{-- Color Scheme --}}
                <x-mary-card title="{{ __('setting.groups.color_scheme') }}" shadow separator class="card-enterprise">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-mary-input 
                             label="{{ __('setting.fields.primary_color') }}" 
                             icon="o-swatch" 
                             type="color" 
                             wire:model="primary_color" 
                             class="rounded-2xl"
                         />
                         <x-mary-input 
                             label="{{ __('setting.fields.secondary_color') }}" 
                             icon="o-swatch" 
                             type="color" 
                             wire:model="secondary_color" 
                             class="rounded-2xl"
                         />
                         <x-mary-input 
                             label="{{ __('setting.fields.accent_color') }}" 
                             icon="o-swatch" 
                             type="color" 
                             wire:model="accent_color" 
                             class="rounded-2xl"
                         />
                    </div>
                </x-mary-card>

                {{-- Mail Services --}}
                <x-mary-card title="{{ __('setting.groups.mail') }}" shadow separator class="card-enterprise">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-input label="{{ __('setting.fields.mail_from_address') }}" icon="o-envelope" type="email" wire:model="mail_from_address" class="rounded-2xl" />
                        <x-mary-input label="{{ __('setting.fields.mail_from_name') }}" icon="o-user" wire:model="mail_from_name" class="rounded-2xl" />

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-base-200 pt-6 mt-2">
                            <x-mary-input label="{{ __('setting.fields.mail_host') }}" icon="o-server" wire:model="mail_host" class="md:col-span-2 rounded-2xl" />
                            <x-mary-input label="{{ __('setting.fields.mail_port') }}" icon="o-arrow-right-end-on-rectangle" wire:model="mail_port" class="rounded-2xl" />

                            <x-mary-select
                                label="{{ __('setting.fields.mail_encryption') }}"
                                icon="o-lock-closed"
                                wire:model="mail_encryption"
                                :options="[
                                    ['id' => 'tls', 'name' => 'TLS'],
                                    ['id' => 'ssl', 'name' => 'SSL'],
                                    ['id' => 'none', 'name' => 'None'],
                                ]"
                                class="rounded-2xl"
                            />
                            <x-mary-input label="{{ __('setting.fields.mail_username') }}" icon="o-user" wire:model="mail_username" class="rounded-2xl" />
                            <x-mary-input label="{{ __('setting.fields.mail_password') }}" icon="o-key" type="password" wire:model="mail_password" class="rounded-2xl" />
                        </div>
                        
                        <div class="md:col-span-2 flex justify-end mt-4">
                            <x-mary-button 
                                label="{{ __('setting.buttons.test_mail') ?? 'Test SMTP Connection' }}" 
                                icon="o-paper-airplane" 
                                class="btn-ghost btn-sm text-primary font-black uppercase tracking-widest" 
                                wire:click="testEmail" 
                                spinner="testEmail" 
                            />
                        </div>
                    </div>
                </x-mary-card>
            </div>

            <div class="lg:col-span-1 space-y-6">
                {{-- System Information (Read-only) --}}
                <x-mary-card title="{{ __('setting.groups.system') }}" shadow separator class="bg-base-200/50 rounded-3xl border-none">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-base-content/60 font-medium">{{ __('setting.fields.app_name') }}</span>
                            <span class="font-black text-base-content">{{ $app_name }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-base-content/60 font-medium">{{ __('setting.fields.app_version') }}</span>
                            <x-mary-badge value="{{ $app_version }}" class="badge-neutral font-black" />
                        </div>
                    </div>
                </x-mary-card>

                {{-- Visual Identity Assets --}}
                <x-mary-card title="{{ __('setting.groups.identity') }}" shadow separator class="card-enterprise">
                    <div class="space-y-8">
                        <x-mary-file
                            label="{{ __('setting.fields.brand_logo') }}"
                            wire:model="brand_logo"
                            accept="image/*"
                            :preview="$current_logo_url"
                            hint="{{ __('setting.hints.brand_logo') }}"
                            class="rounded-2xl"
                        />

                        <x-mary-file
                            label="{{ __('setting.fields.site_favicon') }}"
                            wire:model="site_favicon"
                            accept="image/*"
                            :preview="$current_favicon_url"
                            hint="{{ __('setting.hints.site_favicon') }}"
                            class="rounded-2xl"
                        />
                    </div>
                </x-mary-card>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="{{ __('setting.buttons.save') }}" type="submit" class="btn-primary px-8 rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-primary/20" icon="o-check" spinner="save" />
        </x-slot:actions>
    </x-mary-form>
</div>
