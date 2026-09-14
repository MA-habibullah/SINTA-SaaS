<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Entities\Tenant;

class Eksemplar extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_eksemplar';

    protected $fillable = [
        'id',
        'tenant_id',
        'bibliografi_id',
        'nama_perpus_eksemplar',
        'barcode',
        'no_induk',
        'nomor_panggil_item',
        'lokasi_rak',
        'status_kondisi',
        'tipe_koleksi',
        'sumber_perolehan',
        'harga_beli',
        'rfid_tag',
        'tanggal_perolehan',
        'catatan_kondisi',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'harga_beli'        => 'decimal:2',
        'tanggal_perolehan' => 'date',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'bibliografi_id');
    }

    public function sirkulasi(): HasMany
    {
        return $this->hasMany(Sirkulasi::class, 'eksemplar_id');
    }

    public function opnameItem(): HasMany
    {
        return $this->hasMany(OpnameItem::class, 'eksemplar_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
