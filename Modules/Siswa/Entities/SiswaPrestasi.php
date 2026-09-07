<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaPrestasi extends BaseTenantModel
{
    protected $table = 'siswa.prestasi_siswa';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'jenis_prestasi', // 'akademik', 'non_akademik'
        'tingkat', // 'sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional'
        'nama_kegiatan_lomba',
        'peringkat_juara',
        'tahun',
        'penyelenggara',
        'sertifikat_url',
        'keterangan',
    ];

    protected $casts = [
        'tahun' => 'integer',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
