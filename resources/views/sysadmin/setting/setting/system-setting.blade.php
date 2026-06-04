<div class="py-4">
    <div class="mb-6 flex items-center gap-4">
        <div class="size-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <x-mary-icon name="o-cog-6-tooth" class="size-6" />
        </div>
        <div>
            <h2 class="text-xl font-bold">{{ __('setting.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-0.5">{{ __('setting.subtitle') }}</p>
        </div>
    </div>

    <x-mary-form wire:submit="save" id="settings-form">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- General --}}
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-cog" class="size-4 text-primary" />
                            <span class="font-semibold">{{ __('setting.groups.general') }}</span>
                        </div>
                    </x-slot:title>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="{{ __('setting.fields.brand_name') }}" wire:model="generalForm.brand_name" icon="o-building-library" />
                        <x-mary-select
                            label="{{ __('setting.fields.default_locale') }}"
                            wire:model="generalForm.default_locale"
                            :options="[
                                ['id' => 'id', 'name' => 'Bahasa Indonesia'],
                                ['id' => 'en', 'name' => 'English'],
                            ]"
                        />
                        <x-mary-input
                            label="{{ __('setting.fields.site_title') }}"
                            wire:model="generalForm.site_title"
                            class="md:col-span-2"
                            icon="o-globe-alt"
                        />
                        <x-mary-select
                            label="{{ __('setting.fields.active_academic_year') }}"
                            wire:model="generalForm.active_academic_year"
                            :options="$this->academicYearOptions"
                        />
                    </div>
                </x-mary-card>

                {{-- Color Scheme --}}
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-swatch" class="size-4 text-primary" />
                            <span class="font-semibold">{{ __('setting.groups.color_scheme') }}</span>
                        </div>
                    </x-slot:title>
                    <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('setting.hints.color_scheme') }}</span></x-slot:subtitle>

                    {{-- Presets --}}
                    <div class="mb-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.presets_title') }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($presets as $key => $preset)
                                <button type="button"
                                    wire:click="applyPreset('{{ $key }}')"
                                    @class([
                                        'relative flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200 cursor-pointer hover:scale-105',
                                        'border-primary shadow-md shadow-primary/10' => $brandingForm->selected_preset === $key,
                                        'border-base-content/10 hover:border-base-content/30' => $brandingForm->selected_preset !== $key,
                                    ])>
                                    <div class="flex -space-x-1.5">
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['primary'] }}"></span>
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['secondary'] }}"></span>
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['accent'] }}"></span>
                                        <span class="size-5 rounded-full ring-2 ring-base-100" style="background: {{ $preset['colors']['base'] }}"></span>
                                    </div>
                                    <span class="text-xs font-medium whitespace-nowrap">{{ $preset['label'] }}</span>
                                    @if($brandingForm->selected_preset === $key)
                                        <span class="absolute -top-1.5 -right-1.5 size-4 bg-primary text-primary-content rounded-full flex items-center justify-center">
                                            <x-mary-icon name="o-check" class="size-2.5" />
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.custom_title') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-mary-input label="{{ __('setting.fields.primary_color') }}" type="color" wire:model.live="brandingForm.primary_color" wire:change="$set('brandingForm.selected_preset', null)" />
                        <x-mary-input label="{{ __('setting.fields.secondary_color') }}" type="color" wire:model.live="brandingForm.secondary_color" wire:change="$set('brandingForm.selected_preset', null)" />
                        <x-mary-input label="{{ __('setting.fields.accent_color') }}" type="color" wire:model.live="brandingForm.accent_color" wire:change="$set('brandingForm.selected_preset', null)" />
                        <x-mary-input label="{{ __('setting.fields.base_color') }}" type="color" wire:model.live="brandingForm.base_color" />
                    </div>
                </x-mary-card>

                {{-- Mail --}}
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-envelope" class="size-4 text-primary" />
                            <span class="font-semibold">{{ __('setting.groups.mail') }}</span>
                        </div>
                    </x-slot:title>
                    <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('setting.hints.mail') }}</span></x-slot:subtitle>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="{{ __('setting.fields.mail_from_address') }}" type="email" wire:model="mailSettingsForm.mail_from_address" icon="o-envelope" />
                        <x-mary-input label="{{ __('setting.fields.mail_from_name') }}" wire:model="mailSettingsForm.mail_from_name" icon="o-tag" />
                        <x-mary-input label="{{ __('setting.fields.mail_host') }}" wire:model="mailSettingsForm.mail_host" icon="o-server" />
                        <x-mary-input label="{{ __('setting.fields.mail_port') }}" wire:model="mailSettingsForm.mail_port" icon="o-numbered-list" />
                        <x-mary-select label="{{ __('setting.fields.mail_encryption') }}" wire:model="mailSettingsForm.mail_encryption"
                            :options="[['id' => 'tls', 'name' => 'TLS'], ['id' => 'ssl', 'name' => 'SSL'], ['id' => 'none', 'name' => 'None']]" />
                        <x-mary-input label="{{ __('setting.fields.mail_username') }}" wire:model="mailSettingsForm.mail_username" icon="o-user" />
                        <x-mary-input label="{{ __('setting.fields.mail_password') }}" type="password" wire:model="mailSettingsForm.mail_password" icon="o-key" />
                    </div>
                    <div class="mt-4 flex justify-end">
                        <x-mary-button label="{{ __('setting.buttons.test_mail') }}" icon-right="o-paper-airplane" class="btn-ghost btn-sm" wire:click="testEmail" spinner="testEmail" />
                    </div>
                </x-mary-card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-server" class="size-4 text-base-content/40" />
                            <span class="font-semibold text-sm">{{ __('setting.groups.system') }}</span>
                        </div>
                    </x-slot:title>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between py-1.5 border-b border-base-content/10 last:border-0">
                            <span class="text-base-content/50">{{ __('setting.fields.app_name') }}</span>
                            <span class="font-medium">{{ $app_name }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-base-content/50">{{ __('setting.fields.app_version') }}</span>
                            <x-mary-badge :value="$app_version" class="badge-neutral badge-sm" />
                        </div>
                    </div>
                </x-mary-card>

                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-document-text" class="size-4 text-base-content/40" />
                            <span class="font-semibold text-sm">{{ __('setting.groups.identity') }}</span>
                        </div>
                    </x-slot:title>
                    <div class="flex flex-col items-center gap-6 pt-4">
                        <div class="flex flex-col items-center">
                            <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.fields.brand_logo') }}</p>
                            <div class="relative group">
                                <div class="cursor-pointer relative" onclick="document.getElementById('brand-logo-upload').click()">
                                    <input id="brand-logo-upload" type="file" wire:model="brandingForm.brand_logo" accept="image/png,image/jpeg,image/webp" class="hidden" />
                                    @if($this->brandingForm->brandLogoPreviewUrl() ?? $brandingForm->current_logo_url)
                                        <img src="{{ $this->brandingForm->brandLogoPreviewUrl() ?? $brandingForm->current_logo_url }}"
                                             alt="Brand logo"
                                             class="size-24 rounded-xl object-contain border border-base-content/10" />
                                    @else
                                        <div class="size-24 rounded-xl bg-base-200 flex items-center justify-center border border-dashed border-base-content/20">
                                            <x-mary-icon name="o-building-office" class="size-8 text-base-content/30" />
                                        </div>
                                    @endif
                                    <div class="absolute inset-0 flex items-center justify-center rounded-xl bg-base-content/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <x-mary-icon name="o-camera" class="size-8 text-base-100" />
                                    </div>
                                </div>
                                @if($brandingForm->current_logo_url)
                                    <button type="button"
                                        wire:click="$set('confirmTarget', 'removeBrandLogo'); $set('showConfirm', true)"
                                        class="absolute -top-2 -right-2 size-6 bg-error text-error-content rounded-full flex items-center justify-center hover:scale-110 transition-transform shadow-sm opacity-0 group-hover:opacity-100">
                                        <x-mary-icon name="o-x-mark" class="size-3" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col items-center">
                            <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setting.fields.site_favicon') }}</p>
                            <div class="relative group">
                                <div class="cursor-pointer relative" onclick="document.getElementById('favicon-upload').click()">
                                    <input id="favicon-upload" type="file" wire:model="brandingForm.site_favicon" accept="image/png,image/jpeg,image/x-icon" class="hidden" />
                                    @if($this->brandingForm->faviconPreviewUrl() ?? $brandingForm->current_favicon_url)
                                        <img src="{{ $this->brandingForm->faviconPreviewUrl() ?? $brandingForm->current_favicon_url }}"
                                             alt="Favicon"
                                             class="size-12 rounded-lg object-contain border border-base-content/10" />
                                    @else
                                        <div class="size-12 rounded-lg bg-base-200 flex items-center justify-center border border-dashed border-base-content/20">
                                            <x-mary-icon name="o-globe-alt" class="size-5 text-base-content/30" />
                                        </div>
                                    @endif
                                    <div class="absolute inset-0 flex items-center justify-center rounded-lg bg-base-content/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <x-mary-icon name="o-camera" class="size-5 text-base-100" />
                                    </div>
                                </div>
                                @if($brandingForm->current_favicon_url)
                                    <button type="button"
                                        wire:click="$set('confirmTarget', 'removeFavicon'); $set('showConfirm', true)"
                                        class="absolute -top-2 -right-2 size-6 bg-error text-error-content rounded-full flex items-center justify-center hover:scale-110 transition-transform shadow-sm opacity-0 group-hover:opacity-100">
                                        <x-mary-icon name="o-x-mark" class="size-3" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-mary-card>

                @include('core.ui.confirm', [
                    'message' => __('setting.messages.remove_asset_confirm'),
                    'confirmText' => __('common.actions.remove'),
                ])
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('setting.buttons.save')" type="submit" form="settings-form" class="btn-primary" icon="o-check" spinner="save" />
        </x-slot:actions>
    </x-mary-form>

    @include('sysadmin.setting.components.settings-guide')
</div>
