<?php

declare(strict_types=1);

use App\Domain\Core\Http\Concerns\RespondsWithHttp;

describe('RespondsWithHttp', function () {
    beforeEach(function () {
        $this->responder = new class
        {
            use RespondsWithHttp {
                respondSuccess as public;
                respondCreated as public;
                respondError as public;
                respondNoContent as public;
                respondValidationError as public;
            }
        };
    });

    it('respondSuccess returns 200 with message and data', function () {
        $response = $this->responder->respondSuccess(['id' => 1]);

        expect($response->status())->toBe(200)
            ->and($response->getData(true))->toMatchArray([
                'message' => 'OK',
                'data' => ['id' => 1],
            ]);
    });

    it('respondCreated returns 201', function () {
        $response = $this->responder->respondCreated(['id' => 1]);

        expect($response->status())->toBe(201)
            ->and($response->getData(true))->toHaveKey('data');
    });

    it('respondError returns 400 with custom message', function () {
        $response = $this->responder->respondError('Bad request');

        expect($response->status())->toBe(400)
            ->and($response->getData(true)['message'])->toBe('Bad request');
    });

    it('respondError accepts custom status code', function () {
        $response = $this->responder->respondError('Not found', 404);

        expect($response->status())->toBe(404);
    });

    it('respondError includes errors when provided', function () {
        $response = $this->responder->respondError('Validation failed', 422, ['field' => 'required']);

        expect($response->getData(true)['errors'])->toHaveKey('field');
    });

    it('respondNoContent returns 204 with null body', function () {
        $response = $this->responder->respondNoContent();

        expect($response->status())->toBe(204);
    });

    it('respondValidationError returns 422 with message and errors', function () {
        $response = $this->responder->respondValidationError('Invalid input', ['email' => 'The email field is required.']);

        expect($response->status())->toBe(422)
            ->and($response->getData(true))->toMatchArray([
                'message' => 'Invalid input',
                'errors' => ['email' => 'The email field is required.'],
            ]);
    });
});
