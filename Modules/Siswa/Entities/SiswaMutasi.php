<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaMutasi extends BaseTenantModel
{
    protected $table = 'siswa.registrasi';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'jenis_mutasi', // 'masuk', 'keluar'
        'tanggal_mutasi',
        'sekolah_asal_tujuan',
        'alasan_mutasi',
        'nomor_surat_mutasi',
        'file_surat_url',
        'status_approval',
    ];

    protected $casts = [
        'tanggal_mutasi' => 'date',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
