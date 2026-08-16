<?php

declare(strict_types=1);

namespace App\User\Profile\Data;

use App\Core\Data\BaseData;
use Illuminate\Http\UploadedFile;

final readonly class UpdateProfileData extends BaseData
{
    public function __construct(
        public int $userId,
        public array $profile,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $username = null,
        public ?UploadedFile $avatar = null,
    ) {}
}
