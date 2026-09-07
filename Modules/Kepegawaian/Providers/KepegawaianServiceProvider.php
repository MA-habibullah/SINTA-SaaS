<?php

namespace Modules\Kepegawaian\Providers;

use Illuminate\Support\ServiceProvider;

class KepegawaianServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Kepegawaian';
    protected string $moduleNameLower = 'kepegawaian';

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
