<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class BacaDiTempat extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_baca_di_tempat';

    protected $fillable = [
        'id',
        'tenant_id',
        'eksemplar_id',
        'buku_id',
        'peminjam_id',
        'nama_pembaca',
        'tipe_pembaca',
        'ruang_baca',
        'waktu_baca',
        'is_active',
    ];

    protected $casts = [
        'waktu_baca' => 'datetime',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }

    public function eksemplar(): BelongsTo
    {
        return $this->belongsTo(Eksemplar::class, 'eksemplar_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
