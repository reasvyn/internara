<?php

declare(strict_types=1);

use App\Core\Data\ActionResponse;
use Illuminate\Database\Eloquent\Model;

class TestResponseModel extends Model
{
    protected $table = 'test';

    public function toArray(): array
    {
        return ['id' => 1, 'name' => 'test'];
    }
}

test('ok creates successful response with data and message', function () {
    $response = ActionResponse::ok(['user' => 1], 'Done');

    expect($response->success)->toBeTrue();
    expect($response->data)->toBe(['user' => 1]);
    expect($response->message)->toBe('Done');
});

test('ok creates successful response without data', function () {
    $response = ActionResponse::ok();

    expect($response->success)->toBeTrue();
    expect($response->data)->toBeNull();
    expect($response->message)->toBeNull();
});

test('ok creates successful response with data only', function () {
    $response = ActionResponse::ok(['id' => 1]);

    expect($response->success)->toBeTrue();
    expect($response->data)->toBe(['id' => 1]);
    expect($response->message)->toBeNull();
});

test('created response has created message', function () {
    $response = ActionResponse::created(['id' => 1]);

    expect($response->success)->toBeTrue();
    expect($response->data)->toBe(['id' => 1]);
    expect($response->message)->not->toBeNull();
});

test('created response without data', function () {
    $response = ActionResponse::created();

    expect($response->success)->toBeTrue();
    expect($response->data)->toBeNull();
});

test('updated response has updated message', function () {
    $response = ActionResponse::updated(['id' => 1]);

    expect($response->success)->toBeTrue();
    expect($response->message)->not->toBeNull();
});

test('deleted response has no data', function () {
    $response = ActionResponse::deleted();

    expect($response->success)->toBeTrue();
    expect($response->data)->toBeNull();
    expect($response->message)->not->toBeNull();
});

test('deleted response with custom message', function () {
    $response = ActionResponse::deleted('Removed successfully');

    expect($response->message)->toBe('Removed successfully');
});

test('error response has success false', function () {
    $response = ActionResponse::error('Something went wrong', ['field' => 'required']);

    expect($response->success)->toBeFalse();
    expect($response->message)->toBe('Something went wrong');
    expect($response->errors)->toBe(['field' => 'required']);
    expect($response->failed())->toBeTrue();
});

test('error response without errors', function () {
    $response = ActionResponse::error('Failed');

    expect($response->success)->toBeFalse();
    expect($response->errors)->toBe([]);
    expect($response->failed())->toBeTrue();
});

test('with redirect sets redirect url', function () {
    $response = ActionResponse::ok()->withRedirect('/dashboard');

    expect($response->redirect)->toBe('/dashboard');
});

test('with redirect preserves existing properties', function () {
    $response = ActionResponse::ok(['id' => 1], 'Done')->withRedirect('/show');

    expect($response->success)->toBeTrue();
    expect($response->data)->toBe(['id' => 1]);
    expect($response->message)->toBe('Done');
    expect($response->redirect)->toBe('/show');
});

test('json serializes correct structure', function () {
    $response = ActionResponse::ok(['id' => 1], 'Success');

    $serialized = $response->jsonSerialize();

    expect($serialized)->toHaveKey('success');
    expect($serialized)->toHaveKey('data');
    expect($serialized)->toHaveKey('message');
    expect($serialized['success'])->toBeTrue();
    expect($serialized['data'])->toBe(['id' => 1]);
});

test('json serialized omits null fields', function () {
    $response = ActionResponse::ok();

    $serialized = $response->jsonSerialize();

    expect($serialized)->toHaveKey('success');
    expect($serialized)->not->toHaveKey('data');
    expect($serialized)->not->toHaveKey('message');
});

test('json serialized omits empty errors array', function () {
    $response = ActionResponse::error('Fail', []);

    $serialized = $response->jsonSerialize();

    expect($serialized)->not->toHaveKey('errors');
});

test('json serialized includes errors when present', function () {
    $response = ActionResponse::error('Fail', ['field' => 'required']);

    $serialized = $response->jsonSerialize();

    expect($serialized)->toHaveKey('errors');
    expect($serialized['errors'])->toBe(['field' => 'required']);
});

test('json serialized includes redirect when set', function () {
    $response = ActionResponse::ok()->withRedirect('/home');

    $serialized = $response->jsonSerialize();

    expect($serialized)->toHaveKey('redirect');
    expect($serialized['redirect'])->toBe('/home');
});

test('json serialized converts model to array', function () {
    $model = new TestResponseModel;
    $response = ActionResponse::ok($model);

    $serialized = $response->jsonSerialize();

    expect($serialized['data'])->toBe(['id' => 1, 'name' => 'test']);
});

test('is immutable - withRedirect returns new instance', function () {
    $response = ActionResponse::ok('original');

    $newResponse = $response->withRedirect('/other');

    expect($response->redirect)->toBeNull();
    expect($newResponse->redirect)->toBe('/other');
    expect($response)->not->toBe($newResponse);
});

test('is immutable - chained responses are independent', function () {
    $response = ActionResponse::error('Fail');
    $withRedirect = $response->withRedirect('/retry');

    expect($response->failed())->toBeTrue();
    expect($response->redirect)->toBeNull();
    expect($withRedirect->failed())->toBeTrue();
    expect($withRedirect->redirect)->toBe('/retry');
});

test('factory methods produce correct success states', function () {
    expect(ActionResponse::ok()->success)->toBeTrue();
    expect(ActionResponse::created()->success)->toBeTrue();
    expect(ActionResponse::updated()->success)->toBeTrue();
    expect(ActionResponse::deleted()->success)->toBeTrue();
    expect(ActionResponse::error('fail')->success)->toBeFalse();
});

test('failed returns opposite of success', function () {
    expect(ActionResponse::ok()->failed())->toBeFalse();
    expect(ActionResponse::error('fail')->failed())->toBeTrue();
});
