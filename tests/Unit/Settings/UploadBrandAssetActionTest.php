<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\UploadBrandAssetAction;

describe('UploadBrandAssetAction', function () {
    it('is instantiable', function () {
        $action = app(UploadBrandAssetAction::class);

        expect($action)->toBeInstanceOf(UploadBrandAssetAction::class);
    });

    it('has execute method', function () {
        expect(method_exists(UploadBrandAssetAction::class, 'execute'))->toBeTrue();
    });
});
