<?php

declare(strict_types=1);

use App\Partners\Partnership\Entities\PartnershipState;
use App\Partners\Partnership\Enums\PartnershipStatus;

test('partnership state detects active status', function () {
    $active = new PartnershipState(PartnershipStatus::ACTIVE, null);
    expect($active->isActive())->toBeTrue();
    expect($active->isExpired())->toBeFalse();
    expect($active->isTerminated())->toBeFalse();

    $expired = new PartnershipState(PartnershipStatus::EXPIRED, null);
    expect($expired->isActive())->toBeFalse();
    expect($expired->isExpired())->toBeTrue();

    $terminated = new PartnershipState(PartnershipStatus::TERMINATED, null);
    expect($terminated->isActive())->toBeFalse();
    expect($terminated->isTerminated())->toBeTrue();
});

test('partnership state can be deleted when expired or terminated', function () {
    $expired = new PartnershipState(PartnershipStatus::EXPIRED, null);
    expect($expired->canBeDeleted())->toBeTrue();

    $terminated = new PartnershipState(PartnershipStatus::TERMINATED, null);
    expect($terminated->canBeDeleted())->toBeTrue();

    $active = new PartnershipState(PartnershipStatus::ACTIVE, null);
    expect($active->canBeDeleted())->toBeFalse();
});

test('partnership state detects expiring soon', function () {
    $expiring = new PartnershipState(
        PartnershipStatus::ACTIVE,
        Carbon\Carbon::now()->addDays(15)->format('Y-m-d'),
    );
    expect($expiring->isExpiringSoon())->toBeTrue();
    expect($expiring->isExpiringSoon(20))->toBeTrue();

    $notExpiring = new PartnershipState(
        PartnershipStatus::ACTIVE,
        Carbon\Carbon::now()->addDays(60)->format('Y-m-d'),
    );
    expect($notExpiring->isExpiringSoon())->toBeFalse();

    $noEndDate = new PartnershipState(PartnershipStatus::ACTIVE, null);
    expect($noEndDate->isExpiringSoon())->toBeFalse();

    $expired = new PartnershipState(PartnershipStatus::EXPIRED, null);
    expect($expired->isExpiringSoon())->toBeFalse();
});
