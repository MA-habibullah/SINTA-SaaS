<?php

namespace Modules\Persuratan\Providers;

use Illuminate\Support\ServiceProvider;

class PersuratanServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Persuratan';
    protected string $moduleNameLower = 'persuratan';

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
