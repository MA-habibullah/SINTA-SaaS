<?php

namespace Modules\Bk\Providers;

use Illuminate\Support\ServiceProvider;

class BkServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Bk';
    protected string $moduleNameLower = 'bk';

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
