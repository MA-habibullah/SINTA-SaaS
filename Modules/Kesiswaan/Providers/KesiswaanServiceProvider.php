<?php

namespace Modules\Kesiswaan\Providers;

use Illuminate\Support\ServiceProvider;

class KesiswaanServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Kesiswaan';
    protected string $moduleNameLower = 'kesiswaan';

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        if (file_exists(__DIR__ . '/../Routes/api.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        }
    }

    public function register(): void
    {
        //
    }
}
