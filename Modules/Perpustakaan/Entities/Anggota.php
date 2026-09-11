<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class Anggota extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_anggota';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_anggota',
        'no_anggota',
        'nama_lengkap',
        'tipe_anggota',
        'identitas_no',
        'kelas_jurusan',
        'jenis_kelamin',
        'no_telepon',
        'alamat',
        'foto_url',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
