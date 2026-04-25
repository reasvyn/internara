<x-setup::layouts.setup-wizard>
    <div x-data="{ 
        dataVerified: @entangle('data_verified'),
        securityAware: @entangle('security_aware'),
        legalAgreed: @entangle('legal_agreed'),
        get canFinalize() {
            return this.dataVerified && this.securityAware && this.legalAgreed;
        }
    }" class="w-full">
        <x-slot:header>
            <div class="max-w-4xl">
                <x-ui::badge variant="metadata" :value="__('setup::wizard.steps', ['current' => 8, 'total' => 8])" class="mb-12" />

                <p class="mb-6 font-bold text-success text-lg animate-bounce">
                    {{ __('setup::wizard.complete.badge') }}
                </p>

                <h1 class="text-4xl font-extrabold tracking-tight text-base-content md:text-5xl lg:text-6xl leading-[1.1]">
                    {{ __('setup::wizard.complete.headline', ['app' => setting('app_name', 'Internara')]) }}
                </h1>

                <div class="mt-8 space-y-6">
                    <p class="text-lg text-base-content/70 leading-relaxed max-w-2xl">
                        {{ __('setup::wizard.complete.description', ['app' => setting('app_name', 'Internara')]) }}
                    </p>
                    <p class="text-base-content/60 leading-relaxed max-w-2xl italic">
                        {{ __('setup::wizard.complete.description_extra') }}
                    </p>
                </div>
            </div>

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
        </x-slot>

        <x-slot:content>
            <div class="w-full">
                <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
                    <div class="mb-10">
                        <h3 class="text-2xl font-bold text-base-content">{{ __('setup::wizard.complete.checkup_title') ?? 'System Readiness Check-up' }}</h3>
                        <p class="text-sm text-base-content/50 mt-1">{{ __('setup::wizard.complete.checkup_desc') ?? 'Please review and confirm your compliance with system governance standards.' }}</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Data Verification -->
                        <label class="flex items-start gap-4 p-6 rounded-2xl bg-base-200/30 border border-transparent transition-all hover:bg-base-200/50 cursor-pointer group">
                            <div class="pt-1">
                                <input type="checkbox" x-model="dataVerified" class="checkbox checkbox-primary" />
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-base-content group-hover:text-primary transition-colors">
                                    {{ __('setup::wizard.complete.checkup.data_verified_label') ?? 'Data Integrity Confirmation' }}
                                </span>
                                <span class="block text-xs text-base-content/60 mt-1 leading-relaxed">
                                    {{ __('setup::wizard.complete.checkup.data_verified_desc') ?? 'I have reviewed the school, department, and program data. I confirm that all entered information is accurate and reflects the official status of the institution.' }}
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
                                    {{ __('setup::wizard.complete.checkup.security_aware_label') ?? 'Security Sovereignty Acknowledgment' }}
                                </span>
                                <span class="block text-xs text-base-content/60 mt-1 leading-relaxed">
                                    {{ __('setup::wizard.complete.checkup.security_aware_desc') ?? 'I understand that my SuperAdmin account holds absolute authority. I commit to maintaining credential secrecy and following enterprise security protocols to protect institutional data.' }}
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
                                    {{ __('setup::wizard.complete.checkup.legal_agreed_label') ?? 'Legal & Regulatory Compliance' }}
                                </span>
                                <span class="block text-xs text-base-content/60 mt-1 leading-relaxed">
                                    {!! __('setup::wizard.complete.checkup.legal_agreed_desc', [
                                        'privacy' => '<a href="#" x-on:click.prevent="$wire.set(\'showPrivacy\', true)" class="text-primary hover:underline font-bold">Privacy Policy</a>',
                                        'terms' => '<a href="#" x-on:click.prevent="$wire.set(\'showTerms\', true)" class="text-primary hover:underline font-bold">Terms of Service</a>'
                                    ]) ?? 'I agree to the <a href="#" class="text-primary hover:underline font-bold">Privacy Policy</a> and <a href="#" class="text-primary hover:underline font-bold">Terms of Service</a>. I commit to operating Internara in compliance with applicable data protection laws.' !!}
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </x-slot>
    </div>

    <!-- Legal Modals -->
    <x-ui::modal wire:model="showPrivacy" title="Privacy Policy">
        @include('shared::legal.privacy-policy')
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" x-on:click="$wire.set('showPrivacy', false)" />
        </x-slot:actions>
    </x-ui::modal>

    <x-ui::modal wire:model="showTerms" title="Terms of Service">
        @include('shared::legal.terms-of-service')
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" x-on:click="$wire.set('showTerms', false)" />
        </x-slot:actions>
    </x-ui::modal>
</x-setup::layouts.setup-wizard>
