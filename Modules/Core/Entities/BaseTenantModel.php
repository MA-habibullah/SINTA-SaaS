<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

abstract class BaseTenantModel extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The "booted" method of the model.
     * Secara otomatis menyuntikkan isolasi tenant_id di setiap query dan mutasi data.
     */
    protected static function booted(): void
    {
        // 1. Global Scope: Otomatis filter data berdasarkan tenant_id aktif di session / context
        static::addGlobalScope('tenant_isolation', function (Builder $builder) {
            $tenantId = session('tenant_id') ?? (app()->bound('currentTenantId') ? app('currentTenantId') : null);
            if (!empty($tenantId)) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        // 2. Event Creating: Otomatis isi tenant_id saat menyimpan record baru jika belum diset
        static::creating(function (Model $model) {
            $tenantId = session('tenant_id') ?? (app()->bound('currentTenantId') ? app('currentTenantId') : null);
            if (empty($model->tenant_id) && !empty($tenantId)) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    /**
     * Helper untuk query bypass tenant (hanya untuk Super Admin Platform)
     */
    public static function withoutTenant(): Builder
    {
        return static::withoutGlobalScope('tenant_isolation');
    }
}
