<?php

declare(strict_types=1);

namespace App\Enrollment\AccountApplication\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Models\AccountApplication;

final class ApplyAccountAction extends BaseAction
{
    public function execute(array $data): AccountApplication
    {
        $existing = AccountApplication::where('email', $data['email'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            throw new RejectedException(__('registration.application_exists'));
        }

        return $this->transaction(function () use ($data) {
            $application = AccountApplication::create(array_merge($data, ['status' => 'pending']));

            $this->log('account_applied', $application, $data);

            return $application;
        });
    }
}
