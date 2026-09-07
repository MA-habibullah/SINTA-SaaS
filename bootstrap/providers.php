<?php

return [
    App\Providers\AppServiceProvider::class,
    Nwidart\Modules\LaravelModulesServiceProvider::class,
    Stancl\Tenancy\TenancyServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
    Inertia\ServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,

    // Module Providers
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Siswa\Providers\SiswaServiceProvider::class,
    Modules\Akademik\Providers\AkademikServiceProvider::class,
    Modules\Keuangan\Providers\KeuanganServiceProvider::class,
    Modules\Bk\Providers\BkServiceProvider::class,
    Modules\Pdss\Providers\PdssServiceProvider::class,
    Modules\Perpustakaan\Providers\PerpustakaanServiceProvider::class,
    Modules\Persuratan\Providers\PersuratanServiceProvider::class,
    Modules\Sarpras\Providers\SarprasServiceProvider::class,
    Modules\Smk\Providers\SmkServiceProvider::class,
    Modules\Tracer\Providers\TracerServiceProvider::class,
    Modules\Absensi\Providers\AbsensiServiceProvider::class,
    Modules\Kepegawaian\Providers\KepegawaianServiceProvider::class,
    Modules\Cms\Providers\CmsServiceProvider::class,
];
