<?php

namespace Modules\Sarpras\Providers;

use Illuminate\Support\ServiceProvider;

class SarprasServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Sarpras';
    protected string $moduleNameLower = 'sarpras';

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
