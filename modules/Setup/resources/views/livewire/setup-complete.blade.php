<div x-data="{ 
    dataVerified: @entangle('data_verified'),
    securityAware: @entangle('security_aware'),
    legalAgreed: @entangle('legal_agreed'),
    get canFinalize() {
        return this.dataVerified && this.securityAware && this.legalAgreed;
    }
}" class="w-full">
    <x-setup::layouts.setup-wizard>
        <x-slot:header>
            <x-setup::wizard-header 
                step="8"
                :title="__('setup::wizard.complete.headline', ['app' => setting('app_name', 'Internara')])"
                :description="__('setup::wizard.complete.description', ['app' => setting('app_name', 'Internara')])"
            />

            <div class="mt-12 flex flex-wrap items-center gap-4">
                <x-ui::button
                    variant="secondary"
                    :label="__('setup::wizard.common.back')"
                    wire:click="backToPrev"
                />
                <x-ui::button
                    variant="primary"
                    class="btn-lg px-12 shadow-lg shadow-primary/20"
                    :label="__('setup::wizard.complete.cta')"
                    wire:click="nextStep"
                    x-bind:disabled="!canFinalize"
                    spinner
                />
            </div>
        </x-slot:header>

        <x-slot:content>
            <div class="w-full">
                <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
                    <div class="mb-10 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-2xl font-bold text-base-content">{{ __('setup::wizard.complete.checkup_title') }}</h3>
                            <p class="text-sm text-base-content/50 mt-1">{{ __('setup::wizard.complete.checkup_desc') }}</p>
                        </div>
                        <x-ui::button
                            variant="secondary"
                            size="sm"
                            class="border-base-content/10 text-base-content/60 hover:text-primary hover:border-primary/30"
                            :label="__('setup::wizard.complete.download_report')"
                            icon="tabler.file-download"
                            wire:click="downloadTechnicalReport"
                        />
                    </div>

                    <div class="space-y-6">
                        <!-- Data Verification -->
                        <label class="flex items-start gap-4 p-6 rounded-2xl bg-base-200/30 border border-transparent transition-all hover:bg-base-200/50 cursor-pointer group">
                            <div class="pt-1">
                                <input type="checkbox" x-model="dataVerified" class="checkbox checkbox-primary" />
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-base-content group-hover:text-primary transition-colors">
                                    {{ __('setup::wizard.complete.checkup.data_verified_label') }}
                                </span>
                                <span class="block text-xs text-base-content/60 mt-1 leading-relaxed">
                                    {{ __('setup::wizard.complete.checkup.data_verified_desc') }}
                                </span>
                            </div>
                        </label>

                        <!-- Security Awareness -->
                        <label class="flex items-start gap-4 p-6 rounded-2xl bg-base-200/30 border border-transparent transition-all hover:bg-base-200/50 cursor-pointer group">
                            <div class="pt-1">
                                <input type="checkbox" x-model="securityAware" class="checkbox checkbox-primary" />
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-base-content group-hover:text-primary transition-colors">
                                    {{ __('setup::wizard.complete.checkup.security_aware_label') }}
                                </span>
                                <span class="block text-xs text-base-content/60 mt-1 leading-relaxed">
                                    {{ __('setup::wizard.complete.checkup.security_aware_desc') }}
                                </span>
                            </div>
                        </label>

                        <!-- Legal & Policy Agreement -->
                        <label class="flex items-start gap-4 p-6 rounded-2xl bg-base-200/30 border border-transparent transition-all hover:bg-base-200/50 cursor-pointer group">
                            <div class="pt-1">
                                <input type="checkbox" x-model="legalAgreed" class="checkbox checkbox-primary" />
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-base-content group-hover:text-primary transition-colors">
                                    {{ __('setup::wizard.complete.checkup.legal_agreed_label') }}
                                </span>
                                <span class="block text-xs text-base-content/60 mt-1 leading-relaxed">
                                    {!! __('setup::wizard.complete.checkup.legal_agreed_desc', [
                                        'privacy' => '<a href="#" x-on:click.prevent="$wire.set(\'showPrivacy\', true)" class="text-primary hover:underline font-bold">Privacy Policy</a>',
                                        'terms' => '<a href="#" x-on:click.prevent="$wire.set(\'showTerms\', true)" class="text-primary hover:underline font-bold">Terms of Service</a>'
                                    ]) !!}
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </x-slot:content>
    </x-setup::layouts.setup-wizard>

    <!-- Legal Modals -->
    <x-ui::modal wire:model="showPrivacy" :title="__('setup::wizard.complete.checkup.legal_agreed_label')">
        <div class="p-4">
            @include('shared::legal.privacy-policy')
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" x-on:click="$wire.set('showPrivacy', false)" />
        </x-slot:actions>
    </x-ui::modal>

    <x-ui::modal wire:model="showTerms" :title="__('setup::wizard.complete.checkup.legal_agreed_label')">
        <div class="p-4">
            @include('shared::legal.terms-of-service')
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" x-on:click="$wire.set('showTerms', false)" />
        </x-slot:actions>
    </x-ui::modal>
</div>
