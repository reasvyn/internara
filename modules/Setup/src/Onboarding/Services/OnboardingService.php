<?php

declare(strict_types=1);

namespace Modules\Setup\Onboarding\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Setup\Onboarding\Services\Contracts\OnboardingService as Contract;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Teacher\Services\Contracts\TeacherService;
use Modules\User\Services\Contracts\AccountProvisioningService;
use Modules\User\Services\Contracts\UserService;

/**
 * Class OnboardingService
 *
 * Provides high-level administrative orchestration for batch onboarding
 * stakeholders through CSV data processing. Each successfully created account
 * receives a one-time activation code (via AccountProvisioningService) that is
 * returned in the result for credential slip distribution.
 */
class OnboardingService implements Contract
{
    public function __construct(
        protected UserService $userService,
        protected ProfileService $profileService,
        protected StudentService $studentService,
        protected TeacherService $teacherService,
        protected AccountProvisioningService $provisioningService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function importFromCsv(string $filePath, string $type, int $expiresInDays = 30): array
    {
        $results = [
            'success' => 0,
            'failure' => 0,
            'errors' => [],
            'credentials' => [],
        ];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $results['errors'][] = __('setup::onboarding.errors.file_not_readable');

            return $results;
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            $results['errors'][] = __('setup::onboarding.errors.empty_file');
            fclose($handle);

            return $results;
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);
        $rowCount = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;

            if (empty(array_filter($row))) {
                continue;
            }

            if (count($header) !== count($row)) {
                $results['failure']++;
                $results['errors'][] = __('setup::onboarding.errors.column_mismatch', [
                    'row' => $rowCount,
                ]);

                continue;
            }

            $data = array_combine($header, $row);

            try {
                $slip = DB::transaction(fn() => $this->processRow($data, $type, $expiresInDays));

                $results['success']++;
                $results['credentials'][] = $slip;
            } catch (\Throwable $e) {
                $results['failure']++;
                $results['errors'][] = "Row {$rowCount}: " . $e->getMessage();
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
        // email is optional — accounts without email use username-only login
        $columns = ['name', 'email', 'username', 'phone', 'address', 'department_id'];

        if ($type === 'student') {
            $columns[] = 'national_identifier';
            $columns[] = 'registration_number';
        } elseif ($type === 'teacher') {
            $columns[] = 'nip';
        }

        return implode(',', $columns) . "\n";
    }

    /**
     * Create a single stakeholder account and issue an activation code.
     *
     * @param array<string, mixed> $data Raw row data from the CSV.
     * @param string $type Stakeholder role.
     * @param int $expiresInDays Activation code expiry.
     *
     * @throws \InvalidArgumentException If mandatory data is missing or invalid.
     *
     * @return array{name: string, username: string, code: string}
     */
    protected function processRow(array $data, string $type, int $expiresInDays = 30): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if (empty($name)) {
            throw new \InvalidArgumentException(__('setup::onboarding.errors.required_name'));
        }

        // Email is optional — only validate format when it is provided
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(__('setup::onboarding.errors.invalid_email'));
        }

        $userData = [
            'name' => $name,
            'email' => $email !== '' ? $email : null,
            'username' => $data['username'] ?? null,
            // A random password is set; it will be replaced when the user claims their account.
            'password' => Str::random(24),
            'profile' => [
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'department_id' => $data['department_id'] ?? null,
            ],
        ];

        if ($type === 'student') {
            $userData['profile'] = array_merge($userData['profile'], [
                'national_identifier' => $data['national_identifier'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
            ]);
            $user = $this->studentService->create($userData);
        } elseif ($type === 'teacher') {
            $userData['profile'] = array_merge($userData['profile'], [
                'national_identifier' => $data['nip'] ?? null,
            ]);
            $user = $this->teacherService->create($userData);
        } else {
            $userData['roles'] = [$type];
            $user = $this->userService->create($userData);
        }

        $plainCode = $this->provisioningService->provision(
            $user,
            AccountProvisioningService::TYPE_ACTIVATION,
            $expiresInDays,
        );

        return [
            'name' => $user->name,
            'username' => $user->username,
            'code' => $plainCode,
        ];
    }
}
