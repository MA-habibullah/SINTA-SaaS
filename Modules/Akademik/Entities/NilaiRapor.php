<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiRapor extends BaseTenantModel
{
    protected $table = 'akademik.detail_nilai_rapor';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'kelas_id',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
        'semester',
        'nilai_formatif',
        'nilai_sumatif_materi',
        'nilai_sumatif_akhir_semester',
        'nilai_akhir',
        'capaian_kompetensi_tinggi',
        'capaian_kompetensi_rendah',
        'deskripsi_nilai',
    ];

    protected $casts = [
        'nilai_formatif'               => 'decimal:2',
        'nilai_sumatif_materi'         => 'decimal:2',
        'nilai_sumatif_akhir_semester' => 'decimal:2',
        'nilai_akhir'                  => 'decimal:2',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id', 'id');
    }
}
