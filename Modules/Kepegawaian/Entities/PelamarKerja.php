<?php

namespace Modules\Kepegawaian\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelamarKerja extends BaseTenantModel
{
    protected $table = 'kepegawaian.pelamar_kerja';

    protected $fillable = [
        'id',
        'tenant_id',
        'lowongan_id',
        'nama_lengkap',
        'nik',
        'email',
        'no_hp',
        'pendidikan_terakhir',
        'jurusan',
        'ipk',
        'pengalaman_kerja',
        'berkas_cv_url',
        'status_tahapan', // 'Pendaftaran', 'Seleksi Administrasi', 'Tes Tertulis/Microteaching', 'Wawancara', 'Diterima', 'Ditolak'
        'catatan_seleksi',
        'is_active',
    ];

    protected $casts = [
        'ipk'        => 'decimal:2',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(LowonganKerja::class, 'lowongan_id', 'id');
    }
}
