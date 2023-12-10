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
        $this->commands(MvcBuilder::class, MvcDestroy::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->mergeConfigFrom(__DIR__ . '/resources/config/config.php', 'mvc');
            $publish = [
                __DIR__ . '/resources/config/config.php' => config_path('mvc.php'),
                __DIR__ . '/resources/routes/route.php' => base_path('routes/mvc-route.php'),
            ];
            $this->publishes($publish);
        }
    }
}
