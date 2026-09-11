<?php

declare(strict_types=1);

use App\Modules\Document\Domain\Handbook\Entities\HandbookEntity;
use App\Modules\Document\Domain\Handbook\Enums\HandbookAudience;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role as RoleModel;

final class HandbookEntityModelDouble extends Model
{
    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';
}

describe('ZUFG8: handbook entity', function (): void {
    $makeUser = function (array $roles): User {
        $user = new User([
            'name' => 'Reader',
            'email' => 'reader@test.local',
            'username' => 'reader',
            'status' => AccountStatus::VERIFIED,
        ]);
        $user->setRelation(
            'roles',
            new EloquentCollection(array_map(
                fn (string $role): RoleModel => new RoleModel(['name' => $role, 'guard_name' => 'web']),
                $roles,
            ))
        );

        return $user;
    };

    test('ZUFG8-FR-HAND-007: fromModel bridges metadata, media, and timestamps without persisting', function (): void {
        $model = new HandbookEntityModelDouble([
            'id' => 'hb-1',
            'title' => 'Student Guide',
            'version' => 3,
            'is_active' => true,
            'metadata' => ['target_audience' => 'student', 'description' => 'Guide book'],
        ]);
        $model->setAttribute('created_at', Carbon::parse('2026-02-01 09:00:00'));
        $model->setRelation('media', new EloquentCollection([new HandbookEntityModelDouble]));

        $entity = HandbookEntity::fromModel($model);

        expect($entity->id())->toBe('hb-1');
        expect($entity->title())->toBe('Student Guide');
        expect($entity->version())->toBe(3);
        expect($entity->audience())->toBe(HandbookAudience::STUDENT);
        expect($entity->description())->toBe('Guide book');
        expect($entity->isAvailable())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('ZUFG8-FR-HAND-003: fromModel falls back to the all-reader audience on unknown values', function (): void {
        $model = new HandbookEntityModelDouble([
            'id' => 'hb-2',
            'title' => 'General Notice',
            'version' => null,
            'is_active' => false,
            'metadata' => ['target_audience' => 'not-an-audience'],
        ]);
        $model->setRelation('media', new EloquentCollection);

        $entity = HandbookEntity::fromModel($model);

        expect($entity->audience())->toBe(HandbookAudience::ALL);
        expect($entity->version())->toBe(1);
        expect($entity->description())->toBeNull();
        expect($entity->isAvailable())->toBeFalse();
        expect($model->exists)->toBeFalse();
    });

    test('ZUFG8-FR-HAND-006: isTargetedAt matches audience roles and opens all-reader handbooks', function () use ($makeUser): void {
        $student = $makeUser(['student']);
        $teacher = $makeUser(['teacher']);

        $forAll = HandbookEntity::fromArray([
            'id' => 'hb-1', 'title' => 'Notice', 'version' => 1, 'isActive' => true,
            'audience' => HandbookAudience::ALL, 'description' => null, 'hasFile' => true, 'createdAt' => null,
        ]);
        $forStudents = HandbookEntity::fromArray([
            'id' => 'hb-2', 'title' => 'Guide', 'version' => 1, 'isActive' => true,
            'audience' => HandbookAudience::STUDENT, 'description' => null, 'hasFile' => true, 'createdAt' => null,
        ]);

        expect($forAll->isTargetedAt(null))->toBeTrue();
        expect($forAll->isTargetedAt($student))->toBeTrue();
        expect($forStudents->isTargetedAt(null))->toBeFalse();
        expect($forStudents->isTargetedAt($student))->toBeTrue();
        expect($forStudents->isTargetedAt($teacher))->toBeFalse();
    });

    test('ZUFG8-FR-HAND-010: isNewerThan compares handbook version against the acknowledgment', function (): void {
        $entity = HandbookEntity::fromArray([
            'id' => 'hb-1', 'title' => 'Guide', 'version' => 3, 'isActive' => true,
            'audience' => HandbookAudience::ALL, 'description' => null, 'hasFile' => true, 'createdAt' => null,
        ]);
        $oldAck = new Activity;
        $oldAck->setAttribute('properties', ['version' => 2]);
        $sameAck = new Activity;
        $sameAck->setAttribute('properties', ['version' => 3]);
        $newAck = new Activity;
        $newAck->setAttribute('properties', ['version' => 5]);

        expect($entity->isNewerThan(null))->toBeTrue();
        expect($entity->isNewerThan($oldAck))->toBeTrue();
        expect($entity->isNewerThan($sameAck))->toBeFalse();
        expect($entity->isNewerThan($newAck))->toBeFalse();
    });

    test('ZUFG8-FR-HAND-007: isAvailable needs both the active flag and a stored file', function (): void {
        $base = ['id' => 'hb-1', 'title' => 'G', 'version' => 1, 'audience' => HandbookAudience::ALL, 'description' => null, 'createdAt' => null];

        expect(HandbookEntity::fromArray([...$base, 'isActive' => true, 'hasFile' => true])->isAvailable())->toBeTrue();
        expect(HandbookEntity::fromArray([...$base, 'isActive' => false, 'hasFile' => true])->isAvailable())->toBeFalse();
        expect(HandbookEntity::fromArray([...$base, 'isActive' => true, 'hasFile' => false])->isAvailable())->toBeFalse();
    });

    test('ZUFG8-FR-HAND-001: fromArray rejects a missing title', function (): void {
        expect(fn (): HandbookEntity => HandbookEntity::fromArray([
            'id' => 'hb-1', 'version' => 1, 'isActive' => true,
            'audience' => HandbookAudience::ALL, 'description' => null, 'hasFile' => false, 'createdAt' => null,
        ]))->toThrow(InvalidArgumentException::class, 'title');
    });

    test('ZUFG8-FR-HAND-007: equals and with round-trip by value', function (): void {
        $base = ['id' => 'hb-1', 'title' => 'G', 'version' => 1, 'isActive' => true, 'audience' => HandbookAudience::ALL, 'description' => null, 'hasFile' => true, 'createdAt' => null];
        $entity = HandbookEntity::fromArray($base);

        expect($entity->equals(HandbookEntity::fromArray($base)))->toBeTrue();
        expect($entity->canBeDeleted())->toBeTrue();

        $v2 = $entity->with('version', 2);

        expect($v2->version())->toBe(2);
        expect($v2->isNewerThan(null))->toBeTrue();
        expect($entity->version())->toBe(1);
    });
});
