<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Kepegawaian\Entities\Gtk;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiPtkHarian extends BaseTenantModel
{
    protected $table = 'absensi.presensi_ptk_harian';

    protected $fillable = [
        'id',
        'tenant_id',
        'ptk_id',
        'nama_ptk',
        'nip',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status_kehadiran', // 'Hadir', 'Sakit', 'Izin', 'Cuti', 'Dinas Luar', 'Alpa', 'Terlambat'
        'metode_presensi',  // 'Geolokasi_GPS', 'Selfie_Kamera', 'Fingerprint', 'Manual'
        'latitude',
        'longitude',
        'jarak_meter',
        'status_geofence',  // 'Valid', 'Luar Radius'
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'latitude'    => 'decimal:8',
        'longitude'   => 'decimal:8',
        'jarak_meter' => 'integer',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'id');
    }
}
