<?php

namespace Modules\Akademik\Providers;

use Illuminate\Support\ServiceProvider;

class AkademikServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Akademik';
    protected string $moduleNameLower = 'akademik';

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', $this->moduleNameLower);
    }

    public function register(): void
    {
        //
    }
}
