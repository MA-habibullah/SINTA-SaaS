<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;

class LokasiAbsensiSetting extends BaseTenantModel
{
    protected $table = 'absensi.lokasi_absensi_setting';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_lokasi',
        'latitude_pusat',
        'longitude_pusat',
        'radius_meter',
        'jam_masuk_normal',
        'jam_pulang_normal',
        'toleransi_terlambat_menit',
        'is_active',
    ];

    protected $casts = [
        'latitude_pusat'            => 'decimal:8',
        'longitude_pusat'           => 'decimal:8',
        'radius_meter'              => 'integer',
        'toleransi_terlambat_menit' => 'integer',
        'is_active'                 => 'boolean',
        'created_at'                => 'datetime',
        'updated_at'                => 'datetime',
    ];
}
