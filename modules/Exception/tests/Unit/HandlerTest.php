<?php

declare(strict_types=1);

namespace Modules\Exception\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFound;
use Modules\Exception\Handler;
use Modules\Exception\RecordNotFoundException;

describe('Exception Handler Transformation (Genesis Contract)', function () {
    test(
        'it transforms Eloquent ModelNotFoundException into domain RecordNotFoundException',
        function () {
            $infrastructureException = new EloquentModelNotFound()->setModel(
                'Modules\User\Models\User',
                ['uuid-123'],
            );

            $transformed = Handler::map($infrastructureException);

            expect($transformed)
                ->toBeInstanceOf(RecordNotFoundException::class)
                ->and($transformed->uuid)
                ->toBe('uuid-123')
                ->and($transformed->module)
                ->toBe('user');
        },
    );

    test('it preserves other exceptions as is', function () {
        $generic = new \RuntimeException('Something went wrong');
        $transformed = Handler::map($generic);

        expect($transformed)->toBe($generic);
    });
});
