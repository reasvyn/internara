<?php

declare(strict_types=1);

namespace App\Settings\Data;

use App\Core\Data\BaseData;
use Illuminate\Http\UploadedFile;

final readonly class SystemSettingsData extends BaseData
{
    public function __construct(
        public string $brandName = '',
        public string $siteTitle = '',
        public string $defaultLocale = 'id',
        public string $activeAcademicYear = '',
        public string $primaryColor = '',
        public string $secondaryColor = '',
        public string $accentColor = '',
        public string $baseColor = '',
        public ?UploadedFile $brandLogo = null,
        public ?UploadedFile $siteFavicon = null,
        public string $supportEmail = '',
        public string $mailFromAddress = '',
        public string $mailFromName = '',
        public string $mailHost = '',
        public string $mailPort = '587',
        public string $mailEncryption = 'tls',
        public string $mailUsername = '',
        public ?string $mailPassword = null,
    ) {}
}
