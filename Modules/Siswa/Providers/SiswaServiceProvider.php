<?php

namespace Modules\Siswa\Providers;

use Illuminate\Support\ServiceProvider;

class SiswaServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Siswa';
    protected string $moduleNameLower = 'siswa';

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
