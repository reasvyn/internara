<?php

declare(strict_types=1);

namespace App\Modules\Core\Livewire;

use App\Modules\Core\Services\AppInfo;
use Illuminate\View\View;
use Livewire\Component;

final class AboutPage extends Component
{
    public function render(): View
    {
        $author = AppInfo::author();
        $authorGithub = is_array($author) && isset($author['github']) && is_string($author['github'])
            ? $author['github']
            : AppInfo::gitUrl();

        $appData = [
            'appName' => brand('name') ?: config('app.name', 'Internara'),
            'tagline' => brand('tagline') ?: __('common.app_tagline'),
            'version' => AppInfo::version(),
            'description' => AppInfo::description(),
            'license' => AppInfo::license() ?: 'MIT',
            'gitUrl' => AppInfo::gitUrl(),
            'authorName' => AppInfo::authorName(),
            'authorEmail' => AppInfo::authorEmail(),
            'authorGithub' => $authorGithub,
            'environment' => app()->environment(),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ];

        $diagnostic = implode("\n", [
            $appData['appName'].' System Diagnostic Snapshot',
            '------------------------------------',
            'App Name: '.$appData['appName'],
            'Version: v'.$appData['version'],
            'Environment: '.$appData['environment'],
            'PHP Version: '.$appData['phpVersion'],
            'Laravel Version: '.$appData['laravelVersion'],
            'License: '.$appData['license'],
            'Repository: '.$appData['gitUrl'],
            'Timestamp: '.now()->toIso8601String(),
        ]);

        $appData['diagnosticSnippet'] = $diagnostic;

        $layout = auth()->check() ? 'ui::layouts.app' : 'ui::layouts.guest';
        $title = __('about.about_app', ['app' => $appData['appName']]);

        return view('core.about', $appData)
            ->layout($layout, [
                'title' => $title,
                'header' => $title,
                'context' => $title,
            ]);
    }
}
