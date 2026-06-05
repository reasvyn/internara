<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

use App\Core\Actions\BaseAction;
use App\Enrollment\Models\AccountApplication;
use App\Exceptions\RejectedException;

final class ApplyAccountAction extends BaseAction
{
    public function execute(array $data): AccountApplication
    {
        $existing = AccountApplication::where('email', $data['email'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            throw new RejectedException('An application with this email already exists.');
        }

        return $this->transaction(function () use ($data) {
            $application = AccountApplication::create(array_merge($data, ['status' => 'pending']));

            $this->log('account_applied', $application, $data);

            return $application;
        });
    }
}
