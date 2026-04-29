<div>
    <div class="max-w-2xl mx-auto mt-8 p-6 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">{{ __('setup::wizard.department_title') }}</h1>
        
        <div class="mb-6">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <form wire:submit="saveDepartment">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.department_name') }}</label>
                    <input wire:model="name" type="text" class="w-full px-3 py-2 border rounded">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.department_code') }}</label>
                    <input wire:model="code" type="text" class="w-full px-3 py-2 border rounded">
                    @error('code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.description') }}</label>
                    <textarea wire:model="description" class="w-full px-3 py-2 border rounded" rows="3"></textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    {{ __('setup::wizard.next_step') }}
                </button>
            </div>
        </form>
    </div>
</div>
