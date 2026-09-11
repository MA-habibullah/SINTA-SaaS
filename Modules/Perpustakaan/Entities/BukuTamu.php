<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class BukuTamu extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_buku_tamu';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_buku_tamu',
        'nama_pengunjung',
        'tipe_pengunjung',
        'identitas_no',
        'kelas_instansi',
        'keperluan',
        'tanggal_kunjungan',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
