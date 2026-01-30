<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Unit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Modules\Media\Concerns\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

uses(RefreshDatabase::class);

class MediaTestModel extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'media_test_models';

    protected $fillable = ['name'];
}

beforeEach(function () {
    Schema::create('media_test_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

test('it can attach media to a model', function () {
    $model = MediaTestModel::create(['name' => 'Test']);
    $file = UploadedFile::fake()->image('test.jpg');

    $model->setMedia($file);

    expect($model->getMedia(MediaTestModel::COLLECTION_DEFAULT))->toHaveCount(1);
});

test('it can clear existing media when setting new media', function () {
    $model = MediaTestModel::create(['name' => 'Test']);

    $model->setMedia(UploadedFile::fake()->image('first.jpg'));
    expect($model->getMedia())->toHaveCount(1);

    $model->setMedia(UploadedFile::fake()->image('second.jpg'));
    expect($model->getMedia())->toHaveCount(1);
    expect($model->getFirstMedia()->file_name)->toBe('second.jpg');
});

test('it can get media url', function () {
    $model = MediaTestModel::create(['name' => 'Test']);
    $file = UploadedFile::fake()->image('test.jpg');

    $model->setMedia($file);

    expect($model->getMediaUrl())->toContain('test.jpg');
});
