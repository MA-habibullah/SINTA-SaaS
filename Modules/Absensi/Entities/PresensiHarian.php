<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;

class PresensiHarian extends BaseTenantModel
{
    protected $table = 'absensi.presensi_siswa_harian';

    protected $fillable = [
        'id',
        'tenant_id',
        'subjek_type', // 'siswa', 'gtk'
        'subjek_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status_kehadiran', // 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Terlambat'
        'metode_presensi', // 'QR_Code', 'Geolokasi_GPS', 'Manual_Guru'
        'latitude',
        'longitude',
        'foto_presensi_url',
        'keterangan',
    ];

    protected $casts = [
        'tanggal'   => 'date',
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
    ];
}
