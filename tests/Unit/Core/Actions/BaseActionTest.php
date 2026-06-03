<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Actions;

use App\Domain\Core\Actions\BaseAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // Ensure no ongoing transaction from previous test
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
});

final class TransactionTrackingAction extends BaseAction
{
    public bool $callbackCalled = false;

    public ?int $levelInside = null;

    public function execute(): mixed
    {
        return $this->transaction(function () {
            $this->callbackCalled = true;
            $this->levelInside = DB::transactionLevel();

            return $this->levelInside;
        });
    }
}

it('does not escalate transaction level when called within existing transaction', function () {
    DB::beginTransaction();

    $action = new TransactionTrackingAction;
    $levelReturned = $action->execute();

    $levelAfter = DB::transactionLevel();

    DB::rollBack();

    expect($levelReturned)->toBe(1);
    expect($levelAfter)->toBe(1);
});

it('creates transaction when called outside existing transaction', function () {
    $action = new TransactionTrackingAction;
    $levelReturned = $action->execute();

    expect($levelReturned)->toBe(1);
});
