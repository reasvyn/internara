<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Actions;

use App\Core\Actions\BaseAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

class TestTxModel extends Model
{
    protected $table = 'base_action_test_tx';

    public $timestamps = false;

    protected $fillable = ['label'];

    public $incrementing = true;
}

class TransactionAction extends BaseAction
{
    public function runInTransaction(callable $callback, int $attempts = 3): mixed
    {
        return $this->transaction($callback, $attempts);
    }

    public function insertLabel(string $label): void
    {
        $this->transaction(function () use ($label) {
            TestTxModel::create(['label' => $label]);
        });
    }
}

describe('BaseAction database transactions', function () {
    beforeEach(function () {
        if (! Schema::hasTable('base_action_test_tx')) {
            Schema::create('base_action_test_tx', function ($table) {
                $table->id();
                $table->string('label');
            });
        }
    });

    it('inserts data within a transaction', function () {
        $action = new TransactionAction;

        $action->insertLabel('tx_value');

        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'tx_value']);
    });

    it('supports retry on deadlock', function () {
        $action = new TransactionAction;

        $attempts = 0;
        $action->runInTransaction(function () use (&$attempts) {
            $attempts++;
            TestTxModel::create(['label' => 'attempt_'.$attempts]);

            return $attempts;
        }, 5);

        expect($attempts)->toBe(1);
        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'attempt_1']);
    });

    it('nested transaction skips outer wrapping', function () {
        $action = new TransactionAction;

        $result = $action->runInTransaction(function () use ($action) {
            TestTxModel::create(['label' => 'nested_outer']);

            return $action->runInTransaction(function () {
                TestTxModel::create(['label' => 'nested_inner']);

                return 'nested_result';
            });
        });

        expect($result)->toBe('nested_result');
        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'nested_outer']);
        $this->assertDatabaseHas('base_action_test_tx', ['label' => 'nested_inner']);
    });
});
