<?php

return [
    'tenant_model' => \Modules\Core\Entities\Tenant::class,
    'id_generator' => \Stancl\Tenancy\UUIDGenerator::class,
    'domain_model' => \Stancl\Tenancy\Database\Models\Domain::class,

    'bootstrappers' => [
        \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'pgsql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant_',
        'suffix' => '',
        'managers' => [
            'pgsql' => \Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class,
        ],
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/tenants/%tenant%',
            'public' => '%storage_path%/app/public/tenants/%tenant%',
        ],
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [
        \Stancl\Tenancy\Features\UserImpersonation::class,
        \Stancl\Tenancy\Features\TelescopeTags::class,
    ],

    'routes' => true,
    'migration_parameters' => [
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],
    'seeder_parameters' => [
        '--class' => 'TenantDatabaseSeeder',
    ],
];
