<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\User\Models\User;

/**
 * Livewire component for managing account restrictions.
 *
 * Allows admins to view active restrictions, add new ones, and remove existing ones.
 * Includes support for time-based restrictions with auto-lift capabilities.
 */
class RestrictionManager extends Component
{
    use WithPagination;

    public User $user;

    public bool $showAddRestriction = false;

    public string $restrictionType = '';

    public string $restrictionKey = '';

    public ?string $restrictionValue = '';

    public ?string $reason = '';

    public ?string $expiresAt = '';

    public bool $autoLift = false;

    protected $rules = [
        'restrictionType' => 'required|in:module,feature,rate_limit,schedule,geolocation',
        'restrictionKey' => 'required|string|max:100',
        'restrictionValue' => 'nullable|string|max:255',
        'reason' => 'nullable|string|max:500',
        'expiresAt' => 'nullable|date',
        'autoLift' => 'boolean',
    ];

    protected $messages = [
        'restrictionType.required' => 'Tipe pembatasan harus dipilih',
        'restrictionKey.required' => 'Kunci pembatasan harus diisi',
        'restrictionValue.max' => 'Nilai tidak boleh melebihi 255 karakter',
        'reason.max' => 'Alasan tidak boleh melebihi 500 karakter',
        'expiresAt.date' => 'Format tanggal tidak valid',
    ];

    /**
     * Mount the component.
     */
    public function mount(User $user): void
    {
        $this->user = $user;

        // Check authorization
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->can('manageRestrictions', $this->user), 403);
    }

    /**
     * Get restriction type options.
     */
    public function getRestrictionTypeOptions(): array
    {
        return [
            [
                'value' => 'module',
                'label' => 'Modul',
                'description' => 'Blokir akses ke modul tertentu',
            ],
            ['value' => 'feature', 'label' => 'Fitur', 'description' => 'Blokir fitur spesifik'],
            [
                'value' => 'rate_limit',
                'label' => 'Batasan Laju',
                'description' => 'Batasi jumlah permintaan',
            ],
            [
                'value' => 'schedule',
                'label' => 'Jadwal',
                'description' => 'Izinkan hanya pada waktu tertentu',
            ],
            [
                'value' => 'geolocation',
                'label' => 'Lokasi',
                'description' => 'Batasi berdasarkan lokasi geografis',
            ],
        ];
    }

    /**
     * Get examples for restriction key based on type.
     */
    public function getKeyExamples(): array
    {
        return match ($this->restrictionType) {
            'module' => ['internship', 'student', 'company', 'placement'],
            'feature' => ['export', 'bulk_action', 'api_access', 'schedule_change'],
            'rate_limit' => ['api_calls_per_hour', 'login_attempts', 'file_uploads'],
            'schedule' => ['business_hours', 'weekdays_only', 'no_weekend'],
            'geolocation' => ['allowed_countries', 'blocked_ips', 'vpn_detection'],
            default => [],
        };
    }

    /**
     * Add a new restriction.
     */
    public function addRestriction(): void
    {
        $this->validate();

        try {
            $this->user->restrictions()->create([
                'restriction_type' => $this->restrictionType,
                'restriction_key' => $this->restrictionKey,
                'restriction_value' => $this->restrictionValue,
                'reason' => $this->reason ?: 'Pembatasan diterapkan oleh ' . auth()->user()->name,
                'applied_by_user_id' => auth()->id(),
                'applied_at' => now(),
                'expires_at' => $this->expiresAt ? Carbon::parse($this->expiresAt) : null,
                'is_active' => true,
                'metadata' => [
                    'auto_lift' => $this->autoLift,
                    'created_by_role' => auth()->user()->role,
                ],
            ]);

            flash()->success(__('Pembatasan berhasil diterapkan'));
            $this->showAddRestriction = false;
            $this->resetForm();
            $this->dispatch('restrictionAdded', userId: $this->user->id);
        } catch (\Exception $e) {
            flash()->error(__('Gagal menambah pembatasan: ' . $e->getMessage()));
        }
    }

    /**
     * Remove a restriction.
     */
    public function removeRestriction(string $restrictionId): void
    {
        try {
            $restriction = $this->user->restrictions()->findOrFail($restrictionId);

            abort_unless(auth()->user()->can('manageRestrictions', $this->user), 403);

            $restriction->update([
                'is_active' => false,
                'metadata' => array_merge($restriction->metadata ?? [], [
                    'removed_by' => auth()->id(),
                    'removed_at' => now()->toIso8601String(),
                ]),
            ]);

            flash()->success(__('Pembatasan berhasil dihapus'));
            $this->dispatch('restrictionRemoved', userId: $this->user->id);
        } catch (\Exception $e) {
            flash()->error(__('Gagal menghapus pembatasan: ' . $e->getMessage()));
        }
    }

    /**
     * Get active restrictions.
     */
    public function getActiveRestrictions()
    {
        return $this->user
            ->restrictions()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->paginate(10);
    }

    /**
     * Get expired/inactive restrictions.
     */
    public function getInactiveRestrictions()
    {
        return $this->user
            ->restrictions()
            ->where(function ($query) {
                $query->where('is_active', false)->orWhere('expires_at', '<=', now());
            })
            ->paginate(5);
    }

    /**
     * Reset the form.
     */
    private function resetForm(): void
    {
        $this->restrictionType = '';
        $this->restrictionKey = '';
        $this->restrictionValue = '';
        $this->reason = '';
        $this->expiresAt = '';
        $this->autoLift = false;
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('status::livewire.restriction-manager', [
            'activeRestrictions' => $this->getActiveRestrictions(),
            'inactiveRestrictions' => $this->getInactiveRestrictions(),
            'restrictionTypes' => $this->getRestrictionTypeOptions(),
            'keyExamples' => $this->getKeyExamples(),
            'totalActive' => $this->user->restrictions()->where('is_active', true)->count(),
        ]);
    }
}
