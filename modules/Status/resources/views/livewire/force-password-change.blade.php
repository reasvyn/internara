<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 @if($isExpired) bg-red-50 @else bg-yellow-50 @endif">
            @if($isExpired)
                <h2 class="text-lg font-semibold text-red-900">
                    🔐 Password Has Expired
                </h2>
                <p class="mt-1 text-sm text-red-700">
                    Your password has expired and must be changed before you can continue.
                </p>
            @else
                <h2 class="text-lg font-semibold text-yellow-900">
                    ⚠️ Password Expiring Soon
                </h2>
                <p class="mt-1 text-sm text-yellow-700">
                    Your password will expire in {{ $daysUntilExpiry }} day(s). Please change it now.
                </p>
            @endif
        </div>

        <!-- Form -->
        <form wire:submit.prevent="changePassword" class="px-6 py-4 space-y-4">
            <!-- Error Message -->
            @if($errorMessage)
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ $errorMessage }}
                </div>
            @endif

            <!-- Current Password -->
            <div>
                <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-1">
                    Current Password *
                </label>
                <div class="relative">
                    <input
                        type="{{ $showCurrentPassword ? 'text' : 'password' }}"
                        id="currentPassword"
                        wire:model.debounce="currentPassword"
                        placeholder="Enter your current password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('currentPassword') border-red-500 @enderror"
                        @if($isLoading) disabled @endif
                    />
                    <button
                        type="button"
                        wire:click="toggleCurrentPasswordVisibility"
                        class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700"
                    >
                        {{ $showCurrentPassword ? '👁️' : '👁️‍🗨️' }}
                    </button>
                </div>
                @error('currentPassword')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div>
                <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1">
                    New Password *
                </label>
                <p class="text-xs text-gray-600 mb-2">
                    Must be 12+ characters with uppercase, lowercase, number, and special character (!@#$%^&*)
                </p>
                <div class="relative">
                    <input
                        type="{{ $showPassword ? 'text' : 'password' }}"
                        id="newPassword"
                        wire:model.debounce="newPassword"
                        placeholder="Enter new password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('newPassword') border-red-500 @enderror"
                        @if($isLoading) disabled @endif
                    />
                    <button
                        type="button"
                        wire:click="togglePasswordVisibility"
                        class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700"
                    >
                        {{ $showPassword ? '👁️' : '👁️‍🗨️' }}
                    </button>
                </div>
                @error('newPassword')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="newPasswordConfirmation" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm New Password *
                </label>
                <input
                    type="password"
                    id="newPasswordConfirmation"
                    wire:model.debounce="newPasswordConfirmation"
                    placeholder="Confirm new password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('newPasswordConfirmation') border-red-500 @enderror"
                    @if($isLoading) disabled @endif
                />
                @error('newPasswordConfirmation')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Requirements Checklist -->
            <div class="p-3 bg-gray-50 rounded-lg space-y-1 text-xs">
                <p class="font-semibold text-gray-700 mb-2">Password Requirements:</p>
                <div class="flex items-center gap-2">
                    <span @class([
                        'w-4 h-4 rounded-full',
                        'bg-green-500' => strlen($newPassword) >= 12,
                        'bg-gray-300' => strlen($newPassword) < 12,
                    ])></span>
                    <span>At least 12 characters</span>
                </div>
                <div class="flex items-center gap-2">
                    <span @class([
                        'w-4 h-4 rounded-full',
                        'bg-green-500' => preg_match('/[A-Z]/', $newPassword),
                        'bg-gray-300' => !preg_match('/[A-Z]/', $newPassword),
                    ])></span>
                    <span>One uppercase letter (A-Z)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span @class([
                        'w-4 h-4 rounded-full',
                        'bg-green-500' => preg_match('/[a-z]/', $newPassword),
                        'bg-gray-300' => !preg_match('/[a-z]/', $newPassword),
                    ])></span>
                    <span>One lowercase letter (a-z)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span @class([
                        'w-4 h-4 rounded-full',
                        'bg-green-500' => preg_match('/[0-9]/', $newPassword),
                        'bg-gray-300' => !preg_match('/[0-9]/', $newPassword),
                    ])></span>
                    <span>One number (0-9)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span @class([
                        'w-4 h-4 rounded-full',
                        'bg-green-500' => preg_match('/[!@#$%^&*]/', $newPassword),
                        'bg-gray-300' => !preg_match('/[!@#$%^&*]/', $newPassword),
                    ])></span>
                    <span>One special character (!@#$%^&*)</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 pt-4 flex gap-3">
                <button
                    type="submit"
                    @if($isLoading) disabled @endif
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                >
                    @if($isLoading)
                        ⏳ Updating...
                    @else
                        🔐 Change Password
                    @endif
                </button>
            </div>

            @if(!$isExpired)
                <p class="text-xs text-gray-500 text-center">
                    You can skip this for now, but you must change it within {{ $daysUntilExpiry }} day(s).
                </p>
            @else
                <p class="text-xs text-red-600 text-center font-semibold">
                    ⚠️ You must change your password to continue.
                </p>
            @endif
        </form>
    </div>
</div>
