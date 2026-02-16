<?php

declare(strict_types=1);

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Casts\SettingValueCast;
use Modules\Setting\Models\Setting;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This method seeds the application with default settings, converting values
     * via the SettingValueCast before using a single 'upsert' operation for efficiency.
     */
    public function run(): void
    {
        $infoPath = base_path('app_info.json');
        $info = file_exists($infoPath) ? json_decode(file_get_contents($infoPath), true) : [];

        $rawSettings = [
            [
                'key' => 'app_name',
                'value' => $info['name'] ?? config('app.name', 'Internara'),
                'type' => 'string',
                'description' => 'Application name',
                'group' => 'system',
            ],
            [
                'key' => 'app_version',
                'value' => $info['version'] ?? 'Unknown',
                'type' => 'string',
                'description' => 'Application version',
                'group' => 'system',
            ],
            [
                'key' => 'app_series_code',
                'value' => $info['series_code'] ?? 'Unknown',
                'type' => 'string',
                'description' => 'Application series code',
                'group' => 'system',
            ],
            [
                'key' => 'app_status',
                'value' => $info['status'] ?? 'Unknown',
                'type' => 'string',
                'description' => 'Application development status',
                'group' => 'system',
            ],
            [
                'key' => 'app_author_name',
                'value' => $info['author']['name'] ?? 'Unknown',
                'type' => 'string',
                'description' => 'Application author name',
                'group' => 'system',
            ],
            [
                'key' => 'app_author_github',
                'value' => $info['author']['github'] ?? 'Unknown',
                'type' => 'string',
                'description' => 'Application author github',
                'group' => 'system',
            ],
            [
                'key' => 'app_author_email',
                'value' => $info['author']['email'] ?? 'Unknown',
                'type' => 'string',
                'description' => 'Application author email',
                'group' => 'system',
            ],
            [
                'key' => 'app_logo',
                'value' => config('app.logo', ''),
                'type' => 'string',
                'description' => 'Application logo',
                'group' => 'system',
            ],
            [
                'key' => 'app_installed',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Indicates whether the application is installed',
                'group' => 'system',
            ],
            [
                'key' => 'brand_name',
                'value' => config('setting.brand_name', 'Internara'),
                'type' => 'string',
                'description' => 'The name of the institute brand',
                'group' => 'general',
            ],
            [
                'key' => 'brand_logo',
                'value' => null,
                'type' => 'string',
                'description' => 'The logo of the institute brand',
                'group' => 'general',
            ],
            [
                'key' => 'brand_logo_dark',
                'value' => null,
                'type' => 'string',
                'description' => 'The logo of the institute brand',
                'group' => 'general',
            ],
            [
                'key' => 'site_title',
                'value' => config(
                    'setting.site_title',
                    'Internara - Sistem Informasi Manajemen PKL',
                ),
                'type' => 'string',
                'description' => 'The title of the site',
                'group' => 'general',
            ],
            [
                'key' => 'mail_from_address',
                'value' => 'no-reply@internara.test',
                'type' => 'string',
                'description' => 'Global outgoing mail sender address',
                'group' => 'system',
            ],
            [
                'key' => 'mail_from_name',
                'value' => '',
                'type' => 'string',
                'description' => 'Global outgoing mail sender name (leave empty to use brand name)',
                'group' => 'system',
            ],
            [
                'key' => 'mail_host',
                'value' => config('mail.mailers.smtp.host', '127.0.0.1'),
                'type' => 'string',
                'description' => 'SMTP host address',
                'group' => 'system',
            ],
            [
                'key' => 'mail_port',
                'value' => config('mail.mailers.smtp.port', '587'),
                'type' => 'string',
                'description' => 'SMTP port number',
                'group' => 'system',
            ],
            [
                'key' => 'mail_username',
                'value' => config('mail.mailers.smtp.username', ''),
                'type' => 'string',
                'description' => 'SMTP username',
                'group' => 'system',
            ],
            [
                'key' => 'mail_password',
                'value' => config('mail.mailers.smtp.password', ''),
                'type' => 'password',
                'description' => 'SMTP password',
                'group' => 'system',
            ],
            [
                'key' => 'mail_encryption',
                'value' => config('mail.mailers.smtp.encryption', 'tls'),
                'type' => 'string',
                'description' => 'SMTP encryption (tls/ssl)',
                'group' => 'system',
            ],
            [
                'key' => 'active_academic_year',
                'value' => date('Y') - 1 .'/'.date('Y'),
                'type' => 'string',
                'description' => 'The current active academic year for operational data.',
                'group' => 'operational',
            ],
            [
                'key' => 'attendance_check_in_start',
                'value' => '07:00',
                'type' => 'string',
                'description' => 'Earliest allowed check-in time.',
                'group' => 'operational',
            ],
            [
                'key' => 'attendance_late_threshold',
                'value' => '08:00',
                'type' => 'string',
                'description' => 'Time after which a student is marked as late.',
                'group' => 'operational',
            ],
        ];

        $settingsToUpsert = [];
        $caster = new SettingValueCast;
        $dummyModel = new Setting; // A dummy model instance for the caster's set method

        foreach ($rawSettings as $setting) {
            // Apply the SettingValueCast::set logic manually to each setting
            $processed = $caster->set($dummyModel, 'value', $setting['value'], [
                'type' => $setting['type'] ?? 'string',
            ]);

            $settingsToUpsert[] = array_merge($setting, [
                'value' => $processed['value'],
                'type' => $processed['type'],
            ]);
        }

        Setting::upsert($settingsToUpsert, ['key'], ['value', 'type', 'description', 'group']);
    }
}
