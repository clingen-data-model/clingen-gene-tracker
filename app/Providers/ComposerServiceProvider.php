<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            if (Auth::guest()) {
                $user = [
                    'roles' => [],
                    'expert_panels' => [],
                    'permissions' => []
                ];
                $view->with('user', compact('user'));

                return;
            }

            $userModel = Auth::user();
            $userModel->load(['roles', 'expertPanels']);
            $user = $userModel->toArray();
            $user['permissions'] = $userModel->getAllPermissions()->toArray();
            $user['panel_summary'] = $userModel->panel_summary;
            $view->with('user', compact('user'));
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }
}
