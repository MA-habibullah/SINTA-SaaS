<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Modules\Core\Entities\User;
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
        'metode_presensi',  // 'Geolokasi_GPS', 'QR_Code', 'Manual_WaliKelas', 'Manual_GuruPiket'
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
        'bukti_izin_url',
        'nama_berkas_asli',
        'ukuran_berkas_kb',
        'status_verifikasi', // 'Menunggu', 'Terverifikasi', 'Ditolak'
        'diverifikasi_oleh',
        'waktu_verifikasi',
        'catatan_wali_kelas',
        'diinput_oleh',       // 'siswa', 'wali_kelas', 'guru_piket', 'admin'
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tanggal'          => 'date',
        'latitude'         => 'float',
        'longitude'        => 'float',
        'jarak_meter'      => 'integer',
        'akurasi_meter'    => 'float',
        'ukuran_berkas_kb' => 'float',
        'waktu_verifikasi' => 'datetime',
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

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh', 'id');
    }
}
