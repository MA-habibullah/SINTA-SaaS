<?php

namespace Modules\Smk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UkkPenilaian extends BaseTenantModel
{
    protected $table = 'smk.ukk_penilaian';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'nama_siswa',
        'nisn',
        'jurusan',
        'tahun_ajaran',
        'nama_paket_soal',
        'penguji_internal',
        'penguji_eksternal_dudi',
        'skor_perencanaan',
        'skor_proses_kerja',
        'skor_hasil_produk',
        'skor_sikap_k3',
        'skor_total',
        'predikat', // 'Sangat Kompeten', 'Kompeten', 'Cukup Kompeten', 'Belum Kompeten'
        'nomor_sertifikat',
        'is_active',
    ];

    protected $casts = [
        'skor_perencanaan'   => 'decimal:2',
        'skor_proses_kerja'  => 'decimal:2',
        'skor_hasil_produk'  => 'decimal:2',
        'skor_sikap_k3'      => 'decimal:2',
        'skor_total'         => 'decimal:2',
        'is_active'          => 'boolean',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
