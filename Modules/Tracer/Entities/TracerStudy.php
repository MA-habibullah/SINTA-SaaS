<?php

namespace Modules\Tracer\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TracerStudy extends BaseTenantModel
{
    protected $table = 'tracer.kuesioner_alumni';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tahun_lulus',
        'status_saat_ini', // 'Bekerja', 'Wirausaha', 'Melanjutkan Kuliah', 'Mencari Kerja'
        'nama_instansi_usaha',
        'posisi_jabatan',
        'bidang_pekerjaan',
        'pendapatan_bulanan',
        'waktu_tunggu_bulan', // Waktu tunggu dapat kerja sejak lulus (bulan)
        'keselarasan_jurusan', // 'Sangat Selaras', 'Selaras', 'Kurang Selaras', 'Tidak Selaras'
        'saran_masukan_kurikulum',
    ];

    protected $casts = [
        'tahun_lulus'        => 'integer',
        'waktu_tunggu_bulan' => 'integer',
        'pendapatan_bulanan' => 'decimal:2',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
