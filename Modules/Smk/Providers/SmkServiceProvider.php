<?php

namespace Modules\Smk\Providers;

use Illuminate\Support\ServiceProvider;

class SmkServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Smk';
    protected string $moduleNameLower = 'smk';

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
