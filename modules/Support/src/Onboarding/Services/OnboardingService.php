<?php

declare(strict_types=1);

namespace Modules\Support\Onboarding\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Support\Onboarding\Services\Contracts\OnboardingService as Contract;
use Modules\Teacher\Services\Contracts\TeacherService;
use Modules\User\Services\Contracts\UserService;

/**
 * Class OnboardingService
 *
 * Provides high-level administrative orchestration for batch onboarding
 * stakeholders through CSV data processing.
 */
class OnboardingService implements Contract
{
    /**
     * Create a new onboarding service instance.
     */
    public function __construct(
        protected UserService $userService,
        protected ProfileService $profileService,
        protected StudentService $studentService,
        protected TeacherService $teacherService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function importFromCsv(string $filePath, string $type): array
    {
        $results = [
            'success' => 0,
            'failure' => 0,
            'errors' => [],
        ];

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            $results['errors'][] = __('support::onboarding.errors.file_not_readable');

            return $results;
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            $results['errors'][] = __('support::onboarding.errors.empty_file');
            fclose($handle);

            return $results;
        }

        // Normalize header keys for consistent mapping
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $rowCount = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;

            // Skip empty rows to prevent unnecessary processing
            if (empty(array_filter($row))) {
                continue;
            }

            if (count($header) !== count($row)) {
                $results['failure']++;
                $results['errors'][] = __('support::onboarding.errors.column_mismatch', [
                    'row' => $rowCount,
                ]);

                continue;
            }

            $data = array_combine($header, $row);

            try {
                DB::transaction(function () use ($data, $type) {
                    $this->processRow($data, $type);
                });
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failure']++;
                $results['errors'][] = "Row {$rowCount}: ".$e->getMessage();
            }
        }

        fclose($handle);

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate(string $type): string
    {
        $columns = ['name', 'email', 'username', 'password', 'phone', 'address', 'department_id'];

        if ($type === 'student') {
            $columns[] = 'national_identifier';
            $columns[] = 'registration_number';
        } elseif ($type === 'teacher') {
            $columns[] = 'nip';
        }

        return implode(',', $columns)."\n";
    }

    /**
     * Orchestrates the creation of a single stakeholder identity and its profile.
     *
     * @param array<string, mixed> $data Raw row data from the CSV.
     * @param string $type The designated stakeholder role.
     *
     * @throws \InvalidArgumentException If mandatory data is missing or invalid.
     */
    protected function processRow(array $data, string $type): void
    {
        // 1. Data Integrity Pre-check
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;

        if (empty($name) || empty($email)) {
            throw new \InvalidArgumentException(__('support::onboarding.errors.required_fields'));
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(__('support::onboarding.errors.invalid_email'));
        }

        // 2. Prepare Unified User Data structure
        $userData = [
            'name' => $name,
            'email' => $email,
            'username' => $data['username'] ?? null,
            'password' => $data['password'] ?? Str::random(12),
            'roles' => [$type],
            'profile' => [
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'department_id' => $data['department_id'] ?? null,
            ],
        ];

        // 3. Create User via Service (Orchestrates User, Auth, and Profile initialization)
        $user = $this->userService->create($userData);

        // 4. Update Stakeholder-specific attributes via Domain Services
        $profile = $user->profile;
        if ($profile && $profile->profileable_id) {
            if ($type === 'student') {
                $studentData = [];
                if (! empty($data['national_identifier'])) {
                    $studentData['national_identifier'] = $data['national_identifier'];
                }
                if (! empty($data['registration_number'])) {
                    $studentData['registration_number'] = $data['registration_number'];
                }

                if (! empty($studentData)) {
                    $this->studentService->update($profile->profileable_id, $studentData);
                }
            } elseif ($type === 'teacher' && ! empty($data['nip'])) {
                $this->teacherService->update($profile->profileable_id, ['nip' => $data['nip']]);
            }
        }
    }
}
