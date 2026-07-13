<div class="py-4">
    <div class="mb-6 flex items-center gap-4">
        <div class="size-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <x-mary-icon name="o-building-library" class="size-6" />
        </div>
        <div>
            <h2 class="text-xl font-bold">{{ __('school.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-0.5">{{ __('school.subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-base-100 border border-base-content/10 rounded-xl">
                <div class="p-6 sm:p-8">
                    <form wire:submit="save" class="space-y-5"
                        x-data="{ isDirty: false }"
                        @input="isDirty = true"
                        x-init="
                            $wire.on('saved', () => isDirty = false);
                            window.addEventListener('beforeunload', (e) => {
                                if (isDirty) {
                                    e.preventDefault();
                                    e.returnValue = '';
                                }
                            });
                        ">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <x-mary-input
                                    label="{{ __('school.name') }}"
                                    placeholder="{{ __('school.name_placeholder') }}"
                                    wire:model="form.name"
                                    icon="o-academic-cap"
                                />
                            </div>

                            <x-mary-input
                                label="{{ __('school.institutional_code') }}"
                                placeholder="{{ __('school.institutional_code_placeholder') }}"
                                :hint="__('school.institutional_code_hint')"
                                wire:model="form.institutional_code"
                                icon="o-identification"
                            />

                            <x-mary-input
                                label="{{ __('school.email') }}"
                                type="email"
                                placeholder="{{ __('school.email_placeholder') }}"
                                wire:model="form.email"
                                icon="o-envelope"
                            />

                            <x-mary-input
                                label="{{ __('school.phone') }}"
                                placeholder="{{ __('school.phone_placeholder') }}"
                                wire:model="form.phone"
                                icon="o-phone"
                            />

                            <x-mary-input
                                label="{{ __('school.fax') }}"
                                placeholder="{{ __('school.fax_placeholder') }}"
                                wire:model="form.fax"
                                icon="o-printer"
                            />

                            <div class="md:col-span-2">
                                <x-mary-textarea
                                    label="{{ __('school.address') }}"
                                    placeholder="{{ __('school.address_placeholder') }}"
                                    rows="3"
                                    wire:model="form.address"
                                    icon="o-map-pin"
                                />
                            </div>

                            <x-mary-input
                                label="{{ __('school.website') }}"
                                type="url"
                                placeholder="{{ __('school.website_placeholder') }}"
                                wire:model="form.website"
                                icon="o-globe-alt"
                            />

                            <x-mary-input
                                label="{{ __('school.principal_name') }}"
                                placeholder="{{ __('school.principal_name_placeholder') }}"
                                wire:model="form.principal_name"
                                icon="o-user-circle"
                            />
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-base-content/10">
                            <x-mary-button
                                :label="__('school.discard')"
                                link="{{ url()->previous() }}"
                                class="btn-ghost btn-sm"
                            />
                            <x-mary-button
                                :label="__('school.save_changes')"
                                type="submit"
                                class="btn-primary btn-sm"
                                icon="o-check"
                                spinner="save"
                            />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- School Logo --}}
            <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
                <div class="flex flex-col items-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('school.logo') }}</p>
                        <div class="relative mb-2 group">
                            <div class="cursor-pointer relative" onclick="document.getElementById('school-logo-upload').click()">
                                <input id="school-logo-upload" type="file" wire:model="logo_file" accept="image/png,image/jpeg,image/webp" class="hidden" />
                                @if($this->logoPreviewUrl())
                                    <img src="{{ $this->logoPreviewUrl() }}"
                                         alt="{{ __('school.logo') }}"
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
                        @if($this->logoPreviewUrl())
                            <button type="button"
                                wire:click="$set('showConfirm', true)"
                                aria-label="{{ __('common.actions.remove') }}"
                                class="absolute -top-2 -right-2 size-6 bg-error text-error-content rounded-full flex items-center justify-center hover:scale-110 transition-transform shadow-sm opacity-0 group-hover:opacity-100">
                                <x-mary-icon name="o-x-mark" class="size-3" />
                            </button>
                        @endif
                    </div>
                    <p class="text-[10px] text-base-content/40 text-center">{{ __('school.logo_hint') }}</p>
                </div>

                @include('core.ui.confirm', [
                    'message' => __('school.logo_remove_confirm'),
                    'confirmText' => __('common.actions.remove'),
                ])
            </div>

            {{-- System Context --}}
            <div class="bg-base-200/40 border border-base-content/10 rounded-xl p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-4">{{ __('school.system_context') }}</p>
                <p class="text-sm text-base-content/60 leading-relaxed mb-4">
                    {{ __('school.system_context_desc') }}
                </p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-check-badge" class="size-4 text-success shrink-0" />
                        <span class="text-xs text-base-content/50">{{ __('school.uuid_enabled') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-shield-check" class="size-4 text-info shrink-0" />
                        <span class="text-xs text-base-content/50">{{ __('school.audit_logged') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('setup.components.school-guide')
</div>
