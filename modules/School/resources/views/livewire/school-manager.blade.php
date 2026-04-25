<div>
    <x-ui::form class="flex w-full flex-col gap-12" wire:submit="save">
        <div class="max-w-2xl mx-auto w-full space-y-12">
            <!-- Section 1: Institutional Identity -->
            <div class="space-y-8">
                <div class="space-y-2 border-b border-base-content/5 pb-4">
                    <h3 class="text-xl font-bold tracking-tight text-base-content">{{ __('school::ui.identity_branding') }}</h3>
                    <p class="text-sm text-base-content/60">{{ __('school::ui.identity_branding_desc') }}</p>
                </div>

                <div class="rounded-3xl bg-base-200/30 p-8 border border-base-content/5 flex flex-col items-center gap-6">
                    <x-ui::file
                        :label="__('school::ui.logo')"
                        wire:model="form.logo_file"
                        accept="image/*"
                        crop
                        ratio="1/1"
                        :preview="$form->logo_url"
                        :hint="__('school::ui.logo_hint')"
                    />
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <x-ui::input
                        type="text"
                        :label="__('school::ui.name')"
                        :placeholder="__('school::ui.name_placeholder')"
                        icon="tabler.school"
                        required
                        wire:model="form.name"
                    />

                    <x-ui::input
                        type="text"
                        :label="__('school::ui.institutional_code')"
                        :placeholder="__('school::ui.institutional_code_placeholder')"
                        icon="tabler.id"
                        required
                        wire:model="form.institutional_code"
                        hint="{{ __('school::ui.institutional_code_hint') }}"
                    />
                </div>
            </div>

            <!-- Section 2: Contact & Leadership -->
            <div class="space-y-8">
                <div class="space-y-2 border-b border-base-content/5 pb-4">
                    <h3 class="text-xl font-bold tracking-tight text-base-content">{{ __('school::ui.contact_leadership') }}</h3>
                    <p class="text-sm text-base-content/60">{{ __('school::ui.contact_leadership_desc') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="col-span-full">
                        <x-ui::textarea
                            :label="__('school::ui.address')"
                            :placeholder="__('school::ui.address_placeholder')"
                            icon="tabler.map-pin"
                            wire:model="form.address"
                            rows="3"
                        />
                    </div>

                    <div class="col-span-full">
                        <x-ui::input
                            type="email"
                            :label="__('school::ui.email')"
                            :placeholder="__('school::ui.email_placeholder')"
                            icon="tabler.mail"
                            wire:model="form.email"
                        />
                    </div>

                    <x-ui::input
                        type="tel"
                        :label="__('school::ui.phone')"
                        :placeholder="__('school::ui.phone_placeholder')"
                        icon="tabler.phone"
                        wire:model="form.phone"
                    />

                    <x-ui::input
                        type="tel"
                        :label="__('school::ui.fax')"
                        :placeholder="__('school::ui.fax_placeholder')"
                        icon="tabler.printer"
                        wire:model="form.fax"
                    />

                    <div class="col-span-full">
                        <x-ui::input
                            type="text"
                            :label="__('school::ui.principal_name')"
                            :placeholder="__('school::ui.principal_name_placeholder')"
                            icon="tabler.user-star"
                            wire:model="form.principal_name"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Action -->
        <div class="flex flex-col items-center pt-10 border-t border-base-content/5" wire:key="sm-actions">
            <x-ui::button 
                variant="primary" 
                class="btn-lg px-20 shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-95" 
                :label="__('ui::common.save')" 
                type="submit" 
                spinner="save"
            />
        </div>
    </x-ui::form>
</div>