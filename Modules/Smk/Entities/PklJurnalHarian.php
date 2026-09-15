<?php

namespace Modules\Smk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PklJurnalHarian extends BaseTenantModel
{
    protected $table = 'smk.pkl_jurnal_harian';

    protected $fillable = [
        'id',
        'tenant_id',
        'penempatan_id',
        'siswa_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'kegiatan_pekerjaan',
        'alat_bahan_digunakan',
        'status_verifikasi', // 'Pending', 'Disetujui', 'Revisi'
        'catatan_pembimbing',
        'is_active',
    ];

    protected $casts = [
        'tanggal'    => 'date',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PrakerinPkl::class, 'penempatan_id', 'id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
