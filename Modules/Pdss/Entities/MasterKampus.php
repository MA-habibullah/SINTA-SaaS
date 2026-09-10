<?php

namespace Modules\Pdss\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class MasterKampus extends Model
{
    use HasUuids;

    protected $table = 'pdss.master_kampus';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_kampus',
        'jenis',
        'jenis_kampus',
        'akreditasi',
        'kota',
        'kota_kampus',
        'provinsi',
        'alamat',
        'alamat_kampus',
        'id_ptn',
        'kode_ptn',
        'web',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function withoutTenant(): Builder
    {
        return static::query();
    }

    public function prodi(): HasMany
    {
        return $this->hasMany(MasterKampusProdi::class, 'kampus_id');
    }

    public function prodis(): HasMany
    {
        return $this->hasMany(MasterKampusProdi::class, 'kampus_id');
    }
}

