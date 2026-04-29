<div class="space-y-6">
    {{-- Status Overview Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
            $statuses = [
                ['key' => 'provisioned', 'label' => 'Menunggu Aktivasi', 'color' => 'gray'],
                ['key' => 'activated', 'label' => 'Diaktifkan', 'color' => 'blue'],
                ['key' => 'verified', 'label' => 'Diverifikasi', 'color' => 'green'],
                ['key' => 'protected', 'label' => 'Terlindungi', 'color' => 'purple'],
                ['key' => 'restricted', 'label' => 'Dibatasi', 'color' => 'yellow'],
                ['key' => 'suspended', 'label' => 'Disuspensi', 'color' => 'red'],
                ['key' => 'inactive', 'label' => 'Tidak Aktif', 'color' => 'orange'],
                ['key' => 'archived', 'label' => 'Diarsipkan', 'color' => 'slate'],
            ];
        @endphp

        @foreach ($statuses as $status)
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-gray-600 text-sm font-medium">{{ $status['label'] }}</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $statusStats[$status['key']] ?? 0 }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ round(($statusStats[$status['key']] ?? 0) / max(1, $totalUsers) * 100, 1) }}%
                </p>
            </div>
        @endforeach
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="#pending-verification" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-blue-900">{{ count($pendingVerification) }}</p>
                    <p class="text-sm text-blue-700">Menunggu Verifikasi</p>
                </div>
            </div>
        </a>

        <a href="#locked-out" class="bg-red-50 border border-red-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-red-900">{{ count($lockedOutAccounts) }}</p>
                    <p class="text-sm text-red-700">Terkunci</p>
                </div>
            </div>
        </a>

        <a href="#suspended" class="bg-orange-50 border border-orange-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-orange-900">{{ count($suspendedAccounts) }}</p>
                    <p class="text-sm text-orange-700">Disuspensi</p>
                </div>
            </div>
        </a>

        <a href="#idle" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-yellow-900">{{ count($idleApproachingInactive) }}</p>
                    <p class="text-sm text-yellow-700">Menunggu Inaktif</p>
                </div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Pending Verification --}}
        <div id="pending-verification" class="lg:col-span-1 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Menunggu Verifikasi
            </h3>
            <div class="space-y-3">
                @forelse ($pendingVerification as $user)
                    <div class="p-3 bg-blue-50 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors"
                        wire:click="viewUser('{{ $user['id'] }}')">
                        <p class="font-medium text-gray-900">{{ $user['name'] }}</p>
                        <p class="text-xs text-gray-600">{{ $user['email'] }}</p>
                        <p class="text-xs text-blue-600 mt-1">{{ $user['daysWaiting'] }} hari menunggu</p>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-4">Tidak ada yang menunggu</p>
                @endforelse
            </div>
        </div>

        {{-- Locked Out Accounts --}}
        <div id="locked-out" class="lg:col-span-1 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
                Terkunci
            </h3>
            <div class="space-y-3">
                @forelse ($lockedOutAccounts as $user)
                    <div class="p-3 bg-red-50 rounded-lg cursor-pointer hover:bg-red-100 transition-colors"
                        wire:click="viewUser('{{ $user['id'] }}')">
                        <p class="font-medium text-gray-900">{{ $user['name'] }}</p>
                        <p class="text-xs text-gray-600">{{ $user['email'] }}</p>
                        @if ($user['expiresAt'])
                            <p class="text-xs text-red-600 mt-1">Unlock: {{ $user['expiresAt'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-4">Tidak ada yang terkunci</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Changes --}}
        <div class="lg:col-span-1 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Perubahan Terbaru</h3>
            <div class="space-y-3">
                @forelse ($recentChanges as $change)
                    <div class="p-3 bg-gray-50 rounded-lg text-sm">
                        <p class="font-medium text-gray-900">{{ $change['userName'] }}</p>
                        <p class="text-xs text-gray-600">
                            {{ $change['oldStatus'] ?? 'Initial' }} → {{ $change['newStatus'] }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">{{ $change['changedAt'] }}</p>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-4">Tidak ada perubahan</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Suspended and Idle Accounts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Suspended --}}
        <div id="suspended" class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Akun Disuspensi</h3>
            <div class="space-y-3">
                @forelse ($suspendedAccounts as $user)
                    <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg cursor-pointer hover:shadow-md transition-shadow"
                        wire:click="viewUser('{{ $user['id'] }}')">
                        <p class="font-medium text-gray-900">{{ $user['name'] }}</p>
                        <p class="text-sm text-gray-600">{{ $user['email'] }}</p>
                        <p class="text-xs text-orange-600 mt-2">Disuspensi {{ $user['daysSuspended'] }} hari lalu</p>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-8">Tidak ada akun yang disuspensi</p>
                @endforelse
            </div>
        </div>

        {{-- Idle Approaching Inactive --}}
        <div id="idle" class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Menunggu Otomatis Tidak Aktif</h3>
            <div class="space-y-3">
                @forelse ($idleApproachingInactive as $user)
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg cursor-pointer hover:shadow-md transition-shadow"
                        wire:click="viewUser('{{ $user['id'] }}')">
                        <p class="font-medium text-gray-900">{{ $user['name'] }}</p>
                        <p class="text-sm text-gray-600">{{ $user['email'] }}</p>
                        <p class="text-xs text-yellow-600 mt-2">
                            Tidak aktif: {{ $user['daysUntilInactive'] }} hari
                        </p>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm text-center py-8">Semua akun aktif</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
