<?php

namespace Modules\Siswa\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\Siswa\Http\Controllers';

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'auth', 'tenant.guard'])
            ->namespace($this->moduleNamespace)
            ->group(base_path('Modules/Siswa/Routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/v1/siswa')
            ->middleware(['api', 'auth:sanctum'])
            ->namespace($this->moduleNamespace)
            ->group(base_path('Modules/Siswa/Routes/api.php'));
    }
}
