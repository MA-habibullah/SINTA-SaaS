<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\User;

class PemetaanMapel extends BaseTenantModel
{
    protected $table = 'akademik.pemetaan_mapel';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_pemetaan_mapel',
        'kategori',
        'deskripsi',
        'is_active',
        'tahun_ajaran',
        'semester',
        'kelas_id',
        'kelompok_id',
        'mapel_id',
        'guru_id',
        'kkm',
        'jam_pelajaran',
        'jam_ke',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruangan',
        'warna_label',
        'catatan',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'kkm'           => 'decimal:2',
        'jam_pelajaran' => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id', 'id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id', 'id');
    }
}
