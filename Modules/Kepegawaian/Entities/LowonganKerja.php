<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LowonganKerja extends BaseTenantModel
{
    protected $table = 'kepegawaian.lowongan_kerja';

    protected $fillable = [
        'id',
        'tenant_id',
        'judul_posisi',
        'jenis_pekerjaan', // 'Full Time', 'Part Time', 'Kontrak', 'Honorer'
        'kualifikasi_pendidikan',
        'persyaratan',
        'tanggal_buka',
        'tanggal_tutup',
        'kuota_dibutuhkan',
        'status', // 'Buka', 'Tutup', 'Draft'
        'is_active',
    ];

    protected $casts = [
        'tanggal_buka'     => 'date',
        'tanggal_tutup'    => 'date',
        'kuota_dibutuhkan' => 'integer',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function pelamar(): HasMany
    {
        return $this->hasMany(PelamarKerja::class, 'lowongan_id', 'id');
    }
}
