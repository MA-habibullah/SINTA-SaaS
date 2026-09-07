<?php

namespace Modules\Tracer\Providers;

use Illuminate\Support\ServiceProvider;

class TracerServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Tracer';
    protected string $moduleNameLower = 'tracer';

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
