<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class Reservasi extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_reservasi';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_reservasi',
        'buku_id',
        'eksemplar_id',
        'peminjam_type',
        'peminjam_id',
        'nama_peminjam',
        'nomor_identitas',
        'tanggal_reservasi',
        'tanggal_berakhir',
        'status_reservasi',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tanggal_reservasi' => 'date',
        'tanggal_berakhir'  => 'date',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
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
