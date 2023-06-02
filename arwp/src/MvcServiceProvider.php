<?php

namespace Arwp\Mvc;

use Illuminate\Support\ServiceProvider;

class MvcServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->mergeConfigFrom(__DIR__ . '/../resources/config/config.php', 'mvc');
            if (!file_exists(app_path('Console/Commands'))) {
                mkdir(app_path('Console/Commands'), 0777, true);
            }
            $publish = [
                __DIR__ . '/../resources/config/config.php' => config_path('mvc.php'),
                __DIR__ . '/../resources/assets/createMVC.php' => app_path('Console/Commands/createMVC.php'),
                __DIR__ . '/../resources/assets/deleteMVC.php' => app_path('Console/Commands/deleteMVC.php'),
                __DIR__ . '/../resources/routes/route.php' => base_path('routes/mvc-route.php'),
            ];
            $this->publishes($publish);
        }
    }
}
