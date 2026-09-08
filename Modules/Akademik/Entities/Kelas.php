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
        'kode_kelas',
        'nama_kelas',
        'id_jenjang',
        'id_jurusan',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class, 'id_jenjang', 'id');
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan', 'id');
    }
}
