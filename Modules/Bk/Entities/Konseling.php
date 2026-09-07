<?php

namespace Modules\Bk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Konseling extends BaseTenantModel
{
    protected $table = 'bk.catatan_bk';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tanggal_konseling',
        'jenis_konseling', // 'Pribadi', 'Sosial', 'Belajar', 'Karier'
        'topik_masalah',
        'ringkasan_konseling',
        'solusi_tindak_lanjut',
        'status_kasus', // 'Terbuka', 'Dalam Pendampingan', 'Selesai'
        'guru_bk_nama',
    ];

    protected $casts = [
        'tanggal_konseling' => 'date',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
