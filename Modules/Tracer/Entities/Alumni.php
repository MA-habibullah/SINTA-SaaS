<?php

namespace Modules\Tracer\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumni extends BaseTenantModel
{
    protected $table = 'tracer.alumni';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'nama_alumni',
        'nisn',
        'nis',
        'tahun_lulus',
        'jurusan_asal',
        'status_tracer',
        'is_manual',
        'keterangan',
    ];

    protected $casts = [
        'tahun_lulus' => 'integer',
        'is_manual'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function riwayatKuliah(): HasMany
    {
        return $this->hasMany(RiwayatKuliah::class, 'alumni_id', 'id');
    }

    public function riwayatPekerjaan(): HasMany
    {
        return $this->hasMany(RiwayatPekerjaan::class, 'alumni_id', 'id');
    }
}
