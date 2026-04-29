<div>
    <div class="max-w-2xl mx-auto mt-8 p-6 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">{{ __('setup::wizard.complete_title') }}</h1>
        
        <div class="mb-6">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <div class="mb-6 p-4 bg-gray-50 rounded">
            <h2 class="font-semibold mb-2">{{ __('setup::wizard.summary') }}</h2>
            <p><strong>{{ __('setup::wizard.school') }}:</strong> {{ $schoolName }}</p>
            <p><strong>{{ __('setup::wizard.setup_id') }}:</strong> {{ $setup->id }}</p>
        </div>

        <form wire:submit="completeSetup">
            <div class="space-y-4 mb-6">
                <label class="flex items-center">
                    <input wire:model="dataVerified" type="checkbox" class="mr-2">
                    <span class="text-sm">{{ __('setup::wizard.verify_data') }}</span>
                </label>
                @error('dataVerified') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                <label class="flex items-center">
                    <input wire:model="securityAware" type="checkbox" class="mr-2">
                    <span class="text-sm">{{ __('setup::wizard.security_aware') }}</span>
                </label>
                @error('securityAware') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                <label class="flex items-center">
                    <input wire:model="legalAgreed" type="checkbox" class="mr-2">
                    <span class="text-sm">{{ __('setup::wizard.legal_agree') }}</span>
                </label>
                @error('legalAgreed') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 text-red-800 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    {{ __('setup::wizard.finalize_setup') }}
                </button>
            </div>
        </form>
    </div>
</div>
