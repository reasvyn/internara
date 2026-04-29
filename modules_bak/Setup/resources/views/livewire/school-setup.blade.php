<div>
    <div class="max-w-2xl mx-auto mt-8 p-6 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">{{ __('setup::wizard.school_title') }}</h1>
        
        <div class="mb-6">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <form wire:submit="saveSchool">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.school_name') }}</label>
                    <input wire:model="name" type="text" class="w-full px-3 py-2 border rounded">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.school_type') }}</label>
                    <select wire:model="type" class="w-full px-3 py-2 border rounded">
                        <option value="">{{ __('setup::wizard.select_type') }}</option>
                        <option value="university">{{ __('setup::wizard.type_university') }}</option>
                        <option value="polytechnic">{{ __('setup::wizard.type_polytechnic') }}</option>
                        <option value="school">{{ __('setup::wizard.type_school') }}</option>
                        <option value="college">{{ __('setup::wizard.type_college') }}</option>
                    </select>
                    @error('type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.address') }}</label>
                    <textarea wire:model="address" class="w-full px-3 py-2 border rounded" rows="3"></textarea>
                    @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.phone') }}</label>
                    <input wire:model="phone" type="text" class="w-full px-3 py-2 border rounded">
                    @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.email') }}</label>
                    <input wire:model="email" type="email" class="w-full px-3 py-2 border rounded">
                    @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('setup::wizard.website') }}</label>
                    <input wire:model="website" type="url" class="w-full px-3 py-2 border rounded">
                    @error('website') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
