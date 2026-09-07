<?php

namespace Modules\Perpustakaan\Providers;

use Illuminate\Support\ServiceProvider;

class PerpustakaanServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Perpustakaan';
    protected string $moduleNameLower = 'perpustakaan';

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
