<?php

declare(strict_types=1);

namespace App\Modules\UI\Livewire;

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
            'appName' => config('app.name') ?: AppInfo::name(),
            'tagline' => brand('tagline') ?: __('common.app_tagline'),
            'version' => AppInfo::version(),
            'description' => AppInfo::description(),
            'authorName' => AppInfo::authorName(),
            'authorEmail' => AppInfo::authorEmail(),
            'authorGithub' => $authorGithub,
        ];

        $layout = auth()->check() ? 'ui::layouts.app' : 'ui::layouts.guest';
        $title = __('about.about_app', ['app' => $appData['appName']]);

        return view('ui::about', $appData)
            ->layout($layout, [
                'title' => $title,
                'header' => $title,
                'context' => $title,
            ]);
    }
}
