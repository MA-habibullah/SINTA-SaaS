<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'core.tenants';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'npsn',
        'nama_sekolah',
        'jenjang',
        'status_sekolah',
        'alamat_jalan',
        'desa_kelurahan',
        'kecamatan',
        'kabupaten_kota',
        'provinsi',
        'kode_pos',
        'nomor_telepon',
        'email',
        'website',
        'logo_url',
        'favicon_url',
        'kepala_sekolah_nama',
        'kepala_sekolah_nip',
        'paket_aktif',
        'masa_aktif_hingga',
        'storage_limit_mb',
        'storage_used_bytes',
        'status_langganan',
        'is_active',
        'settings_json',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'storage_limit_mb'   => 'integer',
        'storage_used_bytes' => 'integer',
        'masa_aktif_hingga'  => 'datetime',
        'settings_json'      => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id', 'id');
    }

    public function identitas(): HasOne
    {
        return $this->hasOne(SekolahIdentitas::class, 'tenant_id', 'id');
    }
}
