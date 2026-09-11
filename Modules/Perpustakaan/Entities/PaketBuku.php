<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class PaketBuku extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_paket_buku';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_paket_buku',
        'kelas_id',
        'tahun_ajaran',
        'mata_pelajaran',
        'buku_id',
        'jumlah_distribusi',
        'status_distribusi',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'jumlah_distribusi' => 'integer',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
