<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaAlumni extends BaseTenantModel
{
    protected $table = 'tracer.alumni';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tahun_lulus',
        'nomor_ijazah',
        'status_setelah_lulus', // 'kuliah', 'kerja', 'wirausaha', 'mencari_kerja', 'lainnya'
        'nama_kampus_perusahaan',
        'jurusan_posisi',
        'alamat_kontak_terbaru',
        'no_hp_terbaru',
        'email_terbaru',
    ];

    protected $casts = [
        'tahun_lulus' => 'integer',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
