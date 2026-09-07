<?php

namespace Modules\Pdss\Providers;

use Illuminate\Support\ServiceProvider;

class PdssServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Pdss';
    protected string $moduleNameLower = 'pdss';

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
