<?php

namespace Modules\Keuangan\Providers;

use Illuminate\Support\ServiceProvider;

class KeuanganServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Keuangan';
    protected string $moduleNameLower = 'keuangan';

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
