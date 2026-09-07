<?php

namespace Modules\Sistem\Providers;

use Illuminate\Support\ServiceProvider;

class SistemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
