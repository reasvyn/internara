<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\AppException;
use App\Domain\Core\Models\BaseModel;
use App\Domain\User\Models\User;

arch('AppException uses strict types')
    ->expect(AppException::class)
    ->toUseStrictTypes();

arch('BaseModel uses strict types')
    ->expect(BaseModel::class)
    ->toUseStrictTypes();

arch('User uses strict types')
    ->expect(User::class)
    ->toUseStrictTypes();

arch('AppException has no debug functions')
    ->expect(AppException::class)
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die']);
