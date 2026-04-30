<div>
    <div class="max-w-2xl mx-auto mt-8 p-6 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">{{ __('setup::wizard.welcome_title') }}</h1>
        
        <div class="mb-6">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
            <p class="text-sm text-gray-600 mt-2">{{ __('setup::wizard.progress', ['progress' => $progress]) }}</p>
        </div>

        @if($hasErrors)
            <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded mb-6">
                <p class="font-bold">{{ __('setup::wizard.requirements_not_met') }}</p>
            </div>
        @endif

        <h2 class="text-xl font-semibold mb-4">{{ __('setup::wizard.system_requirements') }}</h2>

        <div class="space-y-3 mb-6">
            @foreach($requirements as $req)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm">{{ $req['name'] }}</span>
                    @if($req['status'])
                        <span class="text-green-600">✓ {{ $req['value'] }}</span>
                    @else
                        <span class="text-red-600">✗ {{ $req['value'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <h2 class="text-xl font-semibold mb-4">{{ __('setup::wizard.permissions') }}</h2>

        <div class="space-y-3 mb-6">
            @foreach($permissions as $perm)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm">{{ $perm['name'] }}</span>
                    @if($perm['status'])
                        <span class="text-green-600">✓ Writable</span>
                    @else
                        <span class="text-red-600">✗ Not writable</span>
                    @endif
                </div>
            @endforeach
        </div>

        <h2 class="text-xl font-semibold mb-4">{{ __('setup::wizard.database') }}</h2>

        <div class="space-y-3 mb-6">
            @foreach($database as $db)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-sm">{{ $db['name'] }}</span>
                    @if($db['status'])
                        <span class="text-green-600">✓ Connected</span>
                    @else
                        <span class="text-red-600">✗ {{ $db['value'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex justify-end">
            <button wire:click="nextStep" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                {{ __('setup::wizard.next_step') }}
            </button>
        </div>
    </div>
</div>
