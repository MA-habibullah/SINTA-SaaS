<?php

namespace Modules\Akademik\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kelas extends BaseTenantModel
{
    protected $table = 'akademik.kelas';

    protected $fillable = [
        'id',
        'tenant_id',
        'tingkat', // 'X', 'XI', 'XII'
        'nama_kelas', // 'X RPL 1'
        'kode_kelas',
        'jurusan_id',
        'tahun_ajaran_id',
        'wali_kelas_id',
        'kapasitas',
        'is_active',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'is_active' => 'boolean',
    ];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id', 'id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id', 'id');
    }
}
