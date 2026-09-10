<?php

namespace Modules\Tracer\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPekerjaan extends BaseTenantModel
{
    protected $table = 'tracer.riwayat_pekerjaan';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'alumni_id',
        'nama_alumni',
        'nisn',
        'nama_perusahaan',
        'posisi',
        'posisi_jabatan',
        'jenis_instansi',
        'pendapatan_bulanan',
        'status_kerja',
        'tahun_mulai',
        'tahun_selesai',
        'is_manual',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tahun_mulai'   => 'integer',
        'tahun_selesai' => 'integer',
        'is_manual'     => 'boolean',
        'is_active'     => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
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
