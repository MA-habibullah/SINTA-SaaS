<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class UsulanBuku extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_usulan_buku';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_usulan_buku',
        'judul_buku',
        'pengarang',
        'penerbit',
        'pengusul_nama',
        'alasan_usulan',
        'status_usulan',
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
