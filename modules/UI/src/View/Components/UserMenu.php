<?php

declare(strict_types=1);

namespace Modules\UI\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class UserMenu extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        $user = Auth::user();

        return view('ui::components.user-menu', [
            'user' => $user,
            'role' => $user?->roles->first()?->name ?? __('ui::common.guest'), // Fallback text should be localized
            'hasLogin' => Route::has('login'),
            'hasRegister' => Route::has('register'),
            'loginRoute' => Route::has('login') ? route('login') : '#',
            'registerRoute' => Route::has('register') ? route('register') : '#',
            'profileRoute' => Route::has('profile.edit') ? route('profile.edit') : '#',
            'logoutRoute' => Route::has('logout') ? route('logout') : '#',
        ]);
    }
}
