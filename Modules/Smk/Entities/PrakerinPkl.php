<?php

namespace Modules\Smk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrakerinPkl extends BaseTenantModel
{
    protected $table = 'smk.pkl_penempatan';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'nama_siswa',
        'nisn',
        'mitra_dudi_id',
        'nama_perusahaan',
        'kelas_id',
        'nama_kelas',
        'jurusan',
        'pembimbing_sekolah_nama',
        'pembimbing_dudi_nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'nilai_kinerja_dudi',
        'nilai_laporan_sekolah',
        'nilai_akhir_pkl',
        'status_pkl', // 'Sedang Berjalan', 'Selesai', 'Ditarik'
        'sertifikat_pkl_url',
        'catatan_evaluasi',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai'         => 'date',
        'tanggal_selesai'       => 'date',
        'nilai_kinerja_dudi'    => 'decimal:2',
        'nilai_laporan_sekolah' => 'decimal:2',
        'nilai_akhir_pkl'       => 'decimal:2',
        'is_active'             => 'boolean',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MitraDudi::class, 'mitra_dudi_id', 'id');
    }

    public function jurnal(): HasMany
    {
        return $this->hasMany(PklJurnalHarian::class, 'penempatan_id', 'id');
    }
}
