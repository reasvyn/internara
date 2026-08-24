<div class="py-4">
    <div class="mb-6 flex items-center gap-4">
        <div class="bg-primary/10 text-primary flex size-12 shrink-0 items-center justify-center rounded-xl">
            <x-ts-icon name="cog-6-tooth" class="size-6" />
        </div>
        <div>
            <h2 class="text-xl font-bold">{{ __('setting.title') }}</h2>
            <p class="text-base-content/50 mt-0.5 text-sm">{{ __('setting.subtitle') }}</p>
        </div>
    </div>

    <form wire:submit="save" id="settings-form">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- General --}}
                <x-mary-card class="bg-base-100 border-base-content/10 border">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-ts-icon name="cog" class="text-primary size-4" />
                            <span class="font-semibold">{{ __('setting.groups.general') }}</span>
                        </div>
                    </x-slot:title>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input
                            label="{{ __('setting.fields.brand_name') }}"
                            wire:model="generalForm.brand_name"
                            icon="building-library"
                        />
                        <x-mary-select
                            label="{{ __('setting.fields.default_locale') }}"
                            wire:model="generalForm.default_locale"
                            :options="[
                                ['id' => 'id', 'name' => __('setting.locales.id')],
                                ['id' => 'en', 'name' => __('setting.locales.en')],
                            ]"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.site_title') }}"
                            wire:model="generalForm.site_title"
                            class="md:col-span-2"
                            icon="globe-alt"
                        />
                        <x-mary-select
                            label="{{ __('setting.fields.active_academic_year') }}"
                            wire:model="generalForm.active_academic_year"
                            :options="$this->academicYearOptions"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.support_email') }}"
                            type="email"
                            wire:model="generalForm.support_email"
                            icon="lifebuoy"
                            hint="{{ __('setting.hints.support_email') }}"
                        />
                    </div>
                </x-mary-card>

                {{-- Color Scheme --}}
                <x-mary-card class="bg-base-100 border-base-content/10 border">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-ts-icon name="swatch" class="text-primary size-4" />
                            <span class="font-semibold">{{ __('setting.groups.color_scheme') }}</span>
                        </div>
                    </x-slot:title>
                    <x-slot:subtitle>
                        <span class="text-base-content/50 text-xs">{{ __('setting.hints.color_scheme') }}</span>
                    </x-slot:subtitle>

                    {{-- Presets --}}
                    <div class="mb-6">
                        <p class="text-base-content/50 mb-3 text-xs font-semibold tracking-wider uppercase">
                            {{ __('setting.presets_title') }}
                        </p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($presets as $key => $preset)
                                <button
                                    type="button"
                                    wire:click="applyPreset('{{ $key }}')"
                                    @class([
                                        'relative flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200 cursor-pointer hover:scale-105',
                                        'border-primary shadow-md shadow-primary/10' => $brandingForm->selected_preset === $key,
                                        'border-base-content/10 hover:border-base-content/30' => $brandingForm->selected_preset !== $key,
                                    ])
                                >
                                    <div class="flex -space-x-1.5">
                                        <span
                                            class="ring-base-100 size-5 rounded-full ring-2"
                                            style="background: {{ $preset['colors']['primary'] }}"
                                        ></span>
                                        <span
                                            class="ring-base-100 size-5 rounded-full ring-2"
                                            style="background: {{ $preset['colors']['secondary'] }}"
                                        ></span>
                                        <span
                                            class="ring-base-100 size-5 rounded-full ring-2"
                                            style="background: {{ $preset['colors']['accent'] }}"
                                        ></span>
                                        <span
                                            class="ring-base-100 size-5 rounded-full ring-2"
                                            style="background: {{ $preset['colors']['base'] }}"
                                        ></span>
                                    </div>
                                    <span class="text-xs font-medium whitespace-nowrap">{{ $preset['label'] }}</span>
                                    @if ($brandingForm->selected_preset === $key)
                                        <span class="bg-primary text-primary-content absolute -top-1.5 -right-1.5 flex size-4 items-center justify-center rounded-full">
                                            <x-ts-icon name="check" class="size-2.5" />
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <p class="text-base-content/50 mb-3 text-xs font-semibold tracking-wider uppercase">
                        {{ __('setting.custom_title') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <x-ts-input
                            label="{{ __('setting.fields.primary_color') }}"
                            type="color"
                            wire:model.live="brandingForm.primary_color"
                            wire:change="$set('brandingForm.selected_preset', null)"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.secondary_color') }}"
                            type="color"
                            wire:model.live="brandingForm.secondary_color"
                            wire:change="$set('brandingForm.selected_preset', null)"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.accent_color') }}"
                            type="color"
                            wire:model.live="brandingForm.accent_color"
                            wire:change="$set('brandingForm.selected_preset', null)"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.base_color') }}"
                            type="color"
                            wire:model.live="brandingForm.base_color"
                        />
                    </div>
                </x-mary-card>

                {{-- Mail --}}
                <x-mary-card class="bg-base-100 border-base-content/10 border">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-ts-icon name="envelope" class="text-primary size-4" />
                            <span class="font-semibold">{{ __('setting.groups.mail') }}</span>
                        </div>
                    </x-slot:title>
                    <x-slot:subtitle>
                        <span class="text-base-content/50 text-xs">{{ __('setting.hints.mail') }}</span>
                    </x-slot:subtitle>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input
                            label="{{ __('setting.fields.mail_from_address') }}"
                            type="email"
                            wire:model="mailSettingsForm.mail_from_address"
                            icon="envelope"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.mail_from_name') }}"
                            wire:model="mailSettingsForm.mail_from_name"
                            icon="tag"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.mail_host') }}"
                            wire:model="mailSettingsForm.mail_host"
                            icon="server"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.mail_port') }}"
                            wire:model="mailSettingsForm.mail_port"
                            icon="numbered-list"
                        />
                        <x-mary-select
                            label="{{ __('setting.fields.mail_encryption') }}"
                            wire:model="mailSettingsForm.mail_encryption"
                            :options="[['id' => 'tls', 'name' => __('setting.encryptions.tls')], ['id' => 'ssl', 'name' => __('setting.encryptions.ssl')], ['id' => 'none', 'name' => __('setting.encryptions.none')]]"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.mail_username') }}"
                            wire:model="mailSettingsForm.mail_username"
                            icon="user"
                        />
                        <x-ts-input
                            label="{{ __('setting.fields.mail_password') }}"
                            type="password"
                            wire:model="mailSettingsForm.mail_password"
                            icon="key"
                        />
                    </div>
                    <div class="mt-4 flex justify-end">
                        <x-ts-button
                            aria-label="{{ __('common.actions.remove') }}"
                            aria-label="{{ __('common.actions.remove') }}"
                            text="{{ __('setting.buttons.test_mail') }}"
                            icon-right="o-paper-airplane"
                            color="white"
                            sm
                            wire:click="testEmail"
                            loading="testEmail"
                        />
                    </div>
                </x-mary-card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <x-mary-card class="bg-base-100 border-base-content/10 border">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-ts-icon name="server" class="text-base-content/40 size-4" />
                            <span class="text-sm font-semibold">{{ __('setting.groups.system') }}</span>
                        </div>
                    </x-slot:title>
                    <div class="space-y-3 text-sm">
                        <div class="border-base-content/10 flex items-center justify-between border-b py-1.5 last:border-0">
                            <span class="text-base-content/50">{{ __('setting.fields.app_name') }}</span>
                            <span class="font-medium">{{ $app_name }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-base-content/50">{{ __('setting.fields.app_version') }}</span>
                            <x-ts-badge :text="$app_version" class="badge-neutral badge-sm" />
                        </div>
                    </div>
                </x-mary-card>

                <x-mary-card class="bg-base-100 border-base-content/10 border">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-ts-icon name="document-text" class="text-base-content/40 size-4" />
                            <span class="text-sm font-semibold">{{ __('setting.groups.identity') }}</span>
                        </div>
                    </x-slot:title>
                    <div class="flex flex-col items-center gap-6 pt-4">
                        <div class="flex flex-col items-center">
                            <p class="text-base-content/50 mb-3 text-xs font-semibold tracking-wider uppercase">
                                {{ __('setting.fields.brand_logo') }}
                            </p>
                            <div class="group relative">
                                <div class="relative cursor-pointer" x-data x-on:click="$refs.brandLogoInput.click()">
                                    <input
                                        id="brand-logo-upload"
                                        x-ref="brandLogoInput"
                                        type="file"
                                        wire:model="brandingForm.brand_logo"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                    />
                                    @if ($this->brandingForm->brandLogoPreviewUrl() ?? $brandingForm->current_logo_url)
                                        <img
                                            src="{{ $this->brandingForm->brandLogoPreviewUrl() ?? $brandingForm->current_logo_url }}"
                                            alt="{{ __('setting.fields.brand_logo') }}"
                                            class="border-base-content/10 size-24 rounded-xl border object-contain"
                                        />
                                    @else
                                        <div class="bg-base-200 border-base-content/20 flex size-24 items-center justify-center rounded-xl border border-dashed">
                                            <x-ts-icon name="building-office" class="text-base-content/30 size-8" />
                                        </div>
                                    @endif
                                    <div class="bg-base-content/60 absolute inset-0 flex items-center justify-center rounded-xl opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        <x-ts-icon name="camera" class="text-base-100 size-8" />
                                    </div>
                                </div>
                                @if ($brandingForm->current_logo_url)
                                    <button
                                        type="button"
                                        wire:click="$set('confirmTarget', 'removeBrandLogo'); $set('showConfirm', true)"
                                        class="bg-error text-error-content absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full opacity-0 shadow-sm transition-transform group-hover:opacity-100 hover:scale-110"
                                    >
                                        <x-ts-icon name="x-mark" class="size-3" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col items-center">
                            <p class="text-base-content/50 mb-3 text-xs font-semibold tracking-wider uppercase">
                                {{ __('setting.fields.site_favicon') }}
                            </p>
                            <div class="group relative">
                                <div class="relative cursor-pointer" x-data x-on:click="$refs.faviconInput.click()">
                                    <input
                                        id="favicon-upload"
                                        x-ref="faviconInput"
                                        type="file"
                                        wire:model="brandingForm.site_favicon"
                                        accept="image/png,image/jpeg,image/x-icon"
                                        class="hidden"
                                    />
                                    @if ($this->brandingForm->faviconPreviewUrl() ?? $brandingForm->current_favicon_url)
                                        <img
                                            src="{{ $this->brandingForm->faviconPreviewUrl() ?? $brandingForm->current_favicon_url }}"
                                            alt="{{ __('setting.fields.site_favicon') }}"
                                            class="border-base-content/10 size-12 rounded-lg border object-contain"
                                        />
                                    @else
                                        <div class="bg-base-200 border-base-content/20 flex size-12 items-center justify-center rounded-lg border border-dashed">
                                            <x-ts-icon name="globe-alt" class="text-base-content/30 size-5" />
                                        </div>
                                    @endif
                                    <div class="bg-base-content/60 absolute inset-0 flex items-center justify-center rounded-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        <x-ts-icon name="camera" class="text-base-100 size-5" />
                                    </div>
                                </div>
                                @if ($brandingForm->current_favicon_url)
                                    <button
                                        type="button"
                                        wire:click="$set('confirmTarget', 'removeFavicon'); $set('showConfirm', true)"
                                        class="bg-error text-error-content absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full opacity-0 shadow-sm transition-transform group-hover:opacity-100 hover:scale-110"
                                    >
                                        <x-ts-icon name="x-mark" class="size-3" />
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
            <x-ts-button
                :text="__('setting.buttons.save')"
                type="submit"
                form="settings-form"
                color="primary"
                icon="check"
                loading="save"
            />
        </x-slot:actions>
    </form>

    @include('settings.components.settings-guide')
</div>
