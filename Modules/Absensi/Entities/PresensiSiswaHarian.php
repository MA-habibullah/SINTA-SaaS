<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiSiswaHarian extends BaseTenantModel
{
    protected $table = 'absensi.presensi_siswa_harian';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'nama_siswa',
        'nisn',
        'kelas_id',
        'nama_kelas',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status_kehadiran', // 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Dispensasi', 'Terlambat'
        'metode_presensi',  // 'Geolokasi_GPS', 'QR_Code', 'Fingerprint', 'Manual_Guru'
        'latitude',
        'longitude',
        'jarak_meter',
        'status_geofence',  // 'Valid', 'Luar Radius', 'Tanpa GPS'
        'akurasi_meter',
        'is_mock_location',
        'is_suspicious',
        'fraud_reason',
        'device_info',
        'ip_address',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tanggal'          => 'date',
        'latitude'         => 'float',
        'longitude'        => 'float',
        'jarak_meter'      => 'integer',
        'akurasi_meter'    => 'float',
        'is_mock_location' => 'boolean',
        'is_suspicious'    => 'boolean',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
