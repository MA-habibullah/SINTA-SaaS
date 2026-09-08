<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKenaikanKelas extends BaseTenantModel
{
    protected $table = 'siswa.riwayat_kenaikan_kelas';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tahun_ajaran',
        'dari_kelas',
        'ke_kelas',
        'status',
        'catatan',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
