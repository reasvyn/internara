<?php

declare(strict_types=1);

use App\Partners\Company\Events\CompanyCreated;
use App\Partners\Company\Events\CompanyDeleted;
use App\Partners\Company\Events\CompanyUpdated;
use App\Partners\Company\Models\Company;

function makeCompany(string $id): Company
{
    $model = new class extends Company {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('company created event name and payload', function () {
    $event = new CompanyCreated(makeCompany('co-1'));

    expect($event->company->id)->toBe('co-1');
    expect($event->eventName())->toBe('company.created');
    expect($event->toPayload())->toHaveKey('company_id');
});

test('company updated event name and payload', function () {
    $event = new CompanyUpdated(makeCompany('co-2'));

    expect($event->company->id)->toBe('co-2');
    expect($event->eventName())->toBe('company.updated');
    expect($event->toPayload())->toHaveKey('company_id');
});

test('company deleted event name and payload', function () {
    $event = new CompanyDeleted(makeCompany('co-3'));

    expect($event->company->id)->toBe('co-3');
    expect($event->eventName())->toBe('company.deleted');
    expect($event->toPayload())->toHaveKey('company_id');
});
