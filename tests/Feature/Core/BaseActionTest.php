<?php

declare(strict_types=1);

use App\Domain\Core\Actions\BaseAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('BaseAction execute transaction successfully', function () {
    $action = new class extends BaseAction
    {
        public function execute()
        {
            return $this->transaction(fn () => 'result');
        }
    };

    expect($action->execute())->toBe('result');
});

test('BaseAction logging runs successfully without throwing exceptions', function () {
    $action = new class extends BaseAction
    {
        public function execute()
        {
            $this->log('test_action', null, ['foo' => 'bar']);
        }
    };

    $action->execute();
    expect(true)->toBeTrue();
});
