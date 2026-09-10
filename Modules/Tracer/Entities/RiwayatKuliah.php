<?php

namespace Modules\Tracer\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKuliah extends BaseTenantModel
{
    protected $table = 'tracer.riwayat_kuliah';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'alumni_id',
        'nama_alumni',
        'nisn',
        'kampus_id',
        'prodi_id',
        'nama_kampus',
        'nama_prodi',
        'fakultas',
        'jenjang',
        'jalur_masuk',
        'jalur_masuk_id',
        'tahun_masuk',
        'tahun_lulus',
        'status_kuliah',
        'is_manual',
        'is_kampus_swasta',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tahun_masuk'      => 'integer',
        'tahun_lulus'      => 'integer',
        'is_manual'        => 'boolean',
        'is_kampus_swasta' => 'boolean',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'alumni_id', 'id');
    }
}
