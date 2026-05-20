<?php

declare(strict_types=1);

namespace App\Domain\Registration\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Registration\Models\AccountApplication;

class ApplyAccountAction extends BaseAction
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
            $application = AccountApplication::create($data);

            $this->log('account_applied', $application, $data);

            return $application;
        });
    }
}
