<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\HandlesActionErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic profile updates with auditing and validation.
 * S2 - Sustain: Proper error handling and logging.
 */
class UpdateProfileAction
{
    use HandlesActionErrors;

    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Execute the profile update.
     *
     * @param array<string, mixed> $data
     *
     * @throws RuntimeException when update fails
     */
    public function execute(User $user, array $data): Profile
    {
        $this->validate($data);

        $data = array_filter($data, fn ($v) => $v !== null);

        if ($data === []) {
            return $user->profile ?? $user->profile()->create([]);
        }

        return $this->withErrorHandling(function () use ($user, $data) {
            return DB::transaction(function () use ($user, $data) {
                $profile = $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $data,
                );

                $this->logAuditAction->execute(
                    action: 'profile_updated',
                    subjectType: Profile::class,
                    subjectId: $profile->id,
                    payload: array_keys($data),
                    module: 'Profile',
                );

                return $profile;
            });
        }, 'Failed to update profile');
    }

    /**
     * Validate profile data.
     *
     * @param array<string, mixed> $data
     */
    protected function validate(array $data): void
    {
        Validator::make($data, [
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'string'],
            'blood_type' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_address' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string'],
            'national_identifier' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ])->validate();
    }
}
