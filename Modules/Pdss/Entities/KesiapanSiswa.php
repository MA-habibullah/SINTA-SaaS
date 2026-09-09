<?php

namespace Modules\Pdss\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KesiapanSiswa extends BaseTenantModel
{
    protected $table = 'pdss.kesiapan_siswa';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'tahun_ajaran_id',
        'is_eligible',
        'nilai_rata_rata',
        'ranking_sekolah',
        'ranking_jurusan',
        'is_eligible_final',
        'catatan_override',
        'overridden_by',
        'overridden_at',
        'status_pengunduran_diri',
        'pengunduran_diri_id',
        'pengganti_siswa_id',
    ];

    protected $casts = [
        'is_eligible'             => 'boolean',
        'is_eligible_final'       => 'boolean',
        'status_pengunduran_diri' => 'boolean',
        'nilai_rata_rata'         => 'float',
        'ranking_sekolah'         => 'integer',
        'ranking_jurusan'         => 'integer',
        'overridden_at'           => 'datetime',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function penggantiSiswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'pengganti_siswa_id');
    }
}
