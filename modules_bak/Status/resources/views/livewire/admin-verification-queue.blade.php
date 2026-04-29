<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                📋 Admin Verification Queue
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                {{ $totalPending }} pending verifications
            </p>
        </div>
        <button 
            wire:click="exportPendingUsers"
            class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100"
        >
            📥 Export CSV
        </button>
    </div>

    <!-- Search and Sort Bar -->
    <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-4">
        <div class="flex gap-4">
            <div class="flex-1">
                <input 
                    type="text"
                    wire:model.debounce.300ms="searchQuery"
                    placeholder="Search by email, name, or phone..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
            <select 
                wire:model="perPage"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="10">10 per page</option>
                <option value="15">15 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </select>
        </div>

        <!-- Bulk Actions Bar -->
        @if($showBulkActionsBar)
            <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg p-3">
                <span class="text-sm font-medium text-blue-900">
                    {{ $selectedCount }} user(s) selected
                </span>
                <div class="flex gap-2">
                    <button 
                        wire:click="bulkVerify"
                        class="px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                    >
                        ✅ Verify All
                    </button>
                    <button 
                        wire:click="clearSelections"
                        class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
                    >
                        ✕ Clear
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Users Table -->
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <input 
                            type="checkbox"
                            wire:click="selectAll"
                            class="rounded"
                        />
                    </th>
                    <th class="px-4 py-3 text-left">
                        <button wire:click="sort('email')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
                            Email
                            @if($sortBy === 'email')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left">
                        <button wire:click="sort('name')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
                            Name
                            @if($sortBy === 'name')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left">
                        <button wire:click="sort('phone')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
                            Phone
                            @if($sortBy === 'phone')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left">
                        <button wire:click="sort('created_at')" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
                            Pending Since
                            @if($sortBy === 'created_at')
                                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Notes</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($pendingUsers as $user)
                    <tr class="hover:bg-gray-50 @if(in_array($user->id, $selectedUsers)) bg-blue-50 @endif">
                        <!-- Checkbox -->
                        <td class="px-4 py-3">
                            <input 
                                type="checkbox"
                                wire:click="toggleUserSelection({{ $user->id }})"
                                @checked(in_array($user->id, $selectedUsers))
                                class="rounded"
                            />
                        </td>

                        <!-- Email -->
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $user->email }}
                        </td>

                        <!-- Name -->
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $user->name ?? '—' }}
                        </td>

                        <!-- Phone -->
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $user->phone ?? '—' }}
                        </td>

                        <!-- Pending Since -->
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <span title="{{ $user->created_at->format('Y-m-d H:i') }}">
                                {{ $user->created_at->diffForHumans() }}
                            </span>
                        </td>

                        <!-- Notes Textarea -->
                        <td class="px-4 py-3">
                            <textarea 
                                wire:model="notes.{{ $user->id }}"
                                placeholder="Add notes..."
                                rows="2"
                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-right space-x-2">
                            <button 
                                wire:click="verifyUser({{ $user->id }})"
                                class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                                title="Verify this account"
                            >
                                ✅ Verify
                            </button>
                            <div class="relative group inline-block">
                                <button 
                                    class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
                                    title="Reject this account"
                                >
                                    ⊘ More
                                </button>
                                <div class="absolute right-0 mt-1 hidden group-hover:block bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                    <button 
                                        wire:click="rejectUser({{ $user->id }}, 'suspended')"
                                        class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                                    >
                                        🚫 Suspend
                                    </button>
                                    <button 
                                        wire:click="rejectUser({{ $user->id }}, 'restricted')"
                                        class="block w-full text-left px-4 py-2 text-sm text-orange-700 hover:bg-orange-50"
                                    >
                                        ⛔ Restrict
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            ✨ No pending verifications! All accounts verified.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Showing {{ $pendingUsers->firstItem() ?? 0 }} to {{ $pendingUsers->lastItem() ?? 0 }} of {{ $pendingUsers->total() }} results
        </div>
        {{ $pendingUsers->links() }}
    </div>
</div>
