<?php

declare(strict_types=1);

use App\Modules\Core\Data\ActionResponse;
use Illuminate\Support\Collection;

describe('SE5Q9: action response', function (): void {
    test('SE5Q9-FR-BASE-013: ok carries data and message as success', function (): void {
        $response = ActionResponse::ok(['id' => 1], 'Placed');

        expect($response->success)->toBeTrue();
        expect($response->failed())->toBeFalse();
        expect($response->data)->toBe(['id' => 1]);
        expect($response->message)->toBe('Placed');
    });

    test('SE5Q9-FR-BASE-013: created/updated/deleted default to translated messages', function (): void {
        app()->setLocale('en');

        expect(ActionResponse::created()->message)->toBe('Created successfully.');
        expect(ActionResponse::updated()->message)->toBe('Updated successfully.');
        expect(ActionResponse::deleted()->message)->toBe('Deleted successfully.');
        expect(ActionResponse::created(null, 'Custom')->message)->toBe('Custom');
    });

    test('SE5Q9-FR-BASE-013: error marks failure with message and errors', function (): void {
        $response = ActionResponse::error('Quota full', ['company_id' => ['Full']]);

        expect($response->success)->toBeFalse();
        expect($response->failed())->toBeTrue();
        expect($response->message)->toBe('Quota full');
        expect($response->errors)->toBe(['company_id' => ['Full']]);
    });

    test('SE5Q9-FR-BASE-013: withRedirect preserves the payload and adds a target', function (): void {
        $response = ActionResponse::ok(['id' => 1], 'Placed')->withRedirect('/placements');

        expect($response->success)->toBeTrue();
        expect($response->data)->toBe(['id' => 1]);
        expect($response->redirect)->toBe('/placements');
    });

    test('SE5Q9-FR-BASE-013: serialization drops nulls and empties, converts arrayables', function (): void {
        $json = ActionResponse::ok(new Collection(['a', 'b']))->jsonSerialize();

        expect($json)->toBe(['success' => true, 'data' => ['a', 'b']]);
        expect(ActionResponse::error('Nope')->jsonSerialize())->not->toHaveKey('data');
        expect(ActionResponse::error('Nope')->jsonSerialize())->not->toHaveKey('redirect');
    });
});
