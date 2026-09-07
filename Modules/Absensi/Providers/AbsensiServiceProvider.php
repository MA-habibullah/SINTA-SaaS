<?php

namespace Modules\Absensi\Providers;

use Illuminate\Support\ServiceProvider;

class AbsensiServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Absensi';
    protected string $moduleNameLower = 'absensi';

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }

    public function register(): void
    {
        //
    }
}
