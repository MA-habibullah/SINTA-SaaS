<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiSiswaKbm extends BaseTenantModel
{
    protected $table = 'absensi.presensi_siswa_kbm';

    protected $fillable = [
        'id',
        'tenant_id',
        'jurnal_id',
        'siswa_id',
        'nama_siswa',
        'nisn',
        'status_kehadiran', // 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat'
        'catatan',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(JurnalMengajar::class, 'jurnal_id', 'id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
