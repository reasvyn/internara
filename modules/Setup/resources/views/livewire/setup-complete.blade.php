<div x-data="{ 
    dataVerified: @entangle('data_verified'),
    securityAware: @entangle('security_aware'),
    legalAgreed: @entangle('legal_agreed'),
    get canFinalize() {
        return this.dataVerified && this.securityAware && this.legalAgreed;
    }
}">
    <x-setup::layouts.setup-wizard :step="7" :totalSteps="7">
        <x-slot:header>
            <x-setup::wizard-header 
                step="7"
                :title="__('setup::wizard.complete.title')"
                :description="__('setup::wizard.complete.description', ['app' => setting('app_name', 'Internara')])"
                badgeText="Final"
            />
        </x-slot:header>

        <x-slot:content>
            <div class="p-5 sm:p-6">
                <h3 class="text-base font-semibold text-base-content/80 dark:text-base-content/70 mb-4">
                    {{ __('setup::wizard.complete.checkup_title') }}
                </h3>

                <div class="space-y-3">
                    <!-- Data Verification -->
                    <label class="flex items-start gap-3 p-4 rounded-xl bg-base-200/30 dark:bg-base-200/20 border border-base-200/50 dark:border-base-200/30 hover:border-base-300/50 dark:hover:border-base-300/30 transition-colors cursor-pointer">
                        <input type="checkbox" x-model="dataVerified" class="checkbox checkbox-sm mt-0.5" />
                        <div class="flex-1">
                            <span class="block text-sm font-medium text-base-content dark:text-base-content/90">
                                {{ __('setup::wizard.complete.checkup.data_verified_label') }}
                            </span>
                            <span class="block text-xs text-base-content/50 dark:text-base-content/40 mt-1">
                                {{ __('setup::wizard.complete.checkup.data_verified_desc') }}
                            </span>
                        </div>
                    </label>

                    <!-- Security Awareness -->
                    <label class="flex items-start gap-3 p-4 rounded-xl bg-base-200/30 dark:bg-base-200/20 border border-base-200/50 dark:border-base-200/30 hover:border-base-300/50 dark:hover:border-base-300/30 transition-colors cursor-pointer">
                        <input type="checkbox" x-model="securityAware" class="checkbox checkbox-sm mt-0.5" />
                        <div class="flex-1">
                            <span class="block text-sm font-medium text-base-content dark:text-base-content/90">
                                {{ __('setup::wizard.complete.checkup.security_aware_label') }}
                            </span>
                            <span class="block text-xs text-base-content/50 dark:text-base-content/40 mt-1">
                                {{ __('setup::wizard.complete.checkup.security_aware_desc') }}
                            </span>
                        </div>
                    </label>

                    <!-- Legal Agreement -->
                    <label class="flex items-start gap-3 p-4 rounded-xl bg-base-200/30 dark:bg-base-200/20 border border-base-200/50 dark:border-base-200/30 hover:border-base-300/50 dark:hover:border-base-300/30 transition-colors cursor-pointer">
                        <input type="checkbox" x-model="legalAgreed" class="checkbox checkbox-sm mt-0.5" />
                        <div class="flex-1">
                            <span class="block text-sm font-medium text-base-content dark:text-base-content/90">
                                {{ __('setup::wizard.complete.checkup.legal_agreed_label') }}
                            </span>
                            <span class="block text-xs text-base-content/50 dark:text-base-content/40 mt-1">
                                {!! __('setup::wizard.complete.checkup.legal_agreed_desc', [
                                    'privacy' => '<a href=# x-on:click.prevent=$wire.set(\'showPrivacy\', true) class=underline font-medium>Privacy Policy</a>',
                                    'terms' => '<a href=# x-on:click.prevent=$wire.set(\'showTerms\', true) class=underline font-medium>Terms of Service</a>'
                                ]) !!}
                            </span>
                        </div>
                    </label>
                </div>
            </div>
        </x-slot:content>

        <x-slot:footer>
        <x-setup::action-footer 
            :canContinue="$canFinalize"
            :continueLabel="__('setup::wizard.complete.cta')"
        />
    </x-slot:footer>
    </x-setup::layouts.setup-wizard>

    <!-- Legal Modals -->
    <x-ui::modal wire:model="showPrivacy" :title="__('Privacy Policy')">
        <div class="p-4 max-h-96 overflow-y-auto">
            @include('shared::legal.privacy-policy')
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" x-on:click="$wire.set('showPrivacy', false)" />
        </x-slot:actions>
    </x-ui::modal>

    <x-ui::modal wire:model="showTerms" :title="__('Terms of Service')">
        <div class="p-4 max-h-96 overflow-y-auto">
            @include('shared::legal.terms-of-service')
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" x-on:click="$wire.set('showTerms', false)" />
        </x-slot:actions>
    </x-ui::modal>
</div>
