<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Casts\SettingValueCast;
use App\Domain\Core\Models\Setting;
use App\Domain\Core\Support\AppInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the database with default system settings.
 *
 * S2 - Sustain: Ensures the application has sensible defaults after installation.
 */
class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $info = AppInfo::all();

        $rawSettings = [
            // System group - app metadata
            [
                'key' => 'app_name',
                'value' => $info['name'] ?? 'Internara',
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
                'value' => '',
                'type' => 'string',
                'description' => 'Application logo URL',
                'group' => 'system',
            ],
            [
                'key' => 'app_installed',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Indicates whether the application is installed',
                'group' => 'system',
            ],

            // General group - branding
            [
                'key' => 'brand_name',
                'value' => 'Internara',
                'type' => 'string',
                'description' => 'The name of the institute brand',
                'group' => 'general',
            ],
            [
                'key' => 'brand_logo',
                'value' => null,
                'type' => 'string',
                'description' => 'The logo of the institute brand (URL)',
                'group' => 'general',
            ],
            [
                'key' => 'brand_logo_dark',
                'value' => null,
                'type' => 'string',
                'description' => 'The dark mode logo of the institute brand (URL)',
                'group' => 'general',
            ],
            [
                'key' => 'site_title',
                'value' => 'Internara - Sistem Informasi Manajemen PKL',
                'type' => 'string',
                'description' => 'The title of the site (browser tab)',
                'group' => 'general',
            ],
            [
                'key' => 'default_locale',
                'value' => 'id',
                'type' => 'string',
                'description' => 'Default application language',
                'group' => 'general',
            ],

            // System group - mail
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
                'value' => '127.0.0.1',
                'type' => 'string',
                'description' => 'SMTP host address',
                'group' => 'system',
            ],
            [
                'key' => 'mail_port',
                'value' => '587',
                'type' => 'string',
                'description' => 'SMTP port number',
                'group' => 'system',
            ],
            [
                'key' => 'mail_username',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP username',
                'group' => 'system',
            ],
            [
                'key' => 'mail_password',
                'value' => '',
                'type' => 'string',
                'description' => 'SMTP password',
                'group' => 'system',
            ],
            [
                'key' => 'mail_encryption',
                'value' => 'tls',
                'type' => 'string',
                'description' => 'SMTP encryption (tls/ssl/none)',
                'group' => 'system',
            ],

            // Operational group
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

        // Process values through the cast to ensure proper type storage
        $caster = new SettingValueCast;
        $dummyModel = new Setting;

        $settingsToUpsert = [];

        foreach ($rawSettings as $setting) {
            $processed = $caster->set($dummyModel, 'value', $setting['value'], [
                'type' => $setting['type'] ?? 'string',
            ]);

            $settingsToUpsert[] = [
                'id' => (string) Str::uuid(),
                ...array_merge($setting, [
                    'value' => $processed['value'],
                    'type' => $processed['type'],
                ]),
            ];
        }

        Setting::upsert($settingsToUpsert, ['key'], ['value', 'type', 'description', 'group']);
    }
}
