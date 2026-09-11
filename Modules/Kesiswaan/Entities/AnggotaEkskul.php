<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Siswa\Entities\Siswa;

class AnggotaEkskul extends BaseTenantModel
{
    protected $table = 'kesiswaan.anggota_ekskul';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'ekskul_id',
        'siswa_id',
        'tahun_ajaran_id',
        'semester',
        'jabatan',
        'nomor_anggota',
        'tanggal_bergabung',
        'status_keanggotaan',
        'catatan',
        'is_active',
    ];

    protected $casts = [
        'tanggal_bergabung' => 'date',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function ekskul(): BelongsTo
    {
        return $this->belongsTo(MasterEkskul::class, 'ekskul_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
